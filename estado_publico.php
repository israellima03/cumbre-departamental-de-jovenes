<?php
/* Estado que el sitio público consulta solo: cupos y si las inscripciones están abiertas */
require_once 'includes/funciones/bd_conexion.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');

$cfg = $conn->query("SELECT valor FROM config_sistema WHERE clave='inscripciones_activas' LIMIT 1")->fetch_assoc();

echo json_encode([
  'inscripciones_activas' => !($cfg && $cfg['valor'] === '0'),
  'paquetes' => $conn->query("SELECT id, cupo_total, cupos_disponibles FROM paquetes")->fetch_all(MYSQLI_ASSOC),
]);
