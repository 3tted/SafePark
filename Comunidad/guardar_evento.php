<?php
require_once '../includes/auth.php';
require_once '../includes/api.php';
requiere_sesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ./'); exit;
}

$nombre  = trim($_POST['nombre'] ?? '');
$id_area = intval($_POST['id_area'] ?? 0);
$fecha   = $_POST['fecha'] ?? '';
$hora    = $_POST['hora'] ?? '';

if (empty($nombre) || !$id_area || empty($fecha) || empty($hora)) {
    header('Location: ./?error=evento#tab-eventos'); exit;
}

$resultado = api_post('/eventos', [
    'id_usuario' => $_SESSION['id_usuario'],
    'id_area'    => $id_area,
    'nombre'     => $nombre,
    'fecha'      => $fecha,
    'hora'       => $hora
]);

header($resultado['ok'] ? 'Location: ./?exito=evento#tab-eventos' : 'Location: ./?error=evento#tab-eventos');
exit;
