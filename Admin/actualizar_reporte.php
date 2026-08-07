<?php
require_once '../includes/auth.php';
require_once '../includes/api.php';
requiere_admin();

$id_reporte = intval($_POST['id_reporte'] ?? 0);
$estado     = $_POST['estado'] ?? '';
$validos    = ['pendiente', 'en_proceso', 'resuelto'];

if (!$id_reporte || !in_array($estado, $validos)) {
    header('Location: ./?error=servidor'); exit;
}

$resultado = api_put('/reportes/' . $id_reporte, ['estado' => $estado]);
header($resultado['ok'] ? 'Location: ./?exito=1' : 'Location: ./?error=servidor');
exit;
