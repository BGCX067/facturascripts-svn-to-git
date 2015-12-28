<?php
/*
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA.
 * 
 * Autor: Carlos Garcia Gomez
*/

class asientos
{
   private $bd;

   public function __construct()
   {
      $this->bd = new db();
   }

   /// devuelve los datos de una subcuenta
   public function get($idasiento, &$asiento)
   {
      $retorno = false;

      if($idasiento != '')
      {
         $resultado = $this->bd->select("SELECT * FROM co_asientos WHERE idasiento = '" . $idasiento . "';");
         if($resultado)
         {
            $asiento = $resultado[0];
            $retorno = true;
         }
         else
            $asiento = false;
      }

      return($retorno);
   }

   /// devuelve el numero de un asiento dado
   public function get_numero($idasiento)
   {
      $retorno = false;

      if($idasiento != '')
      {
         $resultado = $this->bd->select("SELECT numero FROM co_asientos WHERE idasiento = '" . $idasiento . "';");
         if($resultado)
            $retorno = $resultado[0]['numero'];
      }

      return($retorno);
   }

   /// devuelve en un array todas las partidas de un asiento
   public function get_partidas($idasiento, &$partidas)
   {
      $retorno = false;

      if($idasiento != '')
      {
         $resultado = $this->bd->select("SELECT * FROM co_partidas WHERE idasiento = '" . $idasiento . "' ORDER BY codsubcuenta ASC;");
         if($resultado)
         {
            $partidas = $resultado;
            $retorno = true;
         }
         else
            $partidas = false;
      }

      return($retorno);
   }

    /// devuelve un array con los $num ultimos asientos
   public function ultimos($num, &$asientos, &$total, &$desde)
   {
      $retorno = false;
      
      if($num == '')
         $num = FS_LIMITE;
      
      if($desde == '')
         $desde = 0;

      if($total == '')
      {
         $resultado = $this->bd->select("SELECT count(*) as total FROM co_partidas p, co_asientos a WHERE a.idasiento = p.idasiento;");
         $total = $resultado[0]['total'];
      }

      $resultado = $this->bd->select_limit('SELECT a.idasiento, a.numero, a.fecha, a.tipodocumento, p.idsubcuenta, p.codsubcuenta, p.concepto, p.debe, p.haber
         FROM co_partidas p, co_asientos a WHERE a.idasiento = p.idasiento
         ORDER BY a.fecha DESC, a.numero DESC, a.codejercicio DESC', $num, $desde);

      if($resultado)
      {
         $asientos = $resultado;
         $retorno = true;
      }

      return($retorno);
   }

   /// devuelve un array de asientos descuadrados
   public function descuadrados(&$asientos)
   {
      $retorno = false;

      $consulta = 'SELECT p.idasiento,a.numero,SUM(p.debe) as sdebe,SUM(p.haber) as shaber FROM co_partidas p, co_asientos a
         WHERE p.idasiento = a.idasiento GROUP BY p.idasiento,a.numero HAVING (SUM(p.haber) - SUM(p.debe) > 0.01)
         ORDER BY p.idasiento ASC;';

      $resultado = $this->bd->select($consulta);
      if($resultado)
      {
         $asientos = $resultado;
         $retorno = true;
      }

      return($asientos);
   }

   /// devuelve por referencia los datos de las partidas relacionadas con una subcuenta dada
   public function get_by_subcta($idsubcuenta, &$partidas, $limite, &$pagina, &$total)
   {
      $retorno = false;

      if($pagina == '')
         $pagina = 0;

      if($limite == '')
         $limite = FS_LIMITE;

      if($idsubcuenta != '')
      {
         $consulta = "SELECT a.idasiento,a.numero,a.fecha,p.idpartida,p.concepto,p.debe,p.haber,p.punteada FROM co_partidas p, co_asientos a
            WHERE p.idsubcuenta = '$idsubcuenta' AND a.idasiento = p.idasiento ORDER BY a.fecha, a.numero ASC";

         if($total == '')
            $total = $this->bd->num_rows($consulta);

         $resultado = $this->bd->select_limit($consulta, $limite, $pagina);
         if($resultado)
         {
            $partidas = $resultado;
            $retorno = true;
         }
      }

      return($retorno);
   }

   /// devuelve un array de partidas buscadas
   public function buscar($buscar, $limite, &$pagina, &$total)
   {
      $resultado = FALSE;
      
      /// quitamos los espacios del principio y final, y ponemos en mayusculas
      $buscar = strtoupper( trim($buscar) );
      
      if($pagina == '')
         $pagina = 0;
      
      if($limite == '')
         $limite = FS_LIMITE;
      
      if($buscar != '')
      {
         $consulta = "SELECT a.idasiento, a.numero, a.fecha, a.tipodocumento, p.idsubcuenta, p.codsubcuenta, p.concepto, p.debe, p.haber
            FROM co_asientos a, co_partidas p
            WHERE a.idasiento = p.idasiento";
         
         if( is_numeric($buscar) )
         {
            $consulta .= " AND (a.numero::TEXT ~~ '%$buscar%' OR p.debe BETWEEN ".($buscar-.01)." AND ".($buscar+.01)."
               OR p.haber BETWEEN ".($buscar-.01)." AND ".($buscar+.01).")";
         }
         else if( preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})$/i', $buscar) ) /// es una fecha
            $consulta .= " AND a.fecha = '$buscar'";
         else
            $consulta .= " AND upper(p.concepto) ~~ '%".str_replace(' ', '%', $buscar)."%'";
         
         $consulta .= " ORDER BY fecha DESC, numero DESC";

         if($total == '')
            $total = $this->bd->num_rows($consulta);

         $resultado = $this->bd->select_limit($consulta, $limite, $pagina);
      }

      return($resultado);
   }

   public function puntear($idsubcuenta, &$partidas, &$error)
   {
      $retorno = true;

      if($idsubcuenta == "" OR count($partidas) < 1)
      {
         $error = "Datos inv&aacute;lidos";
         $retorno = false;
      }

      if($retorno)
      {
         $consulta = "";

         foreach($partidas as $partida)
         {
            if($partida['puntear'])
               $consulta .= "UPDATE co_partidas SET punteada = true WHERE idpartida = '$partida[idpartida]';";
            else
               $consulta .= "UPDATE co_partidas SET punteada = false WHERE idpartida = '$partida[idpartida]';";
         }

         if(!$this->bd->exec($consulta))
         {
            $error = "Error al ejecutar la consulta: " . $consulta;
            $retorno = false;
         }
      }

      return($retorno);
   }
}
?>
