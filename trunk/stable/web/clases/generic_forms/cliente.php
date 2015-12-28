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
require_once("clases/clientes.php");
require_once("clases/opciones.php");

class script_ extends script
{
   private $cliente;
   private $clientes;
   private $direccion;
   private $opciones;
   private $stats;

   public function __construct($ppal)
   {
      parent::__construct($ppal);

      $this->generico = true;
      $this->coletilla = "&amp;cod=" . $_GET['cod'];
      $this->clientes = new clientes();
      $this->cliente = $this->clientes->get($_GET['cod']);
      $this->direccion = $this->clientes->get_direcciones($_GET['cod']);
      $this->opciones = new opciones();
      $this->stats = $this->get_stats($_GET['cod']);
   }

   /// devuelve el titulo del script
   public function titulo()
   {
      return("Cliente " . $this->cliente['codcliente']);
   }

   /// codigo javascript
   public function javas()
   {
      $direcciones = $this->clientes->get_direcciones_map($this->cliente['codcliente']);

      if($direcciones)
      {
         $texto = "";

         foreach($direcciones as $dir)
         {
            $texto .= "if (geocoder) {
                  geocoder.geocode( { 'address': '" . $dir . "'}, function(results, status) {
                     if (status == google.maps.GeocoderStatus.OK) {
                        map.setCenter(results[0].geometry.location);
                        var marker = new google.maps.Marker({
                           map: map,
                           position: results[0].geometry.location
                        });
                        map.setZoom(12);
                     } else {
                        alert(\"Geocode was not successful for the following reason: \" + status);
                     }
                  });
               }\n\n";
         }

         ?>
         <script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
         <script type="text/javascript">
            var geocoder;
            var map;

            function initialize()
            {
               geocoder = new google.maps.Geocoder();
               var latlng = new google.maps.LatLng(40.426042,-3.674927);
               var myOptions = {
                  zoom: 5,
                  center: latlng,
                  mapTypeId: google.maps.MapTypeId.ROADMAP
               }

               map = new google.maps.Map(document.getElementById("map"), myOptions);
            }

            function fs_onload()
            {
               initialize();

               <?php echo $texto; ?>
            }

            function fs_unload() {
            }

            var simpleEncoding = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

            function simpleEncode(valueArray,maxValue)
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
            
         </script>
         <?php
      }
   }

   public function recargar($mod, $pag)
   {
      return("ppal.php?mod=" . $mod . "&amp;pag=" . $pag . "&amp;cod=" . $_GET['cod']);
   }

   /// carga el cuerpo del script
   public function cuerpo($mod, $pag, &$datos)
   {
      if( $this->cliente )
      {
         echo "<div class='destacado'><span>" , $this->cliente['nombrecomercial'] , "</span></div>\n";

         /// quitamos los espacios de los telefonos
         $this->cliente['telefono1'] = str_replace(' ', '', $this->cliente['telefono1']);
         $this->cliente['telefono2'] = str_replace(' ', '', $this->cliente['telefono2']);
         
         echo "<table class='datos'>\n" , "<tr>\n",
            "<td><b>Nombre:</b> " , $this->cliente['nombre'] , "</td>\n",
            "<td><b>cif/nif:</b> " , $this->cliente['cifnif'] , "</td>\n",
            "</tr>\n" , "<tr>\n",
            "<td><b>Tel&eacute;fonos:</b> " , $this->mostrar_telefonos($this->cliente['telefono1'], $this->cliente['telefono2']) , "</td>\n",
            "<td><b>Fax:</b> " , $this->cliente['fax'] , "</td>\n",
            "</tr>\n" , "<tr>\n",
            "<td><b>email:</b> <a href='mailto:" , $this->cliente['email'] , "'>" , $this->cliente['email'] , "</a></td>\n",
            "<td><b>Web:</b> <a href='http://" , $this->cliente['web'] , "'>" , $this->cliente['web'] , "</a></td>\n",
            "</tr>\n" , "<tr>\n",
            "<td colspan='2'><b>Observaciones:</b> " , $this->cliente['observaciones'] , "</td>\n",
            "</tr>\n" , "</table>\n";


         if( $this->direccion )
         {
            echo "<table width='100%'>\n" , "<tr>\n",
               "<td width='400' valign='top'><div id='map' style='width:400px;height:400px;margin-left:auto;margin-right:auto;'></div>\n<br/>\n";

            if($mod == 'contabilidad' OR $mod == 'principal')
               $this->mostrar_stats();
            
            echo "</td>\n<td valign='top'>\n";

            echo "<div class='lista'>Direcciones</div>\n",
               "<table class='lista'>\n",
               "<tr class='destacado'>\n",
               "<td>Pa&iacute;s</td>\n",
               "<td>Ciudad</td>\n",
               "<td>C&oacute;digo postal</td>\n",
               "<td>Direcci&oacute;n</td>\n",
               "</tr>\n";

            foreach($this->direccion as $dir)
            {
               echo "<tr>\n",
                  "<td>" , $dir['pais'] , "</td>\n",
                  "<td>" , $dir['ciudad'] , "</td>\n",
                  "<td>" , $dir['codpostal'] , "</td>\n",
                  "<td>" , $dir['direccion'] , "</td>\n",
                  "</tr>\n";
            }

            echo "</table>\n";

            /// mostramos el historial
            $this->mostrar_historial($mod);

            echo "</td>\n</tr>\n</table>\n";
         }
         else
         {
            /// mostramos el historial
            $this->mostrar_historial($mod);
         }
      }
      else
         echo "<div class='error'>Cliente no encontrado</div>\n";
   }

   private function mostrar_telefonos($tel1, $tel2)
   {
      if($tel1 != '')
      {
         echo $tel1;

         if($tel2 != '')
            echo ' | ' , $tel2;
      }
      else if($tel2 != '')
         echo $tel2;
   }

   private function mostrar_historial($mod)
   {
      $stats = $this->clientes->stats();

      echo "<div class='lista'>Historial</div>\n",
         "<table class='lista'>\n",
         "<tr class='destacado'>\n",
         "<td>Albaranes</td>\n",
         "<td>Facturas</td>\n",
         "</tr>\n";
      
      echo "<tr>\n",
         "<td><a href='ppal.php?mod=" , $mod , "&pag=albaranescli&buscar=" , $this->cliente['codcliente'] , "&tipo=coc'>",
         number_format($stats[$this->cliente['codcliente']]['albaranes'], 0) , "</a></td>\n",
         "<td><a href='ppal.php?mod=" , $mod , "&pag=facturascli&buscar=" , $this->cliente['codcliente'] , "&tipo=coc'>",
         number_format($stats[$this->cliente['codcliente']]['facturas'], 0) , "</a></td>\n",
         "</tr>\n";

      echo "</table>\n";
   }

   private function get_stats($codcliente)
   {
      $retorno = false;

      if($codcliente != '')
      {
         $consulta = "SELECT to_char(fecha,'FMMM') as mes, sum(total) as total FROM albaranescli
            WHERE codcliente = '$codcliente' AND fecha >= '1-1-" . Date('Y') . "'
            GROUP BY to_char(fecha,'FMMM') ORDER BY mes ASC;";

         $resultado = $this->bd->select($consulta);
         if($resultado)
         {
            /// inicializamos
            for($i = 1; $i < 13; $i++)
               $retorno[$i] = 0;

            foreach($resultado as $col)
               $retorno[$col['mes']] = $col['total'];
         }
      }

      return($retorno);
   }

   private function mostrar_stats()
   {
      $max = 0;
      $tipo = 'lc';

      if( $this->stats )
      {
         for($i = 1; $i <= count($this->stats); $i++)
         {
            if($this->stats[$i] > $max)
               $max = $this->stats[$i];

            if($i == 1)
            {
               $parametros = number_format($this->stats[$i], 2, '.', '');
               $parametros2 = $i;
            }
            else
            {
               $parametros .= "," . number_format($this->stats[$i], 2, '.', '');
               $parametros2 .= "|" . $i;
            }
         }

         echo "<center>\n",
            "<script type=\"text/javascript\">\n",
            "<!--\n",
            "var albaranes = new Array(" , $parametros , ");\n",
            "var maxImporte = " , $max , ";\n",
            "document.write(\"<img src='http://chart.apis.google.com/chart?cht=" , $tipo,
            "&chs=400x150&chd=\" + simpleEncode(albaranes, maxImporte) + \"&chxt=x,y&chxl=0:|",
            $parametros2 , "|1:|€/mes|" , number_format($max / 2, 0) , "|" , number_format($max, 0),
            "' alt='Importe albaranes / mes' title='Importe total de compras al mes del cliente'/>\");\n",
            "//-->\n",
            "</script>\n",
            "</center>\n";
      }
   }
}

?>
