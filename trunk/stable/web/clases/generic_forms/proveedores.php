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
require_once("clases/proveedores.php");

class script_ extends script
{
   private $proveedores;
   private $ranking;

   public function __construct($ppal)
   {
      parent::__construct($ppal);

      $this->generico = true;
      $this->proveedores = new proveedores();
      $this->get_ranking();
   }

   /// devuelve el titulo del script
   public function titulo()
   {
      return("Proveedores");
   }

   /// codigo javascript
   public function javas()
   {
      ?>
      <script type="text/javascript">
      <!--

      function fs_onload()
      {
         document.proveedores.q.focus();
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
      $datos = Array(
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
      $proveedores = false;

      echo "<div class='destacado'>\n",
         "<form name='proveedores' action='ppal.php' method='get'>\n",
         "<input type='hidden' name='mod' value='" , $mod , "'/>\n",
         "<input type='hidden' name='pag' value='" , $pag , "'/>\n",
         "<span>Proveedores</span>\n",
         "<input type='text' name='q' size='18' maxlength='18' value='" , $datos['buscar'] , "'/>\n",
         "<input type='submit' value='Buscar'/>\n",
         "</form>\n</div>\n";

      if($datos['buscar'] != '')
      {
         $proveedores = $this->proveedores->buscar($datos['buscar'], FS_LIMITE, $datos['pagina'], $datos['total']);
         if($proveedores)
         {
            echo "<div class='lista'>Se encontraron <b>" , number_format($datos['total'], 0) , "</b>\n",
            "resultados para la busqueda '<b>" , $datos['buscar'] , "</b>'</div>\n";
            
            $albaranes = $this->proveedores->albaranes();
            $facturas = $this->proveedores->facturas();
            $this->columnas($mod, $proveedores, $albaranes, $facturas);
            $this->paginar("ppal.php?mod=" . $mod . "&amp;pag=" . $pag . "&amp;q=" . $datos['buscar'], FS_LIMITE, $datos['pagina'], $datos['total']);
         }
         else
            echo "<div class='mensaje'>0 resultados</div>\n";
      }
      else
      {
         $proveedores = $this->proveedores->listar(FS_LIMITE, $datos['pagina'], $datos['total']);
         if($proveedores)
         {
            $albaranes = $this->proveedores->albaranes();
            $facturas = $this->proveedores->facturas();
            $this->columnas($mod, $proveedores, $albaranes, $facturas);
            $this->paginar("ppal.php?mod=" . $mod . "&amp;pag=" . $pag, FS_LIMITE, $datos['pagina'], $datos['total']);
         }
         else
            echo "<div class='mensaje'>No hay proveedores</div>\n";
      }
   }

   /// escribe las columnas de la tabla
   private function columnas($mod, $proveedores, $albaranes, $facturas)
   {
      echo "<table class='lista'>\n",
         "<tr class='destacado'>\n",
         "<td>C&oacute;digo</td>\n",
         "<td>Nombre</td>\n",
         "<td>Tel&eacute;fonos</td>\n",
         "<td align='right'>Albaranes</td>\n",
         "<td align='right'>Facturas</td>\n",
         "</tr>\n";

      foreach($proveedores as $col)
      {
         if($this->ranking[$col['codproveedor']] > 0)
         {
            if($this->ranking[$col['codproveedor']] < 10)
               echo "<tr class='verde'>\n";
            else
               echo "<tr>\n";
         }
         else
            echo "<tr class='amarillo'>\n";

         echo "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=proveedor&amp;cod=" , $col['codproveedor'] , "' title='",
            $this->mostrar_ranking($col['codproveedor']) , "'>" , $col['codproveedor'] , "</a></td>\n",
            "<td>" , $col['nombre'] , "</td>\n",
            "<td>" , $this->mostrar_telefonos($col) , "</td>\n";

         if($albaranes[$col['codproveedor']] > 0)
         {
            echo "<td align='right'><a href='ppal.php?mod=" , $mod , "&amp;pag=albaranesprov&amp;buscar=" , $col['codproveedor'] , "&tipo=cop'>",
               number_format($albaranes[$col['codproveedor']], 0) , "</a></td>\n";
         }
         else
            echo "<td align='right'>-</td>\n";
         
         if($facturas[$col['codproveedor']] > 0)
         {
            echo "<td align='right'><a href='ppal.php?mod=contabilidad&amp;pag=facturasprov&amp;buscar=" , $col['codproveedor'] , "&tipo=cop'>",
               number_format($facturas[$col['codproveedor']], 0) , "</a></td>\n";
         }
         else
            echo "<td align='right'>-</td>\n";
         
         echo "</tr>\n";
      }
      
      echo "</table>\n";
   }

   private function mostrar_telefonos($proveedor)
   {
      /// quitamos los espacios y guiones de los telefonos
      $proveedor['telefono1'] = str_replace(' ', '', $proveedor['telefono1']);
      $proveedor['telefono1'] = str_replace('-', '', $proveedor['telefono1']);
      $proveedor['telefono2'] = str_replace(' ', '', $proveedor['telefono2']);
      $proveedor['telefono2'] = str_replace('-', '', $proveedor['telefono2']);

      if($proveedor['telefono1'] != '')
      {
         echo $proveedor['telefono1'];

         if($proveedor['telefono2'] != '')
            echo ' | ' , $proveedor['telefono2'];

      }
      else
      {
         if($proveedor['telefono2'] != '')
            echo $proveedor['telefono2'];
         else
            echo '-';
      }
   }

   private function get_ranking()
   {
      $this->ranking = array();
      $proveedores = $this->bd->select("SELECT * FROM proveedores ORDER BY codproveedor ASC;");
      $sum_albaranesprov = $this->bd->select("SELECT codproveedor, SUM(total) as total FROM albaranesprov
                                              GROUP BY codproveedor ORDER BY total DESC;");
      if($proveedores)
      {
         foreach($proveedores as $p)
            $this->ranking[ $p['codproveedor'] ] = -1;
         
         $i = 1;
         if( $sum_albaranesprov )
         {
            foreach($sum_albaranesprov as $sum)
            {
               $this->ranking[ $sum['codproveedor'] ] = $i;
               $i++;
            }
         }
         
         foreach($proveedores as $p)
         {
            if($this->ranking[ $p['codproveedor'] ] < 0)
            {
               $this->ranking[ $p['codproveedor'] ] = $i;
               $i++;
            }
         }
      }
   }

   private function mostrar_ranking($codproveedor)
   {
      if($this->ranking[$codproveedor] > 0)
         echo '#' , $this->ranking[$codproveedor] , ' del ranking';
      else
         echo 'No aparece en el ranking';
   }
}

?>