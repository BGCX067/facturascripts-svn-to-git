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
require_once("clases/familias.php");

class script_ extends script
{
   private $familias;
   private $ranking;
   private $top_ten;
   public $buscar;
   public $pagina;
   public $total;

   public function __construct($ppal)
   {
      parent::__construct($ppal);

      $this->generico = true;
      $this->familias = new familias();
      $this->get_ranking();
      
      if( isset($_GET['buscar']) )
         $this->buscar = $_GET['buscar'];
      else
         $this->buscar = '';
      
      if( isset($_GET['p']) )
         $this->pagina = $_GET['p'];
      else
         $this->pagina = '';
      
      if( isset($_GET['t']) )
         $this->total = $_GET['t'];
      else
         $this->total = '';
   }

   /// devuelve el titulo del script
   public function titulo()
   {
      return("Familias");
   }

   /// codigo javascript
   public function javas()
   {
      ?>
      <script type="text/javascript">
      <!--

      function fs_onload()
      {
         document.familias.buscar.focus();
      }

      function fs_unload() {
      }

      //-->
      </script>
      <?php
   }

   /// carga el cuerpo del script
   public function cuerpo($mod, $pag, &$datos)
   {
      $error = false;
      
      echo "<form name='familias' action='ppal.php' method='get'>\n",
         "<input type='hidden' name='mod' value='" , $mod , "'/>\n",
         "<input type='hidden' name='pag' value='" , $pag , "'/>\n",
         "<div class='destacado'>\n",
         "<table width='100%'>\n",
         "<tr>\n",
         "<td><span>Familias</span>\n",
         "<input type='text' name='buscar' size='18' maxlength='18' value='" , $this->buscar , "'/>\n",
         "<input type='submit' value='Buscar'/>\n",
         "</td>\n",
         "<td align='right'><a href='#' class='boton' onclick='fs_nueva_familia()'>crear familia</a></td>",
         "</tr>\n",
         "</table>\n",
         "</div>\n",
         "</form>\n";
      
      /// dibujamos el popup
      echo "<div class='popup' id='popup_nueva_familia'>\n",
         "<h1>Crear Familia</h1>\n",
         "<form name='f_p_nueva_familia' action='ppal.php?mod=" , $mod , "&amp;pag=familias' method='post'>\n",
         "<input type='hidden' name='formulario' value='nueva'/>\n",
         "<table width='100%'>\n",
         "<tr>\n",
         "<td align='right'>C&oacute;digo:</td>",
         "<td><input type='text' name='codfamilia' value='' size='4' maxlength='4'/></td>\n",
         "</tr>\n",
         "<tr>\n",
         "<td align='right'>Descripci&oacute;n:</td>",
         "<td><input type='text' name='descripcion' value='' size='20' maxlength='20'/></td>\n",
         "</tr>\n",
         "<tr>\n",
         "<td><a href='#' class='cancelar' onclick='fs_nueva_familia_cerrar()'>cancelar<a></td>\n",
         "<td align='right'><input type='submit' value='Crear familia'/></td>\n",
         "</tr>\n",
         "</table>\n",
         "</form>\n",
         "</div>\n";
      
      if( isset($_POST['formulario']) )
      {
         switch($_POST['formulario'])
         {
            case "nueva":
               $familia['codfamilia'] = $_POST['codfamilia'];
               $familia['descripcion'] = $_POST['descripcion'];
               if( $this->familias->insert($familia, $error) )
               {
                  echo "<div class='mensaje'><a href='ppal.php?mod=",$mod,"&pag=familia&cod=",$_POST['codfamilia'],"'>Familia</a>
                     creada correctamente</div>";
               }
               else
                  echo "<div class='error'>" , $error , "</div>";
               break;
               
            case "borrar":
               if( $this->familias->eliminar($_POST['codfamilia'], $error) )
                  echo "<div class='mensaje'>Familia <b>" , $_POST['codfamilia'] , "</b> eliminada correctamente</div>";
               else
                  echo "<div class='error'>" , $error , "</div>";
               break;
         }
      }

      if($this->buscar != '')
      {
         $error = false;
         $familias = false;
         $this->familias->buscar($this->buscar, $familias, $error);
         $this->mostrar_familias($mod, $pag, $familias);
      }
      else
      {
         $familias = $this->familias->listar(FS_LIMITE*3, $this->pagina, $this->total);

         if( !isset($_GET['p']) )
            $this->mostrar_top_ten($mod);

         $this->listar_familias($mod, $pag, $familias);
      }
   }

   private function mostrar_familias($mod, $pag, &$familias)
   {
      if($familias)
      {
         echo "<div class='lista'><b>" , number_format(count($familias), 0) , "</b> resultados encontrados</div>\n",
            "<table class='lista'>\n",
            "<tr class='destacado'>\n",
            "<td width='70'>C&oacute;digo</td>\n",
            "<td>Descripci&oacute;n</td>\n",
            "<td>&Uacute;ltima actualizaci&oacute;n</td>\n",
            "<td align='right'>Art&iacute;culos</td>\n",
            "<td width='50'></td>\n",
            "</tr>\n";

         foreach($familias as $col)
         {
            echo "<tr>\n",
               "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=familia&amp;cod=" , $col['codfamilia'] , "' title='",
                    $this->mostrar_ranking($col['codfamilia']) , "'>" , $col['codfamilia'] , "</a></td>\n",
               "<td>" , $col['descripcion'] , "</td>\n";

            if($col['factualizado'] == '')
               echo "<td>-</td>\n";
            else
               echo "<td>" , Date('d-m-Y', strtotime($col['factualizado'])) , "</td>\n";

            if($col['articulos'] > 0)
            {
               echo "<td align='right'>" , number_format($col['articulos']) , "</td>\n",
                  "<td></td>\n";
            }
            else
            {
               echo "<td align='right'>-</td>\n",
                  "<td align='right'>\n",
                  "<form action='ppal.php?mod=" , $mod , "&amp;pag=" , $pag , "' method='post'>\n",
                  "<input type='hidden' name='codfamilia' value='" , $col['codfamilia'] , "'/>\n",
                  "<input type='hidden' name='formulario' value='borrar'/>\n",
                  "<input type='submit' value='x' title='borrar familia'/>\n",
                  "<form>\n</td>\n";
            }

            echo "</tr>\n";
         }

         echo "</table>\n";
         $this->paginar("ppal.php?mod=" . $mod . "&amp;pag=" . $pag . "&amp;buscar=" . $this->buscar, FS_LIMITE, $this->pagina, $this->total);
      }
      else
         echo "<div class='mensaje'>Ninguna familia encontrada</div>\n";
   }

   private function listar_familias($mod, $pag, &$familias)
   {
      if($familias)
      {
         echo "<div class='lista'>Todas las familias</div>\n",
            "<ul class='horizontal'>\n";
         
         foreach($familias as $col)
            echo "<li>
               [<a href='ppal.php?mod=",$mod,"&amp;pag=familia&amp;cod=",$col['codfamilia'],"' title='",
                    $this->mostrar_ranking($col['codfamilia']) , "'>" , $col['codfamilia'],
                     "</a>] " , $col['descripcion'] , "</li>\n";
         
         echo "</ul>\n";
         $this->paginar("ppal.php?mod=" . $mod . "&amp;pag=" . $pag . "&amp;buscar=" . $this->buscar, FS_LIMITE*3, $this->pagina, $this->total);
      }
      else
         echo "<div class='mensaje'>No hay familias creadas</div>\n";
   }

   private function get_ranking()
   {
      $consulta = "select codfamilia, sum(l.cantidad) as total from articulos a, lineasalbaranescli l
         where l.referencia = a.referencia group by codfamilia order by total DESC;";
      $resultado = $this->bd->select($consulta);
      if($resultado)
      {
         $i = 1;

         foreach($resultado as $col)
         {
            $this->ranking[$col['codfamilia']] = $i;

            /// añadimos al top 10
            if($i < 11)
               $this->top_ten[$i] = $col;

            $i++;
         }
      }
      else
         $this->ranking = false;
   }

   private function mostrar_ranking($familia)
   {
      if($this->ranking[$familia] > 0)
         echo '#' , $this->ranking[$familia] , ' del ranking';
      else
         echo 'No aparece en el ranking';
   }

   private function mostrar_top_ten($mod)
   {
      if( $this->top_ten )
      {
         echo "<div class='lista'>Top 10:</div>\n",
            "<ul class='horizontal'>\n";
         
         $i = 1;
         foreach($this->top_ten as $col)
         {
            if($i < 11)
            {
               echo "<li>#", $i , ":<a href='ppal.php?mod=" , $mod , "&amp;pag=familia&amp;cod=" , $col['codfamilia'] , "'>" , $col['codfamilia'],
                       "</a> (" , number_format($col['total'], 0) , " uds)</li>\n";
            }

            $i++;
         }

         echo "</ul>\n";
      }
   }
}

?>
