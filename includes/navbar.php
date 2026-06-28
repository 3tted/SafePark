<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$es_admin = false;
if (isset($_SESSION['id_usuario'])) {
    require_once __DIR__ . '/../database/conexion.php';
    $__stmt = $conn->prepare("SELECT rol FROM USUARIO WHERE id_usuario = ?");
    $__stmt->bind_param("i", $_SESSION['id_usuario']);
    $__stmt->execute();
    $__stmt->bind_result($__rol);
    $__stmt->fetch();
    $__stmt->close();
    $es_admin = ($__rol === 'admin');
}
?>
<nav class="navbar">
    <a class="nav-logo" href="<?= $nav_base ?>Home/index.html">
        <img src="<?= $nav_base ?>Assets/logo.png" height="50" alt="SafePark logo">
        <span class="nav-logo-text">Safe<span>Park</span></span>
    </a>
    <div class="nav-links">
        <a class="nav-link <?= ($nav_active==='inicio') ? 'active':'' ?>" href="<?= $nav_base ?>Home/index.html">Inicio</a>
        <a class="nav-link <?= ($nav_active==='mapa') ? 'active':'' ?>" href="<?= $nav_base ?>Mapa/index.html">Mapa</a>
        <a class="nav-link <?= ($nav_active==='explorar') ? 'active':'' ?>" href="<?= $nav_base ?>Explorar/index.html">Explorar</a>
        <a class="nav-link <?= ($nav_active==='reportar') ? 'active':'' ?>" href="<?= $nav_base ?>Reportar/index.php">Reportar</a>
        <a class="nav-link <?= ($nav_active==='comunidad') ? 'active':'' ?>" href="<?= $nav_base ?>Comunidad/index.php">Comunidad</a>
    </div>
    <div class="nav-right" style="display:flex;align-items:center;gap:10px;">
        <?php if ($es_admin): ?>
            <a class="nav-link" href="<?= $nav_base ?>Admin/index.php" style="background:var(--g4);color:var(--g1);font-weight:800;">⚙️ Admin</a>
        <?php endif; ?>
        <a class="nav-link <?= ($nav_active==='perfil') ? 'active':'' ?>" href="<?= $nav_base ?>Perfil/index.php">Mi perfil</a>
        <a class="btn-nav" href="<?= $nav_base ?>Login/logout.php">Cerrar sesión</a>
    </div>
</nav>
