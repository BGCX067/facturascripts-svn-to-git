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
require_once("clases/clientes.php");
require_once("clases/facturas_cli.php");

class script_ extends script
{
   private $clientes;
   private $facturas;

   public function __construct($ppal)
   {
      parent::__construct($ppal);

      $this->clientes = new clientes();
      $this->facturas = new facturas_cli();
   }

   /// devuelve el titulo del script
   public function titulo()
   {
      return("Facturas de Clientes");
   }
   
   /// devuelve el script javascript necesario para la pagina
   public function javas()
   {
      ?>
      <script type="text/javascript">
      <!--

      function fs_onload()
      {
         document.facturas.buscar.focus();
      }

      function fs_unload() {
      }

      //-->
      </script>
      <?php
   }
   
   /// cargar el cuerpo del script
   public function cuerpo($mod, $pag, &$datos)
   {
      $facturas = FALSE;
      
      if( isset($_GET['buscar']) )
         $buscar = $_GET['buscar'];
      else
         $buscar = '';
      
      if( isset($_GET['tipo']) )
         $tipo = $_GET['tipo'];
      else
         $tipo = 'gen';
      
      echo "<div class='destacado'>\n",
         "<form name='facturas' action='ppal.php' method='get'>\n",
         "<input type='hidden' name='mod' value='" , $mod , "'/>\n",
         "<input type='hidden' name='pag' value='" , $pag , "'/>\n",
         "<span>Facturas de cliente</span>\n",
         "<input type='text' name='buscar' size='18' maxlength='18' value='" , $buscar , "'/>\n",
         "<input type='submit' value='Buscar'/>\n";

      switch( $tipo )
      {
         default:
            echo "<input type='radio' name='tipo' value='gen' checked/>General\n",
               "<input type='radio' name='tipo' value='coc'/><a href='ppal.php?mod=" , $mod , "&amp;pag=clientes'>Cliente</a>\n",
               "<input type='radio' name='tipo' value='xre'/>Referencia\n";
            break;
         
         case "coc":
            echo "<input type='radio' name='tipo' value='gen'/>General\n",
               "<input type='radio' name='tipo' value='coc' checked/><a href='ppal.php?mod=" , $mod , "&amp;pag=clientes'>Cliente</a>\n",
               "<input type='radio' name='tipo' value='xre'/>Referencia\n";
            break;
            
         case "xre":
            echo "<input type='radio' name='tipo' value='gen'/>General\n",
               "<input type='radio' name='tipo' value='coc'/><a href='ppal.php?mod=" , $mod , "&amp;pag=clientes'>Cliente</a>\n",
               "<input type='radio' name='tipo' value='xre' checked/>Referencia\n";
            break;
      }

      echo "</form></div>\n";
      
      /// si hay una busqueda
      if($buscar != '')
      {
         $facturas = $this->facturas->buscar($buscar, $tipo, FS_LIMITE, $_GET['p'], $_GET['t']);
         if($facturas)
         {
            switch( $tipo )
            {
               default:
                  echo "<div class='lista'>Mostrando " , count($facturas) , " de " , number_format($_GET['t'], 0) , " resultados encontrados</div>\n";
                  break;
                  
               case "coc":
                  echo "<div class='lista'>Mostrando " , count($facturas) , " de " , number_format($_GET['t'], 0) , " facturas del cliente '",
                     $this->clientes->get_nombre($buscar) , "'</div>\n";
                  break;
               
               case "xre":
                  echo "<div class='lista'>Mostrando " , count($facturas) , " de " , number_format($_GET['t'], 0) , " resultados para el art&iacute;culo '",
                     $buscar , "'</div>\n";
                  break;
            }
            
            if($tipo == 'xre')
               $this->mostrar_facturas_ref($mod, $pag, $facturas);
            else
               $this->mostrar_facturas($mod, $pag, $facturas);
            
            $this->paginar("ppal.php?mod=" . $mod . "&amp;pag=" . $pag . "&amp;buscar=" . $_GET['buscar'] . "&amp;tipo=" . $_GET['tipo'],
                    FS_LIMITE, $_GET['p'], $_GET['t']);
         }
         else
            echo "<div class='mensaje'>0 resultados</div>\n";
      }
      else /// no hay una busqueda
      {
         if( $this->facturas->ultimas(FS_LIMITE, $facturas, $_GET['t'], $_GET['p']) )
         {
            echo "<div class='lista'>&Uacute;ltimas facturas de clientes</div>\n";

            $this->mostrar_facturas($mod, $pag, $facturas);
            $this->paginar('ppal.php?mod=' . $mod . "&amp;pag=" . $pag, FS_LIMITE, $_GET['p'], $_GET['t']);
         }
         else
            echo "<div class='mensaje'>Nada que mostrar</div>\n";
      }
      
      echo "</div>\n";
   }
   
   private function mostrar_facturas($mod, $pag, $facturas)
   {
      echo "<table class='lista'>\n",
         "<tr class='destacado'>\n",
         "<td>C&oacute;digo</td>\n",
         "<td>Cliente</td>\n",
         "<td>Agente</td>\n",
         "<td>Observaciones</td>\n",
         "<td align='right'>Total</td>\n",
         "<td align='right'>Fecha</td>\n",
         "</tr>\n";

      foreach($facturas as $col)
      {
         echo "<tr>\n",
            "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=facturacli&amp;id=" , $col['idfactura'] , "'>" , $col['codigo'] , "</a></td>\n",
            "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=cliente&amp;cod=" , $col['codcliente'] , "'>" , $col['nombrecliente'] , "</a></td>\n",
            "<td>" , $this->mostrar_agente($col['codagente'], $col['anom'], $col['apellidos'], $mod, $pag) , "</td>\n",
            "<td>" , $col['observaciones'] , "</td>\n",
            "<td align='right'>" , number_format($col['total'], 2) , " &euro;</td>\n",
            "<td align='right'>" , Date('d-m-Y', strtotime($col['fecha'])) , "</td>\n",
            "</tr>\n";
      }

      echo "</table>\n";
   }
   
   private function mostrar_facturas_ref($mod, $pag, $facturas)
   {
      echo "<table class='lista'>\n",
              "<tr class='destacado'>\n",
              "<td>C&oacute;digo</td>\n",
              "<td>Observaciones</td>\n",
              "<td>Referencia</td>\n",
              "<td align='right'>Importe</td>\n",
              "<td align='right'>Fecha</td>\n",
              "</tr>\n";
      
      foreach($facturas as $col)
      {
         echo "<tr>\n",
            "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=facturacli&amp;id=" , $col['idfactura'] , "'>" , $col['codigo'] , "</a></td>\n",
            "<td>" , $col['observaciones'] , "</td>\n",
            "<td>" , $col['referencia'] , "</td>\n",
            "<td align='right'>" , number_format($col['pvptotal'], 2) , " &euro;</td>\n",
            "<td align='right'>" , Date('d-m-Y', strtotime($col['fecha'])) , "</td>\n",
            "</tr>\n";
      }
      
      echo "</table>\n";
   }
   
   private function mostrar_agente($agente, $nombre, $apellidos, $mod, $pag)
   {
      if($agente != '')
      {
         switch($mod)
         {
            case 'boxes':
            case 'contabilidad':
               echo "<a href='ppal.php?mod=" , $mod , "&amp;pag=" , $pag , "&amp;buscar=" , $agente , "&amp;tipo=age'>",
                  $nombre , ' ' , $apellidos , "</a>";
               break;

            default:
               echo "<a href='ppal.php?mod=" , $mod , "&amp;pag=agentes&amp;cod=" , $agente , "'>",
                 $nombre , ' ' , $apellidos , "</a>";
               break;
         }
      }
      else
         echo '-';
   }
}

?>