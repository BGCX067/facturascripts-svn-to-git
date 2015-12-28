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

require_once("clases/articulos.php");
require_once("clases/ejercicios.php");
require_once("clases/proveedores.php");
require_once("clases/series.php");

class albaranes_prov
{
   private $bd;
   private $ejercicios;
   private $serires;

   public function __construct()
   {
      $this->bd = new db();
      $this->ejercicios = new ejercicios();
      $this->serires = new series();
   }

   /// devuelve los datos de un albaran de proveedor
   public function get($idalbaran, &$albaran)
   {
      $retorno = false;

      if($idalbaran != '')
      {
         $resultado = $this->bd->select("SELECT * FROM albaranesprov WHERE idalbaran = '" . $idalbaran . "';");
         if($resultado)
         {
            $albaran = $resultado[0];
            $retorno = true;
         }
      }

      return($retorno);
   }

   /// devuelve el codigo de un albaran de proveedor dado
   public function get_codigo($idalbaran)
   {
      $albaran = false;

      if($idalbaran != '')
      {
         $resultado = $this->bd->select("SELECT codigo FROM albaranesprov WHERE idalbaran = '" . $idalbaran . "';");
         if($resultado)
            $albaran = $resultado[0]['codigo'];
      }

      return($albaran);
   }

   /// devuleve en un array las lineas de un albaran de proveedor determinado
   public function get_lineas($idalbaran, &$lineas)
   {
      $retorno = false;

      if($idalbaran != '')
      {
         $resultado = $this->bd->select("SELECT * FROM lineasalbaranesprov WHERE idalbaran = '" . $idalbaran . "' ORDER BY referencia ASC;");
         if($resultado)
         {
            $lineas = $resultado;
            $retorno = true;
         }
      }

      return($retorno);
   }

   /// devuelve los datos de una linea de albaran de proveedor
   public function get_linea($idlinea, &$linea)
   {
      $retorno = false;

      if($idlinea != '')
      {
         $resultado = $this->bd->select("SELECT * FROM lineasalbaranesprov WHERE idlinea = '" . $idlinea . "';");
         if($resultado)
         {
            $linea = $resultado[0];
            $retorno = true;
         }
      }

      return($retorno);
   }

   /// devuelve un array con todos los tipos de albaranes de proveedor
   public function tipos()
   {
      return Array(
         0 => "Normal",
         1 => "Sin albar&aacute;n",
         2 => "Erroneo",
         3 => "Devoluci&oacute;n",
         4 => "Sin suma stock",
         5 => "Eliminar",
         6 => "Parcial"
      );
   }

   /// devuelve un array con los ultimos albaranes creados
   public function ultimos($num, &$ultimos, &$total, &$desde)
   {
      $retorno = FALSE;

      if($num == '')
         $num = FS_LIMITE;
      if($desde == '')
         $desde = 0;

      if($total == '')
      {
         $resultado = $this->bd->select("SELECT count(*) as total FROM albaranesprov;");
         $total = $resultado[0]['total'];
      }

      $resultado = $this->bd->select_limit("SELECT * FROM albaranesprov ORDER BY fecha DESC, codigo DESC", $num, $desde);
      if($resultado)
      {
         $ultimos = $resultado;
         $retorno = true;
      }

      return($retorno);
   }

   /// devuelve un array con los ultimos albaranes pendientes
   public function pendientes($num, &$pendientes)
   {
      $retorno = FALSE;

      if($num == '')
         $num = FS_LIMITE;

      $consulta = "SELECT idalbaran,codigo,codproveedor,numproveedor,albaranesprov.nombre,fecha,total,tipo,revisado,albaranesprov.codagente,
         agentes.nombre as anom,apellidos FROM albaranesprov LEFT JOIN agentes ON albaranesprov.codagente = agentes.codagente
         WHERE revisado = 'false' ORDER BY fecha DESC, codigo DESC";

      $resultado = $this->bd->select_limit($consulta, $num, 0);
      if($resultado)
      {
         $pendientes = $resultado;
         $retorno = true;
      }

      return($retorno);
   }

   /// devuelve por referencia un array de lineas de albaranes (distintos al seleccionado) con dicho prooverdor y referencia
   public function anteriores($idalbaran, $codproveedor, $referencia, $limite, &$albaranes)
   {
      $retorno = false;

      /// comprobamos la integridad de los par&aacute;metros
      if($idalbaran != '' AND $codproveedor != '' AND $referencia != '')
      {
         $consulta = "SELECT a.idalbaran,a.codproveedor,l.referencia,a.codigo,a.fecha,l.pvpunitario,l.cantidad,l.pvptotal,l.dtopor,l.descripcion
            FROM lineasalbaranesprov l,albaranesprov a
            WHERE referencia = '$referencia' AND l.idalbaran = a.idalbaran AND a.codproveedor = '$codproveedor' AND a.idalbaran != '$idalbaran'
            ORDER BY fecha DESC";

         $resultado = $this->bd->select_limit($consulta, $limite, 0);
         if($resultado)
         {
            $albaranes = $resultado;
            $retorno = true;
         }
      }

      return($retorno);
   }

   /// devuelve un array de albaranes de proveedor buscados
   public function buscar($buscar, $tipo, $limite, &$pagina, &$total)
   {
      $resultado = FALSE;
      
      /// quitamos los espacios del principio y final, y ponemos en mayusculas
      $buscar = strtoupper( trim($buscar) );
      
      /// comprobamos la integridad de los parametros
      if($pagina == '')
         $pagina = 0;
      
      if($limite == '')
         $limite = FS_LIMITE;
      
      if($buscar != '')
      {
         switch($tipo)
         {
            default:
               $consulta = "SELECT * FROM albaranesprov WHERE ";
               if( is_numeric($buscar) )
               {
                  $consulta .= "codigo ~~ '%$buscar%' OR numproveedor ~~ '%$buscar%' OR observaciones ~~ '%$buscar%'".
                     " OR total BETWEEN '".($buscar-.01)."' AND '".($buscar+.01)."'";
               }
               else if( preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})$/i', $buscar) ) /// es una fecha
                  $consulta .= "fecha = '$buscar' OR observaciones ~~ '%$buscar%'";
               else
                  $consulta .= "upper(codigo) ~~ '%$buscar%' OR upper(observaciones) ~~ '%".str_replace(' ', '%', $buscar)."%'";
               $consulta .= " ORDER BY fecha DESC, codigo DESC";
               break;

            case 'cop':   /// codigo de proveedor
               $consulta = "SELECT * FROM albaranesprov WHERE codproveedor = '$buscar' ORDER BY fecha DESC";
               break;

            case 'xre':   /// referencia en las lineas
               $consulta = "SELECT a.idalbaran,a.nombre,a.codproveedor,l.referencia,a.codigo,a.fecha,l.pvpunitario,l.cantidad,l.pvptotal,a.tipo,
                  a.revisado,a.ptefactura,a.observaciones
                  FROM lineasalbaranesprov l,albaranesprov a WHERE upper(referencia) = '$buscar' AND l.idalbaran = a.idalbaran
                  ORDER BY fecha DESC";
               break;

            case 'par':   /// Buscar albaranes parciales relacionados
               $consulta = "SELECT * FROM albaranesprov WHERE numproveedor = '$buscar' AND tipo = '6'
                  ORDER BY fecha DESC, codigo ASC";
               break;

            case 'age':   /// codagente
               $consulta = "SELECT idalbaran,codigo,codproveedor,numproveedor,al.nombre,fecha,total,tipo,revisado,al.codagente,
                  ag.nombre as anom,apellidos,ptefactura,observaciones
                  FROM albaranesprov al LEFT JOIN agentes ag ON al.codagente = ag.codagente
                  WHERE al.codagente = '$buscar' ORDER BY fecha DESC, codigo ASC";
               break;
         }

         if($total == '')
            $total = $this->bd->num_rows($consulta);

         $resultado = $this->bd->select_limit($consulta, $limite, $pagina);
      }

      return($resultado);
   }

   /// devuelve true si el albaran ya ha sido facturado
   public function facturado($idalbaran)
   {
      $facturado = false;

      /// comprobamos la integridad de los datos
      if($idalbaran != '')
      {
         $resultado = $this->bd->select("SELECT ptefactura FROM albaranesprov WHERE idalbaran = '$idalbaran';");
         if($resultado)
         {
            if($resultado[0]['ptefactura'] == 'f')
               $facturado = true;
         }
      }

      return($facturado);
   }

   /// guarda un nuevo albaran, devuelve true si todo va bien, false en caso contrario
   public function carrito2albaran(&$albaran, $a_stock, $lineas, &$error)
   {
      $mis_articulos = new articulos();
      $mis_proveedores = new proveedores();
      $retorno = true;

      /// comprobamos los datos
      if($albaran['codproveedor'] != '')
      {
         /// obtenemos los datos del proveedor
         $proveedor = $mis_proveedores->get($albaran['codproveedor']);
      }
      else
      {
         $error = "Debes especificar un proveedor";
         $retorno = false;
      }

      $nfecha = explode('-', $albaran['fecha']);
      if(!is_numeric($nfecha[0]) OR !is_numeric($nfecha[1]) OR !is_numeric($nfecha[2]))
      {
         $error = "Debes escribir una fecha v&aacute;lida";
         $retorno = false;
      }

      if($albaran['codalmacen'] == '')
      {
         $error = "No hay ning&uacute;n almac&eacute;n predeterminado";
         $retorno = false;
      }

      if( empty($lineas) )
      {
         $error = "No hay art&iacute;culos en tu carrito";
         $retorno = false;
      }

      if($albaran['codagente'] == '')
      {
         $error = "Tu usuario no est&aacute; vinculado a ning&uacute;n agente";
         $retorno = false;
      }

      if($albaran['tipo'] == 3)
      {
         foreach($lineas as $linea)
         {
            if($linea['cantidad'] > 0)
            {
               $error = "Si el albar&aacute;n es de devoluci&oacute;n, las cantidades deben ser negativas";
               $retorno = false;
            }
         }
      }

      if($retorno AND $albaran['codejercicio'] != '' AND $albaran['codserie'] != '')
      {
         /// obtenemos el numero de albaran
         $consulta = "SELECT s.valorout,s.id FROM secuencias s,secuenciasejercicios e
            WHERE s.nombre='nalbaranprov' AND s.id=e.id AND e.codserie='$albaran[codserie]'
            AND e.codejercicio='$albaran[codejercicio]';";

         $resultado = $this->bd->select($consulta);
         if($resultado)
         {
            $albaran['numero'] = $resultado[0]['valorout'];
            $albaran['secuenciaid'] = $resultado[0]['id'];
         }
         else
         {
            $retorno = false;
            $error = "Error al obtener el n&uacute;mero de albar&aacute;n";
         }

         /// comprobamos si el ejercicio sigue abierto
         if( !$this->ejercicios->abierto($albaran['codejercicio']) )
         {
            $retorno = false;
            $error = "Ejercicio Cerrado";
         }

         /// comprobamos si la serie es sin iva
         $sin_iva = $this->serires->sin_iva($albaran['codserie']);
      }
      else
      {
         $retorno = false;
         $error = "Ejercicio o serie vac&iacute;os";
      }


      if($retorno)
      {
         /// generamos el codigo del albaran
         $albaran['codigo'] = $albaran['codejercicio'];
         $albaran['codigo'] .= sprintf('%02s', $albaran['codserie']);
         $albaran['codigo'] .= sprintf('%06s', $albaran['numero']);

         /// obtenemos el idalbaran
         $resultado = $this->bd->select("SELECT last_value FROM albaranesprov_idalbaran_seq;");
         if($resultado)
         {
            $albaran['idalbaran'] = ($resultado[0]['last_value'] + 1);

            if( !$this->bd->exec("SELECT setval('\"albaranesprov_idalbaran_seq\"',$albaran[idalbaran],'t');") )
            {
               $retorno = false;
               $error = "Error al actualizar la secuencia";
            }
         }
         else
         {
            $retorno = false;
            $error = "Error al obtener el nuevo id del albar&aacute;n";
         }

         /// si tenemos los datos necesarios
         if($albaran['idalbaran'] != '')
         {
            /// insertamos el albaran
            $consulta = "set datestyle = dmy;
               INSERT INTO albaranesprov (idalbaran,codigo,fecha,nombre,cifnif,codproveedor,coddivisa,codpago,
               codalmacen,numproveedor,codejercicio,codserie,numero,codagente,observaciones)
               VALUES ('$albaran[idalbaran]','$albaran[codigo]','$albaran[fecha]','$proveedor[nombre]','$proveedor[cifnif]',
               '$proveedor[codproveedor]','$albaran[coddivisa]','$albaran[codpago]','$albaran[codalmacen]','$albaran[numproveedor]',
               '$albaran[codejercicio]','$albaran[codserie]','$albaran[numero]','$albaran[codagente]','$albaran[observaciones]');";

            $neto = 0;
            $iva = 0;
            $total = 0;

            /// insertamos las lineas
            foreach($lineas as $linea)
            {
               /// calculamos el importe
               $importe = ($linea['pvp'] * $linea['cantidad']);

               $consulta .= "INSERT INTO lineasalbaranesprov (idalbaran,referencia,descripcion,pvpunitario,cantidad,pvpsindto,pvptotal,codimpuesto,iva)
                  VALUES ('$albaran[idalbaran]','$linea[referencia]','" . htmlspecialchars($linea[descripcion], ENT_QUOTES) . "',";

               if($sin_iva)
               {
                  $consulta .= "'$linea[pvp]','$linea[cantidad]','$importe','$importe',NULL,'0');";
                  $neto += $importe;
                  $total += $importe;
               }
               else
               {
                  $consulta .= "'$linea[pvp]','$linea[cantidad]','$importe','$importe','$linea[codimpuesto]','$linea[iva]');";
                  $neto += $importe;
                  $iva += (($importe * $linea['iva']) / 100);
                  $total += ($importe + (($importe * $linea['iva']) / 100));
               }
            }

            /// actualizamos el albaran
            $consulta .= "UPDATE albaranesprov SET neto = '$neto', totaliva = '$iva', totaleuros = '$total', total = '$total', tipo = '$albaran[tipo]', revisado = 'false'
               WHERE idalbaran = '$albaran[idalbaran]';";

            /// actualizamos la secuencia
            $albaran['nuevonumero'] = $albaran['numero'] + 1;
            $consulta .= "UPDATE secuencias SET valorout='$albaran[nuevonumero]' WHERE nombre='nalbaranprov' AND id='$albaran[secuenciaid]';";

            if($this->bd->exec($consulta))
            {
               /// añadimos a stock
               if($a_stock)
               {
                  foreach($lineas as $linea)
                     $mis_articulos->sum_stock($linea['referencia'],$albaran['codalmacen'],$linea['cantidad'],$error);
               }
            }
            else
            {
               $error = "Error al guardar el albaran: " . $consulta;
               $retorno = false;
            }
         }
      }

      return($retorno);
   }

   /// modifica el albaran dado
   public function update(&$albaran, &$error)
   {
      $retorno = true;

      if($albaran['numproveedor'] != '' AND (eregi("^[A-Z0-9_\.]{3,19}$", $albaran['numproveedor']) != true))
      {
         $error = "Numproveedor solamente admite n&uacute;meros, letras, punto y '_'";
         $retorno = false;
      }

      if($albaran['idalbaran'] != '' AND $retorno)
      {
         /// construimos la sentencia SQL
         $consulta = "UPDATE albaranesprov SET tipo='" . $albaran['tipo'] . "', numproveedor='" . $albaran['numproveedor'] . "', observaciones='" . $albaran['observaciones'] . "'";

         if($albaran['revisado'])
            $consulta .= ", revisado='true'";
         else
            $consulta .= ", revisado='false'";

         $consulta .= " WHERE idalbaran='" . $albaran['idalbaran'] . "';";

         if( !$this->bd->exec($consulta) )
         {
            $error = $consulta;
            $retorno = false;
         }
      }

      return($retorno);
   }

   /// elimina el albaran seleccionado
   public function delete($albaran, $descontar, &$error)
   {
      $retorno = true;

      /// comprobamos la integridad de los datos
      if($albaran['idalbaran'] == '')
      {
         $retorno = false;
         $error = "Datos incorrectos:\n Albar&aacute;n=$albaran[idalbaran]";
      }

      /// comprobamos que el albaran no este facturado
      if($albaran['ptefactura'] == 'f')
      {
         $retorno = false;
         $error = "Albar&aacute;n ya facturado";
      }

      /// comprobamos si el ejercicio sigue abierto
      if( !$this->ejercicios->abierto($albaran['codejercicio']) )
      {
         $retorno = false;
         $error = "Ejercicio Cerrado";
      }

      if($retorno)
      {
         /// obtenemos las lineas del albaran
         $lineas = $this->bd->select("SELECT * FROM lineasalbaranesprov WHERE idalbaran = '$albaran[idalbaran]';");

         if($this->bd->exec("DELETE FROM albaranesprov WHERE idalbaran = '$albaran[idalbaran]' AND ptefactura = 'true';"))
         {
            /// ¿descontamos del stock?
            if($descontar)
            {
               if($lineas)
               {
                  $mis_articulos = new articulos();
                  foreach($lineas as $linea)
                     $mis_articulos->sum_stock($linea['referencia'],$albaran['codalmacen'],(0 - $linea['cantidad']),$error);
               }
            }
         }
         else
         {
            $retorno = false;
            $error = "Error al ejecutar la consulta";
         }
      }

      return($retorno);
   }

   /// recalcula el importe (y otros valores relacionados) del albaran
   public function recalcular(&$albaran, &$error)
   {
      $retorno = true;
      $neto = 0;
      $iva = 0;
      $total = 0;

      /// comprobamos la integridad de los datos
      if($albaran['idalbaran'] == '')
      {
         $retorno = false;
         $error = "Faltan par&aacute;metros";
      }
      else
      {
         /// comprobamos si la serie es sin iva
         $sin_iva = $this->serires->sin_iva($albaran['codserie']);

         /// obtenemos las lineas
         $lineas = $this->bd->select("SELECT * FROM lineasalbaranesprov WHERE idalbaran = '$albaran[idalbaran]';");
         if($lineas)
         {
            foreach($lineas as $linea)
            {
               $neto_aux = ($linea['pvpunitario'] * $linea['cantidad'] * (100 - $linea['dtopor']) / 100);
               $neto += $neto_aux;

               if($sin_iva)
                  $total += $neto_aux;
               else
               {
                  $iva += (($neto_aux * $linea['iva']) / 100);
                  $total += ($neto_aux + (($neto_aux * $linea['iva']) / 100));
               }
            }
         }

         /// actualizamos el albaran
         $consulta = "UPDATE albaranesprov SET neto = '$neto', totaliva = '$iva', totaleuros = '$total', total = '$total'
            WHERE idalbaran = '$albaran[idalbaran]';";

         if( $this->bd->exec($consulta) )
         {
            $albaran['neto'] = $neto;
            $albaran['totaliva'] = $iva;
            $albaran['totaleuros'] = $total;
            $albaran['total'] = $total;
         }
         else
         {
            $retorno = false;
            $error = "Error al guardar el albar&aacute;n: " . $consulta;
         }
      }

      return($retorno);
   }

   /// añade una liena al albaran
   public function add_linea(&$albaran, $linea, &$error)
   {
      $retorno = true;

      /// comprobamos la integridad de los datos
      if(empty($albaran) OR $linea['referencia'] == '' OR !is_numeric($linea['pvpunitario']) OR !is_numeric($linea['dtopor']) OR !is_numeric($linea['cantidad']))
      {
         $retorno = false;
         $error = "par&aacute;metros inv&aacute;lidos\n albaran=$albaran[idalbaran] | linea=$linea[referencia] |
            cantidad=$linea[cantidad] | pvp=$linea[pvp] | dto=$linea[dto]";
      }

      /// comprobamos que el albaran no este facturado
      if($albaran['ptefactura'] == 'f')
      {
         $retorno = false;
         $error = "albar&aacute;n ya facturado";
      }

      /// comprobamos si el ejercicio sigue abierto
      if( !$this->ejercicios->abierto($albaran['codejercicio']) )
      {
         $retorno = false;
         $error = "Ejercicio Cerrado";
      }

      if($retorno)
      {
         /// comprobamos si la serie es sin iva
         $sin_iva = $this->serires->sin_iva($albaran['codserie']);

         /// calculamos el importe
         $importe = ( ($linea['pvpunitario'] - ($linea['pvpunitario'] * $linea['dtopor'] / 100)) * $linea['cantidad'] );
         $importe_sdto = ($linea['pvpunitario'] * $linea['cantidad']);

         $consulta = "INSERT INTO lineasalbaranesprov (idalbaran,referencia,descripcion,pvpunitario,cantidad,pvpsindto,pvptotal,codimpuesto,iva,dtopor)
            VALUES ('$albaran[idalbaran]','$linea[referencia]',";

         if($linea['descripcion2'] == '')
            $consulta .= "'" . htmlspecialchars($linea['descripcion'], ENT_QUOTES) . "',";
         else
            $consulta .= "'" . htmlspecialchars($linea['descripcion2'], ENT_QUOTES) . "',";

         if($sin_iva)
            $consulta .= "'$linea[pvpunitario]','$linea[cantidad]','$importe_sdto','$importe',NULL,'0','$linea[dtopor]');";
         else
            $consulta .= "'$linea[pvpunitario]','$linea[cantidad]','$importe_sdto','$importe','$linea[codimpuesto]','$linea[iva]','$linea[dtopor]');";

         if($this->bd->exec($consulta))
         {
            /// recalculamos el importe del albaran
            $retorno = $this->recalcular($albaran, $error);

            /// descontamos de stock
            $mis_articulos = new articulos();
            $mis_articulos->sum_stock($linea['referencia'], $albaran['codalmacen'], $linea['cantidad'], $error);
         }
         else
         {
            $retorno = false;
            $error = "Error al actualizar la linea: " . $consulta;
         }
      }

      return($retorno);
   }

   /// modifica una linea dada de un albaran seleccionado
   public function update_linea(&$albaran, $linea, &$error)
   {
      $retorno = true;

      /// comprobamos la integridad de los datos
      if($albaran['idalbaran'] == '' OR $linea['idlinea'] == '' OR !is_numeric($linea['pvp']) OR !is_numeric($linea['dto']) OR !is_numeric($linea['cantidad']))
      {
         $retorno = false;
         $error = "Datos incorrectos:\n Albar&aacute;n=$albaran[idalbaran] | L&iacute;nea=$linea[idlinea] | pvp=$linea[pvp] | dto=$linea[dto] | cantidad=$linea[cantidad]";
      }

      /// comprobamos que el albaran no este facturado
      if($albaran['ptefactura'] == 'f')
      {
         $retorno = false;
         $error = "Albar&aacute;n ya facturado";
      }

      if($retorno)
      {
         $total = ($linea['cantidad'] * $linea['pvp'] * (100 - $linea['dto']) / 100);
         $pvp_sin_dto = ($linea['cantidad'] * $linea['pvp']);

         $consulta = "UPDATE lineasalbaranesprov SET pvpunitario = '$linea[pvp]', dtopor = '$linea[dto]', pvptotal = '$total',
            pvpsindto = '$pvp_sin_dto' WHERE idalbaran = '$albaran[idalbaran]' AND idlinea = '$linea[idlinea]';";

         if( $this->bd->exec($consulta) )
         {
            /// recalculamos el importe del albaran
            $retorno = $this->recalcular($albaran, $error);
         }
         else
         {
            $retorno = false;
            $error = "Error al actualizar la linea: " . $consulta;
         }
      }

      return($retorno);
   }

   /// elimina una linea de un albaran seleccionado
   public function delete_linea(&$albaran, $linea, &$error)
   {
      $retorno = true;

      /// comprobamos la integridad de los datos
      if($albaran['idalbaran'] == '' OR $linea['idlinea'] == '')
      {
         $retorno = false;
         $error = "Datos incorrectos:\n Albar&aacute;n=$albaran[idalbaran]' | L&iacute;nea=$linea[idlinea]";
      }

      /// comprobamos que el albaran no este facturado
      if($albaran['ptefactura'] == 'f')
      {
         $retorno = false;
         $error = "Albar&aacute;n ya facturado";
      }

      if($retorno)
      {
         if($this->bd->exec("DELETE FROM lineasalbaranesprov WHERE idlinea = '$linea[idlinea]' AND idalbaran = '$albaran[idalbaran]';"))
         {
            /// recalculamos el importe del albaran
            $retorno = $this->recalcular($albaran, $error);

            /// descontamos del stock
            $mis_articulos = new articulos();
            $mis_articulos->sum_stock($linea['referencia'], $albaran['codalmacen'], (0 - $linea['cantidad']), $error);
         }
         else
         {
            $retorno = false;
            $error = "Error al ejecutar la consulta";
         }
      }

      return($retorno);
   }

   /// marca como revisados todos los albaranes ya facturados de proveedores
   public function revisar_pendientes(&$total)
   {
      $retorno = true;

      $total = $this->bd->num_rows("SELECT idalbaran FROM albaranesprov WHERE revisado = false AND (ptefactura = false OR idfactura <> 0);");

      if($total > 0)
      {
         if( !$this->bd->exec("UPDATE albaranesprov SET revisado = true WHERE revisado = false AND (ptefactura = false OR idfactura <> 0);") )
            $retorno = false;
      }
      else
         $retorno = false;

      return($retorno);
   }

   /// devuelve un array con la información de los albaranes con errores en la suma
   public function descuadrados()
   {
      $retorno = Array();
      $errores = 0;
      $siguiente = 0;
      
      $albaranes = $this->bd->select_limit("SELECT a.idalbaran, a.codigo, a.neto, SUM(l.pvptotal) as t_neto
         FROM albaranesprov a LEFT JOIN lineasalbaranesprov l ON a.idalbaran = l.idalbaran
         GROUP BY a.idalbaran, a.codigo, a.neto", 1000, $siguiente);

      while($albaranes)
      {
         echo ".";
         
         foreach($albaranes as $col)
         {
            if(($col['t_neto'] == '' AND $col['neto'] != '0') OR ($col['t_neto'] != '' AND abs(round($col['t_neto'], 2) - round($col['neto'], 2)) > 0.02))
            {
               $retorno[$errores] = $col;
               $errores++;
            }
         }

         $siguiente += 1000;
         $albaranes = $this->bd->select_limit("SELECT a.idalbaran, a.codigo, a.neto, SUM(l.pvptotal) as t_neto
            FROM albaranesprov a LEFT JOIN lineasalbaranesprov l ON a.idalbaran = l.idalbaran
            GROUP BY a.idalbaran, a.codigo, a.neto", 1000, $siguiente);
      }

      return($retorno);
   }

   /// repara los albaranes que apuntan a facturas que no existen
   public function reparar_enlaces_facturas()
   {
      $this->bd->exec("UPDATE albaranesprov SET idfactura = 0, ptefactura = true
          WHERE idalbaran IN (SELECT idalbaran FROM albaranesprov EXCEPT SELECT idalbaran FROM lineasfacturasprov);");
   }
}

?>
