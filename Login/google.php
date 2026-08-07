<?php
// Paso 1 de "Iniciar sesión con Google": manda al usuario a Google.
//
// No se pide ningún permiso especial, solo el correo y el nombre público.

require_once '../includes/auth.php';

$config = __DIR__ . '/../includes/config_google.php';
if (!file_exists($config)) {
    header('Location: ./?error=google_config');
    exit;
}
require_once $config;

// El "state" es una cadena al azar que viaja a Google y regresa igual. Al
// volver se compara con la que quedó en la sesión: si no coincide, la petición
// no la inició este sitio y se descarta. Evita que alguien fabrique un enlace
// de retorno para colar una sesión ajena.
$state = bin2hex(random_bytes(16));
$_SESSION['google_state'] = $state;

$parametros = [
    'client_id'     => GOOGLE_CLIENT_ID,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'state'         => $state,
    'prompt'        => 'select_account',   // deja elegir cuenta aunque ya haya una activa
];

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($parametros));
exit;
