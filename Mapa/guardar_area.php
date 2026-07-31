<?php
require_once '../includes/auth.php';
require_once '../includes/api.php';
require_once '../includes/fotos.php';
requiere_sesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php'); exit;
}

$nombre    = trim($_POST['nombre'] ?? '');
$colonia   = trim($_POST['colonia'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');
$horario   = trim($_POST['horario'] ?? '');
$tipo      = $_POST['tipo'] ?? '';
$lat     = $_POST['lat'] ?? '';
$lng     = $_POST['lng'] ?? '';
$tipos_validos = ['parque', 'deportivo', 'plaza'];

if (empty($nombre) || empty($colonia) || !in_array($tipo, $tipos_validos) || $lat === '' || $lng === '') {
    header('Location: index.php?error=1'); exit;
}

$foto = guardar_foto($_FILES['foto'] ?? null, 'area', 3);
if ($foto === false) {
    header('Location: index.php?error=foto'); exit;
}

$resultado = api_post('/areas', [
    'id_usuario' => $_SESSION['id_usuario'],
    'nombre'     => $nombre,
    'colonia'    => $colonia,
    'direccion'  => $direccion,
    'horario'    => $horario,
    'tipo'       => $tipo,
    'lat'        => $lat,
    'lng'        => $lng,
    'foto'       => $foto
]);

header($resultado['ok'] ? 'Location: index.php?exito=1' : 'Location: index.php?error=1');
exit;
