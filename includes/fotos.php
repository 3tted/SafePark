<?php
/**
 * Guarda una foto subida en Assets/fotos/ y devuelve el nombre del archivo.
 *
 * Las fotos siempre las escribe PHP (nunca el API) para que vivan en el mismo
 * servidor que las sirve — si el API está desplegado aparte, su disco es otro.
 *
 * @return string|null|false  nombre del archivo | null si no se envió foto | false si es inválida
 */
function guardar_foto(?array $file, string $prefijo = 'img', int $max_mb = 5) {
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;

    // No eligió ninguna foto — es válido, simplemente no hay nada que guardar
    if ($error === UPLOAD_ERR_NO_FILE || empty($file['name'])) return null;

    // Cualquier otro error (excede php.ini, subida parcial, sin carpeta temp)
    // sí debe avisarse: si no, se guardaría sin foto y sin explicación
    if ($error !== UPLOAD_ERR_OK) return false;

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) return false;
    if ($file['size'] > $max_mb * 1024 * 1024)           return false;

    $nombre  = $prefijo . '_' . time() . '_' . random_int(1000, 9999) . '.' . $ext;
    $destino = __DIR__ . '/../Assets/fotos/' . $nombre;

    return move_uploaded_file($file['tmp_name'], $destino) ? $nombre : false;
}
