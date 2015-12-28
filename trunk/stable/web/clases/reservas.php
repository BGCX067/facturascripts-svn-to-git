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

class reservas
{
   private $bd;

   public function __construct()
   {
      $this->bd = new db();
   }

   /*
    * Devuelve true si encuentra una coincidencia del id en la tabla fs_reservas, false en caso contrario.
    * Devuelve por referencia los datos de la reserva (si se ha encontrado).
    */
   public function get($id, &$reserva)
   {
      $retorno = false;

      if($id)
      {
         $resultado = $this->bd->select("SELECT * FROM fs_reservas WHERE id = '$id';");

         if($resultado)
         {
            $reserva = $resultado[0];
            $retorno = true;
         }
      }

      return($retorno);
   }

   /*
    * Devuelve por referencia todas las reservas en grupos de $limite y a partir de $pagina
    */
   public function all($limite, $pagina, &$total, &$reservas)
   {
      $retorno = false;

      if($limite == '')
      {
         $limite = FS_LIMITE;
      }

      if($pagina == '')
      {
         $pagina = 0;
      }

      $consulta = "SELECT r.id, r.codcliente, c.nombrecomercial, r.datos_extra, r.fecha, r.codagente, a.nombre, a.apellidos, r.referencia, r.cantidad,
         r.entregado, r.entrega, r.observaciones FROM fs_reservas r, clientes c, agentes a
         WHERE r.codcliente = c.codcliente AND r.codagente = a.codagente
         ORDER BY r.id DESC";

      if($total == '')
      {
         $total = $this->bd->num_rows($consulta);
      }

      $reservas = $this->bd->select_limit($consulta, $limite, $pagina);
      if($reservas)
      {
         $retorno = true;
      }

      return($retorno);
   }

   /*
    * Devuelve por referencia todas las reservas pendientes en grupos de $limite y a partir de $pagina
    */
   public function pendientes($limite, $pagina, &$total, &$reservas)
   {
      $retorno = false;

      if($limite == '')
      {
         $limite = FS_LIMITE;
      }

      if($pagina == '')
      {
         $pagina = 0;
      }

      $consulta = "SELECT r.id, r.codcliente, c.nombrecomercial, r.datos_extra, r.fecha, r.codagente, a.nombre, a.apellidos, r.referencia, r.cantidad,
         r.entregado, r.entrega, r.observaciones FROM fs_reservas r, clientes c, agentes a
         WHERE r.codcliente = c.codcliente AND r.codagente = a.codagente AND entregado = false
         ORDER BY r.entrega DESC, r.id ASC";

      if($total == '')
      {
         $total = $this->bd->num_rows($consulta);
      }

      $reservas = $this->bd->select_limit($consulta, $limite, $pagina);
      if($reservas)
      {
         $retorno = true;
      }

      return($retorno);
   }

   /*
    * Añade una reserva a la base de datos.
    * Devuelve true si se ha realizado con exito, false si falla o hay errores en los parametros.
    */
   public function add($cliente, $extra, $agente, $referencia, $cantidad, $pvp, $dto, $fecha, $entrega, $observaciones, &$id, &$error)
   {
      $retorno = false;

      if($cliente != '' AND $agente != '' AND $referencia != '' AND $cantidad > 0 AND $pvp != '' AND $dto != '' AND $fecha != '' AND $entrega != '')
      {
         $resultado = $this->bd->select("INSERT INTO fs_reservas
            (codcliente, datos_extra, codagente, referencia, cantidad, pvp, dtopor, fecha, entrega, observaciones)
            VALUES ('$cliente', '$extra', '$agente', '$referencia', '$cantidad', '$pvp', '$dto', '$fecha', '$entrega', '$observaciones')
            RETURNING id;");

         if($resultado)
         {
            $id = $resultado[0]['id'];
            $retorno = true;
         }
         else
         {
            $error = "Error al ejecutar la consulta";
         }
      }
      else
      {
         $error = "Datos incorrectos";
      }

      return($retorno);
   }

   /*
    * Actualiza los datos de una reserva.
    * Devuelve true si todo es correcto, false en caso de error en la transacción o en los parametros.
    */
   public function update($id, $extra, $entregado, $entrega, $observaciones, &$error)
   {
      $retorno = false;

      if($id AND is_bool($entregado) AND $entrega != '')
      {
         if($entregado) { $entregado = "true"; }
         else { $entregado = "false"; }

         $retorno = $this->bd->exec("UPDATE fs_reservas SET datos_extra = '$extra', entregado = '$entregado', entrega = '$entrega',
                                       observaciones = '$observaciones' WHERE id = '$id';");
      }
      else
      {
         $error = "Faltan argumentos";
      }

      return($retorno);
   }

   /// Dado un id elimina la reserva correspondiente.
   public function delete($id)
   {
      $retorno = false;

      if($id)
      {
         $retorno = $this->bd->exec("DELETE FROM fs_reservas WHERE id = '$id';");
      }

      return($retorno);
   }

   public function search($buscar, $tipo, $pendiente, $limite, $pagina, &$total, &$reservas)
   {
      $retorno = false;
      $datos_abuscar = "r.id, r.codcliente, c.nombrecomercial, r.datos_extra, r.fecha, r.codagente, a.nombre, a.apellidos, r.referencia, r.cantidad,
         r.entregado, r.entrega, r.observaciones";

      if($limite == '')
      {
         $limite = FS_LIMITE;
      }

      if($pagina == '')
      {
         $pagina = 0;
      }

      /// construimos la consulta sql
      switch($tipo)
      {
         default:
            $consulta = "SELECT $datos_abuscar FROM fs_reservas r, clientes c, agentes a
               WHERE r.codcliente = c.codcliente AND r.codagente = a.codagente
               AND (referencia ILIKE '%$buscar%' OR datos_extra ILIKE '%$buscar%' OR r.observaciones ILIKE '%$buscar%')";
            break;

         case "xre":
            $consulta = "SELECT $datos_abuscar FROM fs_reservas r, clientes c, agentes a
               WHERE r.codcliente = c.codcliente AND r.codagente = a.codagente AND referencia = '$buscar'";
            break;

         case "cli":
            $consulta = "SELECT $datos_abuscar FROM fs_reservas r, clientes c, agentes a
               WHERE r.codcliente = c.codcliente AND r.codagente = a.codagente AND r.codcliente = '$buscar'";
            break;

         case "age":
            $consulta = "SELECT $datos_abuscar FROM fs_reservas r, clientes c, agentes a
               WHERE r.codcliente = c.codcliente AND r.codagente = a.codagente AND r.codagente = '$buscar'";
            break;
      }

      if($pendiente)
      {
         $consulta .= " AND entregado = false";
      }

      $consulta .= " ORDER BY r.entrega DESC, r.id ASC";

      if($total == '')
      {
         $total = $this->bd->num_rows($consulta);
      }

      $reservas = $this->bd->select_limit($consulta, $limite, $pagina);
      if($reservas)
      {
         $retorno = true;
      }

      return($retorno);
   }
}

?>