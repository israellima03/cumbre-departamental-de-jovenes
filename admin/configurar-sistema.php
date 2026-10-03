<?php
require_once('funciones/sesiones.php');
usuario_autentificado();
verificar_acceso(['Administrador','Lider departamental']);

header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

require_once('../includes/funciones/bd_conexion.php');
include_once 'templates/header.php';
include_once 'templates/navegacion.php';
include_once 'templates/barra.php';
include_once 'funciones/funciones.php';

/* cargar config */
$cfg = [];
$res = $conn->query("SELECT clave, valor FROM config_sitio");
if($res) while($r = $res->fetch_assoc()) $cfg[$r['clave']] = $r['valor'];

/* cargar noticias */
$noticias = [];
$res = $conn->query("SELECT * FROM noticias_footer ORDER BY orden ASC");
if($res) while($r = $res->fetch_assoc()) $noticias[] = $r;

/* cargar redes */
$redes = [];
$res = $conn->query("SELECT * FROM redes_sociales ORDER BY orden ASC");
if($res) while($r = $res->fetch_assoc()) $redes[] = $r;

/* cargar ubicaciones */
$ubicaciones = [];
$res = $conn->query("SELECT * FROM ubicaciones ORDER BY orden ASC");
if($res) while($r = $res->fetch_assoc()) $ubicaciones[] = $r;
?>

<main class="content" id="main-content">

  <div class="page-header">
    <div>
      <h1 class="page-title">Configurar <span>Sistema</span></h1>
      <p class="page-sub">Edita la información del sitio público, noticias, ubicaciones y redes sociales.</p>
    </div>
  </div>

  <!-- TABS -->
  <div class="prog-tabs">
    <button class="prog-tab active" data-tab="evento"><i class="fa-solid fa-dove"></i> <span class="tab-lbl">Evento</span></button>
    <button class="prog-tab" data-tab="footer"><i class="fa-solid fa-align-left"></i> <span class="tab-lbl">Footer</span></button>
    <button class="prog-tab" data-tab="noticias"><i class="fa-solid fa-newspaper"></i> <span class="tab-lbl">Noticias</span></button>
    <button class="prog-tab" data-tab="redes"><i class="fa-solid fa-share-nodes"></i> <span class="tab-lbl">Redes</span></button>
    <button class="prog-tab" data-tab="ubicaciones"><i class="fa-solid fa-map-location-dot"></i> <span class="tab-lbl">Ubicaciones</span></button>
  </div>

  <!-- ══ TAB EVENTO ══ -->
  <div class="prog-panel active" id="panel-evento">
    <div class="card">
      <div class="card-header">
        <h3><i class="fa-solid fa-dove"></i> Información del Evento</h3>
      </div>
      <div class="cfg-form-wrap">
        <div class="cfg-form">
          <div class="modal-field">
            <span class="modal-label">Nombre del Evento</span>
            <input type="text" id="cfg-evento-nombre" class="input-filtro" style="width:100%;"
                   value="<?php echo htmlspecialchars($cfg['evento_nombre']??''); ?>"
                   placeholder="Cumbre Departamental de Jóvenes">
          </div>
          <div class="modal-field">
            <span class="modal-label">Lema / Eslogan</span>
            <input type="text" id="cfg-evento-lema" class="input-filtro" style="width:100%;"
                   value="<?php echo htmlspecialchars($cfg['evento_lema']??''); ?>"
                   placeholder="Llamados a testificar">
          </div>
          <div class="modal-field">
            <span class="modal-label">Versículo</span>
            <input type="text" id="cfg-evento-versiculo" class="input-filtro" style="width:100%;"
                   value="<?php echo htmlspecialchars($cfg['evento_versiculo']??''); ?>"
                   placeholder="1 Samuel 16:7">
          </div>
          <div class="cfg-grid-2">
            <div class="modal-field">
              <span class="modal-label">Fecha del Evento</span>
              <input type="date" id="cfg-evento-fecha" class="input-filtro" style="width:100%;"
                     value="<?php echo htmlspecialchars($cfg['evento_fecha']??''); ?>">
            </div>
            <div class="modal-field">
              <span class="modal-label">Ciudad</span>
              <input type="text" id="cfg-evento-ciudad" class="input-filtro" style="width:100%;"
                     value="<?php echo htmlspecialchars($cfg['evento_ciudad']??'Oruro, Bolivia'); ?>"
                     placeholder="Oruro, Bolivia">
            </div>
          </div>
          <button class="btn-primary cfg-btn-guardar" onclick="guardarConfig('evento')">
            <i class="fa-solid fa-floppy-disk"></i> Guardar Cambios
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ══ TAB FOOTER ══ -->
  <div class="prog-panel" id="panel-footer">
    <div class="card">
      <div class="card-header">
        <h3><i class="fa-solid fa-align-left"></i> Contenido del Footer</h3>
      </div>
      <div class="cfg-form-wrap">
        <div class="cfg-form">
          <div class="modal-field">
            <span class="modal-label">Título "Sobre Nosotros"</span>
            <input type="text" id="cfg-footer-titulo" class="input-filtro" style="width:100%;"
                   value="<?php echo htmlspecialchars($cfg['footer_titulo']??''); ?>"
                   placeholder="Sobre Mins De Jóvenes Oruro">
          </div>
          <div class="modal-field">
            <span class="modal-label">Descripción</span>
            <textarea id="cfg-footer-descripcion" class="input-filtro cfg-textarea"
                      placeholder="Descripción del ministerio..."><?php echo htmlspecialchars($cfg['footer_descripcion']??''); ?></textarea>
          </div>
          <button class="btn-primary cfg-btn-guardar" onclick="guardarConfig('footer')">
            <i class="fa-solid fa-floppy-disk"></i> Guardar Cambios
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ══ TAB NOTICIAS ══ -->
  <div class="prog-panel" id="panel-noticias">
    <div class="card">
      <div class="card-header">
        <h3><i class="fa-solid fa-newspaper"></i> Últimas Noticias</h3>
        <button class="btn-primary" onclick="abrirModalNoticia()">
          <i class="fa-solid fa-plus"></i> <span class="btn-txt-label">Nueva Noticia</span>
        </button>
      </div>
      <div class="tabla-wrap" style="overflow-x:auto;">
        <table class="tabla-inscritos">
          <thead>
            <tr><th>#</th><th>Texto</th><th>Orden</th><th>Acciones</th></tr>
          </thead>
          <tbody id="tbody-noticias">
            <?php foreach($noticias as $i => $n): ?>
            <tr id="fila-noticia-<?php echo $n['id']; ?>">
              <td><?php echo $i+1; ?></td>
              <td style="white-space:normal;font-size:13px;"><?php echo htmlspecialchars($n['texto']); ?></td>
              <td><?php echo $n['orden']; ?></td>
              <td style="white-space:nowrap;">
                <button class="btn-accion btn-ver"
                        onclick="abrirModalNoticia(<?php echo htmlspecialchars(json_encode($n)); ?>)"
                        title="Editar"><i class="fa-solid fa-pen"></i></button>
                <button class="btn-accion" style="color:#dc2626;"
                        onclick="eliminarElemento('eliminar_noticia',<?php echo $n['id']; ?>,'¿Eliminar esta noticia?',recargarNoticias)"
                        title="Eliminar"><i class="fa-solid fa-trash"></i></button>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($noticias)): ?>
            <tr><td colspan="4" class="tabla-vacia">Sin noticias</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ══ TAB REDES ══ -->
  <div class="prog-panel" id="panel-redes">
    <div class="card">
      <div class="card-header">
        <h3><i class="fa-solid fa-share-nodes"></i> Redes Sociales</h3>
        <button class="btn-primary" onclick="abrirModalRed()">
          <i class="fa-solid fa-plus"></i> <span class="btn-txt-label">Nueva Red</span>
        </button>
      </div>
      <div class="tabla-wrap" style="overflow-x:auto;">
        <table class="tabla-inscritos">
          <thead>
            <tr><th>#</th><th>Red</th><th class="col-url-red">URL</th><th>Estado</th><th>Acciones</th></tr>
          </thead>
          <tbody id="tbody-redes">
            <?php foreach($redes as $i => $r): ?>
            <tr id="fila-red-<?php echo $r['id']; ?>">
              <td><?php echo $i+1; ?></td>
              <td>
                <i class="<?php echo htmlspecialchars($r['icono']); ?>" style="margin-right:6px;font-size:1.1em;"></i>
                <?php echo htmlspecialchars($r['nombre']); ?>
              </td>
              <td class="col-url-red" style="font-size:12px;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                <a href="<?php echo htmlspecialchars($r['url']); ?>" target="_blank" style="color:var(--blue);">
                  <?php echo htmlspecialchars($r['url']); ?>
                </a>
              </td>
              <td>
                <?php echo $r['activo'] ? '<span class="badge badge-confirmado">Activa</span>' : '<span class="badge badge-pendiente">Inactiva</span>'; ?>
              </td>
              <td style="white-space:nowrap;">
                <button class="btn-accion btn-ver"
                        onclick="abrirModalRed(<?php echo htmlspecialchars(json_encode($r)); ?>)"
                        title="Editar"><i class="fa-solid fa-pen"></i></button>
                <button class="btn-accion" style="color:#dc2626;"
                        onclick="eliminarElemento('eliminar_red',<?php echo $r['id']; ?>,'¿Eliminar esta red social?',recargarRedes)"
                        title="Eliminar"><i class="fa-solid fa-trash"></i></button>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($redes)): ?>
            <tr><td colspan="5" class="tabla-vacia">Sin redes registradas</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ══ TAB UBICACIONES ══ -->
  <div class="prog-panel" id="panel-ubicaciones">
    <div class="card">
      <div class="card-header">
        <h3><i class="fa-solid fa-map-location-dot"></i> Ubicaciones</h3>
        <button class="btn-primary" onclick="abrirModalUbicacion()">
          <i class="fa-solid fa-plus"></i> <span class="btn-txt-label">Nueva Ubicación</span>
        </button>
      </div>
      <div class="tabla-wrap" style="overflow-x:auto;">
        <table class="tabla-inscritos">
          <thead>
            <tr><th>#</th><th>Nombre</th><th>Tipo</th><th class="col-url-red">Link Maps</th><th>Orden</th><th>Estado</th><th>Acciones</th></tr>
          </thead>
          <tbody id="tbody-ubicaciones">
            <?php foreach($ubicaciones as $i => $u): ?>
            <tr id="fila-ubic-<?php echo $u['id']; ?>">
              <td><?php echo $i+1; ?></td>
              <td style="font-weight:600;"><?php echo htmlspecialchars($u['nombre']); ?></td>
              <td>
                <span class="badge-tipo tipo-<?php echo $u['tipo']==='evento'?'conferencia':'actividad'; ?>">
                  <?php echo htmlspecialchars($u['tipo']); ?>
                </span>
              </td>
              <td class="col-url-red" style="font-size:12px;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                <a href="<?php echo htmlspecialchars($u['link_maps']); ?>" target="_blank" style="color:var(--blue);">
                  <i class="fa-solid fa-map-pin"></i> Ver en Maps
                </a>
              </td>
              <td><?php echo $u['orden']; ?></td>
              <td>
                <?php echo $u['activo'] ? '<span class="badge badge-confirmado">Activa</span>' : '<span class="badge badge-pendiente">Inactiva</span>'; ?>
              </td>
              <td style="white-space:nowrap;">
                <button class="btn-accion btn-ver"
                        onclick="abrirModalUbicacion(<?php echo htmlspecialchars(json_encode($u)); ?>)"
                        title="Editar"><i class="fa-solid fa-pen"></i></button>
                <button class="btn-accion" style="color:#dc2626;"
                        onclick="eliminarElemento('eliminar_ubicacion',<?php echo $u['id']; ?>,'¿Eliminar esta ubicación?',recargarUbicaciones)"
                        title="Eliminar"><i class="fa-solid fa-trash"></i></button>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($ubicaciones)): ?>
            <tr><td colspan="7" class="tabla-vacia">Sin ubicaciones</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</main>

<!-- MODAL NOTICIA -->
<div class="modal-overlay" id="modal-noticia" style="display:none;">
  <div class="modal" style="max-width:460px;">
    <div class="modal-header">
      <h3 id="modal-noticia-titulo"><i class="fa-solid fa-newspaper"></i> Noticia</h3>
      <button class="modal-close" onclick="cerrarModalSis('modal-noticia')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="not-id">
      <div style="display:flex;flex-direction:column;gap:12px;">
        <div class="modal-field">
          <span class="modal-label">Texto *</span>
          <textarea id="not-texto" class="input-filtro cfg-textarea"
                    placeholder="Ej: Que llevar, horarios actualizados..."></textarea>
        </div>
        <div class="modal-field">
          <span class="modal-label">Orden</span>
          <input type="number" id="not-orden" class="input-filtro" style="width:100%;" value="0" min="0">
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="cerrarModalSis('modal-noticia')">Cancelar</button>
      <button class="btn-primary" id="btn-guardar-noticia"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
    </div>
  </div>
</div>

<!-- MODAL RED SOCIAL -->
<div class="modal-overlay" id="modal-red" style="display:none;">
  <div class="modal" style="max-width:480px;">
    <div class="modal-header">
      <h3 id="modal-red-titulo"><i class="fa-solid fa-share-nodes"></i> Red Social</h3>
      <button class="modal-close" onclick="cerrarModalSis('modal-red')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="red-id">
      <div style="display:flex;flex-direction:column;gap:12px;">
        <div class="modal-field">
          <span class="modal-label">Nombre *</span>
          <input type="text" id="red-nombre" class="input-filtro" style="width:100%;" placeholder="Facebook">
        </div>
        <div class="modal-field">
          <span class="modal-label">Ícono Font Awesome *</span>
          <input type="text" id="red-icono" class="input-filtro" style="width:100%;"
                 placeholder="fa-brands fa-facebook-f" oninput="previewIcono(this.value)">
          <div style="margin-top:6px;font-size:12px;color:var(--txt-soft);">
            Preview: <i id="preview-icono" style="font-size:1.4em;margin-left:6px;"></i>
          </div>
        </div>
        <div class="modal-field">
          <span class="modal-label">URL *</span>
          <input type="text" id="red-url" class="input-filtro" style="width:100%;" placeholder="https://...">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="modal-field">
            <span class="modal-label">Orden</span>
            <input type="number" id="red-orden" class="input-filtro" style="width:100%;" value="0" min="0">
          </div>
          <div class="modal-field">
            <span class="modal-label">Estado</span>
            <select id="red-activo" class="select-filtro" style="width:100%;">
              <option value="1">Activa</option>
              <option value="0">Inactiva</option>
            </select>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="cerrarModalSis('modal-red')">Cancelar</button>
      <button class="btn-primary" id="btn-guardar-red"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
    </div>
  </div>
</div>

<!-- MODAL UBICACION -->
<div class="modal-overlay" id="modal-ubicacion" style="display:none;">
  <div class="modal" style="max-width:520px;">
    <div class="modal-header">
      <h3 id="modal-ubicacion-titulo"><i class="fa-solid fa-map-location-dot"></i> Ubicación</h3>
      <button class="modal-close" onclick="cerrarModalSis('modal-ubicacion')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="ubic-id">
      <div style="display:flex;flex-direction:column;gap:12px;">
        <div class="modal-field">
          <span class="modal-label">Nombre del lugar *</span>
          <input type="text" id="ubic-nombre" class="input-filtro" style="width:100%;"
                 placeholder="Ej: Lugar del Evento, Alojamiento 1">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="modal-field">
            <span class="modal-label">Tipo</span>
            <select id="ubic-tipo" class="select-filtro" style="width:100%;">
              <option value="evento">Evento</option>
              <option value="alojamiento">Alojamiento</option>
              <option value="otro">Otro</option>
            </select>
          </div>
          <div class="modal-field">
            <span class="modal-label">Orden</span>
            <input type="number" id="ubic-orden" class="input-filtro" style="width:100%;" value="0" min="0">
          </div>
        </div>
        <div class="modal-field">
          <span class="modal-label">Link de Google Maps *</span>
          <input type="text" id="ubic-link" class="input-filtro" style="width:100%;"
                 placeholder="https://maps.google.com/?q=...">
          <p style="font-size:11px;color:var(--txt-xsoft);margin-top:4px;">
            <i class="fa-solid fa-circle-info"></i>
            Ve a Google Maps, busca el lugar, haz clic en "Compartir" y copia el link.
          </p>
        </div>
        <div class="modal-field">
          <span class="modal-label">Embed URL del iframe (opcional)</span>
          <input type="text" id="ubic-embed" class="input-filtro" style="width:100%;"
                 placeholder="https://www.google.com/maps/embed?pb=...">
          <p style="font-size:11px;color:var(--txt-xsoft);margin-top:4px;">
            <i class="fa-solid fa-circle-info"></i>
            En Google Maps: Compartir → Insertar mapa → copia solo la URL del src.
          </p>
        </div>
        <div class="modal-field">
          <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:13px;">
            <input type="checkbox" id="ubic-activo" style="width:16px;height:16px;" checked>
            <span>Ubicación activa (visible en el sitio)</span>
          </label>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="cerrarModalSis('modal-ubicacion')">Cancelar</button>
      <button class="btn-primary" id="btn-guardar-ubicacion"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
    </div>
  </div>
</div>

<!-- MODAL CONFIRMAR ELIMINAR -->
<div class="modal-overlay" id="modal-eliminar-cfg" style="display:none;">
  <div class="modal" style="max-width:360px;">
    <div class="modal-header" style="background:#dc2626;">
      <h3><i class="fa-solid fa-trash"></i> Confirmar</h3>
      <button class="modal-close" onclick="cerrarModalSis('modal-eliminar-cfg')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body" style="text-align:center;padding:28px 20px;">
      <i class="fa-solid fa-triangle-exclamation" style="font-size:2.5em;color:#dc2626;margin-bottom:14px;display:block;"></i>
      <p id="eliminar-cfg-txt" style="font-size:14px;font-weight:600;color:var(--txt);">¿Eliminar este elemento?</p>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="cerrarModalSis('modal-eliminar-cfg')">Cancelar</button>
      <button class="btn-primary" id="btn-confirmar-eliminar-cfg" style="background:#dc2626;">
        <i class="fa-solid fa-trash"></i> Eliminar
      </button>
    </div>
  </div>
</div>

<div class="toast" id="toast-sistema"></div>

<style>
/* ── TABS ── */
.prog-tabs {
  display:flex; gap:4px; margin-bottom:20px;
  background:var(--card-bg); border:1px solid var(--border);
  border-radius:var(--radius); padding:6px; flex-wrap:wrap;
  box-shadow:var(--shadow);
}
.prog-tab {
  display:flex; align-items:center; gap:7px;
  padding:9px 16px; border:none; border-radius:8px;
  background:transparent; color:var(--txt-soft);
  font-family:var(--font-body); font-size:13px; font-weight:600;
  cursor:pointer; transition:all .2s; white-space:nowrap;
}
.prog-tab:hover { background:var(--bg); color:var(--txt); }
.prog-tab.active { background:var(--sidebar-bg); color:#fff; }
.prog-panel { display:none; }
.prog-panel.active { display:block; }

/* ── FORM ── */
.cfg-form-wrap { padding:24px; }
.cfg-form { max-width:580px; display:flex; flex-direction:column; gap:16px; }
.cfg-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.cfg-textarea { width:100%; min-height:90px; resize:vertical; padding:10px; font-family:var(--font-body); }
.cfg-btn-guardar { align-self:flex-start; }

/* ── TABLA ── */
.badge-tipo { display:inline-block; padding:2px 8px; border-radius:20px; font-size:10px; font-weight:700; text-transform:uppercase; }
.tipo-conferencia { background:#E7E2DF;color:#652C2A; }
.tipo-actividad   { background:#d1fae5;color:#065f46; }

/* ── RESPONSIVE ── */
@media (max-width:768px) {
  .prog-tabs { overflow-x:auto; flex-wrap:nowrap; padding:4px; }
  .prog-tab  { flex-shrink:0; padding:8px 12px; font-size:12px; }
  .cfg-form-wrap { padding:16px; }
  .cfg-form { max-width:100%; }
}

@media (max-width:480px) {
  .prog-tab .tab-lbl { display:none; }
  .prog-tab { padding:10px; }
  .cfg-grid-2 { grid-template-columns:1fr; }
  .modal-body div[style*="grid-template-columns:1fr 1fr"] {
    display:flex !important; flex-direction:column !important;
  }
  .page-header { flex-direction:column; align-items:flex-start; }
  .cfg-btn-guardar { width:100%; justify-content:center; }
}

/* ── TABS ── */
.prog-tabs {
  display:flex; gap:4px; margin-bottom:20px;
  background:var(--card-bg); border:1px solid var(--border);
  border-radius:var(--radius); padding:6px; flex-wrap:wrap;
  box-shadow:var(--shadow);
}
.prog-tab {
  display:flex; align-items:center; gap:7px;
  padding:9px 16px; border:none; border-radius:8px;
  background:transparent; color:var(--txt-soft);
  font-family:var(--font-body); font-size:13px; font-weight:600;
  cursor:pointer; transition:all .2s; white-space:nowrap;
}
.prog-tab:hover { background:var(--bg); color:var(--txt); }
.prog-tab.active { background:var(--sidebar-bg); color:#fff; }
.prog-panel { display:none; }
.prog-panel.active { display:block; }

/* ── FORM ── */
.cfg-form-wrap { padding:24px; }
.cfg-form { max-width:580px; display:flex; flex-direction:column; gap:16px; }
.cfg-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.cfg-textarea { width:100%; min-height:90px; resize:vertical; padding:10px; font-family:var(--font-body); }
.cfg-btn-guardar { align-self:flex-start; }

/* ── BADGE TIPO ── */
.badge-tipo { display:inline-block; padding:2px 8px; border-radius:20px; font-size:10px; font-weight:700; text-transform:uppercase; }
.tipo-conferencia { background:#E7E2DF;color:#652C2A; }
.tipo-actividad   { background:#d1fae5;color:#065f46; }

/* ══════════════════════════════════════
   RESPONSIVE — Las acciones SIEMPRE visibles
   Ocultar solo columnas de contenido secundario
══════════════════════════════════════ */
@media (max-width: 768px) {
  /* tabs scroll horizontal */
  .prog-tabs { overflow-x:auto; flex-wrap:nowrap; padding:4px; }
  .prog-tab  { flex-shrink:0; padding:8px 12px; font-size:12px; }

  /* form */
  .cfg-form-wrap { padding:16px; }
  .cfg-form { max-width:100%; }
  .cfg-btn-guardar { width:100%; justify-content:center; }

  /* header de página */
  .page-header { flex-direction:column; align-items:flex-start; }
}
@media (max-width: 480px) {
  /* tabs solo ícono */
  .prog-tab .tab-lbl { display:none; }
  .prog-tab { padding:10px; }

  /* grids de modales en 1 columna */
  .cfg-grid-2 { grid-template-columns:1fr; }

  /* scroll horizontal en tablas — acciones siempre accesibles */
  .tabla-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  /* ancho mínimo para que no colapsen las columnas */
  #panel-noticias .tabla-inscritos    { min-width: 320px; }
  #panel-redes .tabla-inscritos       { min-width: 380px; }
  #panel-ubicaciones .tabla-inscritos { min-width: 420px; }

  /* compactar celdas */
  .tabla-inscritos tbody td,
  .tabla-inscritos thead th {
    padding: 8px 8px;
    font-size: 12px;
  }

  /* columna acciones siempre visible y fija a la derecha */
  .tabla-inscritos thead th:last-child,
  .tabla-inscritos tbody td:last-child {
    position: sticky;
    right: 0;
    background: var(--card-bg);
    box-shadow: -2px 0 6px rgba(0,0,0,.08);
    white-space: nowrap;
    z-index: 1;
  }
  .tabla-inscritos thead th:last-child {
    background: var(--sidebar-bg);
  }
}

</style>

<?php $js_pagina = ['configurar-sistema.js']; ?>
<?php include_once 'templates/footer.php'; ?>