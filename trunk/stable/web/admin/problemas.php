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

require("clases/script.php");

class script_ extends script
{
   public function __construct($ppal)
   {
      parent::__construct($ppal);
   }

   /// devuelve el titulo del script
   public function titulo()
   {
      return("Problemas");
   }

   /// carga el cuerpo del script
   public function cuerpo($mod, $pag, &$datos)
   {
      /// ¿purgamos?
      if( isset($_GET['purgar']) )
      {
         switch($_GET['purgar'])
         {
            case '1':
               if( $this->bd->exec("DELETE FROM albaranescli WHERE idalbaran IN (SELECT idalbaran FROM albaranescli
                   EXCEPT SELECT idalbaran FROM lineasalbaranescli ORDER BY idalbaran ASC);") )
               {
                  echo "<div class='mensaje'>Albaranes de cliente purgados correctamente</div>\n";
               }
               else
                  echo "<div class='error'>Error al purgar los albaranes de cliente</div>\n";
               break;
               
            case '2':
               if( $this->bd->exec("DELETE FROM albaranesprov WHERE idalbaran IN (SELECT idalbaran FROM albaranesprov
                   EXCEPT SELECT idalbaran FROM lineasalbaranesprov ORDER BY idalbaran ASC);") )
               {
                  echo "<div class='mensaje'>Albaranes de proveedor purgados correctamente</div>\n";
               }
               else
                  echo "<div class='error'>Error al purgar los albaranes de proveedor</div>\n";
               break;
         }
      }

      /// mostramos los bloqueos en la base de datos
      $this->leer_bloqueos();

      /// mostramos los albaranes
      $this->leer_problemas_albaranescli();
      $this->leer_problemas_albaranesprov();

      /// mostramos las facturas
      $this->leer_problemas_facturascli();
      $this->leer_problemas_facturasprov();
   }

   private function leer_bloqueos()
   {
      $bloqueos = $this->bd->select("SELECT relname,pg_locks.* FROM pg_class,pg_locks WHERE relfilenode=relation AND NOT granted;");

      if( $bloqueos )
      {
         echo "<div class='lista'>Bloqueos</div>\n",
            "<table class='lista'>\n",
            "<tr>\n",
            "<td>relname</td>\n",
            "<td>relation</td>\n",
            "<td>database</td>\n",
            "<td>transactionid</td>\n",
            "<td>pid</td>\n",
            "<td>mode</td>\n",
            "<td>granted</td>\n",
            "</tr>\n";

         foreach($bloqueos as $col)
         {
            echo "<tr>\n",
            "<td>" , $col['relname'] , "</td>\n",
            "<td>" , $col['relation'] , "</td>\n",
            "<td>" , $col['database'] , "</td>\n",
            "<td>" , $col['transacionid'] , "</td>\n",
            "<td>" , $col['pid'] , "</td>\n",
            "<td>" , $col['mode'] , "</td>\n",
            "<td>" , $col['granted'] , "</td>\n",
            "</tr>\n";
         }

         echo "</table>\n";
      }
   }

   private function leer_problemas_albaranescli()
   {
      $albaranes = $this->bd->select("SELECT idalbaran FROM albaranescli EXCEPT SELECT idalbaran FROM lineasalbaranescli ORDER BY idalbaran ASC;");
      if($albaranes)
      {
         echo "<div class='lista'>
            Albaranes de cliente sin l&iacute;neas &nbsp; [ <a href='ppal.php?mod=admin&amp;pag=problemas&amp;purgar=1'>purgar</a> ]
            </div>\n",
            "<ul class='horizontal'>\n";

         foreach($albaranes as $col)
            echo "<li><a href='ppal.php?mod=principal&amp;pag=albarancli&amp;id=" , $col['idalbaran'] , "'>" , $col['idalbaran'] , "</a></li>";

         echo "</ul>\n";
      }
   }

   private function leer_problemas_albaranesprov()
   {
      $albaranes = $this->bd->select("SELECT idalbaran FROM albaranesprov EXCEPT SELECT idalbaran FROM lineasalbaranesprov ORDER BY idalbaran ASC;");
      if($albaranes)
      {
         echo "<div class='lista'>
            Albaranes de proveedor sin l&iacute;neas &nbsp; [ <a href='ppal.php?mod=admin&amp;pag=problemas&amp;purgar=2'>purgar</a> ]
            </div>\n",
            "<ul class='horizontal'>\n";

         foreach($albaranes as $col)
            echo "<li><a href='ppal.php?mod=principal&amp;pag=albaranprov&amp;id=" , $col['idalbaran'] , "'>" , $col['idalbaran'] , "</a></li>";

         echo "</ul>\n";
      }
   }

   private function leer_problemas_facturascli()
   {
      $albaranes = $this->bd->select("SELECT idfactura FROM facturascli EXCEPT SELECT idfactura FROM lineasfacturascli ORDER BY idfactura ASC;");
      if($albaranes)
      {
         echo "<div class='lista'>Facturas de cliente sin l&iacute;neas</div>\n",
            "<ul class='horizontal'>\n";

         foreach($albaranes as $col)
            echo "<li><a href='ppal.php?mod=contabilidad&amp;pag=facturacli&amp;id=" , $col['idfactura'] , "'>" , $col['idfactura'] , "</a></li>";

         echo "</ul>\n";
      }
   }

   private function leer_problemas_facturasprov()
   {
      $albaranes = $this->bd->select("SELECT idfactura FROM facturasprov EXCEPT SELECT idfactura FROM lineasfacturasprov ORDER BY idfactura ASC;");
      if($albaranes)
      {
         echo "<div class='lista'>Facturas de proveedor sin l&iacute;neas</div>\n",
            "<ul class='horizontal'>\n";

         foreach($albaranes as $col)
            echo "<li><a href='ppal.php?mod=contabilidad&amp;pag=facturaprov&amp;id=" , $col['idfactura'] , "'>" , $col['idfactura'] , "</a></li>";

         echo "</ul>\n";
      }
   }
}

?>
