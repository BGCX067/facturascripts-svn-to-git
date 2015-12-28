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

class script
{
   protected $bd;
   protected $enlace;
   protected $ppal;
   protected $usuario;
   protected $codagente;
   protected $tiempo_inicio;
   
   /// para formularios genericos
   protected $generico;
   protected $coletilla;
   
   public function __construct($ppal)
   {
      /// comenzamos a contar el tiempo
      $tiempo = microtime();
      $tiempo = explode(' ', $tiempo);
      $this->tiempo_inicio = $tiempo[1] + $tiempo[0];
      $this->bd = new db();
      $this->enlace = $this->bd->conectar();
      $this->ppal = $ppal;
      $this->usuario = FALSE;
      $this->codagente = FALSE;
      $this->generico = FALSE;
      $this->coletilla = '';
   }
   
   public function __destruct()
   {
      $this->bd->desconectar();
   }
   
   public function enlace_db()
   {
      return ($this->enlace != FALSE);
   }
   
   /// escribe un mensaje de error de acceso a la base de datos
   public function error_db()
   {
      echo '<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">' , "\n",
         '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="es">' , "\n",
         '<head>' , "\n",
         '  <title>Acceso denegado !</title>' , "\n",
         '  <meta name="robots" content="noindex,nofollow"/>' , "\n",
         '  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>' , "\n",
         '  <link rel="stylesheet" type="text/css" media="screen" href="css/minimal.css"/>' , "\n",
         '  <link rel="icon" href="images/favicon.ico" type="image/x-icon">' , "\n",
         '</head>' , "\n",
         '<body>' , "\n",
         '<div class="copyright"><a href="index.php">ERROR AL CONECTAR A LA BASE DE DATOS</a></div>' , "\n",
         '</body>' , "\n",
         '</html>' , "\n";
   }
   
   /// autentica un usuario en el sistema
   public function login(&$mod)
   {
      if( isset($_POST['user']) )
         $user = $_POST['user'];
      else
         $user = '';
      
      $pass = FALSE;
      $acceso = FALSE;
      $modaux = $mod;
      
      /// encriptamos la contraseña
      if( isset($_POST['pass']) )
         $pass = sha1(strtolower($_POST['pass']) ); /// convertimos la contraseña en minúsculas para evitar problemas con usuarios "torpes"
      
      if($user AND $pass)
      {
         $resultado = $this->bd->select("SELECT a.modulo,u.codagente
            FROM fs_usuarios u LEFT JOIN fs_ack a ON a.usuario = u.usuario
            WHERE u.usuario = '" . $user . "' AND u.pass = '" . $pass . "'
            ORDER BY a.inicial DESC, a.modulo ASC;");

         if($resultado)
         {
            if($mod == '')
               $mod = $resultado[0]['modulo'];
            
            $this->usuario = $user;
            $this->codagente = $resultado[0]['codagente'];
            setcookie("user", $user, time()+FS_COOKIES_EXPIRE);
            setcookie("pass", $pass, time()+FS_COOKIES_EXPIRE);
            $acceso = TRUE;
         }
      }
      else if( isset($_COOKIE['user']) AND isset($_COOKIE['pass']) )
      {
         $user = $_COOKIE['user'];
         $pass = $_COOKIE['pass'];
         
         $consulta = "SELECT a.modulo,u.codagente
            FROM fs_usuarios u LEFT JOIN fs_ack a ON a.usuario=u.usuario WHERE u.usuario='" . $user . "' AND u.pass='" . $pass . "'
            ORDER BY a.modulo ASC;";

         $resultado = $this->bd->select($consulta);
         if($resultado)
         {
            if($mod == '')
               $mod = $resultado[0]['modulo'];
            
            $this->usuario = $user;
            $this->codagente = $resultado[0]['codagente'];
            $acceso = TRUE;
         }
      }
      
      /// redireccionamos al usuario
      if($acceso AND $modaux == '')
         Header('location: ' . $this->ppal . '?mod=' . $mod);
      
      return $acceso;
   }
   
   /// captura las variables necesarias para el script enviadas por GET y POST
   public function datos()
   {
      return array();
   }
   
   /// devuelve el script javascript necesario para la pagina
   public function javas()
   {
      ?>
      <script type="text/javascript">
         function fs_onload() {}
         function fs_unload() {}
      </script>
      <?php
   }
   
   /// escribe la cabecera del documento html
   public function cabecera()
   {
      echo "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>\n",
         "<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' lang='es'>\n",
         "<head>\n",
         "   <title>" , $this->titulo() , "</title>\n",
         "   <meta name='robots' content='noindex,nofollow'/>\n",
         "   <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'/>\n",
         "   <link rel='stylesheet' type='text/css' media='screen' href='css/minimal.css'/>\n",
         "   <link rel='icon' href='favicon.ico' type='image/x-icon'>\n",
         "   <script type='text/javascript' src='js/funciones.js'></script>\n",
         "   <script type='text/javascript' src='js/tcal.js'></script>\n";
      
      $this->javas();
      
      echo "\n</head>\n",
         "<body onload='fs_onload()' onunload='fs_unload()'>\n";
   }
   
   /// escribe el menu de la aplicacion en html
   public function menu($mod, $pag)
   {
      echo '<div class="cabecera">' , "\n",
         '<form name="fscripts" action="ppal.php" method="get">' , "\n";

      /// obtenemos el listado de usuarios
      $usuarios = $this->bd->select("SELECT usuario FROM fs_usuarios ORDER BY usuario ASC;");

      if($usuarios)
      {
         echo '<select name="user" title="cambiar de usuario / salir" onchange="fs_selec_user()">' , "\n";
         foreach($usuarios as $col)
         {
            if($col['usuario'] == $this->usuario)
               echo '<option value="' , $col['usuario'] , '" selected>' , ucfirst($col['usuario']) , '</option>' , "\n";
            else
               echo '<option value="' , $col['usuario'] , '">' , ucfirst($col['usuario']) , '</option>' , "\n";
         }
         echo '<option value="">---</option>' , "\n",
            '<option value="-">Salir</option>' , "\n",
            '</select>' , "\n",
            '<input type="hidden" name="pass" value=""/>' , "\n";
      }

      /// obtenemos todas las entradas del menu del usuario
      $menu = $this->bd->select("SELECT a.modulo,mo.titulo as nombre,me.titulo,me.enlace FROM fs_ack a, fs_menu me, fs_modulos mo
         WHERE a.modulo = me.modulo AND me.modulo = mo.modulo AND a.usuario = '" . $this->usuario . "'
         ORDER BY a.modulo ASC, me.titulo ASC;");

      if( $menu )
      {
         /*
          * Mostramos los módulos
          */
         echo '&nbsp; » &nbsp; est&aacute;s viendo: <select id="indice" name="mod" title="seleccionar otro m&oacute;dulo" onchange="fs_selec_mod()">' , "\n";
         $i = 0;
         $modulos = Array();
         foreach($menu as $col)
         {
            if( !in_array($col['modulo'], $modulos) )
            {
               if($col['modulo'] == $mod)
                  echo '<option value="' , $col['modulo'] , '" selected>' , $col['nombre'] , '</option>' , "\n";
               else
                  echo '<option value="' , $col['modulo'] , '">' , $col['nombre'] , '</option>' , "\n";
               $modulos[$i] = $col['modulo'];
               $i++;
            }
         }
         echo '</select> »' , "\n";

         /*
          * Mostramos las páginas del módulos seleccionado
          */
         if( $mod )
         {
            $encontrado = FALSE;

            echo '<select name="pag" title="seleccionar otra p&aacute;gina de este m&oacute;dulo" onchange="fs_selec_pag()">' , "\n";

            if($pag == 'home')
            {
               echo '<option value="home" selected>Inicio</option>' , "\n";
               $encontrado = TRUE;
            }
            else
               echo '<option value="home">Inicio</option>' , "\n";

            foreach($menu as $col)
            {
               if($col['modulo'] == $mod AND $col['titulo'] != '')
               {
                  if($col['enlace'] == $pag)
                  {
                     echo '<option value="' , $col['enlace'] , '" selected>' , $col['titulo'] , '</option>' , "\n";
                     $encontrado = TRUE;
                  }
                  else
                     echo '<option value="' , $col['enlace'] , '">' , $col['titulo'] , '</option>' , "\n";
               }
            }

            if( !$encontrado )
            {
               echo '<option value="home">---</option>' , "\n",
                  '<option value="' , $pag , '" selected>' , $this->titulo() , '</option>' , "\n";
            }

            echo "</select>\n" , ' <a href="' , $this->recargar($mod, $pag) , '" title="recargar la p&aacute;gina">
               <img src="images/reload.png" alt="recargar"/></a>';

            ///Si es un formulario generico mostramos otros modulos desde donde acceder
            if($pag AND $this->generico)
            {
               $opciones = '';
               $entradas = 0;

               foreach($menu as $col)
               {
                  if($col['modulo'] != $mod AND $col['enlace'] == $pag)
                  {
                     $opciones .= "<option value='ppal.php?mod=" . $col['modulo'] . "&amp;pag=" . $col['enlace'] . $this->coletilla . "'>".
                        $col['modulo'] . "</option>\n";
                     $entradas++;
                  }
               }

               if($entradas)
               {
                  echo ' &nbsp; ver desde: <select name="cmod" title="ver esta p&aacute;gina desde otro m&oacute;dulo" onchange="fs_selec_cmod()">' , "\n",
                     '<option value="">---</option>' , "\n" , $opciones , '</select>' , "\n";
               }
            }
         }
      }

      echo "<span class='empresa'>",$_COOKIE['empresa'],"</span></form>\n</div>\n",
         '<div class="cuerpo">' , "\n";
   }
   
   /// genera la url necesaria para reargar el script
   public function recargar($mod, $pag)
   {
      if( isset($mod) )
      {
         if( isset($pag) )
            return "ppal.php?mod=" . $mod . "&pag=" . $pag;
         else
            return "ppal.php?mod=" . $mod;
      }
      else
         return "ppal.php";
   }
   
   /// escribe el pie de pagina de documentos html
   public function pie()
   {
      echo "</div>\n";
      
      if( FS_SQL_HISTORY )
      {
         echo "<ul class='historial_sql'>";
         
         foreach($this->bd->get_history() as $h)
            echo "<li>" , $h , "</li>\n";
         
         echo "</ul>";
      }
      
      echo "<div class='pie'>\n",
         '<span>' , $this->timer_end() , ' | Consultas: ' , $this->bd->get_selects() , ' |' , "\n",
         'Transacciones: ' , $this->bd->get_transacciones() , '</span> |' , "\n",
         '<a href="http://code.google.com/p/facturascripts" target="_blank">Web oficial</a> |' , "\n",
         '<a href="http://code.google.com/p/facturascripts/issues/entry" target="_blank">Informar de un error</a>' , "\n",
         '</div>' , "\n" , '</body>' , "\n" , '</html>' , "\n";
   }
   
   /// devuelve el titulo del script
   public function titulo()
   {
      if( isset($_COOKIE['empresa']) )
         return $_COOKIE['empresa'];
      else if( isset($_GET['mod']) )
         return $_GET['mod'];
      else
         return "Home";
   }
   
   /// escribe un mensaje de error de acceso denegado en html
   public function acceso_denegado()
   {
      echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' , "\n",
         '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="es">' , "\n",
         '<head>' , "\n",
         '   <title>¡ Acceso denegado !</title>' , "\n",
         '   <meta name="robots" content="noindex,nofollow"/>' , "\n",
         '   <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>' , "\n",
         '   <link rel="stylesheet" type="text/css" media="screen" href="css/minimal.css"/>' , "\n",
         '   <link rel="icon" href="favicon.ico" type="image/x-icon">' , "\n",
         '</head>' , "\n" , '<body>' , "\n" , '<div class="copyright"><img src="images/system-lock-screen.png"/> ',
         '<a href="index.php">¡¡¡ ACCESO DENEGADO !!!</a></div>' , "\n",
         '</body>' , "\n" , '</html>' , "\n";
   }
   
   /// carga el cuerpo del script, y que recibe los parametros capturados por datos()
   public function cuerpo($mod, $pag, &$datos)
   {
      echo "<div class='destacado'>P&aacute;gina no encontrada</div>\n";
   }
   
   /// genera el documento PDF, es llamado desde pdf.php
   public function documento_pdf($mod, $pag, &$datos)
   {
      echo 'Documento en blanco';
   }
   
   /// funcion para paginar las busquedas
   public function paginar($url, $limite, $pagina, $total)
   {
      if($total > $limite)
      {
         echo '<div class="paginador">';
         
         /// Anterior
         if($pagina > 0)
         {
            echo '<a href="' , $url , '&amp;p=' , ($pagina - $limite) , '&amp;t=' , $total,
                    '" title="anterior"><img src="images/anterior.gif"/></a> &nbsp;' , "\n";
         }

         /// Primera
         if($pagina != 0)
            echo '<a href="',$url,'&amp;p=0&amp;t=',$total,'">1</a> ...',"\n";
         
         /// 5 anteriores
         for($i = 5; $i > 0; $i--)
         {
            $p = $pagina - ($i * $limite);
            if($p > 0)
               echo '<a href="',$url,'&amp;p=',$p,'&amp;t=',$total,'">',(ceil($p / $limite) + 1),'</a>',"\n";
         }
         
         /// Actual
         echo '[<a href="' , $url , '&amp;p=' , $pagina , '&amp;t=' , $total , '">' , (ceil($pagina / $limite) + 1) , '</a>]' , "\n";
         
         /// 5 siguientes
         $p = $pagina;
         for($i = 0; $i < 5; $i++)
         {
            $p += $limite;
            if($p < ($total - $limite))
               echo '<a href="',$url,'&amp;p=',$p,'&amp;t=',$total,'">',(ceil($p / $limite) + 1),'</a>',"\n";
         }

         /// Ultima
         if(($pagina + $limite) < $total)
         {
            echo '... <a href="' , $url , '&amp;p=' , ($limite * (ceil($total / $limite) - 1)) , '&amp;t=' , $total , '">',
                    ceil($total / $limite) , '</a>' , "\n";
         }
         
         /// Siguiente
         $p = $pagina + $limite;
         if($p < $total)
            echo '&nbsp; <a href="',$url,'&amp;p=',$p,'&amp;t=',$total,'" title="siguiente"><img src="images/siguiente.gif"/></a>' , "\n";
         
         echo "</div>\n";
      }
   }
   
   /// funcion que devuelve el tiempo que ha transcurrido desde que se ha iniciado la ejecución del script
   protected function timer_end()
   {
      $tiempo = microtime();
      $tiempo = explode(" ", $tiempo);
      $tiempo = $tiempo[1] + $tiempo[0];
      return (number_format($tiempo - $this->tiempo_inicio, 3) . ' segundos');
   }
}

?>
