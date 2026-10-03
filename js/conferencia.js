/* ============================================================
   CONFERENCIA.JS — Preguntas anonimas del publico
   ============================================================ */

document.querySelectorAll('.form-pregunta').forEach(function(form){
    var textarea  = form.querySelector('.pregunta-texto');
    var contador  = form.querySelector('.pregunta-contador');
    var msgDiv    = form.querySelector('.pregunta-msg');
    var idEvento  = form.dataset.evento;

    /* ── CONTADOR DE CARACTERES ── */
    if(textarea && contador){
        textarea.addEventListener('input', function(){
            var len = this.value.length;
            contador.textContent = len + ' / 500';
            /* cambia color cuando se acerca al limite */
            contador.style.color = len > 450 ? '#E8750A' : '#999';
        });
    }

    /* ── SUBMIT ── */
    form.addEventListener('submit', function(e){
        e.preventDefault();

        var pregunta = textarea ? textarea.value.trim() : '';
        var btn      = form.querySelector('.btn-enviar-pregunta');

        /* limpiar error previo */
        if(textarea) textarea.style.borderColor = '';

        /* validacion */
        if(!pregunta){
            if(textarea) textarea.style.borderColor = '#E8750A';
            mostrarMsg(msgDiv, 'Escribe tu pregunta antes de enviar.', 'error');
            return;
        }

        /* estado loading */
        btn.disabled  = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enviando...';

        var fd = new FormData();
        fd.append('accion',    'enviar_pregunta');
        fd.append('id_evento', idEvento);
        fd.append('pregunta',  pregunta);

        fetch('guardar_pregunta.php', { method: 'POST', body: fd })
        .then(function(r){ return r.json(); })
        .then(function(data){
            btn.disabled  = false;
            btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Enviar pregunta';

            if(data.ok){
                mostrarMsg(msgDiv, '¡Pregunta enviada de forma anonima!', 'ok');
                /* limpiar el textarea */
                if(textarea){
                    textarea.value             = '';
                    textarea.style.borderColor = '';
                }
                if(contador){
                    contador.textContent = '0 / 500';
                    contador.style.color = '#999';
                }
            } else {
                mostrarMsg(msgDiv, data.msg || 'Error al enviar. Intenta de nuevo.', 'error');
            }
        })
        .catch(function(){
            btn.disabled  = false;
            btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Enviar pregunta';
            mostrarMsg(msgDiv, 'Error de conexion. Intenta de nuevo.', 'error');
        });
    });
});

/* ── HELPER MENSAJES ── */
function mostrarMsg(div, msg, tipo){
    if(!div) return;
    div.style.display = 'flex';
    div.className     = 'pregunta-msg pregunta-msg-' + tipo;
    div.innerHTML     =
        '<i class="fa-solid ' +
        (tipo === 'ok' ? 'fa-circle-check' : 'fa-circle-xmark') +
        '"></i> ' + msg;

    /* ocultar automaticamente despues de 5 segundos */
    setTimeout(function(){
        div.style.display = 'none';
    }, 5000);
}

/* ══════════════════════════════════════
   MODAL PREGUNTAS APROBADAS
══════════════════════════════════════ */
var _pqLista   = [];
var _pqIndice  = 0;

/* abrir modal */
document.querySelectorAll('.btn-ver-preguntas').forEach(function(btn){
  btn.addEventListener('click', function(){
    var idEvento = this.dataset.evento;
    var tema     = this.dataset.tema;

    document.getElementById('modal-pq-tema').textContent     = tema;
    document.getElementById('modal-pq-loading').style.display = 'block';
    document.getElementById('modal-pq-contenido').style.display = 'none';
    document.getElementById('modal-pq-vacio').style.display   = 'none';
    document.getElementById('modal-pq-nav').style.display     = 'none';

    var overlay = document.getElementById('modal-preguntas-overlay');
    overlay.style.display = 'grid';
    document.body.style.overflow = 'hidden';

    /* cargar preguntas */
    var fd = new FormData();
    fd.append('accion',    'listar_aprobadas');
    fd.append('id_evento', idEvento);

    fetch('guardar_pregunta.php', { method: 'POST', body: fd })
    .then(function(r){ return r.json(); })
    .then(function(data){
      document.getElementById('modal-pq-loading').style.display = 'none';

      if(!data.ok || !data.preguntas || !data.preguntas.length){
        document.getElementById('modal-pq-vacio').style.display = 'block';
        return;
      }

      _pqLista  = data.preguntas;
      _pqIndice = 0;
      mostrarPreguntaActual();
      document.getElementById('modal-pq-nav').style.display = 'flex';
    })
    .catch(function(){
      document.getElementById('modal-pq-loading').style.display = 'none';
      document.getElementById('modal-pq-vacio').style.display   = 'block';
    });
  });
});

function mostrarPreguntaActual(){
  var pq = _pqLista[_pqIndice];
  if(!pq) return;

  document.getElementById('modal-pq-contenido').style.display = 'block';
  document.getElementById('modal-pq-texto').textContent       = '«' + pq.pregunta + '»';
  document.getElementById('modal-pq-num').textContent         = 'Pregunta ' + (_pqIndice + 1) + ' de ' + _pqLista.length;
  document.getElementById('modal-pq-progreso').textContent    = (_pqIndice + 1) + ' / ' + _pqLista.length;

  /* deshabilitar botones en extremos */
  document.getElementById('btn-pq-ant').disabled = _pqIndice === 0;
  document.getElementById('btn-pq-sig').disabled = _pqIndice === _pqLista.length - 1;

  document.getElementById('btn-pq-ant').style.opacity = _pqIndice === 0 ? '.4' : '1';
  document.getElementById('btn-pq-sig').style.opacity = _pqIndice === _pqLista.length - 1 ? '.4' : '1';
}

window.navegarPregunta = function(dir){
  var nuevo = _pqIndice + dir;
  if(nuevo < 0 || nuevo >= _pqLista.length) return;
  _pqIndice = nuevo;
  /* animación suave */
  var contenido = document.getElementById('modal-pq-contenido');
  contenido.style.opacity = '0';
  contenido.style.transform = 'translateX(' + (dir > 0 ? '20px' : '-20px') + ')';
  setTimeout(function(){
    mostrarPreguntaActual();
    contenido.style.transition = 'all .25s ease';
    contenido.style.opacity    = '1';
    contenido.style.transform  = 'translateX(0)';
    setTimeout(function(){
      contenido.style.transition = '';
    }, 300);
  }, 150);
};

window.cerrarModalPreguntas = function(){
  document.getElementById('modal-preguntas-overlay').style.display = 'none';
  document.body.style.overflow = '';
  _pqLista  = [];
  _pqIndice = 0;
};

/* cerrar con Escape o click fuera */
document.addEventListener('keydown', function(e){
  if(e.key === 'Escape') window.cerrarModalPreguntas();
});
document.getElementById('modal-preguntas-overlay').addEventListener('click', function(e){
  if(e.target === this) window.cerrarModalPreguntas();
});

/* teclado izquierda/derecha para navegar */
document.addEventListener('keydown', function(e){
  var overlay = document.getElementById('modal-preguntas-overlay');
  if(overlay.style.display === 'none') return;
  if(e.key === 'ArrowLeft')  window.navegarPregunta(-1);
  if(e.key === 'ArrowRight') window.navegarPregunta(1);
});
