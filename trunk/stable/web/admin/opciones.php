<?php
/*
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA.
 * 
 * Autor: Carlos Garcia Gomez
*/

require("clases/script.php");
require_once("clases/opciones.php");
require_once("clases/ejercicios.php");
require_once("clases/series.php");
require_once("clases/clientes.php");
require_once("clases/impuestos.php");

class script_ extends script
{
   private $mis_opciones;
   private $ejercicios;
   private $series;
   private $clientes;
   private $impuestos;


   public function __construct($ppal)
   {
      parent::__construct($ppal);

      $this->mis_opciones = new opciones();
      $this->ejercicios = new ejercicios();
      $this->series = new series();
      $this->clientes = new clientes();
      $this->impuestos = new impuestos();
   }

   /// devuelve el titulo del script
   public function titulo()
   {
      return("Opciones de facturaScripts");
   }
   
   /// captura las variables necesarias para el script enviadas por GET y POST
   public function datos()
   {
      $datos = Array(
         'ejercicio' => '',
         'serie' => '',
         'cliente' => '',
         'impuesto' => '',
         'puerto_com' => ''
      );
      
      if( isset($_POST['codejercicio']) )
         $datos['ejercicio'] = $_POST['codejercicio'];
      
      if( isset($_POST['codserie']) )
         $datos['serie'] = $_POST['codserie'];
      
      if( isset($_POST['codcliente']) )
         $datos['cliente'] = $_POST['codcliente'];
      
      if( isset($_POST['codimpuesto']) )
         $datos['impuesto'] = $_POST['codimpuesto'];
      
      if( isset($_POST['puerto_com']) )
         $datos['puerto_com'] = $_POST['puerto_com'];
      
      return $datos;
   }

   /// carga el cuerpo del script
   public function cuerpo($mod, $pag, &$datos)
   {
      $error = false;
      $opciones = false;
      
      /// modificamos si se envia el formulario
      if( isset($_POST['procesar']) )
      {
         if( $this->mis_opciones->update($datos, $error) )
            echo "<div class='mensaje'>Opciones modificadas correctamente</div>\n";
         else
            echo "<div class='error'>Error al modificar las opciones<br/>" , $error , "</div>\n";
      }
      else
         $this->update_opciones();
      
      /// leemos y mostramos las opciones
      if( $this->mis_opciones->all($opciones) )
      {
         echo "<form name='opciones' action='ppal.php?mod=" , $mod , "&amp;pag=" , $pag , "' method='post'>\n",
            "<table width='100%'>\n" , "<tr>\n",
            "<td>\n" , $this->seleccionar_ejercicio($opciones) , "</td>\n",
            "<td width='50'></td>\n",
            "<td>\n" , $this->seleccionar_serie($opciones) , "</td>\n",
            "</tr>\n" , "<tr>\n",
            "<td>\n" , $this->seleccionar_impuesto($opciones) , "</td>\n",
            "<td width='50'></td>\n",
            "<td>\n" , $this->seleccionar_cliente($opciones) , "</td>\n",
            "</tr>\n" , "<tr>\n",
            "<td>\n" , $this->seleccionar_impresora($opciones) , "</td>\n",
            "<td width='50'></td>\n",
            "<td valign='bottom' align='right'><input type='submit' name='procesar' value='modificar'/></td>\n",
            "</tr>\n",
            "</table>\n",
            "</form>\n";
      }
      else
      {
         echo "<div class='error'>no hay datos en la tabla <b>fs_opciones</b>,".
            " debes <a href='ppal.php?mod=admin&amp;pag=upgradedb'>actualizar la base de datos</a></div>\n";
      }
   }

   /// comprueba la tabla fs_opciones y crea los registros necesarios
   private function update_opciones()
   {
      $consulta = "";
      $opciones = $this->bd->select("SELECT cod FROM fs_opciones;");
      $claves = Array(
         0 => 'ejercicio',
         1 => 'serie',
         2 => 'cliente',
         3 => 'impuesto',
         4 => 'g_maps_key',
         5 => 'puerto_com'
      );

      foreach($claves as $col)
      {
         if( !$this->check_opciones($col, $opciones) )
            $consulta .= "INSERT INTO fs_opciones (cod) VALUES ('$col');";
      }

      if($consulta)
      {
         if( $this->bd->exec($consulta) )
            echo "<div class='mensaje'>Actualizadas las opciones</div>\n";
         else
            echo "<div class='error'>error al actualizar las opciones<br/>" . $consulta . "</div>\n";
      }
   }

   /// devuelve true si $cod esta en $opciones, false en caso contrario
   private function check_opciones($cod, $opciones)
   {
      if($opciones)
      {
         $encontrada = FALSE;
         foreach($opciones as $opcion)
         {
            if($opcion['cod'] == $cod)
            {
               $encontrada = true;
               break;
            }
         }
         return $encontrada;
      }
      return FALSE;
   }

   private function seleccionar_ejercicio($opciones)
   {
      $ejercicios = false;

      echo "<div class='lista'>Ejercicio Actual:</div>\n";

      if( $this->ejercicios->all($ejercicios) )
      {
         echo "<div class='lista2'>\n",
            "Selecciona un ejercicio de la lista: <select name='codejercicio'>\n";

         foreach($ejercicios as $col)
         {
            if($col['codejercicio'] == $opciones['ejercicio'])
               echo "<option value='" , $col['codejercicio'] , "' selected='selected'>" , $col['nombre'] , "</option>\n";
            else
               echo "<option value='" , $col['codejercicio'] , "'>" , $col['nombre'] , "</option>\n";
         }

         echo "</select>\n</div>\n";
      }
      else
         echo "<div class='error'>No hay ning&uacute;n ejercicio creado</div>\n";
   }

   private function seleccionar_serie($opciones)
   {
      $series = false;

      echo "<div class='lista'>Serie Predefinida:</div>\n";

      if( $this->series->all($series) )
      {
         echo "<div class='lista2'>\n",
            "Selecciona una serie de la lista: <select name='codserie'>\n";

         foreach($series as $col)
         {
            if($col['codserie'] == $opciones['serie'])
               echo "<option value='" , $col['codserie'] , "' selected='selected'>" , $col['descripcion'] , "</option>\n";
            else
               echo "<option value='" , $col['codserie'] , "'>" , $col['descripcion'] , "</option>\n";
         }

         echo "</select>\n</div>\n";
      }
      else
         echo "<div class='error'>No hay ninguna serie creada</div>\n";
   }

   private function seleccionar_cliente($opciones)
   {
      $clientes = false;

      echo "<div class='lista'>Cliente Predefinido:</div>\n";

      if( $this->clientes->all($clientes) )
      {
         echo "<div class='lista2'>\n",
            "Selecciona un cliente de la lista: <select name='codcliente'>\n";

         foreach($clientes as $col)
         {
            if($col['codcliente'] == $opciones['cliente'])
               echo "<option value='" , $col['codcliente'] , "' selected='selected'>" , $col['nombrecomercial'] , "</option>\n";
            else
               echo "<option value='" , $col['codcliente'] , "'>" , $col['nombrecomercial'] , "</option>\n";
         }

         echo "</select>\n</div>\n";
      }
      else
         echo "<div class='error'>No hay ning&uacute;n cliente creado</div>\n";
   }

   private function seleccionar_impuesto($opciones)
   {
      $impuestos = false;

      echo "<div class='lista'>Impuesto Predefinido:</div>\n";

      if( $this->impuestos->all($impuestos) )
      {
         echo "<div class='lista2'>\n",
            "Selecciona un impuesto de la lista: <select name='codimpuesto'>\n";

         foreach($impuestos as $col)
         {
            if($col['codimpuesto'] == $opciones['impuesto'])
               echo "<option value='" , $col['codimpuesto'] , "' selected='selected'>" , $col['descripcion'] , "</option>\n";
            else
               echo "<option value='" , $col['codimpuesto'] , "'>" , $col['descripcion'] , "</option>\n";
         }

         echo "</select>\n</div>\n";
      }
      else
         echo "<div class='error'>No hay ning&uacute;n impuesto creado</div>\n";
   }

   private function seleccionar_impresora($opciones)
   {
      echo "<div class='lista'>Impresora:</div>\n",
         "<div class='lista2'>Introduce la ruta del puerto COM al que tengas conectada la impresora de tickets (solo Linux),",
         " o bien deja este cuadro en blanco para usar CUPS:\n",
         "<input type='text' name='puerto_com' value='" , $opciones['puerto_com'] , "' size='20' maxlength='49'/>\n",
         "</div>\n";
   }
}

?>