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

$id_reporte = intval($_POST['id_reporte'] ?? 0);
$estado     = $_POST['estado'] ?? '';
$validos    = ['pendiente', 'en_proceso', 'resuelto'];

if (!$id_reporte || !in_array($estado, $validos)) {
    header('Location: index.php?error=servidor');
    exit;
}

$stmt = $conn->prepare("UPDATE REPORTE SET estado = ? WHERE id_reporte = ?");
$stmt->bind_param("si", $estado, $id_reporte);

if ($stmt->execute()) {
    header('Location: index.php?exito=1');
} else {
    header('Location: index.php?error=servidor');
}
exit;
?>
