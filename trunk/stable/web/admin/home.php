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
   /// carga el cuerpo del script
   public function cuerpo($mod, $pag, &$datos)
   {
      $this->datos_servidor();
      $this->modulos();
      $this->modulos_abanq();
   }
   
   /// listado de modulos de facturaScripts
   private function modulos()
   {
      $resultado = $this->bd->select("SELECT modulo,titulo,version,comentario FROM fs_modulos ORDER BY modulo ASC;");
      if($resultado)
      {
         echo "<div class='lista'>M&oacute;dulos de facturaScripts</div>\n",
            "<table class='lista'>\n",
            "<tr class='destacado'>\n",
            "<td>M&oacute;dulo</td>\n",
            "<td>T&iacute;tulo</td>\n",
            "<td>Comentario</td>\n",
            "<td align='right'>Versi&oacute;n</td>\n",
            "</tr>\n";

         foreach($resultado as $col)
         {
            echo "<tr><td>" , $col['modulo'] , "</td><td>" , $col['titulo'] , "</td><td>" , $col['comentario'],
                    "</td><td align='right'>" , $col['version'] , "</td></tr>\n";
         }

         echo "</table>\n";
      }
   }
   
   /// datos del servidor web
   private function datos_servidor()
   {
      $pgversion = $this->bd->version();
      
      echo "<table class='datos'>\n" , "<tr>\n",
         "<td><b>Servidor:</b></td>\n",
         "<td><b>Versi&oacute;n PHP:</b></td>\n",
         "<td><b>Servidor BD:</b></td>\n",
         "<td><b>Versi&oacute;n PostgreSQL:</b></td>\n",
         "<td><b>Nombre BD:</b></td>\n" , "</tr>\n";

      echo "<tr>\n",
         "<td>" , php_uname('s') , " v" , php_uname('r') , "</td>\n",
         "<td>" , phpversion() , "</td>\n",
         "<td>" , FS_HOST , "</td>\n",
         "<td>" , $pgversion['server'] , "</td>\n",
         "<td>" , FS_DB_NAME , "</td>\n",
         "</tr>\n";

      if(PHP_OS == 'Linux')
      {
         echo "<tr><td colspan='5'>&nbsp;</td></tr>\n",
            "<tr class='destacado2'>\n",
            "<td colspan='2'>Memoria:</td>\n",
            "<td colspan='3'>Disco:</td>\n",
            "</tr>\n" , "<tr>\n",
            "<td colspan='2' valign='top'><pre>" , system('free') , "</pre></td>\n",
            "<td colspan='3' valign='top'><pre>" , system('df') , "</pre></td>\n",
            "</tr>\n" , "</table>\n";
      }
      else
      {
         echo "</table>\n";
      }
   }
   
   /// listado de modulos de facturalux/abanq
   private function modulos_abanq()
   {
      $resultado = $this->bd->select("SELECT idmodulo,version,descripcion FROM flmodules ORDER BY idmodulo ASC;");
      if($resultado)
      {
         echo "<div class='lista'>M&oacute;dulos de FacturaLux / Abanq</div>\n",
            "<table class='lista'>\n",
            "<tr class='destacado'>\n",
            "<td>M&oacute;dulo</td>\n",
            "<td>Descripci&oacute;n</td>\n",
            "<td align='right'>Versi&oacute;n</td>\n",
            "</tr>\n";

         foreach($resultado as $col)
         {
            echo "<tr><td>" , $col['idmodulo'] , "</td><td>" , $col['descripcion'] , "</td><td align='right'>",
                    $col['version'] , "</td></tr>\n";
         }

         echo "</table>\n";
      }
   }
}

?>