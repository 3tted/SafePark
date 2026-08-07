<?php
session_start();
require_once '../includes/api.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // La contraseña se verifica en el API; aquí solo llega el resultado
    $r = api_post('/auth/login', ['email' => $email, 'password' => $password]);

    if (!empty($r['ok'])) {
        $_SESSION['id_usuario'] = $r['usuario']['id_usuario'];
        $_SESSION['nombre']     = $r['usuario']['nombre'];
        $_SESSION['rol']        = $r['usuario']['rol'];
        header('Location: ../Home/');
        exit;
    }

    header('Location: ./?error=credenciales');
    exit;
}
?>
