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

class empresa
{
   private $bd;

   public function __construct()
   {
      $this->bd = new db();
   }

   /// devuelve el nombre del ejercicio actual
   public function get_nombre()
   {
      $resultado = $this->bd->select("SELECT nombre FROM empresa;");
      $codejercicio = $resultado[0]['nombre'];

      return($codejercicio);
   }

   /// devuelve el codigo del ejercicio actual
   public function get_ejercicio()
   {
      $resultado = $this->bd->select("SELECT codejercicio FROM empresa;");
      $codejercicio = $resultado[0]['codejercicio'];

      return($codejercicio);
   }

   /// evuelve el codigo de la serie actual
   public function get_serie()
   {
      $codserie = false;

      $resultado = $this->bd->select("SELECT codserie FROM empresa;");
      $codserie = $resultado[0]['codserie'];

      return($codserie);
   }

   /// devuelve el codigo del almacen predeterminado
   public function get_almacen()
   {
      $resultado = $this->bd->select("SELECT codalmacen FROM empresa;");
      $codalmacen = $resultado[0]['codalmacen'];

      return($codalmacen);
   }

   public function get_divisa()
   {
      $resultado = $this->bd->select("SELECT coddivisa FROM empresa;");
      $coddivisa = $resultado[0]['coddivisa'];

      return($coddivisa);
   }

   public function get_pago()
   {
      $resultado = $this->bd->select("SELECT codpago FROM empresa;");
      $codpago = $resultado[0]['codpago'];

      return($codpago);
   }

   public function get()
   {
      $empresa = false;

      $resultado = $this->bd->select("SELECT * FROM empresa;");
      if($resultado)
      {
         $empresa = $resultado[0];
      }

      return($empresa);
   }

   public function insert($empresa)
   {
      $retorno = "OK";

      /// comprobamos la integridad de los datos
      if(!is_numeric($empresa['telefono']))
         $retorno = "Teléfono debe ser numérico!";
      if($retorno == "OK" AND !is_numeric($empresa['fax']))
         $retorno = "Fax debe ser numérico!";
      if($retorno == "OK" AND !is_numeric($empresa['codpostal']))
         $retorno = "CP debe ser numérico!";
      if($retorno == "OK" AND !is_numeric($empresa['apartado']))
         $retorno = "Apartado debe ser numérico!";

      if($retorno == "OK")
      {
         $consulta = "INSERT INTO empresa (nombre,cifnif,administrador,telefono,fax,email,direccion,codpostal,ciudad,provincia,apartado) ";
         $consulta .= "VALUES ('$empresa[nombre]','$empresa[cifnif]','$empresa[administrador]','$empresa[telefono]','$empresa[fax]','$empresa[email]',";
         $consulta .= "'$empresa[direccion]','$empresa[codpostal]','$empresa[ciudad]','$empresa[provincia]','$empresa[apartado]')";
         $resultado = $this->bd->exec($consulta);
         if(!$resultado)
         {
            $retorno = "Error al insertar los datos de la empresa.";
         }
      }

      return($retorno);
   }

   public function update($empresa)
   {
      $retorno = "OK";

      /// comprobamos la integridad de los datos
      if(!is_numeric($empresa['telefono']))
         $retorno = "Teléfono debe ser numérico!";
      if($retorno == "OK" AND !is_numeric($empresa['fax']))
         $retorno = "Fax debe ser numérico!";
      if($retorno == "OK" AND !is_numeric($empresa['codpostal']))
         $retorno = "CP debe ser numérico!";
      if($retorno == "OK" AND !is_numeric($empresa['apartado']))
         $retorno = "Apartado debe ser numérico!";

      if($retorno == "OK")
      {
         /// generamos la sentencia sql
         $consulta = "UPDATE empresa SET nombre = '$empresa[nombre]'";
         $consulta .= ", cifnif = '$empresa[cifnif]'";
         $consulta .= ", administrador = '$empresa[administrador]'";
         $consulta .= ", telefono = $empresa[telefono]";
         $consulta .= ", fax = $empresa[fax]";
         $consulta .= ", email = '$empresa[email]'";
         $consulta .= ", direccion = '$empresa[direccion]'";
         $consulta .= ", codpostal = '$empresa[codpostal]'";
         $consulta .= ", ciudad = '$empresa[ciudad]'";
         $consulta .= ", provincia = '$empresa[provincia]'";
         $consulta .= ", apartado = '$empresa[apartado]'";
         $consulta .= " WHERE id = '$empresa[id]';";

         $resultado = $this->bd->exec($consulta);
         if(!$resultado)
         {
            $retorno = "Error al ejecutar la sentencia: " . $consulta;
         }
      }

      return($retorno);
   }
}

?>
