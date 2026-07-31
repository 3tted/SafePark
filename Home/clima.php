<?php
// Proxy para OpenWeather — el navegador nunca ve la API key, solo consume este endpoint
header('Content-Type: application/json; charset=utf-8');

require_once '../includes/config_clima.php'; // define OW_API_KEY (no está en git)

$ciudad = 'Ciudad Juarez,MX';
$url = 'https://api.openweathermap.org/data/2.5/weather?q=' . urlencode($ciudad)
     . '&appid=' . OW_API_KEY . '&units=metric&lang=es';

// @ suprime warnings si falla la conexión; timeout 5s para no colgar la página
$contexto  = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 5]]);
$respuesta = @file_get_contents($url, false, $contexto);

if ($respuesta === false) {
    http_response_code(502);
    echo json_encode(['cod' => 502, 'message' => 'No se pudo contactar el servicio de clima']);
    exit;
}

echo $respuesta;
