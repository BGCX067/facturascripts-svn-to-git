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

class impuestos
{
   private $bd;

   public function __construct()
   {
      $this->bd = new db();
   }

   public function all(&$impuestos)
   {
      $resultado = $this->bd->select("SELECT * FROM impuestos ORDER BY codimpuesto ASC;");
      if($resultado)
      {
         $impuestos = $resultado;
         return TRUE;
      }
      else
         return FALSE;
   }

   public function iva($codimpuesto)
   {
      $resultado = $this->bd->select("SELECT iva FROM impuestos WHERE codimpuesto = '$codimpuesto';");
      if($resultado)
         return floatval( $resultado[0]['iva'] );
      else
         return 0;
   }

   public function ivas()
   {
      $ivas = array();
      $resultado = $this->bd->select("SELECT codimpuesto, iva FROM impuestos ORDER BY codimpuesto;");
      if($resultado)
      {
         foreach($resultado as $col)
            $ivas[$col['codimpuesto']] = floatval( $col['iva'] );
      }
      return $ivas;
   }
}
?>