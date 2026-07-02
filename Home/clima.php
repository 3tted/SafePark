<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../includes/config_clima.php';

$ciudad = 'Ciudad Juarez,MX';
$url = 'https://api.openweathermap.org/data/2.5/weather?q=' . urlencode($ciudad)
     . '&appid=' . OW_API_KEY . '&units=metric&lang=es';

$contexto  = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 5]]);
$respuesta = @file_get_contents($url, false, $contexto);

if ($respuesta === false) {
    http_response_code(502);
    echo json_encode(['cod' => 502, 'message' => 'No se pudo contactar el servicio de clima']);
    exit;
}

echo $respuesta;
