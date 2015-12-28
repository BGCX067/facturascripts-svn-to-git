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

class clientes
{
   private $bd;

   public function __construct()
   {
      $this->bd = new db();
   }

   /// devuelve los datos de un cliente dado
   public function get($codcliente)
   {
      $cliente = false;

      if($codcliente)
      {
         $resultado = $this->bd->select("SELECT * FROM clientes WHERE codcliente = '$codcliente';");
         if($resultado)
            $cliente = $resultado[0];
      }

      return($cliente);
   }

   /// devuelve el nombre de un cliente a partir de su codigo
   public function get_nombre($codcliente)
   {
      $cliente = false;

      if($codcliente)
      {
         $resultado = $this->bd->select("SELECT nombre FROM clientes WHERE codcliente = '$codcliente';");
         if($resultado)
            $cliente = $resultado[0]['nombre'];
      }

      return($cliente);
   }

   /// devuelve los datos de la dirección de un cliente dado
   public function get_direccion($codcliente)
   {
      $cliente = false;

      if($codcliente)
      {
         $resultado = $this->bd->select("SELECT * FROM dirclientes WHERE codcliente = '$codcliente';");
         if($resultado)
            $cliente = $resultado[0];
      }

      return($cliente);
   }

   /// devuelve los datos de la dirección de un cliente dado
   public function get_direcciones($codcliente)
   {
      $cliente = false;

      if($codcliente)
      {
         $resultado = $this->bd->select("SELECT p.nombre as pais, d.ciudad, d.codpostal, d.direccion
            FROM dirclientes d, paises p
            WHERE codcliente = '$codcliente' AND d.codpais = p.codpais;");
         if($resultado)
            $cliente = $resultado;
      }

      return($cliente);
   }

   /// devuelve una cadena con la direccion ya lista para buscar en google maps
   public function get_direcciones_map($codcliente)
   {
      $direcciones = Array();

      if($codcliente)
      {
         $resultado = $this->bd->select("SELECT p.nombre as pais, d.ciudad, d.codpostal, d.direccion
            FROM dirclientes d, paises p
            WHERE codcliente = '$codcliente' AND d.codpais = p.codpais;");

         $i = 0;
         if($resultado)
         {
            foreach($resultado as $col)
            {
               $direcciones[$i] = $col['pais'] . ", " . $col['ciudad'] . ", " . $col['codpostal'] . ", " . $col['direccion'];
               $i++;
            }
         }
      }

      return($direcciones);
   }

   /// devuelve por referencia un array con todos los clientes
   public function all(&$clientes)
   {
      $retorno = false;

      $resultado = $this->bd->select("SELECT * FROM clientes ORDER BY nombre ASC");
      if($resultado)
      {
         $clientes = $resultado;
         $retorno = true;
      }

      return($retorno);
   }

   /// devuelve por referencia un array con clientes
   public function listar($limite,$pagina,&$total)
   {
      $clientes = false;

      if( empty($limite) )
         $limite = FS_LIMITE;

      if( empty($pagina) )
         $pagina = 0;

      $consulta = "SELECT * FROM clientes ORDER BY nombre ASC";

      if( empty($total) )
         $total = $this->bd->num_rows($consulta);

      $clientes = $this->bd->select_limit($consulta, $limite, $pagina);

      return($clientes);
   }

   /// devuelve los un array de clientes con los resultados de la busqueda
   public function buscar($buscar, $limite, $pagina, &$total)
   {
      $clientes = false;
      
      $buscar = str_replace(' ', '%', strtoupper( trim($buscar) ));
      
      if( empty($limite) )
         $limite = FS_LIMITE;
      
      if( empty($pagina) )
         $pagina = 0;
      
      if($buscar)
      {
         $consulta = "SELECT * FROM clientes
            WHERE codcliente ~~ '%$buscar%' OR upper(nombre) ~~ '%$buscar%'
            ORDER BY nombre ASC";
         $clientes = $this->bd->select_limit($consulta, $limite, $pagina);
         if( empty($total) )
            $total = $this->bd->num_rows($consulta);
      }
      
      return $clientes;
   }

   /// devuelve un array con el numero de reservas y albaranes de cada cliente
   public function stats()
   {
      $stats = array();
      $clientes = $this->bd->select("SELECT * FROM clientes ORDER BY codcliente ASC;");
      $albaranescli = $this->bd->select("SELECT codcliente, COUNT(idalbaran) as total FROM albaranescli GROUP BY codcliente;");
      $facturascli = $this->bd->select("SELECT codcliente, COUNT(idfactura) as total FROM facturascli GROUP BY codcliente;");
      
      if( $clientes )
      {
         foreach($clientes as $cli)
         {
            $stats[ $cli['codcliente'] ] = array(
                'albaranes' => 0,
                'facturas' => 0
            );
         }
      }
      
      if($albaranescli)
      {
         foreach($albaranescli as $albarancli)
            $stats[$albarancli['codcliente']]['albaranes'] = $albarancli['total'];
      }
      
      if($facturascli)
      {
         foreach($facturascli as $facturacli)
            $stats[$facturacli['codcliente']]['facturas'] = $facturacli['total'];
      }
      
      return $stats;
   }
}

?>
