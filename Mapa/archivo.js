// AREAS_DB es inyectada por PHP en Mapa/index.php como JSON
const areas = typeof AREAS_DB !== 'undefined' ? AREAS_DB : [];

const map = L.map('map').setView([31.6900, -106.4500], 12);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://openstreetmap.org">OpenStreetMap</a>',
    maxZoom: 19
}).addTo(map);

// Semáforo de seguridad: verde ≥70, amarillo ≥40, rojo <40
function colorScore(score) {
    if (score >= 70) return '#40916C';
    if (score >= 40) return '#F4A261';
    return '#E63946';
}

const marcadores = [];

areas.forEach(area => {
    const color = colorScore(area.score);

    const icono = L.divIcon({
        className: '',
        html: `<div style="
            background:${color};color:white;border-radius:50%;
            width:36px;height:36px;display:flex;align-items:center;justify-content:center;
            font-weight:900;font-size:13px;border:3px solid white;
            box-shadow:0 2px 6px rgba(0,0,0,0.3);">${area.score}</div>`,
        iconSize: [36, 36],
        iconAnchor: [18, 18]
    });

    const fotoHtml = area.foto
        ? `<img src="${area.foto}" alt="${area.nombre}" style="width:100%;height:100px;object-fit:cover;border-radius:8px;margin-bottom:8px;">`
        : '';

    // puede_editar lo resuelve PHP: true si es admin o si el usuario creó el área
    const editarBtn = area.puede_editar
        ? `<button onclick="abrirEditarArea(${area.id})" style="margin-top:8px;width:100%;padding:6px;background:var(--g2,#2D6A4F);color:white;border:none;border-radius:6px;font-family:Nunito,sans-serif;font-weight:700;font-size:0.78rem;cursor:pointer;">✏️ Editar área</button>`
        : '';

    const direccionHtml = area.direccion
        ? `<div style="color:#6b7280;font-size:0.78rem;margin-bottom:2px;">🏠 ${area.direccion}</div>`
        : '';
    const horarioHtml = area.horario
        ? `<div style="color:#6b7280;font-size:0.78rem;margin-bottom:6px;">🕒 ${area.horario}</div>`
        : '';

    const marker = L.marker([area.lat, area.lng], { icon: icono })
        .addTo(map)
        .bindPopup(`
            <div style="font-family:Nunito,sans-serif;min-width:160px;">
                ${fotoHtml}
                <div style="font-weight:900;font-size:1rem;margin-bottom:4px;">${area.nombre}</div>
                <div style="color:#6b7280;font-size:0.82rem;margin-bottom:${direccionHtml||horarioHtml?'4px':'8px'};">📍 ${area.colonia}</div>
                ${direccionHtml}
                ${horarioHtml}
                <div style="background:${color};color:white;border-radius:20px;padding:3px 10px;display:inline-block;font-weight:700;font-size:0.82rem;">
                    ${area.score >= 70 ? '● Seguro' : area.score >= 40 ? '⚠ Precaución' : '✕ Riesgo'} · ${area.score}/100
                </div>
                ${editarBtn}
            </div>
        `);

    marcadores.push({ area, marker });
});

let filtroTipo = 'todos';

function renderLista(lista) {
    const container = document.getElementById('mapa-lista');
    container.innerHTML = '';
    lista.forEach(({ area, marker }) => {
        const color = colorScore(area.score);
        const div = document.createElement('div');
        div.className = 'mapa-area-item';
        div.innerHTML = `
            <div class="area-score" style="background:${color};color:white;">${area.score}</div>
            <div class="area-info"><h4>${area.nombre}</h4><p>${area.colonia} · ${area.tipo}</p></div>
        `;
        div.onclick = () => {
            map.setView([area.lat, area.lng], 15);
            marker.openPopup();
        };
        container.appendChild(div);
    });
}

function filtrarLista() {
    const busqueda = document.getElementById('panel-search').value.toLowerCase();
    const filtrados = marcadores.filter(({ area }) => {
        const coincideTipo = filtroTipo === 'todos' || area.tipo === filtroTipo;
        const coincideTexto = area.nombre.toLowerCase().includes(busqueda) || area.colonia.toLowerCase().includes(busqueda);
        return coincideTipo && coincideTexto;
    });
    renderLista(filtrados);
}

function filtrarChip(el, tipo) {
    document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    filtroTipo = tipo;
    filtrarLista();
}

function centrarUsuario() {
    if (!navigator.geolocation) return;
    navigator.geolocation.getCurrentPosition(pos => {
        const { latitude, longitude } = pos.coords;
        map.setView([latitude, longitude], 14);
        L.circleMarker([latitude, longitude], {
            radius: 10, fillColor: '#3b82f6', color: 'white',
            weight: 3, fillOpacity: 1
        }).addTo(map).bindPopup('📍 Tu ubicación').openPopup();
    });
}

// ===== Agregar área nueva =====
let modoAgregar = false;

function activarAgregarArea() {
    modoAgregar = true;
    document.getElementById('aviso-agregar').style.display = 'block';
    document.getElementById('map').style.cursor = 'crosshair';
}

function cerrarModalArea() {
    document.getElementById('modal-area').style.display = 'none';
}

map.on('click', (e) => {
    if (!modoAgregar) return;

    document.getElementById('input-lat').value = e.latlng.lat;
    document.getElementById('input-lng').value = e.latlng.lng;
    document.getElementById('modal-area').style.display = 'flex';

    modoAgregar = false;
    document.getElementById('aviso-agregar').style.display = 'none';
    document.getElementById('map').style.cursor = '';
});

// ===== Editar área existente =====
let modoEditarUbicacion = false;
let areaEditandoId = null;

function abrirEditarArea(id) {
    const item = marcadores.find(m => m.area.id === id);
    if (!item) return;
    const { area } = item;

    areaEditandoId = id;
    document.getElementById('edit-id').value = area.id;
    document.getElementById('edit-nombre').value = area.nombre;
    document.getElementById('edit-colonia').value = area.colonia;
    document.getElementById('edit-direccion').value = area.direccion || '';
    document.getElementById('edit-horario').value = area.horario || '';
    document.getElementById('edit-tipo').value = area.tipo;
    document.getElementById('edit-lat').value = area.lat;
    document.getElementById('edit-lng').value = area.lng;

    // Resetear preview de foto al abrir el modal de editar
    document.getElementById('placeholder-editar').style.display = 'block';
    const prevEdit = document.getElementById('preview-editar');
    prevEdit.src = ''; prevEdit.style.display = 'none';
    document.getElementById('input-foto-editar').value = '';

    map.closePopup();
    document.getElementById('modal-editar-area').style.display = 'flex';
}

function cerrarEditarArea() {
    document.getElementById('modal-editar-area').style.display = 'none';
    modoEditarUbicacion = false;
    document.getElementById('aviso-agregar').style.display = 'none';
    document.getElementById('map').style.cursor = '';
}

function activarEditarUbicacion() {
    modoEditarUbicacion = true;
    document.getElementById('modal-editar-area').style.display = 'none';
    document.getElementById('aviso-agregar').textContent = '📍 Haz clic en el mapa para la nueva ubicación';
    document.getElementById('aviso-agregar').style.display = 'block';
    document.getElementById('map').style.cursor = 'crosshair';
}

map.on('click', (e) => {
    if (!modoEditarUbicacion) return;

    document.getElementById('edit-lat').value = e.latlng.lat;
    document.getElementById('edit-lng').value = e.latlng.lng;
    document.getElementById('modal-editar-area').style.display = 'flex';

    modoEditarUbicacion = false;
    document.getElementById('aviso-agregar').style.display = 'none';
    document.getElementById('map').style.cursor = '';
});

function previewFotoMapa(input, placeholderId, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById(placeholderId).style.display = 'none';
            const img = document.getElementById(previewId);
            img.src = e.target.result;
            img.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

renderLista(marcadores);

// Leaflet calcula el tamaño del mapa al crearlo. En móvil el contenedor todavía
// no tiene su altura definitiva en ese momento, así que los tiles nunca se
// dibujan y el mapa se ve en blanco. invalidateSize() lo obliga a recalcular.
setTimeout(() => map.invalidateSize(), 200);
window.addEventListener('resize', () => map.invalidateSize());
window.addEventListener('orientationchange', () => setTimeout(() => map.invalidateSize(), 300));

// Si viene de Explorar con ?area=id, centra el mapa y abre el popup de esa área
// setTimeout de 300ms para que Leaflet termine de renderizar los tiles antes de hacer setView
const params = new URLSearchParams(window.location.search);
const areaId = parseInt(params.get('area'));
if (areaId) {
    const item = marcadores.find(m => m.area.id === areaId);
    if (item) {
        setTimeout(() => {
            map.setView([item.area.lat, item.area.lng], 16);
            item.marker.openPopup();
        }, 300);
    }
}
