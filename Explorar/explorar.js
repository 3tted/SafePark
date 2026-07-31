// Estado central del filtro — se actualiza con cada input o chip seleccionado
const estado = {
    busqueda: '',
    tipo: 'todos',
    seguridad: 'todos'
};

function filtrarAreas() {
    estado.busqueda = document.getElementById('busqueda').value.toLowerCase();
    aplicarFiltros();
}

function toggleChipGrupo(chip, grupo) {
    document.querySelectorAll(`[onclick*="'${grupo}'"]`).forEach(c => c.classList.remove('active'));
    chip.classList.add('active');
    if (grupo === 'tipo') estado.tipo = chip.dataset.valor;
    if (grupo === 'seguridad') estado.seguridad = chip.dataset.valor;
    aplicarFiltros();
}

function aplicarFiltros() {
    const cards = Array.from(document.querySelectorAll('.ecard'));

    let visibles = cards.filter(card => {
        const nombre = card.querySelector('.ecard-name').textContent.toLowerCase();
        const tipo   = card.dataset.tipo;
        const score  = parseInt(card.dataset.score);

        if (estado.busqueda && !nombre.includes(estado.busqueda)) return false;
        if (estado.tipo !== 'todos' && tipo !== estado.tipo) return false;
        if (estado.seguridad === 'seguro'    && score < 70) return false;
        if (estado.seguridad === 'precaucion' && (score < 40 || score >= 70)) return false;
        if (estado.seguridad === 'riesgo'    && score >= 40) return false;
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

    // Marcar favoritos actuales del usuario
    fetch(API_URL + '/favoritos/' + ID_USUARIO)
        .then(r => r.json())
        .then(ids => {
            ids.forEach(id => {
                const btn = document.querySelector(`.btn-fav[data-id="${id}"]`);
                if (btn) { btn.textContent = '♥'; btn.classList.add('fav-active'); }
            });
        })
        .catch(() => {});
});

function toggleFav(btn) {
    const id_area = btn.dataset.id;
    fetch(API_URL + '/favoritos', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_usuario: ID_USUARIO, id_area })
    })
    .then(r => r.json())
    .then(data => {
        if (!data.ok) return;
        if (data.accion === 'added') {
            btn.textContent = '♥';
            btn.classList.add('fav-active');
        } else {
            btn.textContent = '♡';
            btn.classList.remove('fav-active');
        }
    })
    .catch(() => {});
}

// Autocompletado local — busca en las cards ya renderizadas por PHP, sin llamada al servidor
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
    document.querySelector('.search-bar-full').classList.add('abierto');
}

function seleccionarSugerenciaExplorar(nombre) {
    document.getElementById('busqueda').value = nombre;
    document.getElementById('sugerencias-box-explorar').style.display = 'none';
    document.querySelector('.search-bar-full').classList.remove('abierto');

    // Resetear filtro de tipo a "Todos" para no ocultar el resultado
    document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
    document.querySelector('[data-valor="todos"]').classList.add('active');
    estado.tipo = 'todos';

    filtrarAreas();
}

document.addEventListener('click', (e) => {
    if (!e.target.closest('.search-bar-full') && !e.target.closest('.sugerencias-box-explorar')) {
        document.getElementById('sugerencias-box-explorar').style.display = 'none';
        document.querySelector('.search-bar-full').classList.remove('abierto');
    }
});

// ===== Modal detalle de área =====
const iconosTipo  = { incidente: '🚨', condicion: '🏚️', sugerencia: '💡' };
const bgsTipo     = { incidente: '#fee2e2', condicion: '#fef3c7', sugerencia: '#dbeafe' };
const labelsTipo  = { incidente: 'Incidente', condicion: 'Condición', sugerencia: 'Sugerencia' };
const iconoEmoji  = { parque: '🌳', deportivo: '⚽', plaza: '🏛️' };

function abrirModalArea(el) {
    const id     = el.dataset.id;
    const score  = parseInt(el.dataset.score);
    const nombre = el.dataset.nombre;
    const colonia= el.dataset.colonia;
    const tipo   = el.dataset.tipo;
    const foto   = el.dataset.foto;

    const semCls = score >= 70 ? 'sem-safe' : (score >= 40 ? 'sem-warn' : 'sem-risk');
    const semLbl = score >= 70 ? '● Seguro' : (score >= 40 ? '⚠ Precaución' : '✕ Riesgo');

    document.getElementById('modal-nombre').textContent = nombre;
    document.getElementById('modal-meta').textContent   = '📍 ' + colonia + ' · ' + (tipo.charAt(0).toUpperCase() + tipo.slice(1));

    // Dirección y horario son opcionales: sólo se muestran si el área los tiene
    const dirEl = document.getElementById('modal-direccion');
    const horEl = document.getElementById('modal-horario');
    dirEl.textContent    = el.dataset.direccion ? '🏠 ' + el.dataset.direccion : '';
    dirEl.style.display  = el.dataset.direccion ? 'block' : 'none';
    horEl.textContent    = el.dataset.horario   ? '🕒 ' + el.dataset.horario   : '';
    horEl.style.display  = el.dataset.horario   ? 'block' : 'none';
    document.getElementById('modal-semaforo').className = 'semaforo ' + semCls;
    document.getElementById('modal-semaforo').textContent = semLbl + ' · ' + score + '/100';
    document.getElementById('modal-mapa-link').href = '../Mapa/index.php?area=' + id;

    const fotoEl = document.getElementById('modal-foto');
    if (foto) {
        fotoEl.style.backgroundImage = `url('${foto}')`;
        fotoEl.textContent = '';
    } else {
        fotoEl.style.backgroundImage = '';
        fotoEl.textContent = iconoEmoji[tipo] || '🌿';
    }

    document.getElementById('modal-reportes').innerHTML = '<div class="modal-loading">Cargando reportes...</div>';
    document.getElementById('modal-area').style.display = 'flex';
    document.body.style.overflow = 'hidden';

    fetch(API_URL + '/reportes/area/' + id)
        .then(r => r.json())
        .then(reportes => {
            const el = document.getElementById('modal-reportes');
            if (!reportes.length) {
                el.innerHTML = '<div class="modal-sin-reportes">📋 Sin reportes para esta área todavía.</div>';
                return;
            }
            el.innerHTML = reportes.slice(0, 5).map(r => {
                const tipo   = r.tipo || 'incidente';
                const icon   = iconosTipo[tipo]  || '📋';
                const bg     = bgsTipo[tipo]     || '#f3f4f6';
                const label  = labelsTipo[tipo]  || tipo;
                const fecha  = new Date(r.fecha).toLocaleDateString('es-MX', { day:'2-digit', month:'short', year:'numeric' });
                const fotoHtml = r.foto
                    ? `<img src="../Assets/fotos/${r.foto}" alt="foto" class="modal-reporte-foto">`
                    : '';
                return `
                    <div class="modal-reporte-item">
                        <div class="modal-reporte-icon" style="background:${bg}">${icon}</div>
                        <div class="modal-reporte-info">
                            <div class="modal-reporte-tipo">${label}</div>
                            <div class="modal-reporte-desc">${r.descripcion}</div>
                            <div class="modal-reporte-fecha">${fecha}</div>
                            ${fotoHtml}
                        </div>
                    </div>`;
            }).join('');
        })
        .catch(() => {
            document.getElementById('modal-reportes').innerHTML = '<div class="modal-sin-reportes">No se pudieron cargar los reportes.</div>';
        });
}

function cerrarModalArea() {
    document.getElementById('modal-area').style.display = 'none';
    document.body.style.overflow = '';
}
