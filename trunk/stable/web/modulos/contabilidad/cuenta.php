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
require_once("clases/asientos.php");
require_once("clases/cuentas.php");
require_once("clases/ejercicios.php");

class script_ extends script
{
   private $asientos;
   private $cuentas;
   private $ejercicios;

   public function __construct($ppal)
   {
      parent::__construct($ppal);

      $this->asientos = new asientos();
      $this->cuentas = new cuentas();
      $this->ejercicios = new ejercicios();
   }

   /// devuelve el titulo del script
   public function titulo()
   {
      $tipo = "Error";
      
      if( isset($_GET['tipo']) )
      {
         if($_GET['tipo'] == 'c')
            $tipo = "Cuenta";
         else if($_GET['tipo'] == 's')
            $tipo = "Subcuenta";
      }
      
      return $tipo;
   }
   
   /// capturar las variables necesarias para el script enviadas por GET y POST
   public function datos()
   {
      $datos = array(
          'id' => '',
          'tipo' => '',
          'formulario' => '',
          'hasta' => 0,
          'partidas' => array(),
          'pagina' => '',
          'total' => ''
      );
      
      if( isset($_GET['id']) )
         $datos['id'] = $_GET['id'];
      
      if( isset($_GET['tipo']) )
         $datos['tipo'] = $_GET['tipo'];
      
      if( isset($_POST['formulario']) )
         $datos['formulario'] = $_POST['formulario'];
      
      if( isset($_POST['hasta']) )
         $datos['hasta'] = $_POST['hasta'];
      
      if($datos['formulario'] == "puntear" AND $datos['hasta'] > 0)
      {
         for($i = 1; $i <= $datos['hasta']; $i++)
         {
            $datos['partidas'][$i]['idpartida'] = $_POST['id' . $i];
            
            if($_POST['p' . $i])
               $datos['partidas'][$i]['puntear'] = true;
            else
               $datos['partidas'][$i]['puntear'] = false;
         }
      }
      
      if( isset($_GET['p']) )
         $datos['pagina'] = $_GET['p'];
      
      if( isset($_GET['t']) )
         $datos['total'] = $_GET['t'];
      
      return $datos;
   }
   
   /// genera la url necesaria para recargar el script
   public function recargar($mod, $pag)
   {
      return("ppal.php?mod=" . $mod . "&amp;pag=" . $pag . "&amp;tipo=" . $_GET['tipo'] . "&amp;id=" . $_GET['id']);
   }
   
   /// carga el cuerpo del script
   public function cuerpo($mod, $pag, &$datos)
   {
      $item = false;
      $items = false;
      $error = false;
      
      if($datos['tipo'] == 'c')
      {
         if( $this->cuentas->get_cuenta($datos['id'], $item) )
         {
            echo "<div class='destacado'><span>Cuenta: " , $item['codcuenta'] , " | Ejercicio: ",
               $this->ejercicios->get_nombre($item['codejercicio']) , " | " , $item['descripcion'],
               "</span></div>\n";
            
            if( $this->cuentas->subcuentas($datos['id'], $items) )
               $this->subcuentas($mod, $pag, $items);
            else
               echo "<div class='error'>No hay subcuentas asociadas</div>\n";
         }
         else
            echo "<div class='error'>Cuenta no encontrada</div>\n";
      }
      else if($datos['tipo'] == 's')
      {
         if( $this->cuentas->get_subcuenta($datos['id'], $item) )
         {
            echo "<div class='destacado'><span>Subcuenta: " , $item['codsubcuenta'] , " &nbsp; - &nbsp; " , $item['descripcion'],
               " &nbsp; &nbsp; Cuenta: <a href='ppal.php?mod=" , $mod , "&amp;pag=" , $pag , "&amp;tipo=c&amp;id=" , $item['idcuenta'] , "'>",
               $item['codcuenta'] , "</a></span><br/>\n",
               "Ejercicio: <b>" , $this->ejercicios->get_nombre($item['codejercicio']) , "</b>",
               " &nbsp; &nbsp; Debe: <b>" , number_format($item['debe'], 2) , " &euro;</b>",
               " &nbsp; &nbsp; Haber: <b>" , number_format($item['haber'], 2) , " &euro;</b>",
               " &nbsp; &nbsp; Saldo: <b>" , number_format($item['saldo'], 2) , " &euro;</b>",
               "</div>\n";
            
            /// punteamos
            if($datos['formulario'] == "puntear" AND $datos['hasta'] > 0 AND count($datos['partidas']) > 0)
            {
               if( $this->asientos->puntear($datos['id'], $datos['partidas'], $error) )
                  echo "<div class='mensaje'>Modificado correctamente</div>\n";
               else
                  echo "<div class='error'>" , $error , "</div>\n";
            }
            
            if( $this->asientos->get_by_subcta($datos['id'], $items, FS_LIMITE, $datos['pagina'], $datos['total']) )
            {
               $saldo = 0;
               
               /// debemos obtener todos los asientos anteriores para calcular los saldos
               if($datos['pagina'] > 0 AND $datos['pagina'] < $datos['total'])
               {
                  $items_aux = false;
                  $desde = 0;
                  $hasta = $datos['pagina'];
                  $total = 0;
                  
                  if( $this->asientos->get_by_subcta($datos['id'], $items_aux, $hasta, $desde, $total) )
                  {
                     foreach($items_aux as $col)
                        $saldo += ($col['debe'] - $col['haber']);
                  }
                  else
                     echo "<div class='error'>Error al obtener las partidas anteriores</div>\n";
               }
               
               $this->mostrar_asientos($mod, $pag, $datos['id'], $datos['pagina'], $datos['total'], $items, $saldo);
               $this->paginar("ppal.php?mod=" . $mod . "&amp;pag=" . $pag . "&amp;tipo=s&amp;id=" . $datos['id'], FS_LIMITE, $datos['pagina'], $datos['total']);
            }
            else
               echo "<div class='mensaje'>No hay asientos asociados</div>\n";
         }
         else
            echo "<div class='error'>Subcuenta no encontrada</div>\n";
      }
      else
         echo "<div class='error'>Datos inv&aacute;lidos</div>\n";
   }
   
   private function subcuentas($mod, $pag, $subcuentas)
   {
      echo "<div class='lista'>Subcuentas asociadas (" , number_format(count($subcuentas), 0) , ")</div>\n",
         "<table class='lista'>\n",
         "<tr class='destacado'>\n",
         "<td>Subcuenta</td>\n",
         "<td align='right'>Debe</td>\n",
         "<td align='right'>Haber</td>\n",
         "<td align='right'>Saldo</td>\n",
         "</tr>\n";
      
      foreach($subcuentas as $col)
      {
         echo "<tr>\n",
            "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=cuenta&amp;tipo=s&amp;id=" , $col['idsubcuenta'] , "'>" , $col['codsubcuenta'] , "</a>\n",
            substr($col['descripcion'], 0, 80) . "</td>\n",
            "<td align='right'>" , number_format($col['debe'], 1) , " &euro;</td>\n",
            "<td align='right'>" , number_format($col['haber'], 1) , " &euro;</td>\n",
            "<td align='right'>" , number_format($col['saldo'], 1) , " &euro;</td>\n",
            "</tr>\n";
      }
      
      echo "</table>\n";
   }
   
   private function mostrar_asientos($mod, $pag, $idsubcuenta, $pagina, $total, $asientos, $saldo)
   {
      $numero = 1;
      
      echo "<div class='lista'>Mostrando " , count($asientos) , " de " , number_format($total, 0) , "</div>\n",
         "<form name='partidas' action='ppal.php?mod=" , $mod ,"&amp;pag=" , $pag , "&amp;tipo=s&amp;id=",
              $idsubcuenta , "&amp;p=" , $pagina , "&amp;t=" , $total , "' method='post'>\n",
         "<input type='hidden' name='formulario' value='puntear'/>\n",
         "<input type='hidden' name='hasta' value='" , count($asientos) , "'/>\n",
         "<table class='lista'>\n",
         "<tr class='destacado'>\n",
         "<td width='30'></td>\n",
         "<td>Asiento</td>\n",
         "<td>Fecha</td>\n",
         "<td>Concepto</td>\n",
         "<td align='right'>Debe</td>\n",
         "<td align='right'>Haber</td>\n",
         "<td align='right'>Saldo</td>\n",
         "</tr>\n";
      
      foreach($asientos as $col)
      {
         echo "<tr>\n";
         
         if($col['punteada'] == 't')
         {
            echo "<td><input type='hidden' name='id" , $numero , "' value='" , $col['idpartida'] , "'/><input type='checkbox' name='p" , $numero,
                    "' value='true' checked /></td>";
         }
         else
         {
            echo "<td><input type='hidden' name='id" , $numero , "' value='" , $col['idpartida'] , "'/><input type='checkbox' name='p" , $numero,
                    "' value='true'/></td>";
         }
         
         $saldo += $col['debe'] - $col['haber'];
         
         echo "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=asiento&amp;id=" , $col['idasiento'] , "'>" , $col['numero'] , "</a></td>\n",
            "<td>" , Date('d-m-Y', strtotime($col['fecha'])) , "</td>\n",
            "<td>" , $col['concepto'] , "</td>\n",
            "<td align='right'>" , number_format($col['debe'], 2) , " &euro;</td>\n",
            "<td align='right'>" , number_format($col['haber'], 2) , " &euro;</td>\n",
            "<td align='right'>" , number_format($saldo, 2) , " &euro;</td>\n",
            "</tr>\n";
         
         $numero++;
      }
      
      echo "<tr class='gris'>\n",
         "<td colspan='2'><input type='submit' value='Modificar'/></td>\n",
         "<td colspan='5'>Pulsa modificar para guardar los cambios de las partidas punteadas</td>\n",
         "</tr>\n",
         "</table>\n</form>\n";
   }
}

?>