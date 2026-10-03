<?php
require_once('funciones/sesiones.php');
usuario_autentificado();
require_once('../includes/funciones/bd_conexion.php');
require_once('funciones_credencial.php');

$res = $conn->query("
    SELECT ins.id, ins.inscrito_id, ins.codigo_qr 
    FROM inscripciones ins
    WHERE ins.estado_pago = 'confirmado' 
    AND ins.codigo_qr IS NOT NULL
");

$ok = 0; $fail = 0; $detalles = [];
while($r = $res->fetch_assoc()){
    $archivo = 'credencial_' . $r['codigo_qr'] . '.pdf';
    $ruta = '../credenciales/' . $archivo;
    $existe = file_exists($ruta);
    $resultado = generarCredencialHTML($conn, $r['inscrito_id'], $r['id']);
    if($resultado){ 
        $ok++; 
        $detalles[] = '✅ inscrito_id='.$r['inscrito_id'].' → '.$archivo;
    } else { 
        $fail++; 
        $detalles[] = '❌ inscrito_id='.$r['inscrito_id'].' → falló';
    }
}

echo '<pre>';
echo "Total procesados: ".($ok+$fail)."\n";
echo "OK: $ok\n";
echo "Fallidos: $fail\n\n";
foreach($detalles as $d) echo $d."\n";
echo '</pre>';