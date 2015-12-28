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
require_once("clases/articulos.php");
require_once("clases/albaranes_cli.php");
require_once("clases/agentes.php");
require_once("clases/reservas.php");

class script_ extends script
{
   private $articulos;
   private $albaranes;
   private $agentes;
   private $reservas;

   public function __construct($ppal)
   {
      parent::__construct($ppal);
      $this->articulos = new articulos();
      $this->albaranes = new albaranes_cli();
      $this->agentes = new agentes();
      $this->reservas = new reservas();
   }

   /// codigo javascript
   public function javas()
   {
      ?>
      <script type="text/javascript">
         function fs_onload()
         {
            document.articulos.buscar.focus();
         }
         function fs_unload() {}
      </script>
      <?php
   }
   
   /// carga el cuerpo del script
   public function cuerpo($mod, $pag, &$datos)
   {
      $this->articulos->show_search2($mod, '', '', '', false, false);
      echo "<table width='100%'>
         <tr>
            <td valign='top'>",$this->mostrar_movimientos_stock($mod, $pag),"</td>
            <td valign='top'>",$this->mostrar_albaranes($mod),"</td>
         </tr>
         </table>\n";
   }
   
   private function mostrar_movimientos_stock($mod, $pag)
   {
      echo "<form name='stock' action='ppal.php' method='get'>\n",
         "<div class='grupo'>\n",
         "<input type='hidden' name='mod' value='" , $mod , "'/>\n",
         "<input type='hidden' name='pag' value='" , $pag , "'/>\n",
         "<img src='images/system-search.png' alt='buscar'/>\n",
         "C&oacute;digo de barras\n",
         "<input type='text' name='codbar' size='18' maxlength='18' value=''/>\n",
         "<input type='submit' value='+1 al stock'/>\n",
         "</div>\n</form>\n";
      
      if( isset($_GET['codbar']) )
         $this->suma1_stock($mod, $_GET['codbar']);
      
      echo "<div class='lista'>&Uacute;ltimos movimientos de stock</div>\n";
      $movimientos = $this->articulos->lista_last_mov_stock('', FS_LIMITE-3);
      if($movimientos)
      {
         echo "<table class='lista'>\n",
         "<tr class='destacado'>\n",
         "<td>Usuario</td>\n",
         "<td>Referencia</td>\n",
         "<td align='center'>Movimiento</td>\n",
         "<td align='right'>Fecha</td>\n",
         "</tr>\n";
         foreach($movimientos as $col)
         {
            if($col['bloqueado'] == 't')
               echo "<tr class='bloqueado'>\n";
            else
               echo "<tr>\n";
            $motivo = explode('@', $col['motivo']);
            echo "<td>" , $motivo[0] , "</td>\n",
               "<td><a href='ppal.php?mod=",$mod,"&amp;pag=articulo&amp;ref=",rawurlencode($col['referencia']),"'>",$col['referencia'],"</a></td>\n",
               "<td align='center'>" , $col['cantidadini'] , " -> " , $col['cantidadfin'] , "</td>\n",
               "<td align='right'>" , Date('d-m-Y', strtotime($col['fecha'])) , "</td>\n",
               "</tr>\n";
         }
         echo "</table>\n";
      }
   }
   
   private function suma1_stock($mod, $codbar)
   {
      /// ¿cuantos articulos hay con ese codigo de barras?
      $articulos = $this->bd->select("SELECT * FROM articulos WHERE codbarras = '" . $codbar . "' AND bloqueado = 'false';");
      switch(count($articulos))
      {
         case 0:
            echo "<div class='error'>No hay ning&uacute;n art&iacute;culo con este c&oacute;digo de barras</div>\n";
            break;

         case 1:
            $error = false;
            if($this->articulos->sum_stock($articulos[0]['referencia'], $this->almacen, 1, $error))
               echo "<div class='mensaje'>Actualizado correctamente el stock de <b>" . $articulos[0]['referencia'] . "</b></div>\n";
            else
               echo "<div class='error'>Error al actualizar el stock de <b>" . $articulos[0]['referencia'] . "</b></div>\n";
            break;

         default:
            echo "<div class='error'>Hay varios art&iacute;culo con este c&oacute;digo de barras.
               Bloquea el que no utilices:\n";
            foreach($articulos as $a)
            {
               echo "<a href='ppal.php?mod=",$mod,"&amp;pag=articulo&amp;ref=",rawurlencode($a['referencia']),"'>",$a['referencia'],"</a><br/>\n";
            }
            echo "</div>";
            break;
      }
   }
   
   /// muestra los ultimos albaranes
   private function mostrar_albaranes($mod)
   {
      /// Últimos albaranes del agente
      $pagina = 0;
      $total = 0;
      $albaranes = $this->albaranes->buscar($this->codagente, 'age', FS_LIMITE, $pagina, $total);
      if($albaranes)
      {
         echo "<div class='lista'>Tus &uacute;ltimos albaranes de cliente</div>\n",
            "<table class='lista'>\n",
            "<tr class='destacado'>\n",
            "<td width='10'></td>\n",
            "<td>C&oacute;digo [n&uacute;mero 2]</td>\n",
            "<td>Cliente</td>\n",
            "<td align='right'>Total</td>\n",
            "<td align='right'>Fecha</td>\n",
            "</tr>\n";
         foreach($albaranes as $col)
         {
            echo "<tr>\n";
            if($col['ptefactura'] == 'f')
               echo "<td><span title='Facturado'/>F</span></td>\n";
            else
               echo "<td></td>\n";
            echo "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=albarancli&amp;id=" , $col['idalbaran'] , "'>" , $col['codigo'] , "</a>";
            if($col['numero2'] != '')
               echo ' [' , $col['numero2'] , "]</td>\n";
            else
               echo "</td>\n";
            echo "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=cliente&amp;cod=" , $col['codcliente'] , "'>" , $col['nombrecliente'] , "</a></td>\n",
               "<td align='right'>" , number_format($col['total'], 2) , " &euro;</td>\n",
               "<td align='right'>" , Date('d-m-Y', strtotime($col['fecha'])) , "</td>\n",
               "</tr>\n";
         }
         echo "</table>\n";
      }
   }
}

?>