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

class agentes
{
   private $bd;

   public function __construct()
   {
      $this->bd = new db();
   }

   /// devuelve un array con los datos de cada agente ($array[codagente])
   public function all(&$agentes)
   {
      $retorno = false;

      $resultado = $this->bd->select("SELECT * FROM agentes ORDER BY nombre ASC;");
      if($resultado)
      {
         foreach($resultado as $col)
            $agentes[$col['codagente']] = $col;

         $retorno = true;
      }

      return($retorno);
   }

   /// devuelve por referencia los datos de un agente dado
   public function get($codagente,&$agente)
   {
      $retorno = false;

      if($codagente)
      {
         $resultado = $this->bd->select("SELECT * FROM agentes WHERE codagente = '$codagente';");
         if($resultado)
         {
            $agente = $resultado[0];
            $retorno = true;
         }
      }

      return($retorno);
   }

   /// devuelve nombre + apellidos de un agente (o "ninguno" si el agente no existe)
   public function get_nombre($codagente)
   {
      $nombre = false;

      if($codagente)
      {
         $resultado = $this->bd->select("SELECT nombre,apellidos FROM agentes WHERE codagente = '$codagente';");
         if($resultado)
            $nombre = $resultado[0]['nombre'] . " " . $resultado[0]['apellidos'];
         else
            $nombre = "ninguno";
      }
      else
         $nombre = "ninguno";

      return($nombre);
   }

   /// devuleve un array con el numero de reservas y albaranes (clientes y proveedores) de cada agente
   public function stats()
   {
      $stats = array();
      $agentes = $this->bd->select("SELECT * FROM agentes ORDER BY codagente ASC;");
      $reservas = $this->bd->select("SELECT codagente, COUNT(id) as reservas FROM fs_reservas GROUP BY codagente;");
      $albaranescli = $this->bd->select("SELECT codagente, COUNT(idalbaran) as albaranescli FROM albaranescli GROUP BY codagente;");
      $albaranesprov = $this->bd->select("SELECT codagente, COUNT(idalbaran) as albaranesprov FROM albaranesprov GROUP BY codagente;");
      
      if( $agentes )
      {
         foreach($agentes as $a)
         {
            $stats[ $a['codagente'] ]['reservas'] = 0;
            $stats[ $a['codagente'] ]['albaranescli'] = 0;
            $stats[ $a['codagente'] ]['albaranesprov'] = 0;
         }
         
         if($reservas)
         {
            foreach($reservas as $reserva)
               $stats[ $reserva['codagente'] ]['reservas'] = $reserva['reservas'];
         }
         
         if($albaranescli)
         {
            foreach($albaranescli as $albarancli)
               $stats[ $albarancli['codagente'] ]['albaranescli'] = $albarancli['albaranescli'];
         }
         
         if($albaranesprov)
         {
            foreach($albaranesprov as $albaranprov)
               $stats[ $albaranprov['codagente'] ]['albaranesprov'] = $albaranprov['albaranesprov'];
         }
      }
      
      return $stats;
   }
}

?>
