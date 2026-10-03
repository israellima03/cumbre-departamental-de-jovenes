<?php
require_once('funciones/sesiones.php');
usuario_autentificado();

header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

require_once('../includes/funciones/bd_conexion.php');
include_once 'templates/header.php';
include_once 'templates/navegacion.php';
include_once 'templates/barra.php';
include_once 'funciones/funciones.php';

$puede_gastos = puede(['Administrador','Lider departamental','tesorera']);
?>

<main class="content" id="main-content">

  <div class="page-header">
    <div>
      <h1 class="page-title">Reportes <span>de la Cumbre</span></h1>
      <p class="page-sub">Estadísticas, entregas, economía y gastos del evento.</p>
    </div>
  </div>

  <!-- TABS -->
  <div class="rep-tabs">
    <button class="rep-tab active" data-tab="inscritos">
      <i class="fa-solid fa-users"></i><span class="tab-lbl"> Inscritos</span>
    </button>
    <button class="rep-tab" data-tab="entregas">
      <i class="fa-solid fa-box-open"></i><span class="tab-lbl"> Entregas</span>
    </button>
    <button class="rep-tab" data-tab="economia">
      <i class="fa-solid fa-coins"></i><span class="tab-lbl"> Economía</span>
    </button>
    <button class="rep-tab" data-tab="gastos">
      <i class="fa-solid fa-receipt"></i><span class="tab-lbl"> Gastos</span>
    </button>
  </div>

  <!-- ══ TAB INSCRITOS ══ -->
  <div class="rep-panel active" id="panel-inscritos">
    <div class="rep-stats">
      <div class="rep-stat-card rep-blue">
        <div class="rep-stat-icon"><i class="fa-solid fa-users"></i></div>
        <div><div class="rep-stat-num" id="rs-total">—</div><div class="rep-stat-lbl">Total Inscritos</div></div>
      </div>
      <div class="rep-stat-card rep-green">
        <div class="rep-stat-icon"><i class="fa-solid fa-circle-check"></i></div>
        <div><div class="rep-stat-num" id="rs-confirmados">—</div><div class="rep-stat-lbl">Confirmados</div></div>
      </div>
      <div class="rep-stat-card rep-orange">
        <div class="rep-stat-icon"><i class="fa-solid fa-clock"></i></div>
        <div><div class="rep-stat-num" id="rs-pendientes">—</div><div class="rep-stat-lbl">Pendientes</div></div>
      </div>
      <div class="rep-stat-card rep-purple">
        <div class="rep-stat-icon"><i class="fa-solid fa-money-bill-wave"></i></div>
        <div><div class="rep-stat-num" id="rs-recaudado">—</div><div class="rep-stat-lbl">Total Recaudado</div></div>
      </div>
    </div>

    <div class="rep-grid-4">
      <div class="card">
        <div class="card-header"><h3><i class="fa-solid fa-church"></i> Por Iglesia</h3></div>
        <div class="tabla-wrap"><table class="tabla-inscritos"><thead><tr><th>Iglesia</th><th>Total</th><th>Confirmados</th></tr></thead><tbody id="tb-iglesia"><tr><td colspan="3" class="tabla-loading"><i class="fa-solid fa-spinner fa-spin"></i></td></tr></tbody></table></div>
      </div>
      <div class="card">
        <div class="card-header"><h3><i class="fa-solid fa-map"></i> Por Distrito</h3></div>
        <div class="tabla-wrap"><table class="tabla-inscritos"><thead><tr><th>Distrito</th><th>Total</th><th>Confirmados</th></tr></thead><tbody id="tb-distrito"><tr><td colspan="3" class="tabla-loading"><i class="fa-solid fa-spinner fa-spin"></i></td></tr></tbody></table></div>
      </div>
      <div class="card">
        <div class="card-header"><h3><i class="fa-solid fa-box"></i> Por Paquete</h3></div>
        <div class="tabla-wrap"><table class="tabla-inscritos"><thead><tr><th>Paquete</th><th>Total</th><th>Recaudado</th></tr></thead><tbody id="tb-paquete"><tr><td colspan="3" class="tabla-loading"><i class="fa-solid fa-spinner fa-spin"></i></td></tr></tbody></table></div>
      </div>
      <div class="card">
        <div class="card-header"><h3><i class="fa-solid fa-credit-card"></i> Por Método</h3></div>
        <div class="tabla-wrap"><table class="tabla-inscritos"><thead><tr><th>Método</th><th>Total</th><th>Recaudado</th></tr></thead><tbody id="tb-metodo"><tr><td colspan="3" class="tabla-loading"><i class="fa-solid fa-spinner fa-spin"></i></td></tr></tbody></table></div>
      </div>
    </div>

    <div class="card" style="margin-top:16px;">
      <div class="card-header">
        <h3><i class="fa-solid fa-list"></i> Lista de Inscritos</h3>
        <span id="rep-inscritos-lbl" style="font-size:12px;color:var(--txt-xsoft);"></span>
      </div>
      <div class="filtros-rapidos">
        <input type="text" id="rfiltro-nombre" placeholder="Buscar nombre..." class="input-filtro">
        <select id="rfiltro-estado" class="select-filtro" onchange="cargarTablaInscritos()">
          <option value="">Todos los estados</option>
          <option value="confirmado">Confirmado</option>
          <option value="pendiente">Pendiente</option>
        </select>
        <select id="rfiltro-pago" class="select-filtro" onchange="cargarTablaInscritos()">
          <option value="">Todos los métodos</option>
          <option value="qr">QR</option>
          <option value="efectivo">Efectivo</option>
        </select>
      </div>
      <div class="tabla-wrap" style="overflow-x:auto;">
        <table class="tabla-inscritos rep-tabla-sticky">
          <thead>
            <tr>
              <th>#</th><th>Nombre</th><th>Celular</th><th>Iglesia</th>
              <th class="col-hide-sm">Distrito</th><th>Paquete</th>
              <th>Pago</th><th>Estado</th>
              <th class="col-hide-sm">Bs.</th>
              <th class="col-hide-md">Registró</th><th class="col-hide-md">Confirmó</th>
            </tr>
          </thead>
          <tbody id="tb-inscritos-rep">
            <tr><td colspan="11" class="tabla-loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</td></tr>
          </tbody>
        </table>
      </div>
      <div class="rep-total-pie" id="total-inscritos-pie"></div>
    </div>
  </div>

  <!-- ══ TAB ENTREGAS ══ -->
  <div class="rep-panel" id="panel-entregas">
    <div class="rep-stats">
      <div class="rep-stat-card rep-blue">
        <div class="rep-stat-icon"><i class="fa-solid fa-gift"></i></div>
        <div><div class="rep-stat-num" id="re-mat-entregados">—</div><div class="rep-stat-lbl">Materiales Entregados</div></div>
      </div>
      <div class="rep-stat-card rep-purple">
        <div class="rep-stat-icon"><i class="fa-solid fa-gift" style="opacity:.4;"></i></div>
        <div><div class="rep-stat-num" id="re-mat-pendientes">—</div><div class="rep-stat-lbl">Materiales Pendientes</div></div>
      </div>
    </div>

    <div class="card" style="margin-top:16px;">
      <div class="card-header">
        <h3><i class="fa-solid fa-box-open"></i> Detalle de Entregas</h3>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
          <select id="rfiltro-entrega-estado" class="select-filtro" onchange="cargarTablaEntregas()">
            <option value="">Todos</option>
            <option value="entregado">Entregado</option>
            <option value="pendiente">Pendiente</option>
          </select>
        </div>
      </div>
      <div class="tabla-wrap" style="overflow-x:auto;">
        <table class="tabla-inscritos rep-tabla-sticky">
          <thead>
            <tr><th>#</th><th>Inscrito</th><th>Ítem</th><th>Tipo</th><th>Estado</th><th class="col-hide-sm">Entregado por</th></tr>
          </thead>
          <tbody id="tb-entregas-rep">
            <tr><td colspan="6" class="tabla-loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ══ TAB ECONOMÍA ══ -->
  <div class="rep-panel" id="panel-economia">

    <!-- Stats sin balance -->
    <div class="rep-stats">
      <div class="rep-stat-card rep-green">
        <div class="rep-stat-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
        <div><div class="rep-stat-num" id="ec-ingresos">—</div><div class="rep-stat-lbl">Ingresos Inscritos (Bs.)</div></div>
      </div>
      <div class="rep-stat-card rep-purple">
        <div class="rep-stat-icon"><i class="fa-solid fa-hand-holding-heart"></i></div>
        <div><div class="rep-stat-num" id="ec-ofrendas">—</div><div class="rep-stat-lbl">Ofrendas de Amor (Bs.)</div></div>
      </div>
      <div class="rep-stat-card rep-orange">
        <div class="rep-stat-icon"><i class="fa-solid fa-receipt"></i></div>
        <div><div class="rep-stat-num" id="ec-gastos">—</div><div class="rep-stat-lbl">Total Gastos (Bs.)</div></div>
      </div>
    </div>

    <!-- Selector de vista -->
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;align-items:center;">
      <span style="font-size:13px;font-weight:600;color:var(--txt-soft);">Ver recaudado por:</span>
      <button class="ec-filtro-btn active" data-vista="iglesia">Iglesia</button>
      <button class="ec-filtro-btn" data-vista="distrito">Distrito</button>
      <button class="ec-filtro-btn" data-vista="metodo">Método de Pago</button>
    </div>

    <!-- Tabla dinámica economía -->
    <div class="card" id="ec-tabla-card">
      <div class="card-header">
        <h3 id="ec-tabla-titulo"><i class="fa-solid fa-church"></i> Recaudado por Iglesia</h3>
        <div class="rep-total-pie" id="ec-total-pie" style="font-size:14px;"></div>
      </div>
      <div class="tabla-wrap" style="overflow-x:auto;">
        <table class="tabla-inscritos">
          <thead id="ec-thead">
            <tr><th>Iglesia</th><th>Inscritos</th><th>Recaudado (Bs.)</th></tr>
          </thead>
          <tbody id="tb-economia-dyn">
            <tr><td colspan="3" class="tabla-loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</td></tr>
          </tbody>
        </table>
      </div>
      <div style="padding:12px 20px;border-top:1px solid var(--border);text-align:right;">
        <span style="font-size:13px;font-weight:700;color:var(--green);">
          Total: <span id="ec-suma-pie">Bs. 0.00</span>
        </span>
      </div>
    </div>
  </div>

  <!-- ══ TAB GASTOS ══ -->
  <div class="rep-panel" id="panel-gastos">

    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
      <?php if($puede_gastos): ?>
      <button class="btn-primary" onclick="abrirModalGasto()">
        <i class="fa-solid fa-plus"></i> Nuevo Gasto
      </button>
      <button class="btn-primary" style="background:var(--green);" onclick="abrirModalOfrenda()">
        <i class="fa-solid fa-plus"></i> Nueva Ofrenda
      </button>
      <?php endif; ?>
    </div>

    <div class="rep-grid-2">

      <!-- GASTOS -->
      <div class="card">
        <div class="card-header">
          <h3><i class="fa-solid fa-receipt"></i> Gastos</h3>
          <span id="gastos-lbl" style="font-size:12px;color:var(--txt-xsoft);"></span>
        </div>
        <div class="tabla-wrap" style="overflow-x:auto;">
          <table class="tabla-inscritos rep-tabla-sticky">
            <thead>
              <tr><th>#</th><th>Motivo</th><th>Responsable</th><th>Bs.</th><th class="col-hide-sm">Fecha</th><?php if($puede_gastos): ?><th>Acc.</th><?php endif; ?></tr>
            </thead>
            <tbody id="tb-gastos">
              <tr><td colspan="6" class="tabla-loading"><i class="fa-solid fa-spinner fa-spin"></i></td></tr>
            </tbody>
          </table>
        </div>
        <div style="padding:12px 20px;border-top:1px solid var(--border);text-align:right;">
          <span style="font-size:13px;font-weight:700;color:var(--accent);">Total: <span id="total-gastos-pie">Bs. 0.00</span></span>
        </div>
      </div>

      <!-- OFRENDAS -->
      <div class="card">
        <div class="card-header">
          <h3><i class="fa-solid fa-hand-holding-heart"></i> Ofrendas de Amor</h3>
          <span id="ofrendas-lbl" style="font-size:12px;color:var(--txt-xsoft);"></span>
        </div>
        <div class="tabla-wrap" style="overflow-x:auto;">
          <table class="tabla-inscritos rep-tabla-sticky">
            <thead>
              <tr><th>#</th><th>De parte de</th><th>Recibido por</th><th>Bs.</th><th class="col-hide-sm">Fecha</th><?php if($puede_gastos): ?><th>Acc.</th><?php endif; ?></tr>
            </thead>
            <tbody id="tb-ofrendas">
              <tr><td colspan="6" class="tabla-loading"><i class="fa-solid fa-spinner fa-spin"></i></td></tr>
            </tbody>
          </table>
        </div>
        <div style="padding:12px 20px;border-top:1px solid var(--border);text-align:right;">
          <span style="font-size:13px;font-weight:700;color:var(--green);">Total: <span id="total-ofrendas-pie">Bs. 0.00</span></span>
        </div>
      </div>
    </div>
  </div>

</main>

<?php if($puede_gastos): ?>
<!-- MODAL GASTO -->
<div class="modal-overlay" id="modal-gasto" style="display:none;">
  <div class="modal" style="max-width:460px;">
    <div class="modal-header">
      <h3 id="modal-gasto-titulo"><i class="fa-solid fa-receipt"></i> Gasto</h3>
      <button class="modal-close" onclick="cerrarModalRep('modal-gasto')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="gasto-id">
      <div style="display:flex;flex-direction:column;gap:12px;">
        <div class="modal-field">
          <span class="modal-label">Motivo *</span>
          <input type="text" id="gasto-motivo" class="input-filtro" style="width:100%;" placeholder="Ej: Compra de materiales">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="modal-field">
            <span class="modal-label">Monto Bs. *</span>
            <input type="number" id="gasto-monto" class="input-filtro" style="width:100%;" min="0.01" step="0.01" placeholder="0.00">
          </div>
          <div class="modal-field">
            <span class="modal-label">Fecha *</span>
            <input type="date" id="gasto-fecha" class="input-filtro" style="width:100%;">
          </div>
        </div>
        <div class="modal-field">
          <span class="modal-label">Responsable *</span>
          <input type="text" id="gasto-responsable" class="input-filtro" style="width:100%;" placeholder="Nombre de quien realizó el gasto">
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="cerrarModalRep('modal-gasto')">Cancelar</button>
      <button class="btn-primary" id="btn-guardar-gasto"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
    </div>
  </div>
</div>

<!-- MODAL OFRENDA -->
<div class="modal-overlay" id="modal-ofrenda" style="display:none;">
  <div class="modal" style="max-width:460px;">
    <div class="modal-header" style="background:var(--green);">
      <h3 id="modal-ofrenda-titulo"><i class="fa-solid fa-hand-holding-heart"></i> Ofrenda de Amor</h3>
      <button class="modal-close" onclick="cerrarModalRep('modal-ofrenda')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="ofrenda-id">
      <div style="display:flex;flex-direction:column;gap:12px;">
        <div class="modal-field">
          <span class="modal-label">De parte de *</span>
          <input type="text" id="ofrenda-de" class="input-filtro" style="width:100%;" placeholder="Nombre o empresa">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="modal-field">
            <span class="modal-label">Monto Bs. *</span>
            <input type="number" id="ofrenda-monto" class="input-filtro" style="width:100%;" min="0.01" step="0.01" placeholder="0.00">
          </div>
          <div class="modal-field">
            <span class="modal-label">Fecha *</span>
            <input type="date" id="ofrenda-fecha" class="input-filtro" style="width:100%;">
          </div>
        </div>
        <div class="modal-field">
          <span class="modal-label">Notas</span>
          <input type="text" id="ofrenda-notas" class="input-filtro" style="width:100%;" placeholder="Observación adicional">
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="cerrarModalRep('modal-ofrenda')">Cancelar</button>
      <button class="btn-primary" id="btn-guardar-ofrenda" style="background:var(--green);"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
    </div>
  </div>
</div>

<!-- MODAL ELIMINAR -->
<div class="modal-overlay" id="modal-eliminar-rep" style="display:none;">
  <div class="modal" style="max-width:340px;">
    <div class="modal-header" style="background:#dc2626;">
      <h3><i class="fa-solid fa-trash"></i> Eliminar</h3>
      <button class="modal-close" onclick="cerrarModalRep('modal-eliminar-rep')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body" style="text-align:center;padding:24px;">
      <i class="fa-solid fa-triangle-exclamation" style="font-size:2.5em;color:#dc2626;margin-bottom:12px;display:block;"></i>
      <p id="eliminar-rep-txt" style="font-size:14px;font-weight:600;">¿Eliminar este registro?</p>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="cerrarModalRep('modal-eliminar-rep')">Cancelar</button>
      <button class="btn-primary" id="btn-confirmar-eliminar-rep" style="background:#dc2626;">
        <i class="fa-solid fa-trash"></i> Eliminar
      </button>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="toast" id="toast-reportes"></div>

<style>
.rep-tabs {
  display:flex; gap:4px; margin-bottom:20px;
  background:var(--card-bg); border:1px solid var(--border);
  border-radius:var(--radius); padding:6px; flex-wrap:wrap;
  box-shadow:var(--shadow);
}
.rep-tab {
  display:flex; align-items:center; gap:7px;
  padding:10px 18px; border:none; border-radius:8px;
  background:transparent; color:var(--txt-soft);
  font-family:var(--font-body); font-size:13px; font-weight:600;
  cursor:pointer; transition:all .2s; white-space:nowrap;
}
.rep-tab:hover { background:var(--bg); color:var(--txt); }
.rep-tab.active { background:var(--sidebar-bg); color:#fff; }
.rep-panel { display:none; }
.rep-panel.active { display:block; }

.rep-stats {
  display:grid; grid-template-columns:repeat(4,1fr);
  gap:16px; margin-bottom:20px;
}
.rep-stat-card {
  background:var(--card-bg); border-radius:var(--radius);
  padding:18px; display:flex; align-items:center; gap:14px;
  border:1px solid var(--border); box-shadow:var(--shadow);
  position:relative; overflow:hidden;
}
.rep-stat-card::after {
  content:''; position:absolute; top:0; left:0; right:0; height:3px;
}
.rep-blue::after   { background:var(--blue); }
.rep-green::after  { background:var(--green); }
.rep-orange::after { background:var(--orange); }
.rep-purple::after { background:var(--purple); }
.rep-stat-icon {
  width:42px; height:42px; border-radius:10px;
  display:grid; place-items:center; font-size:18px; flex-shrink:0;
}
.rep-blue   .rep-stat-icon { background:var(--blue-light);   color:var(--blue); }
.rep-green  .rep-stat-icon { background:var(--green-light);  color:var(--green); }
.rep-orange .rep-stat-icon { background:var(--orange-light); color:var(--orange); }
.rep-purple .rep-stat-icon { background:var(--purple-light); color:var(--purple); }
.rep-stat-num { font-family:var(--font-display); font-size:22px; font-weight:700; color:var(--txt); line-height:1.1; }
.rep-stat-lbl { font-size:11px; color:var(--txt-soft); margin-top:2px; }

.rep-grid-4 { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:16px; }
.rep-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }

/* Economía — botones filtro */
.ec-filtro-btn {
  padding:7px 14px; border:1.5px solid var(--border);
  border-radius:8px; background:var(--card-bg);
  color:var(--txt-soft); font-family:var(--font-body);
  font-size:12px; font-weight:600; cursor:pointer;
  transition:all .2s;
}
.ec-filtro-btn:hover  { border-color:var(--blue); color:var(--blue); }
.ec-filtro-btn.active { background:var(--sidebar-bg); color:#fff; border-color:var(--sidebar-bg); }

.rep-total-pie { font-size:12px; color:var(--txt-soft); }

/* badges entrega */
.badge-entregado { background:#d1fae5;color:#065f46;border:1px solid #10b981; }
.badge-pend-ent  { background:#fee2e2;color:#991b1b;border:1px solid #ef4444; }

/* sticky last column */
.rep-tabla-sticky { min-width:480px; }
.rep-tabla-sticky thead th:last-child,
.rep-tabla-sticky tbody td:last-child {
  position:sticky; right:0;
  background:var(--card-bg);
  box-shadow:-2px 0 6px rgba(0,0,0,.08);
}
.rep-tabla-sticky thead th:last-child { background:var(--sidebar-bg); }

/* ══ RESPONSIVE ══ */
@media(max-width:1100px){
  .rep-stats  { grid-template-columns:repeat(2,1fr); }
  .rep-grid-4 { grid-template-columns:repeat(2,1fr); }
}
@media(max-width:768px){
  .rep-tabs  { overflow-x:auto; flex-wrap:nowrap; padding:4px; }
  .rep-tab   { flex-shrink:0; padding:9px 12px; font-size:12px; }
  .rep-grid-2 { grid-template-columns:1fr; }
  .tabla-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch; }
  .page-header { flex-direction:column; align-items:flex-start; }
}
@media(max-width:480px){
  .rep-tab .tab-lbl { display:none; }
  .rep-tab { padding:10px; }
  .rep-stats  { grid-template-columns:1fr 1fr; }
  .rep-grid-4 { grid-template-columns:1fr; }
  .rep-stat-num { font-size:18px; }
  .modal-body div[style*="grid-template-columns:1fr 1fr"] {
    display:flex !important; flex-direction:column !important;
  }
}
</style>

<script>
var PUEDE_EDITAR_REP = <?php echo $puede_gastos ? 'true' : 'false'; ?>;
</script>
<?php $js_pagina = ['reportes.js']; ?>
<?php include_once 'templates/footer.php'; ?>