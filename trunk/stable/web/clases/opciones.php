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

class opciones
{
   private $bd;

   public function __construct()
   {
      $this->bd = new db();
   }

   public function all(&$opciones)
   {
      $retorno = false;

      $resultado = $this->bd->select("SELECT * FROM fs_opciones;");
      if($resultado)
      {
         foreach($resultado as $col)
            $opciones[$col['cod']] = $col['valor'];
         $retorno = true;
      }

      return($retorno);
   }

   public function get($cod, &$valor)
   {
      $retorno = false;

      if($cod)
      {
         $resultado = $this->bd->select("SELECT * FROM fs_opciones WHERE cod = '$cod';");
         if($resultado)
         {
            $valor = $resultado[0]['valor'];
            $retorno = true;
         }
      }

      return($retorno);
   }

   public function valor($cod)
   {
      $valor = false;

      if($cod)
      {
         $resultado = $this->bd->select("SELECT * FROM fs_opciones WHERE cod = '$cod';");
         if($resultado)
            $valor = $resultado[0]['valor'];
      }

      return($valor);
   }

   public function update($opciones, &$error)
   {
      $retorno = false;

      if($opciones)
      {
         /// construimos la sentencia SQL
         $consulta = "UPDATE fs_opciones SET valor = '" . $opciones['ejercicio'] . "' WHERE cod = 'ejercicio';".
            "UPDATE fs_opciones SET valor = '" . $opciones['serie'] . "' WHERE cod = 'serie';".
            "UPDATE fs_opciones SET valor = '" . $opciones['cliente'] . "' WHERE cod = 'cliente';".
            "UPDATE fs_opciones SET valor = '" . $opciones['impuesto'] . "' WHERE cod = 'impuesto';".
            "UPDATE fs_opciones SET valor = '" . $opciones['puerto_com'] . "' WHERE cod = 'puerto_com';";

         if( $this->bd->exec($consulta) )
            $retorno = true;
         else
            $error = $consulta;
      }

      return($retorno);
   }
}

?>