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

class facturas_prov
{
   private $bd;

   public function __construct()
   {
      $this->bd = new db();
   }

   /// devuelve los datos de una factura de proveedor
   function get($idfactura, &$factura)
   {
      $retorno = false;

      if($idfactura)
      {
         $resultado = $this->bd->select("SELECT * FROM facturasprov WHERE idfactura = '$idfactura';");
         if($resultado)
         {
            $factura = $resultado[0];
            $retorno = true;
         }
      }

      return($retorno);
   }

   /// devuelve el codigo de factura de proveedor
   public function get_codigo($idfactura)
   {
      $codigo = false;

      if($idfactura)
      {
         $resultado = $this->bd->select("SELECT codigo FROM facturasprov WHERE idfactura='$idfactura';");
         if($resultado)
            $codigo = $resultado[0]['codigo'];
      }

      return($codigo);
   }

   /// devuelve el idfactura para un codigo dado
   public function get_id($codigo)
   {
      $id = false;

      if($codigo)
      {
         $resultado = $this->bd->select("SELECT idfactura FROM facturasprov WHERE codigo='$codigo';");
         if($resultado)
            $id = $resultado[0]['idfactura'];
      }

      return($id);
   }

   /// devuelve la factura para un codigo dado
   public function get_by_id($codigo, &$factura)
   {
      $retorno = false;

      if($codigo)
      {
         $resultado = $this->bd->select("SELECT * FROM facturasprov WHERE codigo='$codigo';");
         if($resultado)
         {
            $factura = $resultado[0];
            $retorno = true;
         }
      }

      return($retorno);
   }

   /// devuleve en un array las lineas de una factura de proveedor determinado
   function get_lineas($idfactura, &$lineas)
   {
      $retorno = false;

      if($idfactura)
      {
         $consulta = "SELECT f.idalbaran, a.codigo, referencia, descripcion, pvptotal
            FROM lineasfacturasprov f LEFT JOIN albaranesprov a ON f.idalbaran = a.idalbaran
            WHERE f.idfactura = '$idfactura'
            ORDER BY a.codigo ASC, referencia ASC;";

         $resultado = $this->bd->select($consulta);
         if($resultado)
         {
            $lineas = $resultado;
            $retorno = true;
         }
      }

      return($retorno);
   }

   /// devuelve en un array las lineas de iva asociadas a una factura de dada
   public function get_lineasiva($idfactura, &$lineas)
   {
      $retorno = false;

      if($idfactura)
      {
         $resultado = $this->bd->select("SELECT * FROM lineasivafactprov WHERE idfactura = '$idfactura' ORDER BY idlinea ASC;");
         if($resultado)
         {
            $lineas = $resultado;
            $retorno = true;
         }
      }

      return($retorno);
   }

   /// devuelve todos los asientos vinculados a una factura
   public function get_asientos($codigo)
   {
      return $this->bd->select("SELECT * FROM co_asientos
         WHERE documento = '$codigo' AND tipodocumento = 'Factura de proveedor'
         ORDER BY codejercicio ASC, numero ASC");
   }

   /// devuelve todos los codigos de facturas que contengas errores en los asientos
   public function get_badasientos()
   {
      return $this->bd->select("SELECT f.idfactura, f.codigo, f.idasiento, f.numero, p.factura
         FROM facturasprov f, co_partidas p
         WHERE f.idasiento = p.idasiento AND p.factura::TEXT <> f.numero
         ORDER BY f.idfactura ASC;");
   }

   /// devuelve un array con la información de las facturas con algún error en la suma de las lineas
   public function errores_lineas()
   {
      $retorno = Array();
      $i = 0;

      $facturas = $this->bd->select("SELECT a.idfactura, a.codigo, a.neto, SUM(l.pvptotal) as t_neto
         FROM facturasprov a LEFT JOIN lineasfacturasprov l ON a.idfactura = l.idfactura
         GROUP BY a.idfactura, a.codigo, a.neto;");

      if($facturas)
      {
         foreach($facturas as $col)
         {
            if(($col['t_neto'] == '' AND $col['neto'] != '0') OR ($col['t_neto'] != '' AND abs(round($col['t_neto'], 2) - round($col['neto'], 2)) > 0.02))
            {
               $retorno[$i] = $col;
               $i++;
            }
         }
      }

      return($retorno);
   }

   /// devuelve un array con la información de las facturas con algún error en la linea de iva
   public function errores_lineasiva()
   {
      $retorno = Array();
      $i = 0;
      $siguiente = 0;

      $facturas = $this->bd->select_limit("SELECT a.idfactura, a.codigo, a.neto, SUM(l.neto) as l_neto, a.totaliva, SUM(l.totaliva) as l_iva,
         a.total, SUM(l.totallinea) as totallinea FROM facturasprov a LEFT JOIN lineasivafactprov l ON a.idfactura = l.idfactura
         GROUP BY a.idfactura, a.codigo, a.neto, a.totaliva, a.total", 1000, $siguiente);

      while($facturas)
      {
         echo ".";
         
         foreach($facturas as $col)
         {
            if(($col['l_neto'] == '' AND $col['neto'] != '0') OR ($col['l_neto'] != '' AND abs(round($col['l_neto'], 2) - round($col['neto'], 2)) > 0.02))
            {
               $retorno[$i] = $col;
               $i++;
            }
            else if(($col['l_iva'] == '' AND $col['totaliva'] != '0') OR ($col['l_iva'] != '' AND abs(round($col['l_iva'], 2) - round($col['totaliva'], 2)) > 0.02))
            {
               $retorno[$i] = $col;
               $i++;
            }
            else if(($col['totallinea']=='' AND $col['total']!='0') OR ($col['totallinea']!='' AND abs(round($col['totallinea'], 2)-round($col['total'], 2)) > 0.02))
            {
               $retorno[$i] = $col;
               $i++;
            }
         }

         $siguiente += 1000;
         $facturas = $this->bd->select_limit("SELECT a.idfactura, a.codigo, a.neto, SUM(l.neto) as l_neto, a.totaliva, SUM(l.totaliva) as l_iva,
            a.total, SUM(l.totallinea) as totallinea FROM facturasprov a LEFT JOIN lineasivafactprov l ON a.idfactura = l.idfactura
            GROUP BY a.idfactura, a.codigo, a.neto, a.totaliva, a.total", 1000, $siguiente);
      }

      return($retorno);
   }

   /// devuelve un array de facturas buscadas
   public function buscar($buscar, $tipo, $limite, &$pagina, &$total)
   {
      $resultado = FALSE;

      /// quitamos los espacios del principio y final
      $buscar = strtoupper( trim($buscar) );

      if($pagina == '')
         $pagina = 0;
      
      if($limite == '')
         $limite = FS_LIMITE;

      if($buscar AND $tipo)
      {
         switch($tipo)
         {
            default:
               $consulta = "SELECT * FROM facturasprov WHERE ";
               if( is_numeric($buscar) )
               {
                  $consulta .= "codigo ~~ '%$buscar%' OR observaciones ~~ '%$buscar%'".
                     " OR total BETWEEN '".($buscar-.01)."' AND '".($buscar+.01)."'";
               }
               else if( preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})$/i', $buscar) ) /// es una fecha
                  $consulta .= "fecha = '$buscar' OR observaciones ~~ '%$buscar%'";
               else
                  $consulta .= "upper(codigo) ~~ '%$buscar%' OR upper(observaciones) ~~ '%".str_replace(' ', '%', $buscar)."%'";
               $consulta .= " ORDER BY fecha DESC, codigo DESC";
               break;

            case "cop":   /// codigo de proveedor
               $consulta = "SELECT * FROM facturasprov
                  WHERE codproveedor = '$buscar'
                  ORDER BY nombre ASC, fecha DESC";
               break;

            case "xre":   /// referencia exacta en las lineas
               $consulta = "SELECT a.idfactura,l.referencia,a.codigo,l.pvpunitario,l.cantidad,l.pvptotal,a.fecha,a.observaciones
                  FROM lineasfacturasprov l, facturasprov a
                  WHERE referencia = '$buscar' AND l.idfactura = a.idfactura
                  ORDER BY referencia ASC";
               break;
         }

         if($total == '')
            $total = $this->bd->num_rows($consulta);

         $resultado = $this->bd->select_limit($consulta, $limite, $pagina);
      }

      return($resultado);
   }

   public function ultimas($num, &$facturas, &$total, &$desde)
   {
      $retorno = FALSE;

      if($num == '')
         $num = FS_LIMITE;
      
      if($desde == '')
         $desde = 0;

      if($total == '')
      {
         $resultado = $this->bd->select("SELECT count(*) as total FROM facturasprov;");
         $total = $resultado[0]['total'];
      }

      $resultado = $this->bd->select_limit("SELECT * FROM facturasprov ORDER BY fecha DESC, codigo DESC", $num, $desde);

      if($resultado)
      {
         $facturas = $resultado;
         $retorno = true;
      }

      return($retorno);
   }
}
?>
