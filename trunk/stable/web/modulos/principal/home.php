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

require_once("clases/articulos.php");
require_once("clases/albaranes_cli.php");
require_once("clases/albaranes_prov.php");
require_once("clases/agentes.php");
require_once("clases/opciones.php");

class script_ extends script
{
   private $articulos;
   private $albaranes_cli;
   private $albaranes_prov;
   private $agentes;
   private $opciones;
   private $albaranes_hoy;

   public function __construct($ppal)
   {
      parent::__construct($ppal);

      $this->articulos = new articulos();
      $this->albaranes_cli = new albaranes_cli();
      $this->albaranes_prov = new albaranes_prov();
      $this->agentes = new agentes();
      $this->opciones = new opciones();

      $this->albaranes_hoy = Array(
         'num' => 0,
         'total' => 0
      );
   }

   /// codigo javascript
   public function javas()
   {
      ?>
      <script type="text/javascript" src="http://www.google.com/jsapi"></script>
      <script type="text/javascript">
      <!--

      function fs_onload()
      {
         document.articulos.buscar.focus();
      }

      function fs_unload() {
      }

      //-->
      </script>
      <?php
   }
   
   /// carga el cuerpo del script
   public function cuerpo($mod, $pag, &$datos)
   {
      $opciones = false;
      $albaranes_cli = false;
      $albaranes_prov = false;
      $total = 0;

      $this->articulos->show_search2($mod, '', '', '', false, false);

      /// obtenemos las estadisticas de albaranes del mes por agente
      $this->albaranes_agente_mes($stats);

      /// graficas
      echo "<table width='100%'>\n" , "<tr>\n",
         "<td valign='top'><div id='chart_albaranes'></div></td>\n",
         "<td valign='top'><div id='quesito_agentes'></div></td>\n",
         "</tr>\n" , "</table>\n";

      $this->mostrar_grafo_albaranes();
      $this->mostrar_quesito_agentes($stats);
      
      
      /// albaranes
      $num = round(FS_LIMITE*2/3);
      $this->albaranes_cli->pendientes($num, $albaranes_cli);
      $this->albaranes_prov->pendientes($num, $albaranes_prov);
      $tipos = $this->albaranes_prov->tipos();

      echo "<table width='100%'>\n" , "<tr>\n",
         "<td width='50%' valign='top'>" , $this->pendientes_cli($mod, $albaranes_cli) , "</td>\n",
         "<td width='50%' valign='top'>" , $this->pendientes_prov($mod, $albaranes_prov, $tipos) , "</td>\n",
         "</tr>\n" , "</table>\n";
   }
   
   /// muestra el ultimos albaranes de clientes sin revisar
   private function pendientes_cli($mod, &$albaranes)
   {
      echo "<div class='lista'>Albaranes no revisados de Clientes</div>\n";

      if($albaranes)
      {
         echo "<table class='lista'>\n",
            "<tr class='destacado'>\n",
            "<td>C&oacute;digo [n&uacute;mero 2]</td>\n",
            "<td>Cliente</td>\n",
            "<td align='right'>Fecha</td>\n",
            "</tr>\n";
         
         foreach($albaranes as $col)
         {
            echo "<tr>\n",
               "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=albarancli&amp;id=" , $col['idalbaran'] , "'>" , $col['codigo'] , "</a>";

            if($col['numero2'] != '') { echo ' [' , $col['numero2'] , "]</td>\n"; }
            else { echo '</td>'; }

            echo "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=cliente&amp;cod=" , $col['codcliente'] , "'>",
               $col['nombrecliente'] , "</a></td>\n" , "<td align='right'>" , Date('d-m-Y', strtotime($col['fecha'])) , "</td>\n",
               "</tr>\n";
         }

         echo "</table>\n";
      }
      else
      {
         echo "<div class='mensaje'>Nada que mostrar</div>\n";
      }
   }
   
   /// muestra el ultimos albaranes de proveedores sin revisar
   private function pendientes_prov($mod, &$albaranes, &$tipos)
   {
      echo "<div class='lista'>Albaranes no revisados de Proveedores</div>\n";

      if($albaranes)
      {
         echo "<table class='lista'>\n",
            "<tr class='destacado'>\n",
            "<td>C&oacute;digo [n&uacute;mproveedor]</td>\n",
            "<td>Proveedor</td>\n",
            "<td align='right'>Fecha</td>\n",
            "</tr>\n";
         
         foreach($albaranes as $col)
         {
            switch($col['tipo'])
            {
               case 1:
               case 2:
                  echo "<tr class='rojo'>\n";
                  break;

               case 5:
                  echo "<tr class='bloqueado'>\n";
                  break;

               default:
                  echo "<tr>\n";
                  break;
            }

            echo "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=albaranprov&amp;id=" , $col['idalbaran'] , "'>" , $col['codigo'] , "</a>";

            if($col['numproveedor'] != '') { echo " [" , $col['numproveedor'] , "]</td>\n"; }
            else { echo "</td>\n";   }

            echo "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=proveedor&amp;cod=" , $col['codproveedor'] , "'>",
               $col['nombre'] , "</a></td>\n" , "<td align='right'>" , Date('d-m-Y', strtotime($col['fecha'])) , "</td>\n",
               "</tr>\n";
         }

         echo "</table>\n";
      }
      else
      {
         echo "<div class='mensaje'>Nada que mostrar</div>\n";
      }
   }

   /// devuelve por referencia un array con el numero de albaranes/total de cada agente en el presente mes
   private function albaranes_agente_mes(&$stats)
   {
      $retorno = false;

      $resultado = $this->bd->select("set datestyle = dmy;
         SELECT codagente, count(idalbaran) as albaranes, sum(total) as total
         FROM albaranescli WHERE fecha >= '1-" . Date("n-Y") . "' GROUP BY codagente;");

      if($resultado)
      {
         foreach($resultado as $col)
         {
            $stats[$col['codagente']]['codagente'] = $col['codagente'];
            $stats[$col['codagente']]['albaranes'] = $col['albaranes'];
            $stats[$col['codagente']]['total'] = $col['total'];
         }

         $retorno = true;
      }

      return($retorno);
   }

   /// devuelve por referencia el numero/total de albaranes de cada dia del presente mes
   private function albaranes_dias_mes(&$stats)
   {
      $retorno = false;

      $resultado = $this->bd->select("set datestyle = dmy;
         SELECT to_char(fecha,'FMDD') as dia, count(idalbaran) as albaranes, sum(total) as total
         FROM albaranescli WHERE fecha >= '1-" . Date("n-Y") . "'
         GROUP BY to_char(fecha,'FMDD') ORDER BY dia ASC;");

      if($resultado)
      {
         /// inicializamos
         for($i = 1; $i <= 30; $i++)
         {
            $stats[$i] = 0;
         }

         foreach($resultado as $col)
         {
            $stats[$col['dia']] = $col['total'];

            /// guardamos los datos de hoy
            if($col['dia'] == Date('j'))
            {
               $this->albaranes_hoy['num'] = $col['albaranes'];
               $this->albaranes_hoy['total'] = $col['total'];
            }
         }

         $retorno = true;
      }

      return($retorno);
   }

   /// mostramos el quesito con la distribucion de total/agente
   private function mostrar_quesito_agentes($stats)
   {
      $agentes = false;
      $total = 0;

      if($stats)
      {
         $this->agentes->all($agentes);

         /// calculamos el total y los parametros
         foreach($stats as $col)
         {
            $total += $col['total'];
         }

         if($total > 0)
         {
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
                  data.addColumn('string', 'Agente');
                  data.addColumn('number', 'Total');
                  data.addRows(" , count($stats) , ");\n";

            $i = 0;
            foreach($stats as $col)
            {
               if($agentes[$col['codagente']]['nombre'] != '')
               {
                  echo "data.setCell(" , $i , ", 0, '" , $agentes[$col['codagente']]['nombre'] , "');\n";
               }
               else
               {
                  echo "data.setCell(" , $i , ", 0, 'ninguno');\n";
               }

               echo "data.setCell(" , $i , ", 1, " , $col['total'] , ");\n";
               $i++;
            }

            echo "// Instantiate and draw our chart, passing in some options.
                  var chart = new google.visualization.PieChart(document.getElementById('quesito_agentes'));
                  chart.draw(data, {width: 360, height: 160, is3D: true, titleFontSize: 12,
                  title: '% ventas de cada agente (del presente mes)'});
               }
               //-->
               </script>\n";
         }
      }
   }

   private function mostrar_grafo_albaranes()
   {
      $stats = false;

      /// obtenemos las estadisticas de albaranes del mes/dia
      if( $this->albaranes_dias_mes($stats) )
      {
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
               data.addColumn('string', 'dia');
               data.addColumn('number', 'total');
               data.addRows(" , count($stats) , ");\n";

         for($i = 0; $i < count($stats); $i++)
         {
            echo "data.setCell(" , $i , ", 0, '" , ($i + 1) , "');\n";
            echo "data.setCell(" , $i , ", 1, " , $stats[$i + 1] , ");\n";
         }

         echo "// Instantiate and draw our chart, passing in some options.
            var chart = new google.visualization.AreaChart(document.getElementById('chart_albaranes'));
            chart.draw(data, {width: 600, height: 180, is3D: true, title: 'Importe total de ventas al dia del presente mes',
            titleFontSize: 12, legend: 'none'});
            }
            //-->
            </script>\n";
      }
   }
}

?>
