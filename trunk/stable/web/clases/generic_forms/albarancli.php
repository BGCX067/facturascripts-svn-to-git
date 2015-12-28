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
require_once("clases/ezpdf/class.ezpdf.php");
require_once("clases/agentes.php");
require_once("clases/albaranes_cli.php");
require_once("clases/facturas_cli.php");
require_once("clases/carrito.php");
require_once("clases/ejercicios.php");
require_once("clases/empresa.php");
require_once("clases/opciones.php");

class script_ extends script
{
   private $agentes;
   private $albaran;
   private $albaranes;
   private $facturas;
   private $carrito;
   private $empresa;
   private $ejercicios;
   private $lineas;
   private $mi_carrito;
   private $opciones;

   public function __construct($ppal)
   {
      parent::__construct($ppal);

      $this->agentes = new agentes();
      $this->albaranes = new albaranes_cli();
      $this->facturas = new facturas_cli();
      $this->carrito = new carrito();
      $this->empresa = new empresa();
      $this->ejercicios = new ejercicios();
      $this->opciones = new opciones();

      $this->generico = true;
      $this->coletilla = "&amp;id=" . $_GET['id'];
      $this->albaran = false;
      $this->lineas = false;
      $this->mi_carrito = false;
   }

   private function recarga_albaran()
   {
      if( !$this->albaranes->get($_GET['id'], $this->albaran) )
         $this->albaran = false;

      if( !$this->albaranes->get_lineas($_GET['id'], $this->lineas) )
         $this->lineas = false;

      if( !$this->carrito->get_articulos($this->usuario, $this->mi_carrito) )
         $this->mi_carrito = false;
   }

   /// devuelve el titulo del script
   public function titulo()
   {
      return "Albar&aacute;n de Cliente";
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
         function enviar(accion)
         {
            document.lineas.formulario_l.value = accion;
            document.lineas.submit();
         }
         function fimprimir(mod, id)
         {
            switch(document.lineas.t_imp.selectedIndex)
            {
               case 0:
                  window.location = 'pdf.php?mod=' + mod + '&pag=albarancli&id=' + id;
                  break;
                  
               case 1:
               case 2:
                  document.lineas.imprimir.value = 'true';
                  document.lineas.submit();
                  break;
            }
         }
         function feliminar()
         {
            if( confirm('¿Eliminar el albarán?') )
            {
               if( confirm('¿Añadir los artículos a stock?') )
                  document.albaran.formulario.value = 'eliminars';
               else
                  document.albaran.formulario.value = 'eliminarn';
               document.albaran.submit();
            }
         }
         function fenviar()
         {
            document.albaran.formulario.value = 'modificar';
            document.albaran.submit();
         }
      </script>
      <?php
   }

   /// genera la url necesaria para recargar el script
   public function recargar($mod, $pag)
   {
      return "ppal.php?mod=".$mod."&amp;pag=".$pag."&amp;id=".$_GET['id'];
   }

   private function procesar($mod, $pag)
   {
      $this->albaran['eliminado'] = false;
      $anadir = true;
      $error = false;
      
      /// modificar/eliminar albarán
      switch( $_POST['formulario'] )
      {
         case 'modificar':
            $this->albaran['numero2'] = $_POST['numero2'];
            $this->albaran['observaciones'] = $_POST['observaciones'];
            $this->albaran['revisado'] = ($_POST['revisado'] != '');

            if( $this->albaranes->update($this->albaran, $error) )
               echo "<div class='mensaje'>Albar&aacute;n modificado correctamente</div>\n";
            else
               echo "<div class='error'>" , $error , "</div>\n";
            break;

         /// eliminar el albaran
         case "eliminarn":
            $anadir = false;
         case "eliminars":
            /// solamente el propio agente o un gerente puede modifiar el albaran
            if($this->albaran['codagente'] == $this->codagente OR $mod == 'principal')
            {
               if( $this->albaranes->delete($this->albaran, $anadir, $error) )
               {
                  $this->albaran['eliminado'] = true;

                  echo "<div class='mensaje'>Albar&aacute;n eliminado correctamente.<br/><br/>
                     <img src='images/progreso.gif' align='middle' alt='en progreso'/> Redireccionando ...</div>\n",
                     "<script type=\"text/javascript\">
                        function fs_onload() {
                           setTimeout('recargar()',1000);
                        }
                        function recargar() {
                           window.location.href = \"ppal.php?mod=",$mod,"&pag=albaranescli\";
                        }
                     </script>";
               }
               else
                  echo "<div class='error'>" , $error , "</div>\n";
            }
            else
            {
               echo "<div class='error'>Solamente <b>" , $this->agentes->get_nombre($this->albaran['codagente']) , "</b>
                  o un gerente <a href='ppal.php?mod=principal&amp;pag=" , $pag , "&amp;id=" , $this->albaran['idalbaran'] , "'>
                  desde el m&oacute;dulo principal</a> pueden eliminar este albar&aacute;n</div>\n";
            }
            break;
      }


      /// añadir/modificar/eliminar líneas e imprimir
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
         echo "<div class='error'>Solamente <b>" , $this->agentes->get_nombre($this->albaran['codagente']) , "</b>
            o un gerente <a href='ppal.php?mod=principal&amp;pag=" , $pag , "&amp;id=" , $this->albaran['idalbaran'] , "'>
            desde el m&oacute;dulo principal</a> pueden modificar este albar&aacute;n</div>\n";
      }

      /// imprimir
      if($_POST['imprimir'] == 'true')
      {
         $empresa = $this->empresa->get();
         if( $this->ticket($empresa, $_POST['copias']) )
            echo "<div class='mensaje'>Ticket impreso correctamente</div>\n";
      }
   }

   /// carga el cuerpo del script
   public function cuerpo($mod, $pag, &$datos)
   {
      /// cargamos el albaran
      $this->recarga_albaran();

      if( $this->albaran )
      {
         echo "<div class='destacado'><span>Albarán de cliente " , $this->albaran['codigo'] , " &nbsp; - &nbsp; ",
            Date('d-m-Y', strtotime($this->albaran['fecha'])),
            "</span></div>\n";

         if( isset($_POST['formulario']) OR isset($_POST['formulario_l']) OR isset($_POST['imprimir']) )
            $this->procesar($mod, $pag);
         else
            $this->albaran['eliminado'] = false;

         /*
          * Mostramos el albarán,
          * siempre y cuando no se haya eliminado o se haya elegido a opción eliminar.
          */
         if( !$this->albaran['eliminado'] )
         {
            echo "<form name='albaran' action='ppal.php?mod=" , $mod , "&amp;pag=" , $pag , "&amp;id=" , $this->albaran['idalbaran'] , "' method='post'>\n",
               "<input type='hidden' name='formulario' value=''/>\n",
               "<table class='datos'>\n",
               "<tr class='destacado2'>\n",
               "<td>Cliente:</td>\n",
               "<td>Agente:</td>\n",
               "<td align='right'>N&uacute;mero:</td>\n",
               "<td align='right'>Serie:</td>\n",
               "<td align='right'>Ejercicio:</td>\n",
               "</tr>\n" , "<tr>\n",
               "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=cliente&amp;cod=" , $this->albaran['codcliente'] , "'>" , $this->albaran['nombrecliente'] , "</a></td>\n",
               "<td>" , $this->agentes->get_nombre($this->albaran['codagente']) , "</td>\n",
               "<td align='right'>" , $this->albaran['numero'] , "</td>\n",
               "<td align='right'>" , $this->albaran['codserie'] , "</td>\n",
               "<td align='right'>" , $this->ejercicios->get_nombre($this->albaran['codejercicio']) , "</td>\n",
               "</tr>\n" , "<tr><td colspan='5'>&nbsp;</td></tr>\n",
               "<tr class='destacado2'>\n",
               "<td colspan='2'>Observaciones:</td>\n",
               "<td align='right'>N&uacute;mero 2:</td>\n",
               "<td align='right'>Revisado:</td>\n",
               "<td align='right'>Facturado:</td>\n",
               "</tr>\n" , "<tr>\n",
               "<td colspan='2'><textarea name='observaciones' rows='2' cols='80'>" , htmlspecialchars($this->albaran['observaciones'], ENT_QUOTES) , "</textarea></td>\n",
               "<td align='right' valign='top'><input type='text' name='numero2' value='" , $this->albaran['numero2'],
                    "' class='numero' size='10' maxlength='20'/></td>\n",
               "<td align='right' valign='top'>" , $this->mostrar_facturado($mod) , "</td>\n</tr>\n<tr><td>";

            if($this->albaran['ptefactura'] == 't')
               echo "<input type='button' class='eliminar' value='eliminar' onclick='feliminar()'/>";

            echo "</td><td colspan='4' align='right'><input type='button' value='modificar' onclick='fenviar()'/></td></tr>\n",
               "</table>\n" , "</form>\n" , "<br/>\n";

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
            echo "<a href='ppal.php?mod=contabilidad&amp;pag=facturacli&amp;id=" , $this->albaran['idfactura'] , "'>",
               $this->facturas->get_codigo($this->albaran['idfactura']) , "</a>";
      }
      else /// NO facturado
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

   private function mostrar_lineas($mod, $pag)
   {
      echo "<form name='lineas' action='ppal.php?mod=" , $mod , "&amp;pag=" , $pag , "&amp;id=" , $this->albaran['idalbaran'] , "' method='post'>\n",
         "<input type='hidden' name='formulario_l' value =''/>\n",
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

      if($this->albaran['ptefactura'] == 't') /// No está facturado
      {
         if( $this->lineas )
         {
            $i = 0;
            foreach($this->lineas as $col)
            {
               echo "<tr>\n",
                  "<td><input type='hidden' name='id_" , $i , "' value='" , $col['idlinea'] , "'/>\n",
                  "<input type='hidden' name='ref_" , $i , "' value='" , $col['referencia'] , "'/>\n",
                  "<input type='hidden' name='cant_" , $i , "' value='" , $col['cantidad'] , "'/>\n",
                  "<input type='checkbox' name='check_" , $i , "' value='true'/></td>\n";

               if($mod == 'mostrador' OR $mod == 'principal')
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
               "<input type='button' value='a&ntilde;adir' title='A&ntilde;adir art&iacute;culos del carrito' onclick='enviar(\"añadir\")'/></td>\n",
               "</tr>\n";
         }

         echo "<tr><td colspan='7'>&nbsp;</td></tr>\n",
            "<tr class='gris'>\n",
            "<td></td>\n",
            "<td colspan='2'>\n",
            "<input type='hidden' name='lineas' value='" , $i , "'/>\n",
            "<input type='button' class='eliminar' value='eliminar' onclick='enviar(\"borrar\")'/>\n",
            "&nbsp; | &nbsp; <input type='button' value='modificar' onclick='enviar(\"modificar\")'/>\n",
            "&nbsp; | &nbsp; <b>Imprimir</b> <input class='numero' type='text' name='copias' value='1' size='1' maxlength='2' autocomplete='off'/>\n",
            "<select name='t_imp'>\n",
            "<option value='0'>albar&aacute;n</option>\n",
            "<option value='1' selected='selected'>ticket</option>\n",
            "</select>\n",
            "<input type='hidden' name='imprimir' value=''/>\n",
            "<input type='button' value='imprimir' onclick='fimprimir(\"" , $mod , "\", \"" , $this->albaran['idalbaran'] , "\")'/></td>\n",
            "<td colspan='4' align='right'><b>Neto:</b> <input type='text' class='numero' name='t_neto' value='" , round($this->albaran['neto'], 2),
                 "' size='4' readOnly='true'/> &euro;\n",
            "&nbsp; | &nbsp; <b>IVA:</b> <input type='text' class='numero' name='t_iva' value='" , round($this->albaran['totaliva'], 2),
                 "' size='4' readOnly='true'/> &euro;\n",
            "&nbsp; | &nbsp; <b>Total:</b> <input type='text' class='numero' name='t_total' value='" , round($this->albaran['total'], 2),
                 "' size='5' readOnly='true'/> &euro;</td>\n",
            "</tr>\n" , "</table>\n" , "</form>\n";
      }
      else /// SI está facturado
      {
         if( $this->lineas )
         {
            foreach($this->lineas as $col)
            {
               echo "<tr>\n<td></td>\n";
               if($mod == 'mostrador' OR $mod == 'principal')
               {
                  echo "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=articulo&amp;ref=" , rawurlencode($col['referencia']) , "'>",
                          $col['referencia'] , "</a></td>\n";
               }
               else
                  echo "<td><b>" , $col['referencia'] , "</b></td>\n";
               echo "<td>" , $col['descripcion'] , "</td>\n",
                  "<td align='right'>" , number_format($col['cantidad'], 0) , "</td>\n",
                  "<td align='right'>" , number_format($col['pvpunitario'], 2) , " &euro;</td>\n",
                  "<td align='right'>" , number_format($col['dtopor'], 2) , " %</td>\n",
                  "<td align='right'>" , number_format($col['pvptotal'], 2) , " &euro;</td>\n",
                  "</tr>\n";
            }
         }
         else
            echo '<tr class="rojo"><td></td><td colspan="6">No hay l&iacute;neas</td></tr>' , "\n";

         echo "<tr><td colspan='7'>&nbsp;</td></tr>\n",
            "<tr class='gris'>\n",
            "<td></td>\n",
            "<td colspan='2'><b>Imprimir</b> <input class='numero' type='text' name='copias' value='1' size='1' maxlength='2' autocomplete='off'/>\n",
            "<select name='t_imp'>\n",
            "<option value='0'>albar&aacute;n</option>\n",
            "<option value='1' selected='selected'>ticket</option>\n",
            "<option value='2'>ticket con firma</option>\n",
            "</select>\n",
            "<input type='hidden' name='imprimir' value=''/>\n",
            "<input type='button' value='imprimir' onclick='fimprimir(\"" , $mod , "\", \"" , $this->albaran['idalbaran'] , "\")'/></td>\n",
            "<td colspan='4' align='right'><b>Neto:</b> " , round($this->albaran['neto'], 2) , " &euro;\n",
            "&nbsp; | &nbsp; <b>IVA:</b> " , round($this->albaran['totaliva'], 2) , " &euro;\n",
            "&nbsp; | &nbsp; <b>Total:</b> " , round($this->albaran['total'], 2) , " &euro;</td>\n",
            "</tr>\n" , "</table>\n" , "</form>\n";
      }
   }
   
   /// carga el cuerpo del script pero para generar el pdf
   public function documento_pdf($mod, $pag, &$datos)
   {
      $this->recarga_albaran();
      
      /// obtenemos los datos
      if( $this->albaran )
      {
         if( $this->lineas )
            $this->albaran_pdf(); /// imprimimos
      }
   }

   /// imprime un ticket del albaran
   public function ticket(&$empresa, $copias)
   {
      $resultado = false;

      /*
       * Leemos la ruta del puerto COM de las opciones
       */
      $puerto_com = $this->opciones->valor('puerto_com');

      if($this->albaran AND $this->lineas AND $empresa AND $copias < 10 AND $copias > 0)
      {
         // Abrimos el archivo temporal
         $file = fopen("/tmp/ticket.txt", "w");
         if($file)
         {
            $linea = "\nTicket: " . $this->albaran['codigo'];
            $fecha = explode("-", $this->albaran['fecha']);
            $linea .= " | " . $fecha[2] . "-" . $fecha[1] . "-" . $fecha[0] . "\n";
            fwrite($file, $linea);
            $linea = "Cliente: " . $this->albaran['nombrecliente'] . "\n";
            fwrite($file, $linea);
            $linea = "Agente: " . $this->albaran['codagente'] . "\n\n";
            fwrite($file, $linea);
            $linea = sprintf("%3s", "Ud.") . " " . sprintf("%-25s", "Articulo") . " " . sprintf("%10s", "Importe") . "\n";
            fwrite($file, $linea);
            $linea = sprintf("%3s", "---") . " " . sprintf("%-25s", "-------------------------") . " ".
               sprintf("%10s", "----------") . "\n";
            fwrite($file, $linea);
            
            foreach($this->lineas as $col)
            {
               if($col['descripcion'] == '')
               {
                  $linea = sprintf("%3s", $col['cantidad']) . " " . sprintf("%-25s", $col['referencia']) . " ".
                          sprintf("%10s", number_format($col['pvptotal'] * (1 + ($col['iva'] / 100)), 2)) . "\n";
               }
               else
               {
                  $linea = sprintf("%3s", $col['cantidad']) . " " . sprintf("%-25s", substr($col['descripcion'], 0, 20)) . " ".
                          sprintf("%10s", number_format($col['pvptotal'] * (1 + ($col['iva'] / 100)), 2)) . "\n";
               }

               fwrite($file, $linea);
            }
            
            $linea = "----------------------------------------\n".
               $this->center_text("IVA: " . number_format($this->albaran['totaliva'],2,',','.') . " Eur.  ".
               "Total: " . number_format($this->albaran['totaleuros'],2,',','.') . " Eur.") . "\n\n\n\n";
            fwrite($file, $linea);
            $linea = chr(27).chr(33).chr(24).$this->center_text($empresa['nombre'],32).chr(27).chr(33).chr(1)."\n"; /// letras grandes
            fwrite($file, $linea);
            $linea = $this->center_text($empresa['direccion'] . " - " . $empresa['ciudad']) . "\n";
            fwrite($file, $linea);
            $linea = $this->center_text("Tel: " . $empresa['telefono']) . "\n";
            fwrite($file, $linea);
            $linea = $this->center_text("CIF: " . $empresa['cifnif']) . "\n" . chr(27).chr(105); /// corta el papel
            fwrite($file, $linea);
            
            fclose($file);
         }

         if( file_exists("/tmp/ticket.txt") )
         {
            $resultado = true;

            /// imprimimos las copias
            for($i = 0; $i < $copias; $i++)
            {
               if($puerto_com != '')
                  shell_exec("cat /tmp/ticket.txt > " . $puerto_com);
               else
                  shell_exec("cat /tmp/ticket.txt | lpr");
            }

            /// borramos el ticket
            unlink("/tmp/ticket.txt");
         }
      }

      return($resultado);
   }

   /// centra el texto en un string de 40 caracteres
   private function center_text($word='', $tot_width=40)
   {
      $symbol = " ";
      $middle = round($tot_width / 2);
      $length_word = strlen($word);
      $middle_word = round($length_word / 2);
      $last_position = $middle + $middle_word;
      $number_of_spaces = $middle - $middle_word;

      $result = sprintf("%'{$symbol}{$last_position}s", $word);
      for ($i = 0; $i < $number_of_spaces; $i++)
      {
         $result .= "$symbol";
      }

      return($result);
   }

   /// genera el cuerpo (texto) para un albaran en pdf
   public function albaran_pdf()
   {
      $lineasfact = count($this->lineas);
      $linea_actual = 0;
      $lppag = 13;
      $pagina = 1;

      $pdf =& new Cezpdf(array(21, 14.8));
      $euro_diff = array(33 => 'Euro');
      $pdf->selectFont("clases/ezpdf/fonts/Helvetica.afm",
              array('encoding' => 'WinAnsiEncoding',
                 'differences' => $euro_diff));

      $pdf->addInfo('Title', 'Albaran ' . $this->albaran['codigo']);
      $pdf->addInfo('Subject', 'Albaran de cliente ' . $this->albaran['codigo']);
      $pdf->addInfo('Author', 'facturascripts');


      /// imprimimos las páginas necesarias
      while($linea_actual < $lineasfact)
      {
         /// salto de página
         if($linea_actual > 0) { $pdf->ezNewPage(); }


         /*
          * encabezado
          */
         $texto = "\n\n<b>Albaran:</b> " . $this->albaran['codigo'] . "\n".
            "<b>Fecha:</b> " . Date('d-m-Y', strtotime($this->albaran['fecha'])) . "\n".
            "<b>SR. D:</b> " . $this->albaran['nombrecliente'];
         $opciones = array(
            'justification' => 'right'
         );
         $pdf->ezText($texto, 12, $opciones);
         $pdf->ezText("\n", 12);


         /*
          * Lineas
          */
         $titulo = array(
            'unidades' => '<b>Ud.</b>',
            'descripcion' => '<b>Descripción</b>',
            'dto' => '<b>DTO.</b>',
            'pvp' => '<b>P.U.</b>',
            'importe' => '<b>Importe</b>'
         );
         $filas = false;
         $saltos = 0;
         for($i = $linea_actual; (($linea_actual < ($lppag + $i)) AND ($linea_actual < $lineasfact));)
         {
            $filas[$linea_actual] = Array(
               'unidades' => $this->lineas[$linea_actual]['cantidad'],
               'descripcion' => substr($this->lineas[$linea_actual]['referencia'] . " - " . $this->lineas[$linea_actual]['descripcion'], 0, 40),
               'dto' => number_format($this->lineas[$linea_actual]['dtopor'], 0) . " %",
               'pvp' => number_format($this->lineas[$linea_actual]['pvpunitario'], 2) . " !",
               'importe' => number_format($this->lineas[$linea_actual]['pvptotal'], 2) . " !"
            );

            $linea_actual++;
            $saltos++;
         }
         $opciones = array(
            'cols' => array(
               'dto' => array('justification' => 'right'),
               'pvp' => array('justification' => 'right'),
               'importe' => array('justification' => 'right')
            ),
            'width' => 540,
            'shadeCol' => array(0.9, 0.9, 0.9)
         );
         $pdf->ezTable($filas, $titulo, '', $opciones);

         /*
          * Rellenamos el hueco que falta hasta donde debe aparecer la última tabla
          */
         if($this->albaran['observaciones'] == '')
            $salto = '';
         else
         {
            $salto = "\n<b>Observaciones</b>: " . $this->albaran['observaciones'];
            $saltos += count( explode("\n", $this->albaran['observaciones']) ) - 1;
         }

         if($saltos < $lppag)
         {
            for(;$saltos < $lppag; $saltos++) { $salto .= "\n"; }
            $pdf->ezText($salto, 12);
         }
         else if($linea_actual >= $lineasfact)
            $pdf->ezText($salto, 12);
         else
            $pdf->ezText("\n", 10);

         /*
          * Escribimos los totales
          */
         $opciones = array(
            'justification' => 'right'
         );
         $neto = '<b>Pag</b>: ' . $pagina . '/' . ceil(count($this->lineas) / $lppag);
         $neto .= '        <b>Neto</b>: ' . number_format($this->albaran['neto'], 2) . ' !';
         $neto .= '    <b>IVA</b>: ' . number_format($this->albaran['totaliva'], 2) . ' !';
         $neto .= '    <b>Total</b>: ' . number_format($this->albaran['total'], 2) . ' !';
         $pdf->ezText($neto, 12, $opciones);

         $pagina++;
      }

      $pdf->ezStream();
   }
}

?>
