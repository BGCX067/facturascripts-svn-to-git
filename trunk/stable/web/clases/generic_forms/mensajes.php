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

require_once("clases/mensajes.php");

class script_ extends script
{
   private $mensajes;
   private $fechas_mes;
   private $lista_mes;

   public function __construct($ppal)
   {
      parent::__construct($ppal);

      $this->generico = true;
      $this->mensajes = new mensajes();
   }
   
   /// devuelve el titulo del script
   public function titulo()
   {
       return("Mensajes");
   }

   /// captura las variables necesarias para el script enviadas por GET y POST
   public function datos()
   {
      /// url
      if($_GET['u'] != "")
      {
         $datos['url'] = rawurldecode($_GET['u']);
      }
      else if($_POST['u'] != "")
      {
         $datos['url'] = $_POST['u'];
      }
      else
      {
         $datos['url'] = "";
      }

      return($datos);
   }

   /// carga el cuerpo del script
   public function cuerpo($mod, $pag, &$datos)
   {
      /// mostrar dialogo para escribir mensaje
      $this->escribir_mensaje($mod, $pag, $datos['url']);

      if($_GET['m'] == 'true')
      {
         if( $this->mensajes->escribir($this->usuario, $datos['url'], $_POST['texto'], $_POST['usu2']) )
         {
            echo "<div class='mensaje'>Mensaje a&ntilde;adido correctamente</div>\n";
         }
         else
         {
            echo "<div class='error'>Error al a&ntilde;adir el mensaje</div>\n";
         }
      }

      if($_GET['id'])
      {
         if( $this->mensajes->borrar($_GET['id']) )
         {
            echo "<div class='mensaje'>Mensaje eliminado correctamente</div>\n";
         }
         else
         {
            echo "<div class='error'>Error al eliminar el mensaje</div>\n";
         }
      }

      /*
       * Cargamos los mensajes
       */
      /// leemos todas las fechas de los mensajes
      $this->fechas_mes = $this->bd->select("SELECT DISTINCT fecha::date FROM fs_mensajes WHERE tipo = 'chat' ORDER BY fecha ASC;");

      /// leemos los mensajes
      if($_POST['fecha'] == '' OR $_POST['fecha'] == '-')
      {
         $this->lista_mes = $this->bd->select_limit("SELECT * FROM fs_mensajes WHERE tipo = 'chat' ORDER BY fecha DESC, id DESC", 100, 0);
      }
      else
      {
         $this->lista_mes = $this->bd->select("SELECT * FROM fs_mensajes WHERE tipo = 'chat' AND fecha::date = '" . $_POST['fecha'] . "' ORDER BY fecha ASC, id ASC;");
      }


      /// mostramos los ultimos mensajes
      if($this->lista_mes)
      {
         if($this->fechas_mes)
         {
            echo "<div class='lista'>\n",
               "<form name='log' action='" , $this->recargar($mod, $pag) , "' method='post'>Mensajes:\n",
               "<select name='fecha'>\n",
               "<option value='-'>-- Lo &uacute;ltimo --</option>\n";

            foreach($this->fechas_mes as $col)
            {
               if($_POST['fecha'] == (Date('j-n-Y', strtotime($col['fecha']))))
               {
                  echo "<option value='" , Date('j-n-Y', strtotime($col['fecha'])) , "' selected>" , Date('j/n/Y', strtotime($col['fecha'])) , "</option>\n";
               }
               else
               {
                  echo "<option value='" , Date('j-n-Y', strtotime($col['fecha'])) , "'>" , Date('j/n/Y', strtotime($col['fecha'])) , "</option>\n";
               }
            }

            echo "</select>\n<input type='submit' value='ver'/></form>\n</div>\n";
         }

         $this->leer_mensajes($mod, $pag);
      }
      else
      {
         echo "<div class='mensaje'>No hay mensajes</div>\n";
      }
   }

   /// muestra por pantalla los mensajes
   private function leer_mensajes($mod, $pag)
   {
      echo "<table class='chat'>\n";

      $i = true;
      $fecha_aux = false;

      foreach($this->lista_mes as $col)
      {
         $fecha_all = explode(' ', $col['fecha']);
         $fecha = explode('-', $fecha_all[0]);

         /// marcamos la fecha
         if($fecha_all[0] != $fecha_aux)
         {
            $fecha_aux = $fecha_all[0];

            if($i) { echo "<tr class='gris'><td colspan='3'><b>" , $fecha[2] , '/' , $fecha[1] ,  '/' , $fecha[0] , "</b></td></tr>\n"; }
            else { echo "<tr><td colspan='3'><b>" , $fecha[2] , '/' , $fecha[1] ,  '/' , $fecha[0] , "</b></td></tr>\n"; }

            $i = !$i;
         }

         if($i) { echo "<tr class='gris'>\n"; }
         else { echo "<tr>\n"; }

         if($col['url'] != '')
         {
            echo "<td width='25'><a href='" , $col['url'] , "'><img src='images/ver.png' alt='enlace' title='Ver p&aacute;gina'/></a></td>\n";
         }
         else
         {
            echo "<td width='25'></td>\n";
         }

         if($col['etiqueta'] == '')
         {
            echo "<td><i>" , $fecha_all[1] , "</i> - <b>" , $col['usuario'] , "</b>: " , $col['texto'] , "</td>\n";
         }
         else
         {
            echo "<td><i>" , $fecha_all[1] , "</i> - <b>" , $col['usuario'] , "</b> dice a <b>" , $col['etiqueta'] , "</b>:<br/>" , $col['texto'] , "</td>\n";
         }

         if($col['usuario'] == $this->usuario)
         {
            echo "<td align='right'><a href='ppal.php?mod=" , $mod , "&amp;pag=" , $pag , "&amp;id=" , $col['id'] , "&amp;b=true'>\n",
               "<img alt='borrar' src='images/dialog-cancel.png'/></a></td></tr>\n";
         }
         else
         {
            echo "<td></td>\n</tr>\n";
         }

         $i = !$i;
      }

      echo "</table>\n";
   }

   private function escribir_mensaje($mod, $pag, $url)
   {
      echo "<div class='destacado'>\n", "<form name='mensaje' action='ppal.php?mod=" , $mod , "&amp;pag=" , $pag , "&amp;m=true' method='post'>\n",
         "Nuevo mensaje para <span>" , $this->listar_usuarios() , "<br/>\n<input type='hidden' name='u' value='" , $url , "'/>\n",
         "<textarea name='texto' rows='1' cols='90' onfocus='this.select()'>Texto</textarea>\n",
         "<br/>\n<input type='submit' value='enviar'/>\n",
         "</form>\n" , "</div>\n";
   }

   private function listar_usuarios()
   {
      /// obtenemos el listado de usuarios
      $usuarios = $this->bd->select("SELECT usuario FROM fs_usuarios ORDER BY usuario ASC;");

      if($usuarios)
      {
         echo '<select name="usu2">' , "\n",
            '<option value="">-- todos --</option>' , "\n";

         foreach($usuarios as $col)
         {
            if($col['usuario'] != $this->usuario)
            {
               echo '<option value="' , $col['usuario'] , '">' , ucfirst($col['usuario']) , '</option>' , "\n";
            }
         }

         echo '</select>' , "\n";
      }
   }
}

?>
