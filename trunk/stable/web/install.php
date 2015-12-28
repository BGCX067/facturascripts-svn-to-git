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

/// obtiene todos los datos del xml de la tabla
function get_xml_tabla($tabla, &$columnas, &$restricciones, &$indices)
{
   $retorno = true;

   $xml = simplexml_load_file('admin/tablas/' . $tabla . '.xml');
   if($xml)
   {
      if($xml->columna)
      {
         $i = 0;
         foreach($xml->columna as $col)
         {
            $columnas[$i]['nombre'] = $col->nombre;
            $columnas[$i]['tipo'] = $col->tipo;
            $columnas[$i]['nulo'] = $col->nulo;
            $columnas[$i]['defecto'] = $col->defecto;
            $i++;
         }
      }
      else /// debe de haber columnas, sino es un fallo
      {
         $retorno = false;
      }


      if($xml->restriccion)
      {
         $i = 0;
         foreach($xml->restriccion as $col)
         {
            $restricciones[$i]['nombre'] = $col->nombre;
            $restricciones[$i]['consulta'] = $col->consulta;
            $i++;
         }
      }
   }
   else
      $retorno = false;

   return($retorno);
}

/*
 * Compara las columnas de una tabla data, devuelve una sentencia sql
 * en caso de encontrar diferencias.
 */
function compara_columna($tabla, $col, $columnas)
{
   $consulta = "";
   $encontrada = false;

   if($columnas)
   {
      foreach($columnas as $col2)
      {
         if($col2['column_name'] == $col['nombre'])
         {
            $encontrada = true;

            if($col['defecto'] == "")
               $col['defecto'] = NULL;

            if($col2['column_default'] != $col['defecto'])
            {
               if($col['defecto'] != NULL)
                  $consulta .= "ALTER TABLE " . $tabla . " ALTER COLUMN " . $col['nombre'] . " SET DEFAULT " . $col['defecto'] . ";\n";
               else
                  $consulta .= "ALTER TABLE " . $tabla . " ALTER COLUMN " . $col['nombre'] . " DROP DEFAULT;\n";
            }

            if(($col2['is_nullable'] == "YES" AND $col['nulo'] == "NO") OR $col2['is_nullable'] == "NO" AND $col['nulo'] == "SI")
            {
               if($col['nulo'] == "SI")
                  $consulta .= "ALTER TABLE " . $tabla . " ALTER COLUMN " . $col['nombre'] . " DROP NOT NULL;\n";
               else
                  $consulta .= "ALTER TABLE " . $tabla . " ALTER COLUMN " . $col['nombre'] . " SET NOT NULL;\n";
            }
         }
      }
   }

   /// si no se ha encontrado
   if(!$encontrada)
   {
      $consulta .= "ALTER TABLE " . $tabla . " ADD COLUMN " . $col['nombre'] . " " . $col['tipo'];
      if($col['defecto'] != "")
         $consulta .= " DEFAULT " . $col['defecto'];
      if($col['nulo'] == "NO")
         $consulta .= " NOT NULL";
      $consulta .= ";\n";
   }

   return($consulta);
}

/*
 * Compara dos arrays de restricciones, devuelve una sentencia sql
 * en caso de encontrar diferencias.
 */
function compara_constraints($tabla, $c_nuevas, $c_old)
{
   $consulta = "";

   if($c_old)
   {
      if($c_nuevas)
      {
         /// comprobamos una a una las viejas
         foreach($c_old as $col)
         {
            $encontrado = false;

            foreach($c_nuevas as $col2)
            {
               if($col['restriccion'] == $col2['nombre'])
                  $encontrado = true;
            }

            if(!$encontrado)
            {
               /// eliminamos la restriccion
               $consulta .= "ALTER TABLE " . $tabla . " DROP CONSTRAINT " . $col['restriccion'] . ";\n";
            }
         }

         /// comprobamos una a una las nuevas
         foreach($c_nuevas as $col)
         {
            $encontrado = false;

            foreach($c_old as $col2)
            {
               if($col['nombre'] == $col2['restriccion'])
                  $encontrado = true;
            }

            if(!$encontrado)
            {
               /// añadimos la restriccion
               $consulta .= "ALTER TABLE " . $tabla . " ADD CONSTRAINT " . $col['consulta'] . ";\n";
            }
         }
      }
      else
      {
         /// eliminamos todas las restricciones
         foreach($c_old as $col)
            $consulta .= "ALTER TABLE " . $tabla . " DROP CONSTRAINT " . $col['restriccion'] . ";\n";
      }
   }
   else
   {
      if($c_nuevas)
      {
         /// añadimos todas las restricciones nuevas
         foreach($c_nuevas as $col)
            $consulta .= "ALTER TABLE " . $tabla . " ADD CONSTRAINT " . $col['consulta'] . ";\n";
      }
   }

   return($consulta);
}

/// devuelve la sentencia sql necesaria para crear una tabla con la estructura proporcionada
function genera_tabla($tabla, $xml_columnas, $xml_restricciones, $xml_indices)
{
   $consulta = "CREATE TABLE " . $tabla . " (\n";
   $i = FALSE;
   foreach($xml_columnas as $col)
   {
      /// añade la coma al final
      if($i)
         $consulta .= ",\n";
      else
         $i = TRUE;

      $consulta .= $col['nombre'] . " " . $col['tipo'];

      if($col['nulo'] == 'NO')
         $consulta .= " NOT NULL";
      if($col['defecto'] != "" AND !in_array($col['tipo'], array('serial', 'bigserial')))
         $consulta .= " DEFAULT " . $col['defecto'];
   }
   $consulta .= " );\n";

   /// añadimos las restricciones
   $consulta .= compara_constraints($tabla, $xml_restricciones, false);

   return($consulta);
}

/// comprueba y actualiza la estructura de la tabla si es necesario
function check_tabla(&$bd, $tabla, &$error)
{
      $retorno = true;
      $error = "";
      $consulta = "";
      $columnas = false;
      $restricciones = false;
      $indices = false;
      $xml_columnas = false;
      $xml_restricciones = false;
      $xml_indices = false;
      $i = false;

      if( get_xml_tabla($tabla, $xml_columnas, $xml_restricciones, $xml_indices) )
      {
         if( $bd->existe_tabla($tabla) )
         {
            $columnas = get_columnas($bd, $tabla);
            $restricciones = get_constraints($bd, $tabla);

            /// comparamos las columnas
            foreach($xml_columnas as $col)
               $consulta .= compara_columna($tabla, $col, $columnas);

            /// comparamos las restricciones
            $consulta .= compara_constraints($tabla, $xml_restricciones, $restricciones);

            /*
             * TODO comparar los indices
             */
         }
         else
         {
            /// generamos el sql para crear la tabla
            $consulta .= genera_tabla($tabla, $xml_columnas, $xml_restricciones, $xml_indices);
         }

         if($consulta != "")
         {
            if( !$bd->exec($consulta) )
            {
               $error = "Error";
               echo "<div class='error'>" . $consulta . "</div>";
            }
         }
      }
      else
      {
         $error = "Error con el xml";
         $retorno = false;
      }

      return($retorno);
}

/// devuelve un array con las columnas de una tabla dada
function get_columnas(&$bd, $tabla)
{
      $columnas = false;
      if($tabla)
      {
         $consulta = "SELECT column_name, column_default, is_nullable
            FROM information_schema.columns
            WHERE table_catalog = '" . FS_DB_NAME . "' AND table_name = '$tabla';";
         $columnas = $bd->select($consulta);
      }
      return($columnas);
}

/// devuelve una array con las restricciones de una tabla dada
function get_constraints(&$bd, $tabla)
{
      $constraints = false;
      if($tabla)
      {
         $consulta = "select c.conname as \"restriccion\"
            from pg_class r, pg_constraint c
            where r.oid = c.conrelid
            and relname = '$tabla';";
         $constraints = $bd->select($consulta);
      }
      return($constraints);
}


if( !(file_exists('config.php')) )
{
   echo "<?xml version=\"1.0\"?>\n",
      "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n",
      "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"es\">\n",
      "<head>\n<title>FacturaScripts - instalador</title>\n<meta name='robots' content='noindex,nofollow'/>\n",
      "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>\n",
      "<link rel='stylesheet' type='text/css' media='screen' href='css/minimal.css'/>\n",
      "<script type='text/javascript' src='js/funciones.js'> </script>\n",
      "</head>\n<body>\n",
      "<div class='pie'>Debes crear el archivo de configuraci&oacute;n '<b>config.php</b>' a partir del archivo de ejemplo '<b>config-sample.php</b>'.<br/>\n",
      "Una vez lo tengas, crea la base de datos y <a href='install.php'>comienza la instalaci&oacute;n</a> de facturaSCRIPTS.</div>";
}
else
{
   require('config.php');
   require('clases/db/postgresql.php');
   $bd = new db();
   
   echo "<?xml version=\"1.0\"?>\n",
      "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n",
      "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"es\">\n",
      "<head>\n<title>FacturaScripts - instalador</title>\n<meta name='robots' content='noindex,nofollow'/>\n",
      "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>\n",
      "<link rel='stylesheet' type='text/css' media='screen' href='css/minimal.css'/>\n",
      "<script type='text/javascript' src='js/funciones.js'> </script>\n",
      "</head>\n<body>\n";
   
   /// conectamos a la base de datos
   if( $bd->conectar() )
   {
      $instalacion = false;
      $error = false;
      
      echo "<div class='cuerpo'>\n",
         "<div class='destacado'><span>Instalador de FacturaScripts:</span></div>\n";

      /*
       * Comprobamos si existe la tabla usuarios de facturaScripts,
       * si no ya sabemos que hay que crear un nuevo usuario.
       */
      if( !$bd->existe_tabla('fs_usuarios') )
         $instalacion = TRUE;
      
      /// comprobamos la tablas tablas necesarias de facturascripts
      check_tabla($bd, 'fs_modulos', $error);
      
      if(!$error)
      {
         check_tabla($bd, 'fs_menu', $error);
         
         if(!$error)
         {
            check_tabla($bd, 'fs_usuarios', $error);
            
            if(!$error)
            {
               check_tabla($bd, 'fs_ack', $error);
               
               if(!$error)
                  check_tabla($bd, 'fs_opciones', $error);
               else
                  echo '<div class="error">' , $error , '</div>';
            }
            else
               echo '<div class="error">' , $error , '</div>';
         }
         else
            echo '<div class="error">' , $error , '</div>';
      }
      else
         echo '<div class="error">' , $error , '</div>';
      
      unset($bd);

      
      if($instalacion)
      {
         /// actualizamos los modulos sys y admin
         require_once('admin/modulo.php');
         $nuevo_modulo = new modulo_();
         $nuevo_modulo->actualizar();
         $bd = new db();
         
         if( $bd->conectar() )
         {
            /// insertamon el usuario admin
            $password = sha1('admin');
            $consulta = "INSERT INTO fs_usuarios (usuario,pass) VALUES ('admin','$password');";
            $consulta .= "INSERT INTO fs_ack (usuario,modulo) VALUES ('admin','admin');";

            if( $bd->exec($consulta) )
               echo "<div class='mensaje'>Usuario creado correctamente</div>";
            else
               echo "<div class='error'>Error al crear el usuario admin</div>";
            
            echo "<br/><br/>
               <div class='centrado'>
               <h1>¡ facturaScripts instalado satisfactoriamente !</h1> Ya puedes comenzar a usarlo.<br/>
               No te olvides de instalar los m&oacute;dulos y actualizar la base de datos.<br/><br/>
               <b>Usuario:</b> admin<br/><b>Contraseña:</b> admin<br/>
               <br/><a href='index.php'>Entrar</a>";
         }
         else
            echo "<div class='error'>Error al conectar a la base de datos</div>";
         
         echo "</div>";
      }
      else
      {
         echo "<br/><br/>\n",
            "<div class='centrado'><a href='index.php'>Entrar</a></div>";
      }
      
      echo "</div>\n";
   }
   else
      echo "<div class='copyright'>Error al conectar a la base de datos</div>\n";
}

echo "</body>\n</html>";

?>
