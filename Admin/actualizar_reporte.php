<?php
require_once '../database/conexion.php';
require_once '../includes/auth.php';
requiere_admin($conn);

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
