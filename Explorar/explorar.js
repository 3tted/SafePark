const estado = {
    busqueda: '',
    tipo: 'todos'
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

function aplicarFiltros() {
    const cards = Array.from(document.querySelectorAll('.ecard'));

    let visibles = cards.filter(card => {
        const nombre = card.querySelector('.ecard-name').textContent.toLowerCase();
        const tipo = card.dataset.tipo;

        if (estado.busqueda && !nombre.includes(estado.busqueda)) return false;
        if (estado.tipo !== 'todos' && tipo !== estado.tipo) return false;
        return true;
    });

    cards.forEach(c => c.style.display = 'none');
    visibles.forEach(c => c.style.display = '');

    document.getElementById('total-areas').textContent = `${visibles.length} área${visibles.length !== 1 ? 's' : ''}`;
    document.getElementById('sin-resultados').style.display = visibles.length === 0 ? 'block' : 'none';
}

document.addEventListener('DOMContentLoaded', () => {
    const inicial = document.getElementById('busqueda').value;
    if (inicial) filtrarAreas();
});

// ===== Autocompletado tipo Google =====
function mostrarSugerenciasExplorar() {
    const texto = document.getElementById('busqueda').value.toLowerCase().trim();
    const box = document.getElementById('sugerencias-box-explorar');

    if (!texto) {
        box.style.display = 'none';
        box.innerHTML = '';
        return;
    }

    const cards = Array.from(document.querySelectorAll('.ecard'));
    const coincidencias = cards.filter(card => {
        const nombre = card.querySelector('.ecard-name').textContent.toLowerCase();
        const meta = card.querySelector('.ecard-meta')?.textContent.toLowerCase() || '';
        return nombre.includes(texto) || meta.includes(texto);
    }).slice(0, 6);

    if (coincidencias.length === 0) {
        box.style.display = 'none';
        box.innerHTML = '';
        return;
    }

    box.innerHTML = coincidencias.map((card, i) => {
        const nombre = card.querySelector('.ecard-name').textContent;
        const meta = card.querySelector('.ecard-meta')?.textContent.trim() || '';
        return `
            <div class="sugerencia-item-explorar" onclick="seleccionarSugerenciaExplorar('${nombre.replace(/'/g, "\\'")}')">
                <span class="sugerencia-icono-explorar">📍</span>
                <div>
                    <div class="sugerencia-nombre-explorar">${nombre}</div>
                    <div class="sugerencia-meta-explorar">${meta}</div>
                </div>
            </div>
        `;
    }).join('');
    box.style.display = 'block';
}

function seleccionarSugerenciaExplorar(nombre) {
    document.getElementById('busqueda').value = nombre;
    document.getElementById('sugerencias-box-explorar').style.display = 'none';

    // Resetear filtro de tipo a "Todos" para no ocultar el resultado
    document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
    document.querySelector('[data-valor="todos"]').classList.add('active');
    estado.tipo = 'todos';

    filtrarAreas();
}

document.addEventListener('click', (e) => {
    if (!e.target.closest('.search-bar-full') && !e.target.closest('.sugerencias-box-explorar')) {
        document.getElementById('sugerencias-box-explorar').style.display = 'none';
    }
});
