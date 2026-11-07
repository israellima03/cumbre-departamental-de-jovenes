<?php
require_once 'includes/funciones/bd_conexion.php';
header('Content-Type: application/json');

$accion = trim($_POST['accion'] ?? '');

switch($accion){

    case 'enviar_pregunta':
        $id_evento = intval($_POST['id_evento'] ?? 0);
        $nombre    = 'Anonimo';
        $pregunta  = trim($_POST['pregunta'] ?? '');

        if(!$id_evento || !$pregunta){
            echo json_encode(['ok'=>false,'msg'=>'Escribe tu pregunta antes de enviar']);
            exit;
        }

        if(strlen($pregunta) > 500){
            echo json_encode(['ok'=>false,'msg'=>'La pregunta no puede superar 500 caracteres']);
            exit;
        }

        /* verificar si preguntas están activas para este evento */
        $stmtA = $conn->prepare("SELECT preguntas_activas FROM eventos WHERE id_evento=? LIMIT 1");
        $stmtA->bind_param('i', $id_evento);
        $stmtA->execute();
        $rowA = $stmtA->get_result()->fetch_assoc();
        $stmtA->close();

        if(!$rowA || !$rowA['preguntas_activas']){
            echo json_encode(['ok'=>false,'msg'=>'Las preguntas no estan activas para esta conferencia']);
            exit;
        }

        $stmt = $conn->prepare("
            INSERT INTO preguntas_publico (id_evento, nombre_autor, pregunta, estado)
            VALUES (?, ?, ?, 'pendiente')
        ");
        $stmt->bind_param('iss', $id_evento, $nombre, $pregunta);

        if($stmt->execute()){
            echo json_encode(['ok'=>true,'msg'=>'¡Pregunta enviada de forma anonima!']);
        } else {
            echo json_encode(['ok'=>false,'msg'=>'Error al guardar la pregunta']);
        }
        $stmt->close();
        exit;

    case 'listar_aprobadas':
        $id_evento = intval($_POST['id_evento'] ?? 0);
        if(!$id_evento){
            echo json_encode(['ok'=>false,'msg'=>'ID requerido']);
            exit;
        }
        $stmt = $conn->prepare("
            SELECT id, pregunta, fecha_envio
            FROM preguntas_publico
            WHERE id_evento = ? AND estado = 'aprobada'
            ORDER BY orden_aprobacion ASC, id ASC
        ");
        $stmt->bind_param('i', $id_evento);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        echo json_encode(['ok'=>true,'preguntas'=>$rows]);
        exit;

    default:
        echo json_encode(['ok'=>false,'msg'=>'Accion no valida']);
        exit;
}