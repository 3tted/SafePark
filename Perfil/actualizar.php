<?php
session_start();
require_once '../database/conexion.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../Login/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: editar.php');
    exit;
}

$id       = $_SESSION['id_usuario'];
$nombre   = trim($_POST['nombre']);
$email    = trim($_POST['email']);
$password = $_POST['password'];
$confirmar = $_POST['confirmar'];

// Verificar email duplicado (excluyendo el propio usuario)
$check = $conn->prepare("SELECT id_usuario FROM USUARIO WHERE email = ? AND id_usuario != ?");
$check->bind_param("si", $email, $id);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    header('Location: editar.php?error=email');
    exit;
}

// Validar contraseñas si se quiere cambiar
if (!empty($password)) {
    if ($password !== $confirmar) {
        header('Location: editar.php?error=passwords');
        exit;
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
} else {
    $hash = null;
}

// Manejo de foto
$foto_nombre = null;
if (!empty($_FILES['foto_perfil']['name'])) {
    $file     = $_FILES['foto_perfil'];
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $permitidos = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($ext, $permitidos) || $file['size'] > 2 * 1024 * 1024) {
        header('Location: editar.php?error=foto');
        exit;
    }

    $foto_nombre = 'u' . $id . '_' . time() . '.' . $ext;
    $destino = __DIR__ . '/../Assets/fotos/' . $foto_nombre;

    if (!move_uploaded_file($file['tmp_name'], $destino)) {
        header('Location: editar.php?error=servidor');
        exit;
    }
}

// Actualizar en DB
if ($hash && $foto_nombre) {
    $stmt = $conn->prepare("UPDATE USUARIO SET nombre=?, email=?, contrasena_hash=?, foto_perfil=? WHERE id_usuario=?");
    $stmt->bind_param("ssssi", $nombre, $email, $hash, $foto_nombre, $id);
} elseif ($hash) {
    $stmt = $conn->prepare("UPDATE USUARIO SET nombre=?, email=?, contrasena_hash=? WHERE id_usuario=?");
    $stmt->bind_param("sssi", $nombre, $email, $hash, $id);
} elseif ($foto_nombre) {
    $stmt = $conn->prepare("UPDATE USUARIO SET nombre=?, email=?, foto_perfil=? WHERE id_usuario=?");
    $stmt->bind_param("sssi", $nombre, $email, $foto_nombre, $id);
} else {
    $stmt = $conn->prepare("UPDATE USUARIO SET nombre=?, email=? WHERE id_usuario=?");
    $stmt->bind_param("ssi", $nombre, $email, $id);
}

if ($stmt->execute()) {
    $_SESSION['nombre'] = $nombre;
    header('Location: editar.php?exito=1');
} else {
    header('Location: editar.php?error=servidor');
}
exit;
?>
