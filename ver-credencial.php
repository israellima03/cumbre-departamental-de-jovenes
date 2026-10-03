<?php
require_once('includes/funciones/bd_conexion.php');

$code = trim($_GET['code'] ?? '');
if(!$code) die('<p style="font-family:Arial;text-align:center;padding:60px;color:#E8750A;">Código inválido.</p>');

$stmt = $conn->prepare("
    SELECT i.nombre, i.apellido, i.carnet, i.celular, i.edad,
           ins.estado_pago, ins.metodo_pago, ins.fecha_pago, ins.precio_final,
           p.nombre AS paquete, ig.nombre AS iglesia, d.nombre AS distrito,
           t.nombre AS tipo
    FROM inscripciones ins
    INNER JOIN inscritos i       ON i.id  = ins.inscrito_id
    LEFT JOIN paquetes p         ON p.id  = ins.paquete_id
    LEFT JOIN iglesias ig        ON ig.id = i.iglesia_id
    LEFT JOIN distritos d        ON d.id  = i.distrito_id
    LEFT JOIN tipos_inscrito t   ON t.id  = i.tipo_inscrito_id
    WHERE ins.codigo_qr = ?
    LIMIT 1
");
$stmt->bind_param('s', $code);
$stmt->execute();
$r = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$r) die('<p style="font-family:Arial;text-align:center;padding:60px;color:#E8750A;">QR no encontrado.</p>');

$iniciales  = strtoupper(substr($r['nombre'],0,1).substr($r['apellido'],0,1));
$fecha_pago = $r['fecha_pago'] ? date('d/m/Y H:i', strtotime($r['fecha_pago'])) : '—';
$confirmado = $r['estado_pago'] === 'confirmado';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Credencial — <?php echo htmlspecialchars($r['nombre'].' '.$r['apellido']); ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:Arial,sans-serif;background:#F4F2F0;display:flex;flex-direction:column;align-items:center;padding:24px 16px;min-height:100vh;}
.credencial{width:100%;max-width:420px;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.15);}
.cab{background:#2C2668;padding:24px 20px 18px;text-align:center;color:#fff;}
.avatar{width:70px;height:70px;border-radius:50%;background:#E8750A;display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:700;color:#fff;margin:0 auto 10px;}
.nombre-txt{font-size:19px;font-weight:700;margin-bottom:4px;}
.subtitulo{font-size:11px;color:rgba(255,255,255,.6);}
.banner{padding:10px 20px;text-align:center;font-weight:700;font-size:13px;letter-spacing:1px;}
.banner-ok{background:#d1fae5;color:#065f46;}
.banner-pend{background:#fef9c3;color:#713f12;}
.body{padding:16px 20px;}
.row{display:flex;justify-content:space-between;align-items:flex-start;padding:7px 0;border-bottom:1px solid #F4F2F0;}
.row:last-child{border-bottom:none;}
.lbl{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#9ca3af;flex-shrink:0;padding-right:8px;}
.val{font-size:13px;font-weight:600;color:#2C2668;text-align:right;}
.pie{background:#F4F2F0;padding:10px;text-align:center;font-size:10px;color:#9ca3af;}
</style>
</head>
<body>
<div class="credencial">
  <div class="cab">
    <div class="avatar"><?php echo $iniciales; ?></div>
    <div class="nombre-txt"><?php echo htmlspecialchars($r['nombre'].' '.$r['apellido']); ?></div>
    <div class="subtitulo">Cumbre Departamental de Jóvenes — Oruro 2026</div>
  </div>
  <div class="banner <?php echo $confirmado ? 'banner-ok' : 'banner-pend'; ?>">
    <?php echo $confirmado ? '✓ INSCRIPCIÓN CONFIRMADA' : '⏳ PAGO PENDIENTE'; ?>
  </div>
  <div class="body">
    <div class="row"><span class="lbl">Carnet</span><span class="val"><?php echo htmlspecialchars($r['carnet']); ?></span></div>
    <div class="row"><span class="lbl">Celular</span><span class="val"><?php echo htmlspecialchars($r['celular']); ?></span></div>
    <div class="row"><span class="lbl">Edad</span><span class="val"><?php echo $r['edad'] ? $r['edad'].' años' : '—'; ?></span></div>
    <div class="row"><span class="lbl">Tipo</span><span class="val"><?php echo htmlspecialchars($r['tipo']??'—'); ?></span></div>
    <div class="row"><span class="lbl">Iglesia</span><span class="val"><?php echo htmlspecialchars($r['iglesia']??'—'); ?></span></div>
    <div class="row"><span class="lbl">Distrito</span><span class="val"><?php echo htmlspecialchars($r['distrito']??'—'); ?></span></div>
    <div class="row"><span class="lbl">Paquete</span><span class="val"><?php echo htmlspecialchars($r['paquete']??'—'); ?></span></div>
    <div class="row"><span class="lbl">Total</span><span class="val">Bs. <?php echo number_format(floatval($r['precio_final']),0); ?></span></div>
    <div class="row"><span class="lbl">Método</span><span class="val"><?php echo strtoupper($r['metodo_pago']); ?></span></div>
    <div class="row"><span class="lbl">Fecha</span><span class="val"><?php echo $fecha_pago; ?></span></div>
  </div>
  <div class="pie">Sistema de inscripciones IDDP Oruro · Lima Technology</div>
</div>
</body>
</html>