// Cambia entre las pestañas del perfil (Mis reportes, Favoritos, Actividad).
// Los tres paneles ya vienen en el HTML; solo se muestra uno a la vez.
function cambiarTab(el, tab) {
    // Quita el resaltado de todas las pestañas
    document.querySelectorAll('.perfil-tab').forEach(t => t.classList.remove('active'));
    // Esconde todos los paneles
    document.querySelectorAll('.ptab-panel').forEach(p => p.style.display = 'none');
    // Resalta la pestaña tocada
    el.classList.add('active');
    // Y muestra su panel correspondiente
    document.getElementById('tab-' + tab).style.display = 'block';
}
