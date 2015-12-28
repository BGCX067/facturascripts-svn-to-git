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
require_once("clases/familias.php");

class script_ extends script
{
   private $familias;
   private $familia;

   public function __construct($ppal)
   {
      parent::__construct($ppal);

      $this->generico = true;
      $this->coletilla = "&amp;cod=" . $_GET['cod'];
      
      $this->familias = new familias();
      
      /// cargamos la familia
      if( $this->familias->get($_GET['cod'], $this->familia) )
      {
         $this->stats_familia($_GET['cod']);

         if($this->familia['articulos'] > 0)
         {
            $this->familia['p_bloqueados'] = number_format(($this->familia['bloqueados'] / $this->familia['articulos'] * 100), 2);
            $this->familia['p_stock'] = number_format(($this->familia['stock'] / $this->familia['articulos'] * 100), 2);
         }
         else
         {
            $this->familia['p_bloqueados'] = 0;
            $this->familia['p_stock'] = 0;
         }
      }
   }

   /// devuelve el titulo del script
   public function titulo()
   {
      return("Familia " . $_GET['cod']);
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

   /// genera la url necesaria para recargar el script
   public function recargar($mod, $pag)
   {
      return("ppal.php?mod=" . $mod . "&amp;pag=" . $pag . "&amp;cod=" . $_GET['cod']);
   }

   /// carga el cuerpo del script
   public function cuerpo($mod, $pag, &$datos)
   {
      $actualizaciones = false;

      if( $this->familia )
      {
         echo "<div class='destacado'><span>" , $this->familia['descripcion'],
            "</span></div>\n",
            "<div class='lista'>Art&iacute;culos</div>\n",
            "<div class='lista2'>\n",
            "Esta familia tiene <a href='ppal.php?mod=" , $mod , "&amp;pag=articulos&amp;f=" , $this->familia['codfamilia'] , "'>",
            number_format($this->familia['articulos'], 0) , "</a> art&iacute;culos, ",
            "<a href='ppal.php?mod=" , $mod , "&amp;pag=articulos&amp;f=" , $this->familia['codfamilia'] , "&amp;b=true'>",
            number_format($this->familia['bloqueados'], 0) , "</a> bloqueados y ",
            "<a href='ppal.php?mod=" , $mod , "&amp;pag=articulos&amp;f=" , $this->familia['codfamilia'] , "&amp;s=true'>",
            number_format($this->familia['stock'], 0) , "</a> en stock.\n";

         if($mod == 'principal')
         {
            echo "<br/>\nEl stock est&aacute; valorado (aproximadamente) en <b>" , number_format($this->familia['valor'], 2),
                    "</b> &euro;\n" , "</div>\n";
         }
         else
            echo "</div>\n";

         echo "<table width='100%'>\n<tr>\n<td align='center'><div id='chart_stock'></div></td>\n",
            "<td align='center'><div id='chart_actu'></div></td>\n</tr>\n",
            "<tr><td colspan='2' align='center'><div id='chart_albaranes'></div></td>\n</tr>\n</table>\n";

         /// mostramos el primer quesito
         if($this->familia['articulos'] > 0)
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
                  data.addColumn('string', 'tipo');
                  data.addColumn('number', 'numero');
                  data.addRows([
                     ['En stock', " , $this->familia['stock'] , "],
                     ['Bloqueados', " , $this->familia['bloqueados'] , "],
                     ['Resto', " , ($this->familia['articulos'] - $this->familia['stock'] - $this->familia['bloqueados']) , "]
                  ]);

                  // Instantiate and draw our chart, passing in some options.
                  var chart = new google.visualization.PieChart(document.getElementById('chart_stock'));
                  chart.draw(data, {width: 400, height: 200, is3D: true, legend: 'left'});
               }
               //-->
               </script>\n";
         }

         /// mostramos el segundo quesito
         if( $this->actualizaciones($this->familia['codfamilia'], $actualizaciones) )
         {
            $this->quesito_actualizaciones($actualizaciones, $this->familia['articulos']);
         }

         /// mostramos la gráfica de compra/venta de articulos de esta familia
         if($mod == 'principal')
         {
            $stats = false;

            if( $this->albaranes_stats($this->familia['codfamilia'], date("Y"), $stats) )
               $this->grafica_dos_variables(date("Y"), $stats);
            else if( $this->albaranes_stats($this->familia['codfamilia'], (date("Y") - 1), $stats) )
               $this->grafica_dos_variables((date("Y") - 1), $stats);
         }

         /// mostramos los proveedores que sirven articulos de esta familia
         $this->mostrar_proveedores($mod, $this->familia['codfamilia']);
      }
      else
         echo "<div class='error'>Familia no encontrada</div>\n";
   }

   /// rellena el número de artículos de una familia, así como otros datos de interes
   private function stats_familia($codfamilia)
   {
      $resultado = $this->bd->select("SELECT GREATEST( COUNT(referencia), 0) as art,
              GREATEST( SUM(case when stockfis > 0 then 1 else 0 end), 0) as stock,
              GREATEST( SUM(bloqueado::integer), 0) as bloq,
              GREATEST( SUM(case when not bloqueado and stockfis > 0 then pvp*stockfis else 0 end), 0) as valor
              FROM articulos WHERE codfamilia = '$codfamilia';");

      if($resultado)
      {
         $this->familia['articulos'] = $resultado[0]['art'];
         $this->familia['bloqueados'] = $resultado[0]['bloq'];
         $this->familia['stock'] = $resultado[0]['stock'];
         $this->familia['valor'] = $resultado[0]['valor'];
      }
      else
      {
         $this->familia['articulos'] = 0;
         $this->familia['stock'] = 0;
         $this->familia['bloqueados'] = 0;
         $this->familia['valor'] = 0;
      }
   }

   /// devuelve por parametro las fechas de actualizaciones de los articulos de la familia
   private function actualizaciones($codfamilia, &$actualizaciones)
   {
      $retorno = false;
      
      $consulta = "SELECT COUNT(*) as articulos, codfamilia, to_char(factualizado,'FMMM-YYYY') as fecha FROM articulos
         WHERE codfamilia = '$codfamilia' AND factualizado IS NOT NULL
          GROUP BY codfamilia, to_char(factualizado,'FMMM-YYYY') ORDER BY articulos DESC";

      $resultado = $this->bd->select_limit($consulta, 10, 0);
      if($resultado)
      {
         $actualizaciones = $resultado;
         $retorno = true;
      }

      return($retorno);
   }

    /// dibuja un quesito del total de articulos y lo divide en funcion de cuando se actualizo
   private function quesito_actualizaciones($stats, $total)
   {
      $restante = $total;

      if($stats AND $total > 0)
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
                  data.addColumn('string', 'fecha');
                  data.addColumn('number', 'total');
                  data.addRows([\n";

         $i = 0;
         foreach($stats as $col)
         {
            $fecha = explode('-', $col['fecha']);

            echo "['" , $meses[$fecha[0]] , ' ' , $fecha[1] , "', " , $col['articulos'] , "],\n";
            
            $restante -= $col['articulos'];
            $i++;
         }
         
         echo "['nunca', " , $restante , "]
            ]);

            // Instantiate and draw our chart, passing in some options.
            var chart = new google.visualization.PieChart(document.getElementById('chart_actu'));
            chart.draw(data, {width: 400, height: 200, is3D: true, title: 'Actualizaciones', titleFontSize: 12});
            }
            //-->
            </script>\n";
      }
   }
   
   private function albaranes_stats($codfamilia, $anyo, &$stats)
   {
      $retorno = false;

      $consulta = "SELECT 'cli' as tipo, to_char(albc.fecha,'FMMM') as mes, sum(lc.cantidad) as total
         FROM articulos a, lineasalbaranescli lc, albaranescli albc
         WHERE a.codfamilia = '$codfamilia' AND a.referencia = lc.referencia AND lc.idalbaran = albc.idalbaran
         AND albc.fecha >= '1-1-" . $anyo . "'
         GROUP BY to_char(fecha,'FMMM')
         UNION
         SELECT 'pro' as tipo, to_char(albp.fecha,'FMMM') as mes, sum(lp.cantidad) as total
         FROM articulos a, lineasalbaranesprov lp, albaranesprov albp
         WHERE a.codfamilia = '$codfamilia' AND a.referencia = lp.referencia AND lp.idalbaran = albp.idalbaran
         AND albp.fecha >= '1-1-" . $anyo . "'
         GROUP BY to_char(fecha,'FMMM')
         ORDER BY mes ASC;";

      $resultado = $this->bd->select($consulta);

      if($resultado)
      {
         /// inicializamos
         for($i = 1; $i < 13; $i++)
         {
            $stats[$i]['cli'] = 0;
            $stats[$i]['pro'] = 0;
         }

         foreach($resultado as $col)
         {
            if($col['tipo'] == 'cli')
               $stats[$col['mes']]['cli'] = $col['total'];
            else
               $stats[$col['mes']]['pro'] = $col['total'];
         }

         $retorno = true;
      }

      return($retorno);
   }

   private function grafica_dos_variables($anyo, &$datos)
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
         echo "['" , $meses[$i] , "', " , $datos[$i]['cli'] , ", " , $datos[$i]['pro'] , "],\n";

      echo "['" , $meses[12] , "', " , $datos[12]['cli'] , ", " , $datos[12]['pro'] , "]
         ]);

         // Instantiate and draw our chart, passing in some options.
         var chart = new google.visualization.AreaChart(document.getElementById('chart_albaranes'));
         chart.draw(data, {width: 750, height: 250, is3D: true, title: 'Historial de compras/ventas del año " , $anyo,
         "', titleY: 'ud', titleFontSize: 12, legend: 'top'});
         }
         //-->
         </script>\n";
   }

   private function mostrar_proveedores($mod, $familia)
   {
      $consulta = "select codproveedor, nombre, count(idalbaran) as albaranes from albaranesprov
         where idalbaran in (select distinct idalbaran from lineasalbaranesprov where referencia in
          (select referencia from articulos where codfamilia = '" . $familia . "'))
         group by codproveedor, nombre order by nombre ASC;";

      $resultado = $this->bd->select($consulta);

      if( $resultado )
      {
         echo "<div class='lista'>Proveedores</div>\n",
            "<table class='lista'>\n",
            "<tr class='destacado'>\n",
            "<td width='70'>C&oacute;digo</td>\n",
            "<td>Nombre</td>\n",
            "<td align='right'>Albaranes</td>\n",
            "</tr>\n";
         
         foreach($resultado as $col)
         {
            echo "<tr>\n",
            "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=proveedor&amp;cod=" , $col['codproveedor'] , "'>" , $col['codproveedor'] , "</a></td>\n",
            "<td>" , $col['nombre'] , "</td>\n",
            "<td align='right'>" , number_format($col['albaranes'], 0) , "</td>\n",
            "</tr>\n";
         }

         echo "</table>\n";
      }
   }
}

?>