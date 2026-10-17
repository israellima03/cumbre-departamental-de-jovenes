<?php
require_once('funciones/sesiones.php');
usuario_autentificado();
verificar_acceso(['Administrador','tesorera','Lider departamental']);

header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

require_once('../includes/funciones/bd_conexion.php');

include_once 'templates/header.php';
include_once 'templates/navegacion.php';
include_once 'templates/barra.php';
include_once 'funciones/funciones.php';
?>

<main class="content" id="main-content">
  <div class="page active" id="page-confirmar-inscripciones">

    <div class="page-header">
      <div>
        <h1 class="page-title">Confirmar <span>Inscripciones</span></h1>
        <p class="page-sub">Confirma los pagos por QR y los pagos en efectivo entregados a tesorería.</p>
      </div>
    </div>

    <!-- PESTAÑAS -->
    <div class="ci-tabs">
      <button class="ci-tab active" data-tab="qr">
        <i class="fa-solid fa-qrcode"></i> Confirmar QR
      </button>
      <button class="ci-tab" data-tab="efectivo">
        <i class="fa-solid fa-money-bill-wave"></i> Confirmar Efectivo
      </button>
    </div>

    <!-- ══ PANEL QR ══ -->
    <div class="ci-panel active" id="panel-qr">

    <div class="ci-panel-header">
      <div class="qr-stats-mini">
        <span class="qr-stat-item" id="stat-pend-mini">
          <i class="fa-solid fa-clock"></i>
          <span id="num-pendientes">—</span> pendientes
        </span>
        <span class="qr-stat-item qr-stat-ok" id="stat-conf-mini">
          <i class="fa-solid fa-circle-check"></i>
          <span id="num-confirmados">—</span> confirmados
        </span>
      </div>
    </div>

    <!-- FILTROS -->
    <div class="qr-filtros">
      <div class="qr-filtro-grupo">
        <i class="fa-solid fa-search"></i>
        <input type="text" id="qr-buscar"
               placeholder="Buscar por nombre, carnet o celular...">
      </div>
      <div class="qr-filtro-grupo">
        <i class="fa-solid fa-calendar"></i>
        <input type="date" id="qr-fecha-desde" title="Desde">
        <span>—</span>
        <input type="date" id="qr-fecha-hasta" title="Hasta">
      </div>
      <select id="qr-estado" class="select-filtro">
        <option value="pendiente">Solo pendientes</option>
        <option value="confirmado">Solo confirmados</option>
        <option value="">Todos</option>
      </select>
      <button class="btn-tool" id="btn-limpiar-filtros">
        <i class="fa-solid fa-broom"></i> Limpiar
      </button>
    </div>

    <!-- TABLA -->
    <div class="card" style="margin-top:20px;overflow:visible;">
      <div class="card-header">
        <h3><i class="fa-solid fa-qrcode"></i> Inscripciones por QR</h3>
        <span class="qr-total-lbl" id="qr-total-lbl">Cargando...</span>
      </div>
      <div style="display:flex;width:100%;overflow:hidden;position:relative;">
          <div class="tabla-wrap" style="flex:1;min-width:0;overflow-x:auto;display:block;-webkit-overflow-scrolling:touch;">
          <table class="tabla-inscritos" id="tabla-qr">
            <thead>
              <tr>
                <th>#</th>
                <th>Participante</th>
                <th>Carnet</th>
                <th>Celular</th>
                <th>Iglesia</th>
                <th>Paquete</th>
                <th>Precio paquete</th>
                <th>Descuento</th>
                <th>Total</th>
                <th>Fecha registro</th>
                <th>Comprobante</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody id="tbody-qr">
              <tr>
                <td colspan="12" class="tabla-loading">
                  <i class="fa-solid fa-spinner fa-spin"></i> Cargando...
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div style="flex-shrink:0;border-left:2px solid var(--border);background:var(--card-bg);box-shadow:-4px 0 10px rgba(0,0,0,.1);">
          <table class="tabla-inscritos" id="tabla-acc">
            <thead>
              <tr><th>Acción</th></tr>
            </thead>
            <tbody id="tbody-acc">
              <tr><td class="tabla-loading"></td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="paginacion" id="paginacion-qr"></div>
    </div>

    </div><!-- /panel-qr -->

    <!-- ══ PANEL EFECTIVO ══ -->
    <div class="ci-panel" id="panel-efectivo">

      <div class="ci-panel-header">
        <div class="qr-stats-mini">
          <span class="qr-stat-item">
            <i class="fa-solid fa-clock"></i>
            <span id="ef-num-pendientes">—</span> pendientes
          </span>
          <span class="qr-stat-item qr-stat-ok">
            <i class="fa-solid fa-circle-check"></i>
            <span id="ef-num-confirmados">—</span> confirmados
          </span>
        </div>
      </div>

      <div class="qr-filtros">
        <div class="qr-filtro-grupo">
          <i class="fa-solid fa-search"></i>
          <input type="text" id="ef-buscar" placeholder="Buscar por nombre, carnet o celular...">
        </div>
        <select id="ef-distrito" class="select-filtro">
          <option value="">Todos los distritos</option>
          <?php
            $res_dis = $conn->query("SELECT id, nombre FROM distritos ORDER BY nombre");
            while($d = $res_dis->fetch_assoc()):
          ?>
            <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['nombre']); ?></option>
          <?php endwhile; ?>
        </select>
        <select id="ef-estado" class="select-filtro">
          <option value="pendiente">Solo pendientes</option>
          <option value="confirmado">Solo confirmados</option>
          <option value="">Todos</option>
        </select>
      </div>

      <div class="card" style="margin-top:20px;">
        <div class="card-header">
          <h3><i class="fa-solid fa-money-bill-wave"></i> Inscripciones en Efectivo</h3>
          <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <span class="qr-total-lbl" id="ef-total-lbl">Cargando...</span>
            <span class="ef-suma" id="ef-suma"></span>
          </div>
        </div>
        <div class="tabla-wrap">
          <table class="tabla-inscritos" id="tabla-efectivo">
            <thead>
              <tr>
                <th>#</th>
                <th>Participante</th>
                <th>Carnet</th>
                <th>Celular</th>
                <th>Iglesia</th>
                <th>Distrito</th>
                <th>Paquete</th>
                <th>Total</th>
                <th>Registró</th>
                <th>Fecha registro</th>
                <th>Estado</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody id="tbody-efectivo">
              <tr><td colspan="12" class="tabla-loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

    </div><!-- /panel-efectivo -->

  </div>
</main>

<!-- MODAL CONFIRMAR -->
<div class="modal-overlay" id="modal-qr-overlay">
  <div class="modal" id="modal-qr">
    <div class="modal-header">
      <h3><i class="fa-solid fa-file-invoice"></i> Detalle de Inscripción</h3>
      <button class="modal-close" id="btn-modal-qr-close">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div class="modal-body" id="modal-qr-body"></div>
    <div class="modal-footer">
      <button class="btn-secondary" id="btn-modal-qr-cancelar">Cerrar</button>
      <button class="btn-success" id="btn-modal-qr-confirmar" style="display:none;">
        <i class="fa-solid fa-check"></i> Confirmar Pago
      </button>
    </div>
  </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast-qr"></div>

<script>
  var PUEDE_CONFIRMAR = <?php echo puede(['Administrador','tesorera']) ? 'true' : 'false'; ?>;
</script>
<?php $js_pagina = ['confirmar-qr.js', 'confirmar-efectivo.js']; ?>
<?php include_once 'templates/footer.php'; ?>