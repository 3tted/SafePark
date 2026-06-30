<?php
session_start();
require_once '../database/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmar = $_POST['confirmar'];

    if ($password !== $confirmar) {
        header('Location: index.php?error=passwords');
        exit;
    }

    // Verificar si el email ya existe
    $check = $conn->prepare("SELECT id_usuario FROM USUARIO WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        header('Location: index.php?error=email');
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO USUARIO (nombre, email, contrasena_hash) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nombre, $email, $hash);

    if ($stmt->execute()) {
        $_SESSION['id_usuario'] = $conn->insert_id;
        $_SESSION['nombre']     = $nombre;
        header('Location: ../Home/index.php');
        exit;
    }

    header('Location: index.php?error=servidor');
    exit;
}
?>
