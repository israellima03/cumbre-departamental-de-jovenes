/* ============================================================
   PROGRAMA.JS — Expositores, Temas, Eventos, Material, Moderadores
   ============================================================ */
(function(){
'use strict';

var _dias        = [];
var _expositores = [];
var _temas       = [];
var _moderadores = [];
var _grupos      = [];
var _eventos     = [];

var _eliminarAccion = null;
var _eliminarId     = null;

document.addEventListener('DOMContentLoaded', function(){
  initTabs();
  cargarTodo();
  initBotones();
  initCerrarModales();
  cargarGruposTabla();
});

/* ══════════════════════════════════════
   TABS
══════════════════════════════════════ */
function initTabs(){
  document.querySelectorAll('.prog-tab').forEach(function(btn){
    btn.addEventListener('click', function(){
      document.querySelectorAll('.prog-tab').forEach(function(b){ b.classList.remove('active'); });
      document.querySelectorAll('.prog-panel').forEach(function(p){ p.classList.remove('active'); });
      this.classList.add('active');
      var panel = document.getElementById('panel-'+this.dataset.tab);
      if(panel) panel.classList.add('active');
    });
  });
  /* abrir tab según parámetro URL */
  var params = new URLSearchParams(window.location.search);
  var tabParam = params.get('tab');
  if(tabParam){
    var btnTab = document.querySelector('.prog-tab[data-tab="'+tabParam+'"]');
    if(btnTab) btnTab.click();
  }
}

/* ══════════════════════════════════════
   CARGAR TODO
══════════════════════════════════════ */
function cargarTodo(){
  cargarExpositores();
  cargarTemas();
  cargarModeradores();
  cargarDias();
  cargarGrupos();
  cargarEventos();
  cargarMaterial();
}

/* ══════════════════════════════════════
   HELPERS PREVIEW ARCHIVOS
══════════════════════════════════════ */
window.previewImagen = function(input, imgId, wrapId, nombreId, lblId){
  var file = input.files[0];
  if(!file) return;
  var lbl = document.getElementById(lblId);
  if(lbl) lbl.textContent = file.name;
  var wrap = document.getElementById(wrapId);
  var img  = document.getElementById(imgId);
  var nom  = document.getElementById(nombreId);
  if(wrap) wrap.style.display = 'block';
  if(nom)  nom.textContent = file.name+' ('+Math.round(file.size/1024)+' KB)';
  if(img){
    var reader = new FileReader();
    reader.onload = function(e){ img.src = e.target.result; };
    reader.readAsDataURL(file);
  }
};

window.previewArchivo = function(input, wrapId, txtId, lblId){
  var file = input.files[0];
  if(!file) return;
  var wrap = document.getElementById(wrapId);
  var txt  = document.getElementById(txtId);
  var lbl  = document.getElementById(lblId);
  if(wrap) wrap.style.display = 'block';
  if(txt)  txt.textContent = file.name+' ('+Math.round(file.size/1024)+' KB)';
  if(lbl)  lbl.textContent = 'Cambiar archivo';
};

/* ══════════════════════════════════════
   EXPOSITORES
══════════════════════════════════════ */
function cargarExpositores(){
  fetch('api_programa.php?accion=listar_expositores')
  .then(function(r){ return r.json(); })
  .then(function(data){
    _expositores = data.expositores || [];
    renderExpositores(_expositores);
  }).catch(function(e){ console.error(e); });
}

function renderExpositores(lista){
  var tbody = document.getElementById('tbody-expositores');
  if(!lista.length){
    tbody.innerHTML = '<tr><td colspan="6" class="tabla-vacia">Sin expositores registrados</td></tr>';
    return;
  }
  tbody.innerHTML = lista.map(function(e,i){
    var imagen = (e.imagen || '').replace('img/','');

    var foto = imagen
      ? '<img src="../img/'+esc(imagen)+'" style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid var(--border);" onerror="this.outerHTML=\'<div style=&quot;width:36px;height:36px;border-radius:50%;background:var(--sidebar-bg);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;&quot;>'+((e.nombre||'')[0]||'').toUpperCase()+'</div>\'">'
      : '<div style="width:36px;height:36px;border-radius:50%;background:var(--sidebar-bg);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;">'+((e.nombre||'')[0]||'').toUpperCase()+'</div>';
    var acciones = PUEDE_EDITAR_PROG
      ? '<td><button class="btn-accion btn-ver" onclick="abrirModalExpositor(\''+encodeURIComponent(JSON.stringify(e))+'\')" title="Editar"><i class="fa-solid fa-pen"></i></button> '+
        '<button class="btn-accion" style="color:#dc2626;" onclick="confirmarEliminar(\'eliminar_expositor\','+e.id_expositor+',\'¿Eliminar a '+esc(e.nombre+' '+e.apellido)+'?\')"><i class="fa-solid fa-trash"></i></button></td>'
      : '';
    return '<tr>'+
      '<td>'+(i+1)+'</td>'+
      '<td>'+foto+'</td>'+
      '<td style="font-weight:600;">'+esc(e.nombre)+' '+esc(e.apellido)+'</td>'+
      '<td><span style="background:#E7E2DF;color:#0369a1;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:700;">'+esc(e.rango)+'</span></td>'+
      '<td style="max-width:200px;white-space:normal;font-size:12px;color:var(--txt-soft);">'+esc(e.descripcion||'—')+'</td>'+
      acciones+
    '</tr>';
  }).join('');
}

window.abrirModalExpositor = function(json){
  var e = json ? JSON.parse(decodeURIComponent(json)) : {};
  document.getElementById('exp-id').value            = e.id_expositor || '';
  document.getElementById('exp-nombre').value        = e.nombre       || '';
  document.getElementById('exp-apellido').value      = e.apellido     || '';
  document.getElementById('exp-rango').value         = e.rango        || '';
  document.getElementById('exp-descripcion').value   = e.descripcion  || '';
  document.getElementById('exp-imagen-actual').value = e.imagen       || '';
  document.getElementById('exp-imagen-file').value   = '';
  document.getElementById('exp-upload-lbl').textContent = 'Seleccionar foto (JPG, PNG, WEBP)';

  /* mostrar imagen actual si existe */
  var wrap = document.getElementById('exp-preview-wrap');
  var img  = document.getElementById('exp-preview-img');
  var nom  = document.getElementById('exp-preview-nombre');
  if(e.imagen){
    if(wrap) wrap.style.display = 'block';
    if(img){
      var imagen = (e.imagen || '').replace('img/','');
      img.src = '../img/' + imagen;
    }
    if(nom)  nom.textContent = e.imagen;
  } else {
    if(wrap) wrap.style.display = 'none';
    if(img)  img.src = '';
  }

  document.getElementById('modal-expositor-titulo').innerHTML =
    '<i class="fa-solid fa-microphone-lines"></i> '+(e.id_expositor?'Editar':'Nuevo')+' Expositor';
  abrirModal('modal-expositor');
};

/* ══════════════════════════════════════
   TEMAS
══════════════════════════════════════ */
function cargarTemas(){
  fetch('api_programa.php?accion=listar_temas')
  .then(function(r){ return r.json(); })
  .then(function(data){
    _temas = data.temas || [];
    renderTemas(_temas);
  }).catch(function(e){ console.error(e); });
}

function renderTemas(lista){
  var tbody = document.getElementById('tbody-temas');
  if(!lista.length){
    tbody.innerHTML = '<tr><td colspan="3" class="tabla-vacia">Sin temas registrados</td></tr>';
    return;
  }
  tbody.innerHTML = lista.map(function(t,i){
    var acciones = PUEDE_EDITAR_PROG
      ? '<td><button class="btn-accion btn-ver" onclick="abrirModalTema(\''+encodeURIComponent(JSON.stringify(t))+'\')" title="Editar"><i class="fa-solid fa-pen"></i></button> '+
        '<button class="btn-accion" style="color:#dc2626;" onclick="confirmarEliminar(\'eliminar_tema\','+t.id_tema+',\'¿Eliminar tema?\')"><i class="fa-solid fa-trash"></i></button></td>'
      : '';
    return '<tr>'+
      '<td>'+(i+1)+'</td>'+
      '<td style="font-weight:500;">'+esc(t.titulo)+'</td>'+
      acciones+
    '</tr>';
  }).join('');
}

window.abrirModalTema = function(json){
  var t = json ? JSON.parse(decodeURIComponent(json)) : {};
  document.getElementById('tema-id').value     = t.id_tema || '';
  document.getElementById('tema-titulo').value = t.titulo  || '';
  document.getElementById('modal-tema-titulo').innerHTML =
    '<i class="fa-solid fa-book-open"></i> '+(t.id_tema?'Editar':'Nuevo')+' Tema';
  abrirModal('modal-tema');
};

/* ══════════════════════════════════════
   DÍAS Y GRUPOS
══════════════════════════════════════ */
function cargarDias(){
  fetch('api_programa.php?accion=listar_dias')
  .then(function(r){ return r.json(); })
  .then(function(data){
    _dias = data.dias || [];
    var sel = document.getElementById('filtro-dia-evento');
    if(sel){
      sel.innerHTML = '<option value="">Todos los días</option>';
      _dias.forEach(function(d){
        sel.innerHTML += '<option value="'+d.id_dia+'">'+esc(d.nombre)+'</option>';
      });
    }
  });
}

function cargarGrupos(){
  fetch('api_programa.php?accion=listar_grupos')
  .then(function(r){ return r.json(); })
  .then(function(data){ _grupos = data.grupos || []; });
}

/* ══════════════════════════════════════
   EVENTOS
══════════════════════════════════════ */
function cargarEventos(){
  var dia  = (document.getElementById('filtro-dia-evento')||{}).value||'';
  var tipo = (document.getElementById('filtro-tipo-evento')||{}).value||'';
  var url  = 'api_programa.php?accion=listar_eventos';
  if(dia)  url += '&dia='+dia;
  if(tipo) url += '&tipo='+encodeURIComponent(tipo);
  fetch(url)
  .then(function(r){ return r.json(); })
  .then(function(data){ _eventos = data.eventos || []; renderEventos(_eventos); })
  .catch(function(e){ console.error(e); });
}
window.cargarEventos = cargarEventos;

function renderEventos(lista){
  var tbody = document.getElementById('tbody-eventos');
  if(!lista.length){
    tbody.innerHTML = '<tr><td colspan="11" class="tabla-vacia">Sin eventos</td></tr>';
    return;
  }
  tbody.innerHTML = lista.map(function(e,i){
    var tipo = '<span class="badge-tipo tipo-'+(e.tipo_evento||'otro')+'">'+esc(e.tipo_evento||'')+'</span>';
    var preg = e.preguntas_activas=='1'
      ? '<span style="color:var(--green);font-size:11px;font-weight:700;"><i class="fa-solid fa-check"></i> Activo</span>'
      : '<span style="color:var(--txt-xsoft);font-size:11px;">No</span>';
    var acciones = PUEDE_EDITAR_PROG
      ? '<td><button class="btn-accion btn-ver" onclick="abrirModalEvento(\''+encodeURIComponent(JSON.stringify(e))+'\')" title="Editar"><i class="fa-solid fa-pen"></i></button> '+
        '<button class="btn-accion" style="color:#dc2626;" onclick="confirmarEliminar(\'eliminar_evento\','+e.id_evento+',\'¿Eliminar este evento?\')"><i class="fa-solid fa-trash"></i></button></td>'
      : '';
    return '<tr>'+
      '<td>'+(i+1)+'</td>'+
      '<td>'+esc(e.dia_nombre||'—')+'</td>'+
      '<td style="font-size:12px;">'+esc(e.fecha||'')+'</td>'+
      '<td style="white-space:nowrap;font-size:12px;">'+esc(e.hora_inicio||'')+' – '+esc(e.hora_fin||'')+'</td>'+
      '<td>'+tipo+'</td>'+
      '<td style="font-size:12px;">'+esc(e.expositor_nombre||'—')+'</td>'+
      '<td style="max-width:130px;white-space:normal;font-size:11px;color:var(--txt-soft);">'+esc(e.tema_titulo||'—')+'</td>'+
      '<td style="font-size:12px;">'+esc(e.moderador_nombre||'—')+'</td>'+
      '<td style="font-size:12px;">'+esc(e.grupo_nombre||'—')+'</td>'+
      '<td>'+preg+'</td>'+
      acciones+
    '</tr>';
  }).join('');
}

window.abrirModalEvento = function(json){
  var ev = json ? JSON.parse(decodeURIComponent(json)) : {};

  var selDia = document.getElementById('evento-dia');
  selDia.innerHTML = '<option value="">-- Selecciona --</option>';
  _dias.forEach(function(d){
    selDia.innerHTML += '<option value="'+d.id_dia+'"'+(String(ev.id_dia)===String(d.id_dia)?' selected':'')+'>'+esc(d.nombre)+'</option>';
  });

  var selExp = document.getElementById('evento-expositor');
  selExp.innerHTML = '<option value="">Sin expositor</option>';
  _expositores.forEach(function(e){
    selExp.innerHTML += '<option value="'+e.id_expositor+'"'+(String(ev.id_expositor)===String(e.id_expositor)?' selected':'')+'>'+esc(e.nombre+' '+e.apellido)+'</option>';
  });

  var selTema = document.getElementById('evento-tema');
  selTema.innerHTML = '<option value="">Sin tema</option>';
  _temas.forEach(function(t){
    selTema.innerHTML += '<option value="'+t.id_tema+'"'+(String(ev.id_tema)===String(t.id_tema)?' selected':'')+'>'+esc(t.titulo)+'</option>';
  });

  var selMod = document.getElementById('evento-moderador');
  selMod.innerHTML = '<option value="">Sin moderador</option>';
  _moderadores.forEach(function(m){
    selMod.innerHTML += '<option value="'+m.id_moderador+'"'+(String(ev.id_moderador)===String(m.id_moderador)?' selected':'')+'>'+esc(m.nombre+' '+m.apellido)+'</option>';
  });

  var selGrupo = document.getElementById('evento-grupo');
  selGrupo.innerHTML = '<option value="">Sin grupo</option>';
  _grupos.forEach(function(g){
    selGrupo.innerHTML += '<option value="'+g.id_grupo+'"'+(String(ev.id_grupo)===String(g.id_grupo)?' selected':'')+'>'+esc(g.nombre_grupo)+'</option>';
  });

  document.getElementById('evento-id').value          = ev.id_evento  || '';
  document.getElementById('evento-fecha').value        = ev.fecha       || '';
  document.getElementById('evento-hora-inicio').value  = ev.hora_inicio || '';
  document.getElementById('evento-hora-fin').value     = ev.hora_fin    || '';
  document.getElementById('evento-tipo').value         = ev.tipo_evento || '';
  document.getElementById('evento-preguntas').checked  = ev.preguntas_activas == '1';
  document.getElementById('modal-evento-titulo').innerHTML =
    '<i class="fa-solid fa-calendar-days"></i> '+(ev.id_evento?'Editar':'Nuevo')+' Evento';
  toggleCamposEvento();
  abrirModal('modal-evento');
};

window.toggleCamposEvento = function(){
  /* mostrar todos los campos siempre — cualquier evento puede tener cualquier combinación */
  document.getElementById('campo-expositor').style.display  = '';
  document.getElementById('campo-grupo').style.display      = '';
  document.getElementById('campo-tema').style.display       = '';
  document.getElementById('campo-moderador').style.display  = '';
};


/* ══════════════════════════════════════
   MATERIAL
══════════════════════════════════════ */
function cargarMaterial(){
  fetch('api_programa.php?accion=listar_material')
  .then(function(r){ return r.json(); })
  .then(function(data){ renderMaterial(data.materiales||[]); })
  .catch(function(e){ console.error(e); });
}

function renderMaterial(lista){
  var tbody = document.getElementById('tbody-material');
  if(!lista.length){
    tbody.innerHTML = '<tr><td colspan="6" class="tabla-vacia">Sin material registrado</td></tr>';
    return;
  }
  var iconos = {pdf:'fa-file-pdf',ppt:'fa-file-powerpoint',img:'fa-file-image',zip:'fa-file-zipper',video:'fa-film',enlace:'fa-link'};
  tbody.innerHTML = lista.map(function(m,i){
    var icono    = iconos[m.tipo] || 'fa-file';
    var descarga = (m.descarga_activa=='1'||m.descarga_activa===undefined||m.descarga_activa===null)
      ? '<span class="descarga-si"><i class="fa-solid fa-unlock"></i> Habilitada</span>'
      : '<span class="descarga-no"><i class="fa-solid fa-lock"></i> Bloqueada</span>';
    var estaHabilitada = (m.descarga_activa=='1'||m.descarga_activa===undefined||m.descarga_activa===null);
    var eventoInfo = m.dia_nombre ? esc(m.dia_nombre)+' '+esc(m.hora_inicio||'') : 'Evento #'+m.id_evento;
    var acciones = PUEDE_EDITAR_PROG
      ? '<td style="white-space:nowrap;">'+
        '<button class="btn-accion btn-ver" onclick="abrirModalMaterial(\''+encodeURIComponent(JSON.stringify(m))+'\')" title="Editar"><i class="fa-solid fa-pen"></i></button> '+
        '<button class="btn-accion" style="color:'+(estaHabilitada?'var(--orange)':'var(--green)')+'" '+
          'onclick="toggleDescarga('+m.id+','+(estaHabilitada?0:1)+')" title="'+(estaHabilitada?'Bloquear descarga':'Habilitar descarga')+'">'+
          '<i class="fa-solid '+(estaHabilitada?'fa-lock':'fa-unlock')+'"></i></button> '+
        '<button class="btn-accion" style="color:#dc2626;" onclick="confirmarEliminar(\'eliminar_material\','+m.id+',\'¿Eliminar material?\')"><i class="fa-solid fa-trash"></i></button>'+
      '</td>'
      : '';
    return '<tr>'+
      '<td>'+(i+1)+'</td>'+
      '<td><i class="fa-solid '+icono+'" style="margin-right:6px;color:var(--accent);"></i>'+esc(m.nombre)+'</td>'+
      '<td style="font-size:12px;color:var(--txt-soft);">'+eventoInfo+'</td>'+
      '<td><span style="background:#f3f4f6;padding:2px 6px;border-radius:4px;font-size:11px;font-weight:700;">'+esc(m.tipo)+'</span></td>'+
      '<td>'+descarga+'</td>'+
      acciones+
    '</tr>';
  }).join('');
}

window.abrirModalMaterial = function(json){
  var m = json ? JSON.parse(decodeURIComponent(json)) : {};

  var selEv = document.getElementById('mat-evento');
  selEv.innerHTML = '<option value="">-- Selecciona evento --</option>';
  _eventos.forEach(function(e){
    var label = (e.dia_nombre||'Día ?')+' '+e.hora_inicio+' ('+e.tipo_evento+')';
    selEv.innerHTML += '<option value="'+e.id_evento+'"'+(String(m.id_evento)===String(e.id_evento)?' selected':'')+'>'+esc(label)+'</option>';
  });

  document.getElementById('mat-id').value               = m.id          || '';
  document.getElementById('mat-url-actual').value        = m.url         || '';
  document.getElementById('mat-nombre').value           = m.nombre      || '';
  document.getElementById('mat-tipo').value             = m.tipo        || 'pdf';
  document.getElementById('mat-url-texto').value        = (m.url && m.url.startsWith('http')) ? m.url : '';
  document.getElementById('mat-descripcion').value      = m.descripcion || '';
  document.getElementById('mat-descarga-activa').checked= (m.descarga_activa!='0');
  document.getElementById('mat-archivo-file').value     = '';
  document.getElementById('mat-upload-lbl').textContent = m.url && !m.url.startsWith('http')
    ? 'Archivo actual: '+m.url
    : 'Seleccionar archivo';
  var wrap = document.getElementById('mat-preview-wrap');
  var txt  = document.getElementById('mat-preview-txt');
  if(m.url && !m.url.startsWith('http')){
    if(wrap) wrap.style.display = 'block';
    if(txt)  txt.textContent = m.url;
  } else {
    if(wrap) wrap.style.display = 'none';
  }
  document.getElementById('modal-material-titulo').innerHTML =
    '<i class="fa-solid fa-folder-open"></i> '+(m.id?'Editar':'Subir')+' Material';
  abrirModal('modal-material');
};

window.toggleDescarga = function(id, valor){
  var fd = new FormData();
  fd.append('accion','toggle_descarga');
  fd.append('id', id);
  fd.append('valor', valor);
  fetch('api_programa.php', {method:'POST', body:fd})
  .then(function(r){ return r.json(); })
  .then(function(data){
    if(data.ok){ toast(data.msg,'ok'); cargarMaterial(); }
    else toast(data.msg,'error');
  });
};

/* ══════════════════════════════════════
   MODERADORES
══════════════════════════════════════ */
function cargarModeradores(){
  fetch('api_programa.php?accion=listar_moderadores')
  .then(function(r){ return r.json(); })
  .then(function(data){
    _moderadores = data.moderadores || [];
    renderModeradores(_moderadores);
  }).catch(function(e){ console.error(e); });
}

function renderModeradores(lista){
  var tbody = document.getElementById('tbody-moderadores');
  if(!lista.length){
    tbody.innerHTML = '<tr><td colspan="4" class="tabla-vacia">Sin moderadores</td></tr>';
    return;
  }
  tbody.innerHTML = lista.map(function(m,i){
    var acciones = PUEDE_EDITAR_PROG
      ? '<td><button class="btn-accion btn-ver" onclick="abrirModalModerador(\''+encodeURIComponent(JSON.stringify(m))+'\')" title="Editar"><i class="fa-solid fa-pen"></i></button> '+
        '<button class="btn-accion" style="color:#dc2626;" onclick="confirmarEliminar(\'eliminar_moderador\','+m.id_moderador+',\'¿Eliminar moderador?\')"><i class="fa-solid fa-trash"></i></button></td>'
      : '';
    return '<tr>'+
      '<td>'+(i+1)+'</td>'+
      '<td>'+esc(m.nombre)+'</td>'+
      '<td>'+esc(m.apellido)+'</td>'+
      acciones+
    '</tr>';
  }).join('');
}

window.abrirModalModerador = function(json){
  var m = json ? JSON.parse(decodeURIComponent(json)) : {};
  document.getElementById('mod-id').value       = m.id_moderador || '';
  document.getElementById('mod-nombre').value   = m.nombre       || '';
  document.getElementById('mod-apellido').value = m.apellido     || '';
  document.getElementById('modal-moderador-titulo').innerHTML =
    '<i class="fa-solid fa-person-chalkboard"></i> '+(m.id_moderador?'Editar':'Nuevo')+' Moderador';
  abrirModal('modal-moderador');
};

/* ══════════════════════════════════════
   INIT BOTONES GUARDAR
══════════════════════════════════════ */
function initBotones(){

  /* ── EXPOSITOR (usa FormData con archivo) ── */
  var btnExp = document.getElementById('btn-guardar-expositor');
  if(btnExp) btnExp.addEventListener('click', function(){
    var fd = new FormData();
    fd.append('accion',         'guardar_expositor');
    fd.append('id',             val('exp-id'));
    fd.append('nombre',         val('exp-nombre'));
    fd.append('apellido',       val('exp-apellido'));
    fd.append('rango',          val('exp-rango'));
    fd.append('descripcion',    document.getElementById('exp-descripcion').value.trim());
    fd.append('imagen_actual',  val('exp-imagen-actual'));
    var fileInput = document.getElementById('exp-imagen-file');
    if(fileInput && fileInput.files[0]) fd.append('imagen', fileInput.files[0]);
    enviarConArchivo(fd, 'modal-expositor', function(){ cargarExpositores(); });
  });

  /* ── TEMA ── */
  var btnTema = document.getElementById('btn-guardar-tema');
  if(btnTema) btnTema.addEventListener('click', function(){
    guardar('guardar_tema',{ id:val('tema-id'), titulo:val('tema-titulo') },'modal-tema', function(){ cargarTemas(); });
  });

  /* ── EVENTO ── */
  var btnEv = document.getElementById('btn-guardar-evento');
  if(btnEv) btnEv.addEventListener('click', function(){
    guardar('guardar_evento',{
      id: val('evento-id'), id_dia: val('evento-dia'), fecha: val('evento-fecha'),
      tipo_evento: val('evento-tipo'), hora_inicio: val('evento-hora-inicio'),
      hora_fin: val('evento-hora-fin'), id_expositor: val('evento-expositor'),
      id_tema: val('evento-tema'), id_moderador: val('evento-moderador'),
      id_grupo: val('evento-grupo'),
      preguntas_activas: document.getElementById('evento-preguntas').checked ? 1 : 0
    }, 'modal-evento', function(){ cargarEventos(); cargarMaterial(); });
  });

  /* ── MATERIAL (usa FormData con archivo) ── */
  var btnMat = document.getElementById('btn-guardar-material');
  if(btnMat) btnMat.addEventListener('click', function(){
    var fd = new FormData();
    fd.append('accion',          'guardar_material');
    fd.append('id',              val('mat-id'));
    fd.append('nombre',          val('mat-nombre'));
    fd.append('id_evento',       val('mat-evento'));
    fd.append('tipo',            val('mat-tipo'));
    fd.append('url',             val('mat-url-texto'));
    fd.append('url_actual',      val('mat-url-actual'));
    fd.append('descripcion',     val('mat-descripcion'));
    fd.append('descarga_activa', document.getElementById('mat-descarga-activa').checked ? 1 : 0);
    var fileInput = document.getElementById('mat-archivo-file');
    if(fileInput && fileInput.files[0]) fd.append('archivo', fileInput.files[0]);
    enviarConArchivo(fd, 'modal-material', function(){ cargarMaterial(); });
  });

  /* ── MODERADOR ── */
  var btnMod = document.getElementById('btn-guardar-moderador');
  if(btnMod) btnMod.addEventListener('click', function(){
    guardar('guardar_moderador',{ id:val('mod-id'), nombre:val('mod-nombre'), apellido:val('mod-apellido') },
      'modal-moderador', function(){ cargarModeradores(); });
  });

  /* ── CONFIRMAR ELIMINAR ── */
  var btnElim = document.getElementById('btn-confirmar-eliminar-prog');
  if(btnElim) btnElim.addEventListener('click', function(){
    if(!_eliminarAccion || !_eliminarId) return;
    var fd = new FormData();
    fd.append('accion', _eliminarAccion);
    fd.append('id', _eliminarId);
    btnElim.disabled = true;
    fetch('api_programa.php', {method:'POST', body:fd})
    .then(function(r){ return r.json(); })
    .then(function(data){
      btnElim.disabled = false;
      if(data.ok){ toast(data.msg,'ok'); cerrarModal('modal-confirmar-eliminar'); cargarTodo(); }
      else toast(data.msg,'error');
    }).catch(function(){ btnElim.disabled=false; toast('Error de conexion','error'); });
  });

  /* ── GRUPO ── */
  var btnGrupo = document.getElementById('btn-guardar-grupo');
  if(btnGrupo) btnGrupo.addEventListener('click', function(){
    guardar('guardar_grupo',{
      id: val('grupo-id'), nombre: val('grupo-nombre')
    }, 'modal-grupo', function(){ cargarGruposTabla(); cargarGrupos(); });
  });
}

window.confirmarEliminar = function(accion, id, msg){
  _eliminarAccion = accion; _eliminarId = id;
  var txt = document.getElementById('eliminar-prog-txt');
  if(txt) txt.textContent = msg || '¿Eliminar este elemento?';
  abrirModal('modal-confirmar-eliminar');
};

/* ══════════════════════════════════════
   HELPERS MODALES
══════════════════════════════════════ */
function initCerrarModales(){
  document.querySelectorAll('.modal-overlay').forEach(function(el){
    el.addEventListener('click', function(e){ if(e.target===this) this.style.display='none'; });
  });
  document.addEventListener('keydown', function(e){
    if(e.key==='Escape') document.querySelectorAll('.modal-overlay').forEach(function(el){ el.style.display='none'; });
  });
}
window.abrirModal  = function(id){ var el=document.getElementById(id); if(el) el.style.display='grid'; };
window.cerrarModal = function(id){ var el=document.getElementById(id); if(el) el.style.display='none'; };

/* ══════════════════════════════════════
   GUARDAR GENÉRICO (sin archivo)
══════════════════════════════════════ */
function guardar(accion, datos, modalId, callback){
  var fd = new FormData();
  fd.append('accion', accion);
  Object.keys(datos).forEach(function(k){ fd.append(k, datos[k]); });
  enviarConArchivo(fd, modalId, callback);
}

/* ══════════════════════════════════════
   ENVIAR CON ARCHIVO (FormData directo)
══════════════════════════════════════ */
function enviarConArchivo(fd, modalId, callback){
  fetch('api_programa.php', {method:'POST', body:fd})
  .then(function(r){ return r.json(); })
  .then(function(data){
    if(data.ok){ toast(data.msg,'ok'); cerrarModal(modalId); if(callback) callback(); }
    else toast(data.msg,'error');
  }).catch(function(){ toast('Error de conexion','error'); });
}

/* ══════════════════════════════════════
   HELPERS
══════════════════════════════════════ */
function val(id){ var el=document.getElementById(id); return el?el.value.trim():''; }
function toast(msg,tipo){
  var el=document.getElementById('toast-programa'); if(!el) return;
  el.textContent=msg; el.className='toast show'+(tipo?' toast-'+tipo:'');
  clearTimeout(toast._t); toast._t=setTimeout(function(){ el.classList.remove('show'); },3500);
}
function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

/* ══ GRUPOS DE ALABANZA ══ */
function cargarGruposTabla(){
  fetch('api_programa.php?accion=listar_grupos')
  .then(function(r){ return r.json(); })
  .then(function(data){
    _grupos = data.grupos || [];
    var tbody = document.getElementById('tbody-grupos');
    if(!tbody) return;
    if(!_grupos.length){
      tbody.innerHTML = '<tr><td colspan="3" class="tabla-vacia">Sin grupos registrados</td></tr>';
      return;
    }
    tbody.innerHTML = _grupos.map(function(g,i){
      var acciones = PUEDE_EDITAR_PROG
        ? '<td><button class="btn-accion btn-ver" onclick="abrirModalGrupo(\''+encodeURIComponent(JSON.stringify(g))+'\')" title="Editar"><i class="fa-solid fa-pen"></i></button> '+
          '<button class="btn-accion" style="color:#dc2626;" onclick="confirmarEliminar(\'eliminar_grupo\','+g.id_grupo+',\'¿Eliminar grupo?\')"><i class="fa-solid fa-trash"></i></button></td>'
        : '';
      return '<tr>'+
        '<td>'+(i+1)+'</td>'+
        '<td style="font-weight:500;">'+esc(g.nombre_grupo)+'</td>'+
        acciones+
      '</tr>';
    }).join('');
  });
}

window.abrirModalGrupo = function(json){
  var g = json ? JSON.parse(decodeURIComponent(json)) : {};
  document.getElementById('grupo-id').value     = g.id_grupo      || '';
  document.getElementById('grupo-nombre').value = g.nombre_grupo  || '';
  document.getElementById('modal-grupo-titulo').innerHTML =
    '<i class="fa-solid fa-music"></i> '+(g.id_grupo?'Editar':'Nuevo')+' Grupo de Alabanza';
  abrirModal('modal-grupo');
};

})();