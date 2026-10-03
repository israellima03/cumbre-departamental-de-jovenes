<?php
$v = function($archivo){
  $ruta = $_SERVER['DOCUMENT_ROOT'] . '/admin/' . $archivo;
  return file_exists($ruta) ? filemtime($ruta) : time();
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin — Cumbre Departamental de Jóvenes</title>
  <link rel="stylesheet" href="css/admin.css?v=<?php echo $v('css/admin.css'); ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Oswald:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/crear-admin.css?v=<?php echo $v('css/crear-admin.css'); ?>">
  <link rel="stylesheet" href="css/confirmar-qr.css?v=<?php echo $v('css/confirmar-qr.css'); ?>">
  <link rel="stylesheet" href="css/editar-cupos.css?v=<?php echo $v('css/editar-cupos.css'); ?>">
  <?php echo $css_extra ?? ''; ?>
</head>