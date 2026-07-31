<?php
require_once '../includes/auth.php';
require_once '../includes/api.php';
require_once '../includes/fotos.php';
requiere_admin();

$id_area = intval($_POST['id_area'] ?? 0);
$nombre    = trim($_POST['nombre'] ?? '');
$colonia   = trim($_POST['colonia'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');
$horario   = trim($_POST['horario'] ?? '');
$tipo      = $_POST['tipo'] ?? '';
$lat     = $_POST['lat'] ?? '';
$lng     = $_POST['lng'] ?? '';
$tipos_validos = ['parque', 'deportivo', 'plaza'];

if (!$id_area || empty($nombre) || empty($colonia) || !in_array($tipo, $tipos_validos) || $lat === '' || $lng === '') {
    header('Location: index.php?error=servidor#tab-areas'); exit;
}

$foto = guardar_foto($_FILES['foto'] ?? null, 'area', 3);
if ($foto === false) {
    header('Location: index.php?error=foto#tab-areas'); exit;
}

$resultado = api_put('/areas/' . $id_area, [
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

header($resultado['ok'] ? 'Location: index.php?exito=1#tab-areas' : 'Location: index.php?error=servidor#tab-areas');
exit;
