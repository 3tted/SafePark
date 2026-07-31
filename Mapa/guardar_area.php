<?php
require_once '../includes/auth.php';
require_once '../includes/api.php';
requiere_sesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php'); exit;
}

$nombre  = trim($_POST['nombre'] ?? '');
$colonia = trim($_POST['colonia'] ?? '');
$tipo    = $_POST['tipo'] ?? '';
$lat     = $_POST['lat'] ?? '';
$lng     = $_POST['lng'] ?? '';
$tipos_validos = ['parque', 'deportivo', 'plaza'];

if (empty($nombre) || empty($colonia) || !in_array($tipo, $tipos_validos) || $lat === '' || $lng === '') {
    header('Location: index.php?error=1'); exit;
}

// Construir multipart para enviar al API (incluyendo foto si hay)
$boundary = '----SafeParkBoundary' . uniqid();
$body = '';

foreach (['nombre' => $nombre, 'colonia' => $colonia, 'tipo' => $tipo, 'lat' => $lat, 'lng' => $lng, 'id_usuario' => $_SESSION['id_usuario']] as $key => $val) {
    $body .= "--$boundary\r\nContent-Disposition: form-data; name=\"$key\"\r\n\r\n$val\r\n";
}

if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === 0) {
    $file_content = file_get_contents($_FILES['foto']['tmp_name']);
    $filename     = basename($_FILES['foto']['name']);
    $body .= "--$boundary\r\nContent-Disposition: form-data; name=\"foto\"; filename=\"$filename\"\r\nContent-Type: {$_FILES['foto']['type']}\r\n\r\n$file_content\r\n";
}

$body .= "--$boundary--\r\n";

$ctx = stream_context_create(['http' => [
    'method'  => 'POST',
    'header'  => "Content-Type: multipart/form-data; boundary=$boundary\r\nContent-Length: " . strlen($body),
    'content' => $body,
    'timeout' => 10,
    'ignore_errors' => true
]]);

$json = @file_get_contents(API_BASE . '/areas', false, $ctx);
$resultado = json_decode($json, true) ?? ['ok' => false];

header($resultado['ok'] ? 'Location: index.php?exito=1' : 'Location: index.php?error=1');
exit;
