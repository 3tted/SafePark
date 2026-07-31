<?php
// URL del API. En local usa localhost; al desplegar, define la variable de
// entorno SAFEPARK_API (ej. https://safepark-api.up.railway.app/api)
define('API_BASE', getenv('SAFEPARK_API') ?: 'http://localhost:3000/api');

function api_get(string $endpoint): array {
    $url = API_BASE . $endpoint;
    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
    $json = @file_get_contents($url, false, $ctx);
    if ($json === false) return [];
    return json_decode($json, true) ?? [];
}

function api_post(string $endpoint, array $data): array {
    $url  = API_BASE . $endpoint;
    $body = json_encode($data);
    $ch   = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $json = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($json === false) {
        error_log('api_post curl error: ' . $err);
        return ['ok' => false];
    }
    return json_decode($json, true) ?? ['ok' => false];
}

function api_put(string $endpoint, array $data): array {
    $url  = API_BASE . $endpoint;
    $body = json_encode($data);
    $ch   = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'PUT',
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $json = curl_exec($ch);
    curl_close($ch);
    if ($json === false) return ['ok' => false];
    return json_decode($json, true) ?? ['ok' => false];
}