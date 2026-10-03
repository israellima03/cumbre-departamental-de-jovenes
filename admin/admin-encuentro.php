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

/* roles con permiso de acción */
$puede_gestionar = puede(['Administrador','Lider departamental','Lider distrital','tesorera','secretario']);

/* puede confirmar entregas de material */
$puede_entregar  = puede(['Administrador','Lider departamental','Lider distrital','tesorera','secretario','Equipo departamental']);
$puede_ver_solo  = puede(['Lider local']);

/* cargar datos para selects del modal */
$iglesias_modal = [];
$res = $conn->query("SELECT i.id, i.nombre, d.id AS distrito_id, d.nombre AS distrito FROM iglesias i LEFT JOIN distritos d ON d.id=i.distrito_id ORDER BY i.nombre");
while($r = $res->fetch_assoc()) $iglesias_modal[] = $r;

$tipos_modal = [];
$res = $conn->query("SELECT id, nombre FROM tipos_inscrito ORDER BY nombre");
while($r = $res->fetch_assoc()) $tipos_modal[] = $r;

$paquetes_modal = [];
$res = $conn->query("SELECT p.id, p.nombre, ROUND(p.precio - (p.precio * COALESCE(d.porcentaje,0)/100),0) AS precio_final, p.cupos_disponibles FROM paquetes p LEFT JOIN paquete_descuentos pd ON pd.paquete_id=p.id LEFT JOIN descuentos d ON d.id=pd.descuento_id AND d.activo=1 ORDER BY p.precio");
while($r = $res->fetch_assoc()) $paquetes_modal[] = $r;

$distritos_modal = [];
$res = $conn->query("SELECT id, nombre FROM distritos ORDER BY nombre");
while($r = $res->fetch_assoc()) $distritos_modal[] = $r;
?>

<main class="content" id="main-content">
<div class="page active" id="page-dashboard">

  <div class="page-header">
    <div>
      <h1 class="page-title">Panel <span>Administrativo</span></h1>
      <p class="page-sub">Resumen logístico de la Cumbre Departamental de Jóvenes.</p>
    </div>
    <?php if($puede_gestionar): ?>
    <button class="btn-primary" id="btn-nueva-inscripcion">
      <i class="fa-solid fa-plus"></i> <span class="btn-txt-label">Nueva Inscripción</span>
    </button>
    <?php endif; ?>
  </div>

  <!-- STATS -->
  <div class="stats-grid" id="stats-grid">
    <div class="stat-card stat-blue">
      <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
      <div class="stat-body">
        <div class="stat-num" id="stat-total">—</div>
        <div class="stat-lbl">Total Inscritos</div>
        <div class="stat-sub" id="stat-hoy">Cargando...</div>
      </div>
    </div>
    <div class="stat-card stat-orange">
      <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
      <div class="stat-body">
        <div class="stat-num" id="stat-pendientes">—</div>
        <div class="stat-lbl">Pagos por Confirmar</div>
        <div class="stat-sub warn">Requiere atención</div>
      </div>
    </div>
    <div class="stat-card stat-green">
      <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
      <div class="stat-body">
        <div class="stat-num" id="stat-confirmados">—</div>
        <div class="stat-lbl">Confirmados</div>
        <div class="stat-sub" id="stat-conf-sub">pagos verificados</div>
      </div>
    </div>
    <div class="stat-card stat-purple">
      <div class="stat-icon"><i class="fa-solid fa-ticket"></i></div>
      <div class="stat-body">
        <div class="stat-num" id="stat-cupos">—</div>
        <div class="stat-lbl">Cupos Disponibles</div>
        <div class="stat-progress-wrap">
          <div class="stat-progress-bar">
            <div class="stat-progress-fill" id="stat-cupos-bar"></div>
          </div>
          <span class="stat-progress-pct" id="stat-cupos-pct">0%</span>
        </div>
      </div>
    </div>
  </div>

  <!-- TABLA -->
  <div class="card">
    <div class="card-header">
      <h3><i class="fa-solid fa-list"></i> Inscritos</h3>
      <div class="card-header-tools">
        <span id="total-lbl" class="total-lbl-txt"></span>
        <button class="btn-tool" id="btn-exportar-pdf-tabla">
          <i class="fa-solid fa-file-pdf"></i>
          <span class="btn-txt-label">Exportar PDF</span>
        </button>
      </div>
    </div>

    <!-- Filtros -->
    <div class="filtros-rapidos">
      <input type="text" id="filtro-carnet"   placeholder="Buscar nombre o carnet..." class="input-filtro">
      <select id="filtro-iglesia"  class="select-filtro">
        <option value="">Todas las iglesias</option>
        <?php foreach($iglesias_modal as $ig): ?>
          <option value="<?php echo $ig['id']; ?>"><?php echo htmlspecialchars($ig['nombre']); ?></option>
        <?php endforeach; ?>
      </select>
      <select id="filtro-distrito" class="select-filtro">
        <option value="">Todos los distritos</option>
        <?php foreach($distritos_modal as $d): ?>
          <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['nombre']); ?></option>
        <?php endforeach; ?>
      </select>
      <select id="filtro-pago"    class="select-filtro">
        <option value="">Tipo de pago</option>
        <option value="efectivo">Efectivo</option>
        <option value="qr">QR</option>
      </select>
      <select id="filtro-estado"  class="select-filtro">
        <option value="">Todos los estados</option>
        <option value="pendiente">Pendiente</option>
        <option value="confirmado">Confirmado</option>
      </select>
    </div>

    <div class="tabla-wrap">
      <table class="tabla-inscritos">
        <thead>
          <tr>
            <th>#</th>
            <th>Participante</th>
            <th>Celular</th>
            <th>Iglesia</th>
            <th>Distrito</th>
            <th>Paquete</th>
            <th>Tipo Pago</th>
            <th>Estado</th>
            <th title="¿Material/regalo entregado?">
              <i class="fa-solid fa-gift"></i> Material
            </th>
            <th>Fecha</th>
            <th>Registró</th>
            <th>Confirmó</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody id="tbody-inscritos">
          <tr>
            <td colspan="13" class="tabla-loading">
              <i class="fa-solid fa-spinner fa-spin"></i> Cargando...
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="paginacion" id="paginacion"></div>
  </div>

  <!-- DESCARGAR CREDENCIAL -->
  <div class="card" style="margin-top:20px;">
    <div class="card-header">
      <h3><i class="fa-solid fa-id-card"></i> Descargar Credencial</h3>
    </div>
    <div class="credencial-buscador">
      <div style="flex:1;min-width:200px;">
        <span class="modal-label" style="display:block;margin-bottom:6px;">
          Buscar inscrito (nombre o carnet)
        </span>
        <input type="text" id="buscar-credencial"
               class="input-filtro" placeholder="Ej: 12345678" style="width:100%;">
        <div id="credencial-resultados" style="margin-top:6px;"></div>
      </div>
      <button class="btn-primary" id="btn-descargar-credencial"
              disabled style="background:var(--green);align-self:flex-end;">
        <i class="fa-solid fa-download"></i>
        <span class="btn-txt-label"> Descargar Credencial PDF</span>
      </button>
    </div>
  </div>

</div>
</main>

<!-- ══ MODAL NUEVA INSCRIPCION EFECTIVO ══ -->
<div class="modal-overlay" id="modal-inscripcion-overlay" style="display:none;">
  <div class="modal" style="max-width:700px;">
    <div class="modal-header">
      <h3><i class="fa-solid fa-user-plus"></i> Nueva Inscripción — Pago en Efectivo</h3>
      <button class="modal-close" onclick="cerrarModalInscripcion()">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div class="modal-body" id="modal-inscripcion-body">

      <!-- PASO 1: DATOS -->
      <div id="paso-datos">
        <div class="modal-grid" style="grid-template-columns:1fr 1fr;">
          <div class="modal-field">
            <span class="modal-label">Nombre *</span>
            <input type="text" id="ni-nombre" class="input-filtro" style="width:100%;">
          </div>
          <div class="modal-field">
            <span class="modal-label">Apellido *</span>
            <input type="text" id="ni-apellido" class="input-filtro" style="width:100%;">
          </div>
          <div class="modal-field">
            <span class="modal-label">Carnet *</span>
            <input type="text" id="ni-carnet" class="input-filtro" style="width:100%;">
          </div>
          <div class="modal-field">
            <span class="modal-label">Edad *</span>
            <input type="number" id="ni-edad" class="input-filtro" min="5" max="100" style="width:100%;">
          </div>
          <div class="modal-field">
            <span class="modal-label">Celular *</span>
            <input type="text" id="ni-celular" class="input-filtro" style="width:100%;">
          </div>
          <div class="modal-field">
            <span class="modal-label">Tipo de Inscrito *</span>
            <select id="ni-tipo" class="select-filtro" style="width:100%;">
              <option value="">-- Selecciona --</option>
              <?php foreach($tipos_modal as $t): ?>
                <option value="<?php echo $t['id']; ?>">
                  <?php echo htmlspecialchars($t['nombre']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="modal-field">
            <span class="modal-label">Iglesia *</span>
            <select id="ni-iglesia" class="select-filtro" style="width:100%;">
              <option value="">-- Selecciona --</option>
              <?php foreach($iglesias_modal as $ig): ?>
                <option value="<?php echo $ig['id']; ?>"
                        data-distrito-id="<?php echo $ig['distrito_id']; ?>"
                        data-distrito="<?php echo htmlspecialchars($ig['distrito']??''); ?>">
                  <?php echo htmlspecialchars($ig['nombre']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="modal-field full">
            <span class="modal-label">Distrito (automático)</span>
            <input type="text" id="ni-distrito-nombre" class="input-filtro" readonly
                   style="width:100%;background:var(--bg);">
            <input type="hidden" id="ni-distrito-id">
          </div>

          <div class="modal-section-title" style="grid-column:span 2;">Paquete</div>
          <div class="modal-field full">
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
              <?php foreach($paquetes_modal as $paq): ?>
                <?php $agotado = $paq['cupos_disponibles'] <= 0; ?>
                <label style="display:flex;align-items:center;gap:8px;padding:10px 16px;
                              border:2px solid var(--border);border-radius:8px;
                              cursor:<?php echo $agotado?'not-allowed':'pointer'; ?>;
                              opacity:<?php echo $agotado?'.5':'1'; ?>;">
                  <input type="radio" name="ni-paquete"
                         value="<?php echo $paq['id']; ?>"
                         data-precio="<?php echo $paq['precio_final']; ?>"
                         data-nombre="<?php echo htmlspecialchars($paq['nombre']); ?>"
                         <?php echo $agotado?'disabled':''; ?>>
                  <span>
                    <strong><?php echo htmlspecialchars($paq['nombre']); ?></strong><br>
                    <small>Bs. <?php echo number_format($paq['precio_final'],2); ?>
                      — <?php echo $paq['cupos_disponibles']; ?> cupos</small>
                  </span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

        </div>
      </div><!-- /paso-datos -->

      <!-- PASO 2: RESUMEN -->
      <div id="paso-resumen" style="display:none;">
        <div id="resumen-contenido"></div>
        <div style="background:#fff3cd;border-left:4px solid #f59e0b;border-radius:6px;
                    padding:14px 16px;margin-top:16px;">
          <p style="font-size:13px;font-weight:700;color:#92400e;">
            <i class="fa-solid fa-triangle-exclamation"></i> Importante
          </p>
          <p style="font-size:13px;color:#78350f;margin-top:4px;">
            Una vez registrada la inscripción,
            <strong>debe entregar el dinero a la hermana Jael</strong>.
            La inscripción quedará <strong>PENDIENTE</strong> hasta que tesorería confirme el pago
            en <strong>Confirmar Inscripciones → Confirmar Efectivo</strong>.
            Esta inscripción no podrá ser eliminada.
          </p>
        </div>
      </div>

    </div><!-- /modal-body -->
    <div class="modal-footer">
      <button class="btn-secondary" onclick="cerrarModalInscripcion()">Cancelar</button>
      <button class="btn-secondary" id="btn-ni-volver" style="display:none;"
              onclick="volverDatos()">
        <i class="fa-solid fa-arrow-left"></i> Volver
      </button>
      <button class="btn-primary" id="btn-ni-resumen">
        <i class="fa-solid fa-eye"></i> Ver Resumen
      </button>
      <button class="btn-primary" id="btn-ni-confirmar"
              style="display:none;background:var(--green);">
        <i class="fa-solid fa-check"></i> Registrar Inscripción
      </button>
    </div>
  </div>
</div>

<!-- ══ MODAL EDITAR INSCRITO ══ -->
<div class="modal-overlay" id="modal-editar-overlay">
  <div class="modal" style="max-width:480px;">
    <div class="modal-header">
      <h3><i class="fa-solid fa-pen"></i> Editar Inscrito</h3>
      <button class="modal-close" onclick="cerrarModalEditar()">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="edit-inscrito-id">
      <div class="modal-grid" style="grid-template-columns:1fr 1fr;gap:12px;">
        <div class="modal-field">
          <span class="modal-label">Nombre</span>
          <input type="text" id="edit-nombre" class="input-filtro" style="width:100%;">
        </div>
        <div class="modal-field">
          <span class="modal-label">Apellido</span>
          <input type="text" id="edit-apellido" class="input-filtro" style="width:100%;">
        </div>
        <div class="modal-field">
          <span class="modal-label">Carnet</span>
          <input type="text" id="edit-carnet" class="input-filtro" style="width:100%;">
        </div>
        <div class="modal-field">
          <span class="modal-label">Edad</span>
          <input type="number" id="edit-edad" class="input-filtro" min="5" max="100" style="width:100%;">
        </div>
        <div class="modal-field">
          <span class="modal-label">Celular</span>
          <input type="text" id="edit-celular" class="input-filtro" style="width:100%;">
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="cerrarModalEditar()">Cancelar</button>
      <button class="btn-primary" id="btn-guardar-editar">
        <i class="fa-solid fa-floppy-disk"></i> Guardar
      </button>
    </div>
  </div>
</div>
<!-- MODAL CONFIRMAR ENTREGA -->
<div class="modal-overlay" id="modal-entrega-overlay" style="display:none;">
  <div class="modal" style="max-width:460px;">
    <div class="modal-header" style="background:#10b981;">
      <h3><i class="fa-solid fa-box-open"></i> Confirmar Entrega</h3>
      <button class="modal-close" onclick="document.getElementById('modal-entrega-overlay').style.display='none'">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div class="modal-body" style="padding:20px;">
      <div id="entrega-detalle" style="margin-bottom:16px;"></div>
      <p id="entrega-txt" style="font-size:14px;color:var(--txt);font-weight:600;text-align:center;"></p>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="document.getElementById('modal-entrega-overlay').style.display='none'">Cancelar</button>
      <button class="btn-primary" id="btn-confirmar-entrega" style="background:#10b981;">
        <i class="fa-solid fa-check"></i> Confirmar
      </button>
    </div>
  </div>
</div>
 

<!-- TOAST -->
<div class="toast" id="toast-dashboard"></div>

<script>
  var PUEDE_GESTIONAR = <?php echo $puede_gestionar ? 'true' : 'false'; ?>;
  var PUEDE_ENTREGAR  = <?php echo $puede_entregar  ? 'true' : 'false'; ?>;
  var ES_ADMIN        = <?php echo puede(['Administrador']) ? 'true' : 'false'; ?>;
</script>
<?php $js_pagina = ['admin-encuentro.js']; ?>
<?php include_once 'templates/footer.php'; ?>
