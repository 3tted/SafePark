<?php
require_once '../database/conexion.php';
require_once '../includes/auth.php';
require_once '../includes/api.php';
requiere_admin($conn);

$id_area = intval($_POST['id_area'] ?? 0);
if (!$id_area) { header('Location: index.php?error=servidor#tab-areas'); exit; }

$ch = curl_init(API_BASE . '/areas/' . $id_area);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST  => 'DELETE',
    CURLOPT_TIMEOUT        => 10,
]);
$json = curl_exec($ch);
curl_close($ch);
$resultado = json_decode($json, true) ?? ['ok' => false];

header($resultado['ok'] ? 'Location: index.php?exito=1#tab-areas' : 'Location: index.php?error=servidor#tab-areas');
exit;
