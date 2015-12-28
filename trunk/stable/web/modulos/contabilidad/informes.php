<?php
/*
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA.
 *
 * Autor: Carlos Garcia Gomez
*/

require("clases/script.php");
require_once("clases/empresa.php");
require_once("clases/ezpdf/class.ezpdf.php");


class script_ extends script
{
   private $empresa;
   private $fechas_facturas_cli;
   private $fechas_facturas_prov;

   public function __construct($ppal)
   {
      parent::__construct($ppal);

      $this->empresa = new empresa();
      $this->fechas_facturas_cli = $this->bd->select("SELECT DISTINCT to_char(fecha,'yyyy-mm') as mes FROM facturascli ORDER BY mes DESC;");
      $this->fechas_facturas_prov = $this->bd->select("SELECT DISTINCT to_char(fecha,'yyyy-mm') as mes FROM facturascli ORDER BY mes DESC;");
   }

   /// devuelve el titulo del script
   public function titulo()
   {
      return("Informes de contabilidad");
   }

   /// carga el cuerpo del script
   public function cuerpo($mod, $pag, &$datos)
   {
      echo "<table width='100%'><tr><td valign='top'>",
         $this->facturascli($mod, $pag),
         "</td><td width='50'></td><td valign='top'>",
         $this->facturasprov($mod, $pag) , "</td></tr></table>\n";
   }

   private function facturascli($mod, $pag)
   {
      if($this->fechas_facturas_cli)
      {
         echo "<div class='lista'>Facturas emitidas (clientes):</div>\n",
            "<br/><div class='lista2'>",
            "<form name='fcli' action='pdf.php?mod=" , $mod, "&amp;pag=" , $pag , "' method='post'>",
            "<input type='hidden' name='tipo' value='cli'/>Selecciona una fecha:<br/>\n",
            "<br/><select size='10' name='fecha'>\n";

         foreach($this->fechas_facturas_cli as $col)
         {
            if( !isset($_POST['fecha']) )
               echo "<option value='" , $col['mes'] , "'>" , $col['mes'] , "</option>\n";
            else if($_POST['fecha'] == $col['mes'])
               echo "<option value='" , $col['mes'] , "' selected>" , $col['mes'] , "</option>\n";
            else
               echo "<option value='" , $col['mes'] , "'>" , $col['mes'] , "</option>\n";
         }

         echo "</select>\n<input type='submit' value='ver'/></form>\n</div>\n";
      }
      else
         echo "<div class='mensaje'>No hay facturas emitidas</div>\n";
   }

   private function facturasprov($mod, $pag)
   {
      if($this->fechas_facturas_prov)
      {
         echo "<div class='lista'>Facturas recibidas (proveedores):</div>\n",
            "<br/><div class='lista2'>",
            "<form name='fprov' action='pdf.php?mod=" , $mod, "&amp;pag=" , $pag , "' method='post'>",
            "<input type='hidden' name='tipo' value='prov'/>Selecciona una fecha:<br/>\n",
            "<br/><select size='10' name='fecha'>\n";

         foreach($this->fechas_facturas_prov as $col)
         {
            if( !isset($_POST['fecha']) )
               echo "<option value='" , $col['mes'] , "'>" , $col['mes'] , "</option>\n";
            else if($_POST['fecha'] == $col['mes'])
               echo "<option value='" , $col['mes'] , "' selected>" , $col['mes'] , "</option>\n";
            else
               echo "<option value='" , $col['mes'] , "'>" , $col['mes'] , "</option>\n";
         }

         echo "</select>\n<input type='submit' value='ver'/></form>\n</div>\n";
      }
      else
         echo "<div class='mensaje'>No hay facturas emitidas</div>\n";
   }

   /// genera el documento pdf
   public function documento_pdf($mod, $pag, &$datos)
   {
      $pdf =& new Cezpdf('A4', 'landscape');
      $factura = false;
      $lineas = false;

      /// cambiamos ! por el simbolo del euro
      $euro_diff = array(33 => 'Euro');
      $pdf->selectFont("clases/ezpdf/fonts/Courier.afm",
              array('encoding' => 'WinAnsiEncoding',
                 'differences' => $euro_diff));


      if( $_POST['fecha'] )
      {
         $pdf->addInfo('Title', 'Facturas emitidas - ' . $_POST['fecha']);
         $pdf->addInfo('Subject', 'Factura emitidas de cliente del mes ' . $_POST['fecha']);
         $pdf->addInfo('Author', 'facturascripts');

         switch( $_POST['tipo'] )
         {
            case 'cli':
               $this->rellena_pdf_facturascli( $_POST['fecha'], $pdf );
               break;

            case 'prov':
               $this->rellena_pdf_facturasprov( $_POST['fecha'], $pdf );
               break;

            default:
               $pdf->ezText("Error: tipo no seleccionado.\n", 15);
               break;
         }
      }
      else
         $pdf->ezText("Error: fecha no proporcionada.\n", 15);

      $pdf->ezStream();
   }

   private function rellena_pdf_facturascli($fecha, $pdf)
   {
      $facturas = $this->bd->select("select * from facturascli where to_char(fecha,'yyyy-mm') = '$fecha' order by codigo ASC;");
      $empresa = $this->empresa->get();

      if($facturas)
      {
         $total_lineas = count( $facturas );
         $linea_actual = 0;
         $lppag = 32;
         $total = $base = $re = $iva16 = $iva18 = 0;
         $pagina = 1;

         while($linea_actual < $total_lineas)
         {
            if($linea_actual > 0)
            {
               $pdf->ezNewPage();
               $pagina++;
            }

            $pdf->ezText($empresa['nombre'] . " - Facturas emitidas de " . $this->fecha2texto($fecha) . ":\n\n", 14);

            $lineas = Array();
            $i = 0;
            
            for(;$i < $lppag && $linea_actual < $total_lineas;)
            {
               $lineas[$i]['serie'] = $facturas[$linea_actual]['codserie'];
               $lineas[$i]['factura'] = $facturas[$linea_actual]['numero'];
               $lineas[$i]['fecha'] = Date('d-m-Y', strtotime($facturas[$linea_actual]['fecha']));
               $lineas[$i]['descripcion'] = $facturas[$linea_actual]['nombrecliente'];
               $lineas[$i]['cifnif'] = $facturas[$linea_actual]['cifnif'];
               $base += $lineas[$i]['base'] = $facturas[$linea_actual]['neto'];
               $lineas[$i]['re'] = $facturas[$linea_actual]['recfinanciero'];
               $re += $lineas[$i]['totalre'] = $facturas[$linea_actual]['totalrecargo'];
               $total += $lineas[$i]['total'] = $facturas[$linea_actual]['totaleuros'];
               $this->completa_facturacli($lineas, $i, $facturas[$linea_actual]['idasiento'], $facturas[$linea_actual]['idfactura'], $iva16, $iva18);
               $linea_actual++;
            }


            /*
             * Creamos la tabla con las lineas de las factura
             */
            $titulo = array(
                'serie' => '<b>S</b>',
                'factura' => '<b>Fact.</b>',
                'asiento' => '<b>Asi.</b>',
                'fecha' => '<b>Fecha</b>',
                'subcuenta' => '<b>Subcuenta</b>',
                'descripcion' => '<b>Descripción</b>',
                'cifnif' => '<b>CIF/NIF</b>',
                'base' => '<b>Base Im.</b>',
                'iva' => '<b>% IVA</b>',
                'totaliva' => '<b>IVA</b>',
                're' => '<b>% RE</b>',
                'totalre' => '<b>RE</b>',
                'total' => '<b>Total</b>'
            );

            $opciones = array(
                'fontSize' => 8,
                'cols' => array(
                    'base' => array('justification' => 'right'),
                    'iva' => array('justification' => 'right'),
                    'totaliva' => array('justification' => 'right'),
                    're' => array('justification' => 'right'),
                    'totalre' => array('justification' => 'right'),
                    'total' => array('justification' => 'right')
                ),
                'shaded' => 0,
                'width' => 750
            );

            $pdf->ezTable($lineas, $titulo, '', $opciones);
            $pdf->ezText("\n", 14);

            /*
             * Rellenamos la última tabla
             */
            $titulo = array(
                'pagina' => '<b>Suma y sigue</b>',
                'base' => '<b>Base im.</b>',
                'iva16' => '<b>IVA16%</b>',
                'iva18' => '<b>IVA18%</b>',
                're' => '<b>RE</b>',
                'total' => '<b>Total</b>'
            );

            $filas = array(
                array(
                    'pagina' => $pagina . '/' . ceil($total_lineas / $lppag),
                    'base' => number_format($base, 2) . ' !',
                    'iva16' => number_format($iva16, 2) . ' !',
                    'iva18' => number_format($iva18, 2) . ' !',
                    're' => number_format($re, 2) . ' !',
                    'total' => number_format($total, 2) . ' !'
               )
            );

            $opciones = array(
                'cols' => array(
                    'base' => array('justification' => 'right'),
                    'iva16' => array('justification' => 'right'),
                    'iva18' => array('justification' => 'right'),
                    're' => array('justification' => 'right'),
                    'total' => array('justification' => 'right')
               ),
               'showLines' => 0,
               'width' => 750
            );

            $pdf->ezTable($filas, $titulo, '', $opciones);
         }
      }
   }

   private function fecha2texto($fecha)
   {
      $meses = Array(
          '01' => 'Enero',
          '02' => 'Febrero',
          '03' => 'Marzo',
          '04' => 'Abril',
          '05' => 'Mayo',
          '06' => 'Junio',
          '07' => 'Julio',
          '08' => 'Agosto',
          '09' => 'Septiembre',
          '10' => 'Octubre',
          '11' => 'Noviembre',
          '12' => 'Diciembre'
      );

      $aux = explode('-', $fecha);
      return $meses[ $aux[1] ] . ' de ' . $aux[0];
   }

   private function completa_facturacli(&$lineas, &$i, $idasiento, $idfactura, &$iva16, &$iva18)
   {
      $asiento = $this->bd->select("select * from co_asientos where idasiento = '$idasiento';");
      $partidas = $this->bd->select("select * from co_partidas where idasiento = '$idasiento';");
      $lineasiva = $this->bd->select("select * from lineasivafactcli where idfactura = '$idfactura';");
      $nuevoreg = False;

      if($asiento)
         $lineas[$i]['asiento'] = $asiento[0]['numero'];
      else
         $lineas[$i]['asiento'] = '-';

      if($partidas)
         $lineas[$i]['subcuenta'] = $partidas[0]['codsubcuenta'];
      else
         $lineas[$i]['subcuenta'] = '-';

      if($lineasiva)
      {
         foreach($lineasiva as $lin)
         {
            /// una linea por cada tipo de iva de la factura
            if($nuevoreg)
               $lineas[$i] = $lineas[$i-1];

            $lineas[$i]['iva'] = $lin['iva'];
            $lineas[$i]['totaliva'] = $lin['totaliva'];
            $i++;
            $nuevoreg = TRUE;

            switch( $lin['iva'] )
            {
               case '16':
                  $iva16 += $lin['totaliva'];
                  break;

               case '18':
                  $iva18 += $lin['totaliva'];
                  break;
            }
         }
      }
      else
      {
         $lineas[$i]['iva'] = '-';
         $lineas[$i]['totaliva'] = '-';
         $i++;
      }

      return $nuevoreg;
   }

   private function rellena_pdf_facturasprov($fecha, $pdf)
   {
      $facturas = $this->bd->select("select * from facturasprov where to_char(fecha,'yyyy-mm') = '$fecha' order by codigo ASC;");
      $empresa = $this->empresa->get();

      if($facturas)
      {
         $total_lineas = count( $facturas );
         $linea_actual = 0;
         $lppag = 32;
         $total = $base = $re = $iva16 = $iva18 = 0;
         $pagina = 1;

         while($linea_actual < $total_lineas)
         {
            if($linea_actual > 0)
            {
               $pdf->ezNewPage();
               $pagina++;
            }

            $pdf->ezText($empresa['nombre'] . " - Facturas recibidas de " . $this->fecha2texto($fecha) . ":\n\n", 14);

            $lineas = Array();
            $i = 0;

            for(;$i < $lppag && $linea_actual < $total_lineas;)
            {
               $lineas[$i]['serie'] = $facturas[$linea_actual]['codserie'];
               $lineas[$i]['factura'] = $facturas[$linea_actual]['numero'];
               $lineas[$i]['fecha'] = Date('d-m-Y', strtotime($facturas[$linea_actual]['fecha']));
               $lineas[$i]['descripcion'] = $facturas[$linea_actual]['nombre'];
               $lineas[$i]['cifnif'] = $facturas[$linea_actual]['cifnif'];
               $base += $lineas[$i]['base'] = $facturas[$linea_actual]['neto'];
               $lineas[$i]['re'] = $facturas[$linea_actual]['recfinanciero'];
               $re += $lineas[$i]['totalre'] = $facturas[$linea_actual]['totalrecargo'];
               $total += $lineas[$i]['total'] = $facturas[$linea_actual]['totaleuros'];
               $this->completa_facturaprov($lineas, $i, $facturas[$linea_actual]['idasiento'], $facturas[$linea_actual]['idfactura'], $iva16, $iva18);
               $linea_actual++;
            }


            /*
             * Creamos la tabla con las lineas de las factura
             */
            $titulo = array(
                'serie' => '<b>S</b>',
                'factura' => '<b>Fact.</b>',
                'asiento' => '<b>Asi.</b>',
                'fecha' => '<b>Fecha</b>',
                'subcuenta' => '<b>Subcuenta</b>',
                'descripcion' => '<b>Descripción</b>',
                'cifnif' => '<b>CIF/NIF</b>',
                'base' => '<b>Base Im.</b>',
                'iva' => '<b>% IVA</b>',
                'totaliva' => '<b>IVA</b>',
                're' => '<b>% RE</b>',
                'totalre' => '<b>RE</b>',
                'total' => '<b>Total</b>'
            );

            $opciones = array(
                'fontSize' => 8,
                'cols' => array(
                    'base' => array('justification' => 'right'),
                    'iva' => array('justification' => 'right'),
                    'totaliva' => array('justification' => 'right'),
                    're' => array('justification' => 'right'),
                    'totalre' => array('justification' => 'right'),
                    'total' => array('justification' => 'right')
                ),
                'shaded' => 0,
                'width' => 750
            );

            $pdf->ezTable($lineas, $titulo, '', $opciones);
            $pdf->ezText("\n", 14);

            /*
             * Rellenamos la última tabla
             */
            $titulo = array(
                'pagina' => '<b>Suma y sigue</b>',
                'base' => '<b>Base im.</b>',
                'iva16' => '<b>IVA16%</b>',
                'iva18' => '<b>IVA18%</b>',
                're' => '<b>RE</b>',
                'total' => '<b>Total</b>'
            );

            $filas = array(
                array(
                    'pagina' => $pagina . '/' . ceil($total_lineas / $lppag),
                    'base' => number_format($base, 2) . ' !',
                    'iva16' => number_format($iva16, 2) . ' !',
                    'iva18' => number_format($iva18, 2) . ' !',
                    're' => number_format($re, 2) . ' !',
                    'total' => number_format($total, 2) . ' !'
               )
            );

            $opciones = array(
                'cols' => array(
                    'base' => array('justification' => 'right'),
                    'iva16' => array('justification' => 'right'),
                    'iva18' => array('justification' => 'right'),
                    're' => array('justification' => 'right'),
                    'total' => array('justification' => 'right')
               ),
               'showLines' => 0,
               'width' => 750
            );

            $pdf->ezTable($filas, $titulo, '', $opciones);
         }
      }
   }

   private function completa_facturaprov(&$lineas, &$i, $idasiento, $idfactura, &$iva16, &$iva18)
   {
      $asiento = $this->bd->select("select * from co_asientos where idasiento = '$idasiento';");
      $partidas = $this->bd->select("select * from co_partidas where idasiento = '$idasiento';");
      $lineasiva = $this->bd->select("select * from lineasivafactprov where idfactura = '$idfactura';");
      $nuevoreg = False;

      if($asiento)
         $lineas[$i]['asiento'] = $asiento[0]['numero'];
      else
         $lineas[$i]['asiento'] = '-';

      if($partidas)
         $lineas[$i]['subcuenta'] = $partidas[0]['codsubcuenta'];
      else
         $lineas[$i]['subcuenta'] = '-';

      if($lineasiva)
      {
         foreach($lineasiva as $lin)
         {
            /// una linea por cada tipo de iva de la factura
            if($nuevoreg)
               $lineas[$i] = $lineas[$i-1];

            $lineas[$i]['iva'] = $lin['iva'];
            $lineas[$i]['totaliva'] = $lin['totaliva'];
            $i++;
            $nuevoreg = TRUE;

            switch( $lin['iva'] )
            {
               case '16':
                  $iva16 += $lin['totaliva'];
                  break;

               case '18':
                  $iva18 += $lin['totaliva'];
                  break;
            }
         }
      }
      else
      {
         $lineas[$i]['iva'] = '-';
         $lineas[$i]['totaliva'] = '-';
         $i++;
      }

      return $nuevoreg;
   }
}

?>
