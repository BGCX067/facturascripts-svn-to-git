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

abstract class modulo
{
   protected $bd;
   protected $sysv;
   protected $nombre;
   protected $titulo;
   protected $version;
   protected $comentario;
   protected $scripts;

   public function __construct($nombre, $titulo, $version, $comentario)
   {
      $this->bd = new db();

      /// version de facturascripts
      $this->sysv = "0.8.5";

      $this->nombre = $nombre;
      $this->titulo = $titulo;
      $this->version = $version;
      $this->comentario = $comentario;

      $this->scripts = false;

      /// actualizamos facturascripts
      $this->actualiza_sys();
   }

   public function get_nombre()
   {
      return( $this->nombre );
   }

   private function actualiza_sys()
   {
      $resultado = true;
      $consulta = "";

      $resultado = $this->bd->select("SELECT modulo, version FROM fs_modulos WHERE modulo = 'sys';");
      if($resultado)
      {
         if($resultado[0]['version'] != $this->sysv)
         {
            $consulta = "UPDATE fs_modulos SET version = '" . $this->sysv . "' WHERE modulo = 'sys';";
         }
      }
      else
      {
         $consulta = "INSERT INTO fs_modulos (modulo, version, comentario)
            VALUES ('sys','" . $this->sysv . "','M&oacute;dulo principal de facturaScripts');";
      }

      if($consulta != "")
      {
         if( !$this->bd->exec($consulta) )
         {
            $resultado = false;
         }
      }

      return($resultado);
   }

   public function actualizar()
   {
      $retorno = true;
      $consulta = "";

      $resultado = $this->bd->select("SELECT modulo, version FROM fs_modulos WHERE modulo = '" . $this->nombre . "';");
      if($resultado)
      {
         if($resultado[0]['version'] != $this->version)
         {
            $consulta = "UPDATE fs_modulos SET version = '" . $this->version . "', titulo = '" . $this->titulo . "', comentario = '" . $this->comentario . "'
               WHERE modulo = '" . $this->nombre . "';";
         }
      }
      else
      {
         $consulta = "INSERT INTO fs_modulos (modulo, version, titulo, comentario)
            VALUES ('" . $this->nombre . "','" . $this->version . "','" . $this->titulo . "','" . $this->comentario . "');";
      }

      if( $this->scripts )
      {
         // Borramos todas las entradas del menu del modulo
         $consulta .= "DELETE FROM fs_menu WHERE modulo = '" . $this->nombre . "';";
         
         foreach($this->scripts as $col)
         {
            $consulta .= "INSERT INTO fs_menu (modulo, titulo, enlace) VALUES ('" . $this->nombre . "','" . $col['titulo'] . "','" . $col['enlace'] . "');";
         }
      }

      if($consulta != "")
      {
         if( !$this->bd->exec($consulta) )
         {
            $retorno = false;
         }
      }

      return($retorno);
   }

   public function borrar()
   {
      $resultado = true;

      if( !$this->bd->exec("DELETE FROM fs_modulos WHERE modulo = '" . $this->nombre . "';") )
         $resultado = false;

      return($resultado);
   }
}

?>
