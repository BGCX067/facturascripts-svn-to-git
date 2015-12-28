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

require_once("clases/albaranes_cli.php");
require_once("clases/albaranes_prov.php");
require_once("clases/articulos.php");
require_once("clases/asientos.php");
require_once("clases/cuentas.php");
require_once("clases/ejercicios.php");
require_once("clases/facturas_cli.php");
require_once("clases/facturas_prov.php");

class automata
{
   private $bd;
   private $enlace = false;
   private $albaranes_cli;
   private $albaranes_prov;
   private $articulos;
   private $asientos;
   private $cuentas;
   private $ejercicios;
   private $facturas_cli;
   private $facturas_prov;
   private $articulos_bk;
   private $periodo;

   public function __construct($argumento)
   {
      switch($argumento)
      {
         default:
            $this->periodo = "dia";
            break;

         case "hora":
         case "semana":
            $this->periodo = $argumento;
            break;
      }

      $this->bd = new db();
      $this->enlace = $this->bd->conectar();

      $this->albaranes_cli = new albaranes_cli();
      $this->albaranes_prov = new albaranes_prov();
      $this->articulos = new articulos();
      $this->asientos = new asientos();
      $this->cuentas = new cuentas();
      $this->ejercicios = new ejercicios();
      $this->facturas_cli = new facturas_cli();
      $this->facturas_prov = new facturas_prov();

      $this->articulos_bk = false;
   }

   public function __destruct()
   {
      if($this->articulos_bk)
      {
         fclose( $this->articulos_bk );
      }

      $this->bd->desconectar();
   }

   private function enlace_db()
   {
      return($this->enlace != false);
   }

   public function run()
   {
      if($this->enlace_db())
      {
         echo " -> conectado correctamente a la base de datos " , FS_DB_NAME , ".\n";

         if( $this->bd->existe_tabla("fs_mensajes") )
         {
            $this->escribe_log("inicio", "automata conectado ( periodo: " . $this->periodo . " )");

            switch( $this->periodo )
            {
               case "hora":
                  $this->scan_articulos();
                  break;

               case "dia":
                  $this->scan_albaranes();
                  $this->scan_facturas();
                  $this->renumerar_asientos();
                  $this->scan_asientos();
                  break;

               case "semana":
                  $this->scan_cuentas();
                  $this->informe_semanal();
                  break;
            }
         }
         else
         {
            echo " -> la base de datos no esta actualizada,\n por favor, actualicela desde Administracion >> Actualizar BD.\n";
         }

         echo " -> bye.\n";
      }
      else
      {
         echo " -> error al conectar a la base de datos.\n";
      }
   }

   private function scan_articulos()
   {
      $total = $this->articulos->total();

      if($total > 1000)
      {
         /// generamos aleatoriamente el numero de articulos a chequear
         $numero = rand( 100, 1000 );

         /// generamos aleatoriamente el numero desde donde comenzaremos a examinar
         $inicio = rand( 1, $total - $numero );

         $articulos = $this->bd->select_limit("SELECT * FROM articulos", $numero, $inicio);
         if($articulos)
         {
            echo " -> escaneando aleatoriamente " . $numero . " articulos: ";
            $avisos = 0;
            $errores = 0;
            $eliminados = 0;

            foreach($articulos as $col)
            {
               /// comprobamos el articulo
               switch($this->check_articulo($col))
               {
                  case 1:
                     $avisos++;
                     break;

                  case 2:
                     $errores++;
                     break;

                  case 3:
                     $eliminados++;
                     break;
               }
            }

            if($eliminados)
            {
               $this->escribe_log("aviso", "scan_articulos: " . $eliminados . " eliminados.");
            }

            echo $avisos . " avisos, " . $errores . " errores, " . $eliminados . " eliminados.\n";
         }
      }
      else
      {
         echo " -> imposible chequear articulos, no hay sufucientes.\n";
      }

      /*
       *  Por ultimo eliminamos el registro de movimientos de stock de años anteriores.
       *  Ejecutamos esto el 10% de las veces del primer dia del año
       */
      if( date("j-n") == "1-1" AND rand(0,9) == 9 )
      {
         if($this->bd->exec("DELETE FROM lineasregstocks WHERE fecha < '1-1-" . date("Y") . "';"))
         {
            $this->escribe_log("aviso", "scan_articulos: Se ha eliminado autom&aacute;ticamente el historial de movimientos de stock de años anteriores.");
         }
         else
         {
            $this->escribe_log("error", "scan_articulos: Error al eliminar el historial de movimientos de stock de años anteriores.");
         }
      }
   }

   /*
    * Comprueba el articulo, devuelve un entero.
    * 0 = correcto.
    * 1 = aviso.
    * 2 = errores.
    * 3 = eliminado.
    */
   private function check_articulo($articulo)
   {
      $retorno = 0;

      $stock = $this->bd->select("SELECT SUM(cantidad) as total FROM stocks WHERE referencia = '" . $articulo['referencia'].
                                 "' GROUP BY referencia;");

      /// ¿tiene stock el articulo?
      if($stock)
      {
         /// ¿es correcto el stock?
         if($articulo['stockfis'] != $stock[0]['total'])
         {
            if($this->bd->exec("UPDATE articulos SET stockfis = (SELECT GREATEST(SUM(cantidad), 0) FROM stocks WHERE referencia = '" . $articulo['referencia'] . "'
               GROUP BY referencia) WHERE referencia = '" . $articulo['referencia'] . "';"))
            {
               $this->escribe_log("aviso","scan_articulos: arreglados errores de stock en art&iacute;culo <a href=\"ppal.php?mod=principal&amp;pag=articulo&amp;ref=".
                       $articulo['referencia'] . "\">" . $articulo['referencia'] . "</a>.");

               $retorno = 1;
            }
            else
            {
               $this->escribe_log("error","scan_articulos: error al corregir el stock del art&iacute;culo <a href=\"ppal.php?mod=principal&amp;pag=articulo&amp;ref=".
                       $articulo['referencia'] . "\">" . $articulo['referencia'] . "</a>.");

               $retorno = 2;
            }
         }
         else if($articulo['stockfis'] < 0) /// ¿es negativo el stock?
         {
            if($this->bd->exec("UPDATE stocks SET cantidad = 0 WHERE referencia = '" . $articulo['referencia'] . "' AND cantidad < 0;
               UPDATE articulos SET stockfis = (SELECT SUM(cantidad) as total FROM stocks WHERE referencia = '" . $articulo['referencia'] . "'
               GROUP BY referencia) WHERE referencia = '" . $articulo['referencia'] . "';"))
            {
               $this->escribe_log("aviso","scan_articulos: arreglados errores de stock en art&iacute;culo <a href=\"ppal.php?mod=principal&amp;pag=articulo&amp;ref=".
                       $articulo['referencia'] . "\">" . $articulo['referencia'] . "</a>.");

               $retorno = 1;
            }
            else
            {
               $this->escribe_log("error","scan_articulos: error al corregir el stock del art&iacute;culo <a href=\"ppal.php?mod=principal&amp;pag=articulo&amp;ref=".
                       $articulo['referencia'] . "\">" . $articulo['referencia'] . "</a>.");

               $retorno = 2;
            }
         }

         /// ¿esta incorrectamente destacado?
         if($articulo['destacado'] == 't' AND ($articulo['equivalencia'] == "" OR $articulo['bloqueado'] == 't'))
         {
            $error = "";
            $a_temp = false;

            if( $this->articulos->get($articulo['referencia'], $a_temp) )
            {
               if( $this->articulos->update_articulo($a_temp, $error) )
               {
                  $this->escribe_log("aviso","scan_articulos: arreglados errores en art&iacute;culo <a href=\"ppal.php?mod=principal&amp;pag=articulo&amp;ref=".
                          $articulo['referencia'] . "\">" . $articulo['referencia'] . "</a>.");

                  $retorno = 1;
               }
               else
               {
                  /// convertimos los caracteres especiales
                  $error = htmlspecialchars($error, ENT_QUOTES);

                  $this->escribe_log("error","scan_articulos: errores en art&iacute;culo <a href=\"ppal.php?mod=principal&amp;pag=articulo&amp;ref=".
                          $articulo['referencia'] . "\">" . $articulo['referencia'] . "</a>: " . $error);

                  $retorno = 2;
               }
            }
            else
            {
               $this->escribe_log("error","scan_articulos: errores en art&iacute;culo <a href=\"ppal.php?mod=principal&amp;pag=articulo&amp;ref=".
                       $articulo['referencia'] . "\">" . $articulo['referencia'] . "</a>.");

               $retorno = 2;
            }
         }
      }

      return($retorno);
   }

   private function escribe_log($etiqueta, $texto)
   {
      $retorno = false;

      if($etiqueta != '' AND $texto != '')
      {
         $fecha = date('c');

         $retorno = $this->bd->exec("INSERT INTO fs_mensajes (tipo,etiqueta,fecha,texto) VALUES ('auto','$etiqueta','$fecha','$texto');");
      }

      return($retorno);
   }

   private function scan_albaranes()
   {
      echo " -> comprobando albaranes: ";
      
      $total = 0;

      /// reparamos los albaranes que apuntan a facturas que no existen
      $this->albaranes_cli->reparar_enlaces_facturas();

      /// reparamos los albaranes que apuntan a facturas que no existen
      $this->albaranes_prov->reparar_enlaces_facturas();

      /// marcamos como revisados los albaranes de cliente ya facturados
      if( $this->albaranes_cli->revisar_pendientes($total) )
      {
         $this->escribe_log("aviso","scan_albaranes: Se han marcado autom&aacute;ticamente como revisados <b>" . number_format($total,0).
                 "</b> albaranes (de clientes) ya facturados.");
      }
      
      /// marcamos como revisados los albaranes de proveedor ya facturados
      if( $this->albaranes_prov->revisar_pendientes($total) )
      {
         $this->escribe_log("aviso","scan_albaranes: Se han marcado autom&aacute;ticamente como revisados <b>" . number_format($total,0).
                 "</b> albaranes (de proveedores) ya facturados.");
      }


      /// comprobamos los albaranes erroneos
      $erroneos = false;
      $total = 0;
      
      /// comprobamos los albaranes de clientes
      $erroneos = $this->albaranes_cli->descuadrados();
      if($erroneos)
      {
         foreach($erroneos as $alb)
         {
            $this->escribe_log('aviso', "scan_albaranes: hay que recalcular el albar&aacute;n (cliente):
               <a href=\"ppal.php?mod=principal&amp;pag=albarancli&amp;id=" . $alb['idalbaran'] . "\">" . $alb['codigo'] . "</a>.");

            $total++;
         }
      }

      /// comprobamos los albaranes de proveedor
      $erroneos = $this->albaranes_prov->descuadrados();
      if($erroneos)
      {
         foreach($erroneos as $alb)
         {
            $this->escribe_log("aviso", "scan_albaranes: hay que recalcular el albar&aacute;n (proveedor):
               <a href=\"ppal.php?mod=principal&amp;pag=albaranprov&amp;id=" . $alb['idalbaran'] . "\">" . $alb['codigo'] . "</a>.");

            $total++;
         }
      }

      echo ' ' , $total , " errores encontrados.\n";
   }

   private function scan_facturas()
   {
      echo " -> buscando facturas con errores: ";

      $errores = 0;

      /// clientes
      $facturas = $this->facturas_cli->get_badasientos();
      if($facturas)
      {
         foreach($facturas as $col)
         {
            $this->escribe_log("aviso","scan_facturas: detectado un <a href=\"ppal.php?mod=contabilidad&amp;pag=asiento&amp;id=".
                    $col['idasiento'] . "\">asiento</a> que apunta a una factura de cliente equivocada (apunta a la num. ".
                    $col['factura'] . " en lugar de a " . $col['codigo'] . ").");

            $errores++;
         }
      }

      $facturas = $this->facturas_cli->errores_lineas();
      if($facturas)
      {
         foreach($facturas as $col)
         {
            $this->escribe_log("error","scan_facturas: hay errores de c&aacute;lculo en la factura (cliente)
               <a href=\"ppal.php?mod=contabilidad&amp;pag=facturacli&amp;id=" . $col['idfactura'] . "\">" . $col['codigo'] . "</a>.");

            $errores++;
         }
      }

      $facturas = $this->facturas_cli->errores_lineasiva();
      if($facturas)
      {
         foreach($facturas as $col)
         {
            $this->escribe_log("error","scan_facturas: hay errores de c&aacute;lculo del IVA en la factura (cliente)
               <a href=\"ppal.php?mod=contabilidad&amp;pag=facturacli&amp;id=" . $col['idfactura'] . "\">" . $col['codigo'] . "</a>.");

            $errores++;
         }
      }

      /// proveedores
      $facturas = $this->facturas_prov->get_badasientos();
      if($facturas)
      {
         foreach($facturas as $col)
         {
            $this->escribe_log("aviso","scan_facturas: detectado un <a href=\"ppal.php?mod=contabilidad&amp;pag=asiento&amp;id=".
                    $col['idasiento'] . "\">asiento</a> que apunta a una factura de cliente equivocada (apunta a la num. ".
                    $col['factura'] . " en lugar de a " . $col['codigo'] . ").");

            $errores++;
         }
      }

      $facturas = $this->facturas_prov->errores_lineas();
      if($facturas)
      {
         foreach($facturas as $col)
         {
            $this->escribe_log("error","scan_facturas: hay errores de c&aacute;lculo en la factura (proveedor)
               <a href=\"ppal.php?mod=contabilidad&amp;pag=facturaprov&amp;id=" . $col['idfactura'] . "\">" . $col['codigo'] . "</a>.");

            $errores++;
         }
      }

      $facturas = $this->facturas_prov->errores_lineasiva();
      if($facturas)
      {
         foreach($facturas as $col)
         {
            $this->escribe_log("error","scan_facturas: hay errores de c&aacute;lculo del IVA en la factura (proveedor)
               <a href=\"ppal.php?mod=contabilidad&amp;pag=facturaprov&amp;id=" . $col['idfactura'] . "\">" . $col['codigo'] . "</a>.");

            $errores++;
         }
      }

      echo $errores . " errores encontrados.\n";
   }

   private function scan_cuentas()
   {
      echo " -> buscando errores en las cuentas: ";
      $total = 0;
      $error = false;

      /// buscamos errores en los debe, haber o saldos de las subcuentas
      $errores = $this->cuentas->errores_saldos();
      if($errores)
      {
         foreach($errores as $col)
         {
            $this->escribe_log("error","scan_cuentas: la subcuenta <a href=\"ppal.php?mod=contabilidad&pag=cuenta&tipo=s&id=".
                    $col['idsubcuenta'] . "\">ID = " . $col['idsubcuenta'] . "</a> contiene errores en el debe, haber o saldo.");

            $total++;
         }
      }

      /// buscamos subcuentas que apunten a cuentas que no existen o con errores en el campo codcuenta
      $errores = $this->cuentas->errores_subcuentas();
      if($errores)
      {
         foreach($errores as $col)
         {
            if( $this->cuentas->repara_subcuenta($col, $error) )
            {
               $this->escribe_log("aviso","scan_cuentas: corregida la subcuenta <a href=\"ppal.php?mod=contabilidad&pag=cuenta&tipo=s&id=".
                       $col['idsubcuenta'] . "\">" . $col['codsubcuenta'] . "</a>.");
            }
            else
            {
               $this->escribe_log("error","scan_cuentas: la subcuenta <a href=\"ppal.php?mod=contabilidad&pag=cuenta&tipo=s&id=".
                       $col['idsubcuenta'] . "\">" . $col['codsubcuenta'] . "</a> apunta a una cuenta que no existe. " . $error);
            }

            $total++;
         }
      }

      /// buscamos errores en los epigrafes
      $errores = $this->cuentas->errores_epigrafes();
      if($errores)
      {
         foreach($errores as $col)
         {
            $this->escribe_log("error","scan_cuentas: la cuenta <a href=\"ppal.php?mod=contabilidad&pag=cuenta&tipo=c&id=".
                    $col['idcuenta'] . "\">" . $col['codcuenta'] . "</a> no est&aacute; asociada a ning&uacute;n ep&iacute;grafe.");

            $total++;
         }
      }

      echo $total . " errores encontrados.\n";
   }

   private function scan_asientos()
   {
      echo " -> buscando asientos descuadrados: ";

      $asientos = false;
      $errores = 0;
      if( $this->asientos->descuadrados($asientos) )
      {
         foreach($asientos as $col)
         {
            $this->escribe_log("error","scan_asientos: asiento <a href=\"ppal.php?mod=contabilidad&pag=asiento&id=".
                    $col['idasiento'] . "\">ID = " . $col['idasiento'] . "</a> descuedrado.");

            $errores++;
         }
      }

      echo $errores . " errores encontrados.\n";
   }

   private function renumerar_asientos()
   {
      echo " -> renumerando asientos ";

      $posicion = 0;
      $numero = 1;
      $codejercicio = false;
      $renumeracion = "";
      $continuar = true;
      $consulta = "SELECT idasiento,codejercicio,numero,fecha FROM co_asientos
         ORDER BY codejercicio ASC, fecha ASC, idasiento ASC";

      $asientos = $this->bd->select_limit($consulta, 1000, $posicion);

      while($asientos AND $continuar)
      {
         echo ".";

         foreach($asientos as $col)
         {
            /// reseteamos en cada ejercicio
            if($col['codejercicio'] != $codejercicio)
            {
               $codejercicio = $col['codejercicio'];
               $numero = 1;
            }

            if($col['numero'] != $numero)
            {
               $renumeracion .= "UPDATE co_asientos SET numero = '$numero' WHERE idasiento = '" . $col['idasiento'] . "';";
            }

            $numero++;
         }

         $posicion += 1000;
            
         if($renumeracion != "")
         {
            if(!$this->bd->exec($renumeracion))
            {
               $this->escribe_log("error","renumerar_asientos: se ha producido un error mientras se renumeraba el ejercicio $codejercicio.");

               $continuar = false;
            }
         }

         $asientos = $this->bd->select_limit($consulta, 1000, $posicion);
      }

      if($continuar)
      {
         $this->escribe_log("aviso","renumerar_asientos: Renumeraci&oacute;n de asientos completada.");
      }

      echo "\n";
   }

   private function informe_semanal()
   {
      $ejercicios = false;

      /// obtenemos la lista de ejercicios
      if( $this->ejercicios->all($ejercicios) )
      {
         $this->informe_articulos();

         foreach($ejercicios as $ejercicio)
         {
            $this->informe_albaranes_cli($ejercicio);
            $this->informe_albaranes_prov($ejercicio);
            $this->informe_facturas_cli($ejercicio);
            $this->informe_facturas_prov($ejercicio);
         }
      }
   }
   
   private function informe_articulos()
   {
      $id = false;

      /// buscamos si existe una entrada para esta fecha
      if( $this->existe_informe("1-" . Date("m-Y"), $id) )
      {
         $consulta = "set datestyle = dmy; UPDATE fs_informes SET ";
         $consulta .= "nart = '" . $this->articulos->total() . "',";
         $consulta .= "nartu = '" . $this->articulos->total_actualizados("1-" . Date("m-Y")) . "',";
         $consulta .= "nstock = '" . $this->articulos->total_stock() . "',";
         $consulta .= "eurstock = '" . $this->articulos->total_euros_stock() . "' ";
         $consulta .= "WHERE id = '" . $id . "';";
      }
      else
      {
         $consulta = "set datestyle = dmy; INSERT INTO fs_informes (fecha, nart, nartu, nstock, eurstock) VALUES (";
         $consulta .= "'1-" . Date("m-Y") . "',";
         $consulta .= "'" . $this->articulos->total() . "',";
         $consulta .= "'" . $this->articulos->total_actualizados("1-" . Date("m-Y")) . "',";
         $consulta .= "'" . $this->articulos->total_stock() . "',";
         $consulta .= "'" . $this->articulos->total_euros_stock() . "');";
      }

      if( !$this->bd->exec($consulta) )
      {
         $this->escribe_log("error","informe_articulos: se ha producido un error al ejecutar la consulta.\n" . $consulta);
      }
   }

   private function informe_albaranes_cli($ejercicio)
   {
      /// obtenemos el numero de albaranes
      $resultado = $this->bd->select("set datestyle = dmy;
         SELECT to_char(fecha,'mm-yyyy') as mes, count(idalbaran) as albaranes, sum(total) as total
         FROM albaranescli WHERE fecha >= '" . $ejercicio['fechainicio'] . "' AND fecha <= '" . $ejercicio['fechafin'] . "'
         GROUP BY to_char(fecha,'mm-yyyy') ORDER BY mes ASC;");

      if($resultado)
      {
         foreach($resultado as $col)
         {
            if( $this->existe_informe("1-" . $col['mes'], $id) )
            {
               $consulta = "set datestyle = dmy; UPDATE fs_informes SET ";
               $consulta .= "nalbcli = '" . $col['albaranes'] . "',";
               $consulta .= "euralbcli = '" . $col['total'] . "' ";
               $consulta .= "WHERE id = '" . $id . "';";
            }
            else
            {
               $consulta = "set datestyle = dmy; INSERT INTO fs_informes (fecha, nalbcli, euralbcli) VALUES (";
               $consulta .= "'1-" . $col['mes'] . "',";
               $consulta .= "'" . $col['albaranes'] . "',";
               $consulta .= "'" . $col['total'] . "');";
            }

            if( !$this->bd->exec($consulta) )
            {
               $this->escribe_log("error","informe_albaranes_cli: se ha producido un error al ejecutar la consulta.\n" . $consulta);
            }
         }
      }
   }

   private function informe_albaranes_prov($ejercicio)
   {
      /// obtenemos el numero de albaranes
      $resultado = $this->bd->select("set datestyle = dmy;
         SELECT to_char(fecha,'mm-yyyy') as mes, count(idalbaran) as albaranes, sum(total) as total
         FROM albaranesprov WHERE fecha >= '" . $ejercicio['fechainicio'] . "' AND fecha <= '" . $ejercicio['fechafin'] . "'
         GROUP BY to_char(fecha,'mm-yyyy') ORDER BY mes ASC;");

      if($resultado)
      {
         foreach($resultado as $col)
         {
            if( $this->existe_informe("1-" . $col['mes'], $id) )
            {
               $consulta = "set datestyle = dmy; UPDATE fs_informes SET ";
               $consulta .= "nalbprov = '" . $col['albaranes'] . "',";
               $consulta .= "euralbprov = '" . $col['total'] . "' ";
               $consulta .= "WHERE id = '" . $id . "';";
            }
            else
            {
               $consulta = "set datestyle = dmy; INSERT INTO fs_informes (fecha, nalbprov, euralbprov) VALUES (";
               $consulta .= "'1-" . $col['mes'] . "',";
               $consulta .= "'" . $col['albaranes'] . "',";
               $consulta .= "'" . $col['total'] . "');";
            }

            if( !$this->bd->exec($consulta) )
            {
               $this->escribe_log("error","informe_albaranes_prov: se ha producido un error al ejecutar la consulta.\n" . $consulta);
            }
         }
      }
   }

   private function informe_facturas_cli($ejercicio)
   {
      /// obtenemos el numero de albaranes
      $resultado = $this->bd->select("set datestyle = dmy;
         SELECT to_char(fecha,'mm-yyyy') as mes, count(idfactura) as facturas, sum(total) as total
         FROM facturascli WHERE fecha >= '" . $ejercicio['fechainicio'] . "' AND fecha <= '" . $ejercicio['fechafin'] . "'
         GROUP BY to_char(fecha,'mm-yyyy') ORDER BY mes ASC;");

      if($resultado)
      {
         foreach($resultado as $col)
         {
            if( $this->existe_informe("1-" . $col['mes'], $id) )
            {
               $consulta = "set datestyle = dmy; UPDATE fs_informes SET ";
               $consulta .= "nfactcli = '" . $col['facturas'] . "',";
               $consulta .= "eurfactcli = '" . $col['total'] . "' ";
               $consulta .= "WHERE id = '" . $id . "';";
            }
            else
            {
               $consulta = "set datestyle = dmy; INSERT INTO fs_informes (fecha, nfactcli, eurfactcli) VALUES (";
               $consulta .= "'1-" . $col['mes'] . "',";
               $consulta .= "'" . $col['facturas'] . "',";
               $consulta .= "'" . $col['total'] . "');";
            }

            if( !$this->bd->exec($consulta) )
            {
               $this->escribe_log("error","informe_facturas_cli: se ha producido un error al ejecutar la consulta.\n" . $consulta);
            }
         }
      }
   }

   private function informe_facturas_prov($ejercicio)
   {
      /// obtenemos el numero de albaranes
      $resultado = $this->bd->select("set datestyle = dmy;
         SELECT to_char(fecha,'mm-yyyy') as mes, count(idfactura) as facturas, sum(total) as total
         FROM facturasprov WHERE fecha >= '" . $ejercicio['fechainicio'] . "' AND fecha <= '" . $ejercicio['fechafin'] . "'
         GROUP BY to_char(fecha,'mm-yyyy') ORDER BY mes ASC;");

      if($resultado)
      {
         foreach($resultado as $col)
         {
            if( $this->existe_informe("1-" . $col['mes'], $id) )
            {
               $consulta = "set datestyle = dmy; UPDATE fs_informes SET ";
               $consulta .= "nfactprov = '" . $col['facturas'] . "',";
               $consulta .= "eurfactprov = '" . $col['total'] . "' ";
               $consulta .= "WHERE id = '" . $id . "';";
            }
            else
            {
               $consulta = "set datestyle = dmy; INSERT INTO fs_informes (fecha, nfactprov, eurfactprov) VALUES (";
               $consulta .= "'1-" . $col['mes'] . "',";
               $consulta .= "'" . $col['facturas'] . "',";
               $consulta .= "'" . $col['total'] . "');";
            }

            if( !$this->bd->exec($consulta) )
            {
               $this->escribe_log("error","informe_facturas_prov: se ha producido un error al ejecutar la consulta.\n" . $consulta);
            }
         }
      }
   }

   private function existe_informe($fecha, &$id)
   {
      $retorno = false;

      /// buscamos si existe una entrada para esta fecha
      $resultado = $this->bd->select("set datestyle = dmy; SELECT id FROM fs_informes WHERE fecha = '$fecha';");
      if($resultado)
      {
         $retorno = true;
         $id = $resultado[0]['id'];
      }

      return($retorno);
   }
}

?>
