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
require_once("clases/carrito.php");

class script_ extends script
{
   private $articulos;
   private $carrito;
   private $articulos_carrito;
   private $consulta;

   public function __construct($ppal)
   {
      parent::__construct($ppal);
      $this->generico = TRUE;
      $this->articulos = new articulos();
      $this->carrito = new carrito();
      $this->consulta = FALSE;
   }
   
   /// devuelve el titulo del script
   public function titulo()
   {
      return "Carrito";
   }
   
   /// captura las variables necesarias para el script enviadas por GET y POST
   public function datos()
   {
      $datos = array();
      if( isset($_GET['formulario']) )
         $datos['formulario'] = $_GET['formulario'];
      else if( isset($_POST['formulario']) )
         $datos['formulario'] = $_POST['formulario'];
      if( isset($_GET['ref']) )
         $datos['referencia'] = rawurldecode($_GET['ref']);
      if( isset($_POST['descripcion2']) )
         $datos['descripcion2'] = $_POST['descripcion2'];
      else
         $datos['descripcion2'] = '';
      if( isset($_POST['cantidad']) )
         $datos['cantidad'] = $_POST['cantidad'];
      else
         $datos['cantidad'] = 1;
      if( isset($_GET['pvp']) )
         $datos['pvp'] = $_GET['pvp'];
      else if( isset($_POST['pvp']) )
         $datos['pvp'] = $_POST['pvp'];
      if( isset($_GET['dto']) )
         $datos['dto'] = $_GET['dto'];
      else if( isset($_POST['dto']) )
         $datos['dto'] = $_POST['dto'];
      else
         $datos['dto'] = 0;
      return $datos;
   }

   /// devuelve el script javascript necesario para la pagina
   public function javas()
   {
      ?>
      <script type="text/javascript">
         function fs_onload() {
            document.articulos.buscar.focus();
         }
         function fs_unload() {}
         function masmenos(linea, campo, num)
         {
            campo.value = parseInt(campo.value) + num;
            if(campo.value < 0)
               campo.value = 0;
            document.carrito.generarp.disabled = 'disabled';
            document.carrito.generarc.disabled = 'disabled';
            /// actualizamos la linea
            if(linea >= 0)
               calcula_linea(linea);
         }
         function calcula_linea(num)
         {
            var iva = document.getElementById("iva_" + num);
            var cantidad = document.getElementById("cant_" + num);
            var pvp = document.getElementById("pvp_" + num);
            var dto = document.getElementById("dto_" + num);
            var total = document.getElementById("total_" + num);
            var neto = document.getElementById("neto_" + num);
            total.value = Math.round(cantidad.value * pvp.value * (1 - dto.value / 100) * (1 + iva.value / 100) * 100) / 100;
            neto.value = Math.round(cantidad.value * pvp.value * (1 - dto.value / 100) * 100) / 100;
            /// actualizamos el total
            calcula_carrito();
            document.carrito.generarp.disabled = 'disabled';
            document.carrito.generarc.disabled = 'disabled';
         }
         function calcula_pvp(num)
         {
            var iva = document.getElementById("iva_" + num);
            var cantidad = document.getElementById("cant_" + num);
            var pvp = document.getElementById("pvp_" + num);
            var dto = document.getElementById("dto_" + num);
            var total = document.getElementById("total_" + num);
            var neto = document.getElementById("neto_" + num);
            pvp.value = Math.round(total.value / (cantidad.value * (1 - dto.value / 100) * (1 + iva.value / 100)) * 100) / 100;
            /// actualizamos el neto
            neto.value = Math.round(cantidad.value * pvp.value * (1 - dto.value / 100) * 100) / 100;
            /// actualizamos el total
            calcula_carrito();
            document.carrito.generarp.disabled = 'disabled';
            document.carrito.generarc.disabled = 'disabled';
         }
         function calcula_neto(num)
         {
            var iva = document.getElementById("iva_" + num);
            var cantidad = document.getElementById("cant_" + num);
            var pvp = document.getElementById("pvp_" + num);
            var dto = document.getElementById("dto_" + num);
            var total = document.getElementById("total_" + num);
            var neto = document.getElementById("neto_" + num);
            pvp.value = Math.round(neto.value / (cantidad.value * (1 - dto.value / 100)) * 100) / 100;
            /// actualizamos el total
            total.value = Math.round(neto.value * (1 + iva.value / 100) * 100) / 100;
            /// actualizamos el total
            calcula_carrito();
            document.carrito.generarp.disabled = 'disabled';
            document.carrito.generarc.disabled = 'disabled';
         }
         function calcula_carrito()
         {
            var numarts = document.carrito.numarts.value;
            var t_total = document.getElementById("t_total");
            var t_neto = document.getElementById("t_neto");
            var total = 0;
            var neto = 0;
            var l_total = null;
            var l_neto = null;
            for(var i = 0; i < numarts; i++)
            {
               l_total = document.getElementById("total_" + i);
               l_neto = document.getElementById("neto_" + i);
               total += parseFloat(l_total.value);
               neto += parseFloat(l_neto.value);
            }
            t_total.value = Math.round(total * 100) / 100;
            t_neto.value = Math.round(neto * 100) / 100;
         }
         function enviar(accion)
         {
            document.carrito.formulario.value = accion;
            document.carrito.submit();
         }
      </script>
      <?php
   }

   /// carga el cuerpo del script
   public function cuerpo($mod, $pag, &$datos)
   {
      $error = FALSE;
      $vaciado = FALSE;

      /// mostramos el cuadro de busqueda de articulos
      $this->articulos->show_search2($mod, '', '', '', FALSE, FALSE);
      
      if( isset($datos['formulario']) )
      {
         switch($datos['formulario'])
         {
            case 'add':
               if( $this->carrito->insert($this->usuario, $datos, $error) )
                  echo "<div class='mensaje'>Art&iacute;culo <b>",$datos['referencia'],"</b> a&ntilde;adido correctamente</div>\n";
               else
                  echo "<div class='error'>Error al a&ntilde;adir el art&iacute;culo al carrito<br/>" , $error , "</div>\n";
               break;
               
            case 'modificar':
               $modificados = 0;
               for($i = 0; $i < $_POST['numarts']; $i++)
               {
                  $linea = Array(
                     'id' => $_POST['idlinea_' . $i],
                     'descripcion2' => $_POST['desc_' . $i],
                     'cantidad' => $_POST['cant_' . $i],
                     'pvp' => $_POST['pvp_' . $i],
                     'dto' => $_POST['dto_' . $i]
                  );
                  if( $this->carrito->update($linea, $error) )
                     $modificados++;
                  else
                     echo '<div class="error">Error al modificar el carrito<br/>' , $error , '</div>' , "\n";
               }
               if($modificados == $_POST['numarts'])
                  echo '<div class="mensaje">Carrito modificado correctamente</div>' , "\n";
               break;
               
            case 'eliminar':
               $eliminados = 0;
               for($i = 0; $i < $_POST['numarts']; $i++)
               {
                  if($_POST['check_' . $i] != '')
                  {
                     if( $this->carrito->delete($_POST['check_' . $i], $error) )
                     {
                        $eliminados++;
                        $vaciado = TRUE;
                     }
                     else
                        echo "<div class='error'>Error al eliminar el art&iacute;culo al carrito<br/>" , $error , "</div>\n";
                  }
                  else /// los no eliminados los actualizamos
                  {
                     $linea = Array(
                        'id' => $_POST['idlinea_' . $i],
                        'descripcion2' => $_POST['desc_' . $i],
                        'cantidad' => $_POST['cant_' . $i],
                        'pvp' => $_POST['pvp_' . $i],
                        'dto' => $_POST['dto_' . $i]
                     );
                     
                     if( !$this->carrito->update($linea, $error) )
                        echo '<div class="error">Error al modificar el carrito<br/>' , $error , '</div>' , "\n";
                  }
               }
               if($eliminados > 0)
                  echo '<div class="mensaje">Se han eliminado ' , $eliminados , ' l&iacute;neas y se han guardado los cambios</div>' , "\n";
               break;
               
            case 'clean':
               if( $this->carrito->clean($this->usuario) )
               {
                  echo "<div class='mensaje'>Carrito vaciado correctamente</div>\n";
                  $vaciado = TRUE;
               }
               else
                  echo "<div class='error'>Error al vaciar el carrito</div>\n";
               break;
               
            case 'consultar':
               $this->consulta = TRUE;
               break;
         }
      }

      if( $this->carrito->get_articulos($this->usuario, $this->articulos_carrito) )
      {
         $this->mostrar_carrito_precios($mod, $pag);
      }
      else if(!$vaciado)
         echo "<div class='mensaje'>El carrito est&aacute; vac&iacute;o</div>\n";
   }

   /// Muestra una tabla con todos los articulos del carrito con precios y descuentos
   private function mostrar_carrito_precios($mod, $pag)
   {
      $i = 0;
      $importe = 0;
      $importe_iva = 0;
      $t_neto = 0;
      $t_total = 0;

      echo "<div class='lista'>Carrito:</div>
         <form name='carrito' action='ppal.php?mod=" , $mod , "&amp;pag=" , $pag , "' method='post'>
         <input type='hidden' name='formulario' value=''/>
         <table class='lista'>\n",
         "<tr>\n",
         "<td width='20'></td>\n",
         "<th align='left'>Referencia</th>\n",
         "<th align='left'>Descripci&oacute;n</th>\n",
         "<th align='right'>Cantidad</th>\n",
         "<th align='right'>PVP</th>\n",
         "<th align='right'>dto.</th>\n",
         "<th align='right'>Total</th>\n",
         "<th align='right'>Total+IVA</th>\n",
         "</tr>\n";

      foreach($this->articulos_carrito as $col)
      {
         $importe = ($col['cantidad'] * $col['pvpunitario'] * (1 - $col['dtopor'] / 100));
         $importe_iva = ($col['cantidad'] * $col['pvpunitario'] * (1 - $col['dtopor'] / 100) * (1 + $col['iva'] / 100));
         
         if( !isset($_GET['ref']) )
            echo "<tr>";
         else if($col['referencia'] == $_GET['ref'])
            echo "<tr class='verde'>";
         else
            echo "<tr>";
         
         echo "<td>\n",
            "<input type='hidden' name='idlinea_" , $i , "' value='" , $col['idlinea'] , "'/>\n",
            "<input type='hidden' name='iva_" , $i , "' id='iva_" , $i , "' value='" , $col['iva'] , "'/>\n",
            "<input type='checkbox' name='check_" , $i , "' value='" , $col['idlinea'] , "'/>\n",
            "</td>\n",
            "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=articulo&amp;ref=" , rawurlencode($col['referencia']) , "'>",
                 $col['referencia'] , "</a></td>\n";
         
         if($col['descripcion2'] != '')
         {
            echo "<td><input name='desc_" , $i , "' type='text' value='" , htmlspecialchars($col['descripcion2'], ENT_QUOTES),
                    "' size='30' maxlength='99'/></td>\n";
         }
         else
         {
            echo "<td><input name='desc_" , $i , "' type='text' value='" , htmlspecialchars($col['descripcion'], ENT_QUOTES),
                    "' size='30' maxlength='99'/></td>\n";
         }

         echo "<td align='right'>\n",
            "<input type='button' value='-' onclick='masmenos(" , $i , ", document.carrito.cant_" , $i , ", -1)'/>\n",
            "<input name='cant_" , $i , "' id='cant_" , $i , "' class='numero' type='text' value='" , $col['cantidad'],
                 "' size='2' maxlength='5' autocomplete='off' onkeyup='calcula_linea(" , $i , ")'/>\n",
            "<input type='button' value='+' onclick='masmenos(" , $i , ", document.carrito.cant_" , $i , ", 1)'/>\n",
            "</td>\n",
            "<td align='right'><input name='pvp_" , $i , "' id='pvp_" , $i , "' type='text' class='numero' value='",
                 number_format($col['pvpunitario'], 2) , "' size='7' maxlength='9' readOnly='true'/> &euro;</td>\n",
            "<td align='right'><input name='dto_" , $i , "' id='dto_" , $i , "' type='text' class='numero' value='",
                 $col['dtopor'] , "' size='2' maxlength='5' onkeyup='calcula_linea(" , $i , ")' autocomplete='off'/> %</td>\n",
            "<td align='right'><input name='neto_" , $i , "' id='neto_" , $i , "' type='text' class='numero' value='",
                 number_format($importe, 2) , "' size='6' maxlength='9' onkeyup='calcula_neto(" , $i , ")' autocomplete='off'/> &euro;</td>\n",
            "<td align='right'><input name='total_" , $i , "' id='total_" , $i , "' type='text' class='numero' value='",
                 number_format($importe_iva, 2) , "' size='6' maxlength='9' onkeyup='calcula_pvp(" , $i,
                 ")' autocomplete='off'/> &euro;</td>\n",
            "</tr>\n";
         
         $i++;
         $t_neto += $importe;
         $t_total += $importe_iva;
      }

      /// botones + totales
      echo "<tr>
            <td colspan='6'><input type='hidden' name='numarts' value='",$i,"'/></td>
            <td align='right'>
               <b>Neto:</b> <input name='t_neto' id='t_neto' type='text' class='numero' value='",number_format($t_neto, 2),
               "' size='6' maxlength='9' readOnly='true'/> &euro;
            </td>
            <td align='right'>
               <b>Total:</b> <input name='t_total' id='t_total' type='text' class='numero' value='" , number_format($t_total, 2),
               "' size='6' maxlength='9' readOnly='true'/> &euro;
            </td>
         </tr>
         </table>
         <table width='100%'>
         <tr>
            <td>
               <input type='button' class='eliminar' value='eliminar' onclick='enviar(\"eliminar\")'/>
               <input type='button' class='eliminar' value='eliminar todo' onclick='enviar(\"clean\")'/>
            </td>
            <td align='center'>
               <input type='button' value='modificar' onclick='enviar(\"modificar\")'/>
            </td>
            <td align='right'>
               <input type='button' name='generarp' value='generar albar&aacute;n de proveedor' onclick='window.location = \"ppal.php?mod=",
                  $mod , "&pag=carrito2albaranprov\"'/>
               <input type='button' name='generarc' value='generar albar&aacute;n de cliente' onclick='window.location = \"ppal.php?mod=",
                  $mod , "&pag=carrito2albarancli\"'/>
            </td>
         </tr>
         </table>
         </form>\n";
   }
}

?>