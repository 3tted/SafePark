<?php
// session_status evita llamar session_start() dos veces si ya fue iniciada por otro include
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/api.php';

// ¿Está viendo la página alguien sin cuenta?
//
// Inicio, Mapa, Explorar y Comunidad se pueden ver sin registrarse: una
// plataforma ciudadana que exige una cuenta antes de dejarte ver si tu parque
// es seguro pierde justo a la gente que más la necesita.
//
// Un invitado solo mira. Todo lo que escribe —reportar, comentar, reaccionar,
// registrar un área, marcar favoritos— sigue exigiendo sesión, y no solo
// escondiendo el botón: cada script de guardado llama a requiere_sesion().
function es_invitado(): bool {
    return !isset($_SESSION['id_usuario']);
}

// Redirige al login si no hay sesión activa
function requiere_sesion($redirect_base = '../') {
    if (!isset($_SESSION['id_usuario'])) {
        header('Location: ' . $redirect_base . 'Login/index.php');
        exit;
    }
}

// Consulta el rol al API. Es la fuente de verdad, pero cuesta una llamada HTTP.
function consultar_rol($id_usuario): ?string {
    $r = api_get('/usuarios/' . intval($id_usuario) . '/rol');
    return $r['rol'] ?? null;
}

// Chequeo rápido para decidir qué mostrar en la interfaz (el enlace de Admin en
// la navbar, el botón de editar área). Usa el rol guardado en la sesión al
// iniciar sesión — las sesiones de PHP viven en el servidor, así que el usuario
// no puede alterarlas desde el navegador.
//
// NO uses esta función para proteger una página: usa requiere_admin().
function es_admin($id_usuario = null): bool {
    if (isset($_SESSION['rol'])) return $_SESSION['rol'] === 'admin';

    // Sesión iniciada antes de que el rol se guardara: lo pedimos y lo cacheamos
    $id = $id_usuario ?? ($_SESSION['id_usuario'] ?? null);
    if (!$id) return false;

    $rol = consultar_rol($id);
    if ($rol !== null) $_SESSION['rol'] = $rol;
    return $rol === 'admin';
}

// Protege las páginas de administración. A diferencia de es_admin(), este
// verifica contra el API en cada carga: si a alguien le quitan el rol, pierde
// el acceso de inmediato sin tener que cerrar sesión.
function requiere_admin($redirect_base = '../') {
    requiere_sesion($redirect_base);

    $rol = consultar_rol($_SESSION['id_usuario']);
    $_SESSION['rol'] = $rol;   // mantiene sincronizado el cache de es_admin()

    if ($rol !== 'admin') {
        header('Location: ' . $redirect_base . 'Home/index.php');
        exit;
    }
}
?>
