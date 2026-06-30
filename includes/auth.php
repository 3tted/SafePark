<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function requiere_sesion($redirect_base = '../') {
    if (!isset($_SESSION['id_usuario'])) {
        header('Location: ' . $redirect_base . 'Login/index.php');
        exit;
    }
}

function es_admin($conn, $id_usuario) {
    $stmt = $conn->prepare("SELECT rol FROM USUARIO WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->bind_result($rol);
    $stmt->fetch();
    $stmt->close();
    return $rol === 'admin';
}

function requiere_admin($conn, $redirect_base = '../') {
    requiere_sesion($redirect_base);
    if (!es_admin($conn, $_SESSION['id_usuario'])) {
        header('Location: ' . $redirect_base . 'Home/index.php');
        exit;
    }
}
?>
