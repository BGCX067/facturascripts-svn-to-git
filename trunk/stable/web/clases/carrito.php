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

class carrito
{
   private $bd;

   public function __construct()
   {
      $this->bd = new db();
   }

   /// devuelve por parametro el carrito de un usuario en un array
   public function get($user, &$carrito)
   {
      $retorno = false;
      $resultado = $this->bd->select("SELECT * FROM fs_carrito WHERE usuario = '$user' ORDER BY referencia ASC;");
      if($resultado)
      {
         $carrito = $resultado;
         $retorno = true;
      }
      return($retorno);
   }

   /// devuelve por parametro los articulos del carrito, pero con columnas extra de articulos
   public function get_articulos($user, &$carrito)
   {
      $retorno = false;
      $consulta = "SELECT c.idlinea,c.referencia,c.cantidad,a.descripcion,a.pvp,a.codimpuesto,i.iva,c.pvpunitario,c.dtopor,c.descripcion2 ".
         "FROM articulos a, fs_carrito c, impuestos i ".
         "WHERE usuario = '$user' AND a.referencia = c.referencia AND a.codimpuesto = i.codimpuesto ".
         "ORDER BY c.referencia ASC;";
      $resultado = $this->bd->select($consulta);
      if($resultado)
      {
         $carrito = $resultado;
         $retorno = true;
      }
      return($retorno);
   }

   /// añade un articulo al carrito del usuario
   public function insert($user, &$linea, &$error)
   {
      $retorno = true;

      /// comprobamos la validez de los datos
      if($linea['referencia'] == '')
      {
         $error = "Referencia en blanco";
         $retorno = false;
      }

      if($linea['pvp'] AND (!is_numeric($linea['pvp'])))
      {
         $error = "PVP no v&aacute;lido";
         $retorno = false;
      }
      else if($linea['pvp'] == '')
         $linea['pvp'] = 0;
      else
         $linea['pvp'] = round($linea['pvp'], 2);

      if($linea['dto'] AND (!is_numeric($linea['dto'])))
      {
         $error = "Descuento no v&aacute;lido";
         $retorno = false;
      }
      else if($linea['dto'] == '')
         $linea['dto'] = 0;

      if($linea['cantidad'] AND (!is_numeric($linea['cantidad'])))
      {
         $error = "Cantidad no v&aacute;lida";
         $retorno = false;
      }
      else if($linea['cantidad'] == '')
         $linea['cantidad'] = 1;

      if($retorno)
      {
         /// generamos la sentencia SQL
         if($linea['descripcion2'] == "")
         {
            $consulta = "INSERT INTO fs_carrito (referencia, usuario, pvpunitario, dtopor, cantidad) VALUES ";
            $consulta .= "('$linea[referencia]','$user','$linea[pvp]','$linea[dto]','$linea[cantidad]');";
         }
         else
         {
            $consulta = "INSERT INTO fs_carrito (referencia, usuario, pvpunitario, dtopor, cantidad, descripcion2) VALUES ";
            $consulta .= "('$linea[referencia]','$user','$linea[pvp]','$linea[dto]','$linea[cantidad]', '$linea[descripcion2]');";
         }

         $resultado = $this->bd->exec($consulta);

         if( !$resultado )
         {
            $error = "Error al insertar el art&iacute;culo";
            $retorno = false;
         }
      }

      return($retorno);
   }

   /// modifica la cantidad de una linea de carrito
   public function update(&$linea, &$error)
   {
      $retorno = true;

      /// comprobamos la validez de los datos
      if($linea['id'] == '')
      {
         $error = "Idlinea en blanco";
         $retorno = false;
      }

      if($linea['cantidad'] == '' OR (!is_numeric($linea['cantidad'])))
      {
         $error = "Cantidad no v&aacute;lida";
         $retorno = false;
      }

      if($linea['pvp'] AND (!is_numeric($linea['pvp'])))
      {
         $error = "PVP no v&aacute;lido";
         $retorno = false;
      }
      else
      {
         /// redondeamos
         $linea['pvp'] = round($linea['pvp'], 2);
      }

      if($linea['dto'] AND (!is_numeric($linea['dto'])))
      {
         $error = "Descuento no v&aacute;lido";
         $retorno = false;
      }


      if($retorno)
      {
         /// construimos la sentencia SQL
         $consulta = "UPDATE fs_carrito SET cantidad = '$linea[cantidad]'";
         if($linea['pvp'])
         {
            $consulta .= ", pvpunitario = '$linea[pvp]'";
         }

         if($linea['dto'])
         {
            $consulta .= ", dtopor = '$linea[dto]'";
         }

         if($linea['descripcion2'])
         {
            $consulta .= ", descripcion2 = '$linea[descripcion2]'";
         }
         $consulta .= " WHERE idlinea = '$linea[id]';";

         $resultado = $this->bd->exec($consulta);
         if(!$resultado)
         {
            $error = $consulta;
            $retorno = false;
         }
      }

      return($retorno);
   }

   /// elimina una linea del carrito
   public function delete($idlinea, &$error)
   {
      $retorno = true;

      /// comprobamos la validez de los datos;
      if($idlinea != '')
      {
         $resultado = $this->bd->exec("DELETE FROM fs_carrito WHERE idlinea = '$idlinea';");

         if(!$resultado)
         {
            $error = "Error al eliminar el art&iacute;culo.";
            $retorno = false;
         }
      }
      else
      {
         $error = "Ninguna l&iacute;nea seleccionada";
         $retorno = false;
      }

      return($retorno);
   }

   /// vacia el carrito del usuario
   public function clean($user)
   {
      $retorno = true;

      if($user)
      {
         $resultado = $this->bd->exec("DELETE FROM fs_carrito WHERE usuario = '$user';");
         
         if(!$resultado)
         {
            $retorno = false;
         }
      }

      return($retorno);
   }
}
?>
