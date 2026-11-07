/* ============================================================
   EDITAR-CUPOS.JS
   ============================================================ */
(function(){
'use strict';

var todosDescuentos = [];
var todosDistritos  = [];
var todasIglesias   = [];
var estadoActivo    = 1;

/* un solo DOMContentLoaded */
document.addEventListener('DOMContentLoaded', function(){
  cargarTodo();
  initBotones();
  initGuardar();
  initEmergencia();
  initBuscadorIglesia();
  initCerrarModales();

  /* cupos y estado del sistema sin recargar la página */
  setInterval(function(){
    if(document.hidden) return;
    cargarPaquetes(true);
    cargarEstadoEmergencia(true);
  }, 6000);
});

function cargarTodo(){
  cargarPaquetes();
  cargarDescuentos();
  cargarTipos();
  cargarIglesias();
  cargarDistritos();
  cargarEstadoEmergencia();
}

/* ══ PAQUETES ══ */
function cargarPaquetes(auto){
  window.consultarJSON('api_gestion.php?accion=listar_paquetes' + (auto ? '&auto=1' : ''))
  .then(function(data){
    var tbody = document.getElementById('tbody-paquetes');
    if(!data.paquetes || !data.paquetes.length){
      tbody.innerHTML = '<tr><td colspan="5" class="tabla-vacia">Sin paquetes</td></tr>'; return;
    }
    tbody.innerHTML = data.paquetes.map(function(p){
      var desc = p.descuento_nombre
        ? '<span class="badge-promo">'+esc(p.descuento_nombre)+' -'+p.porcentaje+'%</span>'
        : '<span style="color:var(--txt-xsoft);font-size:11px;">Sin promo</span>';
      var json = encodeURIComponent(JSON.stringify(p));
      return '<tr>'+
        '<td><strong>'+esc(p.nombre)+'</strong><br><small>'+desc+'</small></td>'+
        '<td>Bs. '+parseFloat(p.precio).toFixed(2)+'</td>'+
        '<td>'+p.cupo_total+'</td>'+
        '<td><span class="'+(p.cupos_disponibles<=5?'cupos-bajo':'cupos-ok')+'">'+p.cupos_disponibles+'</span></td>'+
        '<td>'+
          '<button class="btn-accion btn-ver" onclick="editarPaquete(\''+json+'\')" title="Editar"><i class="fa-solid fa-pen"></i></button> '+
          '<button class="btn-accion btn-eliminar" onclick="eliminarItem(\'paquete\','+p.id+')" title="Eliminar"><i class="fa-solid fa-trash"></i></button>'+
        '</td>'+
      '</tr>';
    }).join('');
  }).catch(function(e){ console.error('paquetes:',e); });
}

window.editarPaquete = function(json){
  var p = JSON.parse(decodeURIComponent(json));
  document.getElementById('paq-id').value         = p.id;
  document.getElementById('paq-nombre').value     = p.nombre;
  document.getElementById('paq-precio').value     = p.precio;
  document.getElementById('paq-cupos').value      = p.cupo_total;
  document.getElementById('paq-cupos-disp').value = p.cupos_disponibles;
  poblarSelectDescuentos(p.descuento_id || '');
  document.getElementById('modal-paquete-titulo').innerHTML = '<i class="fa-solid fa-pen"></i> Editar Paquete';
  abrirModal('modal-paquete-overlay');
};

function poblarSelectDescuentos(sel_id){
  var sel = document.getElementById('paq-descuento');
  if(!sel) return;
  sel.innerHTML = '<option value="">Sin promocion</option>' +
    todosDescuentos.map(function(d){
      return '<option value="'+d.id+'"'+(d.id==sel_id?' selected':'')+'>'+esc(d.nombre)+' ('+d.porcentaje+'%)</option>';
    }).join('');
}

/* ══ DESCUENTOS ══ */
function cargarDescuentos(){
  fetch('api_gestion.php?accion=listar_descuentos')
  .then(function(r){ return r.json(); })
  .then(function(data){
    todosDescuentos = data.descuentos || [];
    var tbody = document.getElementById('tbody-promos');
    if(!todosDescuentos.length){
      tbody.innerHTML = '<tr><td colspan="5" class="tabla-vacia">Sin promociones</td></tr>'; return;
    }
    tbody.innerHTML = todosDescuentos.map(function(d){
      var hasta = d.fecha_fin ? d.fecha_fin.substring(0,10) : '—';
      var badge = parseInt(d.activo)
        ? '<span class="badge badge-confirmado">Activa</span>'
        : '<span class="badge badge-pendiente">Inactiva</span>';
      var json = encodeURIComponent(JSON.stringify(d));
      return '<tr>'+
        '<td><strong>'+esc(d.nombre)+'</strong></td>'+
        '<td><strong>'+d.porcentaje+'%</strong></td>'+
        '<td>'+hasta+'</td>'+
        '<td>'+badge+'</td>'+
        '<td>'+
          '<button class="btn-accion btn-ver" onclick="editarDescuento(\''+json+'\')" title="Editar"><i class="fa-solid fa-pen"></i></button> '+
          '<button class="btn-accion btn-eliminar" onclick="eliminarItem(\'descuento\','+d.id+')" title="Eliminar"><i class="fa-solid fa-trash"></i></button>'+
        '</td>'+
      '</tr>';
    }).join('');
  }).catch(function(e){ console.error('descuentos:',e); });
}

window.editarDescuento = function(json){
  var d = JSON.parse(decodeURIComponent(json));
  document.getElementById('promo-id').value         = d.id;
  document.getElementById('promo-nombre').value     = d.nombre;
  document.getElementById('promo-porcentaje').value = d.porcentaje;
  document.getElementById('promo-activo').value     = d.activo;
  document.getElementById('promo-inicio').value     = d.fecha_inicio ? d.fecha_inicio.replace(' ','T') : '';
  document.getElementById('promo-fin').value        = d.fecha_fin    ? d.fecha_fin.replace(' ','T')    : '';
  document.getElementById('modal-promo-titulo').innerHTML = '<i class="fa-solid fa-pen"></i> Editar Promocion';
  abrirModal('modal-promo-overlay');
};

/* ══ TIPOS ══ */
function cargarTipos(){
  fetch('api_gestion.php?accion=listar_tipos')
  .then(function(r){ return r.json(); })
  .then(function(data){
    var tbody = document.getElementById('tbody-tipos');
    if(!data.tipos || !data.tipos.length){
      tbody.innerHTML = '<tr><td colspan="2" class="tabla-vacia">Sin tipos</td></tr>'; return;
    }
    tbody.innerHTML = data.tipos.map(function(t){
      return '<tr>'+
        '<td><strong>'+esc(t.nombre)+'</strong></td>'+
        '<td>'+
          '<button class="btn-accion btn-ver" onclick="editarTipo('+t.id+',\''+esc(t.nombre)+'\')" title="Editar"><i class="fa-solid fa-pen"></i></button> '+
          '<button class="btn-accion btn-eliminar" onclick="eliminarItem(\'tipo\','+t.id+')" title="Eliminar"><i class="fa-solid fa-trash"></i></button>'+
        '</td>'+
      '</tr>';
    }).join('');
  }).catch(function(e){ console.error('tipos:',e); });
}

window.editarTipo = function(id, nombre){
  document.getElementById('tipo-id').value     = id;
  document.getElementById('tipo-nombre').value = decodeURIComponent(nombre);
  document.getElementById('modal-tipo-titulo').innerHTML = '<i class="fa-solid fa-pen"></i> Editar Tipo';
  abrirModal('modal-tipo-overlay');
};

/* ══ IGLESIAS ══ */
function cargarIglesias(){
  fetch('api_gestion.php?accion=listar_iglesias')
  .then(function(r){ return r.json(); })
  .then(function(data){
    todasIglesias = data.iglesias || [];
    renderIglesias(todasIglesias);
  }).catch(function(e){ console.error('iglesias:',e); });
}

function renderIglesias(lista){
  var tbody = document.getElementById('tbody-iglesias');
  if(!lista.length){
    tbody.innerHTML = '<tr><td colspan="3" class="tabla-vacia">Sin iglesias</td></tr>'; return;
  }
  tbody.innerHTML = lista.map(function(i){
    var json = encodeURIComponent(JSON.stringify(i));
    return '<tr>'+
      '<td><strong>'+esc(i.iglesia)+'</strong></td>'+
      '<td><span class="distrito-badge">'+esc(i.distrito||'Sin distrito')+'</span></td>'+
      '<td>'+
        '<button class="btn-accion btn-ver" onclick="editarIglesia(\''+json+'\')" title="Editar"><i class="fa-solid fa-pen"></i></button> '+
        '<button class="btn-accion btn-eliminar" onclick="eliminarItem(\'iglesia\','+i.id+')" title="Eliminar"><i class="fa-solid fa-trash"></i></button>'+
      '</td>'+
    '</tr>';
  }).join('');
}

function cargarDistritos(){
  fetch('api_gestion.php?accion=listar_distritos')
  .then(function(r){ return r.json(); })
  .then(function(data){ todosDistritos = data.distritos || []; })
  .catch(function(e){ console.error('distritos:',e); });
}

function poblarSelectDistritos(sel_id){
  var sel = document.getElementById('igl-distrito');
  if(!sel) return;
  sel.innerHTML = '<option value="">-- Sin distrito --</option>' +
    todosDistritos.map(function(d){
      return '<option value="'+d.id+'"'+(d.id==sel_id?' selected':'')+'>'+esc(d.nombre)+'</option>';
    }).join('');
}

window.editarIglesia = function(json){
  var i = JSON.parse(decodeURIComponent(json));
  document.getElementById('igl-id').value     = i.id;
  document.getElementById('igl-nombre').value = i.iglesia;
  poblarSelectDistritos(i.distrito_id || '');
  document.getElementById('modal-iglesia-titulo').innerHTML = '<i class="fa-solid fa-pen"></i> Editar Iglesia';
  abrirModal('modal-iglesia-overlay');
};

function initBuscadorIglesia(){
  var inp = document.getElementById('buscar-iglesia');
  if(!inp) return;
  inp.addEventListener('input', function(){
    var q = this.value.toLowerCase().trim();
    renderIglesias(!q ? todasIglesias : todasIglesias.filter(function(i){
      return i.iglesia.toLowerCase().includes(q) || (i.distrito||'').toLowerCase().includes(q);
    }));
  });
}

/* ══ ELIMINAR ══ */
window.eliminarItem = function(tipo, id){
  var nombres = {paquete:'este paquete', descuento:'esta promocion', tipo:'este tipo', iglesia:'esta iglesia'};
  if(!confirm('Eliminar '+nombres[tipo]+'?')) return;
  var fd = new FormData();
  fd.append('accion', 'eliminar_'+tipo);
  fd.append('id', id);
  fetchPost(fd, function(data){
    if(data.ok){
      toast('Eliminado correctamente','ok');
      if(tipo==='paquete')   cargarPaquetes();
      if(tipo==='descuento'){ cargarDescuentos(); cargarPaquetes(); }
      if(tipo==='tipo')      cargarTipos();
      if(tipo==='iglesia')   cargarIglesias();
    } else toast(data.msg,'error');
  });
};

/* ══ BOTONES NUEVO ══ */
function initBotones(){
  on('btn-nuevo-paquete', 'click', function(){
    limpiarCampos(['paq-id','paq-nombre','paq-precio','paq-cupos','paq-cupos-disp']);
    poblarSelectDescuentos('');
    document.getElementById('modal-paquete-titulo').innerHTML = '<i class="fa-solid fa-plus"></i> Nuevo Paquete';
    abrirModal('modal-paquete-overlay');
  });
  on('btn-nueva-promo', 'click', function(){
    limpiarCampos(['promo-id','promo-nombre','promo-porcentaje','promo-inicio','promo-fin']);
    document.getElementById('promo-activo').value = '1';
    document.getElementById('modal-promo-titulo').innerHTML = '<i class="fa-solid fa-plus"></i> Nueva Promocion';
    abrirModal('modal-promo-overlay');
  });
  on('btn-nuevo-tipo', 'click', function(){
    limpiarCampos(['tipo-id','tipo-nombre']);
    document.getElementById('modal-tipo-titulo').innerHTML = '<i class="fa-solid fa-plus"></i> Nuevo Tipo';
    abrirModal('modal-tipo-overlay');
  });
  on('btn-nueva-iglesia', 'click', function(){
    limpiarCampos(['igl-id','igl-nombre']);
    poblarSelectDistritos('');
    document.getElementById('modal-iglesia-titulo').innerHTML = '<i class="fa-solid fa-plus"></i> Nueva Iglesia';
    abrirModal('modal-iglesia-overlay');
  });
}

/* ══ GUARDAR ══ */
function initGuardar(){
  on('btn-guardar-paquete', 'click', function(){
    var fd = new FormData();
    fd.append('accion','guardar_paquete');
    fd.append('id',               val('paq-id'));
    fd.append('nombre',           val('paq-nombre'));
    fd.append('precio',           val('paq-precio'));
    fd.append('cupos',            val('paq-cupos'));
    fd.append('cupos_disponibles',val('paq-cupos-disp'));
    fd.append('descuento_id',     val('paq-descuento'));
    fetchPost(fd, function(data){
      if(data.ok){ toast(data.msg,'ok'); cerrarModal('modal-paquete-overlay'); cargarPaquetes(); }
      else toast(data.msg,'error');
    });
  });

  on('btn-guardar-promo', 'click', function(){
    var fd = new FormData();
    fd.append('accion',      'guardar_descuento');
    fd.append('id',          val('promo-id'));
    fd.append('nombre',      val('promo-nombre'));
    fd.append('porcentaje',  val('promo-porcentaje'));
    fd.append('activo',      val('promo-activo'));
    fd.append('fecha_inicio',val('promo-inicio').replace('T',' '));
    fd.append('fecha_fin',   val('promo-fin').replace('T',' '));
    fetchPost(fd, function(data){
      if(data.ok){ toast(data.msg,'ok'); cerrarModal('modal-promo-overlay'); cargarDescuentos(); cargarPaquetes(); }
      else toast(data.msg,'error');
    });
  });

  on('btn-guardar-tipo', 'click', function(){
    var fd = new FormData();
    fd.append('accion', 'guardar_tipo');
    fd.append('id',     val('tipo-id'));
    fd.append('nombre', val('tipo-nombre'));
    fetchPost(fd, function(data){
      if(data.ok){ toast(data.msg,'ok'); cerrarModal('modal-tipo-overlay'); cargarTipos(); }
      else toast(data.msg,'error');
    });
  });

  on('btn-guardar-iglesia', 'click', function(){
    var fd = new FormData();
    fd.append('accion',      'guardar_iglesia');
    fd.append('id',          val('igl-id'));
    fd.append('nombre',      val('igl-nombre'));
    fd.append('distrito_id', val('igl-distrito'));
    fetchPost(fd, function(data){
      if(data.ok){ toast(data.msg,'ok'); cerrarModal('modal-iglesia-overlay'); cargarIglesias(); }
      else toast(data.msg,'error');
    });
  });
  on('btn-guardar-qr', 'click', function(){
      var file = document.getElementById('nuevo-qr-file').files[0];
      if(!file){ toast('Selecciona una imagen','warn'); return; }
      var fd = new FormData();
      fd.append('accion', 'subir_qr');
      fd.append('qr', file);
      var msg = document.getElementById('qr-msg');
      msg.textContent = 'Subiendo...';
      fetch('api_gestion.php', {method:'POST', body:fd})
      .then(function(r){ return r.json(); })
      .then(function(data){
          if(data.ok){
              toast(data.msg,'ok');
              msg.textContent = '';
              /* actualizar preview */
              document.getElementById('qr-preview').src = '../img/' + data.archivo + '?t=' + Date.now();
              document.getElementById('nuevo-qr-file').value = '';
          } else {
              toast(data.msg,'error');
              msg.textContent = data.msg;
          }
      })
      .catch(function(){ toast('Error de conexion','error'); });
  });
}

/* ══ EMERGENCIA ══ */
function cargarEstadoEmergencia(auto){
  window.consultarJSON('api_gestion.php?accion=estado_inscripciones' + (auto ? '&auto=1' : ''))
  .then(function(data){ estadoActivo = data.activo; actualizarUIEmergencia(estadoActivo); })
  .catch(function(e){ console.error('emergencia:',e); });
}

function actualizarUIEmergencia(activo){
  var wrap   = document.getElementById('emergencia-wrap');
  var titulo = document.getElementById('emergencia-titulo');
  var desc   = document.getElementById('emergencia-desc');
  var btnTxt = document.getElementById('emergencia-btn-txt');
  var btnIco = document.getElementById('emergencia-btn-icon');
  var btn    = document.getElementById('btn-emergencia');
  var icono  = document.getElementById('emergencia-icono');
  if(!wrap) return;
  if(activo){
    wrap.classList.remove('emergencia-pausada');
    icono.style.background = '#10b981';
    titulo.textContent     = 'Sistema de Inscripciones: ACTIVO';
    desc.textContent       = 'Las inscripciones estan habilitadas. Presiona el boton para pausarlas.';
    btnTxt.textContent     = 'Pausar Inscripciones';
    btnIco.className       = 'fa-solid fa-circle-stop';
    btn.style.background   = '#dc2626';
  } else {
    wrap.classList.add('emergencia-pausada');
    icono.style.background = '#dc2626';
    titulo.textContent     = 'Sistema de Inscripciones: PAUSADO';
    desc.textContent       = 'Las inscripciones estan DETENIDAS. Nadie puede inscribirse.';
    btnTxt.textContent     = 'Reactivar Inscripciones';
    btnIco.className       = 'fa-solid fa-circle-play';
    btn.style.background   = '#10b981';
  }
}

function initEmergencia(){
  on('btn-emergencia', 'click', function(){
    document.getElementById('modal-emergencia-txt').textContent = estadoActivo
      ? 'Estas seguro de PAUSAR las inscripciones?'
      : 'Estas seguro de REACTIVAR las inscripciones?';
    abrirModal('modal-emergencia-overlay');
  });
  on('btn-confirmar-emergencia', 'click', function(){
    var fd = new FormData();
    fd.append('accion', 'toggle_inscripciones');
    fd.append('activo', estadoActivo ? 0 : 1);
    fetchPost(fd, function(data){
      if(data.ok){
        estadoActivo = data.activo;
        actualizarUIEmergencia(estadoActivo);
        cerrarModal('modal-emergencia-overlay');
        toast(data.msg, estadoActivo ? 'ok' : 'warn');
      } else toast(data.msg,'error');
    });
  });
}

/* ══ MODALES ══ */
function abrirModal(id){ var el=document.getElementById(id); if(el) el.classList.add('open'); }
window.cerrarModal = function(id){ var el=document.getElementById(id); if(el) el.classList.remove('open'); };

function initCerrarModales(){
  ['modal-paquete-overlay','modal-promo-overlay','modal-tipo-overlay',
   'modal-iglesia-overlay','modal-emergencia-overlay'].forEach(function(id){
    var el = document.getElementById(id);
    if(el) el.addEventListener('click', function(e){ if(e.target===this) this.classList.remove('open'); });
  });
}

/* ══ HELPERS ══ */
function on(id, ev, fn){ var el=document.getElementById(id); if(el) el.addEventListener(ev,fn); }
function val(id){ var el=document.getElementById(id); return el?el.value:''; }
function limpiarCampos(ids){ ids.forEach(function(id){ var el=document.getElementById(id); if(el) el.value=''; }); }
function fetchPost(fd, cb){
  fetch('api_gestion.php',{method:'POST',body:fd})
  .then(function(r){ return r.json(); }).then(cb)
  .catch(function(){ toast('Error de conexion','error'); });
}
function toast(msg, tipo){
  var el = document.getElementById('toast-cupos');
  if(!el) return;
  el.textContent = msg;
  el.className   = 'toast show'+(tipo?' toast-'+tipo:'');
  setTimeout(function(){ el.classList.remove('show'); }, 3200);
}
function esc(s){
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

})();