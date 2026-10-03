<?php
if(!function_exists('generarCredencialHTML')){
function generarCredencialHTML($conn, $inscrito_id, $inscripcion_id){
  $r = $conn->query("
    SELECT i.nombre, i.apellido, i.carnet, i.celular, i.edad,
           ins.precio_final, ins.metodo_pago, ins.estado_pago, ins.fecha_pago,
           p.nombre AS paquete, ig.nombre AS iglesia, d.nombre AS distrito,
           t.nombre AS tipo
    FROM inscritos i
    INNER JOIN inscripciones ins ON ins.id = $inscripcion_id
    LEFT JOIN paquetes p         ON p.id  = ins.paquete_id
    LEFT JOIN iglesias ig        ON ig.id = i.iglesia_id
    LEFT JOIN distritos d        ON d.id  = i.distrito_id
    LEFT JOIN tipos_inscrito t   ON t.id  = i.tipo_inscrito_id
    WHERE i.id = $inscrito_id LIMIT 1
  ")->fetch_assoc();
  if(!$r) return null;

  $codigo_qr = bin2hex(random_bytes(16));
  $conn->query("UPDATE inscripciones SET codigo_qr='$codigo_qr' WHERE id=$inscripcion_id");

  $base_url = 'https://mjoruro.com/ver-credencial.php?code=' . $codigo_qr;
  $qr_url   = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . urlencode($base_url);

  $carpeta  = dirname(__DIR__) . '/credenciales/';
  if(!is_dir($carpeta)) mkdir($carpeta, 0755, true);

  $slug    = preg_replace('/[^a-z0-9]/','_', strtolower($r['carnet']));
  $archivo = 'credencial_' . $slug . '_' . time() . '.html';
  $ruta    = $carpeta . $archivo;

  $iniciales  = strtoupper(substr($r['nombre'],0,1) . substr($r['apellido'],0,1));
  $fecha_pago = $r['fecha_pago'] ? date('d/m/Y H:i', strtotime($r['fecha_pago'])) : '—';
  $carnet_safe   = htmlspecialchars($r['carnet']);
  $apellido_safe = htmlspecialchars($r['apellido']);

  $html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Credencial — ' . $r['nombre'] . ' ' . $r['apellido'] . '</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:Arial,sans-serif;background:#F4F2F0;display:flex;flex-direction:column;align-items:center;padding:30px 16px;min-height:100vh;}
.acciones{margin-bottom:18px;display:flex;gap:12px;flex-wrap:wrap;justify-content:center;}
.btn{display:inline-flex;align-items:center;gap:8px;padding:11px 24px;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;}
.btn-pdf{background:#2C2668;color:#fff;}.btn-pdf:hover{background:#E8750A;}
.btn-print{background:#10b981;color:#fff;}
.credencial{width:100%;max-width:420px;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.15);}
.cab{background:#2C2668;padding:10px 14px 8px;text-align:center;color:#fff;}
.avatar{width:44px;height:44px;border-radius:50%;background:#E8750A;display:flex;align-items:center;justify-content:center;font-size:16px;color:#fff;font-weight:700;margin:0 auto 6px;}
.nombre-txt{font-size:13px;font-weight:700;margin-bottom:2px;}
.subtitulo{font-size:9px;color:rgba(255,255,255,.6);}
.body{padding:6px 12px;}
.row{display:flex;justify-content:space-between;align-items:center;padding:2px 0;border-bottom:1px solid #F4F2F0;}
.row:last-child{border-bottom:none;}
.lbl{font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#9ca3af;}
.val{font-size:9px;font-weight:600;color:#2C2668;text-align:right;max-width:60%;}
.estado-ok{display:inline-block;background:#d1fae5;color:#065f46;border:1px solid #10b981;border-radius:20px;padding:1px 8px;font-size:8px;font-weight:700;}
.qr-wrap{border-top:1px dashed #E7E2DF;margin:0;padding:6px 12px;text-align:center;background:#F4F2F0;}
.qr-lbl{font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#9ca3af;margin-bottom:4px;}
.qr-img{width:80px;height:80px;border:2px solid #2C2668;border-radius:6px;padding:2px;background:#fff;}
.qr-code-txt{font-size:7px;color:#9ca3af;margin-top:4px;letter-spacing:.5px;word-break:break-all;}
.pie{background:#F4F2F0;padding:5px;text-align:center;font-size:8px;color:#9ca3af;}
@media print{.acciones{display:none;}}
</style>
</head><body>
<div class="acciones">
  <button class="btn btn-pdf" onclick="descargarPDF()">⬇ Descargar PDF</button>
  <button class="btn btn-print" onclick="window.print()">🖨 Imprimir</button>
</div>
<div class="credencial" id="credencial-contenido">
  <div class="cab">
    <div class="avatar">' . $iniciales . '</div>
    <div class="nombre-txt">' . htmlspecialchars($r['nombre'].' '.$r['apellido']) . '</div>
    <div class="subtitulo">Cumbre Departamental de Jóvenes — Oruro 2026</div>
  </div>
  <div class="body">
    <div class="row"><span class="lbl">Carnet</span><span class="val">' . htmlspecialchars($r['carnet']) . '</span></div>
    <div class="row"><span class="lbl">Celular</span><span class="val">' . htmlspecialchars($r['celular']) . '</span></div>
    <div class="row"><span class="lbl">Edad</span><span class="val">' . ($r['edad'] ? $r['edad'].' años' : '—') . '</span></div>
    <div class="row"><span class="lbl">Tipo</span><span class="val">' . htmlspecialchars($r['tipo']??'—') . '</span></div>
    <div class="row"><span class="lbl">Iglesia</span><span class="val">' . htmlspecialchars($r['iglesia']??'—') . '</span></div>
    <div class="row"><span class="lbl">Distrito</span><span class="val">' . htmlspecialchars($r['distrito']??'—') . '</span></div>
    <div class="row"><span class="lbl">Paquete</span><span class="val">' . htmlspecialchars($r['paquete']??'—') . '</span></div>
    <div class="row"><span class="lbl">Total pagado</span><span class="val">Bs. ' . number_format(floatval($r['precio_final']),0) . '</span></div>
    <div class="row"><span class="lbl">Método</span><span class="val">' . strtoupper($r['metodo_pago']) . '</span></div>
    <div class="row"><span class="lbl">Estado</span><span class="val"><span class="estado-ok">CONFIRMADO</span></span></div>
    <div class="row"><span class="lbl">Fecha</span><span class="val">' . $fecha_pago . '</span></div>
  </div>
  <div class="qr-wrap">
    <div class="qr-lbl">Escanea para verificar credencial</div>
    <img class="qr-img" src="' . $qr_url . '" alt="QR" crossorigin="anonymous">
    <div class="qr-code-txt">Código: ' . $codigo_qr . '</div>
  </div>
  <div class="pie">Sistema de inscripciones IDDP Oruro · Lima Technology</div>
</div>
<script>
function descargarPDF(){
  html2pdf().set({
    margin:2,
    filename:"credencial_' . $carnet_safe . '_' . $apellido_safe . '.pdf",
    image:{type:"jpeg",quality:0.98},
    html2canvas:{scale:2,useCORS:true,allowTaint:false},
    jsPDF:{unit:"mm",format:[85,130],orientation:"portrait"}
  }).from(document.getElementById("credencial-contenido")).save();
}
</script>
</body></html>';

  file_put_contents($ruta, $html);
  return $archivo;
}
}