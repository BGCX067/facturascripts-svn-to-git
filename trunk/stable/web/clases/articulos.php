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

require_once("clases/familias.php");
require_once 'clases/impuestos.php';
require_once("clases/opciones.php");

class articulos
{
   private $bd;
   private $familias;
   private $impuestos;
   private $opciones;

   public function __construct()
   {
      $this->bd = new db();
      $this->familias = new familias();
      $this->impuestos = new impuestos();
      $this->opciones = new opciones();
   }

   /// devuelve los datos de un articulo
   public function get($referencia, &$articulo)
   {
      $retorno = FALSE;
      if($referencia != '')
      {
         $resultado = $this->bd->select("SELECT * FROM articulos WHERE referencia = '".$referencia."';");
         if($resultado)
         {
            $articulo = $resultado[0];
            
            /// arreglamos
            $articulo['bloqueado'] = ($articulo['bloqueado'] == 't');
            $articulo['controlstock'] = ($articulo['controlstock'] == 't');
            $articulo['destacado'] = ($articulo['destacado'] == 't');
            $articulo['ref_url'] = rawurlencode($articulo['referencia']);
            
            /// obtenemos el IVA
            $articulo['iva'] = $this->impuestos->iva($articulo['codimpuesto']);
            $articulo['pvp_iva'] = ( floatval($articulo['pvp']) * (100 + $articulo['iva']) / 100 );
            
            $retorno = TRUE;
         }
      }
      return($retorno);
   }

   /// devuelve un array de articulos equivalentes
   public function get_equivalentes($referencia, &$equivalentes)
   {
      $retorno = FALSE;
      if($referencia != '')
      {
         $resultado = $this->bd->select("SELECT * FROM articulos
            WHERE equivalencia IN (SELECT equivalencia FROM articulos WHERE referencia = '" . $referencia . "')
            AND referencia <> '" . $referencia . "'
            ORDER BY bloqueado ASC, destacado DESC, referencia ASC;");
         if($resultado)
         {
            $equivalentes = $resultado;
            $retorno = TRUE;
         }
      }
      return($retorno);
   }
   
   public function get_equivalentes_full($codigo, $excluir)
   {
      $equilist = array();
      if( isset($codigo) AND isset($excluir) )
      {
         $equivalentes = $this->bd->select("SELECT * FROM articulos WHERE equivalencia = '".$codigo."';");
         if($equivalentes)
         {
            foreach($equivalentes as $eq)
            {
               if($eq['referencia'] != $excluir)
               {
                  $nuevo = $eq;
                  
                  /// arreglamos
                  $nuevo['bloqueado'] = ($nuevo['bloqueado'] == 't');
                  $nuevo['controlstock'] = ($nuevo['controlstock'] == 't');
                  $nuevo['destacado'] = ($nuevo['destacado'] == 't');
                  $nuevo['ref_url'] = rawurlencode($nuevo['referencia']);
                  
                  /// obtenemos el IVA
                  $nuevo['iva'] = $this->impuestos->iva($nuevo['codimpuesto']);
                  $nuevo['pvp_iva'] = ( floatval($nuevo['pvp']) * (100 + $nuevo['iva']) / 100 );
                  
                  $equilist[] = $nuevo;
               }
            }
         }
      }
      return $equilist;
   }

   /// devuelve un array con el stock de un articulo en los distintos almacenes
   public function get_stock($referencia, &$stock)
   {
      $retorno = FALSE;
      if($referencia != '')
      {
         $resultado = $this->bd->select("SELECT * FROM stocks WHERE referencia = '" . $referencia . "';");
         if($resultado)
         {
            $stock = $resultado;
            $retorno = TRUE;
         }
      }
      return($retorno);
   }
   
   /// devuelve un array con el stock de un articulo en los distintos almacenes, tenga o no
   public function get_stock_full($referencia)
   {
      $stocklist = array();
      if( isset($referencia) )
      {
         $almacenes = $this->bd->select("SELECT * FROM almacenes ORDER BY nombre ASC;");
         if($almacenes)
         {
            foreach($almacenes as $a)
               $stocklist[] = array(
                   'referencia' => $referencia,
                   'codalmacen' => $a['codalmacen'],
                   'nombre' => $a['nombre'],
                   'idstock' => NULL,
                   'cantidad' => 0
               );
         }
         $stock = $this->bd->select("SELECT * FROM stocks WHERE referencia = '".$referencia."';");
         if($stock)
         {
            foreach($stock as $s)
            {
               foreach($stocklist as &$s2)
               {
                  if($s['codalmacen'] == $s2['codalmacen'])
                  {
                     $s2['idstock'] = $s['idstock'];
                     $s2['cantidad'] = $s['cantidad'];
                  }
               }
            }
         }
      }
      return $stocklist;
   }

   /// devuelve un array de tarifas del artículo, estén asignadas o no
   public function get_tarifas_full($referencia, $pvp=0, $iva=18)
   {
      $tarifalist = array();
      if( isset($referencia) )
      {
         $tarifas = $this->bd->select("SELECT * FROM tarifas ORDER BY nombre ASC;");
         if($tarifas)
         {
            foreach($tarifas as $t)
               $tarifalist[] = array(
                   'referencia' => $referencia,
                   'codtarifa' => $t['codtarifa'],
                   'nombre' => $t['nombre'],
                   'id' => NULL,
                   'dtopor' => 0,
                   'pvp_dto' => $pvp,
                   'pvp_iva' => ( $pvp * (100+$iva)/100 )
               );
         }
         $artarifas = $this->bd->select("SELECT * FROM articulostarifas WHERE referencia = '".$referencia."';");
         if($artarifas)
         {
            foreach($artarifas as $at)
            {
               foreach($tarifalist as &$t)
               {
                  if($at['codtarifa'] == $t['codtarifa'])
                  {
                     $t['id'] = $at['id'];
                     $t['dtopor'] = floatval($at['descuento']);
                     $t['pvp_dto'] = ( $pvp * (100-$t['dtopor'])/100 );
                     $t['pvp_iva'] = ( $pvp * (100-$t['dtopor'])/100 * (100+$iva)/100 );
                  }
               }
            }
         }
      }
      return $tarifalist;
   }
   
   public function get_ultimos_precios_compra($referencia, $num=3)
   {
      $preciolist = array();
      if( isset($referencia) )
      {
         $precios_compra = $this->bd->select_limit("SELECT a.idalbaran,a.fecha,l.cantidad,l.pvpunitario,l.dtopor,l.iva,l.pvptotal
            FROM lineasalbaranesprov l, albaranesprov a WHERE l.idalbaran = a.idalbaran AND l.referencia = '".$referencia."'
            ORDER BY a.fecha DESC", $num, 0);
         if($precios_compra)
         {
            foreach($precios_compra as $p)
            {
               $nuevo = $p;
               $nuevo['fecha'] = Date('d-m-Y', strtotime($nuevo['fecha']));
               $nuevo['pvp_iva'] = floatval($nuevo['pvpunitario']) * (100-floatval($nuevo['dtopor']))/100 * (100+floatval($nuevo['iva']))/100;
               $preciolist[] = $nuevo;
            }
         }
      }
      return $preciolist;
   }
   
   public function get_ultimos_precios_venta($referencia, $num=3)
   {
      $preciolist = array();
      if( isset($referencia) )
      {
         $precios_venta = $this->bd->select_limit("SELECT a.idalbaran,a.fecha,l.cantidad,l.pvpunitario,l.dtopor,l.iva,l.pvptotal
            FROM lineasalbaranescli l, albaranescli a WHERE l.idalbaran = a.idalbaran AND l.referencia = '".$referencia."'
            ORDER BY a.fecha DESC", $num, 0);
         if($precios_venta)
         {
            foreach($precios_venta as $p)
            {
               $nuevo = $p;
               $nuevo['fecha'] = Date('d-m-Y', strtotime($nuevo['fecha']));
               $nuevo['pvp_iva'] = floatval($nuevo['pvpunitario']) * (100-floatval($nuevo['dtopor']))/100 * (100+floatval($nuevo['iva']))/100;
               $preciolist[] = $nuevo;
            }
         }
      }
      return $preciolist;
   }

   /// devuelve un array de articulos buscados
   public function buscar2($buscar, $tipo, $familia, $stock, $bloqueado, $limite, &$pagina, &$total)
   {
      $resultado = FALSE;
      
      /// quitamos los espacios del principio y final, y ponemos en mayusculas
      $buscar = strtoupper( trim($buscar) );
      
      /// comprobamos la integridad de los parámetros
      if($pagina == '')
         $pagina = 0;
      
      if($limite == '')
         $limite = FS_LIMITE;
      
      if($buscar != '' OR $familia != '-')
      {
         $consulta = "SELECT * FROM articulos WHERE 1=1 ";
         
         if( is_numeric($buscar) )
            $consulta .= " AND (referencia ~~ '%$buscar%' OR equivalencia ~~ '%$buscar%'
               OR descripcion ~~ '%$buscar%' OR codbarras = '$buscar')";
         else
         {
            $buscar2 = str_replace(' ', '%', $buscar);
            $consulta .= " AND (upper(referencia) ~~ '%$buscar2%'
               OR upper(equivalencia) ~~ '%$buscar2%'
               OR upper(descripcion) ~~ '%$buscar2%')";
         }
         
         if($familia != '-')
            $consulta .= " AND codfamilia = '" . $familia . "'";
         
         if($stock)
            $consulta .= ' AND stockfis > 0';
         
         if($bloqueado)
            $consulta .= " AND bloqueado";
         else
            $consulta .= " AND bloqueado IS NOT TRUE";
         
         $consulta .= " ORDER BY referencia ASC";
         
         $resultado = $this->bd->select_limit($consulta, $limite, $pagina);
         if($resultado)
         {
            $impuestos = $this->impuestos->ivas();
            foreach($resultado as &$r)
            {
               $r['pvp'] = floatval( $r['pvp'] );
               $r['iva'] = $impuestos[ $r['codimpuesto'] ];
               $r['pvp_iva'] = ( $r['pvp'] * (100 + $r['iva']) / 100 );
            }
         }
         
         if($total == '')
         {
            if($resultado)
            {
               if(count($resultado) < $limite)
                  $total = count($resultado);
               else
                  $total = $this->bd->num_rows($consulta);
            }
            else
               $total = 0;
         }
      }
      
      if( !$resultado AND !$bloqueado AND $pagina == 0 )
      {
         /// si no se encuentran resultados, buscamos los bloqueados
         $total = '';
         return $this->buscar2($buscar, $tipo, $familia, $stock, TRUE, $limite, $pagina, $total);
      }
      else
         return $resultado;
   }

   /// inserta el articulo en la base de datos
   public function insert_articulo(&$articulo, &$error)
   {
      $retorno = TRUE;

      /// obtenemos el impuesto por defecto
      $fs_codimpuesto = FALSE;
      $this->opciones->get('impuesto', $fs_codimpuesto);

      /// codificamos la referencia
      $articulo['referencia'] = str_replace(' ', '_', $articulo['referencia']);

      /// comprobamos la validez de los datos;
      if(eregi("^[A-Z0-9_\+\.\*\/\-]{1,18}$", $articulo['referencia']) != TRUE)
      {
         $error = "La referencia solamente admite de 1 a 18 n&uacute;meros, letras y algunos signos de puntuaci&oacute;n";
         $retorno = FALSE;
      }

      if($articulo['codfamilia'] == '')
      {
         $error = "Debes seleccionar una familia v&aacute;lida";
         $retorno = FALSE;
      }

      $articulo['descripcion'] = trim($articulo['descripcion']);
      if(strlen($articulo['descripcion']) > 100)
         $articulo['descripcion'] = substr($articulo['descripcion'], 0, 99);

      if( !is_numeric($articulo['pvp']) )
         $articulo['pvp'] = 0;
      else
         $articulo['pvp'] = round($articulo['pvp'], 2);

      if(strlen($articulo['codbarras']) > 18)
         $articulo['codbarras'] = substr($articulo['codbarras'], 0, 17);

      /// comprobamos que no exista previamente
      if($retorno)
      {
         if($this->bd->select("SELECT referencia FROM articulos WHERE referencia = '" . $articulo['referencia'] . "';"))
         {
            $error = "La referencia ya existe";
            $retorno = FALSE;
         }
      }

      if($retorno)
      {
         $consulta = "INSERT INTO articulos (referencia, codbarras, codfamilia, descripcion, pvp, codimpuesto, factualizado, stockmin, stockmax, stockfis)".
            "VALUES ('" . $articulo['referencia'] . "','" . $articulo['codbarras'] . "','" . $articulo['codfamilia'] . "','" . $articulo['descripcion'] . "','".
            $articulo['pvp'] . "','" . $fs_codimpuesto . "','" . Date('j-n-Y') . "',0,0,0);";

         $resultado = $this->bd->exec($consulta);
         if(!$resultado)
         {
            $error = "Error al ejecutar la consulta: " . $consulta;
            $retorno = FALSE;
         }
      }
      return($retorno);
   }

   /// modifica el articulo en la base de datos
   public function update_articulo(&$articulo, &$error)
   {
      $retorno = TRUE;

      /// comprobamos la validez de los datos;
      if($articulo['referencia'] == '')
      {
         $error = "Referencia no suministrada";
         $retorno = FALSE;
      }

      if( !is_numeric($articulo['stockmin']) )
      {
         $error = "Stockmin debe ser num&eacute;rico";
         $retorno = FALSE;
      }

      if( !is_numeric($articulo['stockmax']) )
      {
         $error = "Stockmax debe ser num&eacute;rico";
         $retorno = FALSE;
      }

      $articulo['descripcion'] = trim($articulo['descripcion']);
      if(strlen($articulo['descripcion']) > 100)
         $articulo['descripcion'] = substr($articulo['descripcion'], 0, 99);

      if(strlen($articulo['codbarras']) > 18)
         $articulo['codbarras'] = substr($articulo['codbarras'], 0, 17);

      if($articulo['equivalencia'] != '' AND eregi('^[A-Z0-9_\+\.\*\/\-]{3,18}$', $articulo['equivalencia']) != TRUE)
      {
         $error = "El c&oacute;digo de equivalencia solamente admite de 3 a 18 n&uacute;meros, letras y '_+-.*/'";
         $retorno = FALSE;
      }

      if( !is_numeric($articulo['pvp']) )
      {
         $error = "PVP debe ser num&eacute;rico";
         $retorno = FALSE;
      }
      else
         $articulo['pvp'] = round($articulo['pvp'], 2);


      if($retorno)
      {
         /// generamos la sentencia sql
         $consulta = "set datestyle = dmy; UPDATE articulos SET descripcion = '" . $articulo['descripcion'] . "'";

         if($articulo['equivalencia'] != '')
         {
            $consulta .= ", equivalencia = '" . $articulo['equivalencia'] . "'";

            if($articulo['destacado'])
               $consulta .= ", destacado = true";
            else
               $consulta .= ", destacado = FALSE";
         }
         else
            $consulta .= ", equivalencia = NULL, destacado = false";

         $consulta .= ", codfamilia = '" . $articulo['codfamilia'] . "', codbarras = '" . $articulo['codbarras'].
            "', stockmin = '" . $articulo['stockmin'] . "', stockmax = '" . $articulo['stockmax'] . "'";

         if($articulo['bloqueado'])
            $consulta .= ", bloqueado = true";
         else
            $consulta .= ", bloqueado = false";

         if($articulo['controlstock'])
            $consulta .= ", controlstock = true";
         else
            $consulta .= ", controlstock = false";

         if($articulo['codimpuesto'] != '')
            $consulta .= ", codimpuesto = '" . $articulo['codimpuesto'] . "'";

         if($articulo['pvp'] != '')
            $consulta .= ", pvp = '" . $articulo['pvp'] . "', factualizado = '" . Date('j-n-Y') . "'";

         $consulta .= ", observaciones = '" . $articulo['observaciones'] . "' WHERE referencia = '" . $articulo['referencia'] . "';";

         if( !$this->bd->exec($consulta) )
         {
            $error = "Error al modificar el art&iacute;culo: " . $consulta;
            $retorno = FALSE;
         }
      }
      return $retorno;
   }

   /// elimina el articulo de la base de datos
   public function delete_articulo(&$articulo, &$error)
   {
      $retorno = TRUE;

      /// comprobamos los parametros
      if($articulo['referencia'] == '')
      {
         $error = "Referencia no suministrada";
         $retorno = FALSE;
      }
      else
      {
         /// comprobamos que el articulo exista
         if(!$this->bd->select("SELECT * FROM articulos WHERE referencia = '$articulo[referencia]';"))
         {
            $error = "El art&iacute;culo no existe";
            $retorno = FALSE;
         }
         else
         {
            if($this->bd->select("SELECT * FROM lineaspedidoscli WHERE referencia = '$articulo[referencia]';"))
            {
               $error = "No se puede eliminar el art&iacute;culo";
               $retorno = FALSE;
            }

            if($this->bd->select("SELECT * FROM lineaspedidosprov WHERE referencia = '$articulo[referencia]';"))
            {
               $error = "No se puede eliminar el art&iacute;culo";
               $retorno = FALSE;
            }

            if($this->bd->select("SELECT * FROM lineasalbaranescli WHERE referencia = '$articulo[referencia]';"))
            {
               $error = "No se puede eliminar el art&iacute;culo";
               $retorno = FALSE;
            }

            if($this->bd->select("SELECT * FROM lineasalbaranesprov WHERE referencia = '$articulo[referencia]';"))
            {
               $error = "No se puede eliminar el art&iacute;culo";
               $retorno = FALSE;
            }

            if($this->bd->select("SELECT * FROM lineasfacturascli WHERE referencia = '$articulo[referencia]';"))
            {
               $error = "No se puede eliminar el art&iacute;culo";
               $retorno = FALSE;
            }

            if($this->bd->select("SELECT * FROM lineasfacturasprov WHERE referencia = '$articulo[referencia]';"))
            {
               $error = "No se puede eliminar el art&iacute;culo";
               $retorno = FALSE;
            }

            /// si todo es correcto, eliminamos
            if($retorno)
            {
               if(!$this->bd->exec("DELETE FROM articulos WHERE referencia = '$articulo[referencia]';"))
               {
                  $error = "Error al ejecutar la consulta";
                  $retorno = FALSE;
               }
            }
         }
      }
      return($retorno);
   }

   /// inserta o actualizar el stock del articulo en la base de datos
   public function set_stock(&$stock, $user, &$error)
   {
      $retorno = TRUE;

      /// Comprobamos la validez de los datos;
      if($stock['referencia'] == '')
      {
         $error = "Referencia no suministrada";
         $retorno = FALSE;
      }

      if($stock['codalmacen'] == '')
      {
         $error = "C&oacute;digo de almac&eacute;n no suministrado";
         $retorno = FALSE;
      }

      if( !is_numeric($stock['cantidad']) OR !is_numeric($stock['old_stock']) )
      {
         $error = "Stock debe ser num&eacute;rico";
         $retorno = FALSE;
      }

      if($stock['cantidad'] < 0)
         $stock['cantidad'] = 0;

      if($retorno)
      {
         /// comprobamos si ya existen los datos en la base de datos
         if( $this->bd->select("SELECT * FROM stocks WHERE referencia = '" . $stock['referencia'] . "' AND codalmacen = '" . $stock['codalmacen'] . "';") )
         {
            if($stock['idstock'] != '')
            {
               /// actualizamos los datos
               $consulta = "UPDATE stocks SET nombre = '" . $stock['nombre'] . "', cantidad = '" . $stock['cantidad'] . "' WHERE idstock = '" . $stock['idstock'] . "';";

               /// actualizamos el stock de la tabla articulos
               $consulta .= "UPDATE articulos SET stockfis = (SELECT GREATEST(SUM(cantidad), 0) FROM stocks WHERE referencia = '".
                  $stock['referencia'] . "') WHERE referencia = '" . $stock['referencia'] . "';";

               /// registramos el cambio en la tabla lineasregstocks
               $consulta .= "set datestyle = dmy;INSERT INTO lineasregstocks (idstock,fecha,hora,cantidadini,cantidadfin,motivo) ".
                  "VALUES ('" . $stock['idstock'] . "','" . Date('j-n-Y') . "','" . Date('G:i') . "','" . $stock['old_stock'].
                  "','" . $stock['cantidad'] . "','" . $user . '@' . $_SERVER['REMOTE_ADDR'] . "');";

               if( !$this->bd->exec($consulta) )
               {
                  $error = "Error al actualizar el stock: " . $consulta;
                  $retorno = FALSE;
               }
            }
            else
            {
               $error = "ID no suministrado";
               $retorno = FALSE;
            }
         }
         else
         {
            /// insertamos los datos
            $consulta = "INSERT INTO stocks (codalmacen, referencia, cantidad, reservada, disponible, pterecibir) VALUES ".
               "('" . $stock['codalmacen'] . "','" . $stock['referencia'] . "','" . $stock['cantidad'] . "','0','" . $stock['cantidad'] . "','0');";

            /// actualizamos el stock de la tabla articulos
            $consulta .= "UPDATE articulos SET stockfis = (SELECT GREATEST(SUM(cantidad), 0) FROM stocks WHERE referencia = '".
                  $stock['referencia'] . "') WHERE referencia = '" . $stock['referencia'] . "';";

            if( !$this->bd->exec($consulta) )
            {
               $error = "Error al insertar el stock: " . $consulta;
               $retorno = FALSE;
            }
         }
      }
      return $retorno;
   }

   /// actualiza el stock de un articulo sumandole la cantidad pasada por parametro
   public function sum_stock($referencia, $codalmacen, $suma, &$error)
   {
      $retorno = TRUE;

      /// comprobamos la validez de los datos;
      if($referencia == '' OR $codalmacen == '' OR !is_numeric($suma))
      {
         $error = "Datos inv&aacute;lidos";
         $retorno = FALSE;
      }

      if($retorno)
      {
         /// comprobamos si ya existen los datos en la base de datos
         $resultado = $this->bd->select("SELECT * FROM stocks WHERE referencia = '" . $referencia . "' AND codalmacen = '" . $codalmacen . "';");
         if($resultado)
         {
            /// actualizamos los datos
            $suma += $resultado[0]['cantidad'];

            if($suma < 0)
               $suma = 0;

            $consulta = "UPDATE stocks SET cantidad = '" . $suma . "' WHERE referencia = '" . $referencia . "' AND codalmacen = '" . $codalmacen . "';".
               "UPDATE articulos SET stockfis = (SELECT GREATEST(SUM(cantidad), 0) FROM stocks WHERE referencia = '" . $referencia.
               "') WHERE referencia = '" . $referencia . "';";

            if( !$this->bd->exec($consulta) )
            {
               $error = "Error al actualizar el stock: " . $consulta;
               $retorno = FALSE;
            }
         }
         else
         {
            if($suma < 0)
               $suma = 0;

            /// insertamos los datos
            $consulta = "INSERT INTO stocks (codalmacen, referencia, cantidad, reservada, disponible, pterecibir) VALUES ".
               "('" . $codalmacen . "','" . $referencia . "','" . $suma . "','0','" . $suma . "','0');";

            /// actualizamos el stock de la tabla articulos
            $consulta .= "UPDATE articulos SET stockfis = (SELECT GREATEST(SUM(cantidad), 0) FROM stocks WHERE referencia = '" . $referencia.
               "') WHERE referencia = '" . $referencia . "';";

            if( !$this->bd->exec($consulta) )
            {
               $error = "Error al insertar el stock: " . $consulta;
               $retorno = FALSE;
            }
         }
      }
      return $retorno;
   }

   /// elimina el stock del articulo
   public function delete_stock($idstock, $referencia, &$error)
   {
      $retorno = TRUE;

      if($idstock == '')
      {
         $error = "ID no suministrado";
         $retorno = FALSE;
      }

      if($referencia == '')
      {
         $error = "Referencia no suministrada";
         $retorno = FALSE;
      }

      if($retorno)
      {
         /// eliminamos de la tabla stocks y actualizamos el stock de la tabla articulos
         $consulta = "DELETE FROM stocks WHERE idstock = '" . $idstock . "';".
            "UPDATE articulos SET stockfis = (SELECT GREATEST(SUM(cantidad), 0) FROM stocks WHERE referencia = '" . $referencia.
            "') WHERE referencia = '" . $referencia . "';";

         if( !$this->bd->exec($consulta) )
         {
            $error = "Error al eliminar el stock";
            $retorno = FALSE;
         }
      }
      
      return $retorno;
   }
   
   /// inserta una tarifa de un articulo en la base de datos
   public function set_tarifa($tarifa, &$error)
   {
      if( is_null($tarifa['id']) )
      {
         return $this->bd->exec("INSERT INTO articulostarifas (codtarifa,referencia,descuento)
            VALUES ('".$tarifa['codtarifa']."','".$tarifa['referencia']."','".$tarifa['descuento']."');");
      }
      else
      {
         return $this->bd->exec("UPDATE articulostarifas SET descuento = '".$tarifa['descuento']."' WHERE id = '".$tarifa['id']."';");
      }
   }

   /// devuelve un array de stock con los ultimos movimientos de stock
   public function lista_last_mov_stock($referencia, $limite)
   {
      if($limite == '')
         $limite = FS_LIMITE;

      if($referencia != '')
      {
         $consulta = "SELECT a.bloqueado,l.fecha,s.referencia,s.codalmacen,l.cantidadini,l.cantidadfin,l.motivo
            FROM articulos a,stocks s,lineasregstocks l
            WHERE a.referencia=s.referencia AND s.idstock=l.idstock AND a.referencia = '$referencia'
            ORDER BY l.fecha DESC, s.referencia DESC";
      }
      else
      {
         $consulta = "SELECT a.bloqueado,l.fecha,s.referencia,s.codalmacen,l.cantidadini,l.cantidadfin,l.motivo
            FROM articulos a,stocks s,lineasregstocks l
            WHERE a.referencia=s.referencia AND s.idstock=l.idstock
            ORDER BY l.fecha DESC, s.referencia DESC";
      }
      return $this->bd->select_limit($consulta, $limite, 0);
   }

   /// devuelve un array de articulos con los mas vendidos
   public function lista_top_ventas($limite)
   {
      if($limite == '')
         $limite = FS_LIMITE;
      
      return $this->bd->select_limit("SELECT referencia, SUM(cantidad) as ventas FROM lineasalbaranescli
         WHERE referencia IN (SELECT referencia FROM articulos WHERE NOT bloqueado AND stockfis > 0)
         AND idalbaran IN (SELECT idalbaran FROM albaranescli WHERE fecha > '1-1-" . Date('Y') . "')
         GROUP BY referencia ORDER BY ventas DESC", $limite, 0);
   }

   /// devuelve un array de stock con los articulos que estan bajo minimos
   public function lista_stock_minimos($limite, &$total)
   {
      if($limite == '')
         $limite = 5;
      
      $consulta = "SELECT * FROM articulos
         WHERE stockfis < stockmin AND bloqueado = FALSE
         ORDER BY (stockfis - stockmin) ASC, referencia DESC";
      $total = $this->bd->num_rows($consulta);
      
      return $this->bd->select_limit($consulta, $limite,0);
   }

   /// devuelve un array de stock con los articulos que tienen algun error en el stock
   public function lista_stock_erroneo($limite, &$total)
   {
      if($limite == '')
         $limite = FS_LIMITE;
      
      $consulta = "SELECT a.referencia, a.stockfis, SUM(s.cantidad) as stock
         FROM articulos a, stocks s
         WHERE a.referencia = s.referencia
         GROUP BY a.referencia, a.stockfis
         HAVING a.stockfis <> SUM(s.cantidad)
         UNION
         SELECT a.referencia, a.stockfis, 0 as stock FROM articulos a
         WHERE a.stockfis <> 0 AND (a.referencia NOT IN (SELECT referencia FROM stocks))
         ORDER BY referencia DESC";
      $total = $this->bd->num_rows($consulta);
      
      return $this->bd->select_limit($consulta, $limite, 0);
   }

   /// genera el html necesario del formulario de busqueda de articulos
   public function show_search2($mod, $buscar, $tipo, $familia='-', $stock=FALSE, $bloqueado=FALSE)
   {
      $familias = $this->familias->all();
      if($familias)
      {
         echo '<form name="articulos" action="ppal.php" method="get">',
            '<input type="hidden" name="mod" value="' , $mod , '"/>',
            '<input type="hidden" name="pag" value="articulos"/>',
            '<div class="destacado">',
            "<table width='100%'><tr><td>",
            "<span>Art&iacute;culos</span>\n",
            '<input type="text" name="buscar" size="18" maxlength="18" value="' , $buscar , '"/>',
            '<input type="submit" value="buscar"/>';

         if($stock)
            echo "<input id='articulos_stock' type='checkbox' name='s' value='true' title='Mostrar solamente art&iacute;culos con stock' checked='checked'/>
               <label for='articulos_stock'>Stock</label>\n";
         else
            echo "<input id='articulos_stock' type='checkbox' name='s' value='true' title='Mostrar solamente art&iacute;culos con stock'/>
               <label for='articulos_stock'>Stock</label>\n";

         if($bloqueado)
            echo "<input id='articulos_bloqueado' type='checkbox' name='b' value='true' title='Mostrar solamente art&iacute;culos bloqueados' checked='checked'/>
               <label for='articulos_bloqueado'>Bloqueado</label>\n";
         else
            echo "<input id='articulos_bloqueado' type='checkbox' name='b' value='true' title='Mostrar solamente art&iacute;culos bloqueados'/>
               <label for='articulos_bloqueado'>Bloqueado</label>\n";

         echo " &nbsp; <a href='ppal.php?mod=" , $mod , "&amp;pag=familias'>Familia</a>: <select name='f' size='0' onchange='document.articulos.submit()'>\n";
         echo "<option value='-'>-Todas-</option>\n";

         foreach($familias as $col)
         {
            if($col['codfamilia'] == $familia)
               echo '<option value="' , $col['codfamilia'] , '" selected>' , $col['descripcion'] . "</option>\n";
            else
               echo '<option value="' , $col['codfamilia'] , '">' , $col['descripcion'] . "</option>\n";
         }
         
         echo "</select>\n</td>\n",
            "<td valign='top' align='right'><a href='#' class='boton' onclick='fs_nuevo_articulo()'>crear artículo</a></td>\n",
            "</tr>\n</table>\n",
            "</div>\n</form>\n";
         
         /// dibujamos el popup
         echo "<div class='popup' id='popup_nuevo_articulo'>
            <h1>Crear artículo</h1>
            <form name='f_p_nuevo_articulo' action='ppal.php?mod=" , $mod , "&amp;pag=articulo' method='post'>\n",
            "<input type='hidden' value='nuevo'/>\n",
            "<table width='100%'>\n",
            "<tr>",
            "<td align='right'>Referencia:</td>\n",
            "<td><input type='text' name='referencia' value='' size='18' maxlength='18'/></td>\n",
            "</tr>\n",
            "<tr>\n",
            "<td align='right'>Familia:</td>\n",
            "<td><select name='codfamilia' size='0'>\n";

         foreach($familias as $col)
            echo '<option value="' , $col['codfamilia'] , '">' , $col['descripcion'] , "</option>\n";

         echo "</select></td>\n",
            "</tr>\n",
            "<tr>\n",
            "<td><a href='#' class='cancelar' onclick='fs_nuevo_articulo_cerrar()'>cancelar<a></td>\n",
            "<td align='right'><input type='submit' value='Crear art&iacute;culo'/></td>\n",
            "</tr>\n",
            "</table>\n",
            "</form>\n</div>\n";
      }
      else
         echo "<div class='mensaje'>No hay familias creadas</div>\n";
   }

   /// devuelve el numero total de articulos en la tabla articulos
   public function total()
   {
      $resultado = $this->bd->select("SELECT count(referencia) as total FROM articulos;");
      if($resultado)
         return intval( $resultado[0]['total'] );
      else
         return 0;
   }

   /// devuelve el numero de articulos creados/actualizados en dicha fecha
   public function total_actualizados($fecha)
   {
      $resultado = $this->bd->select("set datestyle = dmy;
         SELECT count(referencia) as total FROM articulos
         WHERE factualizado >= '$fecha';");
      if($resultado)
         return intval( $resultado[0]['total'] );
      else
         return 0;
   }

   /// devuelve el numero total de articulos en stock
   public function total_stock()
   {
      $resultado = $this->bd->select("SELECT count(a.referencia) as total
         FROM articulos a LEFT JOIN stocks s ON a.referencia = s.referencia
         WHERE stockfis > 0 AND bloqueado = false;");
      if($resultado)
         return intval( $resultado[0]['total'] );
      else
         return 0;
   }

   /// devuelve el valor de todo el stock
   public function total_euros_stock()
   {
      $resultado = $this->bd->select("SELECT SUM(articulos.pvp * articulos.stockfis) as total
         FROM articulos LEFT JOIN stocks ON articulos.referencia = stocks.referencia
         WHERE stockfis > 0 AND bloqueado = false;");
      if($resultado)
         return floatval( $resultado[0]['total'] );
      else
         return 0;
   }
}

?>