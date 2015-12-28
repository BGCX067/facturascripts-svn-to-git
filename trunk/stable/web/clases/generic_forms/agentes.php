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

require_once("clases/agentes.php");

class script_ extends script
{
   private $agentes;
   private $ranking;
   private $stats_agente;

   public function __construct($ppal)
   {
      parent::__construct($ppal);

      $this->agentes = new agentes();
      $this->get_ranking();

      if( isset($_GET['cod']) )
         $this->stats_agente = $this->get_stats($_GET['cod']);
   }

   /// devuelve el titulo del script
   public function titulo()
   {
      return("Agentes");
   }

   /// devuelve el script javascript necesario para la pagina
   public function javas()
   {
      ?>
      <script type="text/javascript" src="http://www.google.com/jsapi"></script>
      <script type="text/javascript">
      <!--

      function fs_onload() {
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
      if( isset($_GET['cod']) )
         $this->mostrar_stats_agente();

      $agentes = false;
      if( $this->agentes->all($agentes) )
      {
         $stats = $this->agentes->stats();

         echo "<div class='lista'>Agentes</div>\n",
            "<table class='lista'>\n",
            "<tr class='destacado'>\n",
            "<td width='40' align='right'>Cod.</td>\n",
            "<td width='10'></td>\n",
            "<td>Nombre</td>\n",
            "<td>Tel&eacute;fono</td>\n",
            "<td align='right'>Reservas</td>\n",
            "<td align='right'>Albaranes de clientes</td>\n",
            "<td align='right'>Albaranes de proveedores</td>\n",
            "</tr>\n";

         foreach($agentes as $col)
         {
            if( !isset($_GET['cod']) )
               echo "<tr>\n";
            else if($_GET['cod'] == $col['codagente'])
               echo "<tr class='verde'>\n";
            else
               echo "<tr>\n";

            echo "<td align='right'><span title='" , $this->mostrar_ranking($col['codagente']) , "'><b>" , $col['codagente'] , "</b></span></td>\n",
               "<td width='10'></td>\n",
               "<td><a href='" . $this->recargar($mod, $pag) . "&amp;cod=" , $col['codagente'] , "' title='" , $this->mostrar_ranking($col['codagente']),
               "'>" , $col['nombre'] , ' ' , $col['apellidos'] , "</a></td>\n",
               "<td>" , $this->mostrar_telefono($col['telefono']) , "</td>\n";

            if($stats[$col['codagente']]['reservas'] > 0)
            {
               echo "<td align='right'><a href='ppal.php?mod=" , $mod , "&pag=reservas&buscar=" , $col['codagente'] , "&tipo=age'>",
                  number_format($stats[$col['codagente']]['reservas'], 0) , "</a></td>\n";
            }
            else
               echo "<td align='right'>-</td>\n";

            if($stats[$col['codagente']]['albaranescli'] > 0)
            {
               echo "<td align='right'><a href='ppal.php?mod=" , $mod , "&pag=albaranescli&buscar=" , $col['codagente'] , "&tipo=age'>",
                  number_format($stats[$col['codagente']]['albaranescli'], 0) , "</a></td>\n";
            }
            else
               echo "<td align='right'>-</td>\n";

            if($stats[$col['codagente']]['albaranesprov'] > 0)
            {
               echo "<td align='right'><a href='ppal.php?mod=" , $mod , "&pag=albaranesprov&buscar=" , $col['codagente'] , "&tipo=age'>",
                  number_format($stats[$col['codagente']]['albaranesprov'], 0) , "</a></td></tr>";
            }
            else
               echo "<td align='right'>-</td>\n";
         }
         echo "</table>\n";
      }
      else
         echo "<div class='mensaje'>No hay agentes</div>\n";
   }

   private function get_ranking()
   {
      $consulta = "select codagente, SUM(total) as total from albaranescli where codagente is not null
         group by codagente order by SUM(total) DESC;";

      $resultado = $this->bd->select($consulta);
      if($resultado)
      {
         $i = 1;

         foreach($resultado as $col)
         {
            $this->ranking[$col['codagente']] = $i;
            $i++;
         }
      }
      else
         $this->ranking = false;
   }

   private function mostrar_ranking($codagente)
   {
      if($this->ranking[$codagente] > 0)
         echo '#' , $this->ranking[$codagente] , ' del ranking';
      else
         echo 'No aparece en el ranking';
   }

   private function mostrar_telefono($telefono)
   {
      $retorno = '-';

      if($telefono != '')
         $retorno = $telefono;

      return($retorno);
   }
   
   private function get_stats($codagente)
   {
      $retorno = false;

      if($codagente != '')
      {
         $consulta = "SELECT 'cli' as tipo, to_char(fecha,'FMMM') as mes, sum(abs(total)) as total
            FROM albaranescli WHERE codagente = '" . $codagente . "' AND fecha >= '1-1-" . Date('Y') . "'
            GROUP BY to_char(fecha,'FMMM')
            UNION
            SELECT 'pro' as tipo, to_char(fecha,'FMMM') as mes, sum(abs(total)) as total
            FROM albaranesprov WHERE codagente = '" . $codagente . "' AND fecha >= '1-1-" . Date('Y') . "'
            GROUP BY to_char(fecha,'FMMM')
            ORDER BY mes ASC;";

         $resultado = $this->bd->select($consulta);
         if($resultado)
         {
            /// inicializamos
            for($i = 1; $i < 13; $i++)
            {
               $retorno[$i]['cli'] = 0;
               $retorno[$i]['pro'] = 0;
            }

            foreach($resultado as $col)
            {
               if($col['tipo'] == 'cli')
                  $retorno[$col['mes']]['cli'] = $col['total'];
               else
                  $retorno[$col['mes']]['pro'] = $col['total'];
            }
         }
      }

      return($retorno);
   }

   private function mostrar_stats_agente()
   {
      echo "<div class='centrado' id='grafica'></div>\n";

      if( $this->stats_agente )
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
         google.load('visualization', '1', {'packages':['areachart']});

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
            data.addColumn('number', 'Ventas');
            data.addColumn('number', 'Compras');
            data.addRows([\n";

      for($i = 1; $i < 12; $i++)
         echo "['" , $meses[$i] , "', " , $this->stats_agente[$i]['cli'] , ", " , $this->stats_agente[$i]['pro'] , "],\n";

      echo "['" , $meses[12] , "', " , $this->stats_agente[12]['cli'] , ", " , $this->stats_agente[12]['pro'] , "]
         ]);

         // Instantiate and draw our chart, passing in some options.
         var chart = new google.visualization.AreaChart(document.getElementById('grafica'));
         chart.draw(data, {width: 750, height: 250, is3D: true, title: 'Historial de compras/ventas del año " , Date('Y'),
         " del agente " , $this->agentes->get_nombre($_GET['cod']) , "', titleY: 'euros', titleFontSize: 12, legend: 'top'});
         }
         //-->
         </script>\n";
      }
      else
         echo "<div class='mensaje'>No hay datos suficientes</div>\n";
   }
}

?>