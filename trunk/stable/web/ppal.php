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

require('config.php');
require('clases/db/postgresql.php');

/// Capturamos todas las variables necesarias
if( isset($_GET['mod']) )
   $mod = $_GET['mod'];
else
   $mod = '';

if( isset($_GET['pag']) )
   $pag = $_GET['pag'];
else
   $pag = 'home';

switch($mod)
{
   case '':
      /// cargamos un script en blanco
      require('clases/script.php');
      $mi_script = new script('ppal');
      break;

   case 'admin':
      if( file_exists('admin/' . $pag . '.php') )
      {
         require('admin/' . $pag . '.php');
         $mi_script = new script_('ppal');
      }
      else
      {
         /// cargamos un script en blanco
         require('clases/script.php');
         $mi_script = new script('ppal');
      }
      break;

   default:
      if( file_exists('modulos/' . $mod . '/' . $pag . '.php') )
      {
         require('modulos/' . $mod . '/' . $pag . '.php');
         $mi_script = new script_('ppal');
      }
      else
      {
         /// cargamos un script en blanco
         require('clases/script.php');
         $mi_script = new script('ppal');
      }
      break;
}

/// comprobamos si hay enlace con la base de datos
if( $mi_script->enlace_db() )
{
   /// autenticamos al usuario
   if( $mi_script->login($mod) )
   {
      $datos = $mi_script->datos();
      $mi_script->cabecera();
      $mi_script->menu($mod, $pag);
      $mi_script->cuerpo($mod, $pag, $datos);
      $mi_script->pie();
   }
   else
      $mi_script->acceso_denegado();
}
else
   $mi_script->error_db();

?>