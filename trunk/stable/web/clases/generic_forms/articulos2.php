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

class script_ extends script
{
   private $articulos;
   private $familias;

   public function __construct($ppal)
   {
      parent::__construct($ppal);
      $this->generico = true;
      $this->coletilla = '';
      
      if( isset($_GET['buscar']) )
         $this->coletilla .= "&amp;buscar=" . rawurldecode($_GET['buscar']);
      if( isset($_GET['tipo']) )
         $this->coletilla .= "&amp;tipo=" . $_GET['tipo'];
      if( isset($_GET['f']) )
         $this->coletilla .= "&amp;f=" . $_GET['f'];
      
      $this->articulos = new articulos();
      $this->familias = new familias();
   }

   /// devuelve el titulo del script
   public function titulo()
   {
      return("Art&iacute;culos");
   }

   /// devuelve el script javascript necesario para la pagina
   public function javas()
   {
      ?>
      <script type="text/javascript">
         function fs_onload()
         {
            document.articulos.buscar.focus();
            document.articulos.buscar.select();
         }
         function fs_unload() {}
      </script>
      <?php
   }

   /// captura las variables necesarias para el script enviadas por GET y POST
   public function datos()
   {
      $datos = Array(
         'buscar' => '',
         'buscar_url' => '',
         'tipo' => '',
         'pagina' => 0,
         'total' => 0,
         'fam' => '-',
         'stock' => FALSE,
         'bloq' => FALSE
      );
      
      if( isset($_GET['buscar']) )
      {
         $datos['buscar'] = rawurldecode($_GET['buscar']);
         $datos['buscar_url'] = $_GET['buscar'];
      }
      
      if( isset($_GET['tipo']) )
         $datos['tipo'] = $_GET['tipo'];
      
      if( isset($_GET['p']) )
         $datos['pagina'] = $_GET['p'];
      
      if( isset($_GET['t']) )
         $datos['total'] = $_GET['t'];
      
      if( isset($_GET['s']) )
         $datos['stock'] = $_GET['s'];
      
      if( isset($_GET['b']) )
         $datos['bloq'] = $_GET['b'];
      
      if( isset($_GET['f']) )
      {
         if($_GET['f'] != '')
            $datos['fam'] = $_GET['f'];
      }
      
      return $datos;
   }

   /// carga el cuerpo del script
   public function cuerpo($mod, $pag, &$datos)
   {
      $this->articulos->show_search2($mod, $datos['buscar'], $datos['tipo'], $datos['fam'], $datos['stock'], $datos['bloq']);

      if($datos['buscar'] != '' OR $datos['fam'] != '-')
      {
         if($datos['tipo'] == 'mov') /// movimientos de stock
         {
            $movimientos = $this->articulos->lista_last_mov_stock($datos['buscar'], 0);
            if($movimientos)
            {
               $this->mostrar_movimientos($mod, $movimientos);
               $this->paginar("ppal.php?mod=" . $mod . "&amp;pag=" . $pag . "&amp;buscar=" . $datos['buscar_url'] . "&amp;tipo=mov",
                       FS_LIMITE, $datos['pagina'], $datos['total']);
            }
            else
               echo "<div class='mensaje'><b>0</b> resultados encontrados</div>\n";
         }
         else /// busqueda normal
         {
            $articulos = $this->articulos->buscar2($datos['buscar'], $datos['tipo'], $datos['fam'], $datos['stock'], $datos['bloq'],
                    FS_LIMITE, $datos['pagina'], $datos['total']);
            if($articulos)
            {
               echo "<div class='lista'>Mostrando " , count($articulos) , " de " , number_format($datos['total'], 0) , " resultados encontrados</div>\n";
               if(count($articulos) == 1)
               {
                  /// redireccionamos al unico articulos encontrado
                  echo "<div class='mensaje'><img src='images/progreso.gif' align='middle' alt='en progreso'/>
                     Redireccionando a <b>" , $articulos[0]['referencia'] , "</b> ...</div>\n";
                  /// redirecciona
                  echo "<script type=\"text/javascript\">
                     <!--
                     function fs_onload() {
                        setTimeout('recargar()', 500);
                     }
                     function recargar() {
                        window.location.href = \"ppal.php?mod=" , $mod , "&pag=articulo&ref=" , rawurlencode($articulos[0]['referencia']) , "\";
                     }
                     //-->
                     </script>\n";
               }
               else
                  $this->mostrar_con_pvp($mod, $datos['tipo'], $articulos);
               $this->paginar("ppal.php?mod=" . $mod . "&amp;pag=" . $pag . "&amp;buscar=" . $datos['buscar_url'] . "&amp;tipo=" . $datos['tipo'].
                       "&amp;f=" . $datos['fam'] . "&amp;s=" . $datos['stock'] . "&amp;b=" . $datos['bloq'], FS_LIMITE, $datos['pagina'], $datos['total']);
            }
            else
               echo "<div class='mensaje'>0 resultados</div>\n";
         }
      }
      else
      {
         $articulos = $this->articulos->lista_top_ventas(FS_LIMITE*4);
         if($articulos)
            $this->listar_top_ventas($mod, $articulos);
      }
   }

   private function listar_top_ventas($mod, $articulos)
   {
      echo "<div class='lista'>Top Ventas (unidades)</div>\n",
         "<ul class='horizontal'>\n";
      
      foreach($articulos as $col)
         echo "<li><a href='ppal.php?mod=" , $mod , "&amp;pag=articulo&amp;ref=" , rawurlencode($col['referencia']) , "'>" , $col['referencia'],
            "</a> (" , $col['ventas'] , ")</li>\n";
      
      echo "</ul>\n";
   }

   private function mostrar_con_pvp($mod, $tipo, &$articulos)
   {
      echo "<table class='lista'>\n",
         "<tr class='destacado'>\n",
         "<td>Referencia</td>\n",
         "<td>Cod. Equiv.</td>\n",
         "<td>Familia</td>\n",
         "<td>Descripci&oacute;n</td>\n",
         "<td align='right'>PVP+IVA</td>\n",
         "<td align='right'>Stock</td>\n",
         "<td width='30'></td>\n",
         "</tr>\n";

      foreach($articulos as $col)
      {
         if($col['bloqueado'] == 't')
            echo '<tr class="rojo">' , "\n";
         else if($col['stockfis'] <= 0)
            echo '<tr class="amarillo">' , "\n";
         else
            echo '<tr>' , "\n";

         if($col['destacado'] == 't' AND ($tipo == 'eqc' OR $tipo == 'xeq'))
            echo '<td><img src="images/help-about.png" alt="destacado"/> ';
         else
            echo '<td>';

         echo "<a href='ppal.php?mod=" , $mod , "&amp;pag=articulo&amp;ref=" , rawurlencode($col['referencia']) , "'>" , $col['referencia'] , "</a></td>\n",
            "<td>" , $this->mostrar_cod_equivalencia($col['equivalencia']) , "</td>\n",
            "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=familia&amp;cod=" , $col['codfamilia'] , "'>" , $col['codfamilia'] , "</a></td>\n",
            "<td>" , $col['descripcion'] , "</td>\n",
            "<td align='right'><span title='" , $this->mostrar_actualizado($col['factualizado']) , "'>" , number_format($col['pvp_iva'], 2) , " &euro;</span></td>\n",
            "<td align='right'><b>" , number_format($col['stockfis'], 0) , "</b></td>\n";

         if($col['bloqueado'] == 'f' AND ($col['stockfis'] > 0 OR $col['controlstock'] == 't'))
         {
            echo '<td align="right"><a href="ppal.php?mod=' , $mod , '&amp;pag=carrito&amp;formulario=add&amp;ref=' , $col['referencia'],
                    '&amp;pvp=' , $col['pvp'] , '">' , "\n",
               '<img valign="middle" src="images/carrito.png" alt="a&ntilde;adir al carrito" title="a&ntilde;adir al carrito"/></a></td>' , "\n",
               '</tr>' , "\n";
         }
         else
            echo "<td></td>\n</tr>\n";
      }

      echo "</table>\n";
   }

   private function mostrar_actualizado($fecha)
   {
      if($fecha != '')
         return "Actualizado el " . Date('d-m-Y', strtotime($fecha));
      else
         return '-';
   }

   private function mostrar_cod_equivalencia($codigo)
   {
      if($codigo != '')
         return $codigo;
      else
         return '-';
   }

   private function mostrar_movimientos($mod, &$articulos)
   {
      echo "<table class='lista'>\n",
         "<tr class='destacado'>\n",
         "<td>Referencia</td>\n",
         "<td>Almac&eacute;n</td>\n",
         "<td>IP / Motivo</td>\n",
         "<td align='center'>Movimiento</td>\n",
         "<td align='right'>Fecha</td>\n",
         "</tr>\n";

      foreach($articulos as $col)
      {
         if($col['bloqueado'] == 't')
            echo "<tr class='bloqueado'>\n";
         else
            echo "<tr>\n";

         echo "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=articulo&amp;ref=" , rawurlencode($col['referencia']) , "'>" , $col['referencia'] , "</a></td>\n",
            "<td>" , $col['codalmacen'] , "</td>\n",
            "<td>" , $col['motivo'] , "</td>\n",
            "<td align='center'>" , $col['cantidadini'] , " -> " , $col['cantidadfin'] , "</td>\n",
            "<td align='right'>" , Date('d-m-Y', strtotime($col['fecha'])) , "</td>\n",
            "</tr>\n";
      }

      echo "</table>\n";
   }
}

?>
