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
require_once("clases/ejercicios.php");
require_once("clases/opciones.php");

class script_ extends script
{
   private $traza;

   public function __construct($ppal)
   {
      parent::__construct($ppal);
      $this->traza = isset($_GET['debug']);
   }

   /// devuelve el titulo del script
   public function titulo()
   {
      return("Actualizar la base de datos");
   }

   /// captura las variables necesarias para el script enviadas por GET y POST
   public function datos()
   {
      $datos = array(
          'p' => isset($_GET['p']),
          't' => ''
      );
      
      if( isset($_GET['t']) )
         $datos['t'] = $_GET['t']; /// nombre de la tabla a actualizar
      
      return($datos);
   }
   
   /// carga el cuerpo del script
   public function cuerpo($mod, $pag, &$datos)
   {
      $tablas = False;
      
      if($this->traza)
         $traza = "&debug=true";
      else
         $traza = "";
      
      $tablas = $this->get_xml_tablas();
      if($tablas)
      {
         /// comprobamos y actualizamos las opciones de facturaScripts
         $this->update_opciones();

         /// comprobamos y actualizamos las secuencias
         $this->update_secuencias();

         /// ¿procesamos las tablas?
         if($datos['p'] AND $datos['t'])
         {
            echo "<div id='progreso' class='centrado'>",
               "<img src='images/progreso.gif' align='middle' alt='en progreso'/> Actualizando la base de datos ...",
               "</div>\n";
            $procesamos = true;
         }
         else
            $procesamos = false;

         /// sig_tabla coje el valor de la nueva tabla cuando siguiente sea true
         $siguiente = false;
         $sig_tabla = false;

         /*
          * mostramos las tablas
          */
         echo "<div class='lista'>Tablas a procesar</div>",
            "<ul class='horizontal'>\n";

         foreach($tablas as $col)
         {
            if($siguiente)
            {
               $sig_tabla = $col;
               $siguiente = false;
            }
            
            echo "<li>" , $col;
            if($procesamos AND $col == $datos['t'])
            {
               $error = false;
               
               if( $this->check_tabla($col, $error) )
                  echo " [ <b>procesando</b> ]</td>";
               else
                  echo " [ <b>" , $error , "</b> ]</td>";
               
               $siguiente = true;
            }
            else
               echo "</td>";
         }

         if($procesamos)
         {
            /// recarga la pagina cada 3 segundos mientras haya una nueva tabla que procesar
            if($sig_tabla AND $error == '')
            {
               echo "<li>
                  <a href='ppal.php?mod=",$mod,"&amp;pag=",$pag,"&amp;p=true&amp;t=",$sig_tabla,$traza,"'>continuar »</a>\n
                  </li>\n
                  </ul>\n
                  <script type=\"text/javascript\">
                     <!--
                     function fs_onload() {
                        setTimeout('recargar()',3000);
                     }
                     function recargar() {
                        window.location.href = \"ppal.php?mod=" , $mod , "&pag=" , $pag , "&p=true&t=" , $sig_tabla , $traza , "\";
                     }
                     //-->
                  </script>\n";
            }
            else if($error == '') /// si no hay mas tablas, simplemente recargamos sin parametros
            {
               echo "</ul>\n<script type=\"text/javascript\">
                     <!--
                     function fs_onload() {
                        setTimeout('recargar()',2000);
                     }
                     function recargar() {
                        window.location.href = \"ppal.php?mod=" , $mod , "&pag=" , $pag , "\";
                     }
                     //-->
                  </script>\n";
            }
         }
         else /// si no procesamos
         {
            echo "<li>
               <a href='ppal.php?mod=",$mod,"&amp;pag=",$pag,"&amp;p=true&amp;t=",$tablas[0],$traza,"'>Actualizar la base de datos »</a>
               </li>\n
               </ul>\n";
            $this->resto_tablas($tablas);
         }
      }
      else
         echo "<div class='error'>La base de datos est&aacute; vac&iacute;a</div>";
   }

   /// muestra el resto de tablas
   private function resto_tablas(&$resto)
   {
      $tablas = $this->bd->list_tables();
      if($tablas)
      {
         echo "<div class='lista'>Resto de tablas presetes en la base de datos</div>\n",
            "<ul class='horizontal'>\n";

         foreach($tablas as $col)
         {
            if( !in_array($col['name'], $resto) )
               echo "<li><a href='tabla2xml.php?tabla=" . $col['name'] . "'>" . $col['name'] . "</a></li>\n";
         }
         
         echo "</ul>\n";
      }
   }
   
   /// devuelve un array con los nombres de los archivos xml que hay en el directorio tablas
   private function get_xml_tablas()
   {
      $tablas = array();
      $path = 'admin/tablas/';
      $directorio = dir($path);
      $i = 0;

      while($archivo = $directorio->read())
      {
         if(substr($archivo, 0, 1) != '.' AND substr($archivo, -4, 4) == '.xml')
         {
            $tablas[$i] = substr($archivo,0,strlen($archivo) - 4);
            $i++;
         }
      }
      $directorio->close();
      /// ordenamos el array
      sort($tablas);
      return($tablas);
   }

   /// obtiene todos los datos del xml de la tabla
   private function get_xml_tabla($tabla, &$columnas, &$restricciones, &$indices)
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
   private function compara_columna($tabla, $col, $columnas)
   {
      $consulta = "";
      $encontrada = False;
      
      if($columnas)
      {
         foreach($columnas as $col2)
         {
            if($col2['column_name'] == $col['nombre'])
            {
               $encontrada = TRUE;

               if($col['defecto'] == "")
                  $col['defecto'] = NULL;
               
               if($col2['column_default'] != $col['defecto'])
               {
                  if($col['defecto'] != NULL)
                     $consulta .= "ALTER TABLE " . $tabla . " ALTER COLUMN " . $col['nombre'] . " SET DEFAULT " . $col['defecto'] . ";\n";
                  else
                     $consulta .= "ALTER TABLE " . $tabla . " ALTER COLUMN " . $col['nombre'] . " DROP DEFAULT;\n";
               }
               
               if($col['nulo'] == "SI")
                  $col['nulo'] = 'YES';
               if($col2['is_nullable'] != $col['nulo'])
               {
                  if($col['nulo'] == "YES")
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
   private function compara_constraints($tabla, $c_nuevas, $c_old)
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
   private function genera_tabla($tabla, $xml_columnas, $xml_restricciones, $xml_indices)
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
      $consulta .= $this->compara_constraints($tabla, $xml_restricciones, false);

      return($consulta);
   }
   
   /// comprueba y actualiza la estructura de la tabla si es necesario
   private function check_tabla($tabla, &$error)
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

      if($this->get_xml_tabla($tabla, $xml_columnas, $xml_restricciones, $xml_indices))
      {
         if($this->bd->existe_tabla($tabla))
         {
            $columnas = $this->get_columnas($tabla);
            $restricciones = $this->get_constraints($tabla);
            $indices = $this->get_indices($tabla);

            /// comparamos las columnas
            foreach($xml_columnas as $col)
               $consulta .= $this->compara_columna($tabla, $col, $columnas);

            /// comparamos las restricciones
            $consulta .= $this->compara_constraints($tabla, $xml_restricciones, $restricciones);

            /*
             * TODO comparar los indices
             */
         }
         else
         {
            /// generamos el sql para crear la tabla
            $consulta .= $this->genera_tabla($tabla, $xml_columnas, $xml_restricciones, $xml_indices);
         }

         if($consulta != "")
         {
            if( !$this->bd->exec($consulta) )
            {
               $error = "Error";
               echo "<div class='error'>" . $consulta . "</div>";
            }

            if( $this->traza )
               echo "<div class='error'>" . $consulta . "</div>";
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
   private function get_columnas($tabla)
   {
      $columnas = FALSE;
      if($tabla)
      {
         $consulta = "SELECT column_name, column_default, is_nullable
            FROM information_schema.columns
            WHERE table_catalog = '" . FS_DB_NAME . "' AND table_name = '$tabla';";
         $columnas = $this->bd->select($consulta);
      }
      return($columnas);
   }
   
   /// devuelve una array con las restricciones de una tabla dada
   private function get_constraints($tabla)
   {
      $constraints = FALSE;
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
   
   /// devuelve una array con los indices de una tabla dada
   private function get_indices($tabla)
   {
      $indices = FALSE;
      if($tabla)
      {
         $consulta = "select * from pg_indexes where tablename = '$tabla';";
         $indices = $this->bd->select($consulta);
      }
      return($indices);
   }

   /// comprueba y actualiza las secuencias de los ejercicios
   private function update_secuencias()
   {
      $mis_opciones = new opciones();
      $opciones = false;

      if($mis_opciones->all($opciones))
      {
         $ejercicio = $opciones['ejercicio'];
         $serie = $opciones['serie'];

         $ids = $this->bd->select("SELECT id FROM secuenciasejercicios WHERE codejercicio = '$ejercicio';");
         $creado = false;

         if(!$ids)
         {
            $this->bd->exec("INSERT INTO secuenciasejercicios (codserie,codejercicio,npedidoprov,nalbaranprov,nfacturaprov,npresupuestocli,npedidocli,nalbarancli,nfacturacli) VALUES ('$serie','$ejercicio','1','1','1','1','1','1','1')");
            $creado = true;
         }

         /// para cada ejercicio comprobamos si existen todas las secuencias necesarios
         if($ids)
         {
            foreach($ids as $id)
            {
               if(!$this->bd->select("SELECT idsec FROM secuencias WHERE id = '$id[id]' AND nombre = 'nalbarancli';"))
               {
                  $this->bd->exec("INSERT INTO secuencias (nombre,descripcion,valor,valorout,id) VALUES ('nalbarancli','creado por facturaScripts','0','1','$id[id]');");
                  $creado = true;
               }

               if(!$this->bd->select("SELECT idsec FROM secuencias WHERE id = '$id[id]' AND nombre = 'nalbaranprov';"))
               {
                  $this->bd->exec("INSERT INTO secuencias (nombre,descripcion,valor,valorout,id) VALUES ('nalbaranprov','creado por facturaScripts','0','1','$id[id]');");
                  $creado = true;
               }
            }
         }

         if($creado)
            echo "<div class='mensaje'>Creadas las secuencias necesarias</div>\n";
      }
   }

   /// comprueba la tabla fs_opciones y crea los registros necesarios
   private function update_opciones()
   {
      $consulta = "";
      $opciones = $this->bd->select("SELECT cod FROM fs_opciones;");
      $claves = Array(
         0 => 'ejercicio',
         1 => 'serie',
         2 => 'cliente',
         3 => 'impuesto',
         4 => 'g_maps_key',
         5 => 'puerto_com'
      );

      foreach($claves as $col)
      {
         if( !$this->check_opciones($col, $opciones) )
            $consulta .= "INSERT INTO fs_opciones (cod) VALUES ('$col');";
      }

      if($consulta)
      {
         if( $this->bd->exec($consulta) )
            echo "<div class='mensaje'>Actualizadas las opciones</div>\n";
         else
            echo "<div class='error'>error al actualizar las opciones<br/>" . $consulta . "</div>\n";
      }
   }

   /// devuelve true si $cod esta en $opciones, false en caso contrario
   private function check_opciones($cod, $opciones)
   {
      $retorno = FALSE;
      if($opciones)
      {
         foreach($opciones as $opcion)
         {
            if($opcion['cod'] == $cod)
            {
               $retorno = TRUE;
               break;
            }
         }
      }
      return($retorno);
   }
}

?>
