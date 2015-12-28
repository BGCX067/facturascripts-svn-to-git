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

require_once("clases/articulos.php");

class script_ extends script
{
   private $articulos;
   private $incremento;
   private $url;

   public function __construct($ppal)
   {
      parent::__construct($ppal);

      $this->articulos = new articulos();
      $this->incremento = 100;
      $this->url = "";
   }

   /// devuelve el titulo del script
   public function titulo()
   {
      return("Actualizar equivalencias de art&iacute;culos");
   }

   /// captura las variables necesarias para el script enviadas por GET y POST
   public function datos()
   {
      $datos = array(
          'enviado' => FALSE,
          'correcto' => FALSE,
          'proceso' => '',
          'posicion' => '',
          'actualizados' => ''
      );
      
      /// subir
      if( isset($_POST['enviado']) )
      {
         $datos['enviado'] = $_POST['enviado'];
         if(is_uploaded_file($_FILES['archivo']['tmp_name']))
         {
            copy($_FILES['archivo']['tmp_name'], getcwd() . "/modulos/principal/csv/equivalencias.csv");
            $datos['correcto'] = FALSE;
         }
      }

      if( isset($_GET['pr']) )
         $datos['proceso'] = $_GET['pr'];
      
      if( isset($_GET['pos']) )
         $datos['posicion'] = $_GET['pos'];
      
      if( isset($_GET['act']) )
         $datos['actualizados'] = $_GET['act'];

      return $datos;
   }

   /// cargar el cuerpo del script
   public function cuerpo($mod, $pag, &$datos)
   {
      /// guardamos mod y pag;
      $this->url = "ppal.php?mod=" . $mod . "&pag=" . $pag;

      /// eliminar
      if($datos['proceso'] == "eliminar" AND file_exists("modulos/principal/csv/equivalencias.csv"))
      {
         unlink(getcwd()."/modulos/principal/csv/equivalencias.csv");
         echo "<div class='mensaje'>Archivo eliminado correctamente</div>\n";
      }

      if(!file_exists("modulos/principal/csv/equivalencias.csv"))
      {
         echo "<div class='lista'>Subir archivo de equivalencias:</div>\n",
            "<div class='lista2'>\n",
            "Debes seleccionar un archivo e formato csv con la siguiente estructura:\n",
            "<ul><li>En la primera l&iacute;nea \"sufijo1;sufijo2;...;sufijoN;\" o bien en en blanco (\";;...;;\").</li>\n",
            "<li>En las siguientes l&iacute;neas \"referencia1;referencia2;...;referenciaN;\"\n",
            "donde referencia1, referencia2 ... y referenciaN son equivalentes.</li></ul>\n",
            "<form enctype='multipart/form-data' action='ppal.php?mod=" , $mod , "&amp;pag=" , $pag , "' method='post'>\n",
            "<input type='hidden' name='enviado' value='true'/>\n",
            "<input type='hidden' name='MAX_FILE_SIZE' value='2000000'/>\n",
            "Archivo: <input name='archivo' type='file'/>\n",
            "<input type='submit' value='Subir'/>\n",
            "</form>\n",
            "</div>\n";
      }
      else
      {
         if($datos['enviado'])
         {
            if($datos['correcto'])
            {
               echo "<div class='mensaje'>Archivo subido correctamente</div>\n";
            }
            else
            {
               echo "<div class='error'>Error al subir el archivo</div>\n";
            }
         }
         
         if( !$this->procesar_archivo($datos['proceso'], $datos['posicion'], $datos['actualizados']) )
         {
            echo "<div class='destacado'>\n",
               "Pulsa el bot&oacute;n para iniciar el proceso\n",
               "<form action='ppal.php' method='get'>\n",
               "<input type='hidden' name='mod' value='" , $mod , "'/>\n",
               "<input type='hidden' name='pag' value='" , $pag , "'/>\n",
               "<input type='hidden' name='pos' value='0'/>\n",
               "<span>\n";
            
            $this->muestra_seleccion($datos['proceso']);

            echo "</span> &nbsp; <input type='submit' value='Comenzar'/>\n",
               "</form>\n",
               "</div>\n";
         }
      }
   }

   private function muestra_seleccion($proceso)
   {
      switch($proceso)
      {
         default:
            echo "<input type='radio' name='pr' value='comprobar' checked/>comprobar\n",
               "<input type='radio' name='pr' value='procesar'/>procesar\n",
               "<input type='radio' name='pr' value='eliminar'/>eliminar\n";
            break;

         case "procesar":
            echo "<input type='radio' name='pr' value='comprobar'/>comprobar\n",
               "<input type='radio' name='pr' value='procesar' checked/>procesar\n",
               "<input type='radio' name='pr' value='eliminar'/>eliminar\n";
            break;

         case "eliminar":
            echo "<input type='radio' name='pr' value='comprobar'/>comprobar\n",
               "<input type='radio' name='pr' value='procesar'/>procesar\n",
               "<input type='radio' name='pr' value='eliminar' checked/>eliminar\n";
            break;
      }
   }

   /// devuelve true mientras tenga que continuar
   private function procesar_archivo($proceso, $posicion, $actualizados)
   {
      $retorno = false;

      $file = fopen("modulos/principal/csv/equivalencias.csv", "r");
      if(!$file)
      {
         echo "<div class='error'>No se encuentra el fichero</div>";
      }
      else
      {
         if($proceso)
         {
            echo "<div id='progreso' class='centrado'>\n",
               "<img src='images/progreso.gif' align='middle' alt='en progreso'/> " , $proceso , " ...</div>\n";
         }

         /// comprobamos los datos del array
         if($posicion == '')
         {
            $posicion = 0;
         }

         if($actualizados == '')
         {
            $actualizados = 0;
         }

         $i = 0;
         
         if($proceso)
         {
            echo "<table class='lista'>\n",
               "<tr class='destacado'>\n",
               "<td width='50'>L&iacute;nea</td>\n",
               "<td></td>\n",
               "<td>Estado</td>\n",
               "</tr>\n";
         }

         while( !feof($file) )
         {
            // Leemos linea a linea
            $linea = fgets ($file, 1024);
            // Limpiamos los caracteres indeseables
            $linea = trim($linea);

            // Si la linea no esta en blanco (caso ultima linea)
            if($linea != '')
            {
               if($i == 0)
               {
                  $cabecera = explode(';', $linea);
               }
               else
               {
                  $equivalencias = explode(';', $linea);

                  if($i >= $posicion AND $i < ($posicion + $this->incremento))
                  {
                     $error = false;

                     switch($proceso)
                     {
                        case "comprobar":
                           $this->procesar_articulos(false, $i, $equivalencias, $cabecera, $error);
                           break;

                        case "procesar":
                           if( $this->procesar_articulos(true, $i, $equivalencias, $cabecera, $error) )
                           {
                              $actualizados++;
                           }
                           break;
                     }
                  }
               }
            }

            $i++;
         }

         if($proceso)
         {
            echo "</table>\n<br/>\n<br/>\n";


            if($actualizados > 0)
            {
               echo "<div class='mensaje'>Se han actualizado <b>" , number_format($actualizados, 0) , "</b> art&iacute;culos</div>\n";
            }
         }

         /// actualizamos datos
         if($proceso)
         {
            $posicion += $this->incremento;

            if($posicion > $i)
            {
               $posicion = $i;
            }
         }

         /// ¿sigue procesando?
         if($proceso AND $posicion < $i)
         {
            $retorno = true;

            $this->url .= "&pr=" . $proceso . "&pos=" . $posicion . "&act=" . $actualizados;

            /// recarga la pagina cada 3 segundos hasta el final
            echo "<script type=\"text/javascript\">
               <!--
               function fs_onload() {
                  setTimeout('recargar()',3000);
                }

               function recargar() {
                  window.location.href = \"" , $this->url , "\"
               }
                 //-->
               </script>\n";
         }
         else if($proceso AND $posicion == $i) /// ha terminado
         {
            /// nota: ocultamos mediante javascript el mensaje de progreso
            echo "<script type=\"text/javascript\">
               <!--
               function fs_onload() {
                  document.getElementById('progreso').style.display = 'none';
               }
               //-->
               </script>\n";
         }
      }

      return($retorno);
   }

   private function procesar_articulos($procesar, $linea, $equivalencias, $cabecera, &$error)
   {
      $retorno = false;
      $articulos = Array();

      if($equivalencias)
      {
         $i = 0;

         /// cargamos todos los articulos
         foreach($equivalencias as $col)
         {
            $art_temp = false;

            if($col != '')
            {
               if( $this->articulos->get($col . $cabecera[$i], $art_temp) )
               {
                  $articulos[$i] = $art_temp;
               }
            }

            $i++;
         }

         /// Si hay más de uno continuamos
         if(count($articulos) > 1)
         {
            $codigo = "";

            /*
             * Comprobamos los códigos de equivalencia de cada artículo.
             * Nos aseguramos de que haya un único código de equivalencia.
             */
            foreach($articulos as $col)
            {
               if($col['equivalencia'] != '')
               {
                  if($codigo == '')
                  {
                     $codigo = $col['equivalencia'];
                     $retorno = true;
                  }
                  else
                  {
                     $error = "Multiples c&oacute;digos de equivalencia";
                     $retorno = false;
                  }
               }
            }

            /// si no se ha encontrado un código de equivalencia común
            if($codigo == '')
            {
               /// probamos con una de las referencias
               $prueba = $this->bd->select("SELECT referencia FROM articulos WHERE equivalencia = '" . $articulos[0]['referencia'] . "';");
               
               if($prueba)
               {
                  $error = "Imposible obtener un c&oacute;digo de equivalencia v&aacute;lido";
               }
               else
               {
                  $codigo = $articulos[0]['referencia'];
                  $retorno = true;
               }
            }

            /// Si hay un único código de equivalencia lo replicamos en los demás artículos.
            if($retorno AND $procesar)
            {
               $consulta = "";

               foreach($articulos as $col)
               {
                  $consulta .= "UPDATE articulos SET equivalencia = '" . $codigo . "' WHERE referencia = '" . $col['referencia'] . "';";
               }

               if( !$this->bd->exec($consulta) )
               {
                  $error = "Error al actualizar los c&oacute;digos de equivalencia";
                  $retorno = false;
               }
            }
         }
         else
         {
            $error = "No hay suficientes art&iacute;culos";
         }
      }
      else
      {
         $error = "No hay ning&uacute;n art&iacute;culo";
      }

      /// mostramos los resultados
      if($retorno)
      {
         echo "<tr><td>" , number_format($linea + 1, 0) , "</td><td>";

         foreach($articulos as $col)
         {
            echo "<a href='ppal.php?mod=principal&amp;pag=articulo&amp;ref=" , $col['referencia'] , "'>" , $col['referencia'] , "</a>, ";
         }

         echo "</td><td>C&oacute;digos de equivalencia actualizables</td></tr>\n";
      }
      else
      {
         echo "<tr class='amarillo'><td>" , number_format($linea + 1, 0) , "</td><td>-</td><td>" , $error , "</td></tr>\n";
      }

      return($retorno);
   }
}

?>
