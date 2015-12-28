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

class mensajes
{
   private $bd;

   public function __construct()
   {
      $this->bd = new db();
   }

   public function escribir($usuario, $url, $texto, $usu2)
   {
      $retorno = true;

      if($texto == '')
      {
         $retorno = false;
      }
      else
      {
         $fecha = date('c');
         $texto = htmlspecialchars($texto, ENT_QUOTES);

         /// construimos las sentencias SQL
         $consulta = "INSERT INTO fs_mensajes (tipo, usuario, fecha, texto) VALUES ('chat', '$usuario', '$fecha', '$texto');";

         if( $url != '')
         {
            $consulta .= "UPDATE fs_mensajes SET url = '$url' WHERE tipo = 'chat' AND usuario = '$usuario' AND fecha = '$fecha' AND texto = '$texto';";
         }

         if( $usu2 != '')
         {
            $consulta .= "UPDATE fs_mensajes SET etiqueta = '$usu2' WHERE tipo = 'chat' AND usuario = '$usuario' AND fecha = '$fecha' AND texto = '$texto';";
         }

         if( !$this->bd->exec($consulta) )
         {
            $retorno = false;
         }
      }

      return($retorno);
   }

   public function leer($numero)
   {
      return( $this->bd->select_limit("SELECT * FROM fs_mensajes WHERE tipo = 'chat' ORDER BY fecha DESC, id DESC", $numero, 0) );
   }

   public function borrar($id)
   {
      $retorno = false;

      if($id != "")
      {
         if($this->bd->exec("DELETE FROM fs_mensajes WHERE id = '$id' AND tipo = 'chat';"))
         {
            $retorno = true;
         }
      }

      return($retorno);
   }
}

?>
