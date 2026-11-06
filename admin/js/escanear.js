/* ============================================================
   ESCANEAR.JS — Registro de entregas por QR o carnet
   ============================================================ */
(function(){
'use strict';

var API = 'api_escaner.php';

var puntos         = [];
var puntoElegido   = null;
var puedeGestionar = false;
var registros      = [];
var lector         = null;
var camaraActiva   = false;
var pausado        = false;
var enviando       = false;

var $ = function(id){ return document.getElementById(id); };

/* ── PETICIONES ── */
function pedir(url, opciones){
  return fetch(url, opciones).then(function(r){
    /* la sesión vencida redirige al login en lugar de devolver JSON */
    if(r.redirected){ window.location.href = 'login.php?expirado=1'; throw new Error('sesion'); }
    return r.json();
  });
}

function enviar(datos){
  var fd = new FormData();
  Object.keys(datos).forEach(function(k){ fd.append(k, datos[k]); });
  return pedir(API, { method:'POST', body:fd });
}

/* ── PUNTOS ── */
function puntoActual(){ return $('punto-actual').value; }

function nombrePunto(){
  var sel = $('punto-actual');
  return sel.selectedIndex >= 0 ? sel.options[sel.selectedIndex].text : '';
}

function cargarPuntos(){
  return pedir(API + '?accion=listar_puntos').then(function(data){
    if(!data.ok){ toast(data.msg || 'Error al cargar los puntos', 'error'); return; }
    puntos = data.puntos || [];
    puedeGestionar = !!data.puede_gestionar;
    renderSelector();
    renderPuntos();
  }).catch(errorConexion);
}

function renderSelector(){
  var sel      = $('punto-actual');
  var anterior = sel.value;

  /* en Registrados también se consultan los puntos ya desactivados */
  sel.innerHTML = puntos.map(function(p){
    return '<option value="' + p.id + '">' + esc(p.nombre) + '</option>';
  }).join('');
  if(puntos.some(function(p){ return String(p.id) === String(anterior); })) sel.value = anterior;

  var activos = puntos.filter(function(p){ return String(p.activo) === '1'; });
  $('esc-opciones').innerHTML = activos.map(function(p){
    return '<button type="button" class="esc-opcion" data-punto="' + p.id + '">' +
      '<i class="fa-solid fa-clipboard-check"></i><span>' + esc(p.nombre) + '</span></button>';
  }).join('');
  $('esc-sin-puntos').hidden = !!activos.length;

  if(puntoElegido && !activos.some(function(p){ return String(p.id) === String(puntoElegido.id); })){
    volverAOpciones();
  }
}

function elegirPunto(id){
  puntoElegido = puntos.filter(function(p){ return String(p.id) === String(id); })[0] || null;
  if(!puntoElegido) return;
  $('esc-actual-nombre').textContent = puntoElegido.nombre;
  $('esc-elegir').hidden  = true;
  $('esc-trabajo').hidden = false;
  window.scrollTo({ top:0, behavior:'smooth' });
}

function volverAOpciones(){
  cerrarCamara();
  puntoElegido = null;
  $('esc-resultado').hidden = true;
  $('esc-carnet').value     = '';
  mensajeCamara('');
  $('esc-trabajo').hidden = true;
  $('esc-elegir').hidden  = false;
}

function renderPuntos(){
  var tbody = $('tbody-puntos');
  if(!tbody) return;
  if(!puntos.length){
    tbody.innerHTML = '<tr><td colspan="5" class="tabla-vacia">Todavía no hay puntos de registro</td></tr>';
    return;
  }
  tbody.innerHTML = puntos.map(function(p, i){
    var activo = String(p.activo) === '1';
    return '<tr>' +
      '<td>' + esc(p.orden) + '</td>' +
      '<td><strong>' + esc(p.nombre) + '</strong></td>' +
      '<td>' + esc(p.registrados) + '</td>' +
      '<td>' + (activo
        ? '<span class="badge badge-confirmado">Activo</span>'
        : '<span class="badge badge-pendiente">Inactivo</span>') + '</td>' +
      '<td class="col-acciones">' +
        '<button class="btn-accion btn-ver" title="Editar" data-editar="' + i + '"><i class="fa-solid fa-pen"></i></button> ' +
        '<button class="btn-accion" title="' + (activo ? 'Desactivar' : 'Activar') + '" data-estado="' + i + '">' +
          '<i class="fa-solid ' + (activo ? 'fa-eye-slash' : 'fa-eye') + '"></i></button> ' +
        '<button class="btn-accion btn-eliminar" title="Eliminar" data-eliminar="' + i + '"><i class="fa-solid fa-trash"></i></button>' +
      '</td>' +
    '</tr>';
  }).join('');
}

function limpiarFormPunto(){
  $('punto-id').value = '0';
  $('punto-nombre').value = '';
  $('punto-orden').value = '0';
  $('punto-form-titulo').innerHTML = '<i class="fa-solid fa-plus"></i> Nuevo punto de registro';
  $('btn-cancelar-punto').hidden = true;
}

function accionPunto(datos){
  enviar(datos).then(function(data){
    toast(data.msg, data.ok ? 'ok' : 'error');
    if(data.ok) cargarPuntos();
  }).catch(errorConexion);
}

function initPuntos(){
  var form = $('form-punto');
  if(!form) return;

  form.addEventListener('submit', function(e){
    e.preventDefault();
    enviar({
      accion: 'guardar_punto',
      id:     $('punto-id').value,
      nombre: $('punto-nombre').value.trim(),
      orden:  $('punto-orden').value
    }).then(function(data){
      toast(data.msg, data.ok ? 'ok' : 'error');
      if(data.ok){ limpiarFormPunto(); cargarPuntos(); }
    }).catch(errorConexion);
  });

  $('btn-cancelar-punto').addEventListener('click', limpiarFormPunto);

  $('tbody-puntos').addEventListener('click', function(e){
    var btn = e.target.closest('button');
    if(!btn) return;

    if(btn.dataset.editar !== undefined){
      var p = puntos[btn.dataset.editar];
      $('punto-id').value = p.id;
      $('punto-nombre').value = p.nombre;
      $('punto-orden').value = p.orden;
      $('punto-form-titulo').innerHTML = '<i class="fa-solid fa-pen"></i> Editar punto de registro';
      $('btn-cancelar-punto').hidden = false;
      $('punto-nombre').focus();
    } else if(btn.dataset.estado !== undefined){
      var pe = puntos[btn.dataset.estado];
      accionPunto({ accion:'cambiar_estado_punto', id:pe.id, activo:String(pe.activo) === '1' ? 0 : 1 });
    } else if(btn.dataset.eliminar !== undefined){
      var pd = puntos[btn.dataset.eliminar];
      if(confirm('¿Eliminar el punto "' + pd.nombre + '"?')) accionPunto({ accion:'eliminar_punto', id:pd.id });
    }
  });
}

/* ── REGISTRAR ── */
function registrar(datos){
  if(enviando) return Promise.resolve();
  if(!puntoElegido) return Promise.resolve();
  enviando = true;
  datos.accion   = 'registrar';
  datos.punto_id = puntoElegido.id;

  return enviar(datos).then(function(data){
    mostrarResultado(data);
  }).catch(function(e){
    if(e.message !== 'sesion') mostrarError('Sin conexión. No se guardó el registro. Intenta de nuevo.');
  }).then(function(){
    enviando = false;
  });
}

function extraerCodigo(texto){
  var t = String(texto).trim();
  var enlace = t.match(/[?&]code=([a-f0-9]{32})\b/i);
  if(enlace) return enlace[1];
  return /^[a-f0-9]{32}$/i.test(t) ? t : '';
}

function alLeerQR(texto){
  if(pausado || enviando) return;
  pausarLector();
  var codigo = extraerCodigo(texto);
  if(!codigo){
    mostrarError('Este QR no es una credencial de la Cumbre.');
    return;
  }
  registrar({ codigo:codigo });
}

/* ── RESULTADO ── */
function filaDato(etiqueta, valor){
  return '<div class="esc-dato"><span>' + etiqueta + '</span><strong>' + esc(valor || '—') + '</strong></div>';
}

function datosPersona(p){
  return '<p class="esc-nombre">' + esc(p.nombre + ' ' + p.apellido) + '</p>' +
    '<div class="esc-datos">' +
      filaDato('Carnet', p.carnet) +
      filaDato('Tipo', p.tipo) +
      filaDato('Iglesia', p.iglesia) +
      filaDato('Distrito', p.distrito) +
      filaDato('Paquete', p.paquete) +
      filaDato('Edad', p.edad) +
    '</div>';
}

function botonSiguiente(){
  return camaraActiva
    ? '<button type="button" class="btn-primary esc-btn" id="btn-siguiente"><i class="fa-solid fa-qrcode"></i> Escanear siguiente</button>'
    : '';
}

function pintarResultado(clase, icono, titulo, cuerpo){
  var caja = $('esc-resultado');
  caja.className = 'esc-resultado ' + clase;
  caja.hidden = false;
  caja.innerHTML =
    '<div class="esc-res-titulo"><i class="fa-solid ' + icono + '"></i> ' + esc(titulo) + '</div>' +
    cuerpo + botonSiguiente();
  caja.scrollIntoView({ behavior:'smooth', block:'nearest' });
  if(navigator.vibrate) navigator.vibrate(clase === 'esc-ok' ? 120 : [120, 80, 120]);
}

function mostrarError(msg, persona){
  pintarResultado('esc-error', 'fa-circle-xmark', msg, persona ? datosPersona(persona) : '');
}

function mostrarResultado(data){
  if(!data.ok){
    mostrarError(data.msg || 'No se pudo registrar.', data.persona);
    return;
  }
  var nuevo = data.estado === 'registrado';
  var hora  = formatoFecha(data.fecha);
  pintarResultado(
    nuevo ? 'esc-ok' : 'esc-repetido',
    nuevo ? 'fa-circle-check' : 'fa-triangle-exclamation',
    (nuevo ? 'Registrado: ' : 'Ya recibió: ') + data.punto,
    datosPersona(data.persona) +
    '<div class="esc-datos esc-registro">' +
      filaDato('Fecha y hora', hora) +
      filaDato('Registró', data.registrado_por) +
      filaDato('Total en ' + data.punto, data.total) +
    '</div>'
  );
  $('esc-carnet').value = '';
}

/* ── CÁMARA ── */
function mensajeCamara(texto){
  var el = $('esc-camara-msg');
  el.textContent = texto || '';
  el.hidden = !texto;
}

function textoBotonCamara(){
  $('btn-camara').innerHTML = camaraActiva
    ? '<i class="fa-solid fa-stop"></i> Cerrar cámara'
    : '<i class="fa-solid fa-camera"></i> Abrir cámara';
}

function abrirCamara(){
  if(!window.Html5Qrcode){
    mensajeCamara('No se pudo cargar el lector. Recarga la página o usa el carnet.');
    return;
  }
  var btn = $('btn-camara');
  btn.disabled = true;
  mensajeCamara('');
  lector = lector || new Html5Qrcode('lector', { verbose:false });

  lector.start(
    { facingMode:'environment' },
    { fps:10, qrbox:function(ancho, alto){
        var lado = Math.floor(Math.min(ancho, alto) * 0.75);
        return { width:lado, height:lado };
      }
    },
    alLeerQR,
    function(){ /* cuadro sin QR: es normal mientras se apunta */ }
  ).then(function(){
    camaraActiva = true;
    pausado = false;
  }).catch(function(){
    mensajeCamara('No se pudo abrir la cámara. Revisa que el navegador tenga permiso o usa el carnet.');
  }).then(function(){
    btn.disabled = false;
    textoBotonCamara();
  });
}

function cerrarCamara(){
  if(!lector || !camaraActiva) return Promise.resolve();
  camaraActiva = false;
  pausado = false;
  $('esc-resultado').hidden = true;
  return lector.stop().catch(function(){}).then(textoBotonCamara);
}

function pausarLector(){
  pausado = true;
  try { lector.pause(true); } catch(e){}
}

function reanudarLector(){
  $('esc-resultado').hidden = true;
  try { lector.resume(); } catch(e){}
  pausado = false;
  $('lector').scrollIntoView({ behavior:'smooth', block:'center' });
}

/* ── REGISTRADOS ── */
function cargarRegistrados(){
  var tbody = $('tbody-registrados');
  var cols  = puedeGestionar ? 10 : 9;
  $('reg-titulo').textContent = nombrePunto() || 'Registrados';

  if(!puntoActual()){
    tbody.innerHTML = '<tr><td colspan="' + cols + '" class="tabla-vacia">Elige un punto de registro</td></tr>';
    $('reg-total').textContent = '';
    return;
  }

  var url = API + '?accion=listar_registrados&punto_id=' + encodeURIComponent(puntoActual()) +
            '&buscar=' + encodeURIComponent($('reg-buscar').value.trim());

  pedir(url).then(function(data){
    if(!data.ok){ toast(data.msg || 'Error al cargar', 'error'); return; }
    registros = data.registros || [];
    $('reg-total').textContent = data.total + ' de ' + data.confirmados + ' confirmados';

    if(!registros.length){
      tbody.innerHTML = '<tr><td colspan="' + cols + '" class="tabla-vacia">Sin registros</td></tr>';
      return;
    }
    tbody.innerHTML = registros.map(function(r, i){
      return '<tr>' +
        '<td>' + (i + 1) + '</td>' +
        '<td><strong>' + esc(r.nombre + ' ' + r.apellido) + '</strong></td>' +
        '<td>' + esc(r.carnet) + '</td>' +
        '<td>' + esc(r.iglesia || '—') + '</td>' +
        '<td>' + esc(r.distrito || '—') + '</td>' +
        '<td>' + esc(r.paquete || '—') + '</td>' +
        '<td>' + esc(formatoFecha(r.fecha)) + '</td>' +
        '<td>' + esc(r.registrado_por) + '</td>' +
        '<td>' + (r.metodo === 'qr' ? 'QR' : 'Carnet') + '</td>' +
        (puedeGestionar
          ? '<td><button class="btn-accion btn-eliminar" title="Revertir registro" data-revertir="' + i + '"><i class="fa-solid fa-rotate-left"></i></button></td>'
          : '') +
      '</tr>';
    }).join('');
  }).catch(errorConexion);
}

function initRegistrados(){
  var timer;
  $('reg-buscar').addEventListener('input', function(){
    clearTimeout(timer);
    timer = setTimeout(cargarRegistrados, 350);
  });

  $('tbody-registrados').addEventListener('click', function(e){
    var btn = e.target.closest('[data-revertir]');
    if(!btn) return;
    var r = registros[btn.dataset.revertir];
    if(!confirm('¿Revertir el registro de ' + r.nombre + ' ' + r.apellido + ' en "' + nombrePunto() + '"?\n\nPodrá volver a registrarse.')) return;
    enviar({ accion:'revertir', id:r.id }).then(function(data){
      toast(data.msg, data.ok ? 'ok' : 'error');
      cargarRegistrados();
      cargarPuntos();
    }).catch(errorConexion);
  });
}

/* ── PESTAÑAS ── */
function initPestanas(){
  document.querySelectorAll('.ci-tab').forEach(function(btn){
    btn.addEventListener('click', function(){
      document.querySelectorAll('.ci-tab').forEach(function(b){ b.classList.remove('active'); });
      document.querySelectorAll('.ci-panel').forEach(function(p){ p.classList.remove('active'); });
      this.classList.add('active');
      $('panel-' + this.dataset.tab).classList.add('active');

      if(this.dataset.tab !== 'registrar') cerrarCamara();
      if(this.dataset.tab === 'registrados'){
        if(puntoElegido) $('punto-actual').value = puntoElegido.id;
        cargarRegistrados();
      }
      if(this.dataset.tab === 'puntos') cargarPuntos();
    });
  });
}

/* ── HELPERS ── */
function esc(s){
  return String(s === null || s === undefined ? '' : s)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function formatoFecha(f){
  var m = String(f || '').match(/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})/);
  return m ? m[3] + '/' + m[2] + '/' + m[1] + ' ' + m[4] + ':' + m[5] : '—';
}

function toast(msg, tipo){
  var el = $('toast-escaner');
  el.textContent = msg;
  el.className = 'toast show' + (tipo ? ' toast-' + tipo : '');
  clearTimeout(toast._t);
  toast._t = setTimeout(function(){ el.classList.remove('show'); }, 3200);
}

function errorConexion(e){
  if(e && e.message === 'sesion') return;
  toast('Error de conexión', 'error');
}

/* ── INICIO ── */
$('btn-camara').addEventListener('click', function(){
  if(camaraActiva){
    cerrarCamara();
  } else {
    abrirCamara();
  }
});

$('esc-resultado').addEventListener('click', function(e){
  if(e.target.closest('#btn-siguiente')) reanudarLector();
});

$('form-carnet').addEventListener('submit', function(e){
  e.preventDefault();
  var carnet = $('esc-carnet').value.trim();
  if(carnet) registrar({ carnet:carnet });
});

$('punto-actual').addEventListener('change', cargarRegistrados);

$('esc-opciones').addEventListener('click', function(e){
  var btn = e.target.closest('[data-punto]');
  if(btn) elegirPunto(btn.dataset.punto);
});

$('btn-cambiar-punto').addEventListener('click', volverAOpciones);

initPestanas();
initPuntos();
initRegistrados();
cargarPuntos();

})();
