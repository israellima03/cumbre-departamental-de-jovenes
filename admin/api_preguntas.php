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

  /* listar eventos con su estado de preguntas */
  case 'listar_eventos_preguntas':
    $res = $conn->query("
      SELECT e.id_evento, e.tipo_evento, e.hora_inicio, e.hora_fin,
             e.preguntas_activas,
             d.nombre AS dia,
             CONCAT(ex.nombre,' ',ex.apellido) AS expositor,
             g.nombre_grupo AS grupo,
             t.titulo AS tema,
             (SELECT COUNT(*) FROM preguntas_publico pp WHERE pp.id_evento=e.id_evento) AS total_preguntas
      FROM eventos e
      LEFT JOIN dias d            ON d.id_dia        = e.id_dia
      LEFT JOIN expositores ex    ON ex.id_expositor = e.id_expositor
      LEFT JOIN grupos_alabanza g ON g.id_grupo      = e.id_grupo
      LEFT JOIN temas t           ON t.id_tema        = e.id_tema
      ORDER BY e.fecha, e.hora_inicio
    ");
    $rows = [];
    while($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode(['ok'=>true,'eventos'=>$rows]);
    break;

  /* toggle preguntas activas de un evento */
  case 'toggle_preguntas':
    $id    = intval($_POST['id']    ?? 0);
    $valor = intval($_POST['valor'] ?? 0);
    if(!$id){ echo json_encode(['ok'=>false,'msg'=>'ID requerido']); exit; }
    $conn->query("UPDATE eventos SET preguntas_activas=$valor WHERE id_evento=$id");
    echo json_encode(['ok'=>true,'msg'=>$valor?'Preguntas activadas':'Preguntas desactivadas']);
    break;

  /* listar preguntas con filtros */
  case 'listar_preguntas':
    $filtro_evento = intval($_GET['evento'] ?? 0);
    $filtro_estado = trim($_GET['estado']   ?? '');
    $where = '1=1';
    if($filtro_evento) $where .= " AND pp.id_evento=$filtro_evento";
    if($filtro_estado) $where .= " AND pp.estado='".addslashes($filtro_estado)."'";
    $res = $conn->query("
      SELECT pp.*,
             (SELECT COUNT(*) FROM preguntas_publico x
              WHERE x.id_evento = pp.id_evento AND x.estado = 'aprobada'
                AND x.orden_aprobacion <= pp.orden_aprobacion) AS posicion,
             d.nombre AS dia,
             CONCAT(e.hora_inicio,' – ',e.hora_fin) AS horario,
             e.tipo_evento
      FROM preguntas_publico pp
      LEFT JOIN eventos e ON e.id_evento = pp.id_evento
      LEFT JOIN dias d    ON d.id_dia    = e.id_dia
      WHERE $where
      ORDER BY pp.fecha_envio DESC
    ");
    $rows = [];
    while($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode(['ok'=>true,'preguntas'=>$rows]);
    break;

  /* cambiar estado de pregunta */
  case 'cambiar_estado':
    $id     = intval($_POST['id']     ?? 0);
    $estado = trim($_POST['estado']   ?? '');
    if(!$id || !in_array($estado,['pendiente','aprobada','rechazada'])){
      echo json_encode(['ok'=>false,'msg'=>'Datos inválidos']); exit;
    }
    if($estado === 'aprobada'){
      /* el público ve las preguntas en el orden en que se aprueban */
      $conn->query("
        UPDATE preguntas_publico p
        INNER JOIN (SELECT COALESCE(MAX(orden_aprobacion),0)+1 AS siguiente FROM preguntas_publico) m
        SET p.estado='aprobada', p.orden_aprobacion=m.siguiente
        WHERE p.id=$id AND p.estado<>'aprobada'
      ");
    } else {
      $conn->query("UPDATE preguntas_publico SET estado='$estado', orden_aprobacion=NULL WHERE id=$id");
    }
    echo json_encode(['ok'=>true,'msg'=>'Estado actualizado']);
    break;

  /* eliminar pregunta */
  case 'eliminar_pregunta':
    $id = intval($_POST['id'] ?? 0);
    if(!$id){ echo json_encode(['ok'=>false,'msg'=>'ID requerido']); exit; }
    $conn->query("DELETE FROM preguntas_publico WHERE id=$id");
    echo json_encode(['ok'=>true,'msg'=>'Pregunta eliminada']);
    break;

  default:
    echo json_encode(['ok'=>false,'msg'=>'Accion no valida']);
}