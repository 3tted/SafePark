<?php
session_start();
require_once '../includes/api.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';

    if ($password !== $confirmar) {
        header('Location: ./?error=passwords');
        exit;
    }

    // El API valida el correo duplicado y genera el hash
    $r = api_post('/usuarios', [
        'nombre'   => $nombre,
        'email'    => $email,
        'password' => $password
    ]);

    if (!empty($r['ok'])) {
        $_SESSION['id_usuario'] = $r['id_usuario'];
        $_SESSION['nombre']     = $nombre;
        $_SESSION['rol']        = 'usuario';
        header('Location: ../Home/');
        exit;
    }

    $destino = ($r['error'] ?? '') === 'email_duplicado' ? 'email' : 'servidor';
    header('Location: ./?error=' . $destino);
    exit;
}
?>
