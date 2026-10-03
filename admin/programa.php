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

$puede_editar = puede(['Administrador','Lider departamental']);
?>

<main class="content" id="main-content">

  <div class="page-header">
    <div>
      <h1 class="page-title">Programa <span>del Evento</span></h1>
      <p class="page-sub">Gestión de expositores, temas, eventos, material y moderadores.</p>
    </div>
  </div>

  <!-- TABS -->
  <div class="prog-tabs">
    <button class="prog-tab active" data-tab="expositores"><i class="fa-solid fa-microphone-lines"></i> Expositores</button>
    <button class="prog-tab" data-tab="temas"><i class="fa-solid fa-book-open"></i> Temas</button>
    <button class="prog-tab" data-tab="eventos"><i class="fa-solid fa-calendar-days"></i> Eventos</button>
    <button class="prog-tab" data-tab="material"><i class="fa-solid fa-folder-open"></i> Material</button>
    <button class="prog-tab" data-tab="moderadores"><i class="fa-solid fa-person-chalkboard"></i> Moderadores</button>
  </div>

  <!-- ══ EXPOSITORES ══ -->
  <div class="prog-panel active" id="panel-expositores">
    <div class="card">
      <div class="card-header">
        <h3><i class="fa-solid fa-microphone-lines"></i> Expositores</h3>
        <?php if($puede_editar): ?>
        <button class="btn-primary" onclick="abrirModalExpositor()">
          <i class="fa-solid fa-plus"></i> Nuevo Expositor
        </button>
        <?php endif; ?>
      </div>
      <div class="tabla-wrap">
        <table class="tabla-inscritos">
          <thead>
            <tr>
              <th>#</th><th>Foto</th><th>Nombre</th><th>Rango</th><th>Descripción</th>
              <?php if($puede_editar): ?><th>Acciones</th><?php endif; ?>
            </tr>
          </thead>
          <tbody id="tbody-expositores">
            <tr><td colspan="6" class="tabla-loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ══ TEMAS ══ -->
  <div class="prog-panel" id="panel-temas">
    <div class="card">
      <div class="card-header">
        <h3><i class="fa-solid fa-book-open"></i> Temas</h3>
        <?php if($puede_editar): ?>
        <button class="btn-primary" onclick="abrirModalTema()">
          <i class="fa-solid fa-plus"></i> Nuevo Tema
        </button>
        <?php endif; ?>
      </div>
      <div class="tabla-wrap">
        <table class="tabla-inscritos">
          <thead>
            <tr>
              <th>#</th><th>Título</th>
              <?php if($puede_editar): ?><th>Acciones</th><?php endif; ?>
            </tr>
          </thead>
          <tbody id="tbody-temas">
            <tr><td colspan="3" class="tabla-loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ══ EVENTOS ══ -->
  <div class="prog-panel" id="panel-eventos">
    <div class="card">
      <div class="card-header">
        <h3><i class="fa-solid fa-calendar-days"></i> Eventos</h3>
        <?php if($puede_editar): ?>
        <button class="btn-primary" onclick="abrirModalEvento()">
          <i class="fa-solid fa-plus"></i> Nuevo Evento
        </button>
        <?php endif; ?>
      </div>
      <div class="filtros-rapidos">
        <select id="filtro-dia-evento" class="select-filtro" onchange="cargarEventos()">
          <option value="">Todos los días</option>
        </select>
        <select id="filtro-tipo-evento" class="select-filtro" onchange="cargarEventos()">
          <option value="">Todos los tipos</option>
          <option value="conferencia">Conferencia</option>
          <option value="predica">Predica</option>
          <option value="concurso">Concurso</option>
          <option value="deportivo">Deportivo</option>
          <option value="premiacion">Premiacion</option>
          <option value="paseo">Paseo(Tours)</option>
          <option value="otro">Otro</option>
        </select>
      </div>
      <div class="tabla-wrap">
        <table class="tabla-inscritos">
          <thead>
            <tr>
              <th>#</th><th>Día</th><th>Fecha</th><th>Hora</th><th>Tipo</th>
              <th>Expositor</th><th>Tema</th><th>Moderador</th><th>Grupo</th><th>Preguntas</th>
              <?php if($puede_editar): ?><th>Acciones</th><?php endif; ?>
            </tr>
          </thead>
          <tbody id="tbody-eventos">
            <tr><td colspan="11" class="tabla-loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ══ MATERIAL ══ -->
  <div class="prog-panel" id="panel-material">
    <div class="card">
      <div class="card-header">
        <h3><i class="fa-solid fa-folder-open"></i> Material</h3>
        <?php if($puede_editar): ?>
        <button class="btn-primary" onclick="abrirModalMaterial()">
          <i class="fa-solid fa-plus"></i> Subir Material
        </button>
        <?php endif; ?>
      </div>
      <div class="tabla-wrap">
        <table class="tabla-inscritos">
          <thead>
            <tr>
              <th>#</th><th>Nombre</th><th>Evento</th><th>Tipo</th><th>Descarga</th>
              <?php if($puede_editar): ?><th>Acciones</th><?php endif; ?>
            </tr>
          </thead>
          <tbody id="tbody-material">
            <tr><td colspan="6" class="tabla-loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <<!-- ══ MODERADORES Y GRUPOS ══ -->
  <div class="prog-panel" id="panel-moderadores">

    <!-- MODERADORES -->
    <div class="card" style="margin-bottom:16px;">
      <div class="card-header">
        <h3><i class="fa-solid fa-person-chalkboard"></i> Moderadores</h3>
        <?php if($puede_editar): ?>
        <button class="btn-primary" onclick="abrirModalModerador()">
          <i class="fa-solid fa-plus"></i> Nuevo Moderador
        </button>
        <?php endif; ?>
      </div>
      <div class="tabla-wrap">
        <table class="tabla-inscritos">
          <thead>
            <tr><th>#</th><th>Nombre</th><th>Apellido</th>
            <?php if($puede_editar): ?><th>Acciones</th><?php endif; ?></tr>
          </thead>
          <tbody id="tbody-moderadores">
            <tr><td colspan="4" class="tabla-loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- GRUPOS DE ALABANZA -->
    <div class="card">
      <div class="card-header">
        <h3><i class="fa-solid fa-music"></i> Grupos de Alabanza</h3>
        <?php if($puede_editar): ?>
        <button class="btn-primary" onclick="abrirModalGrupo()">
          <i class="fa-solid fa-plus"></i> Nuevo Grupo
        </button>
        <?php endif; ?>
      </div>
      <div class="tabla-wrap">
        <table class="tabla-inscritos">
          <thead>
            <tr><th>#</th><th>Nombre del Grupo</th>
            <?php if($puede_editar): ?><th>Acciones</th><?php endif; ?></tr>
          </thead>
          <tbody id="tbody-grupos">
            <tr><td colspan="3" class="tabla-loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</td></tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>

</main>

<?php if($puede_editar): ?>

<!-- ══ MODAL EXPOSITOR ══ -->
<div class="modal-overlay" id="modal-expositor" style="display:none;">
  <div class="modal" style="max-width:520px;">
    <div class="modal-header">
      <h3 id="modal-expositor-titulo"><i class="fa-solid fa-microphone-lines"></i> Expositor</h3>
      <button class="modal-close" onclick="cerrarModal('modal-expositor')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="exp-id">
      <input type="hidden" id="exp-imagen-actual">
      <div style="display:flex;flex-direction:column;gap:12px;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="modal-field">
            <span class="modal-label">Nombre *</span>
            <input type="text" id="exp-nombre" class="input-filtro" style="width:100%;">
          </div>
          <div class="modal-field">
            <span class="modal-label">Apellido *</span>
            <input type="text" id="exp-apellido" class="input-filtro" style="width:100%;">
          </div>
        </div>
        <div class="modal-field">
          <span class="modal-label">Rango *</span>
          <input type="text" id="exp-rango" class="input-filtro" style="width:100%;" placeholder="Ej: Pastor, Evangelista, Dr.">
        </div>
        <div class="modal-field">
          <span class="modal-label">Descripción</span>
          <textarea id="exp-descripcion" class="input-filtro" style="width:100%;min-height:72px;resize:vertical;"></textarea>
        </div>
        <!-- SUBIDA DE IMAGEN -->
        <div class="modal-field">
          <span class="modal-label">Foto del Expositor</span>
          <div class="upload-area" id="exp-upload-area">
            <div id="exp-preview-wrap" style="display:none;text-align:center;margin-bottom:8px;">
              <img id="exp-preview-img" src="" alt="preview"
                   style="max-width:100px;max-height:100px;border-radius:50%;object-fit:cover;border:2px solid var(--border);">
              <p id="exp-preview-nombre" style="font-size:11px;color:var(--txt-soft);margin-top:4px;"></p>
            </div>
            <label class="upload-btn" for="exp-imagen-file">
              <i class="fa-solid fa-image"></i>
              <span id="exp-upload-lbl">Seleccionar foto (JPG, PNG, WEBP)</span>
            </label>
            <input type="file" id="exp-imagen-file" accept="image/jpeg,image/png,image/webp,image/gif"
                   style="display:none;" onchange="previewImagen(this,'exp-preview-img','exp-preview-wrap','exp-preview-nombre','exp-upload-lbl')">
          </div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="cerrarModal('modal-expositor')">Cancelar</button>
      <button class="btn-primary" id="btn-guardar-expositor"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
    </div>
  </div>
</div>

<!-- ══ MODAL TEMA ══ -->
<div class="modal-overlay" id="modal-tema" style="display:none;">
  <div class="modal" style="max-width:440px;">
    <div class="modal-header">
      <h3 id="modal-tema-titulo"><i class="fa-solid fa-book-open"></i> Tema</h3>
      <button class="modal-close" onclick="cerrarModal('modal-tema')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="tema-id">
      <div class="modal-field">
        <span class="modal-label">Título del Tema *</span>
        <input type="text" id="tema-titulo" class="input-filtro" style="width:100%;" placeholder="Ej: La fe que mueve montañas">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="cerrarModal('modal-tema')">Cancelar</button>
      <button class="btn-primary" id="btn-guardar-tema"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
    </div>
  </div>
</div>

<!-- ══ MODAL EVENTO ══ -->
<div class="modal-overlay" id="modal-evento" style="display:none;">
  <div class="modal" style="max-width:620px;">
    <div class="modal-header">
      <h3 id="modal-evento-titulo"><i class="fa-solid fa-calendar-days"></i> Evento</h3>
      <button class="modal-close" onclick="cerrarModal('modal-evento')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="evento-id">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div class="modal-field">
          <span class="modal-label">Día *</span>
          <select id="evento-dia" class="select-filtro" style="width:100%;"></select>
        </div>
        <div class="modal-field">
          <span class="modal-label">Fecha *</span>
          <input type="date" id="evento-fecha" class="input-filtro" style="width:100%;">
        </div>
        <div class="modal-field">
          <span class="modal-label">Hora inicio *</span>
          <input type="time" id="evento-hora-inicio" class="input-filtro" style="width:100%;">
        </div>
        <div class="modal-field">
          <span class="modal-label">Hora fin *</span>
          <input type="time" id="evento-hora-fin" class="input-filtro" style="width:100%;">
        </div>
        <div class="modal-field" style="grid-column:span 2;">
          <span class="modal-label">Tipo de Evento *</span>
          <select id="evento-tipo" class="select-filtro" style="width:100%;" onchange="toggleCamposEvento()">
            <option value="">Todos los tipos</option>
            <option value="conferencia">Conferencia</option>
            <option value="predica">Predica</option>
            <option value="concurso">Concurso</option>
            <option value="deportivo">Deportivo</option>
            <option value="premiacion">Premiacion</option>
            <option value="paseo">Paseo(Tours)</option>
            <option value="otro">Otro</option>
          </select>
        </div>
        <div class="modal-field" id="campo-expositor">
          <span class="modal-label">Expositor</span>
          <select id="evento-expositor" class="select-filtro" style="width:100%;"></select>
        </div>
        <div class="modal-field" id="campo-tema">
          <span class="modal-label">Tema</span>
          <select id="evento-tema" class="select-filtro" style="width:100%;"></select>
        </div>
        <div class="modal-field" id="campo-moderador">
          <span class="modal-label">Moderador</span>
          <select id="evento-moderador" class="select-filtro" style="width:100%;"></select>
        </div>
        <div class="modal-field" id="campo-grupo">
          <span class="modal-label">Grupo de Alabanza</span>
          <select id="evento-grupo" class="select-filtro" style="width:100%;"></select>
        </div>
        <div class="modal-field" style="grid-column:span 2;">
          <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:13px;">
            <input type="checkbox" id="evento-preguntas" style="width:16px;height:16px;">
            <span>Habilitar preguntas del público para este evento</span>
          </label>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="cerrarModal('modal-evento')">Cancelar</button>
      <button class="btn-primary" id="btn-guardar-evento"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
    </div>
  </div>
</div>

<!-- ══ MODAL MATERIAL ══ -->
<div class="modal-overlay" id="modal-material" style="display:none;">
  <div class="modal" style="max-width:500px;">
    <div class="modal-header">
      <h3 id="modal-material-titulo"><i class="fa-solid fa-folder-open"></i> Material</h3>
      <button class="modal-close" onclick="cerrarModal('modal-material')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="mat-id">
      <input type="hidden" id="mat-url-actual">
      <div style="display:flex;flex-direction:column;gap:12px;">
        <div class="modal-field">
          <span class="modal-label">Nombre *</span>
          <input type="text" id="mat-nombre" class="input-filtro" style="width:100%;" placeholder="Ej: Presentación Día 1">
        </div>
        <div class="modal-field">
          <span class="modal-label">Evento *</span>
          <select id="mat-evento" class="select-filtro" style="width:100%;"></select>
        </div>
        <div class="modal-field">
          <span class="modal-label">Tipo *</span>
          <select id="mat-tipo" class="select-filtro" style="width:100%;">
            <option value="pdf">PDF</option>
            <option value="ppt">Presentación PPT</option>
            <option value="img">Imagen</option>
            <option value="zip">ZIP</option>
            <option value="video">Video</option>
            <option value="enlace">Enlace externo</option>
          </select>
        </div>

        <!-- SUBIDA DE ARCHIVO -->
        <div class="modal-field" id="mat-archivo-wrap">
          <span class="modal-label">Archivo</span>
          <div class="upload-area" id="mat-upload-area">
            <div id="mat-preview-wrap" style="display:none;margin-bottom:8px;">
              <p id="mat-preview-nombre" style="font-size:12px;color:var(--txt-soft);display:flex;align-items:center;gap:6px;">
                <i class="fa-solid fa-file" style="color:var(--accent);"></i>
                <span id="mat-preview-txt"></span>
              </p>
            </div>
            <label class="upload-btn" for="mat-archivo-file">
              <i class="fa-solid fa-upload"></i>
              <span id="mat-upload-lbl">Seleccionar archivo</span>
            </label>
            <input type="file" id="mat-archivo-file" style="display:none;"
                   onchange="previewArchivo(this,'mat-preview-wrap','mat-preview-txt','mat-upload-lbl')">
          </div>
          <p style="font-size:11px;color:var(--txt-xsoft);margin-top:4px;">
            O ingresa una URL externa:
          </p>
          <input type="text" id="mat-url-texto" class="input-filtro" style="width:100%;margin-top:4px;"
                 placeholder="https://drive.google.com/...">
        </div>

        <div class="modal-field">
          <span class="modal-label">Descripción</span>
          <input type="text" id="mat-descripcion" class="input-filtro" style="width:100%;" placeholder="Descripción breve (opcional)">
        </div>
        <div class="modal-field">
          <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:13px;">
            <input type="checkbox" id="mat-descarga-activa" style="width:16px;height:16px;" checked>
            <span>Descarga habilitada (desactivar para bloquear descarga)</span>
          </label>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="cerrarModal('modal-material')">Cancelar</button>
      <button class="btn-primary" id="btn-guardar-material"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
    </div>
  </div>
</div>

<!-- ══ MODAL GRUPO ALABANZA ══ -->
<div class="modal-overlay" id="modal-grupo" style="display:none;">
  <div class="modal" style="max-width:440px;">
    <div class="modal-header">
      <h3 id="modal-grupo-titulo"><i class="fa-solid fa-music"></i> Grupo de Alabanza</h3>
      <button class="modal-close" onclick="cerrarModal('modal-grupo')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="grupo-id">
      <div class="modal-field">
        <span class="modal-label">Nombre del Grupo *</span>
        <input type="text" id="grupo-nombre" class="input-filtro" style="width:100%;" placeholder="Ej: Ministerio de Alabanza">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="cerrarModal('modal-grupo')">Cancelar</button>
      <button class="btn-primary" id="btn-guardar-grupo"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
    </div>
  </div>
</div>  

<!-- ══ MODAL MODERADOR ══ -->
<div class="modal-overlay" id="modal-moderador" style="display:none;">
  <div class="modal" style="max-width:440px;">
    <div class="modal-header">
      <h3 id="modal-moderador-titulo"><i class="fa-solid fa-person-chalkboard"></i> Moderador</h3>
      <button class="modal-close" onclick="cerrarModal('modal-moderador')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="mod-id">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div class="modal-field">
          <span class="modal-label">Nombre *</span>
          <input type="text" id="mod-nombre" class="input-filtro" style="width:100%;">
        </div>
        <div class="modal-field">
          <span class="modal-label">Apellido *</span>
          <input type="text" id="mod-apellido" class="input-filtro" style="width:100%;">
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="cerrarModal('modal-moderador')">Cancelar</button>
      <button class="btn-primary" id="btn-guardar-moderador"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
    </div>
  </div>
</div>

<!-- ══ MODAL CONFIRMAR ELIMINAR ══ -->
<div class="modal-overlay" id="modal-confirmar-eliminar" style="display:none;">
  <div class="modal" style="max-width:360px;">
    <div class="modal-header" style="background:#dc2626;">
      <h3><i class="fa-solid fa-trash"></i> Confirmar Eliminación</h3>
      <button class="modal-close" onclick="cerrarModal('modal-confirmar-eliminar')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body" style="text-align:center;padding:28px 20px;">
      <i class="fa-solid fa-triangle-exclamation" style="font-size:2.5em;color:#dc2626;margin-bottom:14px;display:block;"></i>
      <p id="eliminar-prog-txt" style="font-size:14px;font-weight:600;color:var(--txt);">¿Eliminar este elemento?</p>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="cerrarModal('modal-confirmar-eliminar')">Cancelar</button>
      <button class="btn-primary" id="btn-confirmar-eliminar-prog" style="background:#dc2626;">
        <i class="fa-solid fa-trash"></i> Eliminar
      </button>
    </div>
  </div>
</div>

<?php endif; ?>

<div class="toast" id="toast-programa"></div>

<style>
.prog-tabs {
  display:flex; gap:4px; margin-bottom:20px;
  background:var(--card-bg); border:1px solid var(--border);
  border-radius:var(--radius); padding:6px; flex-wrap:wrap;
  box-shadow:var(--shadow);
}
.prog-tab {
  display:flex; align-items:center; gap:7px;
  padding:9px 18px; border:none; border-radius:8px;
  background:transparent; color:var(--txt-soft);
  font-family:var(--font-body); font-size:13px; font-weight:600;
  cursor:pointer; transition:all .2s; white-space:nowrap;
}
.prog-tab:hover { background:var(--bg); color:var(--txt); }
.prog-tab.active { background:var(--sidebar-bg); color:#fff; }
.prog-panel { display:none; }
.prog-panel.active { display:block; }
.badge-tipo {
  display:inline-block; padding:2px 8px; border-radius:20px;
  font-size:10px; font-weight:700; text-transform:uppercase;
}
.tipo-conferencia { background:#E7E2DF;color:#652C2A; }
.tipo-alabanza    { background:#fce7f3;color:#9d174d; }
.tipo-receso      { background:#fef9c3;color:#713f12; }
.tipo-actividad   { background:#d1fae5;color:#065f46; }
.tipo-otro        { background:#f3f4f6;color:#374151; }
.descarga-si { color:var(--green);font-weight:700;font-size:12px; }
.descarga-no { color:var(--accent);font-weight:700;font-size:12px; }
textarea.input-filtro { padding:10px 12px; font-family:var(--font-body); }

/* ── UPLOAD AREA ── */
.upload-area {
  border:2px dashed var(--border);
  border-radius:10px; padding:14px;
  background:var(--bg); text-align:center;
  transition:border-color .2s;
}
.upload-area:hover { border-color:var(--blue); }
.upload-btn {
  display:inline-flex; align-items:center; gap:8px;
  padding:8px 18px;
  background:var(--card-bg); border:1px solid var(--border);
  border-radius:8px; cursor:pointer; font-size:13px;
  font-weight:600; color:var(--txt-soft);
  transition:all .2s; font-family:var(--font-body);
}
.upload-btn:hover { background:var(--sidebar-bg); color:#fff; border-color:var(--sidebar-bg); }
/* ══ RESPONSIVO PROGRAMA ══ */
@media (max-width: 768px) {

  /* tabs en scroll horizontal */
  .prog-tabs {
    overflow-x: auto;
    flex-wrap: nowrap;
    padding: 4px;
    gap: 3px;
  }
  .prog-tab {
    padding: 8px 12px;
    font-size: 12px;
    flex-shrink: 0;
  }
  .prog-tab i { display: none; }

  /* modales ancho completo */
  .modal {
    max-width: 100% !important;
    margin: 0;
    border-radius: 0;
    height: 100dvh;
    overflow-y: auto;
  }
  .modal-body { max-height: calc(100dvh - 130px); }

  /* grids de modales en 1 columna */
  .modal-body > div[style*="grid-template-columns:1fr 1fr"],
  .modal-body > div[style*="grid-template-columns: 1fr 1fr"] {
    display: flex !important;
    flex-direction: column !important;
  }
}

@media (max-width: 480px) {

  /* filtros de eventos en columna */
  #panel-eventos .filtros-rapidos {
    flex-direction: column;
  }
  #panel-eventos .select-filtro {
    width: 100%;
    min-width: unset;
  }

  /* texto de tabs más corto */
  .prog-tab { padding: 7px 10px; font-size: 11px; }
}
</style>

<script>
var PUEDE_EDITAR_PROG = <?php echo $puede_editar ? 'true' : 'false'; ?>;
</script>

<?php $js_pagina = ['programa.js']; ?>
<?php include_once 'templates/footer.php'; ?>