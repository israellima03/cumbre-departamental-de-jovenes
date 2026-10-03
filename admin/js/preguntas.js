/* ============================================================
   PREGUNTAS.JS
   ============================================================ */
(function(){
'use strict';

var _eliminarId = null;

document.addEventListener('DOMContentLoaded', function(){
  if(SECCION_ACTUAL === 'habilitar' && PUEDE_GESTIONAR_P){
    cargarEventosPreguntas();
  } else {
    cargarPreguntas();
  }

  /* filtros ver preguntas */
  ['filtro-evento-preguntas','filtro-estado-preguntas'].forEach(function(id){
    var el = document.getElementById(id);
    if(el) el.addEventListener('change', cargarPreguntas);
  });

  /* modal eliminar */
  var modal = document.getElementById('modal-eliminar-pregunta');
  if(modal) modal.addEventListener('click', function(e){
    if(e.target === this) this.style.display = 'none';
  });

  var btnElim = document.getElementById('btn-confirmar-eliminar-pregunta');
  if(btnElim) btnElim.addEventListener('click', function(){
    if(!_eliminarId) return;
    var fd = new FormData();
    fd.append('accion','eliminar_pregunta');
    fd.append('id', _eliminarId);
    btnElim.disabled = true;
    fetch('api_preguntas.php', {method:'POST', body:fd})
    .then(function(r){ return r.json(); })
    .then(function(data){
      btnElim.disabled = false;
      if(data.ok){
        toast(data.msg,'ok');
        document.getElementById('modal-eliminar-pregunta').style.display='none';
        cargarPreguntas();
      } else toast(data.msg,'error');
    }).catch(function(){
      btnElim.disabled = false;
      toast('Error de conexion','error');
    });
  });
});

/* ══════════════════════════════════════
   EVENTOS CON TOGGLE DE PREGUNTAS
══════════════════════════════════════ */
function cargarEventosPreguntas(){
  fetch('api_preguntas.php?accion=listar_eventos_preguntas')
  .then(function(r){ return r.json(); })
  .then(function(data){ renderEventosPreguntas(data.eventos||[]); })
  .catch(function(e){ console.error(e); });
}

function renderEventosPreguntas(lista){
  var tbody = document.getElementById('tbody-eventos-preguntas');
  var lbl   = document.getElementById('eventos-total-lbl');
  if(lbl) lbl.textContent = lista.length + ' evento' + (lista.length!==1?'s':'');

  if(!lista.length){
    tbody.innerHTML = '<tr><td colspan="8" class="tabla-vacia">Sin eventos registrados</td></tr>';
    return;
  }

  tbody.innerHTML = lista.map(function(e, i){
    var tipo = '<span class="badge-tipo tipo-'+(e.tipo_evento||'otro')+'">'+esc(e.tipo_evento||'')+'</span>';
    var activo = e.preguntas_activas == '1';
    var toggle =
      '<label class="toggle-switch">'+
        '<input type="checkbox" '+(activo?'checked':'')+
          ' onchange="togglePreguntas('+e.id_evento+',this.checked?1:0,this)">'+
        '<div class="toggle-track"></div>'+
        '<span class="toggle-lbl">'+(activo?'Activo':'Inactivo')+'</span>'+
      '</label>';
    var total = e.total_preguntas > 0
      ? '<span style="background:#E7E2DF;color:#652C2A;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:700;">'+e.total_preguntas+' preguntas</span>'
      : '<span style="color:var(--txt-xsoft);font-size:12px;">0</span>';
    var persona = esc(e.expositor || e.grupo || '—');
    return '<tr>'+
      '<td>'+(i+1)+'</td>'+
      '<td>'+esc(e.dia||'—')+'</td>'+
      '<td style="white-space:nowrap;font-size:12px;">'+esc(e.hora_inicio||'')+' – '+esc(e.hora_fin||'')+'</td>'+
      '<td>'+tipo+'</td>'+
      '<td style="font-size:12px;">'+persona+'</td>'+
      '<td style="max-width:150px;white-space:normal;font-size:11px;color:var(--txt-soft);">'+esc(e.tema||'—')+'</td>'+
      '<td>'+total+'</td>'+
      '<td>'+toggle+'</td>'+
    '</tr>';
  }).join('');
}

window.togglePreguntas = function(id, valor, checkbox){
  var fd = new FormData();
  fd.append('accion','toggle_preguntas');
  fd.append('id', id);
  fd.append('valor', valor);
  fetch('api_preguntas.php', {method:'POST', body:fd})
  .then(function(r){ return r.json(); })
  .then(function(data){
    if(data.ok){
      toast(data.msg,'ok');
      /* actualizar etiqueta */
      var lbl = checkbox.parentElement.querySelector('.toggle-lbl');
      if(lbl) lbl.textContent = valor ? 'Activo' : 'Inactivo';
    } else {
      toast(data.msg,'error');
      checkbox.checked = !checkbox.checked; /* revertir */
    }
  }).catch(function(){
    toast('Error de conexion','error');
    checkbox.checked = !checkbox.checked;
  });
};

/* ══════════════════════════════════════
   VER PREGUNTAS
══════════════════════════════════════ */
function cargarPreguntas(){
  var evento = (document.getElementById('filtro-evento-preguntas')||{}).value||'';
  var estado = (document.getElementById('filtro-estado-preguntas')||{}).value||'';
  var url = 'api_preguntas.php?accion=listar_preguntas';
  if(evento) url += '&evento='+evento;
  if(estado) url += '&estado='+encodeURIComponent(estado);

  fetch(url)
  .then(function(r){ return r.json(); })
  .then(function(data){ renderPreguntas(data.preguntas||[]); })
  .catch(function(e){ console.error(e); });
}
window.cargarPreguntas = cargarPreguntas;

function renderPreguntas(lista){
  var tbody = document.getElementById('tbody-preguntas');
  var lbl   = document.getElementById('preguntas-total-lbl');
  if(lbl) lbl.textContent = lista.length + ' pregunta' + (lista.length!==1?'s':'');

  if(!lista.length){
    tbody.innerHTML = '<tr><td colspan="6" class="tabla-vacia">Sin preguntas</td></tr>';
    return;
  }

  var estadoColores = {
    pendiente:  'badge badge-pendiente-p',
    aprobada:   'badge badge-aprobada-p',
    rechazada:  'badge badge-rechazada-p'
  };

  tbody.innerHTML = lista.map(function(p, i){
    var estadoBadge = '<span class="'+(estadoColores[p.estado]||'badge')+'">'+esc(p.estado)+'</span>';
    var evento = esc((p.dia||''))+' '+esc((p.horario||''))+' <small style="color:var(--txt-xsoft);">'+esc(p.tipo_evento||'')+'</small>';
    var fecha = p.fecha_envio ? p.fecha_envio.substring(0,16).replace('T',' ') : '—';

    var acciones = '';
    if(PUEDE_GESTIONAR_P){
      acciones = '<td style="white-space:nowrap;">'+
        /* aprobar */
        (p.estado !== 'aprobada'
          ? '<button class="btn-accion" style="color:var(--green);" onclick="cambiarEstado('+p.id+',\'aprobada\')" title="Aprobar"><i class="fa-solid fa-check"></i></button> '
          : '')+
        /* rechazar */
        (p.estado !== 'rechazada'
          ? '<button class="btn-accion" style="color:var(--orange);" onclick="cambiarEstado('+p.id+',\'rechazada\')" title="Rechazar"><i class="fa-solid fa-xmark"></i></button> '
          : '')+
        /* eliminar */
        '<button class="btn-accion" style="color:#dc2626;" onclick="abrirEliminarPregunta('+p.id+')" title="Eliminar"><i class="fa-solid fa-trash"></i></button>'+
      '</td>';
    }

    return '<tr>'+
      '<td>'+(i+1)+'</td>'+
      '<td style="font-size:11px;">'+evento+'</td>'+
      '<td class="pregunta-txt">'+esc(p.pregunta)+'</td>'+
      '<td>'+estadoBadge+'</td>'+
      '<td style="font-size:11px;white-space:nowrap;">'+esc(fecha)+'</td>'+
      acciones+
    '</tr>';
  }).join('');
}

window.cambiarEstado = function(id, estado){
  var fd = new FormData();
  fd.append('accion','cambiar_estado');
  fd.append('id', id);
  fd.append('estado', estado);
  fetch('api_preguntas.php', {method:'POST', body:fd})
  .then(function(r){ return r.json(); })
  .then(function(data){
    if(data.ok){ toast(data.msg,'ok'); cargarPreguntas(); }
    else toast(data.msg,'error');
  }).catch(function(){ toast('Error de conexion','error'); });
};

window.abrirEliminarPregunta = function(id){
  _eliminarId = id;
  var modal = document.getElementById('modal-eliminar-pregunta');
  if(modal) modal.style.display = 'grid';
};

/* ══════════════════════════════════════
   HELPERS
══════════════════════════════════════ */
function toast(msg, tipo){
  var el = document.getElementById('toast-preguntas'); if(!el) return;
  el.textContent = msg;
  el.className = 'toast show'+(tipo?' toast-'+tipo:'');
  clearTimeout(toast._t);
  toast._t = setTimeout(function(){ el.classList.remove('show'); }, 3500);
}
function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

})();