function cambiarTab(tab, id) {
    document.querySelectorAll('.feed-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');

    document.querySelectorAll('.feed-panel').forEach(p => p.style.display = 'none');
    document.getElementById('tab-' + id).style.display = 'block';
}

function reaccionar(btn) {
    const match = btn.textContent.match(/(\D+)(\d+)/);
    if (!match) return;
    const emoji = match[1];
    const count = parseInt(match[2]);
    if (btn.classList.contains('reaction-active')) {
        btn.textContent = emoji + (count - 1);
        btn.classList.remove('reaction-active');
    } else {
        btn.textContent = emoji + (count + 1);
        btn.classList.add('reaction-active');
    }
}
