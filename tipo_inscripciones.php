<?php include_once 'includes/templates/header.php';
require_once('includes/funciones/bd_conexion.php');

/* líderes distritales */
$lideres_dist = [];
$res = $conn->query("
  SELECT u.nombre, u.telefono, d.nombre AS distrito
  FROM usuarios u
  INNER JOIN usuario_roles ur ON ur.usuario_id = u.id
  INNER JOIN roles r ON r.id = ur.rol_id AND r.nombre = 'Lider distrital'
  LEFT JOIN distritos d ON d.id = u.distrito_id
  ORDER BY d.nombre, u.nombre
");
if($res) while($r = $res->fetch_assoc()) $lideres_dist[] = $r;

/* líderes departamentales */
$lideres_dep = [];
$res = $conn->query("
  SELECT u.nombre, u.telefono, d.nombre AS distrito
  FROM usuarios u
  INNER JOIN usuario_roles ur ON ur.usuario_id = u.id
  INNER JOIN roles r ON r.id = ur.rol_id AND r.nombre = 'Lider departamental'
  LEFT JOIN distritos d ON d.id = u.distrito_id
  ORDER BY u.nombre
");
if($res) while($r = $res->fetch_assoc()) $lideres_dep[] = $r;
?>

<section class="seccion contenedor">
  <h2>Tipo de Inscripción</h2>

  <p class="subtitulo-inscripcion">
    Elige cómo deseas realizar tu inscripción a la Cumbre Departamental de Jóvenes
  </p>

  <div class="grid-inscripcion">

    <!-- OPCION QR / BANCA MOVIL -->
    <div class="card-inscripcion card-qr">
      <div class="card-ins-icono">
        <i class="fa-solid fa-qrcode"></i>
      </div>
      <h3 class="card-ins-titulo">Pago por QR o Banca Móvil</h3>
      <div class="card-ins-separador"></div>
      <p class="card-ins-descripcion">
        Si cuentas con <strong>banca móvil</strong> o cualquier aplicación de pago QR,
        puedes inscribirte tú mismo de forma rápida y segura.
      </p>
      <ul class="card-ins-pasos">
        <li><i class="fa-solid fa-circle-check"></i> Escanea el QR o paga por banca móvil</li>
        <li><i class="fa-solid fa-circle-check"></i> Completa el formulario de registro</li>
        <li><i class="fa-solid fa-circle-check"></i> Guarda tu comprobante</li>
      </ul>
      <a href="registro.php" class="button card-ins-boton">
        <i class="fa-solid fa-arrow-right"></i> Ir al Registro
      </a>
    </div>

    <!-- OPCION EFECTIVO -->
    <div class="card-inscripcion card-efectivo">
      <div class="card-ins-icono">
        <i class="fa-solid fa-money-bills"></i>
      </div>
      <h3 class="card-ins-titulo">Pago en Efectivo</h3>
      <div class="card-ins-separador"></div>
      <p class="card-ins-descripcion">
        Si pagas en <strong>efectivo</strong>, el registro debe ser realizado
        por tu líder local o distrital. Contáctalos por WhatsApp:
      </p>

      <!-- LIDERES DISTRITALES -->
      <?php if(!empty($lideres_dist)): ?>
      <div class="lideres-grupo">
        <h4 class="lideres-titulo">
          <i class="fa-solid fa-building-columns"></i> Líderes Distritales
        </h4>
        <ul class="lista-lideres">
          <?php foreach($lideres_dist as $l): ?>
          <li class="lider-item">
            <div class="lider-info">
              <span class="lider-nombre"><?php echo htmlspecialchars($l['nombre']); ?></span>
              <span class="lider-zona"><?php echo htmlspecialchars($l['distrito'] ?? 'Sin distrito'); ?></span>
            </div>
            <?php if(!empty($l['telefono'])): ?>
            <a href="https://wa.me/591<?php echo preg_replace('/\D/','',$l['telefono']); ?>?text=Hola%2C+quiero+información+sobre+la+cumbre"
               target="_blank" class="boton-whatsapp">
              <i class="fa-brands fa-whatsapp"></i> <?php echo htmlspecialchars($l['telefono']); ?>
            </a>
            <?php else: ?>
            <span class="boton-whatsapp" style="opacity:.5;cursor:default;">Sin número</span>
            <?php endif; ?>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <!-- LIDERES DEPARTAMENTALES -->
      <?php if(!empty($lideres_dep)): ?>
      <div class="lideres-grupo">
        <h4 class="lideres-titulo">
          <i class="fa-solid fa-star"></i> Líderes Departamentales
        </h4>
        <ul class="lista-lideres">
          <?php foreach($lideres_dep as $l): ?>
          <li class="lider-item">
            <div class="lider-info">
              <span class="lider-nombre"><?php echo htmlspecialchars($l['nombre']); ?></span>
              <span class="lider-zona">Lider Departamental</span>
            </div>
            <?php if(!empty($l['telefono'])): ?>
            <a href="https://wa.me/591<?php echo preg_replace('/\D/','',$l['telefono']); ?>?text=Hola%2C+quiero+información+sobre+la+cumbre"
               target="_blank" class="boton-whatsapp">
              <i class="fa-brands fa-whatsapp"></i> <?php echo htmlspecialchars($l['telefono']); ?>
            </a>
            <?php else: ?>
            <span class="boton-whatsapp" style="opacity:.5;cursor:default;">Sin número</span>
            <?php endif; ?>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      

      <div class="aviso-efectivo">
        <i class="fa-solid fa-triangle-exclamation"></i>
        Solo tu líder puede completar el registro en efectivo.
      </div>
      <!-- BOTON LOGIN LIDERES -->
      <div class="bloque-login-lider">
        <div class="separador-login"></div>
        <p class="texto-login-lider">
          <i class="fa-solid fa-lock"></i>
          ¿Eres lider? Ingresa con tu cuenta para registrar jovenes en efectivo
        </p>
        <a href="admin/login.php" class="button button-login-lider">
          <i class="fa-solid fa-right-to-bracket"></i> Acceso Lideres
        </a>
        <p class="aviso-solo-lider">
          <i class="fa-solid fa-triangle-exclamation"></i>
          Solo el lider autorizado puede ingresar al sistema de registro
        </p>
      </div>

    </div>

  </div>
</section>

<?php include_once 'includes/templates/footer.php'; ?>