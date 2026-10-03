<?php
/**
 * guardar_inscripcion.php
 * Acciones: verificar | buscar_estado | subir_comprobante | registrar
 */

require_once 'includes/funciones/bd_conexion.php';
header('Content-Type: application/json');
date_default_timezone_set('America/La_Paz');

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

define('TESORERA_EMAIL', 'mjoruro413@gmail.com');
define('CARPETA_COMP',   'comprobantes/');

if (!is_dir(CARPETA_COMP)) mkdir(CARPETA_COMP, 0755, true);

$accion = trim($_POST['accion'] ?? '');

switch ($accion) {

    /* ================================================================
       VERIFICAR SI YA ESTA INSCRITO
       ================================================================ */
    case 'verificar':
        $carnet  = limpiar($_POST['carnet']  ?? '');
        $celular = limpiar($_POST['celular'] ?? '');

        if (!$carnet && !$celular) {
            echo json_encode(['inscrito' => false]);
            exit;
        }

        $stmt = $conn->prepare("
            SELECT i.id, i.nombre, i.apellido, ins.estado_pago
            FROM inscritos i
            INNER JOIN inscripciones ins ON ins.inscrito_id = i.id
            WHERE i.carnet = ? OR i.celular = ?
            LIMIT 1
        ");
        $stmt->bind_param('ss', $carnet, $celular);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($fila) {
            echo json_encode([
                'inscrito' => true,
                'nombre'   => $fila['nombre'] . ' ' . $fila['apellido'],
                'estado'   => $fila['estado_pago']
            ]);
        } else {
            echo json_encode(['inscrito' => false]);
        }
        exit;

    /* ================================================================
       BUSCAR ESTADO (buscador publico)
       ================================================================ */
    case 'buscar_estado':
        $busqueda = limpiar($_POST['busqueda'] ?? '');

        if (!$busqueda) {
            echo json_encode(['ok' => false, 'msg' => 'Ingresa un carnet o celular']);
            exit;
        }

        $stmt = $conn->prepare("
            SELECT i.nombre, i.apellido, i.carnet, i.celular,
                   ins.estado_pago, ins.fecha_pago, p.nombre AS paquete
            FROM inscritos i
            INNER JOIN inscripciones ins ON ins.inscrito_id = i.id
            INNER JOIN paquetes p ON ins.paquete_id = p.id
            WHERE i.carnet = ? OR i.celular = ?
            LIMIT 1
        ");
        $stmt->bind_param('ss', $busqueda, $busqueda);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($fila) {
            $fila['fecha'] = $fila['fecha_pago']
                ? date('d/m/Y H:i', strtotime($fila['fecha_pago']))
                : '—';
            /* buscar credencial */
            $fila['credencial'] = null;
            $stmt_cred = $conn->prepare("
                SELECT ins.codigo_qr 
                FROM inscripciones ins
                INNER JOIN inscritos i ON i.id = ins.inscrito_id
                WHERE i.carnet = ? OR i.celular = ?
                LIMIT 1
            ");
            $stmt_cred->bind_param('ss', $busqueda, $busqueda);
            $stmt_cred->execute();
            $cred = $stmt_cred->get_result()->fetch_assoc();
            $stmt_cred->close();
            if($cred && !empty($cred['codigo_qr'])){
                /* buscar archivo de credencial por código QR o carnet */
                $patron1 = __DIR__ . '/credenciales/credencial_' . $cred['codigo_qr'] . '.pdf';
                $patron2 = __DIR__ . '/credenciales/credencial_' . $busqueda . '_*.html';
    
                $fila['credencial'] = null;
    
                if(file_exists($patron1)){
                    $fila['credencial'] = 'credencial_' . $cred['codigo_qr'] . '.pdf';
                    $fila['tipo_credencial'] = 'pdf';
                } else {
                    $archivos = glob($patron2);
                    if(!empty($archivos)){
                        $fila['credencial'] = basename(end($archivos));
                        $fila['tipo_credencial'] = 'html';
                    }
                }
            }
            echo json_encode(['ok' => true, 'datos' => $fila]);
            

        } else {
            echo json_encode(['ok' => false, 'msg' => 'No se encontro ningun inscrito con ese carnet o celular']);
        }
        exit;

    /* ================================================================
       SUBIR COMPROBANTE
       ================================================================ */
    case 'subir_comprobante':
        if (!isset($_FILES['comprobante']) || $_FILES['comprobante']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['ok' => false, 'msg' => 'Error al recibir el archivo']);
            exit;
        }

        $archivo    = $_FILES['comprobante'];
        $ext        = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $permitidos = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

        if (!in_array($ext, $permitidos)) {
            echo json_encode(['ok' => false, 'msg' => 'Solo se permiten imagenes JPG, PNG, WEBP o PDF']);
            exit;
        }

        if ($archivo['size'] > 5 * 1024 * 1024) {
            echo json_encode(['ok' => false, 'msg' => 'El archivo no puede superar 5MB']);
            exit;
        }

        $nombre_base = sanitizar(($_POST['nombre_inscrito'] ?? 'x') . '_' . ($_POST['apellido_inscrito'] ?? 'x'));
        $nombre_arch = 'comp_' . $nombre_base . '_' . time() . '.' . $ext;
        $destino     = CARPETA_COMP . $nombre_arch;

        if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
            echo json_encode(['ok' => false, 'msg' => 'No se pudo guardar el archivo en el servidor']);
            exit;
        }

        echo json_encode([
            'ok'      => true,
            'archivo' => $nombre_arch,
            'msg'     => 'Comprobante subido correctamente apreta el boton de confirmar inscripcion por favor'
        ]);
        exit;

    /* ================================================================
       REGISTRAR INSCRITO + INSCRIPCION
       ================================================================ */
    case 'registrar':
        $nombre      = limpiar($_POST['nombre']           ?? '');
        $apellido    = limpiar($_POST['apellido']         ?? '');
        $carnet      = limpiar($_POST['carnet']           ?? '');
        $edad        = intval($_POST['edad']              ?? 0);
        $celular     = limpiar($_POST['celular']          ?? '');
        $igl_id      = intval($_POST['iglesia_id']        ?? 0);
        $dis_id      = intval($_POST['distrito_id']       ?? 0);
        $tipo_id     = intval($_POST['tipo_inscrito_id']  ?? 0);
        $paquete_id  = intval($_POST['paquete_id']        ?? 0);
        $regalo_id   = intval($_POST['regalo_id']         ?? 0);
        $comprobante = limpiar($_POST['comprobante_arch'] ?? '');

        /* validacion basica */
        if (!$nombre || !$apellido || !$carnet || !$celular || !$paquete_id || !$comprobante) {
            echo json_encode(['ok' => false, 'msg' => 'Faltan datos obligatorios']);
            exit;
        }

        /* si el distrito no llego, derivarlo de la iglesia */
        if (!$dis_id && $igl_id) {
            $r = $conn->query("SELECT distrito_id FROM iglesias WHERE id = $igl_id LIMIT 1")->fetch_assoc();
            $dis_id = $r ? intval($r['distrito_id']) : 0;
        }

        /* verificar duplicado */
        $stmt = $conn->prepare("SELECT id FROM inscritos WHERE carnet = ? OR celular = ? LIMIT 1");
        $stmt->bind_param('ss', $carnet, $celular);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            echo json_encode(['ok' => false, 'msg' => 'Ya existe una inscripcion con ese carnet o celular']);
            $stmt->close();
            exit;
        }
        $stmt->close();

        /* precio paquete con descuento */
        $stmtP = $conn->prepare("
            SELECT p.precio,
                   ROUND(p.precio - (p.precio * COALESCE(d.porcentaje,0) / 100), 0) AS precio_final
            FROM paquetes p
            LEFT JOIN paquete_descuentos pd ON p.id = pd.paquete_id
            LEFT JOIN descuentos d ON pd.descuento_id = d.id AND d.activo = 1
            WHERE p.id = ? LIMIT 1
        ");
        $stmtP->bind_param('i', $paquete_id);
        $stmtP->execute();
        $paqData      = $stmtP->get_result()->fetch_assoc();
        $precio_orig  = floatval($paqData['precio']       ?? 0);
        $precio_paq_f = floatval($paqData['precio_final'] ?? $precio_orig);
        $descuento    = round($precio_orig - $precio_paq_f, 2);
        $stmtP->close();

        /* precio final total */
        $precio_final_total = round($precio_paq_f, 2);

        /* ══ INICIO TRANSACCION — si algo falla se revierte todo ══ */
        $conn->begin_transaction();

        try {

            /* -- 1. INSERTAR INSCRITO -- */
            /* bind_param tipos: s=nombre, s=apellido, s=carnet, i=edad, s=celular,
               i=igl_id, i=dis_id, i=tipo_id  →  'sssisiii' */
            $stmtI = $conn->prepare("
                INSERT INTO inscritos
                    (nombre, apellido, carnet, edad, celular,
                     iglesia_id, distrito_id, tipo_inscrito_id)
                VALUES (?,?,?,?,?,?,?,?)
            ");
            $stmtI->bind_param(
                'sssisiii',
                $nombre, $apellido, $carnet, $edad, $celular,
                $igl_id, $dis_id, $tipo_id
            );
            if (!$stmtI->execute()) {
                throw new RuntimeException('Error al guardar inscrito: ' . $stmtI->error);
            }
            $inscrito_id = $conn->insert_id;
            $stmtI->close();

            /* -- 2. INSERTAR INSCRIPCION -- */
            /* bind_param tipos: i=inscrito_id, i=paquete_id, d=precio_orig,
               d=precio_paq_f, d=descuento, d=precio_final_total, s=comprobante  →  'iidddds' */
            $stmtIn = $conn->prepare("
                INSERT INTO inscripciones
                    (inscrito_id, paquete_id,
                     precio_original, precio_paquete,
                     descuento_aplicado, precio_final,
                     metodo_pago, estado_pago, comprobante_qr, fecha_pago)
                VALUES (?,?,?,?,?,?,'qr','pendiente',?,NOW())
            ");
            $stmtIn->bind_param(
                'iidddds',
                $inscrito_id, $paquete_id,
                $precio_orig, $precio_paq_f,
                $descuento, $precio_final_total,
                $comprobante
            );
            if (!$stmtIn->execute()) {
                throw new RuntimeException('Error al guardar inscripcion: ' . $stmtIn->error);
            }
            $inscripcion_id = $conn->insert_id;
            $stmtIn->close();

            /* -- 3. INSERTAR REGALO -- */
            if ($regalo_id > 0) {
                $stmtR = $conn->prepare("
                    INSERT INTO inscripcion_regalos (inscripcion_id, regalo_id, material_entregado, material_entregado_por)
                    VALUES (?,?,0,NULL)
                ");
                $stmtR->bind_param('ii', $inscripcion_id, $regalo_id);
                if (!$stmtR->execute()) {
                    throw new RuntimeException('Error al guardar regalo: ' . $stmtR->error);
                }
                $stmtR->close();
                $conn->query("
                    UPDATE regalos
                    SET cupos_disponibles = cupos_disponibles - 1
                    WHERE id = $regalo_id AND cupos_disponibles > 0
                ");
            }

            /* -- 4. DESCONTAR CUPO PAQUETE -- */
            $conn->query("
                UPDATE paquetes
                SET cupos_disponibles = cupos_disponibles - 1
                WHERE id = $paquete_id AND cupos_disponibles > 0
            ");

            /* ══ CONFIRMAR TRANSACCION ══ */
            $conn->commit();

        } catch (RuntimeException $e) {
            /* algo fallo — deshacer TODO para no dejar datos huerfanos */
            $conn->rollback();
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
            exit;
        }

        /* ══ CORREO — fuera de la transaccion para no bloquearla ══ */
        $rutaArchivo    = CARPETA_COMP . $comprobante;
        $rutaComprimida = null;
        $ext_comp       = strtolower(pathinfo($comprobante, PATHINFO_EXTENSION));

        /* comprimir imagen a JPG calidad 60 para que el correo sea liviano */
        if (in_array($ext_comp, ['jpg','jpeg','png','webp']) && function_exists('imagecreatefromjpeg')) {
            $rutaComprimida = CARPETA_COMP . 'mini_' . pathinfo($comprobante, PATHINFO_FILENAME) . '.jpg';
            $img = null;
            if ($ext_comp === 'png')       $img = @imagecreatefrompng($rutaArchivo);
            elseif ($ext_comp === 'webp')  $img = @imagecreatefromwebp($rutaArchivo);
            else                           $img = @imagecreatefromjpeg($rutaArchivo);

            if ($img) {
                /* redimensionar si es muy grande */
                $w = imagesx($img);
                $h = imagesy($img);
                if ($w > 800) {
                    $ratio = 800 / $w;
                    $nw    = 800;
                    $nh    = intval($h * $ratio);
                    $img2  = imagecreatetruecolor($nw, $nh);
                    imagecopyresampled($img2, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
                    if (function_exists('imagedestroy')) imagedestroy($img);
                    $img = $img2;
                }
                imagejpeg($img, $rutaComprimida, 60);
                if (function_exists('imagedestroy')) imagedestroy($img);
            } else {
                $rutaComprimida = null;
            }
        }

        $archivoAdjunto = $rutaComprimida ?? $rutaArchivo;

        $asunto_correo = 'Nuevo pago por QR — Cumbre Departamental';

        $cuerpo_texto =
            "Inscripcion recibida:\n" .
            "Nombre:           $nombre $apellido\n" .
            "Carnet:           $carnet\n" .
            "Celular:          $celular\n" .
            "Paquete:          Bs. " . number_format($precio_paq_f, 2)       . "\n" .
            "Descuento:        Bs. " . number_format($descuento, 2)          . "\n" .
            "TOTAL:            Bs. " . number_format($precio_final_total, 2) . "\n" .
            "Estado de pago:   PENDIENTE\n" .
            "Metodo de pago:   QR / Banca Movil\n" .
            "Fecha:            " . date('d/m/Y H:i');

        $cuerpo_html = "
        <div style='font-family:Arial,sans-serif;max-width:580px;margin:0 auto;'>
            <div style='background:#2C2668;padding:18px;text-align:center;'>
                <h2 style='color:#fff;margin:0;font-size:18px;'>Nuevo Comprobante de Pago</h2>
                <p style='color:#E8750A;margin:4px 0 0;font-size:13px;'>Cumbre Departamental de Jóvenes</p>
            </div>
            <div style='padding:20px;background:#f8f9fa;'>
                <table style='width:100%;border-collapse:collapse;font-size:14px;'>
                    <tr>
                        <td style='padding:8px;font-weight:bold;color:#666;width:40%;'>Nombre</td>
                        <td style='padding:8px;'>$nombre $apellido</td>
                    </tr>
                    <tr style='background:#fff;'>
                        <td style='padding:8px;font-weight:bold;color:#666;'>Carnet</td>
                        <td style='padding:8px;'>$carnet</td>
                    </tr>
                    <tr>
                        <td style='padding:8px;font-weight:bold;color:#666;'>Celular</td>
                        <td style='padding:8px;'>$celular</td>
                    </tr>
                    <tr style='background:#fff;'>
                        <td style='padding:8px;font-weight:bold;color:#666;'>Precio paquete</td>
                        <td style='padding:8px;'>Bs. " . number_format($precio_paq_f, 2) . "</td>
                    </tr>
                    <tr style='background:#fff;'>
                        <td style='padding:8px;font-weight:bold;color:#666;'>Descuento</td>
                        <td style='padding:8px;color:#2e7d32;'>- Bs. " . number_format($descuento, 2) . "</td>
                    </tr>
                    <tr>
                        <td style='padding:8px;font-weight:bold;color:#2C2668;font-size:15px;'>TOTAL</td>
                        <td style='padding:8px;font-size:15px;font-weight:bold;color:#2C2668;'>Bs. " . number_format($precio_final_total, 2) . "</td>
                    </tr>
                    <tr style='background:#fff;'>
                        <td style='padding:8px;font-weight:bold;color:#666;'>Estado de pago</td>
                        <td style='padding:8px;'>
                            <span style='background:#fef9c3;color:#713f12;border:1px solid #ca8a04;
                                         padding:3px 10px;border-radius:20px;font-weight:bold;font-size:0.9em;'>
                                PENDIENTE
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td style='padding:8px;font-weight:bold;color:#666;'>Metodo de pago</td>
                        <td style='padding:8px;'>QR / Banca Movil</td>
                    </tr>
                    <tr style='background:#fff;'>
                        <td style='padding:8px;font-weight:bold;color:#666;'>Fecha</td>
                        <td style='padding:8px;'>" . date('d/m/Y H:i') . "</td>
                    </tr>
                </table>
                <div style='background:#fff3cd;border-left:4px solid #f59e0b;padding:12px;margin-top:15px;font-size:13px;'>
                    <strong>Accion requerida:</strong>
                    Verifica el comprobante adjunto y confirma la inscripcion en el panel de administracion, seccion <strong>Confirmar QR</strong>.
                </div>
            </div>
            <div style='background:#e1e1e1;padding:10px;text-align:center;font-size:11px;color:#666;'>
                Aviso automatico del sistema de inscripciones
            </div>
        </div>";

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'mjoruro413@gmail.com';
            $mail->Password   = 'xnlt bryj clho anzo';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';
            $mail->Timeout    = 10;

            $mail->setFrom('mjoruro413@gmail.com', 'Sistema Cumbre');
            $mail->addAddress(TESORERA_EMAIL, 'Tesorera');
            $mail->Subject = $asunto_correo;
            $mail->isHTML(true);
            $mail->Body    = $cuerpo_html;
            $mail->AltBody = $cuerpo_texto;

            if ($archivoAdjunto && file_exists($archivoAdjunto)) {
                $mail->addAttachment(
                    $archivoAdjunto,
                    'comprobante_' . $nombre . '_' . $apellido . '.jpg'
                );
            }

            $mail->send();

            /* borrar imagen comprimida temporal */
            if ($rutaComprimida && file_exists($rutaComprimida)) {
                @unlink($rutaComprimida);
            }

        } catch (Exception $e) {
            /* el correo fallo pero la inscripcion ya se guardo — no interrumpir */
            error_log('PHPMailer error inscripcion ' . $inscrito_id . ': ' . $e->getMessage());
        }

        echo json_encode([
            'ok'  => true,
            'msg' => 'Tu inscripcion fue recibida. Estado: PENDIENTE. La tesorera revisara tu comprobante y la confirmara desde el panel.'
        ]);
        exit;
    


    /* ================================================================
       ACCION NO RECONOCIDA
       ================================================================ */
    default:
        echo json_encode(['ok' => false, 'msg' => 'Accion no valida']);
        exit;
    }
    

/* ══════════════════════════════════════════════════════════════════
   FUNCIONES AUXILIARES
   ══════════════════════════════════════════════════════════════════ */

function limpiar($v) {
    return htmlspecialchars(strip_tags(trim($v)));
}

function sanitizar($n) {
    $n = strtolower($n);
    $n = str_replace(
        [' ', 'á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü'],
        ['_', 'a', 'e', 'i', 'o', 'u', 'n', 'u'],
        $n
    );
    return preg_replace('/[^a-z0-9_]/', '', $n);
}