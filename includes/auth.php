<?php
// session_status evita llamar session_start() dos veces si ya fue iniciada por otro include
if (session_status() === PHP_SESSION_NONE) session_start();

// Redirige al login si no hay sesión activa
function requiere_sesion($redirect_base = '../') {
    if (!isset($_SESSION['id_usuario'])) {
        header('Location: ' . $redirect_base . 'Login/index.php');
        exit;
    }
}

// Consulta la BD en lugar de confiar en $_SESSION['rol'] para evitar que un usuario
// manipule su propia sesión y obtenga privilegios de admin
function es_admin($conn, $id_usuario) {
    $stmt = $conn->prepare("SELECT rol FROM USUARIO WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->bind_result($rol);
    $stmt->fetch();
    $stmt->close();
    return $rol === 'admin';
}

// Protege rutas de admin: primero verifica sesión, luego rol
function requiere_admin($conn, $redirect_base = '../') {
    requiere_sesion($redirect_base);
    if (!es_admin($conn, $_SESSION['id_usuario'])) {
        header('Location: ' . $redirect_base . 'Home/index.php');
        exit;
    }
}
?>
