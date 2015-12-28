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
require_once("clases/asientos.php");

class script_ extends script
{
   private $asientos;

   public function __construct($ppal)
   {
      parent::__construct($ppal);

      $this->asientos = new asientos();
   }

   /// devuelve el titulo del script
   public function titulo()
   {
      return("Asientos");
   }
   
   /// devuelve el script javascript necesario para la pagina
   public function javas()
   {
      ?>
      <script type="text/javascript">
      <!--

      function fs_onload()
      {
         document.asientos.buscar.focus();
      }

      function fs_unload() {
      }

      //-->
      </script>
      <?php
   }
   
   /// captura las variables necesarias para el script enviadas por GET y POST
   public function datos()
   {
      $datos = array(
          'buscar' => ''
      );
      
      if( isset($_GET['buscar']) )
         $datos['buscar'] = $_GET['buscar'];
      
      if( isset($_GET['p']) )
         $datos['pagina'] = $_GET['p'];
      
      if( isset($_GET['t']) )
         $datos['total'] = $_GET['t'];
      
      return $datos;
   }
   
   /// carga el cuerpo del script
   public function cuerpo($mod, $pag, &$datos)
   {
      $partidas = false;
      $asientos = false;
      
      echo "<div class='destacado'>\n",
         "<form name='asientos' action='ppal.php' method='get'>\n",
         "<input type='hidden' name='mod' value='" , $mod , "'/>\n",
         "<input type='hidden' name='pag' value='" , $pag , "'/>\n",
         "<span>Asientos</span>\n",
         "<input type='text' name='buscar' size='18' maxlength='18' value='" , $datos['buscar'] , "'/>\n",
         "<input type='submit' value='buscar'/>",
         "</form>\n</div>\n";
      
      /// si hay una busqueda
      if($datos['buscar'] != '')
      {
         $partidas = $this->asientos->buscar($datos['buscar'], FS_LIMITE, $datos['pagina'], $datos['total']);
         if($partidas)
         {
            echo "<div class='lista'>Se encontraron " , number_format($datos['total'], 0) , " resultados</div>\n";
            
            $this->mostrar_partidas($mod, $partidas);
            $this->paginar("ppal.php?mod=" . $mod . "&amp;pag=" . $pag . "&amp;buscar=" . $datos['buscar'],
                    FS_LIMITE, $datos['pagina'], $datos['total']);
         }
         else
            echo "<div class='mensaje'>0 resultados</div>\n";
      }
      else
      {
         /// mostramos los ultimos asientos
         if( $this->asientos->ultimos(FS_LIMITE, $asientos, $datos['total'], $datos['pagina']) )
         {
            echo "<div class='lista'>&Uacute;ltimos asientos</div>\n";

            $this->mostrar_partidas($mod, $asientos);
            $this->paginar("ppal.php?mod=" . $mod . "&amp;pag=" . $pag, FS_LIMITE, $datos['pagina'], $datos['total']);
         }
         else
            echo "<div class='mensaje'>Nada que mostrar</div>\n";
      }
   }

   private function mostrar_partidas($mod, $partidas)
   {
      echo "<table class='lista'>\n",
         "<tr class='destacado'>\n",
         "<td>Asiento</td>\n",
         "<td align='right'>Debe</td>\n",
         "<td align='center' width='110'>Subcuenta</td>\n",
         "<td>Haber</td>\n",
         "<td align='right'>Fecha</td>\n",
         "</tr>\n";

      $aux = false;
      $gris = true;;

      foreach($partidas as $col)
      {
         if($col['idasiento'] != $aux)
            $gris = !$gris;

         if($gris)
            echo "<tr class='amarillo'>\n";
         else
            echo "<tr>\n";

         if($col['idasiento'] != $aux)
         {
            echo "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=asiento&amp;id=" , $col['idasiento'] , "'>" , $col['numero'] , "</a>\n",
               $this->mostrar_tipodoc($col['tipodocumento']) , " - " , $col['concepto'] , "</td>\n",
               "<td align='right'>" , $this->mostrar_importe($col['debe']) , "</td>\n",
               "<td align='center'><a href='ppal.php?mod=",$mod,"&amp;pag=cuenta&amp;tipo=s&amp;id=",$col['idsubcuenta'],"'>",$col['codsubcuenta'],"</a></td>\n",
               "<td>" , $this->mostrar_importe($col['haber']) , "</td>\n",
               "<td align='right'>" , Date('d-m-Y', strtotime($col['fecha'])) , "</td>\n",
               "</tr>\n";

            $aux = $col['idasiento'];
         }
         else
         {
            echo "<td></td>\n",
               "<td align='right'>" , $this->mostrar_importe($col['debe']) , "</td>\n",
               "<td align='center'><a href='ppal.php?mod=",$mod,"&amp;pag=cuenta&amp;tipo=s&amp;id=",$col['idsubcuenta'],"'>",$col['codsubcuenta'],"</a></td>\n",
               "<td>" , $this->mostrar_importe($col['haber']) , "</td>\n",
               "<td></td>\n",
               "</tr>\n";
         }
      }

      echo "</table>\n";
   }

   private function mostrar_tipodoc($tipodoc)
   {
      if($tipodoc != '')
         return " [ " . $tipodoc . " ] ";
      else
         return "";
   }

   private function mostrar_importe($importe)
   {
      if($importe > 0)
         return number_format($importe, 2) . " &euro;";
      else
         return "-";
   }
}

?>