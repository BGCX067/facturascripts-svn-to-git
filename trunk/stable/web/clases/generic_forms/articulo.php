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
require_once("clases/almacenes.php");
require_once("clases/articulos.php");
require_once("clases/familias.php");
require_once("clases/impuestos.php");

class script_ extends script
{
   private $articulos;
   private $familias;
   private $impuestos;
   private $almacenes;
   public $articulo;
   public $all_familias;
   public $all_impuestos;

   public function __construct($ppal)
   {
      parent::__construct($ppal);
      $this->generico = FALSE;
      $this->articulos = new articulos();
      $this->familias = new familias();
      $this->impuestos = new impuestos();
      $this->almacenes = new almacenes();

      /// cargamos las familias
      $this->all_familias = $this->familias->all();

      /// cargamos los impuestos
      $this->impuestos->all($this->all_impuestos);
   }

   /// devuelve el titulo del script
   public function titulo()
   {
      if( isset($_GET['ref']) )
         return "Art&iacute;culo " . $_GET['ref'];
      else
         return "Nuevo art&iacute;culo";
   }

   /// devuelve el script javascript necesario para la pagina
   public function javas()
   {
      ?>
      <script type="text/javascript">
         function fs_onload() {}
         function fs_unload() {}
         function masmenos(campo, num)
         {
            campo.value = parseInt(campo.value) + num;
            if(campo.value < 0)
               campo.value = 0;
         }
         function recalcular_pvp()
         {
            var iva = parseFloat(document.f_tarifas.iva.value);
            document.f_tarifas.pvp0.value = 100*parseFloat(document.f_tarifas.pvp_iva0.value)/(100+iva);
         }
         function recalcular_pvp_iva()
         {
            var iva = parseFloat(document.f_tarifas.iva.value);
            document.f_tarifas.pvp_iva0.value = parseFloat(document.f_tarifas.pvp0.value)*(100+iva)/100;
            var num = parseInt(document.f_tarifas.num.value);
            for(var i = 1; i <= num; i++)
            {
               document.f_tarifas['pvp'+i]['value'] = parseFloat(document.f_tarifas.pvp0.value);
               document.f_tarifas['pvp_iva'+i]['value'] = parseFloat(document.f_tarifas['pvp'+i]['value'])*(100-parseFloat(document.f_tarifas['dto'+i]['value']))/100*(100+iva)/100;
            }
         }
         function recalcular_dto()
         {
            var iva = parseFloat(document.f_tarifas.iva.value);
            document.f_tarifas.pvp_iva0.value = parseFloat(document.f_tarifas.pvp0.value)*(100+iva)/100;
            var num = parseInt(document.f_tarifas.num.value);
            for(var i = 1; i <= num; i++)
            {
               document.f_tarifas['dto'+i]['value'] = 100 - (10000*document.f_tarifas['pvp_iva'+i]['value'])/(document.f_tarifas['pvp'+i]['value']*(100+iva));
            }
         }
      </script>
      <?php
   }

   /// genera la url necesaria para recargar el script
   public function recargar($mod, $pag)
   {
      if($this->articulo)
         return "ppal.php?mod=" . $mod . "&amp;pag=" . $pag . "&amp;ref=" . $this->articulo['ref_url'];
      else if( isset($_GET['ref']) )
         return "ppal.php?mod=" . $mod . "&amp;pag=" . $pag . "&amp;ref=" . $_GET['ref'];
      else if( $_POST['referencia'] )
         return "ppal.php?mod=" . $mod . "&amp;pag=" . $pag . "&amp;ref=" . $_POST['referencia'];
      else
         return "ppal.php?mod=" . $mod . "&amp;pag=articulos";
   }
   
   private function recargar_articulo()
   {
      $this->articulos->get($this->articulo['referencia'], $this->articulo);
   }

   public function cuerpo($mod, $pag, &$datos)
   {
      if( isset($_GET['ref']) )
         $this->articulos->get(rawurldecode($_GET['ref']), $this->articulo);
      else if( $_POST['referencia'] )
         $this->articulos->get($_POST['referencia'], $this->articulo);
      else
         $this->articulo = FALSE;
      
      /// si no existe el artículo lo creamos
      if(!$this->articulo AND isset($_POST['referencia']))
      {
         $error = FALSE;
         $this->articulo['referencia'] = $_POST['referencia'];
         $this->articulo['descripcion'] = $_POST['referencia'];
         $this->articulo['codfamilia'] = $_POST['codfamilia'];
         if( $this->articulos->insert_articulo($this->articulo, $error) )
         {
            echo "<div class='mensaje'>Artículo creado correctamente</div>";
            $this->recargar_articulo();
         }
         else
         {
            echo "<div class='mensaje'>Imposible crear el artículo.\n",$error,"</div>";
            $this->articulo = FALSE;
         }
      }
      
      echo "<div class='destacado'><span>",$this->articulo['referencia'],"</span> &nbsp;
         <a href='ppal.php?mod=",$mod,"&pag=albaranescli&buscar=",$this->articulo['ref_url'],"&tipo=xre'>albaranes de cliente</a> |
         <a href='ppal.php?mod=",$mod,"&pag=albaranesprov&buscar=",$this->articulo['ref_url'],"&tipo=xre'>albaranes de proveedor</a> |
         <a href='ppal.php?mod=",$mod,"&pag=articulos&buscar=",$this->articulo['ref_url'],"&tipo=mov'>últimos movimientos</a>
         </div>\n";
      
      if( isset($_POST['option']) )
      {
         if($_POST['option'] == 'articulo')
            $this->procesar_formulario_articulo();
         else if($_POST['option'] == 'stock')
            $this->procesar_formulario_stock();
         else if($_POST['option'] == 'tarifas')
            $this->procesar_formulario_tarifas();
      }
      
      if( $this->articulo )
      {
         if( $this->articulo['bloqueado'] )
            echo "<div class='error'><img src='images/system-lock-screen.png' alt='bloqueado'/>Art&iacute;culo bloqueado</div>\n";
         
         echo "<table width='100%'>
            <tr>
            <td valign='top'>
            <div class='grupo'>
            <form name='f_articulo' action='",$this->recargar($mod, $pag),"' method='post'>
            <input type='hidden' name='option' value='articulo'/>
            <div>
               <div class='bloque'>",$this->mostrar_familias($mod),"</div>
               <div class='bloque'>
                  Descripción:
                  <input type='text' name='descripcion' value='",htmlspecialchars($this->articulo['descripcion'], ENT_QUOTES),"' size='30' maxlength='100'/>
               </div>
               <div class='bloque'>
                  Código de barras:
                  <input type='text' name='codbarras' value='",htmlspecialchars($this->articulo['codbarras'], ENT_QUOTES),
                  "' size='12' maxlength='18' autocomplete='off'/>
               </div>
               <div class='bloque'>",$this->mostrar_bloqueado(),"</div>
            </div>
            <div>
               <div class='bloque'>IVA:",$this->mostrar_impuestos(),"</div>
               <div class='bloque'>
                  PVP:
                  <input class='numero' type='text' name='pvp' value='",number_format($this->articulo['pvp'], 2),
                  "' size='6' maxlength='9' autocomplete='off' title='Precio unitario'/> &euro;
               </div>
               <div class='bloque'>Fecha de actualización: <b>",$this->mostrar_actualizado(),"</b></div>
            </div>
            <div>
               <div class='bloque'>
                  Stock mínimo:
                  <input class='numero' type='text' name='stockmin' value='",$this->articulo['stockmin'],"' size='5' maxlength='9' autocomplete='off'/>
               </div>
               <div class='bloque'>
                  Stock máximo:
                  <input class='numero' type='text' name='stockmax' value='",$this->articulo['stockmax'],"' size='5' maxlength='9' autocomplete='off'/>
               </div>
               <div class='bloque'>",$this->mostrar_control_stock(),"</div>
            </div>
            <div>
               <div class='bloque'>
                  Código de equivalencia:
                  <input type='text' name='equivalencia' value='",htmlspecialchars($this->articulo['equivalencia'], ENT_QUOTES),
                  "' size='18' maxlength='18' title='Varios art&iacute;culos son equivalentes si tienen el mismo c&oacute;digo de equivalencia'/>
               </div>
               <div class='bloque'>",$this->mostrar_destacado(),"</div>
            </div>
            <div>
               <textarea name='observaciones' rows='2' cols='70'>",htmlspecialchars($this->articulo['observaciones'], ENT_QUOTES),"</textarea>
            </div>
            <div>
               <div style='float: right;'>
                  <input type='submit' value='modificar'/>
               </div>
               <input type='button' class='eliminar' value='eliminar' onclick='windows.location.href=\"",$this->recargar($mod, $pag),"&delete=TRUE\"'/>
            </div>
            </form>
            </div>
            ",$this->mostrar_stock($mod, $pag),"
            </td>
            <td valign='top' width='400'>",
                 $this->listar_tarifas($mod, $pag),
                 $this->listar_precios($mod),
                 $this->mostrar_equivalencias($mod),
            "</td>
            </tr>
            </table>";
      }
      else
         echo "<div class='error'>Art&iacute;culo no encontrado</div>";
   }

   private function mostrar_familias($mod)
   {
      echo "<a href='ppal.php?mod=" , $mod , "&amp;pag=familia&amp;cod=" , $this->articulo['codfamilia'],
         "'>Familia</a>: <select name='codfamilia' size='0'>";
      if( $this->all_familias )
      {
         foreach($this->all_familias as $col)
         {
            if($col['codfamilia'] == $this->articulo['codfamilia'])
               echo "<option value='" , $col['codfamilia'] , "' selected='selected'>" , $col['descripcion'] , "</option>";
            else
               echo "<option value='" , $col['codfamilia'] , "'>" , $col['descripcion'] , "</option>";
         }
      }
      echo "</select>\n";
   }

   private function mostrar_impuestos()
   {
      if($this->all_impuestos)
      {
         echo "<select name='codimpuesto'>\n";
         foreach($this->all_impuestos as $col)
         {
            if($col['codimpuesto'] == $this->articulo['codimpuesto'])
               echo "<option value='" , $col['codimpuesto'] , "' selected='selected'>" , $col['descripcion'] , "</option>\n";
            else
               echo "<option value='" , $col['codimpuesto'] , "'>" , $col['descripcion'] , "</option>\n";
         }
         echo "</select>\n";
      }
   }

   private function mostrar_control_stock()
   {
      if($this->articulo['controlstock'])
         echo "<input id='articulo_controlstock' type='checkbox' name='controlstock' value='true' checked='checked'/>\n";
      else
         echo "<input id='articulo_controlstock' type='checkbox' name='controlstock' value='true'/>\n";
      echo "<label for='articulo_controlstock'>permitir venta sin stock</label>";
   }

   private function mostrar_destacado()
   {
      if($this->articulo['destacado'])
         echo "<input id='articulo_destacado' type='checkbox' name='destacado' value='true' title='¿Destaca sobre equivalentes?' checked='checked'/>\n";
      else
         echo "<input id='articulo_destacado' type='checkbox' name='destacado' value='true' title='¿Destaca sobre equivalentes?'/>\n";
      echo "<label for='articulo_destacado'>destacar frente a articulos equivalentes</label>";
   }

   private function mostrar_actualizado()
   {
      if($this->articulo['factualizado'] == '')
         echo "nunca";
      else
         echo Date('d-m-Y', strtotime($this->articulo['factualizado']));
   }

   private function mostrar_bloqueado()
   {
      if($this->articulo['bloqueado'])
         echo "<input id='articulo_bloqueado' type='checkbox' name='bloqueado' value='true' checked='checked'/>\n";
      else
         echo "<input id='articulo_bloqueado' type='checkbox' name='bloqueado' value='true'/>\n";
      echo "<label for='articulo_bloqueado'>bloqueado</label>";
   }

   private function mostrar_stock($mod, $pag)
   {
      echo "<div class='lista'>Stock (",$this->articulo['stockfis'],")</div>
         <form name='f_stock' action='",$this->recargar($mod, $pag),"' method='post'>
            <input type='hidden' name='option' value='stock'/>
            <table class='lista'>";
      $num = 0;
      foreach($this->articulos->get_stock_full($this->articulo['referencia']) as $s)
      {
         echo "<tr>
            <td>
               <input type='hidden' name='idstock",$num,"' value='",$s['idstock'],"'/>
               <input type='hidden' name='codalmacen",$num,"' value='",$s['codalmacen'],"'/>
               ",$s['codalmacen'],"
            </td>
            <td>",$s['nombre'],"</td>
            <td>
               <input type='button' value='-' onclick='masmenos(document.f_stock.cantidad",$num,",-1)'/>
               <input type='text' name='cantidad",$num,"' value='",$s['cantidad'],"' class='numero' size='3'/>
               <input type='button' value='+' onclick='masmenos(document.f_stock.cantidad",$num,",1)'/>
            </td>
            </tr>";
         $num++;
      }
      echo "<tr>
            <td colspan='3' align='right'>
               <input type='hidden' name='num' value='",$num,"'/>
               <input type='submit' value='modificar'/>
            </td>
         </tr>
         </table>
         </form>
         </div>";
   }

   private function listar_tarifas($mod, $pag)
   {
      echo "<form name='f_tarifas' action='" , $this->recargar($mod, $pag) , "' method='post'>
         <input type='hidden' name='option' value='tarifas'/>
         <input type='hidden' name='iva' value='",$this->articulo['iva'],"'/>
         <div class='grupo'>
            <table width='100%'>
               <tr>
                  <td></td>
                  <th>PVP</td>
                  <th>dto.</td>
                  <th>PVP+IVA</td>
                  <td></td>
               </tr>
               <tr>
                  <td>P.U.</td>
                  <td><input type='text' name='pvp0' value='",number_format($this->articulo['pvp'],2),
                        "' class='numero' size='5' onkeyup='recalcular_pvp_iva()'/> €</td>
                  <td><input type='text' name='dto0' value='0.00' class='numero' size='5' disabled='disabled'/> %</td>
                  <td><input type='text' name='pvp_iva0' value='",number_format($this->articulo['pvp_iva'],2),
                        "' class='numero' size='5' onkeyup='recalcular_pvp()'/> €</td>
                  <td>
                     <a href='ppal.php?mod=",$mod,"&pag=carrito&formulario=add&ref=",$this->articulo['ref_url'], "&pvp=",$this->articulo['pvp'],"&dto=0'>
                        <img valign='middle' src='images/carrito.png' alt='A&ntilde;adir' title='A&ntilde;adir al carrito'/>
                     </a>
                  </td>
               </tr>";
      $num = 0;
      foreach($this->articulos->get_tarifas_full($this->articulo['referencia'], $this->articulo['pvp'], $this->articulo['iva']) as $t)
      {
         $num++;
         echo "<tr>
               <td>",$t['nombre'],"</td>
               <td>
                  <input type='hidden' name='id",$num,"' value='",$t['id'],"'/>
                  <input type='hidden' name='codtarifa",$num,"' value='",$t['codtarifa'],"'/>
                  <input type='text' name='pvp",$num,"' value='",number_format($this->articulo['pvp'],2),"' class='numero' size='5' disabled='disabled'/> €
               </td>
               <td><input type='text' name='dto",$num,"' value='",number_format($t['dtopor'],2),
                     "' class='numero' size='5' onkeyup='recalcular_pvp_iva()'/> %</td>
               <td><input type='text' name='pvp_iva",$num,"' value='",number_format($t['pvp_iva'],2),
                     "' class='numero' size='5' onkeyup='recalcular_dto()'/> €</td>
               <td>
                  <a href='ppal.php?mod=",$mod,"&pag=carrito&formulario=add&ref=",$this->articulo['ref_url'], "&pvp=",$t['pvp_dto'],"&dto=0'>
                     <img valign='middle' src='images/carrito.png' alt='A&ntilde;adir' title='A&ntilde;adir al carrito'/>
                  </a>
               </td>
            </tr>";
      }
      echo "<tr>
            <td colspan='5' align='right'>
               <input type='hidden' name='num' value='",$num,"'/>
               <input type='submit' value='modificar'/>
            </td>
         </tr>
         </table>
         </div>
         </form>\n";
   }
   
   private function listar_precios($mod)
   {
      $compras = $this->articulos->get_ultimos_precios_compra($this->articulo['referencia']);
      if( count($compras) > 0 )
      {
         echo "<div class='lista'>Últimas compras:</div>
            <table class='lista'>
            <tr>
               <th align='left'>Fecha</th>
               <th align='right'>PVP</th>
               <th align='right'>PVP+IVA</th>
            </tr>";
         foreach($compras as $pc)
         {
            echo "<tr>
               <td><a href='ppal.php?mod=",$mod,"&pag=albaranprov&id=",$pc['idalbaran'],"'>",$pc['fecha'],"</a></td>
               <td align='right'>",number_format($pc['pvpunitario'],2)," €</td>
               <td align='right'>",number_format($pc['pvp_iva'],2)," €</td>
               </tr>";
         }
         echo "</table>
            </div>";
      }
      
      $ventas = $this->articulos->get_ultimos_precios_venta($this->articulo['referencia']);
      if( count($ventas) > 0 )
      {
         echo "<div class='lista'>Últimas ventas:</div>
            <table class='lista'>
            <tr>
               <th align='left'>Fecha</th>
               <th align='right'>PVP</th>
               <th align='right'>PVP+IVA</th>
            </tr>";
         foreach($ventas as $pc)
         {
            echo "<tr>
               <td><a href='ppal.php?mod=",$mod,"&pag=albarancli&id=",$pc['idalbaran'],"'>",$pc['fecha'],"</a></td>
               <td align='right'>",number_format($pc['pvpunitario'],2)," €</td>
               <td align='right'>",number_format($pc['pvp_iva'],2)," €</td>
               </tr>";
         }
         echo "</table>
            </div>";
      }
   }
   
   private function mostrar_equivalencias($mod)
   {
      $equivalentes = $this->articulos->get_equivalentes_full($this->articulo['equivalencia'], $this->articulo['referencia']);
      if( count($equivalentes) > 0)
      {
         echo "<div class='lista'>Equivalentes:</div>
            <table class='lista'>
               <tr>
                  <th align='left'>Referencia</th>
                  <th align='right'>Stock</th>
                  <th align='right'>PVP</th>
                  <th align='right'>PVP+IVA</th>
               </tr>";
         foreach($equivalentes as $eq)
         {
            echo "<tr>
               <td><a href='ppal.php?mod=",$mod,"&pag=articulo&ref=",$eq['ref_url'],"'>",$eq['referencia'],"</a></td>
               <td align='right'>",number_format($eq['stockfis']),"</td>
               <td align='right'>",number_format($eq['pvp'],2)," €</td>
               <td align='right'>",number_format($eq['pvp_iva'],2)," €</td>
               <td align='right'>
                  <a href='ppal.php?mod=",$mod,"&pag=carrito&formulario=add&ref=",$this->articulo['ref_url'], "&pvp=",$eq['pvp'],"&dto=0'>
                     <img valign='middle' src='images/carrito.png' alt='A&ntilde;adir' title='A&ntilde;adir al carrito'/>
                  </a>
               </td>
               </tr>";
         }
         echo "</table>\n";
      }
   }
   
   private function procesar_formulario_articulo()
   {
      $this->articulo['codfamilia'] = $_POST['codfamilia'];
      $this->articulo['descripcion'] = $_POST['descripcion'];
      $this->articulo['codbarras'] = $_POST['codbarras'];
      
      if( isset($_POST['bloqueado']) )
         $this->articulo['bloqueado'] = TRUE;
      else
         $this->articulo['bloqueado'] = FALSE;
      
      $this->articulo['codimpuesto'] = $_POST['codimpuesto'];
      $this->articulo['pvp'] = $_POST['pvp'];
      $this->articulo['stockmin'] = $_POST['stockmin'];
      $this->articulo['stockmax'] = $_POST['stockmax'];
      
      if( isset($_POST['controlstock']) )
         $this->articulo['controlstock'] = TRUE;
      else
         $this->articulo['controlstock'] = FALSE;
      
      $this->articulo['equivalencia'] = $_POST['equivalencia'];
      
      if( isset($_POST['destacado']) )
         $this->articulo['destacado'] = TRUE;
      else
         $this->articulo['destacado'] = FALSE;
      
      $this->articulo['observaciones'] = $_POST['observaciones'];
      
      $error = FALSE;
      if( !$this->articulos->update_articulo($this->articulo, $error) )
         echo "<div class='error'>Error al modificar el artículo.\n",$error,"</div>";
      $this->recargar_articulo();
   }
   
   private function procesar_formulario_stock()
   {
      $num = 0;
      $stocks = $this->articulos->get_stock_full($this->articulo['referencia']);
      foreach($stocks as &$s)
      {
         if($s['idstock'] == $_POST['idstock'.$num] OR $s['codalmacen'] == $_POST['codalmacen'.$num])
         {
            $error = FALSE;
            $s['old_stock'] = $s['cantidad'];
            $s['cantidad'] = intval($_POST['cantidad'.$num]);
            if( !$this->articulos->set_stock($s, $this->usuario, $error) )
               echo "<div class='error'>Error al modificar el stock.\n",$error,"</div>";
            $num++;
         }
      }
      $this->recargar_articulo();
   }
   
   private function procesar_formulario_tarifas()
   {
      /// actualizamos el pvp
      $error = FALSE;
      $this->articulo['pvp'] = $_POST['pvp0'];
      if( !$this->articulos->update_articulo($this->articulo, $error) )
         echo "<div class='error'>Imposible modificar el PVP del artículo.\n",$error,"</div>";
      $this->recargar_articulo();
      
      /// actualizamos las tarifas
      $num = 1;
      $tarifas = $this->articulos->get_tarifas_full($this->articulo['referencia'], $this->articulo['pvp'], $this->articulo['iva']);
      foreach($tarifas as &$t)
      {
         if($t['id'] == $_POST['id'.$num] OR $t['codtarifa'] == $_POST['codtarifa'.$num])
         {
            $error = FALSE;
            $t['descuento'] = $_POST['dto'.$num];
            if( !$this->articulos->set_tarifa($t, $error) )
               echo "<div class='error'>Imposible modificar la tarifa.\n",$error,"</div>";
            $num++;
         }
      }
   }
}

?>
