<?php
require_once '../includes/auth.php';
require_once '../includes/api.php';
requiere_admin();

$id_area = intval($_POST['id_area'] ?? 0);
if (!$id_area) { header('Location: index.php?error=servidor#tab-areas'); exit; }

$resultado = api_delete('/areas/' . $id_area);

header($resultado['ok'] ? 'Location: index.php?exito=1#tab-areas' : 'Location: index.php?error=servidor#tab-areas');
exit;
