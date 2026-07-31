<?php
require_once '../includes/auth.php';
require_once '../includes/api.php';
requiere_admin();

$id_usuario = intval($_POST['id_usuario'] ?? 0);
$nuevo_rol  = $_POST['rol'] ?? '';
$validos    = ['usuario', 'admin'];

if (!$id_usuario || !in_array($nuevo_rol, $validos)) {
    header('Location: index.php?error=servidor'); exit;
}

$resultado = api_put('/usuarios/' . $id_usuario . '/rol', ['rol' => $nuevo_rol]);
header($resultado['ok'] ? 'Location: index.php?exito=1#tab-usuarios' : 'Location: index.php?error=servidor#tab-usuarios');
exit;
