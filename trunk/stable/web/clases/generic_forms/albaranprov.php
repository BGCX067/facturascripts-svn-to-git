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
require_once("clases/albaranes_prov.php");
require_once("clases/facturas_prov.php");
require_once("clases/ejercicios.php");
require_once("clases/empresa.php");
require_once("clases/articulos.php");
require_once("clases/agentes.php");
require_once("clases/carrito.php");

class script_ extends script
{
   private $agentes;
   private $albaranes;
   private $facturas;
   private $carrito;
   private $ejercicios;
   private $articulos;
   private $albaran;
   private $lineas;
   private $mi_carrito;
   private $tipos;

   public function __construct($ppal)
   {
      parent::__construct($ppal);

      $this->generico = true;
      $this->coletilla = "&amp;id=" . $_GET['id'];

      $this->agentes = new agentes();
      $this->albaranes = new albaranes_prov();
      $this->facturas = new facturas_prov();
      $this->carrito = new carrito();
      $this->ejercicios = new ejercicios();
      $this->articulos = new articulos();

      $this->tipos = $this->albaranes->tipos();
   }

   private function recarga_albaran()
   {
      if( !$this->albaranes->get($_GET['id'], $this->albaran) )
         $this->albaran = false;

      if( !$this->albaranes->get_lineas($this->albaran['idalbaran'], $this->lineas) )
         $this->lineas = false;

      if( !$this->carrito->get_articulos($this->usuario, $this->mi_carrito) )
         $this->mi_carrito = false;
   }

   /// devuelve el titulo del script
   public function titulo()
   {
      return("Albar&aacute;n de Proveedor");
   }

   /// devuelve el script javascript necesario para la pagina
   public function javas()
   {
      ?>
      <script type="text/javascript">
      <!--

      function fs_onload() {
      }

      function fs_unload() {
      }

      function masmenos(campo, num)
      {
         campo.value = parseInt(campo.value) + num;

         if(campo.value < 0)
         {
            campo.value = 0;
         }
      }

      function fenviar(accion)
      {
         document.albaran.formulario.value = accion;
         document.albaran.submit();
      }

      function feliminar()
      {
         if( confirm('¿Eliminar el albarán?') )
         {
            if( confirm('¿Descontar los artículos a stock?') )
            {
               document.albaran.formulario.value = 'eliminars';
            }
            else
            {
               document.albaran.formulario.value = 'eliminarn';
            }

            document.albaran.submit();
         }
      }

      function fenviarl(accion)
      {
         switch(accion)
         {
            case 'actualizar':
               document.lineas.actualizar.value = 'true';
               break;

            case 'consultar':
               document.lineas.consultar.value = 'true';
               break;

            default:
               document.lineas.formulario_l.value = accion;
               break;
         }

         document.lineas.submit();
      }

      //-->
      </script>
      <?php
   }

   private function procesar($mod, $pag)
   {
      $error = false;
      $this->albaran['eliminado'] = false;
      $descontar = true;

      switch( $_POST['formulario'] )
      {
         case 'modificar':
            $this->albaran['tipo'] = $_POST['tipo'];
            $this->albaran['numproveedor'] = $_POST['numproveedor'];
            $this->albaran['revisado'] = ($_POST['revisado'] != '');
            $this->albaran['observaciones'] = $_POST['observaciones'];

            if( $this->albaranes->update($this->albaran, $error) )
               echo "<div class='mensaje'>Albar&aacute;n modificado correctamente</div>\n";
            else
               echo "<div class='error'>" , $error , "</div>\n";
            break;

         case "eliminarn":
            $descontar = false;
         case "eliminars":
            /// solamente el propio agente o un gerente puede modifiar el albaran
            if($this->albaran['codagente'] == $this->codagente OR $mod == 'principal')
            {
               if( $this->albaranes->delete($this->albaran, $descontar, $error) )
               {
                  $this->albaran['eliminado'] = true;

                  echo "<div class='mensaje'>Albar&aacute;n eliminado correctamente.<br/><br/>
                     <img src='images/progreso.gif' align='middle' alt='en progreso'/> Redireccionando ...</div>\n";

                  /// redirecciona
                  echo "<script type=\"text/javascript\">
                     <!--
                     function fs_onload()
                     {
                        setTimeout('recargar()',1000);
                     }

                     function recargar()
                     {
                        window.location.href = \"ppal.php?mod=" , $mod , "&pag=albaranesprov\";
                     }
                     //-->
                     </script>\n";
               }
               else
                  echo "<div class='error'>" , $error , "</div>\n";
            }
            else
            {
               echo "<div class='error'>Solamente <b>" , $this->agentes->get_nombre($this->albaran['codagente']) , "</b>
                  o un gerente <a href='ppal.php?mod=principal&amp;pag=" , $pag , "&amp;id=" , $this->albaran['idalbaran'] , "'>desde
                  el m&oacute;dulo principal</a> pueden eliminar este albar&aacute;n</div>\n";
            }
            break;
      }

      /// solamente el propio agente o un gerente puede modifiar el albaran
      if($this->albaran['codagente'] == $this->codagente OR $mod == 'principal')
      {
         switch( $_POST['formulario_l'] )
         {
            case 'añadir':
               if($this->mi_carrito AND $_POST['carrito'] == 'true')
               {
                  foreach($this->mi_carrito as $col)
                  {
                     if( $this->albaranes->add_linea($this->albaran, $col, $error) )
                        echo "<div class='mensaje'>Art&iacute;culo <b>" , $col['referencia'] , "</b> a&ntilde;adido correctamente</div>\n";
                     else
                        echo "<div class='error'>Error al a&ntilde;adir el art&iacute;culo " , $col['referencia'] , ".<br/>" , $error , "</div>\n";
                  }

                  $this->carrito->clean($this->usuario);
                  $this->recarga_albaran();
               }
               break;

            case 'modificar':
               $continuar = true;

               for($i = 0; $i < $_POST['lineas'] AND $continuar; $i++)
               {
                  $linea = array(
                     'idlinea' => $_POST['id_' . $i],
                     'pvp' => $_POST['pvp_' . $i],
                     'dto' => $_POST['dto_' . $i],
                     'cantidad' => $_POST['cant_' . $i]
                  );

                  if( !$this->albaranes->update_linea($this->albaran, $linea, $error) )
                  {
                     echo "<div class='error'>" , $error , "</div>\n";
                     $continuar = false;
                  }
               }

               if($continuar)
                  echo "<div class='mensaje'>Albar&aacute;n modificado correctamente</div>\n";

               $this->recarga_albaran();
               break;

            case 'borrar':
               $continuar = true;
               $eliminadas = 0;

               for($i = 0; $i < $_POST['lineas'] AND $continuar; $i++)
               {
                  $linea = array(
                     'idlinea' => $_POST['id_' . $i],
                     'pvp' => $_POST['pvp_' . $i],
                     'dto' => $_POST['dto_' . $i],
                     'cantidad' => $_POST['cant_' . $i]
                  );

                  /// ¿Eliminamos?
                  if($_POST['check_' . $i] == 'true')
                  {
                     if( $this->albaranes->delete_linea($this->albaran, $linea, $error) )
                        $eliminadas++;
                     else
                     {
                        echo "<div class='error'>" , $error , "</div>\n";
                        $continuar = false;
                     }
                  }
                  else /// si no eliminamos, pues guardamos las modificaciones
                  {
                     if( !$this->albaranes->update_linea($this->albaran, $linea, $error) )
                     {
                        echo "<div class='error'>" , $error , "</div>\n";
                        $continuar = false;
                     }
                  }
               }

               if($continuar)
                  echo "<div class='mensaje'>Se han eliminado " , $eliminadas , " l&iacute;neas y se han guardado los cambios correctamente</div>\n";

               $this->recarga_albaran();
               break;
         }
      }
      else if($_POST['formulario_l'])
      {
         echo "<div class='error'>Solamente <b>" . $this->agentes->get_nombre($this->albaran['codagente']) . "</b>
            o un gerente <a href='ppal.php?mod=principal&amp;pag=" . $pag . "&amp;id=" . $this->albaran['idalbaran'] . "'>desde
            el m&oacute;dulo principal</a> pueden modificar este albar&aacute;n</div>\n";
      }
   }

   /// genera la url necesaria para recargar el script
   public function recargar($mod, $pag)
   {
      return("ppal.php?mod=" . $mod . "&amp;pag=" . $pag . "&amp;id=" . $_GET['id']);
   }

   /// carga el cuerpo del script
   public function cuerpo($mod, $pag, &$datos)
   {
      /// cargamos el albaran
      $this->recarga_albaran();

      if( $this->albaran )
      {
         echo "<div class='destacado'><span>Albarán de proveedor " , $this->albaran['codigo'] , " &nbsp; - &nbsp; ",
            Date('d-m-Y', strtotime($this->albaran['fecha'])),
            "</span></div>\n";

         /// procesamos los datos de los formularios
         if( isset($_POST['formulario']) OR isset($_POST['formulario_l']) OR isset($_POST['imprimir']) )
            $this->procesar($mod, $pag);
         else
            $this->albaran['eliminado'] = false;

         /*
          * Mostramos el albarán,
          * siempre y cuando no haya sido eliminado o se haya marcado para eliminar
          */
         if( !$this->albaran['eliminado'] )
         {
            echo "<form name='albaran' action='ppal.php?mod=" , $mod , "&amp;pag=" , $pag , "&amp;id=" , $this->albaran['idalbaran'] , "' method='post'>\n",
               "<input type='hidden' name='formulario' value=''/>\n",
               "<table class='datos'>\n",
               "<tr class='destacado2'>\n",
               "<td>Proveedor:</td>\n",
               "<td>Agente:</td>\n",
               "<td align='right'>N&uacute;mero:</td>\n",
               "<td align='right'>Serie:</td>\n",
               "<td align='right'>Ejercicio:</td>\n",
               "</tr>\n" , "<tr>\n",
               "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=proveedor&amp;cod=" , $this->albaran['codproveedor'] , "'>" , $this->albaran['nombre'] , "</a></td>\n",
               "<td>" , $this->agentes->get_nombre($this->albaran['codagente']) , "</td>\n",
               "<td align='right'>" , $this->albaran['numero'] , "</td>\n",
               "<td align='right'>" , $this->albaran['codserie'] , "</td>\n",
               "<td align='right'>" , $this->ejercicios->get_nombre($this->albaran['codejercicio']) , "</td>\n",
               "</tr>\n",
               "<tr><td colspan='5'>&nbsp;</td></tr>\n",
               "<tr class='destacado2'>\n",
               "<td>Observaciones:</td>\n",
               "<td align='right'>num. proveedor:</td>\n",
               "<td align='right'>Revisado:</td>\n",
               "<td align='right'>Facturado:</td>\n",
               "<td align='right'>Tipo:</td>\n",
               "</tr>\n" , "<tr>\n",
               "<td><textarea name='observaciones' rows='2' cols='80'>" , htmlspecialchars($this->albaran['observaciones'], ENT_QUOTES) , "</textarea></td>\n",
               "<td align='right' valign='top'><input type='text' name='numproveedor' value='" , $this->albaran['numproveedor'],
                  "' class='numero' size='10' maxlength='20'/></td>\n",
               "<td align='right' valign='top'>" , $this->mostrar_facturado($mod) , "</td>\n",
               "<td align='right' valign='top'>" , $this->mostrar_tipos() , "</td>\n",
               "</tr>\n",
               "<tr><td>";

            if($this->albaran['ptefactura'] == 't')
               echo "<input type='button' class='eliminar' value='eliminar' onclick='feliminar()'/>";

            echo "<td colspan='4' align='right'><input type='button' value='modificar' onclick='fenviar(\"modificar\")'/></td></tr>\n",
               "</table>\n" , "</form>\n";
            
            /*
             * Líneas
             */
            $this->mostrar_lineas($mod, $pag);
         }
      }
      else
         echo "<div class='error'>Albar&aacute;n no encontrado</div>\n";
   }

   private function mostrar_facturado($mod)
   {
      if($this->albaran['ptefactura'] == 'f') /// facturado
      {
         /// ¿Revisado?
         if($this->albaran['revisado'] == 't')
            echo "si <input type='hidden' name='revisado' value='true'/>";
         else
            echo "no";

         echo '</td><td align="right" valign="top">';

         /*
          * facturado pero sin idfactura, quiere decir que el ejercicio ha sido cerrado
          */
         if($this->albaran['idfactura'] == '0')
            echo "cerrado";
         else
            echo "<a href='ppal.php?mod=contabilidad&amp;pag=facturaprov&amp;id=" , $this->albaran['idfactura'] , "'>",
               $this->facturas->get_codigo($this->albaran['idfactura']) , "</a>";
      }
      else /// no facturado
      {
         if($mod == 'principal')
         {
            if($this->albaran['revisado'] == 't')
               echo "<input type='checkbox' name='revisado' value='true' checked />";
            else
               echo "<input type='checkbox' name='revisado' value='true'/>";
         }
         else
         {
            if($this->albaran['revisado'] == 't')
               echo "si <input type='hidden' name='revisado' value='true'/>";
            else
               echo "no";
         }

         echo '</td><td align="right" valign="top">no';
      }
   }

   private function mostrar_tipos()
   {
      echo '<select name="tipo">';

      for($i = 0; $i < count($this->tipos); $i++)
      {
         if($i == $this->albaran['tipo'])
            echo "<option value='" , $i , "' selected='selected'>" , $this->tipos[$i] , "</option>\n";
         else
            echo "<option value='" , $i , "'>" , $this->tipos[$i] , "</option>\n";
      }

      echo '</select>';
   }

   private function mostrar_lineas($mod, $pag)
   {
      echo "<br/>\n";
      
      if( isset($_POST['consultar']) )
         echo "<div class='mensaje'>Resultados de la consulta en <b>amarillo</b></div>\n";

      /// actualizamos si el usuario lo pide
      if(isset($_POST['actualizar']) AND $mod == 'principal')
      {
         $actualizados = Array();

         if( $this->actualizar_pvp($this->albaran['idalbaran'], $actualizados) )
         {
            echo "<div class='mensaje'><b>" , count($actualizados) , "</b> art&iacute;culos modificados correctamente.<br/>
               Art&iacute;culos modificados en amarillo.</div>";
         }
         else
            echo "<div class='error'>Error al actualizar los art&iacute;culos.<br/>Art&iacute;culos modificados en amarillo.</div>";
      }

      echo "<form name='lineas' action='ppal.php?mod=" , $mod , "&amp;pag=" , $pag , "&amp;id=" , $this->albaran['idalbaran'] , "' method='post'>\n",
         "<input type='hidden' name='formulario_l' value=''/>\n",
         "<input type='hidden' name='actualizar' value=''/>\n",
         "<input type='hidden' name='consultar' value=''/>\n",
         "<table class='lista'>\n",
         "<tr class='destacado'>\n",
         "<td width='20'></td>\n",
         "<td>Referencia</td>\n",
         "<td>Descripci&oacute;n</td>\n",
         "<td align='right'>Cantidad</td>\n",
         "<td align='right'>P.U.</td>\n",
         "<td align='right'>Dto</td>\n",
         "<td align='right'>Importe</td>\n",
         "</tr>\n";

      if( $this->albaran['ptefactura'] == 't') /// NO está facturado
      {
         if( $this->lineas )
         {
            $i = 0;
            
            foreach($this->lineas as $col)
            {
               if( $this->actualizado($col['referencia'], $actualizados) )
                  echo "<tr class='amarillo'>\n";
               else
                  echo "<tr>\n";

               echo "<td><input type='hidden' name='id_" , $i , "' value='" , $col['idlinea'] , "'/>\n",
                  "<input type='hidden' name='ref_" , $i , "' value='" , $col['referencia'] , "'/>\n",
                  "<input type='hidden' name='cant_" , $i , "' value='" , $col['cantidad'] , "'/>\n",
                  "<input type='checkbox' name='check_" , $i , "' value='true'/></td>\n";
            
               if($mod == 'mostrador' OR $mod == 'principal' OR $mod == 'almacen')
               {
                  echo "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=articulo&amp;ref=" , rawurlencode($col['referencia']) , "'>",
                          $col['referencia'] , "</a></td>\n";
               }
               else
                  echo "<td><b>" , $col['referencia'] , "</b></td>\n";

               echo "<td>" , $col['descripcion'] , "</td>\n",
                  "<td align='right'>" , $col['cantidad'] , "</td>\n",
                  "<td align='right'><input type='text' class='numero' size='6' maxlength='9' name='pvp_" , $i , "' value='" , $col['pvpunitario'],
                       "' autocomplete='off'/> &euro;</td>\n",
                  "<td align='right'><input type='text' class='numero' size='3' maxlength='5' name='dto_" , $i , "' value='" , $col['dtopor'],
                       "' autocomplete='off'/> %</td>\n",
                  "<td align='right'>" , number_format($col['pvptotal'], 2) , " &euro;</td>\n",
                  "</tr>\n";
            
               if( isset($_POST['consultar']) )
                  $this->consultar($mod, $pag, $_POST['id'], $albaran['codproveedor'], $col['referencia']);

               $i++;
            }
         }
         else
            echo '<tr class="rojo"><td></td><td colspan="6">No hay l&iacute;neas</td></tr>' , "\n";

         /// carrito
         if($this->mi_carrito)
         {
            echo "<tr class='verde'>\n",
               "<td><input type='checkbox' name='carrito' value='true'/></td>\n",
               "<td colspan='6'>Hay <b>" , count($this->mi_carrito) , "</b> art&iacute;culos en el carrito:\n",
               "<input type='button' value='a&ntilde;adir' title='A&ntilde;adir art&iacute;culos del carrito' onclick='fenviarl(\"añadir\")'/></td>\n",
               "</tr>\n";
         }

         echo '<tr><td colspan="7">&nbsp;</td></tr>
            <tr class="gris">
            <td></td>
            <td colspan="2">
               <input type="hidden" name="lineas" value="' , $i , '"/>
               <input type="button" class="eliminar" value="eliminar" onclick="fenviarl(\'borrar\')"/>
               &nbsp; | &nbsp; <input type="button" value="modificar" onclick="fenviarl(\'modificar\')"/>
               &nbsp; | &nbsp; <input type="button" value="actualizar precios" onclick="fenviarl(\'actualizar\')"/>
               <input type="button" value="consultar" onclick="fenviarl(\'consultar\')"/>
            </td>
            <td colspan="4" align="right"><b>Neto:</b> <input type="text" class="numero" name="t_neto" value="' , round($this->albaran['neto'], 2),
                 '" size="4" readOnly="true"/> &euro;
            &nbsp; | &nbsp; <b>IVA:</b> <input type="text" class="numero" name="t_iva" value="' , round($this->albaran['totaliva'], 2),
                 '" size="4" readOnly="true"/> &euro;
            &nbsp; | &nbsp; <b>Total:</b> <input type="text" class="numero" name="t_total" value="' , round($this->albaran['total'], 2),
                 '" size="5" readOnly="true"/> &euro;</td>
            </tr>
            </table>
            </form>' , "\n";
      }
      else /// Si está facturado
      {
         if( $this->lineas )
         {
            foreach($this->lineas as $col)
            {
               echo "<tr><td></td>\n";

               if($mod == 'mostrador' OR $mod == 'principal' OR $mod == 'almacen')
               {
                  echo "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=articulo&amp;ref=" , rawurlencode($col['referencia']) , "'>",
                          $col['referencia'] , "</a></td>\n";
               }
               else
                  echo "<td><b>" , $col['referencia'] , "</b></td>\n";

               echo "<td>" , $col['descripcion'] , "</td>\n",
                  "<td align='right'>" , $col['cantidad'] , "</td>\n",
                  "<td align='right'>" , number_format($col['pvpunitario'], 2) , " &euro;</td>\n",
                  "<td align='right'>" , number_format($col['dtopor'], 2) , " %</td>\n",
                  "<td align='right'>" , number_format($col['pvptotal'], 2) , " &euro;</td>\n",
                  "</tr>\n";
            }
         }
         else
            echo '<tr class="rojo"><td></td><td colspan="6">No hay l&iacute;neas</td></tr>' , "\n";

         echo '<tr><td colspan="7">&nbsp;</td></tr>
            <tr class="gris">
            <td colspan="7" align="right">
            <b>Neto:</b> ' , round($this->albaran['neto'], 2) , ' &euro;
            &nbsp; | &nbsp; <b>IVA:</b> ' , round($this->albaran['totaliva'], 2) , ' &euro;
            &nbsp; | &nbsp; <b>Total:</b> ' , round($this->albaran['total'], 2) , ' &euro;</td>
            </tr>
            </table>
            </form>' , "\n";
      }
   }

   private function actualizar_pvp($idalbaran, &$actualizados)
   {
      $retorno = true;
      $lineas = false;
      $articulo = false;
      $error = false;

      if( $this->albaranes->get_lineas($idalbaran, $lineas) )
      {
         foreach($lineas as $linea)
         {
            if($retorno AND $linea['pvpunitario'] > 0)
            {
               if( $this->articulos->get($linea['referencia'], $articulo) )
               {
                  $articulo['pvp_ant'] = $articulo['pvp'];
                  $articulo['pvp'] = $linea['pvpunitario'];

                  if( $this->articulos->update_articulo($articulo, $error) )
                     $actualizados[$linea['referencia']] = $linea['pvpunitario'];
                  else
                  {
                     echo "<div class='error'>" . $error . "</div>\n";
                     $retorno = false;
                  }
               }
               else
                  $retorno = false;
            }
         }
      }

      return($retorno);
   }

   private function consultar($mod, $pag, $idalbaran, $codproveedor, $referencia)
   {
      $anteriores = false;

      if( $this->albaranes->anteriores($idalbaran, $codproveedor, $referencia, 3, $anteriores) )
      {
         foreach($anteriores as $anterior)
         {
            echo "<tr class='amarillo'>\n",
               "<td colspan='2'>|----> Fecha: <b>" , Date('d-m-Y', strtotime($anterior['fecha'])) , "</b>\n",
               "Albar&aacute;n: <a href='ppal.php?mod=" , $mod , "&amp;pag=albarancli&amp;id=" , $anterior['idalbaran'] , "'>" , $anterior['codigo'] , "</a></td>\n",
               "<td align='right'>" , $anterior['cantidad'] , "</td>\n",
               "<td align='right'>" , number_format($anterior['pvpunitario'], 2) , " &euro;</td>\n",
               "<td align='right'>" , number_format($anterior['dtopor'], 0) , " %</td>\n",
               "<td align='right'>" , number_format($anterior['pvptotal'], 2) , " &euro;</td>\n",
               "<td></td><td></td></tr>\n";
         }
      }
      else
         echo "<tr class='amarillo'><td colspan='8'>|----> <b>Sin resultados</b></td></tr>\n";
   }

   private function actualizado($referencia, &$actualizados)
   {
      return ($actualizados AND $actualizados[$referencia] > 0);
   }
}

?>