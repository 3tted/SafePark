<?php
require_once '../database/conexion.php';
require_once '../includes/auth.php';
requiere_sesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id_usuario = $_SESSION['id_usuario'];
$nombre     = trim($_POST['nombre'] ?? '');
$colonia    = trim($_POST['colonia'] ?? '');
$tipo       = $_POST['tipo'] ?? '';
$lat        = $_POST['lat'] ?? '';
$lng        = $_POST['lng'] ?? '';

$tipos_validos = ['parque', 'deportivo', 'plaza'];

if (empty($nombre) || empty($colonia) || !in_array($tipo, $tipos_validos) || $lat === '' || $lng === '') {
    header('Location: index.php?error=1');
    exit;
}

// Manejo de foto (opcional)
$foto_nombre = null;
if (!empty($_FILES['foto']['name'])) {
    $file       = $_FILES['foto'];
    $ext        = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $permitidos = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($ext, $permitidos) || $file['size'] > 3 * 1024 * 1024) {
        header('Location: index.php?error=1');
        exit;
    }

    $foto_nombre = 'area_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
    $destino = __DIR__ . '/../Assets/fotos/' . $foto_nombre;

    if (!move_uploaded_file($file['tmp_name'], $destino)) {
        header('Location: index.php?error=1');
        exit;
    }
}

if ($foto_nombre) {
    $stmt = $conn->prepare("INSERT INTO AREA (nombre, colonia, tipo, lat, lng, id_usuario, foto) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssddis", $nombre, $colonia, $tipo, $lat, $lng, $id_usuario, $foto_nombre);
} else {
    $stmt = $conn->prepare("INSERT INTO AREA (nombre, colonia, tipo, lat, lng, id_usuario) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssddi", $nombre, $colonia, $tipo, $lat, $lng, $id_usuario);
}

if ($stmt->execute()) {
    header('Location: index.php?exito=1');
} else {
    header('Location: index.php?error=1');
}
exit;
?>
