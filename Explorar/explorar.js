// ============================================================
//  Explorar — filtrado de áreas, favoritos y modal de detalle
//
//  El filtrado ocurre en el navegador, no en el servidor: PHP ya pintó todas
//  las tarjetas, así que filtrar es solo esconder y mostrar las que existen.
//  Es instantáneo y no gasta llamadas al API.
// ============================================================

// Los tres filtros viven juntos aquí para poder combinarlos: se puede pedir
// "parques" Y "en riesgo" Y que digan "centro" al mismo tiempo.
const estado = {
    busqueda:  '',
    tipo:      'todos',
    seguridad: 'todos'
};

// Se dispara al escribir en el buscador
function filtrarAreas() {
    estado.busqueda = document.getElementById('busqueda').value.toLowerCase();
    aplicarFiltros();
}

// Se dispara al tocar un chip de Tipo o de Seguridad.
// El "grupo" sirve para apagar solo los chips de esa fila, no los de la otra.
function toggleChipGrupo(chip, grupo) {
    document.querySelectorAll(`[onclick*="'${grupo}'"]`).forEach(c => c.classList.remove('active'));
    chip.classList.add('active');

    if (grupo === 'tipo')      estado.tipo      = chip.dataset.valor;
    if (grupo === 'seguridad') estado.seguridad = chip.dataset.valor;

    aplicarFiltros();
}

// Decide qué tarjetas se ven, aplicando los tres filtros a la vez
function aplicarFiltros() {
    const tarjetas = Array.from(document.querySelectorAll('.ecard'));

    const visibles = tarjetas.filter(tarjeta => {
        // Estos datos los dejó PHP en atributos data-* de cada tarjeta
        const nombre = tarjeta.querySelector('.ecard-name').textContent.toLowerCase();
        const tipo   = tarjeta.dataset.tipo;
        const score  = parseInt(tarjeta.dataset.score);

        // Basta con fallar un filtro para quedar fuera
        if (estado.busqueda && !nombre.includes(estado.busqueda)) return false;
        if (estado.tipo !== 'todos' && tipo !== estado.tipo) return false;

        // Los rangos del semáforo: verde ≥70, amarillo 40-69, rojo <40
        if (estado.seguridad === 'seguro'     && score < 70) return false;
        if (estado.seguridad === 'precaucion' && (score < 40 || score >= 70)) return false;
        if (estado.seguridad === 'riesgo'     && score >= 40) return false;

        return true;
    });

    // Se esconden todas y luego se muestran las que pasaron
    tarjetas.forEach(t => t.style.display = 'none');
    visibles.forEach(t => t.style.display = '');

    // El contador y el mensaje de "sin resultados" se ajustan solos
    document.getElementById('total-areas').textContent =
        `${visibles.length} área${visibles.length !== 1 ? 's' : ''}`;
    document.getElementById('sin-resultados').style.display =
        visibles.length === 0 ? 'block' : 'none';
}

// Al cargar la página
document.addEventListener('DOMContentLoaded', () => {
    // Si se llegó desde el buscador del Inicio, la URL trae un término:
    // se aplica de una vez para que el usuario no tenga que repetirlo
    const inicial = document.getElementById('busqueda').value;
    if (inicial) filtrarAreas();

    // Se piden los favoritos del usuario y se pintan de rojo sus corazones.
    // PHP no los sabe al generar la página, por eso se consultan aparte.
    fetch(API_URL + '/favoritos/' + ID_USUARIO)
        .then(r => r.json())
        .then(ids => {
            ids.forEach(id => {
                const btn = document.querySelector(`.btn-fav[data-id="${id}"]`);
                if (btn) { btn.textContent = '♥'; btn.classList.add('fav-active'); }
            });
        })
        // Si falla, los corazones quedan vacíos: no es crítico
        .catch(() => {});
});

// Guarda o quita un área de favoritos (el mismo botón hace las dos cosas)
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

// ============================================================
//  Modal de detalle: se abre al tocar una tarjeta
//
//  Los datos del área ya venían en la tarjeta (atributos data-*), así que se
//  muestran de inmediato. Lo único que se pide al API son sus reportes, porque
//  esos sí no estaban en la página.
// ============================================================

// Cómo se dibuja cada tipo de reporte dentro del modal
const iconosTipo = { incidente: '🚨',      condicion: '🏚️',     sugerencia: '💡' };
const bgsTipo    = { incidente: '#fee2e2', condicion: '#fef3c7', sugerencia: '#dbeafe' };
const labelsTipo = { incidente: 'Incidente', condicion: 'Condición', sugerencia: 'Sugerencia' };
// Ícono de respaldo según el tipo de área, para cuando no tiene foto
const iconoEmoji = { parque: '🌳', deportivo: '⚽', plaza: '🏛️' };

// Recibe la tarjeta que se tocó y lee sus datos de los atributos data-*
function abrirModalArea(el) {
    const id      = el.dataset.id;
    const score   = parseInt(el.dataset.score);
    const nombre  = el.dataset.nombre;
    const colonia = el.dataset.colonia;
    const tipo    = el.dataset.tipo;
    const foto    = el.dataset.foto;

    // El color y la etiqueta del semáforo salen del score
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

    // Si el área tiene foto se usa de portada; si no, un emoji según su tipo
    const fotoEl = document.getElementById('modal-foto');
    if (foto) {
        fotoEl.style.backgroundImage = `url('${foto}')`;
        fotoEl.textContent = '';
    } else {
        fotoEl.style.backgroundImage = '';
        fotoEl.textContent = iconoEmoji[tipo] || '🌿';
    }

    // El modal se abre de inmediato con un mensaje de carga, en vez de esperar
    // a que llegue la respuesta: se siente más rápido aunque tarde lo mismo
    document.getElementById('modal-reportes').innerHTML =
        '<div class="modal-loading">Cargando reportes...</div>';
    document.getElementById('modal-area').style.display = 'flex';
    // Bloquea el scroll del fondo para que no se mueva detrás del modal
    document.body.style.overflow = 'hidden';

    // Lo único que sí hay que pedir al API
    fetch(API_URL + '/reportes/area/' + id)
        .then(r => r.json())
        .then(reportes => {
            const el = document.getElementById('modal-reportes');
            if (!reportes.length) {
                el.innerHTML = '<div class="modal-sin-reportes">📋 Sin reportes para esta área todavía.</div>';
                return;
            }
            // Se guardan todos y se pinta la primera página
            reportesDelArea = reportes;
            paginaReportes  = 1;
            pintarReportes();
        })
        .catch(() => {
            document.getElementById('modal-reportes').innerHTML = '<div class="modal-sin-reportes">No se pudieron cargar los reportes.</div>';
        });
}

// ============================================================
//  Paginado de los reportes del modal
//
//  Los reportes ya vienen todos en la misma respuesta del API, así que
//  cambiar de página no vuelve a pedir nada: solo repinta.
// ============================================================

let reportesDelArea = [];    // todos los del área abierta
let paginaReportes  = 1;
const REPORTES_POR_PAGINA = 5;

function pintarReportes() {
    const el = document.getElementById('modal-reportes');
    const paginas = Math.ceil(reportesDelArea.length / REPORTES_POR_PAGINA);

    const desde = (paginaReportes - 1) * REPORTES_POR_PAGINA;
    const enEstaPagina = reportesDelArea.slice(desde, desde + REPORTES_POR_PAGINA);

    const items = enEstaPagina.map(r => {
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

    // Con una sola página no se dibuja nada: los botones sobrarían
    const barra = paginas > 1 ? construirPaginacion(paginas) : '';

    el.innerHTML = items + barra;
}

// Botones de página. Con muchas páginas se muestran solo las cercanas a la
// actual para que la fila no se desborde en pantallas chicas.
function construirPaginacion(paginas) {
    const botones = [];

    botones.push(`<button class="pag-btn pag-flecha" onclick="irAPaginaReportes(${paginaReportes - 1})"
                   ${paginaReportes === 1 ? 'disabled' : ''}>‹</button>`);

    for (let p = 1; p <= paginas; p++) {
        // Siempre la primera, la última y las vecinas de la actual
        const cerca = Math.abs(p - paginaReportes) <= 1;
        if (p === 1 || p === paginas || cerca) {
            botones.push(`<button class="pag-btn ${p === paginaReportes ? 'activa' : ''}"
                           onclick="irAPaginaReportes(${p})">${p}</button>`);
        } else if (p === paginaReportes - 2 || p === paginaReportes + 2) {
            botones.push('<span class="pag-puntos">…</span>');
        }
    }

    botones.push(`<button class="pag-btn pag-flecha" onclick="irAPaginaReportes(${paginaReportes + 1})"
                   ${paginaReportes === paginas ? 'disabled' : ''}>›</button>`);

    const primero = (paginaReportes - 1) * REPORTES_POR_PAGINA + 1;
    const ultimo  = Math.min(paginaReportes * REPORTES_POR_PAGINA, reportesDelArea.length);

    return `<div class="paginacion">
                <div class="pag-botones">${botones.join('')}</div>
                <div class="pag-cuenta">${primero}-${ultimo} de ${reportesDelArea.length}</div>
            </div>`;
}

function irAPaginaReportes(p) {
    const paginas = Math.ceil(reportesDelArea.length / REPORTES_POR_PAGINA);
    if (p < 1 || p > paginas) return;
    paginaReportes = p;
    pintarReportes();
    // Sube al inicio de la lista: si no, al cambiar de página quedas viendo
    // la mitad de los reportes nuevos
    document.getElementById('modal-reportes').scrollIntoView({ block: 'nearest' });
}

function cerrarModalArea() {
    document.getElementById('modal-area').style.display = 'none';
    document.body.style.overflow = '';
}
