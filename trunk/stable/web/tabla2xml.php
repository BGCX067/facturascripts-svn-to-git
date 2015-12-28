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

require("config.php");
require("clases/db/postgresql.php");
require("clases/script.php");

class script_ extends script
{
   private $cadena_xml;
   private $archivo_xml;

   public function __construct($ppal)
   {
      parent::__construct($ppal);

      $this->cadena_xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<!--
    Document   : " . $_GET['tabla'] . ".xml
    Description:
        Estructura de la tabla " . $_GET['tabla'] . ".
-->

<tabla>
</tabla>\n";

      /// creamos el xml
      $this->archivo_xml = simplexml_load_string($this->cadena_xml);

      $this->genera_xml($_GET['tabla']);
   }

   /// carga el cuerpo del script
   public function cuerpo($mod, $pag, &$datos)
   {
      header( "content-type: application/xml; charset=UTF-8" );

      echo $this->archivo_xml->asXML();
   }

   private function genera_xml($tabla)
   {
      $columnas = Array();
      $restricciones = Array();

      if($tabla != "")
      {
         if( $this->bd->existe_tabla($tabla) )
         {
            /// obtenemos las columnas
            $columnas = $this->get_columnas($tabla);

            /// obtenemos las restricciones
            $restricciones = $this->get_constraints($tabla);

            /// añadimos las columnas
            if($columnas)
            {
               foreach($columnas as $col)
               {
                  $aux = $this->archivo_xml->addChild("columna");
                  $aux->addChild("nombre", $col['column_name']);

                  if( $col['character_maximum_length'] != "")
                  {
                     $aux->addChild("tipo", $col['data_type'] . "(" . $col['character_maximum_length'] . ")");
                  }
                  else
                  {
                     $aux->addChild("tipo", $col['data_type']);
                  }

                  if( $col['is_nullable'] == "YES")
                  {
                     $aux->addChild("nulo", "YES");
                  }
                  else
                  {
                     $aux->addChild("nulo", "NO");
                  }

                  if( $col['column_default'] != "")
                  {
                     $aux->addChild("defecto", $col['column_default']);
                  }
               }
            }

            /// añadimos las restricciones
            if($restricciones)
            {
               foreach($restricciones as $col)
               {
                  $aux = $this->archivo_xml->addChild("restriccion");
                  $aux->addChild("nombre", $col['restriccion']);
                  $aux->addChild("consulta", "");
               }
            }
         }
      }
   }

   /// devuelve un array con las columnas de una tabla dada
   private function get_columnas($tabla)
   {
      $columnas = false;

      if($tabla)
      {
         $consulta = "SELECT column_name, data_type, character_maximum_length, column_default, is_nullable
            FROM information_schema.columns
            WHERE table_catalog = '" . FS_DB_NAME . "' AND table_name = '$tabla'
            ORDER BY column_name ASC;";

         $columnas = $this->bd->select($consulta);
      }

      return($columnas);
   }

   /// devuelve una array con las restricciones de una tabla dada
   private function get_constraints($tabla)
   {
      $constraints = false;

      if($tabla)
      {
         $consulta = "select c.conname as \"restriccion\"
            from pg_class r, pg_constraint c
            where r.oid = c.conrelid
            and relname = '$tabla';";

         $constraints = $this->bd->select($consulta);
      }

      return($constraints);
   }
}

$mi_script = new script_("ppal");
$mod = "admin";
$pag = "";
$datos = Array();

/// comprobamos si hay enlace con la base de datos
if($mi_script->enlace_db())
{
   /// autenticamos al usuario
   if($mi_script->login($mod))
   {
      $mi_script->cuerpo($mod, $pag, $datos);
   }
   else
   {
      $mi_script->acceso_denegado();
   }
}
else
{
   $mi_script->error_db();
}

?>
