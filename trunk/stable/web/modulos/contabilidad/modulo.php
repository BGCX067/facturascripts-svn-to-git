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
       parent::__construct("contabilidad", "Contabilidad", "0.8.5", "Asientos, facturas ...");

       $this->scripts = Array(
          0 => Array(
             'enlace' => "asientos",
             'titulo' => "Asientos"
          ),
          1 => Array(
             'enlace' => "cuentas",
             'titulo' => "Cuentas"
          ),
          2 => Array(
             'enlace' => "clientes",
             'titulo' => "Clientes"
          ),
          3 => Array(
             'enlace' => "cliente",
             'titulo' => ""
          ),
          4 => Array(
             'enlace' => "albaranescli",
             'titulo' => "Albaranes de Cliente"
          ),
          5 => Array(
             'enlace' => "albarancli",
             'titulo' => ""
          ),
          6 => Array(
             'enlace' => "facturascli",
             'titulo' => "Facturas de Cliente"
          ),
          7 => Array(
             'enlace' => "proveedores",
             'titulo' => "Proveedores"
          ),
          8 => Array(
             'enlace' => "proveedor",
             'titulo' => ""
          ),
          9 => Array(
             'enlace' => "albaranesprov",
             'titulo' => "Albaranes de Proveedor"
          ),
          10 => Array(
             'enlace' => "albaranprov",
             'titulo' => ""
          ),
          11 => Array(
             'enlace' => "facturasprov",
             'titulo' => "Facturas de Proveedor"
          ),
          12 => Array(
             'enlace' => "informes",
             'titulo' => "Informes"
          )
       );
    }
}

?>
