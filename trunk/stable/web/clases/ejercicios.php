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

class ejercicios
{
   private $bd;

   public function __construct()
   {
      $this->bd = new db();
   }

   public function get($codejercicio,&$ejercicio)
   {
      $retorno = false;

      if($codejercicio)
      {
         $resultado = $this->bd->select("SELECT * FROM ejercicios WHERE codejercicio = '$codejercicio';");
         if($resultado)
         {
            $ejercicio = $resultado[0];
            $retorno = true;
         }
      }

      return($retorno);
   }

   public function all(&$ejercicios)
   {
      $retorno = false;

      $resultado = $this->bd->select("SELECT * FROM ejercicios ORDER BY codejercicio DESC;");
      if($resultado)
      {
         $ejercicios = $resultado;
         $retorno = true;
      }

      return($retorno);
   }

   /// devuelve el nombre del un codigo de ejercicio dado
   public function get_nombre($codejercicio)
   {
      $nombre = false;

      if($codejercicio)
      {
         $resultado = $this->bd->select("SELECT nombre FROM ejercicios WHERE codejercicio = '$codejercicio';");
         if($resultado)
         {
            $nombre = $resultado[0]['nombre'];
         }
      }

      return($nombre);
   }

   /// devuelve true si el ejercicio sigue abierto, false en caso contrario
   public function abierto($codejercicio)
   {
      $abierto = false;

      if($codejercicio)
      {
         $resultado = $this->bd->select("SELECT estado FROM ejercicios WHERE codejercicio = '$codejercicio';");
         if($resultado)
         {
            if($resultado[0]['estado'] == "ABIERTO")
            {
               $abierto = true;
            }
         }
      }

      return($abierto);
   }
}

?>
