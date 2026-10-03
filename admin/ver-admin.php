<?php 
   require_once('funciones/sesiones.php');
   usuario_autentificado();
   verificar_acceso(['Administrador','Lider departamental']);
   include_once 'templates/header.php'; 
   include_once 'templates/navegacion.php'; 
   include_once 'templates/barra.php';
   include_once 'funciones/funciones.php';
   $puede_editar = puede(['Administrador']);

   $roles = [];
   $res = $conn->query("SELECT id, nombre FROM roles ORDER BY id");
   if($res) while($r = $res->fetch_assoc()) $roles[] = $r;
?>

<main class="content content-crear-admin" id="main-content">

  <div class="ca-header">
    <div class="ca-header-text">
      <h1 class="ca-titulo">
        <i class="fa-solid fa-users-gear ca-titulo-icono"></i>
        Administradores del Sistema
      </h1>
      <p class="ca-subtitulo">Lista de todos los usuarios con acceso al panel administrativo.</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
      <?php if($puede_editar): ?>
      <a href="crear-admin.php" class="btn-primary" style="text-decoration:none;">
        <i class="fa-solid fa-user-plus"></i> Nuevo Admin
      </a>
      <?php endif; ?>
      <a href="admin-encuentro.php" class="ca-btn-volver">
        <i class="fa-solid fa-arrow-left"></i> Volver
      </a>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h3><i class="fa-solid fa-users"></i> Usuarios</h3>
      <span id="admins-total-lbl" style="font-size:12px;color:var(--txt-xsoft);"></span>
    </div>
    <div class="filtros-rapidos">
      <input type="text" id="filtro-admin" placeholder="Buscar por nombre o usuario..." class="input-filtro">
    </div>
    <div class="tabla-wrap" style="overflow-x:auto;">
      <table class="tabla-inscritos">
        <thead>
          <tr>
            <th>#</th>
            <th>Nombre</th>
            <th>Usuario</th>
            <th>Teléfono</th>
            <th>Distrito</th>
            <th>Iglesia</th>
            <th>Roles</th>
            <?php if($puede_editar): ?><th>Acciones</th><?php endif; ?>
          </tr>
        </thead>
        <tbody id="tbody-admins">
          <tr><td colspan="8" class="tabla-loading">
            <i class="fa-solid fa-spinner fa-spin"></i> Cargando...
          </td></tr>
        </tbody>
      </table>
    </div>
  </div>

</main>

<?php if($puede_editar): ?>
<!-- MODAL EDITAR -->
<div class="modal-overlay" id="modal-editar-admin" style="display:none;">
  <div class="modal" style="max-width:440px;">
    <div class="modal-header">
      <h3><i class="fa-solid fa-pen"></i> Editar Administrador</h3>
      <button class="modal-close" onclick="document.getElementById('modal-editar-admin').style.display='none'">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="edit-admin-id">
      <div style="display:flex;flex-direction:column;gap:14px;">

        <div class="modal-field">
          <span class="modal-label">Nombre completo *</span>
          <input type="text" id="edit-admin-nombre" class="input-filtro" style="width:100%;">
        </div>

        <div class="modal-field">
          <span class="modal-label">Teléfono</span>
          <input type="text" id="edit-admin-telefono" class="input-filtro" style="width:100%;" placeholder="Ej: 70000000">
        </div>

        <div class="modal-field">
          <span class="modal-label">Roles</span>
          <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:6px;">
            <?php foreach($roles as $r): ?>
              <label class="edit-rol-label"
                     style="display:flex;align-items:center;gap:6px;padding:7px 14px;
                            border:1.5px solid var(--border);border-radius:8px;
                            cursor:pointer;font-size:13px;transition:all .18s;">
                <input type="checkbox" name="edit-roles[]"
                       value="<?php echo $r['id']; ?>"
                       data-nombre="<?php echo htmlspecialchars($r['nombre']); ?>"
                       class="edit-rol-cb">
                <?php echo htmlspecialchars($r['nombre']); ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="document.getElementById('modal-editar-admin').style.display='none'">Cancelar</button>
      <button class="btn-primary" id="btn-guardar-admin">
        <i class="fa-solid fa-floppy-disk"></i> Guardar
      </button>
    </div>
  </div>
</div>

<!-- MODAL ELIMINAR -->
<div class="modal-overlay" id="modal-eliminar-admin" style="display:none;">
  <div class="modal" style="max-width:360px;">
    <div class="modal-header" style="background:#dc2626;">
      <h3><i class="fa-solid fa-trash"></i> Eliminar Administrador</h3>
      <button class="modal-close" onclick="document.getElementById('modal-eliminar-admin').style.display='none'">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div class="modal-body" style="text-align:center;padding:28px 20px;">
      <i class="fa-solid fa-triangle-exclamation" style="font-size:2.5em;color:#dc2626;margin-bottom:14px;display:block;"></i>
      <p id="eliminar-txt" style="font-size:14px;font-weight:600;color:var(--txt);">¿Eliminar este administrador?</p>
      <p style="font-size:12px;color:var(--txt-xsoft);margin-top:6px;">Esta acción no se puede deshacer.</p>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="document.getElementById('modal-eliminar-admin').style.display='none'">Cancelar</button>
      <button class="btn-primary" id="btn-confirmar-eliminar" style="background:#dc2626;">
        <i class="fa-solid fa-trash"></i> Eliminar
      </button>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="toast" id="toast-admins"></div>

<script>
var PUEDE_EDITAR_ADMIN = <?php echo $puede_editar ? 'true' : 'false'; ?>;
</script>
<script>
(function(){
'use strict';

var todosAdmins = [];
var adminElimId = null;

document.addEventListener('DOMContentLoaded', function(){
  cargarAdmins();

  var filtro = document.getElementById('filtro-admin');
  if(filtro) filtro.addEventListener('input', aplicarFiltro);

  ['modal-editar-admin','modal-eliminar-admin'].forEach(function(id){
    var el = document.getElementById(id);
    if(el) el.addEventListener('click', function(e){ if(e.target===this) this.style.display='none'; });
  });

  if(PUEDE_EDITAR_ADMIN){
    initRolesCheckboxes();
    initGuardar();
    initEliminar();
  }
});

/* ── CARGAR ── */
function cargarAdmins(){
  fetch('api_admins.php?accion=listar')
  .then(function(r){ return r.json(); })
  .then(function(data){
    todosAdmins = data.admins || [];
    renderAdmins(todosAdmins);
  }).catch(function(e){ console.error(e); });
}

function renderAdmins(lista){
  var tbody = document.getElementById('tbody-admins');
  var lbl   = document.getElementById('admins-total-lbl');
  if(lbl) lbl.textContent = lista.length + ' usuario' + (lista.length!==1?'s':'');

  if(!lista.length){
    tbody.innerHTML = '<tr><td colspan="8" class="tabla-vacia">Sin usuarios</td></tr>';
    return;
  }

  tbody.innerHTML = lista.map(function(u, i){
    var roles = (u.roles||'').split(',').filter(Boolean).map(function(r){
      return '<span style="display:inline-block;background:#F4F2F0;color:#2C2668;'+
             'border:1px solid #bfdbfe;border-radius:6px;padding:2px 8px;font-size:10px;font-weight:700;margin:1px;">'+
             esc(r.trim())+'</span>';
    }).join(' ');

    var acciones = '';
    if(PUEDE_EDITAR_ADMIN){
      var uJson = encodeURIComponent(JSON.stringify(u));
      acciones = '<td style="white-space:nowrap;">'+
        '<button class="btn-accion btn-ver" onclick="abrirEditar(\''+uJson+'\')" title="Editar">'+
          '<i class="fa-solid fa-pen"></i></button> '+
        '<button class="btn-accion" style="color:#dc2626;" onclick="abrirEliminar('+u.id+',\''+esc(u.nombre)+'\')" title="Eliminar">'+
          '<i class="fa-solid fa-trash"></i></button>'+
      '</td>';
    }

    return '<tr>'+
      '<td>'+(i+1)+'</td>'+
      '<td><div class="participante-cell">'+
        '<div class="participante-avatar">'+((u.nombre||'')[0]||'?').toUpperCase()+'</div>'+
        '<div class="participante-nombre">'+esc(u.nombre||'')+'</div>'+
      '</div></td>'+
      '<td><code style="background:var(--bg);padding:2px 8px;border-radius:4px;font-size:12px;">@'+esc(u.usuario||'')+'</code></td>'+
      '<td>'+esc(u.telefono||'—')+'</td>'+
      '<td>'+esc(u.distrito||'—')+'</td>'+
      '<td>'+esc(u.iglesia||'—')+'</td>'+
      '<td>'+roles+'</td>'+
      acciones+
    '</tr>';
  }).join('');
}

function aplicarFiltro(){
  var q = (document.getElementById('filtro-admin')||{}).value||'';
  renderAdmins(todosAdmins.filter(function(u){
    return !q || (u.nombre+' '+u.usuario).toLowerCase().includes(q.toLowerCase());
  }));
}

/* ── ROLES ESTILO ── */
function initRolesCheckboxes(){
  document.querySelectorAll('.edit-rol-cb').forEach(function(cb){
    cb.addEventListener('change', function(){ estiloRol(this); });
  });
}
function estiloRol(cb){
  var lbl = cb.closest('label');
  if(!lbl) return;
  lbl.style.borderColor = cb.checked ? '#2C2668' : '';
  lbl.style.background  = cb.checked ? '#F4F2F0' : '';
  lbl.style.fontWeight  = cb.checked ? '700'     : '';
}

/* ── ABRIR EDITAR ── */
window.abrirEditar = function(json){
  var u = JSON.parse(decodeURIComponent(json));
  document.getElementById('edit-admin-id').value       = u.id;
  document.getElementById('edit-admin-nombre').value   = u.nombre||'';
  document.getElementById('edit-admin-telefono').value = u.telefono||'';

  var rolesIds = (u.roles_ids||'').split(',').map(function(r){ return r.trim(); });
  document.querySelectorAll('.edit-rol-cb').forEach(function(cb){
    cb.checked = rolesIds.includes(String(cb.value));
    estiloRol(cb);
  });

  document.getElementById('modal-editar-admin').style.display = 'grid';
};

/* ── GUARDAR ── */
function initGuardar(){
  var btn = document.getElementById('btn-guardar-admin');
  if(!btn) return;
  btn.addEventListener('click', function(){
    var nombre = document.getElementById('edit-admin-nombre').value.trim();
    if(!nombre){ toast('El nombre es obligatorio','error'); return; }

    var roles = [];
    document.querySelectorAll('.edit-rol-cb:checked').forEach(function(cb){ roles.push(cb.value); });

    var fd = new FormData();
    fd.append('accion',   'editar_admin');
    fd.append('id',       document.getElementById('edit-admin-id').value);
    fd.append('nombre',   nombre);
    fd.append('telefono', document.getElementById('edit-admin-telefono').value.trim());
    roles.forEach(function(r){ fd.append('roles[]', r); });

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

    fetch('api_admins.php', {method:'POST', body:fd})
    .then(function(r){ return r.json(); })
    .then(function(data){
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar';
      if(data.ok){
        toast(data.msg,'ok');
        document.getElementById('modal-editar-admin').style.display = 'none';
        cargarAdmins();
      } else toast(data.msg,'error');
    }).catch(function(){
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar';
      toast('Error de conexion','error');
    });
  });
}

/* ── ELIMINAR ── */
window.abrirEliminar = function(id, nombre){
  adminElimId = id;
  var txt = document.getElementById('eliminar-txt');
  if(txt) txt.textContent = '¿Eliminar a "'+nombre+'"?';
  document.getElementById('modal-eliminar-admin').style.display = 'grid';
};

function initEliminar(){
  var btn = document.getElementById('btn-confirmar-eliminar');
  if(!btn) return;
  btn.addEventListener('click', function(){
    if(!adminElimId) return;
    var fd = new FormData();
    fd.append('accion','eliminar_admin');
    fd.append('id', adminElimId);
    btn.disabled = true;
    fetch('api_admins.php', {method:'POST', body:fd})
    .then(function(r){ return r.json(); })
    .then(function(data){
      btn.disabled = false;
      if(data.ok){
        toast(data.msg,'ok');
        document.getElementById('modal-eliminar-admin').style.display = 'none';
        cargarAdmins();
      } else toast(data.msg,'error');
    }).catch(function(){
      btn.disabled = false;
      toast('Error de conexion','error');
    });
  });
}

function toast(msg, tipo){
  var el = document.getElementById('toast-admins'); if(!el) return;
  el.textContent = msg;
  el.className = 'toast show'+(tipo?' toast-'+tipo:'');
  setTimeout(function(){ el.classList.remove('show'); }, 3200);
}
function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

})();
</script>

<?php $js_pagina = []; ?>
<?php include_once 'templates/footer.php'; ?>