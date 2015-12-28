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

class script_ extends script
{
   private $log;
   private $fechas_log;

   public function __construct($ppal)
   {
      parent::__construct($ppal);

      /// leemos todas las fechas del log
      $this->fechas_log = $this->bd->select("SELECT DISTINCT to_char(fecha,'yyyy-mm') as mes FROM fs_mensajes WHERE tipo = 'auto' ORDER BY mes DESC;");
      
      if( isset($_POST['fecha']) )
      {
         /// leemos el log
         if($_POST['fecha'] == '' OR $_POST['fecha'] == '-')
            $this->log = $this->bd->select_limit("SELECT * FROM fs_mensajes WHERE tipo = 'auto' ORDER BY fecha DESC, id DESC", 100, 0);
         else
         {
            $this->log = $this->bd->select("SELECT * FROM fs_mensajes WHERE tipo = 'auto' AND to_char(fecha,'yyyy-mm') = '".$_POST['fecha']."'
                                            ORDER BY fecha ASC, id ASC;");
         }
      }
      else
         $this->log = $this->bd->select_limit("SELECT * FROM fs_mensajes WHERE tipo = 'auto' ORDER BY fecha DESC, id DESC", 100, 0);
   }

   /// devuelve el titulo del script
   public function titulo()
   {
      return("Automata");
   }

   /// carga el cuerpo del script
   public function cuerpo($mod, $pag, &$datos)
   {
      if( isset($_GET['vh']) )
      {
         /// eliminamos los registros seleccionados (si hay seleccionados)
         $this->vaciar_hasta($_GET['vh']);
      }

      if( !$this->leer_log($mod, $pag) )
         echo "<div class='mensaje'>Log vac&iacute;o</div>\n";
      
      if( file_exists("articulos.bak") )
         echo "<div class='opciones'><a href='articulos.bak'>Descargar articulos.bak</a></div>\n";
   }

   private function vaciar_hasta($num)
   {
      if($num > 0)
      {
         if( $this->bd->exec("DELETE FROM fs_mensajes WHERE tipo = 'auto' AND id < '" . $num . "';") )
            echo "<div class='mensaje'>Registros eliminados correctamente</div>\n";
         else
            echo "<div class='error'>Error al eliminar los registros</div>\n";
      }
   }

   private function leer_log($mod, $pag)
   {
      $retorno = false;

      if($this->fechas_log)
      {
         echo "<div class='lista'>\n",
            "<form name='log' action='" , $this->recargar($mod, $pag) , "' method='post'>Fecha:\n",
            "<select name='fecha' onchange='document.log.submit()'>\n",
            "<option value='-'>-- Lo &uacute;ltimo --</option>\n";

         foreach($this->fechas_log as $col)
         {
            if($_POST['fecha'] == $col['mes'])
               echo "<option value='" , $col['mes'] , "' selected>" , $col['mes'] , "</option>\n";
            else
               echo "<option value='" , $col['mes'] , "'>" , $col['mes'] , "</option>\n";
         }

         echo "</select>\n<input type='submit' value='ver'/></form>\n</div>\n";
      }

      if($this->log)
      {
         $retorno = true;
         $fecha_aux = false;
         $vaciar_hasta = $this->log[0]['id'];

         echo "<table class='lista'>\n";

         foreach($this->log as $col)
         {
            $fecha_all = explode(' ', $col['fecha']);
            $fecha = explode('-', $fecha_all[0]);

            /// marcamos la fecha
            if($fecha_all[0] != $fecha_aux)
            {
               echo "<tr class='destacado'><td> <b>" , $fecha[2] , ' / ' , $fecha[1] ,  ' / ' , $fecha[0] , "</b></td></tr>\n";
               
               $fecha_aux = $fecha_all[0];
            }

            switch($col['etiqueta'])
            {
               default:
                  echo "<tr>"; break;

               case "error":
                  echo "<tr class='rojo'>"; break;

               case "aviso":
                  echo "<tr class='amarillo'>"; break;
            }

            echo "<td><i>" , $fecha_all[1] , "</i> - <b>" , $col['etiqueta'] , "</b>: " , $col['texto'] , "</td></tr>\n";

            /// seleccionamos el menor (para la función vaciar hasta)
            $vaciar_hasta = min($col['id'], $vaciar_hasta);
         }

         echo "</table>\n",
            "<div class='centrado'><a class='cancelar' href='" , $this->recargar($mod, $pag) , "&amp;vh=" , $vaciar_hasta,
                 "'>Eliminar los registros anteriores</a></div>\n";
      }

      return($retorno);
   }
}

?>
