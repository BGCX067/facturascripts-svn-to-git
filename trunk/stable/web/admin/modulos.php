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

class script_ extends script
{
   public function __construct($ppal)
   {
      parent::__construct($ppal);
   }

   /// devuelve el titulo del script
   public function titulo()
   {
      return("M&oacute;dulos de facturaScripts");
   }
   
   /// captura las variables necesarias para el script enviadas por GET y POST
   public function datos()
   {
      $datos = array(
          'eliminar' => isset($_GET['e']),
          'modulo' => ''
      );
      
      if( isset($_GET['m']) )
         $datos['modulo'] = $_GET['m'];
      
      return $datos;
   }
   
   /// carga el cuerpo del script
   public function cuerpo($mod, $pag, &$datos)
   {
      /// ¿cargamos el modulo?
      if($datos['modulo'] != '')
      {
         /// seleccionamos la ruta apropiada
         if($datos['modulo'] == "admin")
         {
            require_once("admin/modulo.php");
            /// el constructor ya se encarga de instalar
            $nuevo_modulo = new modulo_();
         }
         else if( file_exists("modulos/" . $datos['modulo'] . "/modulo.php") )
         {
            require_once("modulos/" . $datos['modulo'] . "/modulo.php");
            /// el constructor ya se encarga de instalar
            $nuevo_modulo = new modulo_();
         }
         else
            $nuevo_modulo = FALSE;

         /// ¿borramos el modulo?
         if( $datos['eliminar'] )
         {
            if( $this->borrar_modulo($datos['modulo']) )
               echo "<div class='mensaje'>M&oacute;dulo <b>" , $datos['modulo'] , "</b> borrado correctamente</div>";
         }
         else if($nuevo_modulo) /// lo instalamos entonces
         {
            if( $nuevo_modulo->actualizar() )
               echo "<div class='mensaje'>M&oacute;dulo <b>" , $nuevo_modulo->get_nombre() , "</b> actualizado correctamente</div>";
         }
      }
      
      /// mostramos los modulos instalados
      $this->modulos($mod, $pag);
   }
   
   private function borrar_modulo($mod)
   {
      return $this->bd->exec("DELETE FROM fs_modulos WHERE modulo = '" . $mod . "';");
   }

   private function combinar($simple, $completo)
   {
      $retorno = Array();

      foreach($simple as $col)
      {
         $modulo = array(
             'modulo' => $col,
             'titulo' => '-',
             'version' => '-',
             'comentario' => '-',
             'local' => TRUE,
             'bd' => FALSE
         );
         $retorno[] = $modulo;
      }

      foreach($completo as $col)
      {
         $encontrado = FALSE;
         foreach($retorno as &$col2)
         {
            if($col2['modulo'] == $col['modulo'])
            {
               $col2['titulo'] = $col['titulo'];
               $col2['version'] = $col['version'];
               $col2['comentario'] = $col['comentario'];
               $col2['bd'] = TRUE;
               $encontrado = TRUE;
               break;
            }
         }
         if( !$encontrado )
         {
            $modulo = array(
                'modulo' => $col['modulo'],
                'titulo' => $col['titulo'],
                'version' => $col['version'],
                'comentario' => $col['comentario'],
                'local' => FALSE,
                'bd' => TRUE
            );
            $retorno[] = $modulo;
         }
      }

      return($retorno);
   }

   private function get_modulos()
   {
      $retorno = Array();
      $modulos = Array(
          0 => "admin"
      );

      $path = "modulos/";
      $directorio = dir($path);
      while($archivo = $directorio->read())
      {
         if(substr($archivo, 0, 1) != ".")
         {
            /// si existe un script para instalar el modulo lo agregamos
            if(file_exists($path . $archivo . "/modulo.php"))
               $modulos[] = $archivo;
         }
      }
      $directorio->close();

      /// agregamos al listado los modulos listados en la base de datos
      $instalados = $this->bd->select("SELECT modulo, titulo, version, comentario FROM fs_modulos ORDER BY modulo ASC;");
      if($instalados)
         $retorno = $this->combinar($modulos, $instalados);
      return($retorno);
   }
   
   /// lista de modulos de facturaScripts
   private function modulos($mod, $pag)
   {
      $modulos = $this->get_modulos();

      if($modulos)
      {
         echo "<div class='lista'>Listado de m&oacute;dulos ( disponibles / instalados ) de facturascripts</div>\n",
            "<table class='lista'>\n",
            "<tr class='destacado'>\n",
            "<td>M&oacute;dulo</td>\n",
            "<td>T&iacute;tulo</td>\n",
            "<td>Comentario</td>\n",
            "<td align='right'>Versi&oacute;n</td>\n",
            "<td width='100'></td>\n" , "</tr>\n";

         foreach($modulos as $col)
         {
            /// ¿esta instalado?
            if($col['bd'])
               echo "<tr>\n";
            else
               echo "<tr class='amarillo'>\n";
            
            if($col['local']) /// ¿estan los archivos en su directorio?
            {
               echo "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=" , $pag , "&amp;m=" , $col['modulo'] , "'>" , $col['modulo'] , "</a></td>\n",
                  "<td>" , $col['titulo'] , "</td>\n",
                  "<td>" , $col['comentario'] , "</td>\n",
                  "<td align='right'>" , $col['version'] , "</td>\n";
            }
            else
            {
               echo "<td>" , $col['modulo'] , "</td>\n",
                  "<td>" , $col['titulo'] , "</td>\n",
                  "<td>" , $col['comentario'] , "</td>\n",
                  "<td align='right'>" , $col['version'] , "</td>\n";
            }
            
            if($col['bd'] AND !in_array($col['modulo'], array('admin', 'sys')))
            {
               echo "<td align='center'><a class='cancelar' href='ppal.php?mod=" , $mod , "&amp;pag=" , $pag , "&amp;m=" , $col['modulo'],
                  "&amp;e=true'>eliminar</a></td>\n</tr>\n";
            }
            else
               echo "<td></td>\n</tr>\n";
         }
         
         echo "</table>\n";
      }
      else
         echo "<div class='error'>¿No hay m&oacute;dulos?</div>\n";
   }
}

?>