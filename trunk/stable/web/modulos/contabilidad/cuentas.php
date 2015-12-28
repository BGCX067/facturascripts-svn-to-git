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
require_once("clases/cuentas.php");
require_once("clases/ejercicios.php");
require_once("clases/opciones.php");


class script_ extends script
{
   private $cuentas;
   private $ejercicios;
   private $opciones;

   public function __construct($ppal)
   {
      parent::__construct($ppal);

      $this->cuentas = new cuentas();
      $this->ejercicios = new ejercicios();
      $this->opciones = new opciones();
   }

   /// devuelve el titulo del script
   public function titulo()
   {
      return("Cuentas");
   }
   
   /// codigo javascript
   public function javas()
   {
      ?>
      <script type="text/javascript">
         function fs_onload()
         {
            document.cuentas.b.focus();
         }

         function fs_unload() {
         }
      </script>
      <?php
   }
   
   /// capturar las variables necesarias para el script enviadas por GET y POST
   public function datos()
   {
      $datos = array(
          'buscar' => '',
          'tipo' => ''
      );
      
      if( isset($_GET['b']) )
         $datos['buscar'] = $_GET['b'];
      
      if( isset($_GET['t']) )
         $datos['tipo'] = $_GET['t'];
      
      /// cargamos el ejercicio predefinido
      $this->opciones->get('ejercicio', $datos['ejercicio']);
      
      /// si no se selecciona ningun ejercicio, cogemos el predefinido
      if( isset($_GET['e']) )
         $datos['codejercicio'] = $_GET['e'];
      else
         $datos['codejercicio'] = $datos['ejercicio'];
      
      return $datos;
   }
   
   /// cargar el cuerpo del script
   public function cuerpo($mod, $pag, &$datos)
   {
      $ejercicios = false;
      $items = false;
      
      echo "<div class='destacado'>\n",
         "<form name='cuentas' action='ppal.php' method='get'>\n",
         "<input type='hidden' name='mod' value='" , $mod , "'/>\n",
         "<input type='hidden' name='pag' value='" , $pag , "'/>\n";
      
      if($datos['tipo'] == 's')
      {
         echo "<input type='radio' name='t' value='c'/>Cuentas\n",
            "<input type='radio' name='t' value='s' checked/>Subcuentas\n";
      }
      else
      {
         echo "<input type='radio' name='t' value='c' checked/>Cuentas\n",
            "<input type='radio' name='t' value='s'/>Subcuentas\n";
      }

      echo "<input type='text' name='b' size='18' maxlength='18' value='" , $datos['buscar'] , "'/>\n",
         "<input type='submit' value='buscar'/>\n";
      
      if( $this->ejercicios->all($ejercicios) )
      {
         echo " &nbsp; Ejercicio <select name='e' size='0'>";

         foreach($ejercicios as $col)
         {
            if($col['codejercicio'] == $datos['codejercicio'])
               echo "<option value='" , $col['codejercicio'] , "' selected='selected'>" , $col['nombre'] , "</option>\n";
            else
               echo "<option value='" , $col['codejercicio'] , "'>" , $col['nombre'] , "</option>\n";
         }

         echo "</select>\n";
      }
      
      echo "</form>\n</div>\n";
      
      
      if($datos['buscar'] != "")
      {
         $items = $this->cuentas->buscar($datos['buscar'], $datos['tipo'], $datos['codejercicio']);
         if($items)
         {
            echo "<div class='lista'>Se encontraron los siguientes resultados para el ejercicio <b>",
                    $this->ejercicios->get_nombre($datos['codejercicio']) , "</b></div>\n";
            
            if($datos['tipo'] == 'c')
               $this->listar_cuentas($mod, $pag, $items);
            else if($datos['tipo'] == 's')
               $this->subcuentas($mod, $pag, $items);
         }
         else
         {
            echo "<div class='mensaje'>No se encontraron resultados para el ejercicio <b>",
                    $this->ejercicios->get_nombre($datos['codejercicio']) , "</b></div>\n";
         }
      }
      else
      {
         if( $this->cuentas->lista_cuentas($datos['codejercicio'], $items) )
         {
            echo "<div class='lista'>Lista de cuentas (" , $this->ejercicios->get_nombre($datos['codejercicio']) , ")</div>\n";
            $this->listar_cuentas($mod, $pag, $items);
         }
         else
            echo "<div class='mensaje'>Nada que mostrar</div>\n";
      }
      
   }
   
   private function listar_cuentas($mod, $pag, $cuentas)
   {
      if($cuentas)
      {
         echo "<table class='lista'>\n",
            "<tr class='destacado'>\n",
            "<td>Cuenta</td>\n",
            "<td>Grupo</td>\n",
            "<td>Descripci&oacute;n</td>\n",
            "<td align='right'>Subcuentas</td>\n",
            "</tr>\n";
         
         foreach($cuentas as $col)
         {
            if($col['subcuentas'] > 1)
               echo "<tr class='amarillo'>\n";
            else
               echo "<tr>\n";

            echo "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=cuenta&amp;tipo=c&amp;id=" , $col['idcuenta'] , "'>" , $col['codcuenta'] , "</a></td>\n",
               "<td>" , $col['codepigrafe'] , "</td>\n",
               "<td>" , $col['descripcion'] , "</td>\n",
               "<td align='right'>" , number_format($col['subcuentas'], 0) , "</td>\n",
               "</tr>\n";
         }

         echo "</table>\n";
      }
      else
         echo "<div class='mensaje'>Nada que mostrar</div>\n";
   }
   
   private function subcuentas($mod, $pag, $subcuentas)
   {
      echo "<table class='lista'>\n",
         "<tr class='destacado'>\n",
         "<td>Cuenta</td>\n",
         "<td>Subcuenta</td>\n",
         "<td>Descripci&oacute;n</td>\n",
         "<td align='right'>Debe</td>\n",
         "<td align='right'>Haber</td>\n",
         "<td align='right'>Saldo</td>\n",
         "</tr>\n";
      
      foreach($subcuentas as $col)
      {
         echo "<tr>\n",
            "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=cuenta&amp;tipo=c&amp;id=" , $col['idcuenta'] , "'>" , $col['codcuenta'] , "</a></td>\n",
            "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=cuenta&amp;tipo=s&amp;id=" , $col['idsubcuenta'] , "'>" , $col['codsubcuenta'] , "</a></td>\n",
            "<td>" , substr($col['descripcion'], 0, 60) , "</td>\n",
            "<td align='right'>" , number_format($col['debe'], 1) , " &euro;</td>\n",
            "<td align='right'>" , number_format($col['haber'], 1) , " &euro;</td>\n",
            "<td align='right'>" , number_format($col['saldo'], 1) , " &euro;</td>\n",
            "</tr>\n";
      }
      
      echo "</table>\n";
   }
}

?>