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
   private $anyos;
   private $seleccionado;
   private $comparativo;

   public function __construct($ppal)
   {
      parent::__construct($ppal);

      $this->comparativo = false;
      $this->anyos = $this->get_anyos();
   }

   private function get_anyos()
   {
      $retorno = false;

      $resultado = $this->bd->select("SELECT DISTINCT to_char(fecha,'yyyy') as anyo
         FROM fs_informes ORDER BY anyo DESC;");

      if($resultado)
      {
         $i = 0;

         foreach($resultado as $col)
         {
            if($i == 0)
               $this->seleccionado = $col['anyo'];

            $retorno[$i] = $col['anyo'];
            $i++;
         }

         if( isset($_GET['e']) )
            $this->seleccionado = $_GET['e'];

         if( isset($_GET['c']) )
            $this->comparativo = $_GET['c'];
      }
      else
         $this->seleccionado = false;

      return($retorno);
   }

   /// devuelve el titulo del script
   public function titulo()
   {
      return("Informes");
   }

   /// codigo javascript
   public function javas()
   {
      ?>
   <script type="text/javascript">
   <!--
   function fs_onload()
   {
   }

   function fs_unload()
   {
   }

   var simpleEncoding = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

   function simpleEncode(valueArray, maxValue)
   {
      var chartData = ['s:'];

      for (var i = 0; i < valueArray.length; i++)
      {
         var currentValue = valueArray[i];

         if (!isNaN(currentValue) && currentValue >= 0)
         {
            chartData.push(simpleEncoding.charAt(Math.round((simpleEncoding.length-1) * currentValue / maxValue)));
         }
         else
         {
            chartData.push('_');
         }
      }

      return chartData.join('');
   }
   //-->
   </script>
      <?php
   }

   /// carga el cuerpo del script
   public function cuerpo($mod, $pag, &$datos)
   {
      if( $this->anyos )
      {
         echo "<div class='destacado'>\n",
            "<form name='informes' action='ppal.php' method='get'>\n",
            "<input type='hidden' name='mod' value='" , $mod , "'/>\n",
            "<input type='hidden' name='pag' value='" , $pag , "'/>\n",
            "<span>\n",
            "<b>Ejercicio:</b> <select name='e'>\n";

         foreach($this->anyos as $anyo)
         {
            if($anyo == $this->seleccionado)
               echo "<option value='" , $anyo , "' selected='selected'>" , $anyo , "</option>\n";
            else
               echo "<option value='" , $anyo , "'>" , $anyo , "</option>\n";
         }

         echo "</select>\n",
            "<b>comparado con:</b> <select name='c'>\n",
            "<option value=''>----</option>\n";

         foreach($this->anyos as $anyo)
         {
            if($anyo == $this->comparativo)
               echo "<option value='" , $anyo , "' selected='selected'>" , $anyo , "</option>\n";
            else
               echo "<option value='" , $anyo , "'>" , $anyo , "</option>\n";
         }

         echo "</select>\n",
            "<input type='submit' value='mostrar'/>\n",
            "</span>\n</form>\n</div>\n";

         $this->mostrar_informe($this->seleccionado, $this->comparativo);
      }
      else
         echo "<div class='mensaje'>No hay informes que mostar</div>";
   }

   private function mostrar_informe($anyo, $anyo2)
   {
      $datos = $this->rellenar_stats($anyo, $anyo2);

      if($anyo2)
      {
         if($datos)
         {
            /// grafica de cliente
            echo "<div class='lista'>Clientes</div>\n",
               "<table class='lista'>\n",
               "<tr>\n<td align='center'>" , $this->grafica_dos_variables($datos, "nalbcli", $anyo, $anyo2, "N&uacute;mero de albaranes / mes") , "</td>\n",
               "<td align='center'>" , $this->grafica_dos_variables($datos, "euralbcli", $anyo, $anyo2, "Importe de albaranes / mes") , "</td>\n</tr>\n",
               "<tr>\n<td align='center'>" , $this->grafica_dos_variables($datos, "nfactcli", $anyo, $anyo2, "N&uacute;mero de facturas / mes") , "</td>\n",
               "<td align='center'>" , $this->grafica_dos_variables($datos, "eurfactcli", $anyo, $anyo2, "Importe de facturas / mes") , "</td>\n</tr>\n",
               "</table>\n";

            /// grafica de proveedor
            echo "<div class='lista'>Proveedores</div>\n",
               "<table class='lista'>\n",
               "<tr>\n<td align='center'>" , $this->grafica_dos_variables($datos, "nalbprov", $anyo, $anyo2, "N&uacute;mero de albaranes / mes") , "</td>\n",
               "<td align='center'>" , $this->grafica_dos_variables($datos, "euralbprov", $anyo, $anyo2, "Importe de albaranes / mes") , "</td>\n</tr>\n",
               "<tr>\n<td align='center'>" , $this->grafica_dos_variables($datos, "nfactprov", $anyo, $anyo2, "N&uacute;mero de facturas / mes") , "</td>\n",
               "<td align='center'>" , $this->grafica_dos_variables($datos, "eurfactprov", $anyo, $anyo2, "Importe de facturas / mes") , "</td>\n</tr>\n",
               "</table>\n";
         }
      }
      else
      {
         if($datos)
         {
            /// grafica de articulos
            echo "<div class='lista'>Art&iacute;culos</div>\n",
               "<table class='lista'>\n",
               "<tr>\n<td align='center'>" , $this->grafica_una_variable($datos, "nart", "N&uacute;mero de art&iacute;culos / mes") , "</td>\n",
               "<td align='center'>" , $this->grafica_una_variable($datos, "nartu", "N&uacute;mero de art&iacute;culos actualizados / mes") , "</td>\n</tr>\n",
               "<tr>\n<td align='center'>" , $this->grafica_una_variable($datos, "nstock", "Stock / mes") , "</td>\n",
               "<td align='center'>" , $this->grafica_una_variable($datos, "eurstock", "Importe de stock / mes") , "</td>\n</tr>\n",
               "</table>\n";

            /// grafica de cliente
            echo "<div class='lista'>Clientes</div>\n",
               "<table class='lista'>\n",
               "<tr>\n<td align='center'>" , $this->grafica_una_variable($datos, "nalbcli", "N&uacute;mero de albaranes / mes") , "</td>\n",
               "<td align='center'>" , $this->grafica_una_variable($datos, "euralbcli", "Importe de albaranes / mes") , "</td>\n</tr>\n",
               "<tr>\n<td align='center'>" , $this->grafica_una_variable($datos, "nfactcli", "N&uacute;mero de facturas / mes") , "</td>\n",
               "<td align='center'>" , $this->grafica_una_variable($datos, "eurfactcli", "Importe de facturas / mes") , "</td>\n</tr>\n",
               "</table>\n";
            
            /// grafica de proveedor
            echo "<div class='lista'>Proveedores</div>\n",
               "<table class='lista'>\n",
               "<tr>\n<td align='center'>" , $this->grafica_una_variable($datos, "nalbprov", "N&uacute;mero de albaranes / mes") , "</td>\n",
               "<td align='center'>" , $this->grafica_una_variable($datos, "euralbprov", "Importe de albaranes / mes") , "</td>\n</tr>\n",
               "<tr>\n<td align='center'>" , $this->grafica_una_variable($datos, "nfactprov", "N&uacute;mero de facturas / mes") , "</td>\n",
               "<td align='center'>" , $this->grafica_una_variable($datos, "eurfactprov", "Importe de facturas / mes") , "</td>\n</tr>\n",
               "</table>\n";
         }
      }
   }

   private function rellenar_stats($anyo, $anyo2)
   {
      $datos = false;
      $resultado = false;

      if($anyo)
      {
         if($anyo2)
         {
            $datos = $this->bd->select("SELECT * FROM fs_informes WHERE to_char(fecha,'yyyy') = '$anyo' OR to_char(fecha,'yyyy') = '$anyo2'
               ORDER BY fecha ASC;");
         }
         else
         {
            $datos = $this->bd->select("SELECT * FROM fs_informes WHERE to_char(fecha,'yyyy') = '$anyo' ORDER BY fecha ASC;");
         }

         if($datos)
         {
            /// inicializamos
            for($i = 1; $i < 13; $i++)
            {
               $fecha = Date('d-m-Y', strtotime($anyo . '-' . $i . '-1'));

               $resultado[$fecha] = Array(
                  'fecha' => $fecha,
                  'nart' => 0,
                  'nartu' => 0,
                  'nstock' => 0,
                  'eurstock' => 0,
                  'nalbcli' => 0,
                  'euralbcli' => 0,
                  'nalbprov' => 0,
                  'euralbprov' => 0,
                  'nfactcli' => 0,
                  'eurfactcli' => 0,
                  'nfactprov' => 0,
                  'eurfactprov' => 0
               );
            }

            foreach($datos as $col)
            {
               $fecha = Date('d-m-Y', strtotime($col['fecha']));

               $resultado[$fecha] = Array(
                  'fecha' => $fecha,
                  'nart' => $col['nart'],
                  'nartu' => $col['nartu'],
                  'nstock' => $col['nstock'],
                  'eurstock' => $col['eurstock'],
                  'nalbcli' => $col['nalbcli'],
                  'euralbcli' => $col['euralbcli'],
                  'nalbprov' => $col['nalbprov'],
                  'euralbprov' => $col['euralbprov'],
                  'nfactcli' => $col['nfactcli'],
                  'eurfactcli' => $col['eurfactcli'],
                  'nfactprov' => $col['nfactprov'],
                  'eurfactprov' => $col['eurfactprov']
               );
            }
         }
      }

      return($resultado);
   }

   private function grafica_una_variable(&$datos, $var, $titulo)
   {
      $max = 0;
      $i = 0;

      foreach($datos as $col)
      {
         if($col[$var] > $max)
            $max = $col[$var];

         if($i == 0)
         {
            $parametros = number_format($col[$var], 2, '.', '');
            $parametros2 = number_format(substr($col['fecha'], 3, 2), 0);
            $i++;
         }
         else
         {
            $parametros .= "," . number_format($col[$var], 2, '.', '');
            $parametros2 .= "|" . number_format(substr($col['fecha'], 3, 2), 0);
         }
      }

      echo $titulo , "<br/>\n<script type=\"text/javascript\">\n",
         "<!--\n",
         "var " , $var , " = new Array(" , $parametros , ");\n",
         "var max" , $var , " = " , $max , ";\n",
         "\n",
         "document.write(\"<img src='http://chart.apis.google.com/chart?cht=lc&chs=500x120&chd=\" + simpleEncode(" , $var , ", max" , $var,
         ") + \"&chxt=x,y&chxl=0:|" , $parametros2 , "|1:|0|" , number_format($max / 2, 0) , '|' , number_format($max, 0),
         "' alt='" , $titulo , "' title='" , $titulo , "'/>\");\n" , "//-->\n",
         "</script>\n<br/>\n<br/>\n";
   }
   
   private function sobre_cien($numero, $total)
   {
      $resultado = 0;

      if($numero > 0 AND $total > 0 AND $total > $numero)
      {
         $resultado = round(($numero / $total * 100), 2);
      }
      else if($numero == $total)
      {
         $resultado = 100;
      }

      return($resultado);
   }

   private function grafica_dos_variables(&$datos, $var, $anyo, $anyo2, $titulo)
   {
      $max = 0;
      $ejex = "";
      $ejex2 = "";
      $abajo = "1|2|3|4|5|6|7|8|9|10|11|12";
      
      foreach($datos as $col)
      {
         if($col[$var] > $max)
            $max = $col[$var];
      }

      foreach($datos as $col)
      {
         if( substr($col['fecha'], 6, 4) == $anyo )
         {
            if($ejex == "")
            {
               $parametros = number_format( $this->sobre_cien($col[$var], $max), 2);
               $ejex = number_format( $this->sobre_cien(substr($col['fecha'], 3, 2) - 1, 11), 0);
            }
            else
            {
               $parametros .= "," . number_format( $this->sobre_cien($col[$var], $max), 2);
               $ejex .= "," . number_format( $this->sobre_cien(substr($col['fecha'], 3, 2) - 1, 11), 0);
            }
         }
         else if( substr($col['fecha'], 6, 4) == $anyo2 )
         {
            if($ejex2 == "")
            {
               $parametros2 = number_format( $this->sobre_cien($col[$var], $max), 2);
               $ejex2 = number_format( $this->sobre_cien(substr($col['fecha'], 3, 2) - 1, 11), 0);
            }
            else
            {
               $parametros2 .= "," . number_format( $this->sobre_cien($col[$var], $max), 2);
               $ejex2 .= "," . number_format( $this->sobre_cien(substr($col['fecha'], 3, 2) - 1, 11), 0);
            }
         }
      }

      echo $titulo , "<br/>\n<script type=\"text/javascript\">
         <!--
         document.write(\"<img src='http://chart.apis.google.com/chart?cht=lxy&chs=500x120&chd=t:" , $ejex , '|' , $parametros , '|',
         $ejex2 , '|' , $parametros2 , "&chco=ff0000,ff9900&chxt=x,y&chxl=0:|" , $abajo , "|1:|0|" , number_format($max / 2, 0) , '|',
         number_format($max, 0) , "&chdl=" , $anyo , '|' , $anyo2 , "' alt='" , $titulo , "' title='" , $titulo , "'/>\");
         //-->
         </script>\n<br/>\n<br/>\n";
   }
}

?>
