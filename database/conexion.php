<?php
$host     = 'localhost';
$db       = 'safepark_db';
$user     = 'root';
$password = '';

$conn = new mysqli($host, $user, $password, $db);

if ($conn->connect_error) {
    die('Error de conexion: ' . $conn->connect_error);
}

$conn->set_charset('utf8');
?>
