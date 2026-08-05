<?php
require_once __DIR__ . '/auth.php';

$es_admin = isset($_SESSION['id_usuario']) && es_admin();
?>
<nav class="navbar">
    <a class="nav-logo" href="<?= $nav_base ?>Home/index.php">
        <img src="<?= $nav_base ?>Assets/logo.png" height="50" alt="SafePark logo">
        <span class="nav-logo-text">Safe<span>Park</span></span>
    </a>
    <div class="nav-links">
        <a class="nav-link <?= ($nav_active==='inicio') ? 'active':'' ?>" href="<?= $nav_base ?>Home/index.php">Inicio</a>
        <a class="nav-link <?= ($nav_active==='mapa') ? 'active':'' ?>" href="<?= $nav_base ?>Mapa/index.php">Mapa</a>
        <a class="nav-link <?= ($nav_active==='explorar') ? 'active':'' ?>" href="<?= $nav_base ?>Explorar/index.php">Explorar</a>
        <a class="nav-link <?= ($nav_active==='reportar') ? 'active':'' ?>" href="<?= $nav_base ?>Reportar/index.php">Reportar</a>
        <a class="nav-link <?= ($nav_active==='comunidad') ? 'active':'' ?>" href="<?= $nav_base ?>Comunidad/index.php">Comunidad</a>
    </div>
    <div class="nav-right">
        <?php if ($es_admin): ?>
            <a class="nav-link" href="<?= $nav_base ?>Admin/index.php" style="background:var(--g4);color:var(--g1);font-weight:800;">⚙️ Admin</a>
        <?php endif; ?>
        <a class="nav-link <?= ($nav_active==='perfil') ? 'active':'' ?>" href="<?= $nav_base ?>Perfil/index.php">Mi perfil</a>
        <a class="btn-nav" href="<?= $nav_base ?>Login/logout.php">Cerrar sesión</a>
    </div>
</nav>

<style>
/* Los avisos flotan sobre el contenido, fuera del flujo: así aparecer o
   desaparecer no cambia la altura de la página ni obliga al mapa a recalcular.

   El "top" lo pone el script, no esta hoja: en móvil el navbar es de alto
   variable (height:auto con flex-wrap) y un valor fijo no le atina. */
[class^="msg-ok"], [class^="msg-err"] {
    position: fixed;
    left: 50%;
    transform: translateX(-50%);
    z-index: 1100;                    /* el mapa y sus botones usan 1000 */
    margin: 0;
    width: max-content;
    max-width: min(92vw, 560px);
    padding: 12px 22px;
    border-radius: 10px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.18);
    text-align: center;
}
</style>

<script>
// Los avisos de "guardado correctamente" viven en la URL: PHP redirige a
// index.php?exito=1 y el mensaje se pinta mientras ese parámetro siga ahí.
// Esta función lo quita de la URL y desvanece el aviso.
//
// Va en el navbar porque todas las páginas que muestran avisos lo incluyen.
// El navbar se incluye ARRIBA del aviso, así que la función se ejecuta hasta
// DOMContentLoaded: antes de eso el aviso todavía no existe en el documento.
function limpiarAvisos() {
    // Todas las clases de aviso empiezan igual: msg-ok, msg-ok-mapa,
    // msg-err-feed, msg-err-global...
    const avisos = document.querySelectorAll('[class^="msg-ok"], [class^="msg-err"]');
    if (!avisos.length) return;

    // El navbar queda pegado arriba, así que el aviso se coloca justo debajo.
    // Se mide en vez de suponer 60px porque en móvil crece al acomodarse
    // en varios renglones.
    const nav = document.querySelector('.navbar');
    const abajoDelNav = (nav ? nav.getBoundingClientRect().height : 60) + 12;
    avisos.forEach(a => a.style.top = abajoDelNav + 'px');

    // Se limpian los parámetros de la URL sin recargar, para que al refrescar
    // no reaparezca el mensaje
    const url = new URL(window.location);
    if (url.searchParams.has('exito') || url.searchParams.has('error')) {
        url.searchParams.delete('exito');
        url.searchParams.delete('error');
        history.replaceState(null, '', url);
    }

    // Y se desvanecen solos. Cuatro segundos alcanzan para leerlos.
    setTimeout(() => {
        avisos.forEach(a => {
            a.style.transition = 'opacity .4s';
            a.style.opacity = '0';
            // Se quita del flujo al terminar, para que no deje un hueco
            setTimeout(() => a.remove(), 400);
        });
    }, 4000);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', limpiarAvisos);
} else {
    limpiarAvisos();   // por si el navbar se incluyera al final de la página
}
</script>
