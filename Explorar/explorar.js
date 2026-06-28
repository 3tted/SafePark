const estado = {
    busqueda: '',
    tipo: 'todos',
    soloSeguros: false,
    orden: 'cercanas'
};

function filtrarAreas() {
    estado.busqueda = document.getElementById('busqueda').value.toLowerCase();
    aplicarFiltros();
}

function toggleChipGrupo(chip, grupo) {
    document.querySelectorAll(`[onclick*="'${grupo}'"]`).forEach(c => c.classList.remove('active'));
    chip.classList.add('active');
    if (grupo === 'tipo') estado.tipo = chip.dataset.valor;
    aplicarFiltros();
}

function toggleChipSeguro(chip) {
    chip.classList.toggle('active');
    estado.soloSeguros = chip.classList.contains('active');
    aplicarFiltros();
}

function ordenarAreas(valor) {
    estado.orden = valor;
    aplicarFiltros();
}

function aplicarFiltros() {
    const cards = Array.from(document.querySelectorAll('.ecard'));

    let visibles = cards.filter(card => {
        const nombre = card.querySelector('.ecard-name').textContent.toLowerCase();
        const tipo = card.dataset.tipo;
        const seguridad = card.dataset.seguridad;

        if (estado.busqueda && !nombre.includes(estado.busqueda)) return false;
        if (estado.tipo !== 'todos' && tipo !== estado.tipo) return false;
        if (estado.soloSeguros && seguridad !== 'seguro') return false;
        return true;
    });

    // Ordenar
    visibles.sort((a, b) => {
        if (estado.orden === 'cercanas') return parseFloat(a.dataset.distancia) - parseFloat(b.dataset.distancia);
        if (estado.orden === 'seguras') return parseFloat(b.dataset.score) - parseFloat(a.dataset.score);
        if (estado.orden === 'calificadas') return parseFloat(b.dataset.rating) - parseFloat(a.dataset.rating);
        return 0;
    });

    // Ocultar todas
    cards.forEach(c => c.style.display = 'none');

    // Reinsertar en orden
    const grid = document.getElementById('explorar-grid');
    visibles.forEach(c => {
        c.style.display = '';
        grid.appendChild(c);
    });

    document.getElementById('total-areas').textContent = `${visibles.length} área${visibles.length !== 1 ? 's' : ''}`;
    document.getElementById('sin-resultados').style.display = visibles.length === 0 ? 'block' : 'none';
    document.querySelector('.explorar-pagination').style.display = visibles.length === 0 ? 'none' : 'flex';
}
