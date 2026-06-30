<?php
require_once '../database/conexion.php';
require_once '../includes/auth.php';
requiere_admin($conn);

$id_area = intval($_POST['id_area'] ?? 0);

if ($id_area) {
    // Borrar registros relacionados primero (evita error de llave foránea)
    $tablas_relacionadas = ['EVENTO', 'REPORTE', 'FAVORITO'];
    foreach ($tablas_relacionadas as $tabla) {
        $del = $conn->prepare("DELETE FROM $tabla WHERE id_area = ?");
        $del->bind_param("i", $id_area);
        $del->execute();
    }

    $stmt = $conn->prepare("DELETE FROM AREA WHERE id_area = ?");
    $stmt->bind_param("i", $id_area);
    $stmt->execute();
}

header('Location: index.php?exito=1#tab-areas');
exit;
?>
