<?php
require_once __DIR__.'/../funciones/bd_conexion.php';
require_once __DIR__.'/../funciones/config_sitio.php';
$cfg    = get_config($conn);
$redes  = get_redes($conn);
$nombre = $cfg['evento_nombre'] ?? 'Cumbre Departamental de Jóvenes';
$lema   = $cfg['evento_lema']   ?? 'Llamados a testificar';
$versiculo = $cfg['evento_versiculo'] ?? '';
$ciudad = !empty($cfg['evento_ciudad']) ? $cfg['evento_ciudad'] : 'Oruro, Bolivia';
$fecha_evento = !empty($cfg['evento_fecha']) ? $cfg['evento_fecha'] : '2026-11-06';
$fecha_ts     = strtotime($fecha_evento);
$fecha_txt    = $fecha_ts ? date('d/m/Y', $fecha_ts) : $fecha_evento;
$pagina_actual = basename($_SERVER['PHP_SELF']);

/* cache busting */
$v = function($archivo){
  $ruta = $_SERVER['DOCUMENT_ROOT'] . '/' . $archivo;
  return file_exists($ruta) ? filemtime($ruta) : time();
};
?>
<!doctype html>
<html class="no-js" lang="es">
<head>
  <meta charset="utf-8">
  <title>Cumbre Departamental de Jóvenes Oruro</title>
  <meta name="description" content="Cumbre Departamental de Jóvenes Oruro 2026 — Iglesia de Dios de la Profecía. 6 de noviembre en la Ex Terminal de Oruro. Llamados a testificar.">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="manifest" href="site.webmanifest">
  <link rel="apple-touch-icon" href="icon.png">
  <link rel="stylesheet" href="css/normalize.css?v=<?php echo $v('css/normalize.css'); ?>">
  <link rel="stylesheet" href="css/main.css?v=<?php echo $v('css/main.css'); ?>">
  <link rel="stylesheet" href="css/cumbre.css?v=<?php echo $v('css/cumbre.css'); ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Oswald:wght@400;500;600;700&family=Montserrat:wght@400;500;600;700&family=Kaushan+Script&family=Pacifico&display=swap" rel="stylesheet">
  <meta name="theme-color" content="#2C2668">
</head>

<body <?php echo ($pagina_actual !== 'index.php') ? 'class="pagina-interior"' : ''; ?>>
  <!--[if IE]>
    <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="https://browsehappy.com/">upgrade your browser</a> to improve your experience and security.</p>
  <![endif]-->

  <?php if($pagina_actual === 'index.php'): ?>
    <header class="site-header">
      <div class="hero">
        <span class="hero-fondo-txt" aria-hidden="true">CUMBRE</span>
        <div class="logos-laterales">
          <img src="img/escudoidpo.png" class="logo-izq" alt="Iglesia de Dios de la Profecía">
          <img src="img/escudomjo.png" class="logo-der" alt="Ministerio de Jóvenes Oruro">
        </div>
        <div class="contenido-header">
          <nav class="redes-sociales">
            <?php foreach($redes as $r): ?>
              <a href="<?php echo htmlspecialchars($r['url']); ?>" target="_blank" title="<?php echo htmlspecialchars($r['nombre']); ?>">
                <i class="<?php echo htmlspecialchars($r['icono']); ?>"></i>
              </a>
            <?php endforeach; ?>
          </nav>
          <div class="informacion-evento">
            <div class="clearfix">
              <p class="fecha"><i class="fa-solid fa-calendar"></i><?php echo htmlspecialchars($fecha_txt); ?></p>
              <p class="ciudad"><i class="fa-solid fa-location-dot"></i><?php echo htmlspecialchars($ciudad); ?></p>
            </div>
            <h1 class="nombre-sitio"><?php echo htmlspecialchars($nombre); ?></h1>
            <p class="slogan">
              <strong class="lema-txt"><?php echo htmlspecialchars($lema); ?></strong>
              <?php if($versiculo !== ''): ?>
                <span class="versiculo-txt"><?php echo htmlspecialchars($versiculo); ?></span>
              <?php endif; ?>
            </p>
          </div>
        </div>
        <div class="scroll-down">
          <p class="scroll-texto">Desliza</p>
          <div class="scroll-flechas">
            <i class="fa-solid fa-chevron-down"></i>
            <i class="fa-solid fa-chevron-down"></i>
            <i class="fa-solid fa-chevron-down"></i>
          </div>
        </div>
      </div>
    </header>
  <?php endif; ?>

  <div class="barra">
    <div class="contenedor clearfix">
      <div class="menu-movil">
        <span></span>
        <span></span>
        <span></span>
      </div>
      <nav class="navegacion-principal clearfix">
        <a href="index.php" <?php echo $pagina_actual === 'index.php' ? 'class="nav-activo"' : ''; ?>>Inicio</a>
        <a href="conferencia.php" <?php echo $pagina_actual === 'conferencia.php' ? 'class="nav-activo"' : ''; ?>>Conferencia</a>
        <a href="calendario.php" <?php echo $pagina_actual === 'calendario.php' ? 'class="nav-activo"' : ''; ?>>Calendario</a>
        <a href="invitados.php" <?php echo $pagina_actual === 'invitados.php' ? 'class="nav-activo"' : ''; ?>>Invitados</a>
        <a href="tipo_inscripciones.php" <?php echo in_array($pagina_actual, ['tipo_inscripciones.php','registro.php']) ? 'class="nav-activo"' : ''; ?>>Inscripciones</a>
      </nav>
    </div>
  </div>
