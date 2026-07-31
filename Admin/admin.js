function cambiarTab(el, tab) {
    document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.atab-panel').forEach(p => p.style.display = 'none');
    el.classList.add('active');
    document.getElementById('tab-' + tab).style.display = 'block';
}

function abrirEditarArea(area) {
    document.getElementById('edit-id').value = area.id_area;
    document.getElementById('edit-nombre').value = area.nombre;
    document.getElementById('edit-colonia').value = area.colonia || '';
    document.getElementById('edit-direccion').value = area.direccion || '';
    document.getElementById('edit-horario').value = area.horario || '';
    document.getElementById('edit-tipo').value = area.tipo || 'parque';
    document.getElementById('edit-lat').value = area.lat || '';
    document.getElementById('edit-lng').value = area.lng || '';
    document.getElementById('modal-editar-area').style.display = 'flex';
}

function cerrarEditarArea() {
    document.getElementById('modal-editar-area').style.display = 'none';
}

// Recordar el tab activo según el hash de la URL (#tab-areas, #tab-usuarios, etc.)
window.addEventListener('DOMContentLoaded', () => {
    const hash = window.location.hash.replace('#tab-', '');
    if (!hash) return;
    const tab = document.querySelector(`.admin-tab[onclick*="'${hash}'"]`);
    if (tab) cambiarTab(tab, hash);
});
