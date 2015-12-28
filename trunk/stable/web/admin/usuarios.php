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
require_once("clases/agentes.php");

class script_ extends script
{
   private $agentes;
   private $mis_agentes;

   public function __construct($ppal)
   {
      parent::__construct($ppal);
      $this->agentes = new agentes();
      $this->agentes->all( $this->mis_agentes );
   }

   /// devuelve el titulo del script
   public function titulo()
   {
      return("Usuarios de facturaScripts");
   }
   
   /// carga el cuerpo del script
   public function cuerpo($mod, $pag, &$datos)
   {
      $error = FALSE;
      
      if( isset($_POST['accion']) )
      {
         switch( $_POST['accion'] )
         {
            case 'nuevo':
               if( $this->nuevo($_POST['nuser'], $_POST['npass'], $_POST['nagente'], $error) )
                  echo "<div class='mensaje'>Usuario <b>" , $_POST['nuser'] ,
                       "</b> creado correctamente<br/>No olvides permitirle acceso a alg&uacute;n m&oacute;dulo</div>\n";
               else
                  echo "<div class='error'>Error al crear el usuario<br/>" , $error , "</div>\n";
               break;
               
            case 'modificar':
               if( !$this->modificar($_POST['id_'], $_POST['pass_'], $_POST['age_'], $_POST['qacces_'], $_POST['aacces_'], $_POST['inicio_'], $error) )
                  echo "<div class='error'>Error al modificar el usuario<br/>" , $error , "</div>\n";
               
               if( !$error )
                  echo "<div class='mensaje'>Datos modificados correctamente</div>\n";
               break;
         }
      }
      else if( isset($_GET['delete']) )
      {
         if( $this->eliminar($_GET['delete'], $error) )
            echo "<div class='mensaje'>Usuario <b>" , $_GET['delete'] , "</b> eliminado correctamente</div>\n";
         else
            echo "<div class='error'>Error al borrar el usuario<br/>" , $error , "</div>\n";
      }
      
      $this->listar2();
   }
   
   /// crear un nuevo usuario
   private function nuevo($usuario, $pass, $codagente, &$error)
   {
      $resultado = FALSE;
      $error = FALSE;
      
      if($usuario AND $pass)
      {
         /// convertimos la contraseña en minúsculas y la encriptamos
         $pass = sha1( strtolower($pass) );
         if(strlen($pass) < 100)
         {
            $consulta = "INSERT INTO fs_usuarios (usuario,pass,codagente) VALUES ('$usuario','$pass','$codagente');";
            $resultado = $this->bd->exec($consulta);
         }
         else
            $error = "La contrase&ntilde;a es demasiado larga";
      }
      else
         $error = "Usuario / contrase&ntilde;a no v&aacute;lid@s";
      return($resultado);
   }
   
   /// modificar un usuario
   private function modificar($usuario, $pass, $codagente, $qacceso, $aacceso, $inicial, &$error)
   {
      $resultado = FALSE;
      $error = FALSE;
      
      if($usuario)
      {
         /// construimos la consulta SQL
         $consulta = "";
         
         if($pass)
         {
            /// convertimos la contraseña en minúsculas y la encriptamos
            $pass = sha1( strtolower($pass) );
            
            if(strlen($pass) < 100)
               $consulta .= "UPDATE fs_usuarios SET pass = '$pass' WHERE usuario = '$usuario';";
            else
               $error = "La contrase&ntilde;a es demasiado larga";
         }
         
         if($codagente == '')
            $consulta .= "UPDATE fs_usuarios SET codagente = NULL WHERE usuario = '$usuario';";
         else
            $consulta .= "UPDATE fs_usuarios SET codagente = '$codagente' WHERE usuario = '$usuario';";
         
         if($qacceso)
            $consulta .= "DELETE FROM fs_ack WHERE usuario = '$usuario' AND modulo = '$qacceso';";
         
         if($aacceso)
            $consulta .= "INSERT INTO fs_ack (usuario,modulo) VALUES ('$usuario','$aacceso');";

         if($inicial)
         {
            $consulta .= "UPDATE fs_ack SET inicial = false WHERE usuario = '$usuario';";
            $consulta .= "UPDATE fs_ack SET inicial = true WHERE usuario = '$usuario' AND modulo = '$inicial';";
         }
         
         if($consulta)
            $resultado = $this->bd->exec($consulta);
      }
      else
         $error = "Usuario en blanco";
      return($resultado);
   }
   
   /// eliminar un usuario
   private function eliminar($usuario, &$error)
   {
      $resultado = FALSE;
      $error = FALSE;
      
      if($usuario)
      {
         $usuarios = $this->bd->select("SELECT count(usuario) as numero from fs_usuarios;");
         
         if($usuarios[0]['numero'] > 1)
            $resultado = $this->bd->exec("DELETE FROM fs_usuarios WHERE usuario = '$usuario';");
         else
            $error = "No se puede eliminar al &uacute;nico usuario del sistema";
      }
      else
         $error = "Usuario en blanco";
      return($resultado);
   }
   
   /// lista los modulos a los que tiene acceso un determinado usuario
   private function lista_modulos_acceso($usuario, $inicial)
   {
      echo "<option value=''>------</option>\n";
      
      $listado = $this->bd->select("SELECT * FROM fs_ack WHERE usuario='$usuario' ORDER BY modulo ASC;");
      if($listado)
      {
         foreach($listado as $col)
         {
            if($inicial AND $col['inicial'] == 't')
               echo '<option value="' , $col['modulo'] , '" selected>' , $col['modulo'] , '</option>' , "\n";
            else
               echo '<option value="' , $col['modulo'] , '">' , $col['modulo'] , '</option>' , "\n";
         }
      }
   }
   
   /// lista los modulos a los que NO tiene acceso un determinado usuario
   private function lista_modulos_no_acceso($usuario)
   {
      echo "<option value=''>------</option>\n";
      
      $listado = $this->bd->select("SELECT modulo FROM fs_modulos WHERE modulo != 'sys'
         AND modulo NOT IN (SELECT modulo FROM fs_ack WHERE usuario='$usuario') ORDER BY modulo ASC;");

      if($listado)
      {
         foreach($listado as $col)
            echo '<option value="' , $col['modulo'] , '">' , $col['modulo'] , '</option>' , "\n";
      }
   }
   
   private function listar_agente($agente)
   {
      echo "<select name='age_'/>";
      if( $this->mis_agentes )
      {
         foreach($this->mis_agentes as $col2)
         {
            if($col2['codagente'] == $agente)
               echo "<option value='",$col2['codagente'],"' selected='selected'/>",$col2['nombre']," ",$col2['apellidos'],"</option>\n";
            else
               echo "<option value='",$col2['codagente'],"'/>",$col2['nombre']," ",$col2['apellidos'],"</option>\n";
         }
      }
      echo "<select/>\n";
   }
   
   private function listar_agentes()
   {
      if( $this->mis_agentes )
      {
         foreach($this->mis_agentes as $col)
            echo '<option value="' , $col['codagente'] , '">' , $col['nombre'] , ' ' , $col['apellidos'] , "</option>\n";
      }
   }

   private function listar2()
   {
      echo "<div class='lista'>Nuevo usuario</div>
         <div class='lista2'>
         <form action='ppal.php?mod=admin&amp;pag=usuarios' method='post'>
            <table width='100%'>
               <tr>
                  <td>
                     <input type='hidden' name='accion' value='nuevo'/>
                     Usuario:
                     <input type='text' name='nuser' size='12' maxlength='12'/>
                     &nbsp;
                     Contraseña:
                     <input type='password' name='npass' size='12' maxlength='12' value='' autocomplete='off'/>
                     &nbsp;
                     Agente: <select name='nagente' size='0'>" , $this->listar_agentes() , "</select>
                  </td>
                  <td align='right'><input type='submit' value='nuevo'/></td>
               </tr>
            </table>
         </form>
         </div>";
      
      $usuarios = $this->bd->select("SELECT * FROM fs_usuarios ORDER BY usuario ASC;");
      if( $usuarios )
      {
         foreach($usuarios as $u)
         {
            echo "<div class='lista'>".$u['usuario']."</div>
               <div class='lista2'>
               <form action='ppal.php?mod=admin&amp;pag=usuarios' method='post'>
                  <input type='hidden' name='accion' value='modificar'/>
                  <input type='hidden' name='id_' value='" , $u['usuario'] , "'/>
                  Agente: ", $this->listar_agente($u['codagente']) ," &nbsp;
                  Nueva contraseña:
                  <input type='password' name='pass_' size='12' maxlength='12' value='' autocomplete='off'/>
                  &nbsp;
                  Quitar acceso a módulo:
                  <select name='qacces_' size='0'>" , $this->lista_modulos_acceso($u['usuario'], FALSE) , "</select>
                  &nbsp;
                  Añadir acceso a módulo:
                  <select name='aacces_' size='0'>" , $this->lista_modulos_no_acceso($u['usuario']) , "</select>
                  &nbsp;
                  Módulo por defecto:
                  <select name='inicio_' size='0'>" , $this->lista_modulos_acceso($u['usuario'], TRUE) , "</select>
                  <table width='100%'>
                     <tr>
                        <td><a class='cancelar' href='ppal.php?mod=admin&pag=usuarios&delete=",$u['usuario'],"'>eliminar</a></td>
                        <td align='right'><input type='submit' value='modificar'/></td>
                     </tr>
                  </table>
               </form>
               </div>";
         }
      }
   }
}

?>