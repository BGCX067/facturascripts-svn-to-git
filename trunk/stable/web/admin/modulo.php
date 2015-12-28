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

require_once("clases/modulo.php");

class modulo_ extends modulo
{
    public function __construct()
    {
       parent::__construct("admin", "Administraci&oacute;n", "0.8.5", "Administraci&oacute;n");

       $this->scripts = Array(
          0 => Array(
             'enlace' => "modulos",
             'titulo' => "M&oacute;dulos"
          ),
          1 => Array(
             'enlace' => "usuarios",
             'titulo' => "Usuarios"
          ),
          2 => Array(
             'enlace' => "upgradedb2",
             'titulo' => "Actualizar BD"
          ),
          3 => Array(
             'enlace' => "opciones",
             'titulo' => "Opciones"
          ),
          4 => Array(
             'enlace' => "automata",
             'titulo' => "Automata"
          ),
          5 => Array(
             'enlace' => "problemas",
             'titulo' => "Problemas"
          )
       );
    }
}

?>
