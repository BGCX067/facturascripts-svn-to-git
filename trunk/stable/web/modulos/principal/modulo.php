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
       parent::__construct("principal", "Principal", "0.8.5", "Art&iacute;culos, tarifas, reservas, albaranes...");

       $this->scripts = Array(
          0 => Array(
             'enlace' => "familias",
             'titulo' => "Familias"
          ),
          1 => Array(
             'enlace' => "familia",
             'titulo' => ""
          ),
          2 => Array(
             'enlace' => "articulos",
             'titulo' => "Art&iacute;culos"
          ),
          3 => Array(
             'enlace' => "articulo",
             'titulo' => ""
          ),
          4 => Array(
             'enlace' => "carrito",
             'titulo' => "Carrito"
          ),
          5 => Array(
             'enlace' => "clientes",
             'titulo' => "Clientes"
          ),
          6 => Array(
             'enlace' => "cliente",
             'titulo' => ""
          ),
          7 => Array(
             'enlace' => "albaranescli",
             'titulo' => "Albaranes de Cliente"
          ),
          8 => Array(
             'enlace' => "albarancli",
             'titulo' => ""
          ),
          9 => Array(
             'enlace' => "proveedores",
             'titulo' => "Proveedores"
          ),
          10 => Array(
             'enlace' => "proveedor",
             'titulo' => ""
          ),
          11 => Array(
             'enlace' => "albaranesprov",
             'titulo' => "Albaranes de Proveedor"
          ),
          12 => Array(
             'enlace' => "albaranprov",
             'titulo' => ""
          ),
          13 => Array(
             'enlace' => "agentes",
             'titulo' => "Agentes"
          ),
          14 => Array(
             'enlace' => "equivalencias",
             'titulo' => "Equivalencias"
          ),
          15 => Array(
             'enlace' => "tarifas2",
             'titulo' => "Tarifas"
          ),
          16 => Array(
             'enlace' => "informes",
             'titulo' => "Informes"
          )
       );
    }
}

?>
