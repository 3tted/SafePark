<?php
require_once '../includes/auth.php';
require_once '../includes/api.php';
require_once '../includes/fotos.php';
requiere_sesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ./'); exit;
}

$tipo        = trim($_POST['tipo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$id_area     = intval($_POST['id_area'] ?? 0);
$tipos_validos = ['incidente', 'condicion', 'sugerencia'];

if (empty($tipo) || empty($descripcion) || !$id_area || !in_array($tipo, $tipos_validos)) {
    header('Location: ./?error=campos'); exit;
}

$foto_nombre = guardar_foto($_FILES['foto'] ?? null, 'r' . $_SESSION['id_usuario'], 5);
if ($foto_nombre === false) {
    header('Location: ./?error=foto'); exit;
}

$resultado = api_post('/reportes', [
    'id_usuario'  => $_SESSION['id_usuario'],
    'id_area'     => $id_area,
    'tipo'        => $tipo,
    'descripcion' => $descripcion,
    'foto'        => $foto_nombre
]);

header($resultado['ok'] ? 'Location: ./?exito=1' : 'Location: ./?error=servidor');
exit;
