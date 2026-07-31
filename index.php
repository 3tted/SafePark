<?php
// Portada del sitio: manda al usuario a donde corresponde.
// Redirigir (en vez de incluir la página aquí) mantiene la URL en /Login/ o
// /Home/, que es lo que necesitan las rutas relativas de CSS y JavaScript.

require_once __DIR__ . '/includes/auth.php';

$destino = isset($_SESSION['id_usuario']) ? 'Home/index.php' : 'Login/index.php';
header('Location: ' . $destino);
exit;
