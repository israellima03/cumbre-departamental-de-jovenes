<?php
/* Copia este archivo como bd_conexion.php y completa los datos del servidor.
   bd_conexion.php no se sube a git, así cada servidor mantiene su propia conexión. */
$conn = new mysqli('localhost', 'USUARIO', 'CONTRASEÑA', 'NOMBRE_BASE_DE_DATOS');
if($conn->connect_error){
  echo $conn->connect_error;
}
?>
