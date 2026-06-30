<?php
require_once '../database/conexion.php';
require_once '../includes/auth.php';
requiere_admin($conn);

$id_usuario = intval($_POST['id_usuario'] ?? 0);
$nuevo_rol  = $_POST['rol'] ?? '';
$validos    = ['usuario', 'admin'];

if (!$id_usuario || !in_array($nuevo_rol, $validos)) {
    header('Location: index.php?error=servidor');
    exit;
}

$stmt = $conn->prepare("UPDATE USUARIO SET rol = ? WHERE id_usuario = ?");
$stmt->bind_param("si", $nuevo_rol, $id_usuario);

if ($stmt->execute()) {
    header('Location: index.php?exito=1#tab-usuarios');
} else {
    header('Location: index.php?error=servidor#tab-usuarios');
}
exit;
?>
