/// Funciones basicas de la interfaz principal

function fs_selec_user()
{
   var user = document.fscripts.user.options[document.fscripts.user.selectedIndex].value;
   if(user == '-')
      window.location.href = 'index.php';
   else if(user != '')
      window.location.href = 'index.php?usuario='+user;
}

function fs_selec_mod()
{
   document.fscripts.pag.selectedIndex = 0;
   document.fscripts.submit();
}

function fs_selec_pag()
{
   document.fscripts.submit();
}

function fs_selec_cmod()
{
   var enlace = document.fscripts.cmod.options[document.fscripts.cmod.selectedIndex].value;
   if(enlace != '')
      window.location.href = enlace;
}

function fs_post_to_url(path, params)
{
    var form = document.createElement("form");
    form.setAttribute("method", "POST");
    form.setAttribute("action", path);

    for(var key in params) {
        var hiddenField = document.createElement("input");
        hiddenField.setAttribute("type", "hidden");
        hiddenField.setAttribute("name", key);
        hiddenField.setAttribute("value", params[key]);
        form.appendChild(hiddenField);
    }

    document.body.appendChild(form);
    form.submit();
}

function fs_nuevo_articulo()
{
   var popup = document.getElementById('popup_nuevo_articulo');
   if( popup )
   {
      popup.style.display = 'block';
      document.f_p_nuevo_articulo.referencia.focus();
   }
}

function fs_nuevo_articulo_cerrar()
{
   var popup = document.getElementById('popup_nuevo_articulo');
   if( popup )
   {
      popup.style.display = 'none';
   }
}

function fs_nueva_familia()
{
   var popup = document.getElementById('popup_nueva_familia');
   if( popup )
   {
      popup.style.display = 'block';
      document.f_p_nueva_familia.codfamilia.focus();
   }
}

function fs_nueva_familia_cerrar()
{
   var popup = document.getElementById('popup_nueva_familia');
   if( popup )
   {
      popup.style.display = 'none';
   }
}