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

if( !file_exists('config.php') )
{
   echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n",
      "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"es\">\n",
      "<head>\n",
      "<title>FacturaScripts</title>\n",
      "<meta name='robots' content='noindex,nofollow'/>\n",
      "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>\n",
      "<link rel='stylesheet' type='text/css' media='screen' href='css/minimal.css'/>\n",
      "<link rel='icon' href='favicon.ico' type='image/x-icon'>\n",
      "</head>\n" , "<body>\n",
      "<div class='copyright'>\n",
      "Debes crear el archivo de configuraci&oacute;n '<b>config.php</b>' a partir del archivo de ejemplo '<b>config-sample.php</b>'.\n",
      "<br/>\n",
      "Una vez lo tengas, crea la base de datos y <a href='install.php'>comienza la instalaci&oacute;n</a> de facturaSCRIPTS.\n",
      "</div>";
}
else
{
   require('config.php');
   require('clases/db/postgresql.php');
   $bd = new db();
   
   if( isset($_COOKIE['user']) )
   {
      /*
       * Nos guardamos el nombre de usuario y borramos las cookies
       */
      $usuario = $_COOKIE['user'];
      ///setcookie('user', '', time()-FS_COOKIES_EXPIRE); mejor conservar el nombre de usuario, por comodidad
      setcookie('pass', '', time()-FS_COOKIES_EXPIRE);
   }
   else if( isset($_GET['usuario']) )
      $usuario = $_GET['usuario'];
   else
      $usuario = '';
   
   echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n",
      "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"es\">\n",
      "<head>\n",
      "<title>FacturaScripts</title>\n",
      "<meta name='robots' content='noindex,nofollow'/>\n",
      "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>\n",
      "<link rel='stylesheet' type='text/css' media='screen' href='css/minimal.css'/>\n",
      "<link rel='icon' href='favicon.ico' type='image/x-icon'>\n",
      "<script type=\"text/javascript\">\n",
      "<!--\n",
      "function fs_onload() {\n",
      "  document.login.user.focus();\n",
      "}\n",
      "//-->\n",
      "</script>\n",
      "</head>\n",
      "<body onload='document.login.pass.focus()'>\n";
   
   /// conectamos a la base de datos
   if( $bd->conectar() )
   {
      if( !$bd->existe_tabla('fs_modulos') )
      {
         echo "<div class='copyright'>Debes crear el archivo de configuraci&oacute;n '<b>config.php</b>' a partir del archivo de ejemplo\n",
            "'<b>config-sample.php</b>'.<br/>Una vez lo tengas, crea la base de datos y <a href='install.php'>comienza la instalaci&oacute;n</a>\n",
            "de facturaSCRIPTS.</div>\n";
      }
      else
      {
         require_once('clases/empresa.php');
         $mi_empresa = new empresa();
         /// guardamos el nombre de la empresa
         setcookie('empresa', $mi_empresa->get_nombre(), time()+FS_COOKIES_EXPIRE);
         
         echo "<div class='login'>\n",
            "<form name='login' method='post' action='ppal.php'>\n",
            "<table>\n",
            "<tr>\n",
            "<td colspan='2' align='center'><img src='images/system-lock-screen.png' alt='login'/></td>\n",
            "</tr>\n",
            "<tr>\n",
            "<td align='right'>Usuario:</td>\n",
            "<td align='left'><select name='user'>";
         
         $resultado = $bd->select("SELECT usuario FROM fs_usuarios ORDER BY usuario ASC;");
         if($resultado)
         {
            foreach($resultado as $col)
            {
               if($col['usuario'] == $usuario)
                  echo '<option value="' , $usuario , '" selected="selected">' , ucfirst($usuario) , '</option>' , "\n";
               else
                  echo '<option value="' , $col['usuario'] , '">' , ucfirst($col['usuario']) , '</option>' , "\n";
            }
         }
         else
            echo '<option>Error al conectar o la tabla est&aacute; vac&iacute;a</option>';

         echo "</select></td>\n",
            "</tr>\n",
            "<tr>\n",
            "<td align='right'>Contrase&ntilde;a:</td>\n",
            "<td align='left'><input type='password' name='pass' size='12' maxlength='12'/></td>\n",
            "</tr>\n",
            "<tr>\n",
            "<td colspan='2' align='center'><input type='submit' value='Entrar'/></td>\n",
            "</tr>\n",
            "</table>\n" , "</form>\n" , "</div>\n",
            "<div class='copyright'>\n",
            "<h1>" , $mi_empresa->get_nombre() , "</h1>\n",
            "Accediendo desde <b>" , $_SERVER['REMOTE_ADDR'] , "</b> " , $_SERVER['HTTP_USER_AGENT'] , "<br/>\n",
            "<i><a href='http://code.google.com/p/facturascripts/'>FacturaScripts</a> es un software libre bajo licencia <a href='COPYING'>GNU/GPL</a></i>\n",
            "<!-- Logotipos -->\n",
            "<a href='http://www.php.net'><img src='images/php-mini.png' alt='php powered'/></a>\n",
            "<a href='http://www.postgresql.org'><img src='images/postgresql-mini.gif' alt='postrgresql powered'/></a>\n",
            "</div>\n";
      }
      
      /// desconectamos de la base de datos
      $bd->desconectar();
   }
   else
      echo '<div class="copyright">Error al conectar a la base de datos</div>' , "\n";
}

echo "</body>\n" , "</html>\n";

?>