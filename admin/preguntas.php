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

$puede_gestionar = puede(['Administrador','Lider departamental']);
$seccion = trim($_GET['seccion'] ?? 'ver');

/* si no puede gestionar, forzar a ver */
if(!$puede_gestionar) $seccion = 'ver';

/* cargar eventos para el filtro */
$eventos_lista = [];
$res = $conn->query("
  SELECT e.id_evento, d.nombre AS dia,
         CONCAT(e.hora_inicio,' – ',e.hora_fin) AS horario,
         e.tipo_evento
  FROM eventos e
  LEFT JOIN dias d ON d.id_dia = e.id_dia
  ORDER BY e.fecha, e.hora_inicio
");
if($res) while($r = $res->fetch_assoc()) $eventos_lista[] = $r;
?>

<main class="content" id="main-content">

  <div class="page-header">
    <div>
      <h1 class="page-title">Preguntas <span>del Público</span></h1>
      <p class="page-sub">Gestión y visualización de preguntas enviadas durante el evento.</p>
    </div>
    <?php if($puede_gestionar): ?>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <a href="preguntas.php?seccion=habilitar"
         class="btn-<?php echo $seccion==='habilitar'?'primary':'secondary'; ?>"
         style="text-decoration:none;">
        <i class="fa-solid fa-toggle-on"></i> Habilitar Preguntas
      </a>
      <a href="preguntas.php?seccion=ver"
         class="btn-<?php echo $seccion==='ver'?'primary':'secondary'; ?>"
         style="text-decoration:none;">
        <i class="fa-solid fa-list-ul"></i> Ver Preguntas
      </a>
    </div>
    <?php endif; ?>
  </div>

  <?php if($puede_gestionar && $seccion === 'habilitar'): ?>
  <!-- ══════════════════════════════════════
       SECCIÓN: HABILITAR / GESTIONAR
  ══════════════════════════════════════ -->
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header">
      <h3><i class="fa-solid fa-toggle-on"></i> Control de Preguntas por Evento</h3>
      <span id="eventos-total-lbl" style="font-size:12px;color:var(--txt-xsoft);"></span>
    </div>
    <p style="padding:12px 20px 0;font-size:13px;color:var(--txt-soft);">
      Activa o desactiva las preguntas del público para cada evento. Solo los eventos con preguntas activas mostrarán el formulario al público.
    </p>
    <div class="tabla-wrap" style="overflow-x:auto;">
      <table class="tabla-inscritos">
        <thead>
          <tr>
            <th>#</th>
            <th>Día</th>
            <th>Horario</th>
            <th>Tipo</th>
            <th>Expositor / Grupo</th>
            <th>Tema</th>
            <th>Preguntas</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody id="tbody-eventos-preguntas">
          <tr><td colspan="8" class="tabla-loading">
            <i class="fa-solid fa-spinner fa-spin"></i> Cargando...
          </td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <?php else: ?>
  <!-- ══════════════════════════════════════
       SECCIÓN: VER PREGUNTAS
  ══════════════════════════════════════ -->
  <div class="card">
    <div class="card-header">
      <h3><i class="fa-solid fa-circle-question"></i> Preguntas del Público</h3>
      <span id="preguntas-total-lbl" style="font-size:12px;color:var(--txt-xsoft);"></span>
    </div>
    <div class="filtros-rapidos">
      <select id="filtro-evento-preguntas" class="select-filtro" onchange="cargarPreguntas()">
        <option value="">Todos los eventos</option>
        <?php foreach($eventos_lista as $ev): ?>
          <option value="<?php echo $ev['id_evento']; ?>">
            <?php echo htmlspecialchars($ev['dia'].' '.$ev['horario'].' — '.$ev['tipo_evento']); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <select id="filtro-estado-preguntas" class="select-filtro" onchange="cargarPreguntas()">
        <option value="">Todos los estados</option>
        <option value="pendiente">Pendiente</option>
        <option value="aprobada">Aprobada</option>
        <option value="rechazada">Rechazada</option>
      </select>
    </div>
    <div class="tabla-wrap" style="overflow-x:auto;">
      <table class="tabla-inscritos">
        <thead>
          <tr>
            <th>#</th>
            <th>Evento</th>
            <th>Pregunta</th>
            <th>Estado</th>
            <th>Fecha</th>
            <?php if($puede_gestionar): ?><th>Acciones</th><?php endif; ?>
          </tr>
        </thead>
        <tbody id="tbody-preguntas">
          <tr><td colspan="6" class="tabla-loading">
            <i class="fa-solid fa-spinner fa-spin"></i> Cargando...
          </td></tr>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

</main>

<!-- MODAL CONFIRMAR ELIMINAR -->
<?php if($puede_gestionar): ?>
<div class="modal-overlay" id="modal-eliminar-pregunta" style="display:none;">
  <div class="modal" style="max-width:360px;">
    <div class="modal-header" style="background:#dc2626;">
      <h3><i class="fa-solid fa-trash"></i> Eliminar Pregunta</h3>
      <button class="modal-close" onclick="document.getElementById('modal-eliminar-pregunta').style.display='none'">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div class="modal-body" style="text-align:center;padding:28px 20px;">
      <i class="fa-solid fa-triangle-exclamation" style="font-size:2.5em;color:#dc2626;margin-bottom:14px;display:block;"></i>
      <p style="font-size:14px;font-weight:600;color:var(--txt);">¿Eliminar esta pregunta?</p>
      <p style="font-size:12px;color:var(--txt-xsoft);margin-top:6px;">Esta acción no se puede deshacer.</p>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="document.getElementById('modal-eliminar-pregunta').style.display='none'">Cancelar</button>
      <button class="btn-primary" id="btn-confirmar-eliminar-pregunta" style="background:#dc2626;">
        <i class="fa-solid fa-trash"></i> Eliminar
      </button>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="toast" id="toast-preguntas"></div>

<style>
/* ── TOGGLE SWITCH ── */
.toggle-switch {
  position: relative; display: inline-flex;
  align-items: center; gap: 8px; cursor: pointer;
}
.toggle-switch input { display: none; }
.toggle-track {
  width: 42px; height: 24px;
  background: #e5e7eb; border-radius: 12px;
  position: relative; transition: background .2s;
  flex-shrink: 0;
}
.toggle-track::after {
  content: '';
  position: absolute; top: 3px; left: 3px;
  width: 18px; height: 18px;
  background: #fff; border-radius: 50%;
  transition: transform .2s;
  box-shadow: 0 1px 3px rgba(0,0,0,.2);
}
.toggle-switch input:checked + .toggle-track { background: var(--green); }
.toggle-switch input:checked + .toggle-track::after { transform: translateX(18px); }
.toggle-lbl { font-size: 12px; font-weight: 700; color: var(--txt-soft); }
.toggle-switch input:checked ~ .toggle-lbl { color: var(--green); }

/* ── BADGE ESTADO ── */
.badge-pendiente-p  { background:#fef9c3;color:#713f12;border:1px solid #ca8a04; }
.badge-aprobada-p   { background:#d1fae5;color:#065f46;border:1px solid #10b981; }
.badge-rechazada-p  { background:#fee2e2;color:#991b1b;border:1px solid #ef4444; }

/* ── PREGUNTA TEXTO ── */
.pregunta-txt {
  max-width: 320px;
  white-space: normal;
  font-size: 13px;
  line-height: 1.4;
}

/* ── RESPONSIVE ── */
@media (max-width: 768px) {
  .page-header { flex-direction: column; align-items: flex-start; }
  .page-header > div:last-child { width: 100%; }
  .page-header > div:last-child a { flex: 1; justify-content: center; }
}

@media (max-width: 480px) {
  .filtros-rapidos { flex-direction: column; }
  .filtros-rapidos .select-filtro { width: 100%; min-width: unset; }
}
</style>

<script>
var PUEDE_GESTIONAR_P = <?php echo $puede_gestionar ? 'true' : 'false'; ?>;
var SECCION_ACTUAL    = '<?php echo $seccion; ?>';
</script>
<?php $js_pagina = ['preguntas.js']; ?>
<?php include_once 'templates/footer.php'; ?>