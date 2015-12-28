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
require_once("clases/ezpdf/class.ezpdf.php");
require_once("clases/agentes.php");
require_once("clases/ejercicios.php");
require_once("clases/facturas_cli.php");

class impuesto
{
   public $iva;
   public $total;
   
   public function __construct($i)
   {
      $this->iva = $i;
      $this->total = 0;
   }
}

class script_ extends script
{
   private $ejercicios;
   private $facturas;
   private $agentes;
   private $factura;
   private $lineas;
   private $partidas;
   private $lineas_iva;

   public function __construct($ppal)
   {
      parent::__construct($ppal);

      $this->ejercicios = new ejercicios();
      $this->facturas = new facturas_cli();
      $this->agentes = new agentes();

      /// Cargamos la factura
      if( !$this->facturas->get($_GET['id'], $this->factura) )
         $this->factura = false;

      /// Cargamos las líneas
      if( !$this->facturas->get_lineas($_GET['id'], $this->lineas) )
         $this->lineas = false;

      /// Cargamos los asientos
      $this->partidas = $this->facturas->get_asientos($this->factura['codigo']);

      /// Cargamos el iva
      if( !$this->facturas->get_lineasiva($_GET['id'], $this->lineas_iva) )
         $this->lineas_iva = false;
   }

   private function modificar($factura, $observaciones)
   {
      $retorno = false;
      $consulta = "UPDATE facturascli SET observaciones = '" . $observaciones . "' WHERE idfactura = '" . $factura . "';";

      if( $this->bd->exec($consulta) )
      {
         $retorno = true;

         /// recargamos la factura
         $this->facturas->get($_GET['id'], $this->factura);
      }

      return($retorno);
   }

   /// devuelve el titulo del script
   public function titulo()
   {
      return("Factura de Cliente");
   }
   
   /// genera la url necesaria para recargar el script
   public function recargar($mod, $pag)
   {
      return("ppal.php?mod=" . $mod . "&amp;pag=" . $pag . "&amp;id=" . $_GET['id']);
   }
   
   /// carga el cuerpo del script
   public function cuerpo($mod, $pag, &$datos)
   {
      if( $this->factura )
      {
         /// ¿modificamos?
         if( !isset($_POST['opc']) )
            echo "";
         else if( $_POST['opc'] == 'modificar')
         {
            if( $this->modificar($this->factura['idfactura'], $_POST['observaciones']) )
               echo "<div class='mensaje'>Factura modificada correcatmente</div>\n";
            else
               echo "<div class='error'>Error al modificar la factura</div>\n";
         }
         
         echo "<div class='destacado'><span>Factura de Cliente " , $this->factura['codigo'] , " &nbsp; - &nbsp; ",
            Date('d-m-Y', strtotime($this->factura['fecha'])) , "</span>
            &nbsp; | &nbsp; <a href='pdf.php?mod=",$mod,"&amp;pag=",$pag,"&amp;id=",$_GET['id'],"' target='_blank'>Imprimir</a>
            </div>\n";
         
         /// cabecera
         echo "<form name='factura' action='ppal.php?mod=" , $mod , "&amp;pag=" , $pag , "&amp;id=" , $this->factura['idfactura'] , "' method='post'>\n",
            "<table class='datos'>\n" , "<tr>\n",
            "<td align='right' width='120'><b>Cliente:</b></td>\n",
            "<td></td>\n",
            "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=cliente&amp;cod=" , $this->factura['codcliente'] , "'>" , $this->factura['nombrecliente'] , "</a></td>\n",
            "<td align='right'><b>N&uacute;mero:</b></td>\n",
            "<td></td>\n",
            "<td>" , $this->factura['numero'] , "</td>\n",
            "</tr>\n" , "<tr>\n",
            "<td align='right'><b>Agente:</b></td>\n",
            "<td></td>\n",
            "<td>" , $this->agentes->get_nombre($this->factura['codagente']) , "</td>\n",
            "<td align='right'><b>Serie:</b></td>\n",
            "<td></td>\n",
            "<td>" , $this->factura['codserie'] , "</td>\n",
            "</tr>\n" , "<tr>\n",
            "<td align='right'><b>De abono:</b></td>\n",
            "<td></td>\n" , "<td>";
          
         if($this->factura['deabono'] == 't')
            echo "Si";
         else
            echo "No";
         
         echo "</td>\n" , "<td align='right'><b>Ejercicio:</b></td>\n" , "<td></td>\n",
            "<td>" , $this->ejercicios->get_nombre($this->factura['codejercicio']) , "</td>\n",
            "</tr>\n" , "<tr>\n",
            "<td align='right'><b>Observaciones:</b></td>\n",
            "<td></td>\n",
            "<td colspan='3'><textarea name='observaciones' rows='2' cols='80'>" , $this->factura['observaciones'] , "</textarea></td>\n",
            "<td align='right' valign='bottom'><input type='submit' name='opc' value='modificar'/></td>\n",
            "</tr>\n" , "</table>\n",
            "</form>\n";

         /// lineas
         $this->mostrar_lineas();

         echo "<table width='100%'>\n" , "<tr>\n",
            "<td valign='top' width='48%'>\n" , $this->mostrar_asientos() , "</td>\n",
            "<td></td>\n",
            "<td valign='top' width='48%'>\n" , $this->mostrar_iva() , "</td>\n",
            "</tr>\n" , "</table>\n";
      }
      else
         echo "<div class='error'>Factura no encontrada</div>\n";
   }

   private function mostrar_lineas()
   {
      echo "<br/><table class='lista'>\n",
         "<tr class='destacado'>\n",
         "<td>Albar&aacute;n</td>\n",
         "<td>Referencia</td>\n",
         "<td>Descripci&oacute;n</td>\n",
         "<td align='right'>Total</td>\n",
         "</tr>\n";

      if( $this->lineas )
      {
         foreach($this->lineas as $col)
         {
            if($col['pvptotal'] > 0)
               echo "<tr>\n";
            else
               echo "<tr class='amarillo'>\n";

            echo "<td><a href='ppal.php?mod=contabilidad&amp;pag=albarancli&amp;id=" , $col['idalbaran'] , "'>" , $col['numero'] , "</a>";
            
            if($col['numero2'] != '')
               echo " [" , $col['numero2'] , "]";

            echo "</td>\n" , "<td>" , $col['referencia'] , "</td>\n",
               "<td>" , $col['descripcion'] , "</td>\n",
               "<td align='right'>" , number_format($col['pvptotal'], 2) , " &euro;</td>\n",
               "</tr>\n";
         }
      }
      else
         echo "<tr class='rojo'><td colspan='4'>Esta factura no posee l&iacute;neas</td></tr>\n";

      echo "<tr><td colspan='4'>&nbsp;</td></tr>\n",
         "<tr class='gris'><td align='right' colspan='4'><b>Neto:</b> " , number_format($this->factura['neto'], 2) , " &euro; ",
         "&nbsp; | &nbsp; <b>IVA:</b> " , number_format($this->factura['totaliva'], 2) , " &euro; ",
         "&nbsp; | &nbsp; <b>Total:</b> " , number_format($this->factura['total'], 2) , " &euro;",
         "</td></tr>\n" , "</table>\n";
   }

   private function mostrar_asientos()
   {
      if( $this->partidas )
      {
         echo "<div class='lista'>Asientos</div>\n",
            "<table class='lista'>\n",
            "<tr class='destacado'>\n",
            "<td>Concepto</td>\n",
            "<td>Ejercicio</td>\n",
            "<td align='right'>Fecha</td>\n",
            "</tr>\n";
         
         foreach($this->partidas as $col)
         {
            if($col['idasiento'] == $this->factura['idasiento'])
               echo "<tr>\n";
            else
               echo "<tr class='rojo'>\n";

            echo "<td><a href='ppal.php?mod=contabilidad&amp;pag=asiento&amp;id=" , $col['idasiento'] , "'>" , $col['concepto'] , "</a></td>\n",
               "<td>" , $col['codejercicio'] , "</td>\n",
               "<td align='right'>" , Date('d-m-Y', strtotime($col['fecha'])) , "</td>\n",
               "</tr>\n";
         }

         echo "</table>\n";
      }
      else
         echo "<div class='error'>No se encontraron asientos relacionados</div>\n";
   }

   private function mostrar_iva()
   {
      if( $this->lineas_iva )
      {
         echo "<div class='lista'>IVA</div>\n",
            "<table class='lista'>\n",
            "<tr class='destacado'>\n",
            "<td>Codimpuesto</td>\n",
            "<td align='right'>IVA</td>\n",
            "<td align='right'>Neto</td>\n",
            "<td align='right'>Total IVA</td>\n",
            "<td align='right'>Total Linea</td>\n",
            "</tr>\n";

         foreach($this->lineas_iva as $col)
         {
            echo "<tr>\n",
               "<td>" , $col['codimpuesto'] , "</td>\n",
               "<td align='right'>" , number_format($col['iva'], 2) , " %</td>\n",
               "<td align='right'>" , number_format($col['neto'], 2) , " &euro;</td>\n",
               "<td align='right'>" , number_format($col['totaliva'], 2) , " &euro;</td>\n",
               "<td align='right'>" , number_format($col['totallinea'], 2) , " &euro;</td>\n",
               "</tr>\n";
         }

         echo "</table>\n";
      }
      else
         echo "<div class='error'>No se encontraron l&iacute;neas de IVA relacionadas</div>\n";
   }
   
   /// genera el documento pdf
   public function documento_pdf($mod, $pag, &$datos)
   {
      $pdf =& new Cezpdf('a4');
      $factura = False;
      $lineas = False;
      $impuestos = array();
      
      /// cambiamos ! por el simbolo del euro
      $euro_diff = array(33 => 'Euro');
      $pdf->selectFont("clases/ezpdf/fonts/Helvetica.afm",
              array('encoding' => 'WinAnsiEncoding',
                 'differences' => $euro_diff));
      
      if( $this->factura )
      {
         $pdf->addInfo('Title', 'Factura ' . $this->factura['codigo']);
         $pdf->addInfo('Subject', 'Factura de cliente ' . $this->factura['codigo']);
         $pdf->addInfo('Author', 'facturascripts');

         if( $this->lineas )
         {
            $lineasfact = count($this->lineas);
            $linea_actual = 0;
            $lppag = 35;
            $pagina = 1;
            
            /// desglosamos el iva
            foreach($this->lineas as $col)
            {
               $encontrado = FALSE;
               foreach($impuestos as $i)
               {
                  if($col['iva'] == $i->iva)
                  {
                     $i->total += floatval($col['iva'])*floatval($col['pvptotal'])/100;
                     $encontrado = TRUE;
                     break;
                  }
               }
               if( !$encontrado )
               {
                  $impuesto = new impuesto($col['iva']);
                  $impuesto->total += floatval($col['iva'])*floatval($col['pvptotal'])/100;
                  $impuestos[] = $impuesto;
               }
            }
            
            // Imprimimos las páginas necesarias
            while($linea_actual < $lineasfact)
            {
               /// salto de página
               if($linea_actual > 0) { $pdf->ezNewPage(); }
               
               $pdf->ezText("\n\n\n\n", 12);

               /*
                * Creamos la tabla del encabezado
                */
               $titulo = array(
                  'factura' => '',
                  'cliente' => ''
               );

               $filas[0]['factura'] = "<b>Factura:</b> " . $this->factura['codigo'] . "\n<b>Fecha:</b> " . Date('d-m-Y', strtotime($this->factura['fecha']));
               $filas[0]['factura'] .= "\n<b>NIF:</b> " . $this->factura['cifnif'];

               $filas[0]['cliente'] = $this->factura['nombrecliente'] . "\n" . $this->factura['direccion'] . "\n";
               $filas[0]['cliente'] .= "CP: " . $this->factura[codpostal] . "\n" . $this->factura['ciudad'] . ", " . $this->factura['provincia'];
               
               $opciones = array(
                  'cols' => array(
                     'factura' => array('justification' => 'left'),
                     'cliente' => array('justification' => 'right')
                  ),
                  'showLines' => 0,
                  'width' => 540
               );

               $pdf->ezTable($filas, $titulo, '', $opciones);
               $filas = false;
               $pdf->ezText("\n\n", 13);

               /*
                * Creamos la tabla con las lineas de la factura
                */
               $titulo = array(
                  'albaran' => '<b>Albarán</b>',
                  'descripcion' => '<b>Descripción</b>',
                  'pvp' => '<b>PVP</b>',
                  'dto' => '<b>DTO</b>',
                  'cantidad' => '<b>Cantidad</b>',
                  'importe' => '<b>Importe</b>'
               );

               $opciones = array(
                  'fontSize' => 8,
                  'cols' => array(
                     'albaran' => array('justification' => 'center'),
                     'pvp' => array('justification' => 'right'),
                     'dto' => array('justification' => 'right'),
                     'cantidad' => array('justification' => 'right'),
                     'importe' => array('justification' => 'right')
                  ),
                  'width' => 540,
                  'shaded' => 0
               );

               /// escribimos las lineas de la factura
               $saltos = 0;
               for($i = $linea_actual; (($linea_actual < ($lppag + $i)) AND ($linea_actual < $lineasfact));)
               {
                  if($this->lineas[$linea_actual]['numero2'] == '')
                     $filas[$linea_actual]['albaran'] = $this->lineas[$linea_actual]['numero'];
                  else
                     $filas[$linea_actual]['albaran'] = "[" . $this->lineas[$linea_actual]['numero2'] . "]";

                  if($this->lineas[$linea_actual]['referencia'] != '0')
                     $filas[$linea_actual]['descripcion'] = substr($this->lineas[$linea_actual]['referencia']. " - " .$this->lineas[$linea_actual]['descripcion'], 0, 40);
                  else
                     $filas[$linea_actual]['descripcion'] = substr($this->lineas[$linea_actual]['descripcion'], 0, 45);

                  $filas[$linea_actual]['pvp'] = number_format($this->lineas[$linea_actual]['pvpunitario'], 2) . " !";
                  $filas[$linea_actual]['dto'] = number_format($this->lineas[$linea_actual]['dtopor'], 0) . " %";
                  $filas[$linea_actual]['cantidad'] = $this->lineas[$linea_actual]['cantidad'];
                  $filas[$linea_actual]['importe'] = number_format($this->lineas[$linea_actual]['pvptotal'], 2) . " !";

                  $saltos++;
                  $linea_actual++;
               }

               $pdf->ezTable($filas, $titulo, '', $opciones);

               /*
                * Rellenamos el hueco que falta hasta donde debe aparecer la última tabla
                */
               if($this->factura['observaciones'] == '')
                  $salto = '';
               else
               {
                  $salto = "\n<b>Observaciones</b>: " . $this->factura['observaciones'];
                  $saltos += count( explode("\n", $this->factura['observaciones']) ) - 1;
               }
               
               if($saltos < $lppag)
               {
                  for(;$saltos < $lppag; $saltos++) { $salto .= "\n"; }
                  $pdf->ezText($salto, 10);
               }
               else if($linea_actual >= $lineasfact)
                  $pdf->ezText($salto, 10);
               else
                  $pdf->ezText("\n", 10);

               /*
                * Rellenamos la última tabla
                */
               $titulo = array(
                  'pagina' => '<b>Página</b>',
                  'neto' => '<b>Importe Neto</b>',
               );
               $filas = array(
                  array(
                     'pagina' => $pagina . '/' . ceil(count($this->lineas) / $lppag),
                     'neto' => number_format($this->factura['neto'], 2) . ' !',
                  )
               );
               $opciones = array(
                  'cols' => array(
                     'neto' => array('justification' => 'right'),
                  ),
                  'showLines' => 0,
                  'width' => 540
               );
               foreach($impuestos as $i)
               {
                  $titulo['iva'.$i->iva] = '<b>IVA'.$i->iva.'%</b>';
                  $filas[0]['iva'.$i->iva] = number_format($i->total, 2) . ' !';
                  $opciones['cols']['iva'.$i->iva] = array('justification' => 'right');
               }
               $titulo['liquido'] = '<b>Líquido (Euros)</b>';
               $filas[0]['liquido'] = number_format($this->factura['total'], 2) . ' !';
               $opciones['cols']['liquido'] = array('justification' => 'right');
               $pdf->ezTable($filas, $titulo, '', $opciones);
               
               $pagina++;
            }
         }
      }
      
      $pdf->ezStream();
   }
}

?>