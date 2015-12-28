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
require_once("clases/familias.php");
require_once("clases/mensajes.php");

class script_ extends script
{
   private $articulos;
   private $familias;
   private $mensajes;
   private $incremento;
   private $url;

   public function __construct($ppal)
   {
      parent::__construct($ppal);

      $this->articulos = new articulos();
      $this->familias = new familias();
      $this->mensajes = new mensajes();
      $this->incremento = 100;
      $this->url = "";
   }

   /// devuelve el titulo del script
   public function titulo()
   {
      return("Actualizar tarifas de art&iacute;culos");
   }

   /// captura las variables necesarias para el script enviadas por GET y POST
   public function datos()
   {
      $datos = array(
          'enviado' => '',
          'correcto' => FALSE,
          'proceso' => '',
          'posicion' => '',
          'subref' => '',
          'fam' => '',
          'insertar' => isset($_GET['in'])
      );
      
      if( isset($_POST['enviado']) )
      {
         $datos['enviado'] = $_POST['enviado'];
         if(is_uploaded_file($_FILES['archivo']['tmp_name']))
         {
            copy($_FILES['archivo']['tmp_name'], getcwd() . "/modulos/principal/csv/tarifa.csv");
            $datos['correcto'] = TRUE;
         }
      }
      
      if( isset($_GET['pr']) )
         $datos['proceso'] = $_GET['pr'];
      
      if( isset($_GET['pos']) )
         $datos['posicion'] = $_GET['pos'];
      
      if( isset($_GET['subref']) )
         $datos['subref'] = $_GET['subref'];
      
      if( isset($_GET['fam']) )
         $datos['fam'] = $_GET['fam'];
      
      return $datos;
   }

   /// cargar el cuerpo del script
   public function cuerpo($mod, $pag, &$datos)
   {
      $this->url = "ppal.php?mod=" . $mod . "&pag=" . $pag;

      /// ¿Eliminamos?
      if($datos['proceso'] == 'eliminar' AND file_exists("modulos/principal/csv/tarifa.csv"))
      {
         unlink(getcwd()."/modulos/principal/csv/tarifa.csv");
         echo "<div class='mensaje'>Archivo eliminado correctamente</div>\n";
      }


      /// ¿Hay alguna tarifa por leer?
      if( !file_exists("modulos/principal/csv/tarifa.csv") )
      {
         $this->listar_estado_tarifas($mod);

         echo "<div class='lista'>Subir tarifa</div>
            <div class='lista2'>
            <div class='mensaje'>
               La tarifa debe estar en formato csv: valores separados por punto y coma, y sin separador de texto,
               adem&aacute;s las columnas deben ir en este orden: referencia,pvp,descripcion,codigo de barras.
               <br/>
               <a href='modulos/principal/ejemplo.csv'>Aqu&iacute;</a> tienes un ejemplo.
            </div>
            <form enctype='multipart/form-data' action='ppal.php?mod=principal&amp;pag=" , $pag , "' method='post'>
               <input type='hidden' name='enviado' value='true'/>
               <input type='hidden' name='MAX_FILE_SIZE' value='2000000'/>
               Tarifa: <input name='archivo' type='file'/>
               <input type='submit' value='Subir'/>
            </form>
            </div>\n";
      }
      else /// Si que hay tarifa
      {
         if($datos['enviado'] != '')
         {
            if($datos['correcto'] != '')
               echo "<div class='mensaje'>Archivo subido correctamente</div>\n";
            else
               echo "<div class='error'>Error al subir el archivo</div>\n";
         }

         $lineas = false;
         if( !$this->procesar_tarifa($datos['subref'], $datos['fam'], $datos['proceso'], $datos['posicion'], $datos['insertar'] , $lineas) )
         {
            echo "<div class='lista'>Tarifa cargada: ", number_format($lineas) , " l&iacute;neas</div>
               <div class='lista2'>
               <form action='ppal.php' method='get'>
               <input type='hidden' name='mod' value='" , $mod , "'/>
               <input type='hidden' name='pag' value='" , $pag , "'/>
               <input type='hidden' name='pos' value='0'/>
               Sufijo para las referencias: <input type='text' name='subref' value='" , $datos['subref'] , "' size='4' maxlength='8'/>\n";

            $familias = $this->familias->all();
            if($familias)
            {
               echo " Familia: <select name='fam' size='0'>";
               foreach($familias as $col)
               {
                  if($col['codfamilia'] == $datos['fam'])
                     echo "<option value='" , $col['codfamilia'] , "' selected>" , $col['descripcion'] , "</option>";
                  else
                     echo "<option value='" , $col['codfamilia'] , "'>" , $col['descripcion'] , "</option>";
               }
               echo "</select>\n";

               $this->muestra_seleccion($datos['proceso']);

               if( $datos['insertar'] )
                  echo "| <input type='checkbox' name='in' value='false' checked/>No insertar art&iacute;culos nuevos\n";
               else
                  echo "| <input type='checkbox' name='in' value='false'/>No insertar art&iacute;culos nuevos\n";
            }
            else
            {
               echo "<div class='error'>No hay familias creadas</div>
                  <input type='radio' name='pr' value='eliminar'/>eliminar<br/>\n";
            }

            echo "<input type='submit' value='Continuar'/></form>\n</div>\n";
         }
      }
   }

   private function muestra_seleccion($proceso)
   {
      switch($proceso)
      {
         default:
            echo "<input type='radio' name='pr' value='comprobar' checked='checked'/>comprobar\n";
            echo "<input type='radio' name='pr' value='procesar'/>procesar\n";
            echo "<input type='radio' name='pr' value='eliminar'/>eliminar\n";
            break;

         case "procesar":
            echo "<input type='radio' name='pr' value='comprobar'/>comprobar\n";
            echo "<input type='radio' name='pr' value='procesar' checked='checked'/>procesar\n";
            echo "<input type='radio' name='pr' value='eliminar'/>eliminar\n";
            break;

         case "eliminar":
            echo "<input type='radio' name='pr' value='comprobar'/>comprobar\n";
            echo "<input type='radio' name='pr' value='procesar'/>procesar\n";
            echo "<input type='radio' name='pr' value='eliminar' checked='checked'/>eliminar\n";
            break;
      }
   }

   /// devuelve true mientras tenga que continuar
   private function procesar_tarifa($subref, $fam, $proceso, $posicion, $insertar, &$lineas)
   {
      $retorno = false;

      $file = fopen("modulos/principal/csv/tarifa.csv", 'r');

      if( !$file )
         echo "<div class='error'>No se encuentra el fichero</div>";
      else
      {
         if($proceso)
            echo "<div class='centrado' id='progreso'><img src='images/progreso.gif' align='middle' alt='en progreso'/> ",
                 $proceso , " ...</div>\n";

         /// comprobamos los datos
         if($posicion == '') { $posicion = 0; }
         $i = 0;
         $continua = true;
         $pos_mostrar = 0;

         while(!feof($file) AND $continua)
         {
            // Leemos la linea y limpiamos los caracteres indeseables
            $linea = trim(fgets ($file, 1024));

            // Si la linea no esta en blanco (caso ultima linea)
            if($linea != '')
            {
               // Primera linea
               if($i == 0)
               {
                  $cabecera = explode(';', $linea);

                  // Si la cabecera no es REF;PVP;DESC no seguimos leyendo
                  if($cabecera[0] != 'REF' OR $cabecera[1] != 'PVP' OR $cabecera[2] != 'DESC' OR $cabecera[3] != 'CODBAR')
                  {
                     echo "<div class='error'>Las columnas no concuerdan</div>";

                     $continua = false;
                  }
               }
               else
               {
                  $tarifa = explode(';', $linea);

                  /// modificamos la referencia
                  $tarifa[0] = str_replace(' ', '', $tarifa[0] . $subref);

                  // sustituimos las comas por puntos en el pvp
                  $tarifa[1] = str_replace(',', '.', $tarifa[1]);

                  // comprobamos la longitud de la descripcion
                  if(strlen($tarifa[2]) > 99)
                     $tarifa[2] = substr($tarifa[2], 0, 99);

                  if($i >= $posicion AND $i < ($posicion + $this->incremento))
                  {
                     $error = false;
                     $existe = false;

                     switch($proceso)
                     {
                        case 'comprobar':
                           if( $this->comprobar_articulo($tarifa, $fam, $insertar, $existe, $error) )
                           {
                              $this->muestra_tarifa($pos_mostrar, $tarifa, $existe, $error);
                              $pos_mostrar++;
                           }
                           else
                           {
                              $this->muestra_tarifa($pos_mostrar, $tarifa, $existe, $error);
                              $pos_mostrar++;

                              $continua = false;
                              $retorno = false;
                           }
                           break;

                        case 'procesar':
                           if( !$this->procesar_articulo($tarifa, $fam, $insertar, $error) )
                           {
                              echo "<script type=\"text/javascript\">
                                 <!--
                                    function fs_onload() {
                                       document.getElementById('progreso').style.display = 'none';
                                    }
                                 //-->
                                 </script>\n";

                              echo "<div class='error'>Error en la linea <b>" , number_format($i + 1, 0) , "</b>, en el art&iacute;culo <b>",
                                 $tarifa[0] , "</b><br/>" , $error , "</div>\n";

                              $continua = false;
                              $retorno = false;
                           }
                           break;
                     }
                  }
               }
            }

            $i++;
         }

         /// cerramos la tabla
         if($proceso)
            $this->muestra_tarifa($this->incremento, $tarifa, $existe, $error);

         /// ¿Ha habido errores?
         if( !$continua )
            echo "<div class='error'>Se han encontrado errores en la l&iacute;nea <b>" , number_format($i) , "</b></div>\n<br/><br/>\n";

         $lineas = $i;

         /// actualizamos datos
         if($proceso != '')
         {
            $posicion += $this->incremento;

            if($posicion > $i)
               $posicion = $i;
         }

         /// ¿sigue procesando?
         if($proceso != '' AND $posicion < $i)
         {
            $retorno = true;

            $this->url .= "&subref=" . $subref . "&fam=" . $fam . "&pr=" . $proceso . "&pos=" . $posicion . "&in=" . $insertar;

            /// recarga la pagina cada 3 segundos hasta el final
            echo "<script type=\"text/javascript\">
               <!--
               function fs_onload() {
                  setTimeout('recargar()', 1000);
                }

               function recargar() {
                  window.location.href = \"" , $this->url , "\"
               }
                 //-->
               </script>\n";

            echo "<div class='mensaje'>Leidos <b>" , number_format($posicion, 0) , "</b> art&iacute;culos de un total de <b>",
               number_format($i, 0) , "</b></div>\n";
         }
         else if($proceso != '' AND $posicion == $i) /// ha terminado
         {
            /// nota: ocultamos mediante javascript el mensaje de progreso
            echo "<script type=\"text/javascript\">
               <!--
               function fs_onload() {
                  document.getElementById('progreso').style.display = 'none';
               }
               //-->
               </script>\n";

            if( $continua )
            {
               if($proceso == 'comprobar')
               {
                  echo "<div class='mensaje'>Comprobaci&oacute;n de la familia <a href='ppal.php?mod=principal&amp;pag=familia&amp;cod=" , $fam , "'>" , $fam,
                     "</a> terminada</div>\n<br/><br/>\n";
               }
               else if($proceso == 'procesar')
               {
                  echo "<div class='mensaje'>Actualizaci&oacute;n de la familia <a href='ppal.php?mod=principal&amp;pag=familia&amp;cod=" , $fam , "'>" , $fam,
                     "</a> terminada, ya puede eliminar la tarifa si lo desea</div>\n<br/><br/>\n";
               }
               else
                  echo "<div class='error'>Algo raro ha pasado, pregunta a Carlos</div>\n<br/><br/>\n";
            }
            
            $retorno = false;
         }
      }

      return($retorno);
   }

   private function muestra_tarifa($pos, $tarifa, $existe, $error)
   {
      if($pos == 0)
         echo "<div class='lista'>Referencia (PVP) [estado]</div>\n
            <ul class='horizontal'>\n";
      
      if($pos == $this->incremento)
         echo "\n</ul>\n";
      else
      {
         echo "<li>";
         
         if($existe)
         {
            echo "<a href='ppal.php?mod=principal&amp;pag=articulo&amp;ref=" , $tarifa[0] , "'>" , $tarifa[0],
               "</a> (" , number_format($tarifa[1] , 2) , " &euro;)";
         }
         else
            echo $tarifa[0] , " (" , number_format($tarifa[1] , 2) , " &euro;)";
         
         if($error != '')
            echo " [ <b>" , $error , "</b> ]";
         
         echo "</li>";
      }
   }

   private function comprobar_articulo($tarifa, $familia, $insertar, &$existe, &$error)
   {
      $retorno = true;
      $articulo = false;
      
      /// comprobamos la validez de los datos;
      if(eregi("^[A-Z0-9_\+\.\*\/\-]{1,18}$", $tarifa[0]) != true)
      {
         $error = "La referencia solamente admite de 1 a 18 n&uacute;meros, letras y algunos signos de puntuaci&oacute;n";
         $retorno = false;
      }

      if($tarifa[1] != '' AND !is_numeric($tarifa[1]) )
      {
         $error = "PVP debe ser num&eacute;rico";
         $retorno = false;
      }

      /// ¿existe ya el articulo?
      if($retorno AND $this->articulos->get($tarifa[0], $articulo))
      {
         $existe = true;

         if($articulo['codfamilia'] != $familia)
         {
            $error = "La familia no coincide";
            $retorno = false;
         }
      }

      return($retorno);
   }

   private function procesar_articulo($tarifa, $fam, $insertar, &$error)
   {
      $retorno = true;
      $articulo = false;
      $error = false;

      /// ¿existe ya el articulo?
      if( $this->articulos->get($tarifa[0], $articulo) )
      {
         $articulo['pvp_ant'] = $articulo['pvp'];
         $articulo['pvp'] = $tarifa[1];

         if($tarifa[2] != '')
         {
            /// escapamos las comillas
            $articulo['descripcion'] = addslashes($tarifa[2]);
         }
         else
         {
            /// escapamos las comillas
            $articulo['descripcion'] = addslashes($articulo['descripcion']);
         }

         if($tarifa[3] != '')
            $articulo['codbarras'] = $tarifa[3];

         /// escapamos tambien las comillas de observaciones
         $articulo['observaciones'] = addslashes($articulo['observaciones']);

         if( !$this->articulos->update_articulo($articulo, $error) )
            $retorno = false;
      }
      else if( !$insertar) /// no existe el articulo
      {
         $articulo['referencia'] = $tarifa[0];
         $articulo['codfamilia'] = $fam;
         $articulo['pvp'] = $tarifa[1];

         if($tarifa[2] != '')
         {
            /// escapamos las comillas
            $articulo['descripcion'] = addslashes($tarifa[2]);
         }

         if($tarifa[3] != '')
            $articulo['codbarras'] = $tarifa[3];

         if( !$this->articulos->insert_articulo($articulo, $error) )
            $retorno = false;
      }

      return($retorno);
   }

   private function listar_estado_tarifas($mod)
   {
      $tarifas = $this->bd->select("SELECT codfamilia, COUNT(referencia) as articulos, GREATEST( AVG( EXTRACT(EPOCH FROM factualizado)), 0) as fecha
          FROM articulos GROUP BY codfamilia ORDER BY fecha ASC, articulos DESC;");
      if($tarifas)
      {
         echo "<div class='destacado'>
            <span>Familia ( n&uacute;mero de art&iacute;culos ) [ fecha media de actualizaci&oacute;n ]</span>
            </div>\n";

         $i = 0;
         $ano = false;

         foreach($tarifas as $col)
         {
            if($ano != date('Y', $col['fecha']))
            {
               if($i != 0)
                  echo "</ul>\n";
               
               echo "<div class='lista'>A&ntilde;o " , date('Y', $col['fecha']) , "</div>\n",
                  "<ul class='horizontal'>";
               
               $ano = date('Y', $col['fecha']);
               $i = 0;
            }
            
            echo "<li><a href='ppal.php?mod=" , $mod , "&amp;pag=familia&amp;cod=" , $col['codfamilia'] , "'>" , $col['codfamilia'],
               "</a> ( <b>" , number_format($col['articulos'], 0) , "</b> ) [ " , date('d-m-Y' ,$col['fecha']) , " ]</li>\n";
            $i++;
         }
         
         echo "</ul>\n<br/><br/>\n";
      }
   }
}

?>
