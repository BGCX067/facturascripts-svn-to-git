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
require_once("clases/ejercicios.php");
require_once("clases/facturas_prov.php");


class script_ extends script
{
   private $ejercicios;
   private $facturas;

   private $factura;
   private $lineas;
   private $partidas;
   private $lineas_iva;

   public function __construct($ppal)
   {
      parent::__construct($ppal);

      $this->ejercicios = new ejercicios();
      $this->facturas = new facturas_prov();

      /// cargamos la factura
      if( !$this->facturas->get($_GET['id'], $this->factura) )
         $this->factura = false;

      /// cargamos las lineas
      if( !$this->facturas->get_lineas($_GET['id'], $this->lineas) )
         $this->lineas = false;

      /// cargamos las partidas
      $this->partidas = $this->facturas->get_asientos($this->factura['codigo']);

      /// cargamos las lineas de IVA
      if( !$this->facturas->get_lineasiva($_GET['id'], $this->lineas_iva) )
         $this->lineas_iva = false;
   }

   /// devuelve el titulo del script
   public function titulo()
   {
      return("Factura de Proveedor");
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
         echo "<div class='destacado'><span>Factura de proveedor " , $this->factura['codigo'] , " &nbsp; - &nbsp; ",
            Date('d-m-Y', strtotime($this->factura['fecha'])) , "</span></div>\n";
         
         /// cabecera
         echo "<table class='datos'>\n" , "<tr>\n",
            "<td align='right' width='180'><b>Proveedor:</b></td>\n",
            "<td></td>\n",
            "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=proveedor&amp;cod=" , $this->factura['codproveedor'] , "'>" , $this->factura['nombre'] , "</a></td>\n",
            "<td align='right'><b>N&uacute;mero:</b></td>\n",
            "<td></td>\n",
            "<td>" , $this->factura['numero'] , "</td>\n",
            "</tr>\n" , "<tr>\n",
            "<td align='right'><b>Fecha:</b></td>\n",
            "<td></td>\n",
            "<td>" , Date('d-m-Y', strtotime($this->factura['fecha'])) , "</td>\n",
            "<td align='right'><b>Serie:</b></td>\n",
            "<td></td>\n",
            "<td>" , $this->factura['codserie'] , "</td>\n",
            "</tr>\n",
            "<tr>\n",
            "<td align='right'><b>De abono:</b></td>\n",
            "<td></td>\n" , "<td>";
          
         if($this->factura['deabono'] == 't')
            echo "Si";
         else
            echo "No";
         
         echo "</td>\n" , "<td align='right'><b>Ejercicio:</b></td>\n" , "<td></td>\n",
            "<td>" , $this->ejercicios->get_nombre($this->factura['codejercicio']) , "</td>\n",
            "</tr>\n" , "<tr>\n",
            "<td align='right'><b>Numeraci&oacute;n proveedor:</b></td>\n",
            "<td></td>\n",
            "<td>" , $this->factura['numproveedor'] , "</td>\n",
            "<td align='right'><b>Neto:</b></td>\n",
            "<td></td>\n",
            "<td>" , number_format($this->factura['neto'], 2) , " &euro;</td>\n",
            "</tr>\n" , "<tr>\n",
            "<td rowspan='2' align='right'><b>Observaciones:</b></td>\n",
            "<td rowspan='2'></td>\n",
            "<td rowspan='2'>" , $this->factura['observaciones'] , "</td>\n",
            "<td align='right'><b>IVA:</b></td>\n",
            "<td></td>\n",
            "<td>" , number_format($this->factura['totaliva'], 2) , " &euro;</td>\n",
            "</tr>\n" , "<tr>\n",
            "<td align='right'><b>Total:</b></td>\n",
            "<td></td>\n",
            "<td>" , number_format($this->factura['total'], 2) , " &euro;</td>\n",
            "</tr>\n" , "</table>\n";
         
         /// lineas
         $this->mostrar_lineas();

         echo "<table width='100%'>\n" , "<tr>\n",
            "<td valign='top' width='48%'>\n" , $this->mostrar_asientos() , "</td>\n",
            "<td></td>\n",
            "<td valign='top' width='48%'>\n" , $this->mostrar_lineasiva() , "</td>\n",
            "</tr>\n" , "</table>\n";
      }
      else
         echo "<div class='error'>Factura no encontrada</div>\n";
   }

   private function mostrar_lineas()
   {
      if( $this->lineas )
      {
         echo "<br/><table class='lista'>\n",
            "<tr class='destacado'>\n",
            "<td>Albar&aacute;n</td>\n",
            "<td>Referencia</td>\n",
            "<td>Descripci&oacute;n</td>\n",
            "<td align='right'>Total</td>\n",
            "</tr>\n";
         
         foreach($this->lineas as $col)
         {
            echo "<tr>\n",
               "<td><a href='ppal.php?mod=contabilidad&amp;pag=albaranprov&amp;id=" , $col['idalbaran'] , "'>" , $col['codigo'] , "</a></td>\n",
               "<td>" , $col['referencia'] , "</td>\n",
               "<td>" , $col['descripcion'] , "</td>\n",
               "<td align='right'>" , number_format($col['pvptotal'], 2) , " &euro;</td>\n",
               "</tr>\n";
         }

         echo "</table>\n";
      }
      else
         echo "<div class='error'>Esta factura no posee l&iacute;neas</div>";
   }

   private function mostrar_asientos()
   {
      if( $this->partidas )
      {
         echo "<div class='lista'>Asientos</div>\n",
            "<table class='lista'>\n",
            "<tr class='destacado'>\n",
            "<td>N&uacute;mero</td>\n",
            "<td>Concepto</td>\n",
            "<td>Ejercicio</td>\n",
            "<td align='right'>Fecha</td>\n",
            "</tr>\n";
         
         foreach($this->partidas as $col)
         {
            if($col['idasiento'] == $this->factura['idasiento'])
            {
               echo "<tr>\n",
                  "<td><a href='ppal.php?mod=contabilidad&amp;pag=asiento&amp;id=" , $col['idasiento'] , "'>" , $col['numero'] , "</a></td>\n",
                  "<td>" , $col['concepto'] , "</td>\n",
                  "<td>" , $col['codejercicio'] , "</td>\n",
                  "<td align='right'>" , Date('d-m-Y', strtotime($col['fecha'])) , "</td>\n",
                  "</tr>\n";
            }
            else
            {
               echo "<tr class='rojo'>\n",
                  "<td><a href='ppal.php?mod=contabilidad&amp;pag=asiento&amp;id=" , $col['idasiento'] , "'>" , $col['numero'] , "</a></td>\n",
                  "<td>" , $col['concepto'] , "</td>\n",
                  "<td>" , $col['codejercicio'] , "</td>\n",
                  "<td align='right'>" , Date('d-m-Y', strtotime($col['fecha'])) , "</td>\n",
                  "</tr>\n";
            }
         }

         echo "</table>\n";
      }
   }

   private function mostrar_lineasiva()
   {
      if( $this->lineas_iva )
      {
         echo "<div class='lista'>IVA</div>\n",
            "<table class='lista'>\n",
            "<tr class='destacado'>\n",
            "<td>ID</td>\n",
            "<td>Codimpuesto</td>\n",
            "<td align='right'>IVA</td>\n",
            "<td align='right'>Neto</td>\n",
            "<td align='right'>Total IVA</td>\n",
            "<td align='right'>Total Linea</td>\n",
            "</tr>\n";
         
         foreach($this->lineas_iva as $col)
         {
            echo "<tr>\n",
               "<td>" , $col['idlinea'] , "</td>\n",
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
}

?>