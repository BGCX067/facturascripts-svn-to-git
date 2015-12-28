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
require_once("clases/clientes.php");
require_once("clases/ejercicios.php");
require_once("clases/series.php");

class albaranes_cli
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

   /// devuelve los datos de un albaran de cliente
   public function get($idalbaran, &$albaran)
   {
      $retorno = false;

      if($idalbaran != '')
      {
         $resultado = $this->bd->select("SELECT * FROM albaranescli WHERE idalbaran = '$idalbaran';");
         if($resultado)
         {
            $albaran = $resultado[0];
            $retorno = true;
         }
      }

      return($retorno);
   }

   /// devuelve el codigo de un albaran de cliente dado
   public function get_codigo($idalbaran)
   {
      $albaran = false;

      if($idalbaran != '')
      {
         $resultado = $this->bd->select("SELECT codigo FROM albaranescli WHERE idalbaran = '$idalbaran';");
         if($resultado)
            $albaran = $resultado[0]['codigo'];
      }

      return($albaran);
   }

   /// devuleve en un array las lineas de un albaran de cliente determinado
   public function get_lineas($idalbaran, &$lineas)
   {
      $retorno = false;

      if($idalbaran != '')
      {
         $resultado = $this->bd->select("SELECT * FROM lineasalbaranescli WHERE idalbaran = '$idalbaran' ORDER BY referencia ASC;");
         if($resultado)
         {
            $lineas = $resultado;
            $retorno = true;
         }
      }

      return($retorno);
   }

   /// devuelve los datos de una linea de un albaran de cliente
   public function get_linea($idlinea, &$linea)
   {
      $retorno = false;

      if($idlinea != '')
      {
         $resultado = $this->bd->select("SELECT * FROM lineasalbaranescli WHERE idlinea = '$idlinea';");
         if($resultado)
         {
            $linea = $resultado[0];
            $retorno = true;
         }
      }

      return($retorno);
   }

   /// devuelve el codigo de factura de cliente asociado a un idfactura
   public function get_codigo_factura($idfactura)
   {
      $codigo = false;

      if($idfactura != '')
      {
         $resultado = $this->bd->select("SELECT codigo FROM facturascli WHERE idfactura = '$idfactura';");
         if($resultado)
            $codigo = $resultado[0]['codigo'];
      }

      return($codigo);
   }

   /// devuelve en un array resultado de la busqueda de albaranes de cliente
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
               $consulta = "SELECT idalbaran,codigo,numero2,codcliente,nombrecliente,ag.nombre as anom,ag.apellidos,fecha,total,
                  al.codagente,revisado,ptefactura,al.observaciones
                  FROM albaranescli al LEFT JOIN agentes ag ON al.codagente = ag.codagente
                  WHERE ";
               
               if( is_numeric($buscar) )
               {
                  $consulta .= "codigo ~~ '%$buscar%' OR numero2 LIKE '%$buscar%' OR al.observaciones ~~ '%$buscar%'".
                     " OR total BETWEEN '".($buscar-.01)."' AND '".($buscar+.01)."'";
               }
               else if( preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})$/i', $buscar) ) /// es una fecha
                  $consulta .= "fecha = '$buscar' OR al.observaciones ~~ '%$buscar%'";
               else
                  $consulta .= "upper(codigo) ~~ '%$buscar%' OR upper(al.observaciones) ~~ '%".str_replace(' ', '%', $buscar)."%'";
               
               $consulta .= " ORDER BY fecha DESC, codigo DESC";
               break;
            
            case 'coc': /// codigo de cliente
               $consulta = "SELECT idalbaran,codigo,numero2,codcliente,nombrecliente,ag.nombre as anom,ag.apellidos,fecha,total,
                  al.codagente,revisado,ptefactura,al.observaciones
                  FROM albaranescli al LEFT JOIN agentes ag ON al.codagente = ag.codagente
                  WHERE codcliente = '$buscar' ORDER BY fecha DESC, codigo DESC";
               break;
            
            case 'xre': /// referencia en las lineas
               $consulta = "SELECT a.idalbaran,a.codcliente,a.nombrecliente,l.referencia,a.codigo,a.fecha,l.pvpunitario,l.cantidad,l.pvptotal,
                  l.dtopor,revisado,ptefactura,a.observaciones
                  FROM lineasalbaranescli l, albaranescli a
                  WHERE upper(referencia) = '$buscar' AND l.idalbaran = a.idalbaran ORDER BY fecha DESC";
               break;
            
            case 'age': /// codagente
               $consulta = "SELECT idalbaran,codigo,numero2,codcliente,nombrecliente,ag.nombre as anom,ag.apellidos,fecha,total,
                  al.codagente,revisado,ptefactura,al.observaciones
                  FROM albaranescli al LEFT JOIN agentes ag ON al.codagente = ag.codagente
                  WHERE al.codagente = '$buscar' ORDER BY fecha DESC, codigo DESC";
               break;
         }

         if($total == '')
            $total = $this->bd->num_rows($consulta);

         $resultado = $this->bd->select_limit($consulta, $limite, $pagina);
      }

      return($resultado);
   }

   /// listado de los ultimos albaranes
   public function ultimos($num, &$albaranes, &$total, &$desde)
   {
      $retorno = false;
      
      if($num == '')
         $num = FS_LIMITE;
      
      if($desde == '')
         $desde = 0;

      if($total == '')
      {
         $resultado = $this->bd->select("SELECT count(*) as total FROM albaranescli;");
         $total = $resultado[0]['total'];
      }

      $resultado = $this->bd->select_limit("SELECT idalbaran,codigo,numero2,codcliente,nombrecliente,agentes.nombre as anom,agentes.apellidos,fecha,total,
         albaranescli.codagente,revisado,ptefactura,observaciones FROM albaranescli LEFT JOIN agentes ON albaranescli.codagente = agentes.codagente
         ORDER BY fecha DESC, codigo DESC", $num, $desde);

      if($resultado)
      {
         $albaranes = $resultado;
         $retorno = true;
      }

      return($retorno);
   }

   /// listado de los ultimos albaranes pendientes (no revisados)
   public function pendientes($num, &$albaranes)
   {
      $retorno = false;

      if( empty($num) )
         $num = FS_LIMITE;

      $consulta = "SELECT idalbaran,codigo,numero2,codcliente,nombrecliente,agentes.nombre as anom,agentes.apellidos,fecha,total,albaranescli.codagente,revisado
         FROM albaranescli LEFT JOIN agentes ON albaranescli.codagente = agentes.codagente
         WHERE revisado = false ORDER BY fecha DESC, codigo DESC";

      $resultado = $this->bd->select_limit($consulta, $num, 0);
      if($resultado)
      {
         $albaranes = $resultado;
         $retorno = true;
      }

      return($retorno);
   }

   /// listado para recorrer todos los albaranes ordenados desde los ultimos al primero
   public function anteriores($codcliente, $referencia, $limite, &$albaranes)
   {
      $retorno = false;

      if($codcliente != '' AND $referencia != '')
      {
         $consulta = "SELECT a.idalbaran,l.referencia,a.codigo,a.fecha,l.pvpunitario,l.cantidad,l.pvptotal,l.dtopor,l.descripcion,l.iva
            FROM lineasalbaranescli l,albaranescli a
            WHERE referencia = '$referencia' AND l.idalbaran = a.idalbaran AND a.codcliente = '$codcliente'
            ORDER BY fecha DESC";

         $resultado = $this->bd->select_limit($consulta, $limite, 0);
         if($resultado)
         {
            $albaranes = $resultado;
            $retorno = true;
         }
      }

      return($resultado);
   }

   /// devuelve true si el albaran ya ha sido facturado
   public function facturado($idalbaran)
   {
      $facturado = false;

      if($idalbaran != '')
      {
         $resultado = $this->bd->select("SELECT ptefactura FROM albaranescli WHERE idalbaran = '$idalbaran';");
         if($resultado)
         {
            if($resultado[0]['ptefactura'] == 'f')
               $facturado = true;
         }
      }

      return($facturado);
   }

   /// guarda un nuevo albaran, devuelve true si todo va bien, false en caso contrario
   public function carrito2albaran(&$albaran, $des_stock, $lineas, &$error)
   {
      $mis_articulos = new articulos();
      $mis_clientes = new clientes();
      $retorno = true;

      if($albaran['codcliente'] != '')
      {
         /// obtenemos los datos del cliente
         $cliente = $mis_clientes->get($albaran['codcliente']);
         $direccion = $mis_clientes->get_direccion($albaran['codcliente']);

         if($direccion['id'] == '')
         {
            $retorno = false;
            $error = "El cliente no tiene una direcci&oacute;n conocida";
         }
      }
      else
      {
         $retorno = false;
         $error = "Debes especificar un cliente";
      }

      $nfecha = explode('-', $albaran['fecha']);
      if(!is_numeric($nfecha[0]) OR !is_numeric($nfecha[1]) OR !is_numeric($nfecha[2]))
      {
         $retorno = false;
         $error = "Debes escribir una fecha v&aacute;lida";
      }

      if($albaran['codalmacen'] == '')
      {
         $retorno = false;
         $error = "No hay ning&uacute;n almac&eacute;n predeterminado";
      }

      if( empty($lineas) )
      {
         $retorno = false;
         $error = "No hay art&iacute;culos en tu carrito";
      }

      if($albaran['codagente'] == '')
      {
         $retorno = false;
         $error = "Tu usuario no est&aacute; vinculado a ning&uacute;n agente";
      }

      if($retorno AND $albaran['codejercicio'] != '' AND $albaran['codserie'] != '')
      {
         /// obtenemos el numero de albaran
         $consulta = "SELECT s.valorout,s.id FROM secuencias s,secuenciasejercicios e
            WHERE s.nombre='nalbarancli' AND s.id=e.id AND e.codserie='$albaran[codserie]'
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
      else if($retorno)
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
         $resultado = $this->bd->select("SELECT last_value FROM albaranescli_idalbaran_seq;");
         if($resultado)
         {
            $albaran['idalbaran'] = ($resultado[0]['last_value'] + 1);

            /// actualizamos la secuencia
            if( !$this->bd->exec("SELECT setval('\"albaranescli_idalbaran_seq\"',$albaran[idalbaran],'t');") )
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
               INSERT INTO albaranescli (idalbaran,codigo,fecha,nombrecliente,cifnif,codcliente,observaciones,coddivisa,numero,
               codserie,codpago,codagente,codalmacen,coddir,direccion,codpostal,ciudad,provincia,apartado,codpais,codejercicio,numero2)
               VALUES ('$albaran[idalbaran]','$albaran[codigo]','$albaran[fecha]','$cliente[nombre]','$cliente[cifnif]','$cliente[codcliente]',
               '$albaran[observaciones]','$albaran[coddivisa]','$albaran[numero]','$albaran[codserie]','$albaran[codpago]',
               '$albaran[codagente]','$albaran[codalmacen]','$direccion[id]','$direccion[direccion]','$direccion[codpostal]',
               '$direccion[ciudad]','$direccion[provincia]','$direccion[apartado]','$direccion[codpais]','$albaran[codejercicio]','$albaran[numero2]');";

            $neto = 0;
            $iva = 0;
            $total = 0;

            /// insertamos las lineas
            foreach($lineas as $linea)
            {
               /// calculamos el importe
               $importe = ( ($linea['pvpunitario'] - ($linea['pvpunitario'] * $linea['dtopor'] / 100)) * $linea['cantidad'] );
               $importe_sdto = ($linea['pvpunitario'] * $linea['cantidad']);

               $consulta .= "INSERT INTO lineasalbaranescli (idalbaran,referencia,descripcion,pvpunitario,cantidad,pvpsindto,pvptotal,codimpuesto,iva,dtopor)
                  VALUES ('$albaran[idalbaran]','$linea[referencia]',";

               if($linea['descripcion2'] == "")
                  $consulta .= "'" . htmlspecialchars($linea['descripcion'], ENT_QUOTES) . "',";
               else
                  $consulta .= "'" . htmlspecialchars($linea['descripcion2'], ENT_QUOTES) . "',";

               if($sin_iva)
               {
                  $consulta .= "'$linea[pvpunitario]','$linea[cantidad]','$importe_sdto','$importe',NULL,'0','$linea[dtopor]');";
                  $neto += $importe;
                  $total += $importe;
               }
               else
               {
                  $consulta .= "'$linea[pvpunitario]','$linea[cantidad]','$importe_sdto','$importe','$linea[codimpuesto]','$linea[iva]','$linea[dtopor]');";
                  $neto += $importe;
                  $iva += ($importe * $linea['iva'] / 100);
                  $total += ($importe + ($importe * $linea['iva'] / 100));
               }
            }

            /// actualizamos el albaran
            $consulta .= "UPDATE albaranescli SET neto='$neto',totaliva='$iva',totaleuros='$total',total='$total' WHERE idalbaran='$albaran[idalbaran]';";

            /// actualizamos la secuencia
            $albaran['nuevonumero'] = $albaran['numero'] + 1;
            $consulta .= "UPDATE secuencias SET valorout='$albaran[nuevonumero]' WHERE nombre='nalbarancli' AND id='$albaran[secuenciaid]';";

            /// Ejecutamos la sentencia
            if( $this->bd->exec($consulta) )
            {
               /// descontamos de stock
               if($des_stock)
               {
                  foreach($lineas as $linea)
                     $mis_articulos->sum_stock($linea['referencia'], $albaran['codalmacen'], (0 - $linea['cantidad']), $error);
               }
            }
            else
            {
               $retorno = false;
               $error = "Error al guardar el albar&aacute;n: " . $consulta;
            }
         }
      }

      return($retorno);
   }

   /// modifica un albarande cliente dado
   public function update($albaran, &$error)
   {
      $retorno = true;

      /// comprobamos la integridad de los datos
      if($albaran['numero2'] != '' AND eregi("^[A-Z0-9_\.]{3,19}$", $albaran['numero2']) != true)
      {
         $error = "Numero2 solamente admite n&uacute;meros, letras, punto y '_'";
         $retorno = false;
      }

      if($albaran['idalbaran'] != '' AND $retorno)
      {
         /// construimos la sentencia sql
         $consulta = "UPDATE albaranescli SET numero2='" . $albaran['numero2'] . "', observaciones='" . $albaran['observaciones'] . "'";

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

   /// borra un albaran de la base de datos
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
         $lineas = $this->bd->select("SELECT * FROM lineasalbaranescli WHERE idalbaran = '$albaran[idalbaran]';");

         if( $this->bd->exec("DELETE FROM albaranescli WHERE idalbaran = '$albaran[idalbaran]' AND ptefactura = 'true';") )
         {
            /// ¿descontamos del stock?
            if($descontar)
            {
               if($lineas)
               {
                  $mis_articulos = new articulos();
                  foreach($lineas as $linea)
                     $mis_articulos->sum_stock($linea['referencia'], $albaran['codalmacen'], $linea['cantidad'], $error);
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
         $lineas = $this->bd->select("SELECT * FROM lineasalbaranescli WHERE idalbaran = '$albaran[idalbaran]';");
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
         $consulta = "UPDATE albaranescli SET neto = '$neto', totaliva = '$iva', totaleuros = '$total', total = '$total'
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

   /// añade una linea al albaran
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

         $consulta = "INSERT INTO lineasalbaranescli (idalbaran,referencia,descripcion,pvpunitario,cantidad,pvpsindto,pvptotal,codimpuesto,iva,dtopor)
            VALUES ('$albaran[idalbaran]','$linea[referencia]',";

         if( $linea['descripcion2'] == "")
            $consulta .= "'" . htmlspecialchars($linea['descripcion'], ENT_QUOTES) . "',";
         else
            $consulta .= "'" . htmlspecialchars($linea['descripcion2'], ENT_QUOTES) . "',";

         if($sin_iva)
            $consulta .= "'$linea[pvpunitario]','$linea[cantidad]','$importe_sdto','$importe',NULL,'0','$linea[dtopor]');";
         else
            $consulta .= "'$linea[pvpunitario]','$linea[cantidad]','$importe_sdto','$importe','$linea[codimpuesto]','$linea[iva]','$linea[dtopor]');";

         if( $this->bd->exec($consulta) )
         {
            /// recalculamos el importe del albaran
            $retorno = $this->recalcular($albaran, $error);

            /// descontamos de stock
            $mis_articulos = new articulos();
            $mis_articulos->sum_stock($linea['referencia'], $albaran['codalmacen'], (0 - $linea['cantidad']), $error);
         }
         else
         {
            $retorno = false;
            $error = "Error al actualizar la linea: " . $consulta;
         }
      }

      return($retorno);
   }

   /// modificar una linea de un albaran
   public function update_linea(&$albaran, $linea, &$error)
   {
      $retorno = true;

      /// comprobamos la integridad de los datos
      if($albaran['idalbaran'] == '' OR $linea['idlinea'] == '' OR !is_numeric($linea['pvp']) OR !is_numeric($linea['dto']) OR !is_numeric($linea['cantidad']))
      {
         $retorno = false;
         $error = "Par&aacute;metros inv&aacute;lidos\n albaran=$albaran[idalbaran] | linea=$linea[idlinea] | cantidad=$linea[cantidad] | pvp=$linea[pvp] | dto=$linea[dto]";
      }

      /// comprobamos que el albaran no este facturado
      if( $this->facturado($albaran['idalbaran']) )
      {
         $retorno = false;
         $error = "Albar&aacute;n ya facturado";
      }

      if($retorno)
      {
         $total = ($linea['cantidad'] * $linea['pvp'] * (100 - $linea['dto']) / 100);
         $pvp_sin_dto = ($linea['cantidad'] * $linea['pvp']);

         /// construimos la sentencia sql
         $consulta = "UPDATE lineasalbaranescli SET pvpunitario = '$linea[pvp]', dtopor = '$linea[dto]', pvptotal = '$total',
            pvpsindto = '$pvp_sin_dto' WHERE idalbaran='$albaran[idalbaran]' AND idlinea='$linea[idlinea]';";

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

   public function delete_linea($albaran, $linea, &$error)
   {
      $retorno = true;

      /// comprobamos la integridad de los datos
      if($albaran['idalbaran'] == '' OR $linea['idlinea'] == '')
      {
         $retorno = false;
         $error = "Datos incorrectos:\n Albar&aacute;n=$albaran[idalbaran] | L&iacute;nea=$linea[idlinea]";
      }

      /// comprobamos que el albaran no este facturado
      if($albaran['ptefactura'] == 'f')
      {
         $retorno = false;
         $error = "Albar&aacute;n ya facturado";
      }

      if($retorno)
      {
         if( $this->bd->exec("DELETE FROM lineasalbaranescli WHERE idlinea = '$linea[idlinea]' AND idalbaran = '$albaran[idalbaran]';") )
         {
            /// recalculamos el importe del albaran
            $retorno = $this->recalcular($albaran, $error);

            /// descontamos del stock
            $mis_articulos = new articulos();
            $mis_articulos->sum_stock($linea['referencia'], $albaran['codalmacen'], $linea['cantidad'], $error);
         }
         else
         {
            $retorno = false;
            $error = "Error al ejecutar la consulta";
         }
      }

      return($retorno);
   }

   /// marca como revisados todos los albaranes ya facturados de clientes
   public function revisar_pendientes(&$total)
   {
      $retorno = true;

      $total = $this->bd->num_rows("SELECT idalbaran FROM albaranescli WHERE revisado = false AND (ptefactura = false OR idfactura <> 0);");

      if($total > 0)
      {
         if( !$this->bd->exec("UPDATE albaranescli SET revisado = true WHERE revisado = false AND (ptefactura = false OR idfactura <> 0);") )
            $retorno = false;
      }
      else
         $retorno = false;

      return($retorno);
   }

   /// devuelve un array con la información de los albaranes con errores en las sumas
   public function descuadrados()
   {
      $retorno = Array();
      $errores = 0;
      $siguiente = 0;
      
      $albaranes = $this->bd->select_limit("SELECT a.idalbaran, a.codigo, a.neto, SUM(l.pvptotal) as t_neto
         FROM albaranescli a LEFT JOIN lineasalbaranescli l ON a.idalbaran = l.idalbaran
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
            FROM albaranescli a LEFT JOIN lineasalbaranescli l ON a.idalbaran = l.idalbaran
            GROUP BY a.idalbaran, a.codigo, a.neto", 1000, $siguiente);
      }

      return($retorno);
   }

   /// repara los albaranes que apuntan a facturas que no existen
   public function reparar_enlaces_facturas()
   {
      $this->bd->exec("UPDATE albaranescli SET idfactura = 0, ptefactura = true
         WHERE idalbaran IN (SELECT idalbaran FROM albaranescli EXCEPT SELECT idalbaran FROM lineasfacturascli);");
   }
}

?>
