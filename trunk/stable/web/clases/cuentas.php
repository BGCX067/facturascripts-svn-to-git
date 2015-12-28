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

require_once("clases/opciones.php");

class cuentas
{
   private $bd;
   private $opciones;

   public function __construct()
   {
      $this->bd = new db();
      $this->opciones = new opciones();
   }

   /// devuelve por referencia los datos de una cuenta
   public function get_cuenta($idcuenta, &$cuenta)
   {
      $retorno = false;

      if($idcuenta)
      {
         $resultado = $this->bd->select("SELECT * FROM co_cuentas WHERE idcuenta = '$idcuenta';");
         if($resultado)
         {
            $cuenta = $resultado[0];
            $retorno = true;
         }
      }

      return($retorno);
   }

   /// devuelve por referencia los datos de una subcuenta
   public function get_subcuenta($idsubcuenta, &$subcuenta)
   {
      $retorno = false;

      if($idsubcuenta)
      {
         $resultado = $this->bd->select("SELECT * FROM co_subcuentas WHERE idsubcuenta = '$idsubcuenta';");
         if($resultado)
         {
            $subcuenta = $resultado[0];
            $retorno = true;
         }
      }

      return($retorno);
   }

   /// devuelve por referencia un array con las subcuentas de una cuenta dada
   public function subcuentas($idcuenta, &$subcuentas)
   {
      $retorno = false;

      if($idcuenta)
      {
         $resultado = $this->bd->select("SELECT * FROM co_subcuentas WHERE idcuenta = '$idcuenta' ORDER BY codsubcuenta ASC;");
         if($resultado)
         {
            $subcuentas = $resultado;
            $retorno = true;
         }
      }

      return($retorno);
   }

   /// devuelve el codigo de una cuenta dada
   public function get_codigo_cuenta($idcuenta)
   {
      $retorno = false;

      if($idcuenta)
      {
         $resultado = $this->bd->select("SELECT codcuenta FROM co_cuentas WHERE idcuenta = '$idcuenta';");
         if($resultado)
            $retorno = $resultado[0]['codcuenta'];
      }

      return($retorno);
   }

   /// devuelve el codigo de una subcuenta dada
   public function get_codigo_subcuenta($idsubcuenta)
   {
      $retorno = false;

      if($idsubcuenta)
      {
         $resultado = $this->bd->select("SELECT codsubcuenta FROM co_subcuentas WHERE idsubcuenta = '$idsubcuenta';");
         if($resultado)
            $retorno = $resultado[0]['codsubcuenta'];
      }

      return($retorno);
   }

   /// devuelve un array de cuentas o subcuentas buscadas
   public function buscar($buscar, $tipo, $codejercicio)
   {
      /// Quitamos los espacios del principio y final y ponemos en mayusculas
      $buscar = strtoupper( trim($buscar) );
      
      if($buscar != "" AND $codejercicio != "")
      {
         if($tipo == "c")
         {
            $consulta = "SELECT s.idcuenta, s.codcuenta, c.codepigrafe, c.descripcion, COUNT(s.codsubcuenta) as subcuentas
               FROM co_subcuentas s LEFT JOIN co_cuentas c ON s.idcuenta = c.idcuenta
               WHERE s.codejercicio = '$codejercicio' AND (s.codcuenta ~~ '$buscar%'
                 OR upper(c.descripcion) ~~ '%".str_replace(' ', '%', $buscar)."%')
               GROUP BY s.idcuenta, s.codcuenta, c.codepigrafe, c.descripcion
               ORDER BY c.codepigrafe ASC, s.codcuenta ASC;";
         }
         else
         {
            $consulta = "SELECT * FROM co_subcuentas
               WHERE codejercicio = '$codejercicio' AND (codsubcuenta ~~ '$buscar%'
                 OR upper(descripcion) ~~ '%".str_replace(' ', '%', $buscar)."%')
               ORDER BY codcuenta ASC, codsubcuenta ASC;";
         }
         
         $resultado = $this->bd->select($consulta);
      }
      
      return($resultado);
   }

    /// devuelve por referencia un array con las cuentas del ejercicio contable dado
   public function lista_cuentas($codejercicio, &$cuentas)
   {
      $retorno = false;
      
      if($codejercicio != "")
      {
         $resultado = $this->bd->select("SELECT s.idcuenta, s.codcuenta, c.codepigrafe, c.descripcion, COUNT(s.codsubcuenta) as subcuentas
            FROM co_subcuentas s LEFT JOIN co_cuentas c ON s.idcuenta = c.idcuenta
            WHERE s.codejercicio = '$codejercicio'
            GROUP BY s.idcuenta, s.codcuenta, c.codepigrafe, c.descripcion
            ORDER BY c.codepigrafe ASC, s.codcuenta ASC;");

         if($resultado)
         {
            $cuentas = $resultado;
            $retorno = true;
         }
      }

      return($retorno);
   }

   public function repara_subcuenta($subcuenta, &$error)
   {
      $retorno = true;
      $error = false;
      $cuenta = false;

      if( $this->get_cuenta($subcuenta['idcuenta'], $cuenta) )
      {
         if( !$this->bd->exec("UPDATE co_subcuentas SET codcuenta = '" . $cuenta['codcuenta'] . "' WHERE idsubcuenta = '" . $subcuenta['idsubcuenta'] . "';") )
         {
            $retorno = false;
            $error = "Error al actualizar la subcuenta";
         }
      }
      else
      {
         $retorno = false;
         $error = "Cuenta no encontrada";
      }

      return($retorno);
   }

   /// devuelve un array con cuentas con saldo distinto de cero y que no están asociadas a ningún epígrafe
   public function errores_epigrafes()
   {
      /// obtenemos el ejercicio por defecto
      $fs_ejercicio = false;
      $this->opciones->get('ejercicio', $fs_ejercicio);

      $retorno = $this->bd->select("SELECT s.idcuenta, s.codsubcuenta, s.codcuenta, s.debe, s.haber
         FROM co_subcuentas s LEFT JOIN co_cuentascb cb on s.codcuenta like cb.codcuenta || '%'
         WHERE s.codejercicio = '$fs_ejercicio' AND (s.debe>0 OR s.haber>0) and cb.codcuenta is null
         ORDER BY s.codsubcuenta;");

      return($retorno);
   }

   /// devuelve un array con las subcuentas con errores en debe, haber o saldo
   public function errores_saldos()
   {
      $retorno = $this->bd->select("SELECT p.idsubcuenta, s.debe, SUM(p.debe), s.haber, SUM(p.haber), s.saldo, SUM(p.debe - p.haber)
         FROM co_partidas p LEFT JOIN co_subcuentas s ON p.idsubcuenta = s.idsubcuenta
         GROUP BY p.idsubcuenta, s.debe, s.haber, s.saldo
         HAVING @(s.debe - SUM(p.debe)) > 0.01
         OR @(s.haber - SUM(p.haber)) > 0.01
         OR @(s.saldo - SUM(p.debe - p.haber)) > 0.01;");

      return($retorno);
   }

   /// devuelve un array con las subcuentas que apuntan a cuentas que no existen
   public function errores_subcuentas()
   {
      $retorno = $this->bd->select("select * from co_subcuentas
         where codcuenta not in (select codcuenta from co_cuentas)
         order by codejercicio ASC, codcuenta ASC, codsubcuenta ASC;");

      return($retorno);
   }
}

?>
