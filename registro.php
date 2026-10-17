<?php 
include_once 'includes/templates/header.php';
require_once('includes/funciones/bd_conexion.php');
/* verificar si inscripciones están activas */
$cfg = $conn->query("SELECT valor FROM config_sistema WHERE clave='inscripciones_activas' LIMIT 1")->fetch_assoc();
if($cfg && $cfg['valor'] == '0'){
  die('<div style="text-align:center;padding:60px;font-family:sans-serif;">
    <h2 style="color:#dc2626;">⚠ Inscripciones temporalmente suspendidas</h2>
    <p>El sistema está en mantenimiento. Vuelve más tarde.</p>
  </div>');
}

$iglesias = [];
$res = $conn->query("
    SELECT i.id, i.nombre AS iglesia,
           d.id AS distrito_id, d.nombre AS distrito
    FROM iglesias i
    LEFT JOIN distritos d ON i.distrito_id = d.id
    ORDER BY i.nombre
");
while($r = $res->fetch_assoc()) $iglesias[] = $r;

$tipos = [];
$res = $conn->query("SELECT id, nombre FROM tipos_inscrito ORDER BY id");
while($r = $res->fetch_assoc()) $tipos[] = $r;

$paquetes = [];
$res = $conn->query("
    SELECT p.id, p.nombre, p.precio, p.cupo_total, p.cupos_disponibles,
           d.nombre        AS descuento_nombre,
           d.porcentaje    AS descuento_porcentaje,
           d.fecha_fin,
           ROUND(p.precio - (p.precio * COALESCE(d.porcentaje,0) / 100), 0) AS precio_final
    FROM paquetes p
    LEFT JOIN paquete_descuentos pd ON p.id = pd.paquete_id
    LEFT JOIN descuentos d ON pd.descuento_id = d.id AND d.activo = 1
    ORDER BY p.precio
");
while($r = $res->fetch_assoc()) $paquetes[] = $r;

/* regalo: el primero con cupos disponibles */
$regalo_id     = 0;
$regalo_nombre = '';
$res_r = $conn->query("SELECT id, nombre FROM regalos WHERE cupos_disponibles > 0 ORDER BY id LIMIT 1");
if($res_r && $row_r = $res_r->fetch_assoc()){
  $regalo_id     = (int)$row_r['id'];
  $regalo_nombre = $row_r['nombre'];
}
?>

<section class="seccion contenedor">
  <h2>Inscripción a la Cumbre</h2>

  <div class="aviso-qr">
    <i class="fa-solid fa-qrcode"></i>
    El pago es <strong>unicamente por QR / Banca Movil</strong>. 
    Completa todos los datos, revisa el resumen, escanea el QR, sube tu comprobante y tu inscripcion quedara como <strong>PENDIENTE</strong> hasta que la tesorera la confirme.
  </div>

  <div id="bloque-formulario">
    <form id="form-registro" novalidate>

      <div class="seccion-form caja">
        <h4><i class="fa-solid fa-user"></i> Datos Personales</h4>
        <div class="grid-campos">

          <div class="campo">
            <label>Nombre <span class="req">*</span></label>
            <input type="text" id="nombre" placeholder="Tu nombre">
            <span class="campo-error" id="err-nombre"></span>
          </div>

          <div class="campo">
            <label>Apellido <span class="req">*</span></label>
            <input type="text" id="apellido" placeholder="Tu apellido">
            <span class="campo-error" id="err-apellido"></span>
          </div>

          <div class="campo">
            <label>Carnet de Identidad <span class="req">*</span></label>
            <input type="text" id="carnet" placeholder="Ej: 1234567 o 1234567-1A">
            <span class="campo-error" id="err-carnet"></span>
          </div>

          <div class="campo">
            <label>Edad <span class="req">*</span></label>
            <input type="number" id="edad" min="5" max="100" placeholder="Ej: 20">
            <span class="campo-error" id="err-edad"></span>
          </div>

          <div class="campo">
            <label>Celular <span class="req">*</span></label>
            <input type="text" id="celular" placeholder="Ej: 68319277">
            <span class="campo-error" id="err-celular"></span>
          </div>

          <div class="campo">
            <label>Tipo de Inscrito <span class="req">*</span></label>
            <select id="tipo_inscrito_id">
              <option value="">-- Selecciona tu tipo --</option>
              <?php foreach($tipos as $t): ?>
                <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['nombre']); ?></option>
              <?php endforeach; ?>
            </select>
            <span class="campo-error" id="err-tipo"></span>
          </div>

          <div class="campo campo-ancho">
            <label>Iglesia <span class="req">*</span></label>
            <select id="iglesia_id">
              <option value="">-- Selecciona tu iglesia --</option>
              <?php foreach($iglesias as $ig): ?>
                <option value="<?php echo $ig['id']; ?>"
                        data-distrito-id="<?php echo $ig['distrito_id'] ?? ''; ?>"
                        data-distrito="<?php echo htmlspecialchars($ig['distrito'] ?? ''); ?>">
                  <?php echo htmlspecialchars($ig['iglesia']); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <span class="campo-error" id="err-iglesia"></span>
          </div>

          <input type="hidden" id="distrito_id" value="">

          <div class="campo">
            <label>Distrito</label>
            <input type="text" id="distrito_nombre" placeholder="Se completa al elegir iglesia" readonly class="campo-derivado">
          </div>

        </div>
      </div>

      <div class="seccion-form">
        <h3 style="text-align:center;margin-bottom:8px;">Elige tu Paquete</h3>
        <p class="texto-paquete">Solo puedes elegir un paquete</p>
        <span class="campo-error" id="err-paquete" style="text-align:center;display:block;"></span>

        <ul class="lista-precios">
          <?php foreach($paquetes as $paq):
            $con_desc = !empty($paq['descuento_porcentaje']);
            $pf       = $con_desc ? $paq['precio_final'] : $paq['precio'];
            $agotado  = $paq['cupos_disponibles'] <= 0;
          ?>
            <li>
              <div class="tabla-precio <?php echo $agotado ? 'agotado' : ''; ?>">
                <label class="paquete-label">
                  <input type="radio" name="paquete"
                         value="<?php echo $paq['id']; ?>"
                         data-precio="<?php echo $pf; ?>"
                         data-nombre="<?php echo htmlspecialchars($paq['nombre']); ?>"
                         <?php echo $agotado ? 'disabled' : ''; ?>>
                  <h3><?php echo htmlspecialchars($paq['nombre']); ?></h3>
                </label>
                <?php if(stripos($paq['nombre'], 'aliment') !== false): ?>
                  <div class="aviso-vigencia"><i class="fa-solid fa-hourglass-half"></i> Paquete activo hasta el <strong>31 de octubre</strong></div>
                <?php endif; ?>

                <?php if($con_desc): ?>
                  <p class="precio-original">Bs. <?php echo number_format($paq['precio'],0); ?></p>
                  <p class="numero">Bs. <?php echo number_format($pf,0); ?></p>
                  <div class="badge-descuento">
                    <i class="fa-solid fa-tag"></i>
                    <?php echo htmlspecialchars($paq['descuento_nombre']); ?> —
                    <?php echo $paq['descuento_porcentaje']; ?>% OFF
                  </div>
                  <p class="fecha-descuento">
                    <i class="fa-solid fa-calendar"></i>
                    Hasta: <?php echo date('d/m/Y', strtotime($paq['fecha_fin'])); ?>
                  </p>
                <?php else: ?>
                  <p class="numero">Bs. <?php echo number_format($pf,2); ?></p>
                <?php endif; ?>

                <p class="cupos-disponibles">
                  <i class="fa-solid fa-users"></i>
                  <?php echo $paq['cupos_disponibles']; ?> / <?php echo $paq['cupo_total']; ?> cupos
                </p>

                <ul class="lista-beneficios">
                  <?php
                    $lista = ['Acceso a todas las conferencias','Material de la cumbre'];
                    if(stripos($paq['nombre'], 'aliment') !== false) $lista[] = 'Almuerzo en el Hotel';
                    foreach($lista as $b): ?>
                      <li><?php echo $b; ?></li>
                  <?php endforeach; ?>
                </ul>

                <?php if($agotado): ?>
                  <p class="texto-agotado">AGOTADO</p>
                <?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <?php if($regalo_id): ?>
      <div class="seccion-form caja">
        <h4><i class="fa-solid fa-gift"></i> Regalo de la Cumbre</h4>
        <div class="aviso-regalo">
          <i class="fa-solid fa-box-open"></i>
          Todos los inscritos reciben: <strong><?php echo htmlspecialchars($regalo_nombre); ?></strong>
        </div>
      </div>
      <?php endif; ?>
      <input type="hidden" id="regalo_id" value="<?php echo $regalo_id; ?>">

      <div class="seccion-form resumen">
        <h3>Resumen de tu Inscripcion</h3>
        <div class="caja" style="text-align:center;padding:25px;">
          <button type="button" id="btn-calcular" class="button" disabled>
            <i class="fa-solid fa-eye"></i> Ver Resumen y Continuar al Pago
          </button>
          <p class="texto-paquete" id="hint-calcular">Completa todos los campos para activar este boton</p>
        </div>
      </div>

    </form>
  </div>

  <div id="bloque-resumen" style="display:none;">
    <div class="resumen-completo caja">
      <h4><i class="fa-solid fa-clipboard-list"></i> Verifica tus Datos Antes de Pagar</h4>
      <div class="resumen-grid">
        <div class="resumen-col">
          <h5 class="resumen-subtitulo"><i class="fa-solid fa-user"></i> Datos Personales</h5>
          <table class="tabla-resumen">
            <tr><td class="lbl">Nombre completo</td><td id="res-nombre-completo">—</td></tr>
            <tr><td class="lbl">Carnet</td><td id="res-carnet">—</td></tr>
            <tr><td class="lbl">Edad</td><td id="res-edad">—</td></tr>
            <tr><td class="lbl">Celular</td><td id="res-celular">—</td></tr>
            <tr><td class="lbl">Tipo de inscrito</td><td id="res-tipo">—</td></tr>
          </table>
          <h5 class="resumen-subtitulo" style="margin-top:20px;"><i class="fa-solid fa-church"></i> Datos de Iglesia</h5>
          <table class="tabla-resumen">
            <tr><td class="lbl">Iglesia</td><td id="res-iglesia">—</td></tr>
            <tr><td class="lbl">Distrito</td><td id="res-distrito">—</td></tr>
          </table>
        </div>
        <div class="resumen-col">
          <h5 class="resumen-subtitulo"><i class="fa-solid fa-box-open"></i> Inscripcion</h5>
          <table class="tabla-resumen">
            <tr><td class="lbl">Paquete</td><td id="res-paquete">—</td></tr>
            <tr><td class="lbl">Precio paquete</td><td id="res-precio-paquete">—</td></tr>
            <?php if($regalo_id): ?>
            <tr><td class="lbl">Regalo incluido</td><td><?php echo htmlspecialchars($regalo_nombre); ?></td></tr>
            <?php endif; ?>
          </table>
          <div class="resumen-total-wrap">
            <span class="resumen-total-lbl">TOTAL A PAGAR</span>
            <span class="resumen-total-num" id="res-total">Bs. 0.00</span>
          </div>
        </div>
      </div>
      <div class="resumen-acciones">
        <button type="button" id="btn-editar" class="button hollow">
          <i class="fa-solid fa-pen-to-square"></i> Editar mis datos
        </button>
        <button type="button" id="btn-ir-pago" class="button">
          <i class="fa-solid fa-qrcode"></i> Todo esta correcto, ir al pago
        </button>
      </div>
    </div>
  </div>

  <?php
  /* leer imagen QR de la BD */
  $qr_imagen = 'comprobante1.jpeg'; /* valor por defecto */
  $res_qr = $conn->query("SELECT valor FROM config_sistema WHERE clave='qr_imagen' LIMIT 1");
  if($res_qr && $row_qr = $res_qr->fetch_assoc()){
      $qr_imagen = $row_qr['valor'];
  }
  $qr_src = 'img/' . htmlspecialchars($qr_imagen);
  ?>



  <div id="seccion-qr" style="display:none;">
    <div class="seccion-qr">
      <h3><i class="fa-solid fa-qrcode"></i> Realiza tu Pago por QR</h3>
      <div style="background:#2C2668;color:#fff;border-radius:10px;padding:14px 24px;text-align:center;margin-bottom:20px;display:inline-block;">
        <span style="font-size:0.85em;letter-spacing:1px;text-transform:uppercase;opacity:0.75;">Total a Pagar</span>
        <div style="font-size:2em;font-weight:bold;" id="qr-total-mostrar">Bs. 0.00</div>
      </div>
      <div class="qr-contenido">
        <div class="qr-imagen-wrap">
          <p class="qr-instruccion"><i class="fa-solid fa-circle-info"></i> Descarga y Escanea este QR con tu app de banca movil, no olvides poner tu nombre en el comprobante</p>
          <img src="<?php echo $qr_src; ?>" alt="QR de pago" class="qr-imagen">
          <a href="<?php echo $qr_src; ?>" download="QR-Cumbre.jpeg" class="button hollow">
            <i class="fa-solid fa-download"></i> Descargar QR
          </a>
        </div>
        <div class="qr-subir">
          <p class="qr-instruccion"><i class="fa-solid fa-upload"></i> Luego sube aqui tu comprobante:</p>
          <div class="campo">
            <label>Comprobante de pago <span class="req">*</span></label>
            <input type="file" id="comprobante" accept="image/*,.pdf">
            <span class="campo-error" id="err-comprobante"></span>
          </div>
          <button type="button" id="btn-subir" class="button" disabled>
            <i class="fa-solid fa-upload"></i> Subir Comprobante
          </button>
          <div id="msg-subida" style="display:none;"></div>
          <button type="button" id="btn-registrar" class="button" disabled style="margin-top:15px;width:100%;">
            <i class="fa-solid fa-check"></i> Confirmar Inscripcion
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="seccion-form caja buscador-estado" style="margin-top:50px;">
      <h4><i class="fa-solid fa-magnifying-glass"></i> Consulta el estado de tu inscripcion</h4>
      <p style="text-align:center;color:#666;font-size:0.9em;">Ingresa tu carnet o celular para ver si tu inscripcion fue confirmada</p>
      <div class="campo" style="max-width:400px;margin:0 auto;">
        <label>Carnet o Celular</label>
        <input type="text" id="buscar-inscrito" placeholder="Ej: 1234567 o 70000000">
      </div>
      <div style="text-align:center;margin-top:10px;">
        <button type="button" id="btn-buscar" class="button hollow">
          <i class="fa-solid fa-search"></i> Consultar Estado
        </button>
      </div>
      <div id="resultado-busqueda" style="margin-top:20px;"></div>
      <div id="credencial-wrap" style="display:none;text-align:center;margin-top:20px;padding:20px;background:#F4F2F0;border:2px solid #2C2668;border-radius:12px;">
        <i class="fa-solid fa-id-card" style="font-size:2em;color:#2C2668;display:block;margin-bottom:10px;"></i>
        <p style="font-weight:bold;color:#2C2668;margin-bottom:14px;">Tu inscripcion esta confirmada. Puedes descargar tu credencial:</p>
        <button type="button" id="btn-descargar-credencial" class="button">
          <i class="fa-solid fa-download"></i> Descargar Credencial PDF
        </button>
      </div>
    </div>

</section>

<?php include_once 'includes/templates/footer.php'; ?>