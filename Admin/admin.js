function cambiarTab(el, tab) {
    document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.atab-panel').forEach(p => p.style.display = 'none');
    el.classList.add('active');
    document.getElementById('tab-' + tab).style.display = 'block';
}
