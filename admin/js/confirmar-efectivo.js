/* ============================================================
   CONFIRMAR-EFECTIVO.JS — Pestañas y confirmación de pagos en efectivo
   ============================================================ */
(function(){
'use strict';

var lista = [];
var puedeConfirmar = false;
var puedeEliminar  = false;

/* ── PESTAÑAS ── */
document.querySelectorAll('.ci-tab').forEach(function(btn){
  btn.addEventListener('click', function(){
    document.querySelectorAll('.ci-tab').forEach(function(b){ b.classList.remove('active'); });
    document.querySelectorAll('.ci-panel').forEach(function(p){ p.classList.remove('active'); });
    this.classList.add('active');
    var panel = document.getElementById('panel-' + this.dataset.tab);
    if(panel) panel.classList.add('active');
    if(this.dataset.tab === 'efectivo') cargar();
  });
});

/* ── CARGAR ── */
function cargar(){
  var tbody = document.getElementById('tbody-efectivo');
  tbody.innerHTML = '<tr><td colspan="12" class="tabla-loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</td></tr>';

  var url = 'api_qr.php?accion=listar_efectivo'
    + '&buscar='      + encodeURIComponent(document.getElementById('ef-buscar').value.trim())
    + '&distrito_id=' + encodeURIComponent(document.getElementById('ef-distrito').value)
    + '&estado='      + encodeURIComponent(document.getElementById('ef-estado').value);

  fetch(url)
  .then(function(r){ return r.json(); })
  .then(function(data){
    if(!data.ok){ toast('Error al cargar datos','error'); return; }
    lista = data.datos || [];
    puedeConfirmar = !!data.puede_confirmar;
    puedeEliminar  = !!data.puede_eliminar;
    setText('ef-num-pendientes',  data.pendientes);
    setText('ef-num-confirmados', data.confirmados);
    render();
  })
  .catch(function(){ toast('Error de conexion','error'); });
}

/* ── RENDER ── */
function render(){
  var tbody = document.getElementById('tbody-efectivo');
  setText('ef-total-lbl', lista.length + ' registros');

  var suma = lista.reduce(function(s, ins){ return s + (parseFloat(ins.precio_final) || 0); }, 0);
  setText('ef-suma', lista.length ? 'Total: Bs. ' + fmt(suma) : '');

  if(!lista.length){
    tbody.innerHTML = '<tr><td colspan="12" class="tabla-vacia"><i class="fa-solid fa-inbox" style="display:block;font-size:28px;margin-bottom:8px;"></i>No hay inscripciones en efectivo</td></tr>';
    return;
  }

  tbody.innerHTML = lista.map(function(ins, i){
    var iniciales = ((ins.nombre||'')[0]||'') + ((ins.apellido||'')[0]||'');
    var confirmado = ins.estado_pago === 'confirmado';
    var badge = confirmado
      ? '<span class="badge badge-confirmado" title="Confirmó: ' + esc(ins.confirmado_por_nombre||'—') + '"><i class="fa-solid fa-circle-check"></i> Confirmado</span>'
      : '<span class="badge badge-pendiente"><i class="fa-solid fa-clock"></i> Pendiente</span>';
    var accion = '';
    if(confirmado){
      accion = '<span style="font-size:11px;color:var(--txt-xsoft);">' + esc(ins.confirmado_por_nombre||'—') + '</span>';
    } else {
      if(puedeConfirmar) accion += '<button class="btn-accion btn-confirmar" title="Confirmar pago" onclick="confirmarEfectivo(' + i + ')"><i class="fa-solid fa-check"></i></button> ';
      if(puedeEliminar)  accion += '<button class="btn-accion btn-eliminar" title="Eliminar (no pagó) y devolver cupo" onclick="eliminarPendiente(' + i + ')"><i class="fa-solid fa-trash"></i></button>';
      if(!accion) accion = '—';
    }

    return '<tr>' +
      '<td>' + (i+1) + '</td>' +
      '<td><div class="participante-cell">' +
        '<div class="participante-avatar">' + esc(iniciales.toUpperCase()) + '</div>' +
        '<div class="participante-nombre">' + esc(ins.nombre) + ' ' + esc(ins.apellido) + '</div>' +
      '</div></td>' +
      '<td>' + esc(ins.carnet) + '</td>' +
      '<td>' + esc(ins.celular) + '</td>' +
      '<td>' + esc(ins.iglesia||'—') + '</td>' +
      '<td>' + esc(ins.distrito||'—') + '</td>' +
      '<td>' + esc(ins.paquete||'—') + '</td>' +
      '<td><strong>Bs. ' + fmt(ins.precio_final) + '</strong></td>' +
      '<td>' + esc(ins.registrado_por_nombre||'—') + '</td>' +
      '<td>' + formatFecha(ins.fecha_pago) + '</td>' +
      '<td>' + badge + '</td>' +
      '<td>' + accion + '</td>' +
    '</tr>';
  }).join('');
}

/* ── CONFIRMAR ── */
window.confirmarEfectivo = function(idx){
  var ins = lista[idx];
  if(!ins) return;
  if(!confirm('¿Confirmas que se recibió el dinero en efectivo de "' + ins.nombre + ' ' + ins.apellido + '"?\n\nTotal: Bs. ' + fmt(ins.precio_final))) return;

  var fd = new FormData();
  fd.append('accion', 'confirmar_efectivo');
  fd.append('inscripcion_id', ins.inscripcion_id);

  fetch('api_qr.php', { method:'POST', body:fd })
  .then(function(r){ return r.json(); })
  .then(function(data){
    if(data.ok){
      toast('Pago en efectivo confirmado', 'ok');
      if(data.credencial) window.open('../credenciales/' + data.credencial, '_blank');
      cargar();
    } else {
      toast(data.msg || 'Error al confirmar', 'error');
    }
  })
  .catch(function(){ toast('Error de conexion', 'error'); });
};

/* ── ELIMINAR PENDIENTE (no pagó) ── */
window.eliminarPendiente = function(idx){
  var ins = lista[idx];
  if(!ins) return;
  if(!confirm('⚠ ELIMINAR INSCRIPCIÓN PENDIENTE\n\n"' + ins.nombre + ' ' + ins.apellido + '" no pagó su inscripción en efectivo.\n\nSe eliminará su registro y el cupo del paquete volverá a quedar disponible.\n\nEsta acción NO se puede deshacer.')) return;

  var fd = new FormData();
  fd.append('accion', 'eliminar_pendiente');
  fd.append('inscripcion_id', ins.inscripcion_id);

  fetch('api_qr.php', { method:'POST', body:fd })
  .then(function(r){ return r.json(); })
  .then(function(data){
    if(data.ok){ toast(data.msg, 'ok'); cargar(); }
    else toast(data.msg || 'Error al eliminar', 'error');
  })
  .catch(function(){ toast('Error de conexion', 'error'); });
};

/* ── FILTROS ── */
var timer;
document.getElementById('ef-buscar').addEventListener('input', function(){
  clearTimeout(timer);
  timer = setTimeout(cargar, 400);
});
['ef-distrito','ef-estado'].forEach(function(id){
  document.getElementById(id).addEventListener('change', cargar);
});

/* ── HELPERS ── */
function fmt(v){ return parseFloat(v||0).toFixed(2); }
function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function setText(id, v){ var el = document.getElementById(id); if(el) el.textContent = v; }
function formatFecha(f){
  if(!f) return '—';
  var d = new Date(f.replace(' ', 'T'));
  if(isNaN(d)) return f;
  return String(d.getDate()).padStart(2,'0') + '/' + String(d.getMonth()+1).padStart(2,'0') + '/' + d.getFullYear() +
         ' ' + String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0');
}
function toast(msg, tipo){
  var el = document.getElementById('toast-qr');
  if(!el) return;
  el.textContent = msg;
  el.className = 'toast show' + (tipo ? ' toast-' + tipo : '');
  setTimeout(function(){ el.classList.remove('show'); }, 3200);
}

})();
