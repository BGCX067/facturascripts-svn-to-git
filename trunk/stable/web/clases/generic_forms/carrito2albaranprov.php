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
require_once("clases/carrito.php");
require_once("clases/ejercicios.php");
require_once("clases/empresa.php");
require_once("clases/opciones.php");
require_once("clases/proveedores.php");
require_once("clases/series.php");

class script_ extends script
{
   private $opciones;
   private $mis_opciones;
   private $albaranes;
   private $carrito;
   private $ejercicios;
   private $empresa;
   private $proveedores;
   private $series;
   private $mi_carrito;
   private $mi_albaran;

   public function __construct($ppal)
   {
      parent::__construct($ppal);
      $this->opciones = new opciones();
      $this->albaranes = new albaranes_prov();
      $this->carrito = new carrito();
      $this->ejercicios = new ejercicios();
      $this->empresa = new empresa();
      $this->proveedores = new proveedores();
      $this->series = new series();
      
      /// obtenemos las opciones de facturaScripts
      $this->opciones->all($this->mis_opciones);
   }

   public function titulo()
   {
      return "Nuevo Albar&aacute;n de Proveedor";
   }
   
   public function cuerpo($mod, $pag, &$datos)
   {
      $this->carrito->get_articulos($this->usuario, $this->mi_carrito);
      if( $this->mi_carrito )
      {
         $this->mi_albaran = array(
             'codalmacen' => $this->empresa->get_almacen(),
             'coddivisa' => $this->empresa->get_divisa(),
             'codpago' => $this->empresa->get_pago(),
             'codagente' => $this->codagente,
             'codproveedor' => NULL,
             'codejercicio' => $this->mis_opciones['ejercicio'],
             'codserie' => $this->mis_opciones['serie'],
             'fecha' => Date('j-n-Y'),
             'numproveedor' => '',
             'a_stock' => TRUE,
             'observaciones' => '',
             'sin_iva' => FALSE,
             'neto' => 0,
             'iva' => 0,
             'total' => 0,
             'tipo' => NULL
         );
         
         if( isset($_POST['codproveedor']) )
         {
            $this->mi_albaran['codproveedor'] = $_POST['codproveedor'];
            setcookie('fs_codproveedor', $_POST['codproveedor'], time()+31536000);
         }
         else if( isset($_COOKIE['fs_codproveedor']) )
            $this->mi_albaran['codproveedor'] = $_COOKIE['fs_codproveedor'];
         
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
         
         if( isset($_POST['tipo']) )
         {
            $this->mi_albaran['tipo'] = $_POST['tipo'];
            setcookie('fs_albp_tipo', $_POST['tipo'], time()+31536000);
         }
         else if( isset($_COOKIE['fs_albp_tipo']) )
            $this->mi_albaran['tipo'] = $_COOKIE['fs_albp_tipo'];
         
         if( isset($_POST['fecha']) )
            $this->mi_albaran['fecha'] = $_POST['fecha'];
         
         if( isset($_POST['numproveedor']) )
            $this->mi_albaran['numproveedor'] = $_POST['numproveedor'];
         
         if( isset($_POST['observaciones']) )
            $this->mi_albaran['observaciones'] = $_POST['observaciones'];
         
         if( isset($_POST['enviado']) )
         {
            if( !isset($_POST['a_stock']) )
            {
               $this->mi_albaran['a_stock'] = FALSE;
               setcookie ('fs_a_stock', 'FALSE', time()+31536000);
            }
            else
               setcookie ('fs_a_stock', 'TRUE', time()+31536000);
         }
         else
         {
            if( isset($_COOKIE['fs_a_stock']) )
               $this->mi_albaran['desc_stock'] = ($_COOKIE['fs_desc_stock'] == 'TRUE');
         }
         
         if( !$this->crear_albaran($mod) )
         {
            echo "<div class='destacado'><span>Nuevo albarán de proveedor:</span></div>\n",
               "<form action='",$this->recargar($mod, $pag),"' method='post'>\n
               <input type='hidden' name='enviado' value='TRUE'/>
               <div class='lista2'>
                  <div class='bloque'>Proveedor: ",$this->select_proveedores(),"</div>
                  <div class='bloque'>Ejercicio: ",$this->select_ejercicios(),"</div>
                  <div class='bloque'>Serie: ",$this->select_series(),"</div>
                  <div class='bloque'>Fecha: <input type='text' class='tcal' name='fecha' value='",$this->mi_albaran['fecha'],"' size='8'/></div>
                  <div class='bloque'>Tipo de albarán: ",$this->select_tipos(),"</div>
                  <div class='bloque'>Num. proveedor: <input type='text' name='numproveedor' value='",$this->mi_albaran['numproveedor'],"' size='6'/></div>
                  <div class='bloque'>",$this->checkbox_a_stock(),"</div>
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
                  <td align='right'><input type='submit' value='guardar'></td>\n
               </tr>\n
               </table>\n</form>\n";
            }
         }
         else
            echo "<div class='error'>El carrito está vacío.</div>";
   }
   
   private function crear_albaran($mod)
   {
      $guardado = FALSE;
      if( isset($_POST['enviado']) )
      {
         $error = '';
         if( $this->albaranes->carrito2albaran($this->mi_albaran, $this->mi_albaran['a_stock'], $this->mi_carrito, $error) )
         {
            echo "<div class='mensaje'>Albar&aacute;n
               <a href='ppal.php?mod=",$mod,"&amp;pag=albaranprov&amp;id=",$this->mi_albaran['idalbaran'],"'>",$this->mi_albaran['codigo'],"</a>
               guardado correctamente.<br/><br/>
               <img src='images/progreso.gif' align='middle' alt='en progreso'/> Redireccionando ...</div>\n
               <script type=\"text/javascript\">
                  function fs_onload() {
                     setTimeout('recargar()', 1000);
                  }
                  function recargar() {
                     window.location.href = \"ppal.php?mod=",$mod,"&pag=albaranprov&id=",$this->mi_albaran['idalbaran'],"\";
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
   
   private function select_proveedores()
   {
      $mis_proveedores = FALSE;
      $this->proveedores->all($mis_proveedores);
      
      echo "<select name='codproveedor'>";
      if( $mis_proveedores )
      {
         foreach($mis_proveedores as $pro)
         {
            if($pro['codproveedor'] == $this->mi_albaran['codproveedor'])
               echo "<option value='",$pro['codproveedor'],"' selected='selected'>",$pro['nombre'],"</option>\n";
            else
               echo "<option value='",$pro['codproveedor'],"'>",$pro['nombre'],"</option>\n";
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
   
   private function select_tipos()
   {
      $tipos = $this->albaranes->tipos();
      
      echo "<select name='tipo'>\n";
      for($i = 0; $i < count($tipos); $i++)
      {
         if($i == $datos['tipo'])
            echo '<option value="' , $i , '" selected="selected">' , $tipos[$i] , '</option>' , "\n";
         else
            echo '<option value="' , $i , '">' , $tipos[$i] , '</option>' , "\n";
      }
      echo "</select>";
   }
   
   private function checkbox_a_stock()
   {
      if( $this->mi_albaran['a_stock'] )
         echo "<input id='a_stock' type='checkbox' name='a_stock' value='TRUE' checked='checked'/>";
      else
         echo "<input id='a_stock' type='checkbox' name='a_stock' value='TRUE'/>";
      echo "<label for='a_stock'>añadir a stock</label>";
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
}

?>