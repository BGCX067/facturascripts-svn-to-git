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

class proveedores
{
   private $bd;

   public function __construct()
   {
      $this->bd = new db();
   }

   /// devuelve los datos de un proveedor dado
   public function get($codproveedor)
   {
      $proveedor = false;

      if($codproveedor)
      {
         $resultado = $this->bd->select("SELECT * FROM proveedores WHERE codproveedor = '$codproveedor';");
         if($resultado)
            $proveedor = $resultado[0];
      }

      return($proveedor);
   }

   /// devuelve el nombre de un proveedor a partir de su codigo
   public function get_nombre($codproveedor)
   {
      $nombre = false;

      if($codproveedor)
      {
         $resultado = $this->bd->select("SELECT nombre FROM proveedores WHERE codproveedor = '$codproveedor';");
         if($resultado)
            $nombre = $resultado[0]['nombre'];
      }

      return($nombre);
   }

   /// devuelve los datos de la dirección de un proveedor dado
   public function get_direcciones($codproveedor)
   {
      $proveedor = false;

      if($codproveedor)
      {
         $resultado = $this->bd->select("SELECT p.nombre as pais, d.ciudad, d.codpostal, d.direccion
            FROM dirproveedores d, paises p
            WHERE codproveedor = '$codproveedor' AND d.codpais = p.codpais;");
         if($resultado)
            $proveedor = $resultado;
      }

      return($proveedor);
   }

   /// devuelve una cadena con la direccion ya lista para buscar en google maps
   public function get_direcciones_map($codproveedor)
   {
      $direcciones = Array();

      if($codproveedor)
      {
         $resultado = $this->bd->select("SELECT p.nombre as pais, d.ciudad, d.codpostal, d.direccion
            FROM dirproveedores d, paises p
            WHERE codproveedor = '$codproveedor' AND d.codpais = p.codpais;");

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

   /// devuelve por referencia un array con todos los proveedores
   public function all(&$proveedores)
   {
      $retorno = false;

      $resultado = $this->bd->select("SELECT * FROM proveedores ORDER BY nombre ASC;");
      if($resultado)
      {
         $proveedores = $resultado;
         $retorno = true;
      }

      return($retorno);
   }

   /// devuelve por referencia un array con proveedores
   public function listar($limite, $pagina, &$total)
   {
      $proveedores = false;

      if( empty($limite) )
         $limite = FS_LIMITE;

      if( empty($pagina) )
         $pagina = 0;

      $consulta = "SELECT * FROM proveedores ORDER BY nombre ASC";

      if( empty($total) )
         $total = $this->bd->num_rows($consulta);

      $proveedores = $this->bd->select_limit($consulta,$limite,$pagina);

      return($proveedores);
   }

   /// devuelve los un array de proveedores con los resultados de la busqueda
   public function buscar($buscar, $limite, $pagina, &$total)
   {
      $proveedores = false;
      
      $buscar = strtoupper( str_replace(' ', '%', trim($buscar)) );
      
      if( empty($limite) )
         $limite = FS_LIMITE;
      
      if( empty($pagina) )
         $pagina = 0;
      
      if($buscar)
      {
         $consulta = "SELECT * FROM proveedores
            WHERE codproveedor ~~ '%$buscar%' OR upper(nombre) ~~ '%$buscar%'
            ORDER BY nombre ASC";
         $proveedores = $this->bd->select_limit($consulta, $limite, $pagina);
         if( empty($total) )
            $total = $this->bd->num_rows($consulta);
      }
      
      return $proveedores;
   }

   /// devuelve un array con el numero de albaranes de cada proveedor
   public function albaranes()
   {
      $albaranes = array();
      $proveedores = $this->bd->select("SELECT * FROM proveedores ORDER BY codproveedor ASC;");
      $albaranesprov = $this->bd->select("SELECT codproveedor, COUNT(idalbaran) as albaranesprov
                                          FROM albaranesprov GROUP BY codproveedor;");
      if( $proveedores )
      {
         foreach($proveedores as $p)
            $albaranes[ $p['codproveedor'] ] = 0;
         
         if($albaranesprov)
         {
            foreach($albaranesprov as $albaranprov)
               $albaranes[ $albaranprov['codproveedor'] ] = $albaranprov['albaranesprov'];
         }
      }
      
      return $albaranes;
   }
   
   /// devuelve un array con el numero de facturas de cada proveedor
   public function facturas()
   {
      $facturas = array();
      $proveedores = $this->bd->select("SELECT * FROM proveedores ORDER BY codproveedor ASC;");
      $facturasprov = $this->bd->select("SELECT codproveedor, COUNT(idfactura) as facturasprov
                                          FROM facturasprov GROUP BY codproveedor;");
      if( $proveedores )
      {
         foreach($proveedores as $p)
            $facturas[ $p['codproveedor'] ] = 0;
         
         if($facturasprov)
         {
            foreach($facturasprov as $f)
               $facturas[ $f['codproveedor'] ] = $f['facturasprov'];
         }
      }
      
      return $facturas;
   }
}

?>
