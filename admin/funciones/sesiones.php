<?php
if(session_status() === PHP_SESSION_NONE){
    session_start();
}

/* ============================================================
   CIERRE AUTOMATICO POR INACTIVIDAD — 30 minutos
   ============================================================ */
define('TIEMPO_INACTIVIDAD', 30 * 60); /* 30 minutos en segundos */

if(isset($_SESSION['usuario_id'])){
    $ahora = time();

    /* si hay tiempo de ultima actividad registrado */
    if(isset($_SESSION['ultima_actividad'])){
        $inactivo = $ahora - $_SESSION['ultima_actividad'];

        if($inactivo > TIEMPO_INACTIVIDAD){
            /* sesion expirada por inactividad */
            session_unset();
            session_destroy();
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Location: login.php?expirado=1');
            exit();
        }
    }

    /* actualizar tiempo de ultima actividad; las consultas automáticas no cuentan */
    if(empty($_GET['auto'])) $_SESSION['ultima_actividad'] = $ahora;
}

/* ============================================================
   FUNCIONES
   ============================================================ */
function usuario_autentificado(){
    if(!revisar_usuario()){
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Location: login.php');
        exit();
    }
}

function revisar_usuario(){
    return isset($_SESSION['usuario_id']);
}

function obtener_rol(){
    return $_SESSION['rol'] ?? '';
}

function obtener_nombre(){
    return $_SESSION['nombre'] ?? 'Administrador';
}

function puede($roles_requeridos){
    $roles_usuario = $_SESSION['roles'] ?? [];
    foreach($roles_requeridos as $r){
        foreach($roles_usuario as $ru){
            if(strtolower(trim($ru)) === strtolower(trim($r))) return true;
        }
    }
    return false;
}

function verificar_acceso($roles_permitidos){
    if(!puede($roles_permitidos)){
        header('Location: sin-acceso.php');
        exit();
    }
}