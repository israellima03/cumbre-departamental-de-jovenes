<?php
ob_start();
require_once('funciones/sesiones.php');
usuario_autentificado();
ob_clean();
header('Content-Type: application/json');
require_once('../includes/funciones/bd_conexion.php');
date_default_timezone_set('America/La_Paz');

$accion     = trim($_GET['accion'] ?? $_POST['accion'] ?? '');
$usuario_id = intval($_SESSION['usuario_id'] ?? 0);

/* crear puntos y revertir registros: solo estos roles */
$puede_gestionar = puede(['Administrador','Lider departamental','tesorera']);

function responder(array $datos){
  echo json_encode($datos);
  exit;
}

function exigir_gestion(bool $puede){
  if(!$puede) responder(['ok'=>false,'msg'=>'No tienes permiso para esta accion']);
}

function total_registrados(mysqli $conn, int $punto_id): int {
  return intval($conn->query("SELECT COUNT(*) c FROM registros_puntos WHERE punto_id=$punto_id")->fetch_assoc()['c']);
}

try {
  switch($accion){

    /* ── PUNTOS DE REGISTRO ── */
    case 'listar_puntos':
      $res = $conn->query("
        SELECT p.id, p.nombre, p.orden, p.activo, COUNT(r.id) AS registrados
        FROM puntos_registro p
        LEFT JOIN registros_puntos r ON r.punto_id = p.id
        GROUP BY p.id
        ORDER BY p.orden, p.nombre
      ");
      responder(['ok'=>true,'puntos'=>$res->fetch_all(MYSQLI_ASSOC),'puede_gestionar'=>$puede_gestionar]);

    case 'guardar_punto':
      exigir_gestion($puede_gestionar);
      $id     = intval($_POST['id'] ?? 0);
      $nombre = trim($_POST['nombre'] ?? '');
      $orden  = intval($_POST['orden'] ?? 0);
      if($nombre === '') responder(['ok'=>false,'msg'=>'El nombre es obligatorio']);

      $stmt = $conn->prepare("SELECT id FROM puntos_registro WHERE nombre=? AND id<>? LIMIT 1");
      $stmt->bind_param('si', $nombre, $id);
      $stmt->execute();
      $repetido = $stmt->get_result()->fetch_assoc();
      $stmt->close();
      if($repetido) responder(['ok'=>false,'msg'=>'Ya existe un punto con ese nombre']);

      if($id){
        $stmt = $conn->prepare("UPDATE puntos_registro SET nombre=?, orden=? WHERE id=?");
        $stmt->bind_param('sii', $nombre, $orden, $id);
      } else {
        $stmt = $conn->prepare("INSERT INTO puntos_registro (nombre, orden) VALUES (?,?)");
        $stmt->bind_param('si', $nombre, $orden);
      }
      $stmt->execute();
      $stmt->close();
      responder(['ok'=>true,'msg'=>$id ? 'Punto actualizado' : 'Punto creado']);

    case 'cambiar_estado_punto':
      exigir_gestion($puede_gestionar);
      $id     = intval($_POST['id'] ?? 0);
      $activo = intval($_POST['activo'] ?? 0) ? 1 : 0;
      $conn->query("UPDATE puntos_registro SET activo=$activo WHERE id=$id");
      responder(['ok'=>true,'msg'=>$activo ? 'Punto activado' : 'Punto desactivado']);

    case 'eliminar_punto':
      exigir_gestion($puede_gestionar);
      $id = intval($_POST['id'] ?? 0);
      if(total_registrados($conn, $id) > 0){
        responder(['ok'=>false,'msg'=>'Este punto ya tiene registros. Desactivalo en lugar de eliminarlo.']);
      }
      $conn->query("DELETE FROM puntos_registro WHERE id=$id");
      responder(['ok'=>true,'msg'=>'Punto eliminado']);

    /* ── REGISTRAR POR QR O CARNET ── */
    case 'registrar':
      $punto_id = intval($_POST['punto_id'] ?? 0);
      $codigo   = strtolower(trim($_POST['codigo'] ?? ''));
      $carnet   = trim($_POST['carnet'] ?? '');

      $punto = $conn->query("SELECT id, nombre, activo FROM puntos_registro WHERE id=$punto_id LIMIT 1")->fetch_assoc();
      if(!$punto || !$punto['activo']) responder(['ok'=>false,'estado'=>'error','msg'=>'Elige un punto de registro activo']);

      $metodo = $codigo !== '' ? 'qr' : 'carnet';
      $campo  = $metodo === 'qr' ? 'ins.codigo_qr' : 'i.carnet';
      $valor  = $metodo === 'qr' ? $codigo : $carnet;
      if($valor === '') responder(['ok'=>false,'estado'=>'error','msg'=>'Escribe el numero de carnet']);

      $stmt = $conn->prepare("
        SELECT ins.id AS inscripcion_id, ins.estado_pago,
               i.nombre, i.apellido, i.carnet, i.edad,
               ig.nombre AS iglesia, d.nombre AS distrito,
               p.nombre AS paquete, t.nombre AS tipo
        FROM inscripciones ins
        INNER JOIN inscritos i ON i.id = ins.inscrito_id
        LEFT JOIN iglesias ig  ON ig.id = i.iglesia_id
        LEFT JOIN distritos d  ON d.id = i.distrito_id
        LEFT JOIN paquetes p   ON p.id = ins.paquete_id
        LEFT JOIN tipos_inscrito t ON t.id = i.tipo_inscrito_id
        WHERE $campo = ?
        LIMIT 1
      ");
      $stmt->bind_param('s', $valor);
      $stmt->execute();
      $persona = $stmt->get_result()->fetch_assoc();
      $stmt->close();

      if(!$persona){
        responder(['ok'=>false,'estado'=>'error','msg'=>$metodo === 'qr'
          ? 'Este QR no corresponde a ningun inscrito. Prueba con el carnet.'
          : 'No hay ningun inscrito con ese carnet']);
      }
      if($persona['estado_pago'] !== 'confirmado'){
        responder(['ok'=>false,'estado'=>'error','msg'=>'Pago sin confirmar. No se puede registrar.','persona'=>$persona]);
      }

      $inscripcion_id = intval($persona['inscripcion_id']);
      $fecha = date('Y-m-d H:i:s');

      /* el indice unico evita el doble registro aunque dos celulares escaneen a la vez */
      $stmt = $conn->prepare("
        INSERT IGNORE INTO registros_puntos (punto_id, inscripcion_id, registrado_por, metodo, fecha)
        VALUES (?,?,?,?,?)
      ");
      $stmt->bind_param('iiiss', $punto_id, $inscripcion_id, $usuario_id, $metodo, $fecha);
      $stmt->execute();
      $nuevo = $stmt->affected_rows > 0;
      $stmt->close();

      $reg = $conn->query("
        SELECT r.fecha, COALESCE(u.nombre,'—') AS registrado_por
        FROM registros_puntos r
        LEFT JOIN usuarios u ON u.id = r.registrado_por
        WHERE r.punto_id=$punto_id AND r.inscripcion_id=$inscripcion_id
        LIMIT 1
      ")->fetch_assoc();

      responder([
        'ok'             => true,
        'estado'         => $nuevo ? 'registrado' : 'duplicado',
        'punto'          => $punto['nombre'],
        'persona'        => $persona,
        'fecha'          => $reg['fecha'],
        'registrado_por' => $reg['registrado_por'],
        'total'          => total_registrados($conn, $punto_id),
      ]);

    /* ── QUIÉN YA SE REGISTRÓ EN CADA PUNTO ── */
    case 'listar_registrados':
      $punto_id = intval($_GET['punto_id'] ?? 0);
      $buscar   = trim($_GET['buscar'] ?? '');

      $filtro = '';
      $tipos  = 'i';
      $params = [$punto_id];
      if($buscar !== ''){
        $like   = '%' . $buscar . '%';
        $filtro = 'AND (i.nombre LIKE ? OR i.apellido LIKE ? OR i.carnet LIKE ?)';
        $tipos .= 'sss';
        array_push($params, $like, $like, $like);
      }

      $stmt = $conn->prepare("
        SELECT r.id, r.fecha, r.metodo,
               i.nombre, i.apellido, i.carnet,
               ig.nombre AS iglesia, d.nombre AS distrito, p.nombre AS paquete,
               COALESCE(u.nombre,'—') AS registrado_por
        FROM registros_puntos r
        INNER JOIN inscripciones ins ON ins.id = r.inscripcion_id
        INNER JOIN inscritos i ON i.id = ins.inscrito_id
        LEFT JOIN iglesias ig  ON ig.id = i.iglesia_id
        LEFT JOIN distritos d  ON d.id = i.distrito_id
        LEFT JOIN paquetes p   ON p.id = ins.paquete_id
        LEFT JOIN usuarios u   ON u.id = r.registrado_por
        WHERE r.punto_id = ?
        $filtro
        ORDER BY r.fecha DESC, r.id DESC
      ");
      $stmt->bind_param($tipos, ...$params);
      $stmt->execute();
      $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
      $stmt->close();

      $confirmados = intval($conn->query("SELECT COUNT(*) c FROM inscripciones WHERE estado_pago='confirmado'")->fetch_assoc()['c']);
      responder([
        'ok'              => true,
        'registros'       => $filas,
        'total'           => total_registrados($conn, $punto_id),
        'confirmados'     => $confirmados,
        'puede_gestionar' => $puede_gestionar,
      ]);

    case 'revertir':
      exigir_gestion($puede_gestionar);
      $id = intval($_POST['id'] ?? 0);
      $conn->query("DELETE FROM registros_puntos WHERE id=$id");
      responder($conn->affected_rows > 0
        ? ['ok'=>true,'msg'=>'Registro revertido']
        : ['ok'=>false,'msg'=>'El registro ya no existe']);

    default:
      responder(['ok'=>false,'msg'=>'Accion no valida']);
  }
} catch(Throwable $e){
  error_log('api_escaner: ' . $e->getMessage());
  /* el administrador ve el detalle para poder diagnosticar en producción */
  $detalle = puede(['Administrador']) ? ' (' . $e->getMessage() . ')' : '';
  responder(['ok'=>false,'estado'=>'error','msg'=>'Error del sistema. Intenta de nuevo.' . $detalle]);
}
