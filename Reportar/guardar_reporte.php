<?php
require_once '../database/conexion.php';
require_once '../includes/auth.php';
requiere_sesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id_usuario  = $_SESSION['id_usuario'];
$tipo        = trim($_POST['tipo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$id_area     = intval($_POST['id_area'] ?? 0);

if (empty($tipo) || empty($descripcion) || $id_area === 0) {
    header('Location: index.html?error=campos');
    exit;
}

$tipos_validos = ['incidente', 'condicion', 'sugerencia'];
if (!in_array($tipo, $tipos_validos)) {
    header('Location: index.html?error=tipo');
    exit;
}

$stmt = $conn->prepare("INSERT INTO REPORTE (id_usuario, id_area, tipo, descripcion) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiss", $id_usuario, $id_area, $tipo, $descripcion);

if ($stmt->execute()) {
    header('Location: index.html?exito=1');
} else {
    header('Location: index.html?error=servidor');
}
exit;
?>
