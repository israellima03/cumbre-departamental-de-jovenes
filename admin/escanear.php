<?php
require_once('funciones/sesiones.php');
usuario_autentificado();

header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');

require_once('../includes/funciones/bd_conexion.php');
$css_extra = '<link rel="stylesheet" href="css/escanear.css?v=' . filemtime(__DIR__ . '/css/escanear.css') . '">';
include_once 'templates/header.php';
include_once 'templates/navegacion.php';
include_once 'templates/barra.php';
include_once 'funciones/funciones.php';

$puede_gestionar = puede(['Administrador','Lider departamental','tesorera']);
?>

<main class="content" id="main-content">
  <div class="page active" id="page-escanear">

    <div class="page-header">
      <div>
        <h1 class="page-title">Escanear <span>Credencial</span></h1>
        <p class="page-sub">Registra la entrega de material, refrigerios y más.</p>
      </div>
    </div>

    <!-- PESTAÑAS -->
    <div class="ci-tabs">
      <button class="ci-tab active" data-tab="registrar">
        <i class="fa-solid fa-qrcode"></i> Registrar
      </button>
      <button class="ci-tab" data-tab="registrados">
        <i class="fa-solid fa-list-check"></i> Registrados
      </button>
      <?php if($puede_gestionar): ?>
      <button class="ci-tab" data-tab="puntos">
        <i class="fa-solid fa-sliders"></i> Puntos de registro
      </button>
      <?php endif; ?>
    </div>

    <!-- ══ REGISTRAR ══ -->
    <div class="ci-panel active" id="panel-registrar">

      <!-- PASO 1: ELEGIR QUÉ SE REGISTRA -->
      <div class="card esc-bloque" id="esc-elegir">
        <h3><i class="fa-solid fa-hand-pointer"></i> ¿Qué quieres registrar?</h3>
        <div class="esc-opciones" id="esc-opciones"></div>
        <p class="esc-sin-puntos" id="esc-sin-puntos" hidden>
          <i class="fa-solid fa-circle-info"></i>
          Aún no hay puntos de registro activos.
          <?php echo $puede_gestionar ? 'Créalos en la pestaña "Puntos de registro".' : 'Pídele a un encargado que los cree.'; ?>
        </p>
      </div>

      <!-- PASO 2: ESCANEAR O ESCRIBIR EL CARNET -->
      <div id="esc-trabajo" hidden>
        <div class="esc-actual">
          <div>
            <span>Estás registrando</span>
            <strong id="esc-actual-nombre"></strong>
          </div>
          <button type="button" class="btn-secondary" id="btn-cambiar-punto">
            <i class="fa-solid fa-arrow-left"></i> Cambiar
          </button>
        </div>

        <div id="esc-resultado" class="esc-resultado" role="status" aria-live="polite" hidden></div>

        <div class="esc-grid">

        <div class="card esc-bloque">
          <h3><i class="fa-solid fa-camera"></i> Escanear QR</h3>
          <div id="lector"></div>
          <button class="btn-primary esc-btn" id="btn-camara">
            <i class="fa-solid fa-camera"></i> Abrir cámara
          </button>
          <p class="esc-aviso" id="esc-camara-msg" hidden></p>
        </div>

        <div class="card esc-bloque">
          <h3><i class="fa-solid fa-id-card"></i> Registrar con carnet</h3>
          <p class="esc-ayuda">Si la cámara o el QR no funcionan, usa el número de carnet.</p>
          <form id="form-carnet" class="esc-carnet">
            <input type="text" id="esc-carnet" class="input-filtro"
                   placeholder="Ej: 1234567" autocomplete="off" required>
            <button type="submit" class="btn-primary" id="btn-carnet">
              <i class="fa-solid fa-check"></i> Registrar
            </button>
          </form>
        </div>

        </div>
      </div>
    </div>

    <!-- ══ REGISTRADOS ══ -->
    <div class="ci-panel" id="panel-registrados">
      <div class="esc-punto card">
        <label for="punto-actual">Ver registrados de:</label>
        <select id="punto-actual" class="select-filtro"></select>
      </div>
      <div class="card">
        <div class="card-header">
          <h3><i class="fa-solid fa-list-check"></i> <span id="reg-titulo">Registrados</span></h3>
          <span class="total-lbl-txt" id="reg-total"></span>
        </div>
        <div class="filtros-rapidos">
          <input type="text" id="reg-buscar" class="input-filtro" placeholder="Buscar nombre o carnet...">
        </div>
        <div class="tabla-wrap">
          <table class="tabla-inscritos">
            <thead>
              <tr>
                <th>#</th>
                <th>Participante</th>
                <th>Carnet</th>
                <th>Iglesia</th>
                <th>Distrito</th>
                <th>Paquete</th>
                <th>Fecha y hora</th>
                <th>Registró</th>
                <th>Método</th>
                <?php if($puede_gestionar): ?><th>Acción</th><?php endif; ?>
              </tr>
            </thead>
            <tbody id="tbody-registrados"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ══ PUNTOS DE REGISTRO ══ -->
    <?php if($puede_gestionar): ?>
    <div class="ci-panel" id="panel-puntos">
      <div class="card esc-bloque">
        <h3 id="punto-form-titulo"><i class="fa-solid fa-plus"></i> Nuevo punto de registro</h3>
        <form id="form-punto" class="esc-form-punto">
          <input type="hidden" id="punto-id" value="0">
          <div class="modal-field">
            <label class="modal-label" for="punto-nombre">Nombre</label>
            <input type="text" id="punto-nombre" class="input-filtro" maxlength="100"
                   placeholder="Ej: Material, Refrigerio 1" required>
          </div>
          <div class="modal-field">
            <label class="modal-label" for="punto-orden">Orden</label>
            <input type="number" id="punto-orden" class="input-filtro" value="0">
          </div>
          <div class="esc-form-acciones">
            <button type="submit" class="btn-primary" id="btn-guardar-punto">
              <i class="fa-solid fa-floppy-disk"></i> Guardar
            </button>
            <button type="button" class="btn-secondary" id="btn-cancelar-punto" hidden>Cancelar</button>
          </div>
        </form>
      </div>

      <div class="card" style="margin-top:20px;">
        <div class="card-header">
          <h3><i class="fa-solid fa-sliders"></i> Puntos de registro</h3>
        </div>
        <div class="tabla-wrap">
          <table class="tabla-inscritos">
            <thead>
              <tr><th>Orden</th><th>Nombre</th><th>Registrados</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody id="tbody-puntos"></tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>
</main>

<div class="toast" id="toast-escaner"></div>

<?php $js_pagina = ['vendor/html5-qrcode.min.js', 'escanear.js']; ?>
<?php include_once 'templates/footer.php'; ?>
