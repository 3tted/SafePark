<?php
// Proxy para Nominatim — evita bloqueos CORS y de User-Agent desde el navegador
header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
if (!$q) { echo '[]'; exit; }

$url = 'https://nominatim.openstreetmap.org/search'
     . '?q=' . urlencode($q . ' Ciudad Juarez Chihuahua Mexico')
     . '&format=json&limit=5&countrycodes=mx&addressdetails=0';

$contexto = stream_context_create([
    'http' => [
        'method'  => 'GET',
        'timeout' => 5,
        'header'  => "User-Agent: SafePark/1.0 (safepark@localhost)\r\nAccept-Language: es\r\n"
    ]
]);

$respuesta = @file_get_contents($url, false, $contexto);
echo $respuesta !== false ? $respuesta : '[]';
