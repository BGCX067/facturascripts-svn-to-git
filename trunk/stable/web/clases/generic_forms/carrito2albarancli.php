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
require_once("clases/albaranes_cli.php");
require_once("clases/carrito.php");
require_once("clases/clientes.php");
require_once("clases/ejercicios.php");
require_once("clases/empresa.php");
require_once("clases/opciones.php");
require_once("clases/series.php");
require_once 'clases/agentes.php';

class script_ extends script
{
   private $agentes;
   private $albaranes;
   private $carrito;
   private $clientes;
   private $ejercicios;
   private $empresa;
   private $opciones;
   private $series;
   private $mis_opciones;
   private $mi_carrito;
   private $mi_albaran;

   public function __construct($ppal)
   {
      parent::__construct($ppal);
      $this->agentes = new agentes();
      $this->albaranes = new albaranes_cli();
      $this->carrito = new carrito();
      $this->clientes = new clientes();
      $this->ejercicios = new ejercicios();
      $this->empresa = new empresa();
      $this->opciones = new opciones();
      $this->series = new series();
      $this->mi_carrito = FALSE;
      
      /// obtenemos las opciones de facturaScripts
      $this->opciones->all($this->mis_opciones);
   }

   /// devuelve el titulo del script
   public function titulo()
   {
      return "Nuevo Albar&aacute;n de Cliente";
   }

   public function cuerpo($mod, $pag, &$datos)
   {
      $agente = FALSE;
      $this->agentes->get($this->codagente, $agente);
      if( $agente )
      {
         $this->carrito->get_articulos($this->usuario, $this->mi_carrito);
         if( $this->mi_carrito )
         {
            $this->mi_albaran = array(
                'codalmacen' => $this->empresa->get_almacen(),
                'coddivisa' => $this->empresa->get_divisa(),
                'codpago' => $this->empresa->get_pago(),
                'codagente' => $this->codagente,
                'codcliente' => $this->mis_opciones['cliente'],
                'codejercicio' => $this->mis_opciones['ejercicio'],
                'codserie' => $this->mis_opciones['serie'],
                'fecha' => Date('j-n-Y'),
                'numero2' => '',
                'desc_stock' => TRUE,
                'observaciones' => '',
                'sin_iva' => FALSE,
                'neto' => 0,
                'iva' => 0,
                'total' => 0,
                'ticket' => TRUE
            );
            
            if( isset($_POST['codcliente']) )
            {
               $this->mi_albaran['codcliente'] = $_POST['codcliente'];
               setcookie('fs_codcliente', $_POST['codcliente'], time()+31536000);
            }
            else if( isset($_COOKIE['fs_codcliente']) )
               $this->mi_albaran['codcliente'] = $_COOKIE['fs_codcliente'];
            
            if( isset($_POST['codejercicio']) )
            {
               $this->mi_albaran['codejercicio'] = $_POST['codejercicio'];
               setcookie('fs_codejercicio', $_POST['codejercicio'], time()+31536000);
            }
            else if( isset($_COOKIE['fs_codejercicio']) )
               $this->mi_albaran['codejercicio'] = $_COOKIE['fs_codejercicio'];
            
            if( isset($_POST['codserie']) )
            {
               $this->mi_albaran['codserie'] = $_POST['codserie'];
               setcookie('fs_codserie', $_POST['codserie'], time()+31536000);
            }
            else if( isset($_COOKIE['fs_codserie']) )
               $this->mi_albaran['codserie'] = $_COOKIE['fs_codserie'];
            
            if( isset($_POST['fecha']) )
               $this->mi_albaran['fecha'] = $_POST['fecha'];
            
            if( isset($_POST['numero2']) )
               $this->mi_albaran['numero2'] = $_POST['numero2'];
            
            if( isset($_POST['observaciones']) )
               $this->mi_albaran['observaciones'] = $_POST['observaciones'];
            
            if( isset($_POST['enviado']) )
            {
               if( !isset($_POST['desc_stock']) )
               {
                  $this->mi_albaran['desc_stock'] = FALSE;
                  setcookie ('fs_desc_stock', 'FALSE', time()+31536000);
               }
               else
                  setcookie ('fs_desc_stock', 'TRUE', time()+31536000);
               
               if( !isset($_POST['ticket']) )
               {
                  $this->mi_albaran['ticket'] = FALSE;
                  setcookie ('fs_ticket', 'FALSE', time()+31536000);
               }
               else
                  setcookie ('fs_ticket', 'TRUE', time()+31536000);
            }
            else
            {
               if( isset($_COOKIE['fs_desc_stock']) )
                  $this->mi_albaran['desc_stock'] = ($_COOKIE['fs_desc_stock'] == 'TRUE');
               
               if( isset($_COOKIE['fs_ticket']) )
                  $this->mi_albaran['ticket'] = ($_COOKIE['fs_ticket'] == 'TRUE');
            }
            
            if( !$this->crear_albaran($mod) )
            {
               echo "<div class='destacado'><span>Nuevo albarán de cliente:</span></div>\n";
               echo "<form action='",$this->recargar($mod, $pag),"' method='post'>\n
                     <input type='hidden' name='enviado' value='TRUE'/>
                     <div class='lista2'>
                     <div class='bloque'>Cliente: ",$this->select_clientes(),"</div>
                     <div class='bloque'>Ejercicio: ",$this->select_ejercicios(),"</div>
                     <div class='bloque'>Serie: ",$this->select_series(),"</div>
                     <div class='bloque'>Fecha: <input type='text' class='tcal' name='fecha' value='",$this->mi_albaran['fecha'],"' size='8'/></div>
                     <div class='bloque'>Número 2: <input type='text' name='numero2' value='",$this->mi_albaran['numero2'],"' size='6'/></div>
                     <div class='bloque'>",$this->checkbox_desc_stock(),"</div>
                     <div>Observaciones:<br/><textarea name='observaciones' cols='80' rows='2'>",$this->mi_albaran['observaciones'],"</textarea></div>
                  </div>\n";
               $this->show_lineas();
               echo "<table width='100%'>
                  <tr>
                     <td><input type='button' class='eliminar' value='cancelar' onclick='window.location.href=\"ppal.php?mod=",$mod,"&pag=carrito\"'/></td>\n
                     <td align='center'>
                        <b>Neto:</b> ",number_format($this->mi_albaran['neto'], 2)," &euro; &nbsp;
                        <b>IVA:</b> ",number_format($this->mi_albaran['iva'], 2)," &euro; &nbsp;
                        <b>Total:</b> ",number_format($this->mi_albaran['total'], 2)," &euro;
                     </td>\n
                     <td align='right'>
                        ",$this->checkbox_ticket(),"
                        <input type='submit' value='guardar'>
                     </td>\n
                  </tr>\n
                  </table>\n</form>\n";
            }
         }
         else
            echo "<div class='error'>El carrito está vacío.</div>";
      }
      else
         echo "<div class='error'>No tienes un agente asociado a tu usuario.</div>";
   }
   
   private function crear_albaran($mod)
   {
      $guardado = FALSE;
      if( isset($_POST['enviado']) )
      {
         $error = '';
         if( $this->albaranes->carrito2albaran($this->mi_albaran, $this->mi_albaran['desc_stock'], $this->mi_carrito, $error) )
         {
            if($this->mi_albaran['ticket'])
               $imprimir = "{'imprimir':'true', 't_imp':'1', 'copias':'1'}";
            else
               $imprimir = "{'imprimir':'false', 't_imp':'1', 'copias':'1'}";
            echo "<div class='mensaje'>Albar&aacute;n
               <a href='ppal.php?mod=",$mod,"&amp;pag=albarancli&amp;id=",$this->mi_albaran['idalbaran'],"'>",$this->mi_albaran['codigo'],"</a>
               guardado correctamente.<br/><br/>
               <img src='images/progreso.gif' align='middle' alt='en progreso'/> Redireccionando ...</div>\n
               <script type=\"text/javascript\">
                  function fs_onload() {
                     setTimeout('recargar()', 1000);
                  }
                  function recargar() {
                     fs_post_to_url(\"ppal.php?mod=",$mod,"&pag=albarancli&id=",$this->mi_albaran['idalbaran'],"\", ",$imprimir," );
                  }
               </script>\n";
            /// vaciamos el carrito
            $this->carrito->clean($this->usuario);
            $guardado = TRUE;
         }
         else
            echo '<div class="error">',$error,'</div>' , "\n";
      }
      return $guardado;
   }
   
   private function select_clientes()
   {
      $mis_clientes = FALSE;
      $this->clientes->all($mis_clientes);
      
      echo "<select name='codcliente'>";
      if( $mis_clientes )
      {
         foreach($mis_clientes as $cli)
         {
            if($cli['codcliente'] == $this->mi_albaran['codcliente'])
               echo "<option value='",$cli['codcliente'],"' selected='selected'>",$cli['nombre'],"</option>\n";
            else
               echo "<option value='",$cli['codcliente'],"'>",$cli['nombre'],"</option>\n";
         }
      }
      echo "</select>";
   }
   
   private function select_ejercicios()
   {
      $mis_ejercicios = FALSE;
      $this->ejercicios->all($mis_ejercicios);
      
      echo "<select name='codejercicio'>";
      if( $mis_ejercicios )
      {
         foreach($mis_ejercicios as $eje)
         {
            if($eje['codejercicio'] == $this->mi_albaran['codejercicio'])
               echo "<option value='",$eje['codejercicio'],"' selected='selected'>",$eje['nombre'],"</option>\n";
            else
               echo "<option value='",$eje['codejercicio'],"'>",$eje['nombre'],"</option>\n";
         }
      }
      echo "</select>";
   }
   
   private function select_series()
   {
      $mis_series = FALSE;
      $this->series->all($mis_series);
      
      echo "<select name='codserie'>";
      if( $mis_series )
      {
         foreach($mis_series as $s)
         {
            if($s['codserie'] == $this->mi_albaran['codserie'])
            {
               echo "<option value='",$s['codserie'],"' selected='selected'>",$s['descripcion'],"</option>\n";
               if($s['siniva'] == 't')
                  $this->mi_albaran['sin_iva'] = TRUE;
            }
            else
               echo "<option value='",$s['codserie'],"'>",$s['descripcion'],"</option>\n";
         }
      }
      echo "</select>";
      if($this->mi_albaran['sin_iva'])
         echo " <b>Sin IVA</b>";
   }
   
   private function checkbox_desc_stock()
   {
      if( $this->mi_albaran['desc_stock'] )
         echo "<input id='desc_stock' type='checkbox' name='desc_stock' value='TRUE' checked='checked'/>";
      else
         echo "<input id='desc_stock' type='checkbox' name='desc_stock' value='TRUE'/>";
      echo "<label for='desc_stock'>descontar de stock</label>";
   }
   
   private function show_lineas()
   {
      echo "<br/><table class='lista'>
         <tr class='destacado'>
            <td>Artículo</td>
            <td align='right'>Cantidad</td>
            <td align='right'>P.U.</td>
            <td align='right'>Dto.</td>
            <td align='right'>Importe</td>
         </tr>\n";
      if( $this->mi_carrito )
      {
         foreach($this->mi_carrito as $c)
         {
            $importe = ($c['pvpunitario'] * $c['cantidad'] * (1 - $c['dtopor'] / 100));
            $this->mi_albaran['neto'] += $importe;
            if($this->mi_albaran['sin_iva'])
               $this->mi_albaran['total'] += $importe;
            else
            {
               $this->mi_albaran['iva'] += ($importe * $c['iva'] / 100);
               $this->mi_albaran['total'] += ($importe + ($importe * $c['iva'] / 100));
            }
            
            echo "<tr>
               <td>",$c['referencia'],' ',$c['descripcion'],"</td>
               <td align='right'>",$c['cantidad'],"</td>
               <td align='right'>",number_format($c['pvpunitario'], 2) , " &euro;</td>
               <td align='right'>",$c['dtopor']," %</td>
               <td align='right'>",number_format($importe, 2) , " &euro;</td>
               </tr>\n";
         }
      }
      echo "</table>";
   }
   
   private function checkbox_ticket()
   {
      if( $this->mi_albaran['ticket'] )
         echo "<input id='ch_ticket' type='checkbox' name='ticket' value='TRUE' checked='checked'/>";
      else
         echo "<input id='ch_ticket' type='checkbox' name='ticket' value='TRUE'/>";
      echo "<label for='ch_ticket'>imprimir ticket</label>";
   }
}

?>