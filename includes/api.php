<?php
// URL del API. En local usa localhost; al desplegar, define la variable de
// entorno SAFEPARK_API (ej. https://safepark-production.up.railway.app/api)
define('API_BASE', getenv('SAFEPARK_API') ?: 'http://localhost:3000/api');

// Secreto compartido con el API. Solo se necesita para las operaciones de
// escritura; las de lectura son publicas. Se configura con SAFEPARK_API_SECRET
// y debe coincidir con la variable API_SECRET del servicio en Railway.
define('API_SECRET', getenv('SAFEPARK_API_SECRET') ?: '');

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
    $ch = curl_init(API_BASE . $endpoint);
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
    $ctx  = stream_context_create(['http' => ['timeout' => 5]]);
    $json = @file_get_contents(API_BASE . $endpoint, false, $ctx);
    if ($json === false) return [];
    return json_decode($json, true) ?? [];
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
