<?php
session_start();
require_once '../database/conexion.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../Login/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id_usuario = $_SESSION['id_usuario'];
$nombre     = trim($_POST['nombre'] ?? '');
$id_area    = intval($_POST['id_area'] ?? 0);
$fecha      = $_POST['fecha'] ?? '';
$hora       = $_POST['hora'] ?? '';

if (empty($nombre) || !$id_area || empty($fecha) || empty($hora)) {
    header('Location: index.php?error=evento#tab-eventos');
    exit;
}

$stmt = $conn->prepare("INSERT INTO EVENTO (id_usuario, id_area, nombre, fecha, hora) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("iisss", $id_usuario, $id_area, $nombre, $fecha, $hora);

if ($stmt->execute()) {
    header('Location: index.php?exito=evento#tab-eventos');
} else {
    header('Location: index.php?error=evento#tab-eventos');
}
exit;
?>
