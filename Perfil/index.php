<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - SafePark</title>
    <link rel="stylesheet" href="../CSS/styles.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php
session_start();
require_once '../database/conexion.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../Login/index.php');
    exit;
}

$id = $_SESSION['id_usuario'];
$stmt = $conn->prepare("SELECT nombre, email, fecha_registro FROM USUARIO WHERE id_usuario = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

$nombre = htmlspecialchars($usuario['nombre']);
$email  = htmlspecialchars($usuario['email']);
$fecha  = date('d/m/Y', strtotime($usuario['fecha_registro']));
$inicial = strtoupper(mb_substr($nombre, 0, 1));
?>

    <nav class="navbar">
        <a class="nav-logo" href="../Home/index.html">
            <img src="../Assets/logo.png" height="50" alt="SafePark logo">
            <span class="nav-logo-text">Safe<span>Park</span></span>
        </a>
        <div class="nav-links">
            <a class="nav-link" href="../Home/index.html">Inicio</a>
            <a class="nav-link" href="../Mapa/index.html">Mapa</a>
            <a class="nav-link" href="../Explorar/index.html">Explorar</a>
            <a class="nav-link" href="../Reportar/index.html">Reportar</a>
            <a class="nav-link" href="../Comunidad/index.html">Comunidad</a>
        </div>
        <div class="nav-right">
            <a class="btn-nav" href="../Login/index.php">Cerrar sesión</a>
        </div>
    </nav>

    <!-- Hero del perfil -->
    <div class="perfil-hero">
        <div class="perfil-avatar"><?= $inicial ?></div>
        <div class="perfil-hero-info">
            <h2><?= $nombre ?></h2>
            <p class="perfil-email"><?= $email ?></p>
            <p class="perfil-fecha">Miembro desde <?= $fecha ?></p>
            <div class="perfil-badges">
                <span class="pbadge">🌱 Ciudadano activo</span>
                <span class="pbadge">📍 Ciudad Juárez</span>
            </div>
        </div>
        <div class="perfil-stats">
            <div class="pstat"><div class="pstat-n">0</div><div class="pstat-l">Reportes</div></div>
            <div class="pstat"><div class="pstat-n">0</div><div class="pstat-l">Favoritos</div></div>
            <div class="pstat"><div class="pstat-n">0</div><div class="pstat-l">Puntos</div></div>
        </div>
    </div>

    <!-- Layout principal -->
    <div class="perfil-layout">

        <!-- Columna izquierda: datos y logros -->
        <div class="perfil-side">

            <div class="pcard">
                <div class="pcard-title">Información personal</div>
                <div class="pinfo-row">
                    <span class="pinfo-label">Nombre</span>
                    <span class="pinfo-val"><?= $nombre ?></span>
                </div>
                <div class="pinfo-row">
                    <span class="pinfo-label">Correo</span>
                    <span class="pinfo-val"><?= $email ?></span>
                </div>
                <div class="pinfo-row">
                    <span class="pinfo-label">Miembro desde</span>
                    <span class="pinfo-val"><?= $fecha ?></span>
                </div>
                <div class="pinfo-row">
                    <span class="pinfo-label">Ciudad</span>
                    <span class="pinfo-val">Ciudad Juárez</span>
                </div>
                <button class="btn-editar">✏️ Editar perfil</button>
            </div>

            <div class="pcard">
                <div class="pcard-title">🏅 Logros</div>
                <div class="logro-item">
                    <div class="logro-icon lock">🔒</div>
                    <div class="logro-info">
                        <div class="logro-nombre">Primer reporte</div>
                        <div class="logro-desc">Envía tu primer reporte ciudadano</div>
                    </div>
                    <div class="logro-pts">+10 pts</div>
                </div>
                <div class="logro-item">
                    <div class="logro-icon lock">🔒</div>
                    <div class="logro-info">
                        <div class="logro-nombre">Guardián del Parque</div>
                        <div class="logro-desc">10 reportes aprobados consecutivos</div>
                    </div>
                    <div class="logro-pts">+50 pts</div>
                </div>
                <div class="logro-item">
                    <div class="logro-icon lock">🔒</div>
                    <div class="logro-info">
                        <div class="logro-nombre">Explorador</div>
                        <div class="logro-desc">Visita 5 áreas verdes distintas</div>
                    </div>
                    <div class="logro-pts">+20 pts</div>
                </div>
                <div class="logro-item">
                    <div class="logro-icon lock">🔒</div>
                    <div class="logro-info">
                        <div class="logro-nombre">Organizador</div>
                        <div class="logro-desc">Crea tu primer evento comunitario</div>
                    </div>
                    <div class="logro-pts">+40 pts</div>
                </div>
            </div>

        </div>

        <!-- Columna derecha: actividad -->
        <div class="perfil-main">

            <div class="perfil-tabs">
                <div class="perfil-tab active" onclick="cambiarTab(this,'reportes')">📋 Mis reportes</div>
                <div class="perfil-tab" onclick="cambiarTab(this,'favoritos')">❤️ Favoritos</div>
                <div class="perfil-tab" onclick="cambiarTab(this,'actividad')">📰 Actividad</div>
            </div>

            <!-- Tab: Mis reportes -->
            <div class="ptab-panel" id="tab-reportes">
                <div class="pempty">
                    <div class="pempty-icon">📋</div>
                    <div class="pempty-text">Aún no has enviado reportes</div>
                    <div class="pempty-sub">Ayuda a la comunidad reportando áreas verdes</div>
                    <a class="btn-ir" href="../Reportar/index.html">Crear reporte</a>
                </div>
            </div>

            <!-- Tab: Favoritos -->
            <div class="ptab-panel" id="tab-favoritos" style="display:none;">
                <div class="pempty">
                    <div class="pempty-icon">❤️</div>
                    <div class="pempty-text">Aún no tienes favoritos</div>
                    <div class="pempty-sub">Explora áreas verdes y guárdalas aquí</div>
                    <a class="btn-ir" href="../Explorar/index.html">Explorar áreas</a>
                </div>
            </div>

            <!-- Tab: Actividad -->
            <div class="ptab-panel" id="tab-actividad" style="display:none;">
                <div class="pempty">
                    <div class="pempty-icon">📰</div>
                    <div class="pempty-text">Sin actividad reciente</div>
                    <div class="pempty-sub">Tu historial de acciones aparecerá aquí</div>
                    <a class="btn-ir" href="../Comunidad/index.html">Ver comunidad</a>
                </div>
            </div>

        </div>
    </div>

    <div class="footer-bar">SafePark · Mi Perfil · Ciudad Juárez</div>

    <script src="perfil.js"></script>
</body>
</html>
