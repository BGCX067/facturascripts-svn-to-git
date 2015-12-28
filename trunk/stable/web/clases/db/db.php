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

abstract class db_a
{
   protected static $enlace;
   protected static $t_selects;
   protected static $t_transacciones;
   protected static $sql_history;
   
   public function __construct()
   {
      if( !self::$enlace )
      {
         self::$t_selects = 0;
         self::$t_transacciones = 0;
         self::$sql_history = array();
      }
   }
   
   public function get_selects()
   {
      return self::$t_selects;
   }
   
   public function get_transacciones()
   {
      return self::$t_transacciones;
   }
   
   public function get_history()
   {
      return self::$sql_history;
   }
   
   public function add2history($sql)
   {
      self::$sql_history[] = $sql;
   }
   
   /// abre una conexion con la base de datos
   abstract public function conectar();

   /// cierra una conexion con la base de datos
   abstract public function desconectar();

   /// ejecuta un select sobre la base de datos
   abstract public function select($consulta);

   /// ejecuta un select (con paginacion de resultados) sobre la base de datos
   abstract public function select_limit($consulta, $limit, $offset);

   /// devuelve el num. total de filas de una consulta
   abstract function num_rows($consulta);

   /// ejecuta una consulta sobre la base de datos
   abstract public function exec($consulta);

   /// devuelve la lista de tablas de la base de datos
   abstract public function list_tables();

   /// devuelve true si existe la tabla especificada, false en caso contrario
   abstract public function existe_tabla($nombre);

   /// devuleve la version del servidor de postgresql
   abstract public function version();

   /// obtiene la version actual de la tabla dada
   public function version_tabla($tabla)
   {
      $resultado = $this->select("SELECT version FROM fs_tablas WHERE nombre='$tabla';");
      if($resultado)
         return $resultado[0]['version'];
      else
         return FALSE;
   }
}

?>