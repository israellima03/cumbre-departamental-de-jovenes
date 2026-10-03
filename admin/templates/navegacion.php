<?php
$admin_full  = ['Administrador', 'Lider departamental', 'tesorera'];
$solo_basico = ['Lider distrital', 'Lider local', 'secretario', 'Equipo departamental'];
?>

<body>
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <div class="brand-icon"><i class="fa-solid fa-dove"></i></div>
      <div class="brand-text">
        <span class="brand-name">Cumbre</span>
        <span class="brand-sub">Jóvenes</span>
      </div>
    </div>

    <nav class="sidebar-nav">

      <!-- PRINCIPAL — todos los roles -->
      <div class="nav-section-label">Principal</div>
      <a href="admin-encuentro.php" class="nav-item">
        <i class="fa-solid fa-chart-pie"></i>
        <span>Dashboard</span>
      </a>

      <!-- CONFIRMAR INSCRIPCIONES (QR y efectivo) — solo admin full -->
      <?php if(puede($admin_full)): ?>
      <a href="confirmar-inscripciones.php" class="nav-item">
        <i class="fa-solid fa-circle-check"></i>
        <span>Confirmar Inscripciones</span>
      </a>
      <?php endif; ?>
      <!-- Ver comisiones o editar comisiones -->
      <?php if(puede($admin_full)): ?>
      <a href="comisiones.php" class="nav-item">
        <i class="fa-solid fa-users"></i>
        <span>Comisiones</span>
      </a>
      <?php endif; ?>

      <!-- INSCRIPCIONES — solo admin full -->
      <?php if(puede($admin_full)): ?>
      <div class="nav-section-label">Inscripciones</div>
      <div class="nav-item nav-parent" id="nav-inscripciones-toggle">
        <i class="fa-solid fa-clipboard-list"></i>
        <span>Inscripciones</span>
        <i class="fa-solid fa-chevron-down nav-arrow"></i>
      </div>
      <div class="nav-submenu" id="submenu-inscripciones">
        <a href="editar-cupos.php" class="nav-subitem">
          <i class="fa-solid fa-users-gear"></i> Editar Cupos
        </a>
        <a href="configurar-sistema.php" class="nav-subitem">
          <i class="fa-solid fa-pen-to-square"></i> Configurar Sistema
        </a>
      </div>
      <?php endif; ?>

      <!-- GESTION — solo admin full -->
      <?php if(puede($admin_full)): ?>
      <div class="nav-section-label">Gestión</div>
      <a href="reportes.php" class="nav-item">
        <i class="fa-solid fa-chart-bar"></i>
        <span>Reportes</span>
      </a>
      <?php endif; ?>

      <!-- PROGRAMA — solo admin full -->
      <?php if(puede($admin_full)): ?>
      <div class="nav-section-label">Programa</div>
      <div class="nav-item nav-parent" id="nav-programa-toggle">
        <i class="fa-solid fa-calendar-days"></i>
        <span>Programa</span>
        <i class="fa-solid fa-chevron-down nav-arrow"></i>
      </div>
      <div class="nav-submenu" id="submenu-programa">
        <a href="programa.php?tab=expositores" class="nav-subitem">
          <i class="fa-solid fa-microphone"></i> Invitados
        </a>
        <a href="programa.php?tab=temas" class="nav-subitem">
          <i class="fa-solid fa-book-open"></i> Temas
        </a>
        <a href="programa.php?tab=eventos" class="nav-subitem">
          <i class="fa-solid fa-calendar-check"></i> Eventos
        </a>
        <a href="programa.php?tab=material" class="nav-subitem">
          <i class="fa-solid fa-folder-open"></i> Material
        </a>
        <a href="programa.php?tab=moderadores" class="nav-subitem">
          <i class="fa-solid fa-person-chalkboard"></i> Moderadores
        </a>
      </div>
      <?php endif; ?>

      <!-- SISTEMA — todos los roles -->
      <div class="nav-section-label">Sistema</div>

      <!-- PREGUNTAS — submenú con dos niveles de acceso -->
      <div class="nav-item nav-parent" id="nav-preguntas-toggle">
        <i class="fa-solid fa-circle-question"></i>
        <span>Preguntas</span>
        <i class="fa-solid fa-chevron-down nav-arrow"></i>
      </div>
      <div class="nav-submenu" id="submenu-preguntas">
        <?php if(puede($admin_full)): ?>
        <a href="preguntas.php?seccion=habilitar" class="nav-subitem">
          <i class="fa-solid fa-toggle-on"></i> Habilitar Preguntas
        </a>
        <?php endif; ?>
        <a href="preguntas.php?seccion=ver" class="nav-subitem">
          <i class="fa-solid fa-list-ul"></i> Ver Preguntas
        </a>
      </div>


      <!-- ADMINISTRADORES — solo Administrador, Lider departamental -->
      <?php if(puede(['Administrador', 'Lider departamental'])): ?>
      <div class="nav-section-label">Administradores</div>
      <div class="nav-item nav-parent" id="nav-administradores-toggle">
        <i class="fa-solid fa-user-shield"></i>
        <span>Administradores</span>
        <i class="fa-solid fa-chevron-down nav-arrow"></i>
      </div>
      <div class="nav-submenu" id="submenu-administradores">
        <a href="ver-admin.php" class="nav-subitem">
          <i class="fa-solid fa-users"></i> Ver Todos
        </a>
        <?php if(puede(['Administrador'])): ?>
        <a href="crear-admin.php" class="nav-subitem">
          <i class="fa-solid fa-user-plus"></i> Agregar
        </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>

    </nav>

    <div class="sidebar-footer">
      <a href="logout.php" class="btn-cerrar-sesion">
        <i class="fa-solid fa-right-from-bracket"></i>
        <span>Cerrar Sesión</span>
      </a>
    </div>
  </aside>