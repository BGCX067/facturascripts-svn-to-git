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
require_once("clases/clientes.php");

class script_ extends script
{
   private $clientes;
   private $ranking;

   public function __construct($ppal)
   {
      parent::__construct($ppal);
      $this->generico = true;
      $this->clientes = new clientes();
      $this->get_ranking();
   }

   /// devuelve el titulo del script
   public function titulo()
   {
      return("Clientes");
   }

   /// codigo javascript
   public function javas()
   {
      ?>
      <script type="text/javascript">
      <!--

      function fs_onload()
      {
         document.clientes.q.focus();
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
      $datos = Array (
         'buscar' => '',
         'pagina' => '',
         'total' => ''
      );
      
      if( isset($_GET['q']) )
         $datos['buscar'] = rawurldecode($_GET['q']);
      
      if( isset($_GET['p']) )
         $datos['pagina'] = $_GET['p'];
      
      if( isset($_GET['t']) )
         $datos['total'] = $_GET['t'];
      
      return $datos;
   }

   /// carga el cuerpo del script
   public function cuerpo($mod, $pag, &$datos)
   {
      $clientes = false;

      echo "<div class='destacado'>\n",
         "<form name='clientes' action='ppal.php' method='get'>\n",
         "<input type='hidden' name='mod' value='" , $mod , "'/>\n",
         "<input type='hidden' name='pag' value='" , $pag , "'/>\n",
         "<span>Clientes</span>\n",
         "<input type='text' name='q' size='18' maxlength='18' value='" , $datos['buscar'] , "'/>\n",
         "<input type='submit' value='buscar'/>\n",
         "</form>\n" , "</div>\n";

      if($datos['buscar'] != '')
      {
         $clientes = $this->clientes->buscar($datos['buscar'], FS_LIMITE, $datos['pagina'], $datos['total']);
         if($clientes)
         {
            echo "<div class='lista'>Se encontraron <b>" , number_format($datos['total'], 0) , "</b> resultados para la busqueda '<b>",
               $datos['buscar'] , "</b>'</div>\n";
            $stats = $this->clientes->stats();
            $this->columnas($mod, $clientes, $stats);
            $this->paginar("ppal.php?mod=" . $mod . "&amp;pag=" . $pag . "&amp;q=" . $datos['buscar'], FS_LIMITE, $datos['pagina'], $datos['total']);
         }
         else
            echo "<div class='mensaje'>0 resultados</div>\n";
      }
      else
      {
         $clientes = $this->clientes->listar(FS_LIMITE, $datos['pagina'], $datos['total']);

         if($clientes)
         {
            $stats = $this->clientes->stats();
            $this->columnas($mod, $clientes, $stats);
            $this->paginar("ppal.php?mod=" . $mod . "&amp;pag=" . $pag, FS_LIMITE, $datos['pagina'], $datos['total']);
         }
         else
            echo "<div class='mensaje'>No hay clientes</div>\n";
      }
   }

   /// escribe las columas de la tabla
   private function columnas($mod, $clientes, $stats)
   {
      echo "<table class='lista'>\n",
         "<tr class='destacado'>\n",
         "<td>C&oacute;digo</td>\n",
         "<td>Nombre</td>\n",
         "<td>Tel&eacute;fonos</td>\n",
         "<td align='right'>Albaranes</td>\n",
         "<td align='right'>Facturas</td>\n",
         "</tr>\n";
      
      foreach($clientes as $col)
      {
         if($col['debaja'] == 't')
            echo "<tr class='bloqueado'>\n";
         else
         {
            if($this->ranking[$col['codcliente']] > 0 AND $this->ranking[$col['codcliente']] < 10)
               echo "<tr class='verde'>\n";
            else
               echo "<tr>\n";
         }

         echo "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=cliente&amp;cod=" , $col['codcliente'] , "' title='",
            $this->mostrar_ranking($col['codcliente']) , "'>" , $col['codcliente'] , "</a></td>\n",
            "<td>" , $col['nombre'] , "</td>\n",
            "<td>" , $this->mostrar_telefonos($col) , "</td>\n";
         
         if($stats[$col['codcliente']]['albaranes'] > 0)
         {
            echo "<td align='right'><a href='ppal.php?mod=" , $mod , "&pag=albaranescli&buscar=" , $col['codcliente'] , "&tipo=coc'>",
            number_format($stats[$col['codcliente']]['albaranes'], 0) , "</a></td>\n";
         }
         else
            echo "<td align='right'>-</td>\n";
         
         if($stats[$col['codcliente']]['facturas'] > 0)
         {
            echo "<td align='right'><a href='ppal.php?mod=contabilidad&pag=facturascli&buscar=" , $col['codcliente'] , "&tipo=coc'>",
               number_format($stats[$col['codcliente']]['facturas'], 0) , "</a></td>\n";
         }
         else
            echo "<td align='right'>-</td>\n";

         echo "</tr>";
      }
      
      echo "</table>\n";
   }

   private function mostrar_telefonos($cliente)
   {
      /// quitamos los espacios y guiones de los telefonos
      $cliente['telefono1'] = str_replace(' ', '', $cliente['telefono1']);
      $cliente['telefono1'] = str_replace('-', '', $cliente['telefono1']);
      $cliente['telefono2'] = str_replace(' ', '', $cliente['telefono2']);
      $cliente['telefono2'] = str_replace('-', '', $cliente['telefono2']);

      if($cliente['telefono1'] != '')
      {
         echo $cliente['telefono1'];

         if($cliente['telefono2'] != '')
            echo ' | ' , $cliente['telefono2'];

      }
      else
      {
         if($cliente['telefono2'] != '')
            echo $cliente['telefono2'];
         else
            echo '-';
      }
   }

   private function get_ranking()
   {
      $this->ranking = array();
      $clientes = $this->bd->select("SELECT * FROM clientes ORDER BY codcliente ASC;");
      $sum_albaranescli = $this->bd->select("SELECT codcliente, SUM(total) as total FROM albaranescli
                                             GROUP BY codcliente ORDER BY total DESC;");
      if( $clientes )
      {
         foreach($clientes as $cli)
            $this->ranking[ $cli['codcliente'] ] = -1;
         
         $i = 1;
         if( $sum_albaranescli )
         {
            foreach($sum_albaranescli as $sum)
            {
               $this->ranking[ $sum['codcliente'] ] = $i;
               $i++;
            }
         }
         
         foreach($clientes as $cli)
         {
            if($this->ranking[ $cli['codcliente'] ] < 0)
            {
               $this->ranking[ $cli['codcliente'] ] = $i;
               $i++;
            }
         }
      }
   }

   private function mostrar_ranking($codcliente)
   {
      if($this->ranking[$codcliente] > 0)
         echo '#' , $this->ranking[$codcliente] , ' del ranking';
      else
         echo 'No aparece en el ranking';
   }
}

?>