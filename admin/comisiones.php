<?php
require_once('funciones/sesiones.php');
usuario_autentificado();
verificar_acceso(['Administrador','Lider departamental','tesorera']);

header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
require_once('../includes/funciones/bd_conexion.php');
include_once 'templates/header.php';
include_once 'templates/navegacion.php';
include_once 'templates/barra.php';
include_once 'funciones/funciones.php';

$puede_gestionar = puede(['Administrador','Lider departamental','tesorera']);
?>

<link rel="stylesheet" href="css/comisiones.css">

<main class="content" id="main-content">
<div class="page active">

  <div class="page-header">
    <div>
      <h1 class="page-title">Comisiones <span>de la Cumbre</span></h1>
      <p class="page-sub">Gestiona las comisiones y sus encargados.</p>
    </div>
    <button class="btn-primary" id="btn-nueva-comision">
      <i class="fa-solid fa-plus"></i>
      <span class="btn-txt-label">Nueva Comisión</span>
    </button>
  </div>

  <div class="card" style="overflow:visible;">
    <div class="card-header">
      <h3><i class="fa-solid fa-people-group"></i> Comisiones</h3>
      <span id="com-total-lbl" class="total-lbl-txt"></span>
    </div>
    <div class="filtros-rapidos">
      <input type="text" id="com-buscar"
             placeholder="Buscar comisión..." class="input-filtro">
      <select id="com-estado" class="select-filtro">
        <option value="">Todos los estados</option>
        <option value="1">Activas</option>
        <option value="0">Inactivas</option>
      </select>
    </div>
    <div class="com-tabla-wrap">
      <table class="tabla-inscritos" id="tabla-comisiones">
        <thead>
          <tr>
            <th>#</th>
            <th>Ícono</th>
            <th>Nombre</th>
            <th>Encargados</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody id="tbody-comisiones">
          <tr>
            <td colspan="7" class="tabla-loading">
              <i class="fa-solid fa-spinner fa-spin"></i> Cargando...
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

</div>
</main>

<!-- MODAL COMISIÓN -->
<div class="modal-overlay" id="modal-com-overlay" style="display:none;">
  <div class="modal com-modal">
    <div class="modal-header">
      <h3>
        <i class="fa-solid fa-people-group"></i>
        <span id="com-modal-titulo">Nueva Comisión</span>
      </h3>
      <button class="modal-close" onclick="cerrarModalCom()">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="com-id">

      <div class="com-form-grid">

        <div class="modal-field com-field-full">
          <span class="modal-label">Nombre *</span>
          <input type="text" id="com-nombre" class="input-filtro com-input"
                 placeholder="Ej: Comisión de Logística">
          <span id="com-nombre-err" class="com-err"></span>
        </div>

        <div class="modal-field">
          <span class="modal-label">Ícono Font Awesome</span>
          <div class="com-icono-row">
            <input type="text" id="com-icono" class="input-filtro com-input"
                   placeholder="fa-solid fa-users" value="fa-solid fa-users">
            <div id="com-icono-preview" class="com-icono-prev">
              <i class="fa-solid fa-users"></i>
            </div>
          </div>
          <span class="com-hint">Ej: fa-solid fa-wrench, fa-solid fa-music</span>
        </div>

        <div class="modal-field">
          <span class="modal-label">Orden</span>
          <input type="number" id="com-orden" class="input-filtro com-input"
                 min="0" value="0">
        </div>

        <div class="modal-field com-field-full">
          <span class="modal-label">Estado</span>
          <select id="com-activo" class="select-filtro com-input">
            <option value="1">Activa</option>
            <option value="0">Inactiva</option>
          </select>
        </div>

      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="cerrarModalCom()">Cancelar</button>
      <button class="btn-primary" id="btn-com-guardar">
        <i class="fa-solid fa-floppy-disk"></i> Guardar
      </button>
    </div>
  </div>
</div>

<!-- MODAL ENCARGADOS -->
<div class="modal-overlay" id="modal-enc-overlay" style="display:none;">
  <div class="modal com-modal-enc">
    <div class="modal-header">
      <h3>
        <i class="fa-solid fa-user-gear"></i>
        Encargados — <span id="enc-comision-nombre"></span>
      </h3>
      <button class="modal-close" onclick="cerrarModalEnc()">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="enc-comision-id">

      <!-- Lista -->
      <div id="enc-lista" class="enc-lista-wrap"></div>

      <!-- Formulario agregar -->
      <div class="enc-form-wrap">
        <p class="enc-form-titulo">
          <i class="fa-solid fa-plus"></i> Agregar Encargado
        </p>
        <div class="enc-form-grid">
          <div class="modal-field">
            <span class="modal-label">Nombre *</span>
            <input type="text" id="enc-nombre" class="input-filtro com-input"
                   placeholder="Nombre completo">
          </div>
          <div class="modal-field">
            <span class="modal-label">Celular</span>
            <input type="text" id="enc-celular" class="input-filtro com-input"
                   placeholder="Ej: 68319277">
          </div>
        </div>
        <div id="enc-aviso" class="enc-aviso" style="display:none;"></div>
        <button class="btn-primary enc-btn-agregar" id="btn-enc-agregar">
          <i class="fa-solid fa-plus"></i> Agregar Encargado
        </button>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="cerrarModalEnc()">Cerrar</button>
    </div>
  </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast-comisiones"></div>

<script>
var PUEDE_GESTIONAR = <?php echo $puede_gestionar ? 'true' : 'false'; ?>;
</script>
<?php $js_pagina = ['comisiones.js']; ?>
<?php include_once 'templates/footer.php'; ?>