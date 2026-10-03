<?php
require_once('funciones/sesiones.php');

header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

if(isset($_SESSION['usuario_id'])){
    header('Location: admin-encuentro.php');
    exit();
}
require_once('../includes/funciones/bd_conexion.php');

$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $usuario  = trim($_POST['usuario']  ?? '');
    $password = trim($_POST['password'] ?? '');

    if(!$usuario || !$password){
        $error = 'Ingresa tu usuario y contraseña';
    } else {
        $stmt = $conn->prepare("
            SELECT u.id, u.nombre, u.password,
                   GROUP_CONCAT(r.nombre ORDER BY r.id SEPARATOR ',') AS roles
            FROM usuarios u
            LEFT JOIN usuario_roles ur ON ur.usuario_id = u.id
            LEFT JOIN roles r ON r.id = ur.rol_id
            WHERE u.usuario = ?
            GROUP BY u.id
            LIMIT 1
        ");
        $stmt->bind_param('s', $usuario);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if(!$user){
            $error = 'Usuario no encontrado';
        } elseif(!password_verify($password, $user['password'])){
            $error = 'Contraseña incorrecta';
        } else {
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['nombre']     = $user['nombre'];
            $_SESSION['usuario']    = $usuario;
            $_SESSION['roles']      = $user['roles'] ? explode(',', $user['roles']) : [];
            $_SESSION['rol']        = $_SESSION['roles'][0] ?? 'Admin';
            header('Location: admin-encuentro.php');
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — Admin Cumbre</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/login.css?v=<?php echo filemtime(__DIR__.'/css/login.css'); ?>">
</head>
<body>

  <div class="login-bg">
    <div class="login-card">

      <div class="login-logo">
        <div class="login-icono">
          <i class="fa-solid fa-dove"></i>
        </div>
        <h1>Panel Administrativo</h1>
        <p>Cumbre Departamental de Jóvenes</p>
      </div>

      <!-- error PHP -->
      <?php if($error): ?>
        <div class="login-error">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <!-- error JS (oculto por defecto) -->
      <div id="msg-error" style="display:none;" class="login-error">
        <i class="fa-solid fa-circle-xmark"></i>
        <span id="msg-error-txt"></span>
      </div>

      <!-- UN SOLO FORM -->
      <form id="form-login" method="POST" action="login.php" class="login-form">

        <div class="login-campo">
          <label for="usuario">Usuario</label>
          <div class="login-input-wrap">
            <i class="fa-solid fa-user"></i>
            <input
              type="text"
              id="usuario"
              name="usuario"
              class="field-input"
              placeholder="tu_usuario"
              value="<?php echo htmlspecialchars($_POST['usuario'] ?? ''); ?>"
              autocomplete="username">
          </div>
          <span class="field-error" id="err-usuario"></span>
        </div>

        <div class="login-campo">
          <label for="password">Contraseña</label>
          <div class="login-input-wrap">
            <i class="fa-solid fa-lock"></i>
            <input
              type="password"
              id="password"
              name="password"
              class="field-input"
              placeholder="••••••••"
              autocomplete="current-password">
            <button type="button" id="btn-toggle-pass" class="btn-ver-pass" tabindex="-1">
              <i class="fa-solid fa-eye" id="ico-pass"></i>
            </button>
          </div>
          <span class="field-error" id="err-password"></span>
        </div>

        <button type="submit" id="btn-login" class="login-btn">
          <span class="btn-txt">Iniciar Sesión</span>
          <i class="fa-solid fa-arrow-right-to-bracket btn-ico"></i>
          <span id="btn-loader" style="display:none;">
            <i class="fa-solid fa-spinner fa-spin"></i>
          </span>
        </button>

      </form>

      <div class="login-volver">
        <a href="../index.php">
          <i class="fa-solid fa-arrow-left"></i> Volver al sitio
        </a>
      </div>

    </div>
  </div>

  <script src="js/login.js?v=<?php echo filemtime(__DIR__.'/js/login.js'); ?>"></script>
</body>
</html>