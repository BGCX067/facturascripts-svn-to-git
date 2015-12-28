<?php
/*
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this program; if not, write to the Free Software
   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA.

   Autor: Carlos Garcia Gomez
*/

require("clases/script.php");
require_once("clases/albaranes_cli.php");
require_once("clases/agentes.php");
require_once("clases/clientes.php");

class script_ extends script
{
   private $agentes;
   private $albaranes;
   private $clientes;

   public function __construct($ppal)
   {
      parent::__construct($ppal);
      $this->generico = true;
      $this->agentes = new agentes();
      $this->albaranes = new albaranes_cli();
      $this->clientes = new clientes();
   }

   /// devuelve el titulo del script
   public function titulo()
   {
      return("Albaranes de Clientes");
   }

   /// codigo javascript
   public function javas()
   {
      ?>
      <script type="text/javascript">
         function fs_onload() {
            document.albaranes.buscar.focus();
         }
         function fs_unload() {
         }
      </script>
      <?php
   }

   /// captura las variables necesarias para el script enviadas por GET y POST
   public function datos()
   {
      $datos = array(
          'tipo' => '',
          'buscar' => ''
      );
      
      if( isset($_GET['tipo']) )
         $datos['tipo'] = $_GET['tipo'];
      
      /// decodificamos en caso de que se busque por referencia
      if($datos['tipo'] == "xre" AND isset($_GET['buscar']))
         $datos['buscar'] = rawurldecode($_GET['buscar']);
      else if( isset($_GET['buscar']) )
         $datos['buscar'] = $_GET['buscar'];
      
      if( isset($_GET['p']) )
         $datos['pagina'] = $_GET['p'];
      
      if( isset($_GET['t']) )
         $datos['total'] = $_GET['t'];

      return $datos;
   }

   /// carga el cuerpo del script
   public function cuerpo($mod, $pag, &$datos)
   {
      $albaranes = FALSE;

      echo "<form name='albaranes' action='ppal.php' method='get'>\n",
         "<div class='destacado'>\n",
         "<input type='hidden' name='mod' value='" , $mod , "'/>\n",
         "<input type='hidden' name='pag' value='" , $pag , "'/>\n",
         "<span>Albaranes de cliente</span>\n",
         "<input type='text' name='buscar' size='18' maxlength='18' value='" , $datos['buscar'] , "'/>\n",
         "<input type='submit' value='Buscar'/>\n";

      switch($datos['tipo'])
      {
         default:
            echo "<input type='radio' name='tipo' value='age'/><a href='ppal.php?mod=" , $mod , "&amp;pag=agentes'>Agentes</a>\n",
               "<input type='radio' name='tipo' value='coc'/><a href='ppal.php?mod=" , $mod , "&amp;pag=clientes'>Cliente</a>\n",
               "<input type='radio' name='tipo' value='gen' checked/>General\n",
               "<input type='radio' name='tipo' value='xre'/><a href='ppal.php?mod=" , $mod , "&amp;pag=articulos'>Referencia</a>\n";
            break;

         case "age":
            echo "<input type='radio' name='tipo' value='age' checked/><a href='ppal.php?mod=" , $mod , "&amp;pag=agentes'>Agentes</a>\n",
               "<input type='radio' name='tipo' value='coc'/><a href='ppal.php?mod=" , $mod , "&amp;pag=clientes'>Cliente</a>\n",
               "<input type='radio' name='tipo' value='gen'/>General\n",
               "<input type='radio' name='tipo' value='xre'/><a href='ppal.php?mod=" , $mod , "&amp;pag=articulos'>Referencia</a>\n";
            break;

         case "coc":
            echo "<input type='radio' name='tipo' value='age'/><a href='ppal.php?mod=" , $mod , "&amp;pag=agentes'>Agentes</a>\n",
               "<input type='radio' name='tipo' value='coc' checked/><a href='ppal.php?mod=" , $mod , "&amp;pag=clientes'>Cliente</a>\n",
               "<input type='radio' name='tipo' value='gen'/>General\n",
               "<input type='radio' name='tipo' value='xre'/><a href='ppal.php?mod=" , $mod , "&amp;pag=articulos'>Referencia</a>\n";
            break;

         case "xre":
            echo "<input type='radio' name='tipo' value='age'/><a href='ppal.php?mod=" , $mod , "&amp;pag=agentes'>Agentes</a>\n",
               "<input type='radio' name='tipo' value='coc'/><a href='ppal.php?mod=" , $mod , "&amp;pag=clientes'>Cliente</a>\n",
               "<input type='radio' name='tipo' value='gen'/>General\n",
               "<input type='radio' name='tipo' value='xre' checked/><a href='ppal.php?mod=" , $mod , "&amp;pag=articulos'>Referencia</a>\n";
            break;
      }

      echo "</div>\n</form>\n";

      if($datos['buscar'] != '')
      {
         $albaranes = $this->albaranes->buscar($datos['buscar'], $datos['tipo'], FS_LIMITE, $datos['pagina'], $datos['total']);
         if($albaranes)
         {
            switch($datos['tipo'])
            {
               default:
                  echo "<div class='lista'>Mostrando " , count($albaranes) , " de " , number_format($datos['total'], 0) , " resultados encontrados</div>\n";
                  break;

               case "age":
                  echo "<div class='lista'>Mostrando " , count($albaranes) , " de " , number_format($datos['total'], 0) , " albaranes del agente '",
                       $this->agentes->get_nombre($datos['buscar']) , "'</div>\n";
                  break;

               case "coc":
                  echo "<div class='lista'>Mostrando " , count($albaranes) , " de " , number_format($datos['total'], 0) , " albaranes del cliente '",
                       $this->clientes->get_nombre($datos['buscar']) , "'</div>\n";
                  break;
            }

            if($datos['tipo'] == 'xre')
               $this->mostrar_ref($mod, $pag, $albaranes);
            else
               $this->mostrar_alb($mod, $pag, $albaranes);

            $this->paginar('ppal.php?mod=' . $mod . "&amp;pag=" . $pag . "&amp;buscar=" . $datos['buscar'] . "&amp;tipo=" . $datos['tipo'],
                    FS_LIMITE, $datos['pagina'], $datos['total']);
         }
         else
            echo "<div class='mensaje'><b>0</b> resultados encontrados</div>\n";
      }
      else
      {
         if( $this->albaranes->ultimos(FS_LIMITE, $albaranes, $datos['total'], $datos['pagina']) )
         {
            echo "<div class='lista'>&Uacute;ltimos albaranes de clientes</div>\n";

            $this->mostrar_alb($mod, $pag, $albaranes);
            $this->paginar('ppal.php?mod=' . $mod . "&amp;pag=" . $pag, FS_LIMITE, $datos['pagina'], $datos['total']);
         }
         else
            echo "<div class='mensaje'>Nada que mostrar</div>\n";
      }
   }

   /// muestra los albaranes en una tabla
   public function mostrar_alb($mod, $pag, $albaranes)
   {
      echo "<table class='lista'>\n",
         "<tr class='destacado'>\n",
         "<td width='10'></td>\n",
         "<td>C&oacute;digo [n&uacute;mero 2]</td>\n",
         "<td>Cliente</td>\n",
         "<td>Agente</td>\n",
         "<td>Observaciones</td>\n",
         "<td align='right'>Total</td>\n",
         "<td align='right'>Fecha</td>\n",
         "</tr>\n";

      foreach($albaranes as $col)
      {
         if($col['total'] > 0)
            echo "<tr>\n";
         else
            echo "<tr class='amarillo'>\n";
         
         if($col['ptefactura'] == 'f')
            echo '<td><span title="Facturado"/>F</span></td>' , "\n";
         else
            echo "<td></td>\n";
         
         if($col['numero2'] != '')
         {
            echo "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=albarancli&amp;id=" , $col['idalbaran'] , "'>",
                    $col['codigo'] , "</a> [" , $col['numero2'] , "]</td>\n";
         }
         else
         {
            echo "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=albarancli&amp;id=" , $col['idalbaran'] , "'>",
                    $col['codigo'] , "</a></td>\n";
         }

         echo "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=cliente&amp;cod=" , $col['codcliente'] , "'>",
            $col['nombrecliente'] , "</a></td>\n",
            "<td>" , $this->mostrar_agente($col['codagente'], $col['anom'], $col['apellidos'], $mod, $pag) , "</td>\n",
            "<td>" , $col['observaciones'] , "</td>\n",
            "<td align='right'>" , number_format($col['total'], 2) , " &euro;</td>\n",
            "<td align='right'>" , Date('d-m-Y', strtotime($col['fecha'])) , "</td>\n",
            "</tr>\n";
      }
      
      echo "</table>\n";
   }

   /// muestra los artículos de los albaranes en una tabla
   public function mostrar_ref($mod, $pag, $albaranes)
   {
      echo "<table class='lista'>\n",
         "<tr class='destacado'>\n",
         "<td width='10'></td>\n",
         "<td>C&oacute;digo</td>\n",
         "<td>Cliente</td>\n",
         "<td>Observaciones</td>\n",
         "<td>Referencia</td>\n",
         "<td align='right'>Importe</td>\n",
         "<td align='right'>Fecha</td>\n",
         "</tr>\n";

      foreach($albaranes as $col)
      {
         echo "<tr>\n";
         
         if($col['ptefactura'] == 'f')
            echo '<td><span title="Facturado"/>F</span></td>' , "\n";
         else
            echo "<td></td>\n";
         
         echo "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=albarancli&amp;id=" , $col['idalbaran'] , "'>" , $col['codigo'] , "</a></td>\n",
            "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=cliente&amp;cod=" , $col['codcliente'] , "'>",$col['nombrecliente'],"</a></td>\n",
            "<td>" , $col['observaciones'] , "</td>\n",
            "<td><a href='ppal.php?mod=" , $mod , "&amp;pag=articulo&amp;ref=" , rawurlencode($col['referencia']) , "'>" , $col['referencia'] , "</a></td>\n",
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
