<?php
ini_set('display_errors', 0);
ob_start();
require_once('funciones/sesiones.php');
usuario_autentificado();
ob_clean();
header('Content-Type: application/json');
require_once('../includes/funciones/bd_conexion.php');

$accion = trim($_GET['accion'] ?? $_POST['accion'] ?? '');

switch($accion){

  /* ══════════════════════════════════════
     STATS INSCRITOS
  ══════════════════════════════════════ */
  case 'stats_inscritos':
    $total  = intval($conn->query("SELECT COUNT(*) c FROM inscripciones")->fetch_assoc()['c']);
    $conf   = intval($conn->query("SELECT COUNT(*) c FROM inscripciones WHERE estado_pago='confirmado'")->fetch_assoc()['c']);
    $pend   = intval($conn->query("SELECT COUNT(*) c FROM inscripciones WHERE estado_pago='pendiente'")->fetch_assoc()['c']);
    $recaud = floatval($conn->query("SELECT COALESCE(SUM(precio_final),0) c FROM inscripciones WHERE estado_pago='confirmado'")->fetch_assoc()['c']);
    echo json_encode(['ok'=>true,'total'=>$total,'confirmados'=>$conf,'pendientes'=>$pend,'recaudado'=>$recaud]);
    break;

  case 'por_iglesia':
    $res = $conn->query("
      SELECT COALESCE(ig.nombre,'Sin iglesia') AS iglesia,
             COUNT(*) AS total,
             SUM(ins.estado_pago='confirmado') AS confirmados
      FROM inscripciones ins
      INNER JOIN inscritos i ON i.id=ins.inscrito_id
      LEFT JOIN iglesias ig ON ig.id=i.iglesia_id
      GROUP BY ig.id ORDER BY total DESC
    ");
    $rows=[]; while($r=$res->fetch_assoc()){ $r['total']=intval($r['total']); $r['confirmados']=intval($r['confirmados']); $rows[]=$r; }
    echo json_encode(['ok'=>true,'datos'=>$rows]); break;

  case 'por_distrito':
    $res = $conn->query("
      SELECT COALESCE(d.nombre,'Sin distrito') AS distrito,
             COUNT(*) AS total,
             SUM(ins.estado_pago='confirmado') AS confirmados
      FROM inscripciones ins
      INNER JOIN inscritos i ON i.id=ins.inscrito_id
      LEFT JOIN distritos d ON d.id=i.distrito_id
      GROUP BY d.id ORDER BY total DESC
    ");
    $rows=[]; while($r=$res->fetch_assoc()){ $r['total']=intval($r['total']); $r['confirmados']=intval($r['confirmados']); $rows[]=$r; }
    echo json_encode(['ok'=>true,'datos'=>$rows]); break;

  case 'por_paquete':
    $res = $conn->query("
      SELECT COALESCE(p.nombre,'Sin paquete') AS paquete,
             COUNT(*) AS total,
             COALESCE(SUM(CASE WHEN ins.estado_pago='confirmado' THEN ins.precio_final ELSE 0 END),0) AS recaudado
      FROM inscripciones ins
      LEFT JOIN paquetes p ON p.id=ins.paquete_id
      GROUP BY p.id ORDER BY total DESC
    ");
    $rows=[]; while($r=$res->fetch_assoc()){ $r['total']=intval($r['total']); $r['recaudado']=floatval($r['recaudado']); $rows[]=$r; }
    echo json_encode(['ok'=>true,'datos'=>$rows]); break;

  case 'por_metodo':
    $res = $conn->query("
      SELECT metodo_pago AS metodo, COUNT(*) AS total,
             COALESCE(SUM(CASE WHEN estado_pago='confirmado' THEN precio_final ELSE 0 END),0) AS recaudado
      FROM inscripciones GROUP BY metodo_pago
    ");
    $rows=[]; while($r=$res->fetch_assoc()){ $r['total']=intval($r['total']); $r['recaudado']=floatval($r['recaudado']); $rows[]=$r; }
    echo json_encode(['ok'=>true,'datos'=>$rows]); break;

  case 'tabla_inscritos':
    $estado = trim($_GET['estado'] ?? '');
    $pago   = trim($_GET['pago']   ?? '');
    $where  = '1=1';
    if($estado) $where .= " AND ins.estado_pago='".addslashes($estado)."'";
    if($pago)   $where .= " AND ins.metodo_pago='".addslashes($pago)."'";
    $res = $conn->query("
      SELECT i.nombre, i.apellido, i.celular,
             COALESCE(ig.nombre,'—') AS iglesia,
             COALESCE(d.nombre,'—') AS distrito,
             COALESCE(p.nombre,'—') AS paquete,
             ins.metodo_pago, ins.estado_pago,
             CAST(ins.precio_final AS DECIMAL(10,0)) AS precio_final,
             COALESCE(ur.nombre,'—') AS registrado_por,
             COALESCE(uc.nombre,'—') AS confirmo_por
      FROM inscripciones ins
      INNER JOIN inscritos i ON i.id=ins.inscrito_id
      LEFT JOIN iglesias ig  ON ig.id=i.iglesia_id
      LEFT JOIN distritos d  ON d.id=i.distrito_id
      LEFT JOIN paquetes p   ON p.id=ins.paquete_id
      LEFT JOIN usuarios ur  ON ur.id=ins.registrado_por
      LEFT JOIN usuarios uc  ON uc.id=ins.confirmado_por
      WHERE $where ORDER BY ins.fecha_pago DESC
    ");
    $rows=[]; while($r=$res->fetch_assoc()){ $r['precio_final']=floatval($r['precio_final']); $rows[]=$r; }
    echo json_encode(['ok'=>true,'inscritos'=>$rows]); break;

  /* ══════════════════════════════════════
     STATS ENTREGAS
  ══════════════════════════════════════ */
  case 'stats_entregas':
    $me = $conn->query("SELECT SUM(material_entregado=1) ent, SUM(material_entregado=0) pend FROM inscripcion_regalos")->fetch_assoc();
    echo json_encode(['ok'=>true,
      'mat_entregados'=>intval($me['ent']),    'mat_pendientes'=>intval($me['pend'])]);
    break;

  case 'tabla_entregas':
    $estado = trim($_GET['estado'] ?? '');
    $w = '1=1';
    if($estado==='entregado') $w='ir.material_entregado=1';
    if($estado==='pendiente') $w='ir.material_entregado=0';
    $res = $conn->query("
      SELECT CONCAT(i.nombre,' ',i.apellido) AS inscrito,
             COALESCE(r.nombre,'—') AS item, 'Material' AS tipo_item,
             ir.material_entregado AS entregado,
             COALESCE(ue.nombre,'—') AS entregado_por
      FROM inscripcion_regalos ir
      INNER JOIN inscripciones ins ON ins.id=ir.inscripcion_id
      INNER JOIN inscritos i ON i.id=ins.inscrito_id
      LEFT JOIN regalos r ON r.id=ir.regalo_id
      LEFT JOIN usuarios ue ON ue.id=ir.material_entregado_por
      WHERE $w ORDER BY i.apellido
    ");
    $rows = [];
    while($r=$res->fetch_assoc()) $rows[]=$r;
    echo json_encode(['ok'=>true,'entregas'=>$rows]); break;

  /* ══════════════════════════════════════
     ECONOMÍA — sin balance
  ══════════════════════════════════════ */
  case 'stats_economia':
    $ing  = floatval($conn->query("SELECT COALESCE(SUM(precio_final),0) t FROM inscripciones WHERE estado_pago='confirmado'")->fetch_assoc()['t']);
    $ofr  = floatval($conn->query("SELECT COALESCE(SUM(monto),0) t FROM ofrendas_amor")->fetch_assoc()['t']);
    $gas  = floatval($conn->query("SELECT COALESCE(SUM(monto),0) t FROM gastos")->fetch_assoc()['t']);
    echo json_encode(['ok'=>true,'ingresos'=>$ing,'ofrendas'=>$ofr,'gastos'=>$gas]);
    break;

  /* por iglesia — recaudado */
  case 'rec_por_iglesia':
    $res = $conn->query("
      SELECT COALESCE(ig.nombre,'Sin iglesia') AS iglesia,
             COUNT(*) AS inscritos,
             COALESCE(SUM(CASE WHEN ins.estado_pago='confirmado' THEN ins.precio_final ELSE 0 END),0) AS recaudado
      FROM inscripciones ins
      INNER JOIN inscritos i ON i.id=ins.inscrito_id
      LEFT JOIN iglesias ig ON ig.id=i.iglesia_id
      GROUP BY ig.id ORDER BY recaudado DESC
    ");
    $rows=[]; while($r=$res->fetch_assoc()){ $r['inscritos']=intval($r['inscritos']); $r['recaudado']=floatval($r['recaudado']); $rows[]=$r; }
    echo json_encode(['ok'=>true,'datos'=>$rows]); break;

  /* por distrito — recaudado */
  case 'rec_por_distrito':
    $res = $conn->query("
      SELECT COALESCE(d.nombre,'Sin distrito') AS distrito,
             COUNT(*) AS inscritos,
             COALESCE(SUM(CASE WHEN ins.estado_pago='confirmado' THEN ins.precio_final ELSE 0 END),0) AS recaudado
      FROM inscripciones ins
      INNER JOIN inscritos i ON i.id=ins.inscrito_id
      LEFT JOIN distritos d ON d.id=i.distrito_id
      GROUP BY d.id ORDER BY recaudado DESC
    ");
    $rows=[]; while($r=$res->fetch_assoc()){ $r['inscritos']=intval($r['inscritos']); $r['recaudado']=floatval($r['recaudado']); $rows[]=$r; }
    echo json_encode(['ok'=>true,'datos'=>$rows]); break;

  /* por método — recaudado */
  case 'rec_por_metodo':
    $res = $conn->query("
      SELECT metodo_pago AS metodo, COUNT(*) AS total,
             COALESCE(SUM(CASE WHEN estado_pago='confirmado' THEN precio_final ELSE 0 END),0) AS recaudado
      FROM inscripciones GROUP BY metodo_pago ORDER BY recaudado DESC
    ");
    $rows=[]; while($r=$res->fetch_assoc()){ $r['total']=intval($r['total']); $r['recaudado']=floatval($r['recaudado']); $rows[]=$r; }
    echo json_encode(['ok'=>true,'datos'=>$rows]); break;

  /* ══════════════════════════════════════
     GASTOS
  ══════════════════════════════════════ */
  case 'listar_gastos':
    $res = $conn->query("
      SELECT g.*, COALESCE(u.nombre,'—') AS registrado_nom
      FROM gastos g
      LEFT JOIN usuarios u ON u.id=g.registrado_por
      ORDER BY g.fecha DESC
    ");
    $rows=[]; while($r=$res->fetch_assoc()){ $r['monto']=floatval($r['monto']); $rows[]=$r; }
    echo json_encode(['ok'=>true,'gastos'=>$rows]); break;

  case 'guardar_gasto':
    $id   = intval($_POST['id']          ?? 0);
    $mot  = trim($_POST['motivo']        ?? '');
    $mon  = floatval($_POST['monto']     ?? 0);
    $resp = trim($_POST['responsable']   ?? '');
    $fec  = trim($_POST['fecha']         ?? '');
    $uid  = intval($_SESSION['usuario_id'] ?? 0) ?: null;
    if(!$mot || $mon <= 0 || !$resp || !$fec){
      echo json_encode(['ok'=>false,'msg'=>'Completa todos los campos']); exit;
    }
    if($id > 0){
      $stmt = $conn->prepare("UPDATE gastos SET motivo=?,monto=?,responsable=?,fecha=? WHERE id=?");
      $stmt->bind_param('sdssi',$mot,$mon,$resp,$fec,$id);
    } else {
      $stmt = $conn->prepare("INSERT INTO gastos (motivo,monto,responsable,fecha,registrado_por) VALUES(?,?,?,?,?)");
      $stmt->bind_param('sdssi',$mot,$mon,$resp,$fec,$uid);
    }
    if(!$stmt->execute()){ echo json_encode(['ok'=>false,'msg'=>'Error BD: '.$stmt->error]); exit; }
    $stmt->close();
    echo json_encode(['ok'=>true,'msg'=>$id?'Gasto actualizado':'Gasto registrado']); break;

  case 'eliminar_gasto':
    $id = intval($_POST['id'] ?? 0);
    if(!$id){ echo json_encode(['ok'=>false,'msg'=>'ID requerido']); exit; }
    $conn->query("DELETE FROM gastos WHERE id=$id");
    echo json_encode(['ok'=>true,'msg'=>'Gasto eliminado']); break;

  /* ══════════════════════════════════════
     OFRENDAS
  ══════════════════════════════════════ */
  case 'listar_ofrendas':
    $res = $conn->query("
      SELECT o.*, COALESCE(u.nombre,'—') AS recibido_nom
      FROM ofrendas_amor o
      LEFT JOIN usuarios u ON u.id=o.recibido_por
      ORDER BY o.fecha DESC
    ");
    $rows=[]; while($r=$res->fetch_assoc()){ $r['monto']=floatval($r['monto']); $rows[]=$r; }
    echo json_encode(['ok'=>true,'ofrendas'=>$rows]); break;

  case 'guardar_ofrenda':
    $id  = intval($_POST['id']          ?? 0);
    $de  = trim($_POST['de_parte_de']   ?? '');
    $mon = floatval($_POST['monto']     ?? 0);
    $fec = trim($_POST['fecha']         ?? '');
    $not = trim($_POST['notas']         ?? '');
    $uid = intval($_SESSION['usuario_id'] ?? 0) ?: null;
    if(!$de || $mon <= 0 || !$fec){
      echo json_encode(['ok'=>false,'msg'=>'Completa todos los campos']); exit;
    }
    if($id > 0){
      $stmt = $conn->prepare("UPDATE ofrendas_amor SET de_parte_de=?,monto=?,fecha=?,notas=? WHERE id=?");
      $stmt->bind_param('sdssi',$de,$mon,$fec,$not,$id);
    } else {
      $stmt = $conn->prepare("INSERT INTO ofrendas_amor (de_parte_de,monto,fecha,notas,recibido_por) VALUES(?,?,?,?,?)");
      $stmt->bind_param('sdssi',$de,$mon,$fec,$not,$uid);
    }
    if(!$stmt->execute()){ echo json_encode(['ok'=>false,'msg'=>'Error BD: '.$stmt->error]); exit; }
    $stmt->close();
    echo json_encode(['ok'=>true,'msg'=>$id?'Ofrenda actualizada':'Ofrenda registrada']); break;

  case 'eliminar_ofrenda':
    $id = intval($_POST['id'] ?? 0);
    if(!$id){ echo json_encode(['ok'=>false,'msg'=>'ID requerido']); exit; }
    $conn->query("DELETE FROM ofrendas_amor WHERE id=$id");
    echo json_encode(['ok'=>true,'msg'=>'Ofrenda eliminada']); break;

  default:
    echo json_encode(['ok'=>false,'msg'=>'Accion no valida']);
}