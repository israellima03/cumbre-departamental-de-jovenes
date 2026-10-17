<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
ob_start();
require_once('funciones/sesiones.php');
usuario_autentificado();
ob_clean();
header('Content-Type: application/json');
require_once('../includes/funciones/bd_conexion.php');

$accion     = trim($_GET['accion'] ?? $_POST['accion'] ?? '');
$usuario_id = intval($_SESSION['usuario_id'] ?? 0);
 
switch($accion){
 
  case 'stats':
    $total       = $conn->query("SELECT COUNT(*) AS c FROM inscripciones")->fetch_assoc()['c'];
    $pendientes  = $conn->query("SELECT COUNT(*) AS c FROM inscripciones WHERE estado_pago='pendiente'")->fetch_assoc()['c'];
    $confirmados = $conn->query("SELECT COUNT(*) AS c FROM inscripciones WHERE estado_pago='confirmado'")->fetch_assoc()['c'];
    $hoy         = $conn->query("SELECT COUNT(*) AS c FROM inscripciones WHERE DATE(fecha_pago)=CURDATE()")->fetch_assoc()['c'];
    $cupos_disp  = $conn->query("SELECT COALESCE(SUM(cupos_disponibles),0) AS c FROM paquetes")->fetch_assoc()['c'];
    $cupos_tot   = $conn->query("SELECT COALESCE(SUM(cupo_total),0) AS c FROM paquetes")->fetch_assoc()['c'];
    echo json_encode(['ok'=>true,'total'=>$total,'pendientes'=>$pendientes,
      'confirmados'=>$confirmados,'hoy'=>$hoy,'cupos_disp'=>$cupos_disp,'cupos_total'=>$cupos_tot]);
    break;
 
  case 'listar_inscritos':
      $res = $conn->query("
        SELECT
          i.id, i.nombre, i.apellido, i.carnet, i.celular,
          i.edad,
          ig.nombre  AS iglesia,  ig.id AS iglesia_id,
          d.nombre   AS distrito, d.id  AS distrito_id,
          p.nombre   AS paquete,
          ins.id     AS inscripcion_id,
          ins.estado_pago, ins.metodo_pago, ins.fecha_pago, ins.precio_final,
          ur.nombre  AS registro_por,
          uc.nombre  AS confirmo_por,
          ir.material_entregado,
          ir.material_entregado_por AS mat_ent_por_id
        FROM inscritos i
        INNER JOIN inscripciones ins ON ins.inscrito_id = i.id
        LEFT JOIN iglesias ig    ON ig.id  = i.iglesia_id
        LEFT JOIN distritos d    ON d.id   = i.distrito_id
        LEFT JOIN paquetes p     ON p.id   = ins.paquete_id
        LEFT JOIN usuarios ur    ON ur.id  = ins.registrado_por
        LEFT JOIN usuarios uc    ON uc.id  = ins.confirmado_por
        LEFT JOIN inscripcion_regalos ir   ON ir.inscripcion_id = ins.id
        GROUP BY ins.id, i.id, ig.id, d.id, p.id, ur.id, uc.id, ir.id
        ORDER BY ins.fecha_pago DESC
      ");
      $rows = [];
      while($r = $res->fetch_assoc()){
        /* resolver nombres de quién entregó en PHP en vez de SQL */
        $mat_por_id  = intval($r['mat_ent_por_id']  ?? 0);
        if($mat_por_id > 0){
          $u = $conn->query("SELECT nombre FROM usuarios WHERE id=$mat_por_id LIMIT 1")->fetch_assoc();
          $r['material_entregado_por'] = $u ? $u['nombre'] : '—';
        } else {
          $r['material_entregado_por'] = null;
        }
        $rows[] = $r;
      }
     echo json_encode(['ok'=>true,'inscritos'=>$rows]);
      break;
 
  case 'registrar_efectivo':
    /* verificar si inscripciones están activas */
    $cfg = $conn->query("SELECT valor FROM config_sistema WHERE clave='inscripciones_activas' LIMIT 1")->fetch_assoc();
    if($cfg && $cfg['valor'] == '0'){
      echo json_encode(['ok'=>false,'msg'=>'Las inscripciones están pausadas. Actívalas desde Gestión General.']);
      exit;
    }
    $nombre    = trim($_POST['nombre']    ?? '');
    $apellido  = trim($_POST['apellido']  ?? '');
    $carnet    = trim($_POST['carnet']    ?? '');
    $edad      = intval($_POST['edad']    ?? 0);
    $celular   = trim($_POST['celular']   ?? '');
    $tipo_id   = intval($_POST['tipo_inscrito_id'] ?? 0);
    $igl_id    = intval($_POST['iglesia_id']  ?? 0);
    $dis_id    = intval($_POST['distrito_id'] ?? 0);
    $paq_id    = intval($_POST['paquete_id']  ?? 0);
 
    if(!$nombre||!$apellido||!$carnet||!$celular||!$paq_id){
      echo json_encode(['ok'=>false,'msg'=>'Faltan datos obligatorios']); exit;
    }
 
    $stmt=$conn->prepare("SELECT id FROM inscritos WHERE carnet=? LIMIT 1");
    $stmt->bind_param('s',$carnet); $stmt->execute(); $stmt->store_result();
    if($stmt->num_rows>0){ echo json_encode(['ok'=>false,'msg'=>'Ya existe un inscrito con ese carnet']); exit; }
    $stmt->close();
 
    $paqData=$conn->query("
      SELECT p.precio, ROUND(p.precio-(p.precio*COALESCE(d.porcentaje,0)/100),2) AS precio_final
      FROM paquetes p
      LEFT JOIN paquete_descuentos pd ON pd.paquete_id=p.id
      LEFT JOIN descuentos d ON d.id=pd.descuento_id AND d.activo=1
      WHERE p.id=$paq_id LIMIT 1
    ")->fetch_assoc();
    $precio_orig=floatval($paqData['precio']??0);
    $precio_paq =floatval($paqData['precio_final']??$precio_orig);
    $descuento  =round($precio_orig-$precio_paq,2);
 
    $precio_final=round($precio_paq,2);
 
    $conn->begin_transaction();
    try {
      $stmt=$conn->prepare("INSERT INTO inscritos (nombre,apellido,carnet,edad,celular,iglesia_id,distrito_id,tipo_inscrito_id) VALUES(?,?,?,?,?,?,?,?)");
      $stmt->bind_param('sssisiii',$nombre,$apellido,$carnet,$edad,$celular,$igl_id,$dis_id,$tipo_id);
      if(!$stmt->execute()) throw new RuntimeException('Error inscrito');
      $inscrito_id=$conn->insert_id; $stmt->close();
 
      /* queda pendiente hasta que tesorería confirme la entrega del dinero */
      $stmt=$conn->prepare("INSERT INTO inscripciones (inscrito_id,paquete_id,precio_original,precio_paquete,descuento_aplicado,precio_final,metodo_pago,estado_pago,fecha_pago,registrado_por) VALUES(?,?,?,?,?,?,'efectivo','pendiente',NOW(),?)");
      $stmt->bind_param('iiddddi',$inscrito_id,$paq_id,$precio_orig,$precio_paq,$descuento,$precio_final,$usuario_id);
      if(!$stmt->execute()) throw new RuntimeException('Error inscripcion');
      $inscripcion_id=$conn->insert_id; $stmt->close();
 
      /* regalo: el primero con cupos disponibles */
      $regal=$conn->query("SELECT id FROM regalos WHERE cupos_disponibles>0 ORDER BY id LIMIT 1")->fetch_assoc();
      if($regal){
        $regalo_id=intval($regal['id']);
        $stmt=$conn->prepare("INSERT INTO inscripcion_regalos (inscripcion_id,regalo_id,material_entregado,material_entregado_por) VALUES(?,?,0,NULL)");
        $stmt->bind_param('ii',$inscripcion_id,$regalo_id);
        $stmt->execute(); $stmt->close();
        $conn->query("UPDATE regalos SET cupos_disponibles=cupos_disponibles-1 WHERE id=$regalo_id AND cupos_disponibles>0");
      }
 
      $conn->query("UPDATE paquetes SET cupos_disponibles=cupos_disponibles-1 WHERE id=$paq_id AND cupos_disponibles>0");
      $conn->commit();
 
      echo json_encode(['ok'=>true,'msg'=>'Inscripcion registrada. Queda PENDIENTE hasta que tesoreria confirme el pago. Si no se paga lo antes posible, el cupo sera liberado.','inscrito_id'=>$inscrito_id]);
 
    } catch(RuntimeException $e){
      $conn->rollback();
      echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
    }
    break;
 
  case 'editar_inscrito':
    $id=intval($_POST['id']??0);
    $nombre=trim($_POST['nombre']??'');
    $apellido=trim($_POST['apellido']??'');
    $carnet=trim($_POST['carnet']??'');
    $edad=intval($_POST['edad']??0);
    $celular=trim($_POST['celular']??'');
    if(!$id||!$nombre){ echo json_encode(['ok'=>false,'msg'=>'Datos incompletos']); exit; }
    $stmt=$conn->prepare("UPDATE inscritos SET nombre=?,apellido=?,carnet=?,edad=?,celular=? WHERE id=?");
    $stmt->bind_param('sssisi',$nombre,$apellido,$carnet,$edad,$celular,$id);
    $stmt->execute(); $stmt->close();
    echo json_encode(['ok'=>true,'msg'=>'Inscrito actualizado']);
    break;
 
  case 'marcar_material_entregado':
    $inscripcion_id=intval($_POST['inscripcion_id']??0);
    $entregado=intval($_POST['entregado']??0);
    if($entregado){
      $conn->query("UPDATE inscripcion_regalos SET material_entregado=1, material_entregado_por=$usuario_id WHERE inscripcion_id=$inscripcion_id");
    } else {
      $conn->query("UPDATE inscripcion_regalos SET material_entregado=0, material_entregado_por=NULL WHERE inscripcion_id=$inscripcion_id");
    }
    echo json_encode(['ok'=>true,'msg'=>$entregado?'Material marcado como entregado':'Material desmarcado']);
    break;
 
  case 'buscar_inscrito':
    $q='%'.trim($_GET['q']??'').'%';
    $stmt=$conn->prepare("SELECT i.id, i.nombre, i.apellido, i.carnet, ins.id AS inscripcion_id FROM inscritos i INNER JOIN inscripciones ins ON ins.inscrito_id=i.id WHERE i.nombre LIKE ? OR i.apellido LIKE ? OR i.carnet LIKE ? LIMIT 8");
    $stmt->bind_param('sss',$q,$q,$q);
    $stmt->execute();
    $rows=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    echo json_encode(['ok'=>true,'inscritos'=>$rows]);
    break;
 
  case 'descargar_credencial':
    $inscrito_id=intval($_POST['inscrito_id']??0);
    if(!$inscrito_id){ echo json_encode(['ok'=>false,'msg'=>'ID requerido']); exit; }
    $r=$conn->query("SELECT i.*, ins.id AS inscripcion_id, ins.estado_pago FROM inscritos i INNER JOIN inscripciones ins ON ins.inscrito_id=i.id WHERE i.id=$inscrito_id LIMIT 1")->fetch_assoc();
    if(!$r){ echo json_encode(['ok'=>false,'msg'=>'No encontrado']); exit; }
    if($r['estado_pago']!=='confirmado'){ echo json_encode(['ok'=>false,'msg'=>'La credencial solo se genera cuando el pago esta confirmado']); exit; }
    $credencial=generarCredencialHTML($conn,$inscrito_id,$r['inscripcion_id']);
    echo json_encode(['ok'=>true,'credencial'=>$credencial]);
    break;
  case 'detalle_entrega':
    $inscripcion_id = intval($_GET['inscripcion_id'] ?? 0);
    $res = $conn->query("
        SELECT r.nombre, 1 AS cantidad, '' AS talla, '' AS genero
        FROM inscripcion_regalos ir
        INNER JOIN regalos r ON r.id = ir.regalo_id
        WHERE ir.inscripcion_id = $inscripcion_id
    ");
    $rows = [];
    while($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode(['ok'=>true,'items'=>$rows]);
    break;
  case 'eliminar_inscrito':
    /* solo administrador */
    if(!puede(['Administrador'])){
      echo json_encode(['ok'=>false,'msg'=>'Sin permisos']);
      exit;
    }
    $inscrito_id = intval($_POST['inscrito_id'] ?? 0);
    if(!$inscrito_id){ echo json_encode(['ok'=>false,'msg'=>'ID requerido']); exit; }

    /* obtener inscripcion */
    $ins = $conn->query("SELECT id, paquete_id FROM inscripciones WHERE inscrito_id=$inscrito_id LIMIT 1")->fetch_assoc();

    if($ins){
      $inscripcion_id = $ins['id'];
      $paquete_id     = $ins['paquete_id'];

      /* recuperar cupos de regalos */
      $conn->query("UPDATE regalos r
        INNER JOIN inscripcion_regalos ir ON ir.regalo_id = r.id
        SET r.cupos_disponibles = r.cupos_disponibles + 1
        WHERE ir.inscripcion_id = $inscripcion_id");

      /* recuperar cupo paquete */
      if($paquete_id){
        $conn->query("UPDATE paquetes SET cupos_disponibles=cupos_disponibles+1 WHERE id=$paquete_id");
      }

      /* eliminar registros relacionados */
      $conn->query("DELETE FROM inscripcion_regalos WHERE inscripcion_id=$inscripcion_id");

      /* eliminar credenciales generadas */
      $comprobante = $conn->query("SELECT comprobante_qr FROM inscripciones WHERE id=$inscripcion_id LIMIT 1")->fetch_assoc();
      if($comprobante && $comprobante['comprobante_qr']){
        $ruta_comp = dirname(__DIR__) . '/comprobantes/' . $comprobante['comprobante_qr'];
        if(file_exists($ruta_comp)) @unlink($ruta_comp);
      }

      $conn->query("DELETE FROM inscripciones WHERE id=$inscripcion_id");
    }

    /* eliminar inscrito */
    $conn->query("DELETE FROM inscritos WHERE id=$inscrito_id");

    echo json_encode(['ok'=>true,'msg'=>'Inscrito eliminado correctamente y cupos recuperados']);
    break;
 
  default:
    echo json_encode(['ok'=>false,'msg'=>'Accion no valida']);
}
 

function generarCredencialHTML($conn,$inscrito_id,$inscripcion_id){
  $r=$conn->query("
    SELECT i.nombre,i.apellido,i.carnet,i.celular,i.edad,
           ins.precio_final,ins.metodo_pago,ins.estado_pago,ins.fecha_pago,
           p.nombre AS paquete,ig.nombre AS iglesia,d.nombre AS distrito,t.nombre AS tipo
    FROM inscritos i
    INNER JOIN inscripciones ins ON ins.id=$inscripcion_id
    LEFT JOIN paquetes p ON p.id=ins.paquete_id
    LEFT JOIN iglesias ig ON ig.id=i.iglesia_id
    LEFT JOIN distritos d ON d.id=i.distrito_id
    LEFT JOIN tipos_inscrito t ON t.id=i.tipo_inscrito_id
    WHERE i.id=$inscrito_id LIMIT 1
  ")->fetch_assoc();
  if(!$r) return null;

  /* ── GENERAR CÓDIGO QR ÚNICO ── */
  $codigo_qr = bin2hex(random_bytes(16)); // 32 caracteres únicos
  $conn->query("UPDATE inscripciones SET codigo_qr='$codigo_qr' WHERE id=$inscripcion_id");

  /* URL que apunta al verificador */
  $base_url = 'https://mjoruro.com/ver-credencial.php?code=' . $codigo_qr;
  $qr_url   = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . urlencode($base_url);

  $carpeta  = dirname(__DIR__) . '/credenciales/';
  if(!is_dir($carpeta)) mkdir($carpeta, 0755, true);   

  $slug=preg_replace('/[^a-z0-9]/','_',strtolower($r['carnet']));
  $archivo='credencial_'.$slug.'_'.time().'.html';
  $ruta=$carpeta.$archivo;

  $iniciales=strtoupper(substr($r['nombre'],0,1).substr($r['apellido'],0,1));
  $fecha_pago=$r['fecha_pago']?date('d/m/Y H:i',strtotime($r['fecha_pago'])):'—';
  $carnet_safe=htmlspecialchars($r['carnet']);
  $apellido_safe=htmlspecialchars($r['apellido']);

  $html='<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
<title>Credencial — '.$r['nombre'].' '.$r['apellido'].'</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:Arial,sans-serif;background:#F4F2F0;display:flex;flex-direction:column;align-items:center;padding:30px 16px;min-height:100vh;}
.acciones{margin-bottom:18px;display:flex;gap:12px;flex-wrap:wrap;justify-content:center;}
.btn{display:inline-flex;align-items:center;gap:8px;padding:11px 24px;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;}
.btn-pdf{background:#2C2668;color:#fff;}
.btn-pdf:hover{background:#E8750A;}
.btn-print{background:#10b981;color:#fff;}
.credencial{width:100%;max-width:420px;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.15);}
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
    <div class="avatar">'.$iniciales.'</div>
    <div class="nombre-txt">'.htmlspecialchars($r['nombre'].' '.$r['apellido']).'</div>
    <div class="subtitulo">Cumbre Departamental de Jóvenes — Oruro 2026</div>
  </div>
  <div class="body">
    <div class="row"><span class="lbl">Carnet</span><span class="val">'.htmlspecialchars($r['carnet']).'</span></div>
    <div class="row"><span class="lbl">Celular</span><span class="val">'.htmlspecialchars($r['celular']).'</span></div>
    <div class="row"><span class="lbl">Edad</span><span class="val">'.($r['edad']?$r['edad'].' años':'—').'</span></div>
    <div class="row"><span class="lbl">Tipo</span><span class="val">'.htmlspecialchars($r['tipo']??'—').'</span></div>
    <div class="row"><span class="lbl">Iglesia</span><span class="val">'.htmlspecialchars($r['iglesia']??'—').'</span></div>
    <div class="row"><span class="lbl">Distrito</span><span class="val">'.htmlspecialchars($r['distrito']??'—').'</span></div>
    <div class="row"><span class="lbl">Paquete</span><span class="val">'.htmlspecialchars($r['paquete']??'—').'</span></div>
    <div class="row"><span class="lbl">Total pagado</span><span class="val">Bs. '.number_format(floatval($r['precio_final']),0).'</span></div>
    <div class="row"><span class="lbl">Método pago</span><span class="val">'.strtoupper($r['metodo_pago']).'</span></div>
    <div class="row"><span class="lbl">Estado</span><span class="val"><span class="estado-ok">CONFIRMADO</span></span></div>
    <div class="row"><span class="lbl">Fecha</span><span class="val">'.$fecha_pago.'</span></div>
  </div>
  <div class="qr-wrap">
    <div class="qr-lbl">Escanea para verificar credencial</div>
    <img class="qr-img" src="'.$qr_url.'" alt="QR Credencial" crossorigin="anonymous">
    <div class="qr-code-txt">Código: '.$codigo_qr.'</div>
  </div>
  <div class="pie">Sistema de inscripciones IDDP Oruro · Lima Technology</div>
</div>
<script>
function descargarPDF(){
  var el=document.getElementById("credencial-contenido");
  html2pdf().set({
    margin:2,
    filename:"credencial_'.$carnet_safe.'_'.$apellido_safe.'.pdf",
    image:{type:"jpeg",quality:0.98},
    html2canvas:{scale:2,useCORS:true,allowTaint:false},
    jsPDF:{unit:"mm",format:[85,130],orientation:"portrait"}
  }).from(el).save();
}
</script>
</body></html>';

  file_put_contents($ruta,$html);
  return $archivo;
}
