(function(){
'use strict';

var todos        = [];
var filtrados    = [];
var paginaActual = 1;
var porPagina    = 10;
var inscActual   = null;

/* ── CARGAR DATOS ── */
function cargar(){
    var buscar      = document.getElementById('qr-buscar').value.trim();
    var estado      = document.getElementById('qr-estado').value;
    var fechaDesde  = document.getElementById('qr-fecha-desde').value;
    var fechaHasta  = document.getElementById('qr-fecha-hasta').value;

    setText('qr-total-lbl', 'Cargando...');
    document.getElementById('tbody-qr').innerHTML =
        '<tr><td colspan="12" class="tabla-loading">' +
        '<i class="fa-solid fa-spinner fa-spin"></i> Cargando...</td></tr>';

    var url = 'api_qr.php?accion=listar'
        + '&buscar='      + encodeURIComponent(buscar)
        + '&estado='      + encodeURIComponent(estado)
        + '&fecha_desde=' + encodeURIComponent(fechaDesde)
        + '&fecha_hasta=' + encodeURIComponent(fechaHasta);

    fetch(url)
    .then(function(r){ return r.json(); })
    .then(function(data){
        if(!data.ok){ mostrarToast('Error al cargar datos','error'); return; }

        todos     = data.datos || [];
        filtrados = todos;
        paginaActual = 1;

        setText('num-pendientes',  data.pendientes);
        setText('num-confirmados', data.confirmados);
        setText('qr-total-lbl',    filtrados.length + ' registros');

        renderTabla();
    })
    .catch(function(){ mostrarToast('Error de conexion','error'); });
}

/* ── RENDER TABLA ── */

function renderTabla(){
  var tbody    = document.getElementById('tbody-qr');
  var tbodyAcc = document.getElementById('tbody-acc');
  var lbl      = document.getElementById('qr-total-lbl');
  if(!tbody) return;

  if(!filtrados.length){
    tbody.innerHTML    = '<tr><td colspan="12" class="tabla-vacia"><i class="fa-solid fa-inbox" style="display:block;font-size:28px;margin-bottom:8px;"></i>No hay inscripciones</td></tr>';
    tbodyAcc.innerHTML = '<tr><td>—</td></tr>';
    if(lbl) lbl.textContent = '0 registros';
    renderPaginacion();
    return;
  }

  if(lbl) lbl.textContent = filtrados.length + ' registros';
  var inicio = (paginaActual - 1) * porPagina;
  var pagina = filtrados.slice(inicio, inicio + porPagina);

  tbody.innerHTML = pagina.map(function(ins, i){
    var iniciales = ((ins.nombre||'')[0]||'') + ((ins.apellido||'')[0]||'');
    var badge = ins.estado_pago === 'confirmado'
      ? '<span class="badge badge-confirmado"><i class="fa-solid fa-circle-check"></i> Confirmado</span>'
      : '<span class="badge badge-pendiente"><i class="fa-solid fa-clock"></i> Pendiente</span>';

    return '<tr>' +
      '<td>' + (inicio+i+1) + '</td>' +
      '<td><div class="participante-cell">' +
        '<div class="participante-avatar">' + iniciales.toUpperCase() + '</div>' +
        '<div class="participante-nombre">' + htmlEsc(ins.nombre) + ' ' + htmlEsc(ins.apellido) + '</div>' +
      '</div></td>' +
      '<td>' + htmlEsc(ins.carnet) + '</td>' +
      '<td>' + htmlEsc(ins.celular) + '</td>' +
      '<td>' + htmlEsc(ins.iglesia||'—') + '</td>' +
      '<td>' + htmlEsc(ins.paquete||'—') + '</td>' +
      '<td>Bs. ' + fmt(ins.precio_paquete) + '</td>' +
      '<td style="color:var(--green);">- Bs. ' + fmt(ins.descuento_aplicado) + '</td>' +
      '<td><strong>Bs. ' + fmt(ins.precio_final) + '</strong></td>' +
      '<td>' + formatFecha(ins.fecha_pago) + '</td>' +
      '<td>' +
        (ins.comprobante_qr
          ? '<button class="btn-accion btn-ver" onclick="verComprobante(\'' + htmlEsc(ins.comprobante_qr) + '\')"><i class="fa-solid fa-image"></i></button>'
          : '<span style="color:var(--txt-xsoft);font-size:11px;">—</span>') +
      '</td>' +
      '<td>' + badge + '</td>' +
    '</tr>';
  }).join('');

  tbodyAcc.innerHTML = pagina.map(function(ins, i){
    var btnConf = ins.estado_pago === 'pendiente'
      ? '<button class="btn-accion btn-confirmar" onclick="abrirModal(' + (inicio+i) + ')"><i class="fa-solid fa-check"></i></button> '
      : '';
    return '<tr><td>' + btnConf +
      '<button class="btn-accion btn-ver" onclick="abrirModal(' + (inicio+i) + ')"><i class="fa-solid fa-eye"></i></button>' +
      '</td></tr>';
  }).join('');

  /* sincronizar alturas */
  setTimeout(function(){
    var trsDatos = tbody.querySelectorAll('tr');
    var trsAcc   = tbodyAcc.querySelectorAll('tr');

    /* resetear primero */
    trsAcc.forEach(function(tr){ tr.style.height = ''; });

    trsDatos.forEach(function(tr, i){
      if(trsAcc[i]){
        var h = tr.getBoundingClientRect().height;
        trsAcc[i].style.height = h + 'px';
        trsAcc[i].style.maxHeight = h + 'px';
      }
    });

    /* sincronizar también cabecera */
    var thDatos = document.querySelector('#tabla-qr thead tr');
    var thAcc   = document.querySelector('#tabla-acc thead tr');
    if(thDatos && thAcc){
      thAcc.style.height = thDatos.getBoundingClientRect().height + 'px';
    }
  }, 100);

  renderPaginacion();
}


/* ── PAGINACION ── */
function renderPaginacion(){
    var el = document.getElementById('paginacion-qr');
    if(!el) return;
    var total = Math.ceil(filtrados.length / porPagina);
    if(total <= 1){ el.innerHTML = ''; return; }

    var html = '<button class="pag-btn" onclick="cambiarPag(' + (paginaActual-1) + ')" ' +
        (paginaActual <= 1 ? 'disabled' : '') + '><i class="fa-solid fa-chevron-left"></i></button>';
    for(var i=1; i<=total; i++){
        html += '<button class="pag-btn ' + (i===paginaActual?'active':'') +
            '" onclick="cambiarPag(' + i + ')">' + i + '</button>';
    }
    html += '<button class="pag-btn" onclick="cambiarPag(' + (paginaActual+1) + ')" ' +
        (paginaActual >= total ? 'disabled' : '') + '><i class="fa-solid fa-chevron-right"></i></button>';
    el.innerHTML = html;
}

window.cambiarPag = function(p){
    var total = Math.ceil(filtrados.length / porPagina);
    if(p < 1 || p > total) return;
    paginaActual = p;
    renderTabla();
};

/* ── MODAL DETALLE ── */
window.abrirModal = function(idx){
    inscActual = filtrados[(paginaActual-1)*porPagina + idx] || filtrados[idx];
    if(!inscActual) return;

    var ins = inscActual;
    var body = document.getElementById('modal-qr-body');

    var compHTML = ins.comprobante_qr
        ? '<div class="comprobante-preview" style="grid-column:span 2;margin-top:12px;">' +
          '<span class="modal-label">Comprobante de pago</span>' +
          '<img src="../comprobantes/' + htmlEsc(ins.comprobante_qr) + '" ' +
          'alt="Comprobante" style="width:100%;max-height:320px;object-fit:contain;' +
          'border:1px solid var(--border);border-radius:8px;margin-top:6px;background:var(--bg);">' +
          '</div>'
        : '<div style="grid-column:span 2;padding:20px;text-align:center;color:var(--txt-xsoft);">' +
          '<i class="fa-solid fa-image" style="font-size:24px;display:block;margin-bottom:8px;"></i>' +
          'Sin comprobante adjunto</div>';

    var confHTML = ins.confirmado_por_nombre
        ? '<div class="modal-field"><span class="modal-label">Confirmado por</span>' +
          '<span class="modal-value">' + htmlEsc(ins.confirmado_por_nombre) + '</span></div>' +
          '<div class="modal-field"><span class="modal-label">Fecha confirmacion</span>' +
          '<span class="modal-value">' + formatFecha(ins.fecha_confirmacion) + '</span></div>'
        : '';

    body.innerHTML =
        '<div class="modal-grid">' +
        '<div class="modal-section-title">Datos del Inscrito</div>' +
        mf('Nombre completo', htmlEsc(ins.nombre) + ' ' + htmlEsc(ins.apellido)) +
        mf('Carnet',  htmlEsc(ins.carnet)) +
        mf('Celular', htmlEsc(ins.celular)) +
        mf('Iglesia', htmlEsc(ins.iglesia||'—')) +

        '<div class="modal-section-title">Detalle de Pago</div>' +
        mf('Paquete',          htmlEsc(ins.paquete||'—')) +
        mf('Precio paquete',   'Bs. ' + fmt(ins.precio_paquete)) +
        mf('Descuento',        '- Bs. ' + fmt(ins.descuento_aplicado)) +
        '<div class="modal-field full"><span class="modal-label">TOTAL A PAGAR</span>' +
        '<span class="modal-value" style="font-size:20px;font-weight:700;color:var(--sidebar-bg);">' +
        'Bs. ' + fmt(ins.precio_final) + '</span></div>' +
        mf('Fecha registro', formatFecha(ins.fecha_pago)) +
        mf('Estado', ins.estado_pago === 'confirmado'
            ? '<span class="badge badge-confirmado"><i class="fa-solid fa-circle-check"></i> Confirmado</span>'
            : '<span class="badge badge-pendiente"><i class="fa-solid fa-clock"></i> Pendiente</span>') +
        confHTML +

        '<div class="modal-section-title">Comprobante QR</div>' +
        compHTML +
        '</div>';

    var btnConf = document.getElementById('btn-modal-qr-confirmar');
    if(btnConf){
        btnConf.style.display = ins.estado_pago === 'pendiente' ? 'inline-flex' : 'none';
    }

    document.getElementById('modal-qr-overlay').classList.add('open');
};

function mf(label, val){
    return '<div class="modal-field"><span class="modal-label">' + label + '</span>' +
           '<span class="modal-value">' + val + '</span></div>';
}

/* ── VER COMPROBANTE EN NUEVA VENTANA ── */
window.verComprobante = function(archivo){
    window.open('../comprobantes/' + archivo, '_blank');
};

/* ── CONFIRMAR PAGO ── */
document.getElementById('btn-modal-qr-confirmar').addEventListener('click', function(){
    if(!inscActual) return;
    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Confirmando...';

    var fd = new FormData();
    fd.append('accion',         'confirmar');
    fd.append('inscripcion_id', inscActual.inscripcion_id);

    fetch('api_qr.php', { method:'POST', body:fd })
        .then(function(r){ return r.json(); })
        .then(function(data){
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Confirmar Pago';

            if(data.ok){
                mostrarToast('Pago confirmado correctamente', 'ok');
                document.getElementById('modal-qr-overlay').classList.remove('open');
                if(data.credencial) window.open('../credenciales/' + data.credencial, '_blank');
                cargar();
            } else {
                mostrarToast(data.msg || 'Error al confirmar', 'error');
            }
        })
        .catch(function(){
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Confirmar Pago';
            mostrarToast('Error de conexion', 'error');
        });
    });

/* ── CERRAR MODAL ── */
function cerrarModal(){
    document.getElementById('modal-qr-overlay').classList.remove('open');
}
document.getElementById('btn-modal-qr-close').addEventListener('click',   cerrarModal);
document.getElementById('btn-modal-qr-cancelar').addEventListener('click', cerrarModal);
document.getElementById('modal-qr-overlay').addEventListener('click', function(e){
    if(e.target === this) cerrarModal();
});

/* ── FILTROS ── */
var timerBuscar;
document.getElementById('qr-buscar').addEventListener('input', function(){
    clearTimeout(timerBuscar);
    timerBuscar = setTimeout(cargar, 400);
});
['qr-estado','qr-fecha-desde','qr-fecha-hasta'].forEach(function(id){
    var el = document.getElementById(id);
    if(el) el.addEventListener('change', cargar);
});
document.getElementById('btn-limpiar-filtros').addEventListener('click', function(){
    document.getElementById('qr-buscar').value      = '';
    document.getElementById('qr-estado').value      = 'pendiente';
    document.getElementById('qr-fecha-desde').value = '';
    document.getElementById('qr-fecha-hasta').value = '';
    cargar();
});

/* ── HELPERS ── */
function fmt(v){ return parseFloat(v||0).toFixed(2); }

function htmlEsc(s){
    return String(s||'')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function formatFecha(f){
    if(!f) return '—';
    var d = new Date(f);
    if(isNaN(d)) return f;
    return d.getDate().toString().padStart(2,'0') + '/' +
           (d.getMonth()+1).toString().padStart(2,'0') + '/' +
           d.getFullYear() + ' ' +
           d.getHours().toString().padStart(2,'0') + ':' +
           d.getMinutes().toString().padStart(2,'0');
}

function setText(id, val){
    var el = document.getElementById(id);
    if(el) el.textContent = val;
}

function mostrarToast(msg, tipo){
    var el = document.getElementById('toast-qr');
    if(!el) return;
    el.textContent = msg;
    el.className = 'toast show' + (tipo ? ' toast-'+tipo : '');
    setTimeout(function(){ el.classList.remove('show'); }, 3200);
}



/* ── INIT ── */
document.addEventListener('DOMContentLoaded', cargar);

})();