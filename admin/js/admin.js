/* ============================================================
   ADMIN.JS — Navegación global (todas las páginas del admin)
   Submenús, hamburguesa, cerrar sesión
   ============================================================ */
(function(){
'use strict';

document.addEventListener('DOMContentLoaded', function(){

  /* ── Submenús colapsables ── */
  [
    ['nav-inscripciones-toggle',  'submenu-inscripciones'],
    ['nav-programa-toggle',       'submenu-programa'],
    ['nav-preguntas-toggle',      'submenu-preguntas'],
    ['nav-administradores-toggle','submenu-administradores'],
  ].forEach(function(par){
    var toggle  = document.getElementById(par[0]);
    var submenu = document.getElementById(par[1]);
    if(toggle && submenu){
      toggle.addEventListener('click', function(){
        this.classList.toggle('open');
        submenu.classList.toggle('open');
      });
    }
  });

  /* ── Hamburguesa ── */
  var btnMenu = document.getElementById('btn-toggle-sidebar');
  if(btnMenu){
    btnMenu.addEventListener('click', function(){
      document.body.classList.toggle('sidebar-collapsed');
    });
  }

  /* ── Cerrar sesión ── */
  var btnCerrar = document.querySelector('.btn-cerrar-sesion');
  if(btnCerrar){
    btnCerrar.addEventListener('click', function(e){
      e.preventDefault();
      if(confirm('¿Deseas cerrar sesión?')){
        window.location.href = 'logout.php';
      }
    });
  }

  /* ── Responsive: colapsar sidebar en móvil ── */
  if(window.innerWidth < 768){
    document.body.classList.add('sidebar-collapsed');
  }

});

})();