<?php
require_once '../database/conexion.php';
require_once '../includes/auth.php';
requiere_admin($conn);

$id_area = intval($_POST['id_area'] ?? 0);
$nombre  = trim($_POST['nombre'] ?? '');
$colonia = trim($_POST['colonia'] ?? '');
$tipo    = $_POST['tipo'] ?? '';
$lat     = $_POST['lat'] ?? '';
$lng     = $_POST['lng'] ?? '';

$tipos_validos = ['parque', 'deportivo', 'plaza'];

if (!$id_area || empty($nombre) || empty($colonia) || !in_array($tipo, $tipos_validos) || $lat === '' || $lng === '') {
    header('Location: index.php?error=servidor#tab-areas');
    exit;
}

// Manejo de foto (opcional)
$foto_nombre = null;
if (!empty($_FILES['foto']['name'])) {
    $file       = $_FILES['foto'];
    $ext        = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $permitidos = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($ext, $permitidos) || $file['size'] > 3 * 1024 * 1024) {
        header('Location: index.php?error=servidor#tab-areas');
        exit;
    }

    $foto_nombre = 'area_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
    $destino = __DIR__ . '/../Assets/fotos/' . $foto_nombre;

    if (!move_uploaded_file($file['tmp_name'], $destino)) {
        header('Location: index.php?error=servidor#tab-areas');
        exit;
    }
}

if ($foto_nombre) {
    $stmt = $conn->prepare("UPDATE AREA SET nombre=?, colonia=?, tipo=?, lat=?, lng=?, foto=? WHERE id_area=?");
    $stmt->bind_param("sssddsi", $nombre, $colonia, $tipo, $lat, $lng, $foto_nombre, $id_area);
} else {
    $stmt = $conn->prepare("UPDATE AREA SET nombre=?, colonia=?, tipo=?, lat=?, lng=? WHERE id_area=?");
    $stmt->bind_param("sssddi", $nombre, $colonia, $tipo, $lat, $lng, $id_area);
}

if ($stmt->execute()) {
    header('Location: index.php?exito=1#tab-areas');
} else {
    header('Location: index.php?error=servidor#tab-areas');
}
exit;
?>
