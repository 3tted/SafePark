<?php
require_once '../includes/auth.php';
require_once '../includes/api.php';
require_once '../includes/fotos.php';
requiere_sesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: editar.php');
    exit;
}

$id        = $_SESSION['id_usuario'];
$nombre    = trim($_POST['nombre'] ?? '');
$email     = trim($_POST['email'] ?? '');
$password  = $_POST['password'] ?? '';
$confirmar = $_POST['confirmar'] ?? '';

if (!empty($password) && $password !== $confirmar) {
    header('Location: editar.php?error=passwords');
    exit;
}

// La foto la guarda PHP; al API solo viaja el nombre del archivo
$foto_nombre = guardar_foto($_FILES['foto_perfil'] ?? null, 'u' . $id, 2);
if ($foto_nombre === false) {
    header('Location: editar.php?error=foto');
    exit;
}

$datos = ['nombre' => $nombre, 'email' => $email];
if (!empty($password)) $datos['password']    = $password;
if ($foto_nombre)      $datos['foto_perfil'] = $foto_nombre;

$r = api_put('/usuarios/' . $id, $datos);

if (!empty($r['ok'])) {
    $_SESSION['nombre'] = $nombre;
    header('Location: editar.php?exito=1');
    exit;
}

$destino = ($r['error'] ?? '') === 'email_duplicado' ? 'email' : 'servidor';
header('Location: editar.php?error=' . $destino);
exit;
?>
