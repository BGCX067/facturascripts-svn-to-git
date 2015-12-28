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
require_once("clases/proveedores.php");
require_once("clases/opciones.php");

class script_ extends script
{
   private $proveedor;
   private $proveedores;
   private $direccion;
   private $opciones;
   private $stats;

   public function __construct($ppal)
   {
      parent::__construct($ppal);

      $this->generico = true;
      $this->coletilla = "&amp;cod=" . $_GET['cod'];
      $this->proveedores = new proveedores();
      $this->proveedor = $this->proveedores->get($_GET['cod']);
      $this->direccion = $this->proveedores->get_direcciones($_GET['cod']);
      $this->opciones = new opciones();
      $this->stats = $this->get_stats($_GET['cod']);
   }

   /// devuelve el titulo del script
   public function titulo()
   {
      return("Proveedor " . $this->proveedor['codproveedor']);
   }

   /// codigo javascript
   public function javas()
   {
      $direcciones = $this->proveedores->get_direcciones_map($this->proveedor['codproveedor']);

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
         <script type="text/javascript" src="http://visapi-gadgets.googlecode.com/svn/trunk/termcloud/tc.js"></script>
         <script type="text/javascript" src="http://www.google.com/jsapi"></script>
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
      if( $this->proveedor )
      {
         echo "<div class='destacado'><span>" , $this->proveedor['nombrecomercial'] , "</span></div>\n";

         /// ¿modificamos?
         if(isset($_POST['tlf1']) OR isset($_POST['tlf2']) OR isset($_POST['fax']) OR isset($_POST['email']) OR isset($_POST['web']) OR isset($_POST['obs']))
         {
            $this->modificar($_POST['tlf1'], $_POST['tlf2'], $_POST['fax'], $_POST['email'], $_POST['web'], $_POST['obs']);
         }

         /// quitamos los espacios de los telefonos
         $this->proveedor['telefono1'] = str_replace(' ', '', $this->proveedor['telefono1']);
         $this->proveedor['telefono2'] = str_replace(' ', '', $this->proveedor['telefono2']);
         $this->proveedor['fax'] = str_replace(' ', '', $this->proveedor['fax']);
         
         
         echo "<form name='proveedor' action='" , $this->recargar($mod, $pag) , "' method='post'>\n",
            "<table class='datos'>\n" , "<tr>\n",
            "<td width='110' align='right'><b>Nombre:</b></td><td>" , $this->proveedor['nombre'] , "</td>\n",
            "<td width='50' align='right'><b>cif/nif:</b></td><td>" , $this->proveedor['cifnif'] , "</td>\n",
            "</tr>\n" , "<tr>\n",
            "<td align='right'><b>Tel&eacute;fonos:</b></td><td>" , $this->mostrar_telefonos($this->proveedor['telefono1'], $this->proveedor['telefono2']) , "</td>\n",
            "<td align='right'><b>Fax:</b></td><td><input type='text' name='fax' value='" , $this->proveedor['fax'] , "' size='8' maxlength='9'/></td>\n",
            "</tr>\n" , "<tr>\n",
            "<td align='right'><b>email:</b></td><td><input type='text' name='email' value='" , $this->proveedor['email'] , "' size='25' maxlength='99'/></td>\n",
            "<td align='right'><b>Web:</b></td><td><input type='text' name='web' value='" , $this->proveedor['web'] , "' size='40' maxlength='99'></td>\n",
            "</tr>\n" , "<tr>\n",
            "<td align='right'><b>Observaciones:</b></td><td colspan='2'><textarea name='obs' rows='2' cols='80'>" , $this->proveedor['observaciones'],
            "</textarea></td>\n",
            "<td align='right' valign='bottom'><input type='submit' name='save' value='modificar'/></td>\n",
            "</tr>\n" , "</table></form>\n";

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

            /// mostramos las familias de artículos que suministra
            $this->mostrar_familias($mod);

            echo "</td>\n</tr>\n</table>\n";
         }
         else
         {
            /// mostramos el historial
            $this->mostrar_historial($mod);

            /// mostramos las familias de artículos que suministra
            $this->mostrar_familias($mod);
         }
      }
      else
      {
         echo "<div class='error'>Proveedor no encontrado</div>\n";
      }
   }

   private function mostrar_telefonos($tel1, $tel2)
   {
      echo "<input type='text' name='tlf1' value='" , $tel1 , "' size='8' maxlength='9'/>\n",
         " | <input type='text' name='tlf2' value='" , $tel2 , "' size='8' maxlength='9'/>\n";
   }

   private function mostrar_historial($mod)
   {
      $albaranes = $this->bd->select("SELECT COUNT(*) as total FROM albaranesprov WHERE codproveedor = '" . $this->proveedor['codproveedor'] . "';");
      $facturas = $this->bd->select("SELECT COUNT(*) as total FROM facturasprov WHERE codproveedor = '" . $this->proveedor['codproveedor'] . "';");

      echo "<div class='lista'>Historial</div>\n",
         "<table class='lista'>\n",
         "<tr class='destacado'>\n",
         "<td>Albaranes</td>\n",
         "<td>Facturas</td>\n",
         "</tr>\n",
         "<tr>\n",
         "<td><a href='ppal.php?mod=" , $mod , "&pag=albaranesprov&buscar=" , $this->proveedor['codproveedor'] , "&tipo=cop'>",
         number_format($albaranes[0]['total'], 0) , "</a></td>\n",
         "<td><a href='ppal.php?mod=" , $mod , "&pag=facturasprov&buscar=" , $this->proveedor['codproveedor'] , "&tipo=cop'>",
         number_format($facturas[0]['total'], 0) , "</a></td>\n",
         "</tr>\n",
         "</table>\n";
   }

   private function modificar($tlf1, $tlf2, $fax, $email, $web, $observaciones)
   {
      if( $this->bd->exec("UPDATE proveedores SET telefono1 = '" . $tlf1 . "', telefono2 = '" . $tlf2 . "', fax = '" . $fax . "',
         email = '" . $email . "', web = '" . $web . "', observaciones = '" . $observaciones . "'
         WHERE codproveedor = '" . $this->proveedor['codproveedor'] . "';") )
      {
         echo "<div class='mensaje'>Datos modificados correctamente</div>";
      }
      else
      {
         echo "<div class='error'>Error al modificar los datos</div>";
      }

      /// recargamos
      $this->proveedor = $this->proveedores->get($_GET['cod']);
   }

   private function mostrar_familias($mod)
   {
      $consulta = "select codfamilia, count(*) as total from articulos
         where referencia in (select distinct referencia from lineasalbaranesprov where idalbaran in
          (select idalbaran from albaranesprov where codproveedor = '" . $this->proveedor['codproveedor'] . "'))
           group by codfamilia order by codfamilia ASC;";

      $resultado = $this->bd->select($consulta);

      if($resultado AND $mod != 'contabilidad')
      {
         echo "<div class='lista'>Suministra</div>\n
         <div class='nube' id='nube_fam'></div>\n
         <script type='text/javascript'>
         google.load('visualization', '1');
         google.setOnLoadCallback(draw);
         function draw()
         {
            data = new google.visualization.DataTable();
            data.addColumn('string', 'Label');
            data.addColumn('number', 'Value');
            data.addColumn('string', 'Link');
            data.addRows(" , count($resultado) , ");\n";

      $i = 0;
      foreach($resultado as $col)
      {
         echo "data.setValue(" , $i , ", 0, '" , $col['codfamilia'] , "');
            data.setValue(" , $i , ", 1, " , $col['total'] , ");
            data.setValue(" , $i , ", 2, 'ppal.php?mod=" , $mod , "&amp;pag=familia&amp;cod=" , $col['codfamilia'] , "');\n";

         $i++;
      }

      echo "var outputDiv = document.getElementById('nube_fam');
            var tc = new TermCloud(outputDiv);
            tc.draw(data, null);
         }
         </script>";
      }
   }

   private function get_stats($codproveedor)
   {
      $retorno = false;

      if($codproveedor != '')
      {
         $consulta = "SELECT to_char(fecha,'FMMM') as mes, sum(total) as total FROM albaranesprov
            WHERE codproveedor = '$codproveedor' AND fecha >= '1-1-" . Date('Y') . "'
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
            "' alt='Importe albaranes / mes' title='Importe total de compras al mes al proveedor'/>\");\n",
            "//-->\n",
            "</script>\n",
            "</center>\n";
      }
   }
}

?>
