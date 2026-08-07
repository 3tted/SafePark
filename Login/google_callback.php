<?php
// Paso 2 de "Iniciar sesión con Google": Google devuelve al usuario aquí.
//
// Llega un código de un solo uso que se canjea por un token, y con ese token se
// consultan el correo y el nombre. Hasta que Google confirma la identidad no se
// toca la base de datos.

require_once '../includes/auth.php';
require_once '../includes/api.php';

$config = __DIR__ . '/../includes/config_google.php';
if (!file_exists($config)) {
    header('Location: ./?error=google_config');
    exit;
}
require_once $config;

function fallo_google(string $motivo, string $detalle = ''): void {
    if ($detalle !== '') error_log("Login con Google fallo ($motivo): $detalle");
    header('Location: ./?error=' . $motivo);
    exit;
}

// El usuario canceló o Google rechazó la petición
if (isset($_GET['error']) || !isset($_GET['code'])) {
    fallo_google('google_cancelado');
}

// Verificar el state antes que nada
$state_guardado = $_SESSION['google_state'] ?? '';
unset($_SESSION['google_state']);          // de un solo uso
if (empty($_GET['state']) || !hash_equals($state_guardado, $_GET['state'])) {
    fallo_google('google', 'state no coincide');
}

// --- Canjear el código por un token -------------------------------------
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'code'          => $_GET['code'],
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'grant_type'    => 'authorization_code',
    ]),
    CURLOPT_TIMEOUT        => 15,
]);
$respuesta = curl_exec($ch);
$err       = curl_error($ch);
curl_close($ch);

if ($respuesta === false) fallo_google('google', 'curl token: ' . $err);

$token = json_decode($respuesta, true);
if (empty($token['access_token'])) {
    fallo_google('google', 'sin access_token: ' . substr($respuesta, 0, 200));
}

// --- Pedir los datos del usuario ----------------------------------------
$ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token['access_token']],
    CURLOPT_TIMEOUT        => 15,
]);
$respuesta = curl_exec($ch);
curl_close($ch);

$perfil = json_decode($respuesta, true);
if (empty($perfil['email'])) {
    fallo_google('google', 'userinfo sin email');
}

// Google marca si el correo está confirmado. Si no lo está, no sirve como
// identidad: cualquiera podría haber puesto ese correo en su cuenta.
if (isset($perfil['email_verified']) && !$perfil['email_verified']) {
    fallo_google('google_sin_verificar');
}

// --- Entrar (o registrarse) ---------------------------------------------
$r = api_post('/auth/google', [
    'email'  => $perfil['email'],
    'nombre' => $perfil['name'] ?? null,
]);

if (empty($r['ok'])) fallo_google('servidor', json_encode($r));

$_SESSION['id_usuario'] = $r['usuario']['id_usuario'];
$_SESSION['nombre']     = $r['usuario']['nombre'];
$_SESSION['rol']        = $r['usuario']['rol'];

header('Location: ../Home/');
exit;
