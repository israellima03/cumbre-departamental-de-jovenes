/* ============================================================
   TIEMPO-REAL.JS — Cupos y estado de inscripciones sin recargar
   ============================================================ */
(function () {
  'use strict';

  var ACTUALIZAR_CADA = 6000;
  var seccion = document.getElementById('seccion-registro');

  function clasePorcentaje(porcentaje) {
    if (porcentaje > 50) return 'cupo-alto';
    return porcentaje > 20 ? 'cupo-medio' : 'cupo-bajo';
  }

  function cambiarClaseCupo(el, clase) {
    el.classList.remove('cupo-alto', 'cupo-medio', 'cupo-bajo');
    el.classList.add(clase);
  }

  /* portada */
  function actualizarPortada(tarjeta, disponibles, total, agotado) {
    var barra = tarjeta.querySelector('.barra-cupos');
    var texto = tarjeta.querySelector('.texto-cupos');
    var boton = tarjeta.querySelector('a.button');
    if (!barra || !texto) return;

    var porcentaje = total > 0 ? Math.round((disponibles / total) * 100) : 0;
    var clase = clasePorcentaje(porcentaje);
    cambiarClaseCupo(barra, clase);
    cambiarClaseCupo(texto, clase);
    tarjeta.querySelector('.barra-cupos-relleno').style.width = porcentaje + '%';
    texto.innerHTML = '<i class="fa-solid fa-users"></i> ' + (agotado
      ? '<strong>AGOTADO</strong>'
      : '<strong>' + disponibles + '</strong> cupos disponibles de ' + total);

    if (boton) {
      boton.textContent         = agotado ? 'Agotado' : 'Inscribirme';
      boton.style.opacity       = agotado ? '.5' : '';
      boton.style.pointerEvents = agotado ? 'none' : '';
      boton.setAttribute('href', agotado ? '#' : 'tipo_inscripciones.php');
    }
  }

  /* formulario de registro */
  function actualizarRegistro(tarjeta, disponibles, total, agotado) {
    var radio = tarjeta.querySelector('input[name="paquete"]');
    var cupos = tarjeta.querySelector('.cupos-disponibles');
    var aviso = tarjeta.querySelector('.texto-agotado');
    if (!radio) return;

    if (cupos) cupos.innerHTML = '<i class="fa-solid fa-users"></i> ' + disponibles + ' / ' + total + ' cupos';
    if (aviso) aviso.hidden = !agotado;

    radio.disabled = agotado;
    if (agotado && radio.checked) {
      radio.checked = false;
      radio.dispatchEvent(new Event('change'));
    }
  }

  function aplicar(data) {
    if (seccion) seccion.classList.toggle('inscripciones-cerradas', !data.inscripciones_activas);

    (data.paquetes || []).forEach(function (p) {
      var tarjeta = document.querySelector('[data-paquete="' + p.id + '"]');
      if (!tarjeta) return;
      var disponibles = parseInt(p.cupos_disponibles, 10) || 0;
      var total       = parseInt(p.cupo_total, 10) || 0;
      var agotado     = disponibles <= 0;

      tarjeta.classList.toggle('agotado', agotado);
      actualizarPortada(tarjeta, disponibles, total, agotado);
      actualizarRegistro(tarjeta, disponibles, total, agotado);
    });
  }

  setInterval(function () {
    if (document.hidden) return;
    fetch('estado_publico.php')
      .then(function (r) { return r.json(); })
      .then(aplicar)
      .catch(function () { /* se vuelve a intentar en la siguiente vuelta */ });
  }, ACTUALIZAR_CADA);
})();
