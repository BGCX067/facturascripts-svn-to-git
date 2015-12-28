<?php
/*
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.
   
   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU General Public License for more details.
   
   You should have received a copy of the GNU General Public License
   along with this program; if not, write to the Free Software
   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA.
   
   Autor: Carlos Garcia Gomez
*/

require("clases/script.php");
require_once("clases/ejercicios.php");
require_once("clases/asientos.php");
require_once("clases/facturas_cli.php");

class script_ extends script
{
   private $ejercicios;
   private $asientos;
   private $facturas;
   private $asiento;
   private $partidas;

   public function __construct($ppal)
   {
      parent::__construct($ppal);

      $this->ejercicios = new ejercicios();
      $this->asientos = new asientos();
      $this->facturas = new facturas_cli();

      /// cargamos el asiento
      if( !$this->asientos->get($_GET['id'], $this->asiento) )
         $this->asiento = false;

      /// cargamos las partidas
      if( !$this->asientos->get_partidas($_GET['id'], $this->partidas) )
         $this->partidas = false;
   }

   /// devuelve el titulo del script
   public function titulo()
   {
      return("Asiento");
   }
   
   /// genera la url necesaria para recargar el script
   public function recargar($mod, $pag)
   {
      return("ppal.php?mod=" . $mod . "&amp;pag=" . $pag . "&amp;id=" . $_GET['id']);
   }
   
   /// carga el cuerpo del script
   public function cuerpo($mod, $pag, &$datos)
   {
      if( $this->asiento )
      {
         echo "<div class='destacado'><span>N&uacute;mero: " , $this->asiento['numero'],
            " | " , Date('d-m-Y', strtotime($this->asiento['fecha'])), "</span><br/>\n",
            "Ejercicio: " , $this->ejercicios->get_nombre($this->asiento['codejercicio']) , " | ";

         if($this->asiento['editable'] == 't')
            echo "Editable";
         else
            echo "No editable";

         echo " | " , $this->asiento['concepto'];

         if($this->asiento['documento'] != '' OR $this->asiento['tipodocumento'] != '')
         {
            echo " | Documento: " , $this->asiento['documento'],
            " | Tipo: " , $this->asiento['tipodocumento'];
         }

         echo "</div>\n";

         if( $this->partidas )
         {
            echo "<div class='lista'>Partidas</div>\n",
               "<table class='lista'>\n",
               "<tr class='destacado'>\n",
               "<td>Concepto</td>\n",
               "<td>Documento</td>\n",
               "<td>Subcuenta</td>\n",
               "<td align='right'>Debe</td>\n",
               "<td align='right'>Haber</td>\n",
               "<td align='right'>IVA</td>\n",
               "</tr>\n";
            
            foreach($this->partidas as $col)
            {
               echo "<tr>\n" , "<td>" , $col['concepto'] , "</td>\n";
               
               switch($col['tipodocumento'])
               {
                  case "Factura de cliente":
                     echo "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=facturacli&amp;id=" , $this->facturas->get_id($col['documento']) , "'>",
                     $col['documento'] , "</a></td>\n";
                     break;
                  
                  default:
                     echo "<td>" , $col['tipodocumento'] , "</td>\n";
                     break;
               }
               
               echo "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=cuenta&amp;tipo=s&amp;id=" , $col['idsubcuenta'] , "'>" , $col['codsubcuenta'] , "</a></td>\n",
                  "<td align='right'>" , number_format($col['debe'], 2) , " &euro;</td>\n",
                  "<td align='right'>" , number_format($col['haber'], 2) , " &euro;</td>\n",
                  "<td align='right'>" , number_format($col['iva'], 2) , " %</td>\n",
                  "</tr>\n";
            }

            echo "<tr><td colspan='6' align='right'>&nbsp;</td></tr>\n",
               "<tr class='amarillo'><td colspan='6' align='right'><b>Importe del asiento: " , number_format($this->asiento['importe'], 2) , " &euro;</b></td></tr>\n",
               "</table>\n";
         }
         else
            echo "<div class='error'>No se encontraron partidas relacionadas.</div>\n";
      }
      else
         echo "<div class='error'>Asiento no encontrada</div>\n";
   }
}

?>
