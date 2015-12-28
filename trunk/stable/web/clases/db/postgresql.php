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

require("clases/db/db.php");

class db extends db_a
{
   /// abre una conexion con la base de datos
   public function conectar()
   {
      if( !parent::$enlace )
      {
         parent::$enlace = pg_pconnect('host=' . FS_HOST . ' dbname=' . FS_DB_NAME . ' port=' . FS_DB_PORT.
                 ' user=' . FS_USERDB . ' password=' . FS_PASSDB);
      }
      
      return parent::$enlace;
   }
   
   /// cierra una conexion con la base de datos
   public function desconectar()
   {
      if(parent::$enlace)
         return pg_close(parent::$enlace);
      else
         return FALSE;
   }

   /// ejecuta un select sobre la base de datos
   public function select($consulta)
   {
      if(parent::$enlace)
      {
         $filas = pg_query(parent::$enlace, $consulta);
         if($filas)
         {
            $resultado = pg_fetch_all($filas);
            pg_free_result($filas);
         }
         else
            $resultado = FALSE;
         
         parent::$t_selects++;
         $this->add2history($consulta);
         
         return $resultado;
      }
      else
         return FALSE;
   }

   /// ejecuta un select (con paginacion de resultados) sobre la base de datos
   public function select_limit($consulta, $limit=FS_LIMITE, $offset=0)
   {
      if(parent::$enlace)
      {
         $filas = pg_query(parent::$enlace, $consulta . ' LIMIT ' . $limit . ' OFFSET ' . $offset . ';');
         if($filas)
         {
            $resultado = pg_fetch_all($filas);
            pg_free_result($filas);
         }
         else
            $resultado = FALSE;
         
         parent::$t_selects++;
         $this->add2history($consulta . ' LIMIT ' . $limit . ' OFFSET ' . $offset . ';');
         
         return $resultado;
      }
      else
         return FALSE;
   }

   /// devuelve el num. total de filas de una consulta
   function num_rows($consulta)
   {
      if(parent::$enlace)
      {
         $filas = pg_query(parent::$enlace, $consulta);
         if($filas)
         {
            $total = intval( pg_num_rows($filas) );
            pg_free_result($filas);
         }
         else
            $total = 0;
         
         parent::$t_selects++;
         $this->add2history($consulta);
         
         return $total;
      }
      else
         return 0;
   }

   /// ejecuta una consulta sobre la base de datos
   public function exec($consulta)
   {
      if(parent::$enlace)
      {
         /// iniciamos la transacción
         pg_query(parent::$enlace, 'BEGIN TRANSACTION;');

         /// realizar una consulta SQL
         if( pg_query(parent::$enlace, $consulta) )
         {
            pg_query(parent::$enlace, 'COMMIT;');
            $resultado = TRUE;
         }
         else
         {
            pg_query(parent::$enlace, 'ROLLBACK;');
            $resultado = FALSE;
         }
         
         parent::$t_transacciones++;
         $this->add2history($consulta);
         
         return $resultado;
      }
      else
         return FALSE;
   }

   /// devuelve la lista de tablas de la base de datos
   public function list_tables()
   {
      if(parent::$enlace)
      {
         $sql = "SELECT a.relname AS Name FROM pg_class a, pg_user b WHERE ( relkind = 'r') and relname !~ '^pg_' AND relname !~ '^sql_'
            AND relname !~ '^xin[vx][0-9]+' AND b.usesysid = a.relowner AND NOT (EXISTS (SELECT viewname FROM pg_views WHERE viewname=a.relname))
            ORDER BY a.relname ASC;";
         $filas = pg_query(parent::$enlace, $sql);
         if($filas)
         {
            $resultado = pg_fetch_all($filas);
            pg_free_result($filas);
         }
         else
            $resultado = FALSE;
         
         parent::$t_selects++;
         $this->add2history($sql);
         
         return $resultado;
      }
      else
         return array();
   }

   /// devuelve true si existe la tabla especificada, false en caso contrario
   public function existe_tabla($nombre)
   {
      $tablas = $this->list_tables();
      if($tablas)
      {
         $encontrada = FALSE;
         foreach($tablas as $tabla)
         {
            if($tabla['name'] == $nombre)
            {
               $encontrada = TRUE;
               break;
            }
         }
         
         return $encontrada;
      }
      else
         return FALSE;
   }

   /// devuleve la version del servidor de postgresql
   public function version()
   {
      if(parent::$enlace)
         return pg_version(parent::$enlace);
      else
         return FALSE;
   }
}

?>