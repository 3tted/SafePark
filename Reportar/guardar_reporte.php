<?php
require_once '../includes/auth.php';
require_once '../includes/api.php';
requiere_sesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php'); exit;
}

$tipo        = trim($_POST['tipo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$id_area     = intval($_POST['id_area'] ?? 0);
$tipos_validos = ['incidente', 'condicion', 'sugerencia'];

if (empty($tipo) || empty($descripcion) || !$id_area || !in_array($tipo, $tipos_validos)) {
    header('Location: index.php?error=campos'); exit;
}

// Manejo de foto
$foto_nombre = null;
if (!empty($_FILES['foto']['name'])) {
    $file = $_FILES['foto'];
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $permitidos = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($ext, $permitidos) || $file['size'] > 5 * 1024 * 1024) {
        header('Location: index.php?error=foto'); exit;
    }

    $foto_nombre = 'r' . $_SESSION['id_usuario'] . '_' . time() . '.' . $ext;
    $destino = __DIR__ . '/../Assets/fotos/' . $foto_nombre;

    if (!move_uploaded_file($file['tmp_name'], $destino)) {
        header('Location: index.php?error=servidor'); exit;
    }
}

$resultado = api_post('/reportes', [
    'id_usuario'  => $_SESSION['id_usuario'],
    'id_area'     => $id_area,
    'tipo'        => $tipo,
    'descripcion' => $descripcion,
    'foto'        => $foto_nombre
]);

header($resultado['ok'] ? 'Location: index.php?exito=1' : 'Location: index.php?error=servidor');
exit;
