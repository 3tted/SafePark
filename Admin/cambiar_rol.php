<?php
session_start();
require_once '../database/conexion.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../Login/index.php');
    exit;
}

$id = $_SESSION['id_usuario'];
$check = $conn->prepare("SELECT rol FROM USUARIO WHERE id_usuario = ?");
$check->bind_param("i", $id);
$check->execute();
$check->bind_result($rol);
$check->fetch();
$check->close();

if ($rol !== 'admin') {
    header('Location: ../Home/index.html');
    exit;
}

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
    header('Location: index.php?exito=1');
} else {
    header('Location: index.php?error=servidor');
}
exit;
?>
