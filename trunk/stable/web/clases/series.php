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

class series
{
   private $bd;

   public function __construct()
   {
      $this->bd = new db();
   }

   public function all(&$series)
   {
      $retorno = false;

      $resultado = $this->bd->select("SELECT * FROM series ORDER BY codserie ASC;");
      if($resultado)
      {
         $series = $resultado;
         $retorno = true;
      }

      return($retorno);
   }

   public function get($codserie)
   {
      $serie = false;

      if($codserie)
      {
         $resultado = $this->bd->select("SELECT * FROM series WHERE codserie = '$codserie';");
         if($resultado)
         {
            $serie = $resultado[0];
         }
      }

      return($serie);
   }

   /// devuelve true si la serie es sin iva
   public function sin_iva($codserie)
   {
      $retorno = false;

      if($codserie)
      {
         $resultado = $this->bd->select("SELECT siniva FROM series WHERE codserie = '$codserie';");
         if($resultado[0]['siniva'] == 't')
         {
            $retorno = true;
         }
      }

      return($retorno);
   }
}

?>