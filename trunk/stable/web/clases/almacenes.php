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

class almacenes
{
   private $bd;

   public function __construct()
   {
      $this->bd = new db();
   }

   public function get($codalmacen)
   {
      $almacen = false;

      if($codalmacen)
      {
         $resultado = $this->bd->select("SELECT * FROM almacenes WHERE codalmacen='$codalmacen';");
         if($resultado)
         {
            $almacen = $resultado[0];
         }
      }

      return($almacen);
   }

   public function all()
   {
      $resultado = $this->bd->select("SELECT * FROM almacenes ORDER BY codalmacen ASC;");

      return($resultado);
   }
}

?>
