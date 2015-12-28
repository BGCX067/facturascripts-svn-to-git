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

class familias
{
   private $bd;

   public function __construct()
   {
      $this->bd = new db();
   }

   /// devuelve por parametros los datos de una familia dada
   public function get($codfamilia,&$familia)
   {
      $retorno = false;

      if($codfamilia)
      {
         $resultado = $this->bd->select("SELECT * FROM familias WHERE codfamilia = '$codfamilia';");
         if($resultado)
         {
            $familia = $resultado[0];
            $retorno = true;
         }
      }

      return($retorno);
   }

   /// devuelve un array de familias buscadas
   public function buscar($buscar, &$familias, &$error)
   {
      $retorno = false;

      /// quitamos los espacios del principio y final y lo ponemos en mayusculas
      $buscar = strtoupper( trim($buscar) );

      if($buscar != '')
      {
         /// buscamos familias + articulos
         $consulta = "SELECT familias.codfamilia, familias.descripcion, count(articulos.referencia) as articulos,
            MAX(articulos.factualizado) as factualizado
            FROM familias LEFT JOIN articulos ON familias.codfamilia = articulos.codfamilia
            WHERE upper(familias.codfamilia) ~~ '%$buscar%'
              OR upper(familias.descripcion) ~~ '%".str_replace(' ', '%', $buscar)."%'
            GROUP BY familias.codfamilia,familias.descripcion
            ORDER BY familias.codfamilia ASC;";

         $familias = $this->bd->select($consulta);
         $retorno = true;
      }

      return($retorno);
   }

   /// devuleve un numero determinado de familias ordenadas
   public function listar($limite, $pagina, &$total)
   {
      $familias = false;

      if($limite == '')
         $limite = FS_LIMITE;

      if($pagina == '')
         $pagina = 0;

      $consulta = "SELECT codfamilia, descripcion FROM familias ORDER BY codfamilia ASC";

      if($total == '')
      {
         $total = $this->bd->num_rows($consulta);
      }

      $familias = $this->bd->select_limit($consulta, $limite, $pagina);

      return($familias);
   }

   /// devuelve un array de familias
   public function all()
   {
      return( $this->bd->select("SELECT * FROM familias ORDER BY descripcion ASC;") );
   }

   /// inserta una familia en la base de datos
   public function insert($familia, &$error)
   {
      $retorno = true;

      // codificamos codfamilia
      $familia['codfamilia'] = str_replace(' ','_',$familia['codfamilia']);

      // comprobamos la validez de los datos
      if(eregi("^[A-Z0-9_]{2,4}$", $familia['codfamilia']) != true)
      {
         $error = "Codfamilia solamente admite n&uacute;meros, letras y '_'.";
         $retorno = false;
      }

      if(eregi("^[A-Z0-9_ ]{2,99}$", $familia['descripcion']) != true)
      {
         $error = "Descripci&oacute;n solamente admite n&uacute;meros, letras y '_'.";
         $retorno = false;
      }

      // comprobamos que no exista previamente
      if($retorno)
      {
         if($this->bd->select("SELECT codfamilia FROM familias WHERE codfamilia = '$familia[codfamilia]';"))
         {
            $error = "La familia ya existe";
            $retorno = false;
         }
      }

      if($retorno)
      {
         $consulta = "INSERT INTO familias (codfamilia,descripcion) VALUES ('$familia[codfamilia]','$familia[descripcion]');";
         $resultado = $this->bd->exec($consulta);
         if(!$resultado)
         {
            $error = "Error al insertar la familia:\n" . $consulta;
            $retorno = false;
         }
      }

      return $retorno;
   }

   /// elimina una familia, siempre y cuando no tenga articulos asignados
   public function eliminar($codfamilia, &$error)
   {
      $retorno = true;

      if($codfamilia)
      {
         $articulos = $this->bd->select("SELECT COUNT(referencia) as count FROM articulos WHERE codfamilia = '$codfamilia';");
         if($articulos[0]['count'] != 0)
         {
            $error = "No puede eliminarse esta familia porque contiene " . $articulos[0]['count'] . " art&iacute;culo(s)";
            $retorno = false;
         }

         if($retorno)
         {
            if(!$this->bd->exec("DELETE FROM familias WHERE codfamilia='$codfamilia';"))
            {
               $error = "Error al eliminar la familia";
               $retorno = false;
            }
         }
      }
      else
      {
         $error = "Debe especificar una familia";
         $retorno = false;
      }

      return($retorno);
   }
}

?>
