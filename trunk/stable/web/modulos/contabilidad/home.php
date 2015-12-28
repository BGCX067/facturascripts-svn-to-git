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
require_once("clases/facturas_cli.php");
require_once("clases/facturas_prov.php");
require_once("clases/opciones.php");

class script_ extends script
{
   private $facturas_cli;
   private $facturas_prov;
   private $opciones;

   public function __construct($ppal)
   {
      parent::__construct($ppal);

      $this->facturas_cli = new facturas_cli();
      $this->facturas_prov = new facturas_prov();
      $this->opciones = new opciones();
   }

   /// codigo javascript
   public function javas()
   {
   ?>
      <script type="text/javascript" src="http://www.google.com/jsapi"></script>
      <script type="text/javascript">
      <!--
      function fs_onload() {}
      function fs_unload() {}
      //-->
      </script>
   <?php
   }
   
   /// carga el cuerpo del script
   public function cuerpo($mod, $pag, &$datos)
   {
      $opciones = false;
      $stats = false;

      if( $this->opciones->all($opciones) )
      {
         /// graficas
         if( $this->albaranescli_stats($opciones['ejercicio'], $stats) )
         {
            echo "<table width='100%'>\n<tr>\n<td align='center'><div id='chart_facturados'></div></td>\n",
               "<td align='center'><div id='chart_facturas'></div></td>\n</tr>\n</table>\n";

            $facturados = number_format($stats['facturados'] / $stats['total'] * 100, 2);
            $no_facturados = number_format(100 - $facturados, 2);

            echo "<script type=\"text/javascript\">
               <!--
               // Load the Visualization API and the piechart package.
               google.load('visualization', '1', {'packages':['piechart']});

               // Set a callback to run when the Google Visualization API is loaded.
               google.setOnLoadCallback(drawChart);

               // Callback that creates and populates a data table,
               // instantiates the pie chart, passes in the data and
               // draws it.
               function drawChart()
               {
                  // Create our data table.
                  var data = new google.visualization.DataTable();
                  data.addColumn('string', 'tipo');
                  data.addColumn('number', 'numero');
                  data.addRows([
                     ['Facturados', " , $facturados , "],
                     ['Resto', " , $no_facturados , "]
                  ]);

                  // Instantiate and draw our chart, passing in some options.
                  var chart = new google.visualization.PieChart(document.getElementById('chart_facturados'));
                  chart.draw(data, {width: 360, height: 160, is3D: true, legend: 'left', titleFontSize: 12,
                  title: 'Albaranes de clientes'});
               }
               //-->
               </script>\n";

            $stats = false;
            if( $this->facturascli_stats($opciones['ejercicio'], $stats) )
               $this->grafica_facturas($stats, $opciones['ejercicio']);
         }
         else
            echo "<div class='mensaje'>No hay suficientes datos para las estad&iacute;sticas</div>\n";
      }

      /// Ultimas facturas de clientes
      $desde = 0;
      $total = 0;
      $num = round(FS_LIMITE/3);
      if( $this->facturas_cli->ultimas($num, $facturas_cli, $total, $desde) )
         $this->mostrar_facturas_cli($mod, $facturas_cli);

      /// Ultimas facturas de proveedor
      if( $this->facturas_prov->ultimas($num, $facturas_prov, $total, $desde) )
         $this->mostrar_facturas_prov($mod, $facturas_prov);
   }
   
   private function mostrar_facturas_cli($mod, $facturas)
   {
      echo "<div class='lista'>&Uacute;limas facturas de clientes</div>\n",
         "<table class='lista'>\n",
         "<tr class='destacado'>\n",
         "<td>C&oacute;digo</td>\n",
         "<td>Cliente</td>\n",
         "<td>Observaciones</td>\n",
         "<td align='right'>Total</td>\n",
         "<td align='right'>Fecha</td>\n",
         "</tr>\n";

      foreach($facturas as $col)
      {
         echo "<tr>\n",
            "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=facturacli&amp;id=" , $col['idfactura'] , "'>" , $col['codigo'] , "</a></td>\n",
            "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=cliente&amp;cod=" , $col['codcliente'] , "'>" , $col['nombrecliente'] , "</a></td>\n",
            "<td>" , $col['observaciones'] , "</td>\n",
            "<td align='right'>" , number_format($col['total'], 2) , " &euro;</td>\n",
            "<td align='right'>" , Date('d-m-Y', strtotime($col['fecha'])) , "</td>\n",
            "</tr>\n";
      }

      echo "</table>\n";
   }

   private function mostrar_facturas_prov($mod, $facturas)
   {
      echo "<div class='lista'>&Uacute;limas facturas de proveedores</div>\n",
         "<table class='lista'>\n",
         "<tr class='destacado'>\n",
         "<td>C&oacute;digo</td>\n",
         "<td>Proveedor</td>\n",
         "<td>Observaciones</td>\n",
         "<td align='right'>Total</td>\n",
         "<td align='right'>Fecha</td>\n",
         "</tr>\n";

      foreach($facturas as $col)
      {
         echo "<tr>\n",
            "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=facturaprov&amp;id=" , $col['idfactura'] , "'>" , $col['codigo'] , "</a></td>\n",
            "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=proveedor&amp;cod=" , $col['codproveedor'] , "'>" , $col['nombre'] , "</a></td>\n",
            "<td>" , $col['observaciones'] , "</td>\n",
            "<td align='right'>" , number_format($col['total'], 2) , " &euro;</td>\n",
            "<td align='right'>" , Date('d-m-Y', strtotime($col['fecha'])) , "</td>\n",
            "</tr>\n";
      }

      echo "</table>\n";
   }
   
   /// devuelve un array con el numero de albaranes facturados y el numero que no, asi como el total
   private function albaranescli_stats($codejercicio, &$stats)
   {
      $retorno = false;
      
      if($codejercicio != '')
      {
         $resultado = $this->bd->select("SELECT COUNT(idalbaran) as total, ptefactura FROM albaranescli
            WHERE codejercicio = '$codejercicio' GROUP BY ptefactura ORDER BY ptefactura ASC;");

         if($resultado)
         {
            $stats['facturados'] = $resultado[0]['total'];
            $stats['pendientes'] = $resultado[1]['total'];
            $stats['total'] = ($resultado[0]['total'] + $resultado[1]['total']);
            $retorno = true;
         }
      }
      
      return($retorno);
   }
   
   /// Devuelve un array con la facturacion total de cada mes del presente año fiscal
   private function facturascli_stats($codejercicio, &$stats)
   {
      $retorno = false;
      
      if($codejercicio != '')
      {
         $consulta = "set datestyle = dmy;
            SELECT SUM(total) as total, to_char(fecha,'FMMM') as mes FROM facturascli
            WHERE codejercicio = '$codejercicio' GROUP BY to_char(fecha,'FMMM') ORDER BY mes ASC;";

         $resultado = $this->bd->select($consulta);
         if($resultado)
         {
            /// inicializamos
            for($i = 1; $i <= 12; $i++)
               $stats[$i] = 0;

            foreach($resultado as $col)
               $stats[$col['mes']] = $col['total'];

            $retorno = true;
         }
      }
      
      return($retorno);
   }
   
   private function grafica_facturas($stats, $codejercicio)
   {
      if($stats)
      {
         $meses = Array(
            1 => 'Ene',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Abr',
            5 => 'May',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Ago',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Dic'
         );

         echo "<script type=\"text/javascript\">
            <!--
            // Load the Visualization API and the piechart package.
            google.load('visualization', '1', {packages:['areachart']});

            // Set a callback to run when the Google Visualization API is loaded.
            google.setOnLoadCallback(drawChart);

            // Callback that creates and populates a data table,
            // instantiates the pie chart, passes in the data and
            // draws it.
            function drawChart()
            {
               // Create our data table.
               var data = new google.visualization.DataTable();
               data.addColumn('string', 'mes');
               data.addColumn('number', 'Facturas');
               data.addRows(" , count($stats) , ");\n";

         for($i = 0; $i < 12; $i++)
         {
            echo "data.setCell(" , $i , ", 0, '" , $meses[$i + 1] , "');\n";
            echo "data.setCell(" , $i , ", 1, " , $stats[$i + 1] , ");\n";
         }

         echo "// Instantiate and draw our chart, passing in some options.
            var chart = new google.visualization.AreaChart(document.getElementById('chart_facturas'));
            chart.draw(data, {width: 600, height: 180, is3D: true, title: 'Facturación por mes del ejercicio " , $codejercicio,
            "', titleFontSize: 12, legend: 'none'});
            }
            //-->
            </script>\n";
      }
   }
}

?>