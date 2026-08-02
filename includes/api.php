<?php
// Configuración del API, buscada en este orden:
//   1. includes/config_api.php  (para hostings que no soportan SetEnv)
//   2. variables de entorno     (Railway, o .htaccess con SetEnv)
//   3. localhost                (desarrollo local, sin configurar nada)
if (file_exists(__DIR__ . '/config_api.php')) {
    require_once __DIR__ . '/config_api.php';
}

// SafePark tiene dos APIs propias, cada una en su propio servicio:
//   Usuarios -> cuentas, autenticación, perfiles y roles
//   Datos    -> áreas, reportes, eventos, comentarios, reacciones y favoritos
define('API_USUARIOS', defined('CFG_API_USUARIOS') ? CFG_API_USUARIOS : (getenv('SAFEPARK_API_USUARIOS') ?: 'http://localhost:3001/api'));
define('API_DATOS',    defined('CFG_API_DATOS')    ? CFG_API_DATOS    : (getenv('SAFEPARK_API_DATOS')    ?: 'http://localhost:3002/api'));

// El secreto solo hace falta para las operaciones de escritura; las lecturas
// son públicas. Debe coincidir con API_SECRET de ambos servicios en Railway.
define('API_SECRET', defined('CFG_API_SECRET') ? CFG_API_SECRET : (getenv('SAFEPARK_API_SECRET') ?: ''));

/**
 * Decide a qué servicio va cada llamada, mirando el inicio de la ruta.
 *
 * Se resuelve aquí para que las páginas sigan llamando api_get('/usuarios/5')
 * sin preocuparse por cuál servicio la atiende. Si mañana se dividen distinto,
 * solo cambia esta función.
 */
function api_url(string $endpoint): string {
    $de_usuarios = ['/usuarios', '/auth'];
    foreach ($de_usuarios as $prefijo) {
        if (strpos($endpoint, $prefijo) === 0) return API_USUARIOS . $endpoint;
    }
    return API_DATOS . $endpoint;
}

// Cabeceras comunes a todas las llamadas que modifican datos
function api_headers(): array {
    $headers = ['Content-Type: application/json'];
    if (API_SECRET !== '') {
        $headers[] = 'X-API-Secret: ' . API_SECRET;
    }
    return $headers;
}

// Ejecuta la peticion y devuelve el JSON decodificado.
// $metodo es POST, PUT o DELETE; $data null para DELETE.
function api_request(string $metodo, string $endpoint, ?array $data = null): array {
    $ch = curl_init(api_url($endpoint));
    $opciones = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $metodo,
        CURLOPT_HTTPHEADER     => api_headers(),
        CURLOPT_TIMEOUT        => 10,
    ];
    if ($data !== null) {
        $opciones[CURLOPT_POSTFIELDS] = json_encode($data);
    }
    curl_setopt_array($ch, $opciones);

    $json = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($json === false) {
        error_log("api_request $metodo $endpoint fallo: $err");
        return ['ok' => false];
    }
    return json_decode($json, true) ?? ['ok' => false];
}

function api_get(string $endpoint): array {
    $ctx  = stream_context_create(['http' => ['timeout' => 8]]);
    $json = @file_get_contents(api_url($endpoint), false, $ctx);
    if ($json === false) return [];
    return json_decode($json, true) ?? [];
}

/**
 * Pide varios endpoints a la vez.
 *
 * Cada llamada al API cuesta cerca de un segundo, casi todo en el saludo TLS y
 * el viaje de ida y vuelta al servidor. Una página como Comunidad necesita
 * cinco, y hacerlas en fila tardaba unos cuatro segundos. Lanzándolas en
 * paralelo el costo total es el de la más lenta.
 *
 * Recibe un arreglo ['clave' => '/endpoint'] y devuelve ['clave' => resultado],
 * conservando las claves para que la página lea cada respuesta por su nombre.
 */
function api_get_multi(array $endpoints): array {
    if (!$endpoints) return [];

    // Varios hostings compartidos deshabilitan parte de curl_multi_*. InfinityFree,
    // por ejemplo, deja curl_multi_init pero bloquea curl_multi_exec — por eso hay
    // que comprobar todas las que se usan, no solo la primera.
    // Sin ellas se piden una por una: más lento, pero funciona igual.
    foreach (['curl_multi_init', 'curl_multi_exec', 'curl_multi_select',
              'curl_multi_getcontent', 'curl_multi_add_handle'] as $requerida) {
        if (!function_exists($requerida)) {
            $resultados = [];
            foreach ($endpoints as $clave => $endpoint) {
                $resultados[$clave] = api_get($endpoint);
            }
            return $resultados;
        }
    }

    $multi   = curl_multi_init();
    $handles = [];

    foreach ($endpoints as $clave => $endpoint) {
        $ch = curl_init(api_url($endpoint));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_ENCODING       => '',   // acepta gzip: menos bytes por la red
        ]);
        curl_multi_add_handle($multi, $ch);
        $handles[$clave] = $ch;
    }

    do {
        $estado = curl_multi_exec($multi, $pendientes);
        if ($pendientes) curl_multi_select($multi, 1.0);
    } while ($pendientes && $estado === CURLM_OK);

    $resultados = [];
    foreach ($handles as $clave => $ch) {
        $json = curl_multi_getcontent($ch);
        $resultados[$clave] = $json === false ? [] : (json_decode($json, true) ?? []);
        curl_multi_remove_handle($multi, $ch);
        curl_close($ch);
    }
    curl_multi_close($multi);

    return $resultados;
}

function api_post(string $endpoint, array $data): array {
    return api_request('POST', $endpoint, $data);
}

function api_put(string $endpoint, array $data): array {
    return api_request('PUT', $endpoint, $data);
}

function api_delete(string $endpoint): array {
    return api_request('DELETE', $endpoint);
}
