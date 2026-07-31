<?php
require_once '../database/conexion.php';
require_once '../includes/auth.php';
require_once '../includes/fotos.php';
requiere_sesion();

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
$foto_nombre = guardar_foto($_FILES['foto_perfil'] ?? null, 'u' . $id, 2);
if ($foto_nombre === false) {
    header('Location: editar.php?error=foto');
    exit;
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
