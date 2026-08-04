// ============================================================
//  Mapa interactivo con Leaflet
//
//  Muestra las áreas verdes de Ciudad Juárez como marcadores de colores según
//  su semáforo de seguridad, con un panel lateral para buscarlas y filtrarlas.
//
//  También permite agregar áreas nuevas tocando el mapa, y editarlas si eres
//  quien las creó o administrador.
// ============================================================

// PHP inyecta las áreas ya listas en Mapa/index.php como un arreglo JSON.
// El "typeof" evita que truene si por alguna razón no llegaran.
const areas = typeof AREAS_DB !== 'undefined' ? AREAS_DB : [];

// Se centra en Ciudad Juárez con un zoom que abarca toda la mancha urbana
const map = L.map('map').setView([31.6900, -106.4500], 12);

// Las imágenes del mapa vienen de OpenStreetMap, que es gratuito y no necesita
// llave de acceso (a diferencia de Google Maps)
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://openstreetmap.org">OpenStreetMap</a>',
    maxZoom: 19
}).addTo(map);

// Traduce el score a color: verde ≥70, amarillo 40-69, rojo <40.
// Son los mismos cortes que usa Explorar, para que el usuario vea lo mismo
// en las dos pantallas.
function colorScore(score) {
    if (score >= 70) return '#40916C';   // verde
    if (score >= 40) return '#F4A261';   // amarillo
    return '#E63946';                    // rojo
}

// Guarda cada área junto con su marcador, para poder localizarlo después
// desde el buscador o la lista lateral
const marcadores = [];

areas.forEach(area => {
    const color = colorScore(area.score);

    // En vez del pin clásico de Leaflet se usa un círculo con el score dentro,
    // así el mapa comunica el estado de cada área de un vistazo.
    // divIcon permite dibujarlo con HTML y CSS normales.
    const icono = L.divIcon({
        className: '',
        html: `<div style="
            background:${color};color:white;border-radius:50%;
            width:36px;height:36px;display:flex;align-items:center;justify-content:center;
            font-weight:900;font-size:13px;border:3px solid white;
            box-shadow:0 2px 6px rgba(0,0,0,0.3);">${area.score}</div>`,
        iconSize: [36, 36],
        // El ancla va al centro (la mitad de 36) para que el círculo quede
        // justo sobre la coordenada, no colgando de ella
        iconAnchor: [18, 18]
    });

    const fotoHtml = area.foto
        ? `<img src="${area.foto}" alt="${area.nombre}" style="width:100%;height:100px;object-fit:cover;border-radius:8px;margin-bottom:8px;">`
        : '';

    // puede_editar lo resuelve PHP: true si es admin o si el usuario creó el área
    const editarBtn = area.puede_editar
        ? `<button onclick="abrirEditarArea(${area.id})" style="margin-top:8px;width:100%;padding:6px;background:var(--g2,#2D6A4F);color:white;border:none;border-radius:6px;font-family:Nunito,sans-serif;font-weight:700;font-size:0.78rem;cursor:pointer;">✏️ Editar área</button>`
        : '';

    // Dirección y horario son opcionales: si el área no los tiene, su bloque
    // queda vacío y ni siquiera aparece en el popup
    const direccionHtml = area.direccion
        ? `<div style="color:#6b7280;font-size:0.78rem;margin-bottom:2px;">🏠 ${area.direccion}</div>`
        : '';
    const horarioHtml = area.horario
        ? `<div style="color:#6b7280;font-size:0.78rem;margin-bottom:6px;">🕒 ${area.horario}</div>`
        : '';

    // El popup es la tarjeta que sale al tocar un marcador
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
                <button onclick="comoLlegar(${area.id})" style="margin-top:8px;width:100%;padding:6px;background:#3b82f6;color:white;border:none;border-radius:6px;font-family:Nunito,sans-serif;font-weight:700;font-size:0.78rem;cursor:pointer;">🧭 Cómo llegar</button>
                ${editarBtn}
            </div>
        `);

    // Se guarda la pareja área+marcador para poder relacionarlos después:
    // al tocar un renglón de la lista hay que saber qué marcador abrir
    marcadores.push({ area, marker });
});

// ============================================================
//  Panel lateral: lista, búsqueda y filtros por tipo
// ============================================================

let filtroTipo = 'todos';

// Dibuja la lista del panel izquierdo. Recibe qué áreas mostrar, para servir
// tanto a la lista completa como a los resultados de un filtro.
function renderLista(lista) {
    const container = document.getElementById('mapa-lista');
    container.innerHTML = '';   // se vacía antes de repintar

    lista.forEach(({ area, marker }) => {
        const color = colorScore(area.score);
        const div = document.createElement('div');
        div.className = 'mapa-area-item';
        div.innerHTML = `
            <div class="area-score" style="background:${color};color:white;">${area.score}</div>
            <div class="area-info"><h4>${area.nombre}</h4><p>${area.colonia} · ${area.tipo}</p></div>
        `;
        // Al tocar un renglón, el mapa vuela hasta esa área y abre su popup
        div.onclick = () => {
            map.setView([area.lat, area.lng], 15);
            marker.openPopup();
        };
        container.appendChild(div);
    });
}

// Combina la búsqueda por texto con el filtro de tipo
function filtrarLista() {
    const busqueda = document.getElementById('panel-search').value.toLowerCase();
    const filtrados = marcadores.filter(({ area }) => {
        const coincideTipo = filtroTipo === 'todos' || area.tipo === filtroTipo;
        const coincideTexto = area.nombre.toLowerCase().includes(busqueda) || area.colonia.toLowerCase().includes(busqueda);
        return coincideTipo && coincideTexto;
    });
    renderLista(filtrados);
}

// Filtro por tipo de área (Todos, Parques, Deportivo, Plaza)
function filtrarChip(el, tipo) {
    document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    filtroTipo = tipo;
    filtrarLista();
}

// Centra el mapa en donde está el usuario, si da permiso.
// El navegador siempre le pregunta antes; aquí no se puede forzar.
function centrarUsuario() {
    if (!navigator.geolocation) return;   // navegador sin soporte

    navigator.geolocation.getCurrentPosition(pos => {
        const { latitude, longitude } = pos.coords;
        map.setView([latitude, longitude], 14);

        // Un punto azul, distinto de los marcadores de áreas para no confundir
        L.circleMarker([latitude, longitude], {
            radius: 10, fillColor: '#3b82f6', color: 'white',
            weight: 3, fillOpacity: 1
        }).addTo(map).bindPopup('📍 Tu ubicación').openPopup();
    });
}

// ============================================================
//  Cómo llegar
//
//  Pide la ubicación del usuario, se la manda al API junto con la del área, y
//  dibuja el trayecto que devuelve OpenRouteService.
//
//  Todo el camino puede fallar —el usuario niega el permiso, el servicio de
//  rutas no responde, se acabó la cuota— así que en cada punto donde eso pasa
//  se ofrece abrir Google Maps, que siempre funciona. La función nunca deja al
//  usuario sin salida.
// ============================================================

// La ruta dibujada actualmente. Se guarda para poder borrarla antes de
// dibujar otra: si no, se irían acumulando líneas encima del mapa.
let rutaDibujada = null;
let marcadorOrigen = null;

function limpiarRuta() {
    if (rutaDibujada)   { map.removeLayer(rutaDibujada);   rutaDibujada = null; }
    if (marcadorOrigen) { map.removeLayer(marcadorOrigen); marcadorOrigen = null; }
}

// Enlace a Google Maps con la ruta a pie ya planteada. Es el plan B de todo
// lo de abajo, y en celular abre la app con navegación por voz.
function enlaceGoogleMaps(area) {
    return `https://www.google.com/maps/dir/?api=1&destination=${area.lat},${area.lng}&travelmode=walking`;
}

function comoLlegar(id) {
    const item = marcadores.find(m => m.area.id === id);
    if (!item) return;
    const { area, marker } = item;

    // Espacio dentro del popup donde se irá informando del avance
    const popup = marker.getPopup().getElement();
    let estado = popup.querySelector('.sp-estado-ruta');
    if (!estado) {
        estado = document.createElement('div');
        estado.className = 'sp-estado-ruta';
        estado.style.cssText = 'margin-top:8px;font-size:0.78rem;color:#374151;';
        popup.querySelector('.leaflet-popup-content').appendChild(estado);
    }

    if (!navigator.geolocation) {
        estado.innerHTML = `Tu navegador no comparte la ubicación. <a href="${enlaceGoogleMaps(area)}" target="_blank" rel="noopener">Abrir en Google Maps</a>`;
        return;
    }

    estado.textContent = '📍 Buscando tu ubicación…';

    navigator.geolocation.getCurrentPosition(
        pos => trazarRuta(pos.coords, area, marker, estado),
        () => {
            // El usuario negó el permiso, o el GPS no respondió
            estado.innerHTML = `No pudimos obtener tu ubicación. <a href="${enlaceGoogleMaps(area)}" target="_blank" rel="noopener">Abrir en Google Maps</a>`;
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
}

async function trazarRuta(coords, area, marker, estado) {
    estado.textContent = '🧭 Calculando la ruta…';

    const desde = `${coords.latitude},${coords.longitude}`;
    const hasta = `${area.lat},${area.lng}`;

    try {
        const r = await fetch(`${API_URL}/rutas?desde=${desde}&hasta=${hasta}&perfil=foot-walking`);
        const datos = await r.json();

        if (!r.ok || !datos.ok) throw new Error(datos.error || 'Sin respuesta');

        limpiarRuta();

        rutaDibujada = L.polyline(datos.linea, {
            color: '#3b82f6', weight: 5, opacity: 0.8
        }).addTo(map);

        // Punto de partida, del mismo azul que la línea
        marcadorOrigen = L.circleMarker([coords.latitude, coords.longitude], {
            radius: 9, fillColor: '#3b82f6', color: 'white', weight: 3, fillOpacity: 1
        }).addTo(map).bindPopup('📍 Tu ubicación');

        // Encuadra la ruta completa, con margen para que no quede pegada al borde
        map.fitBounds(rutaDibujada.getBounds(), { padding: [50, 50] });

        const km = (datos.distancia / 1000).toFixed(1);
        const min = Math.round(datos.duracion / 60);

        estado.innerHTML = `
            <div style="font-weight:700;">🚶 ${km} km · ${min} min caminando</div>
            <div style="margin-top:4px;">
                <a href="${enlaceGoogleMaps(area)}" target="_blank" rel="noopener">Navegar en Google Maps</a>
                · <a href="#" onclick="limpiarRuta();return false;">Quitar ruta</a>
            </div>`;

    } catch (e) {
        // Cualquier fallo del servicio de rutas termina aquí. El usuario no se
        // queda sin poder llegar: Google Maps sigue disponible.
        estado.innerHTML = `No se pudo trazar la ruta (${e.message}). <a href="${enlaceGoogleMaps(area)}" target="_blank" rel="noopener">Abrir en Google Maps</a>`;
    }
}

// ============================================================
//  Agregar un área nueva
//
//  En vez de pedir latitud y longitud escritas —que nadie sabe de memoria— se
//  activa un modo donde el usuario toca el punto del mapa y de ahí se sacan
//  las coordenadas.
// ============================================================

let modoAgregar = false;

// Enciende el modo: cambia el cursor a cruz y muestra el aviso
function activarAgregarArea() {
    modoAgregar = true;
    document.getElementById('aviso-agregar').style.display = 'block';
    document.getElementById('map').style.cursor = 'crosshair';
}

function cerrarModalArea() {
    document.getElementById('modal-area').style.display = 'none';
}

// Clic en el mapa mientras el modo está encendido
map.on('click', (e) => {
    if (!modoAgregar) return;   // en modo normal, el clic no hace nada

    // Las coordenadas del punto tocado se guardan en campos ocultos del formulario
    document.getElementById('input-lat').value = e.latlng.lat;
    document.getElementById('input-lng').value = e.latlng.lng;
    document.getElementById('modal-area').style.display = 'flex';

    // Se apaga el modo para que el siguiente clic no vuelva a abrir el modal
    modoAgregar = false;
    document.getElementById('aviso-agregar').style.display = 'none';
    document.getElementById('map').style.cursor = '';
});

// ============================================================
//  Editar un área existente
//
//  El botón de editar solo aparece en el popup si el usuario es el dueño del
//  área o administrador. Ese permiso lo resuelve PHP (area.puede_editar) y el
//  API vuelve a revisarlo al guardar, para que no baste con manipular el HTML.
// ============================================================

let modoEditarUbicacion = false;
let areaEditandoId = null;

// Llena el formulario del modal con los datos del área que se va a editar
function abrirEditarArea(id) {
    // Los datos ya están en memoria, no hace falta pedirlos al servidor
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

// Permite mover un área a otro punto.
//
// Esconde el modal para dejar ver el mapa, igual que al agregar. Los datos que
// el usuario ya escribió no se pierden: siguen en el formulario, solo oculto.
function activarEditarUbicacion() {
    modoEditarUbicacion = true;
    document.getElementById('modal-editar-area').style.display = 'none';
    document.getElementById('aviso-agregar').textContent = '📍 Haz clic en el mapa para la nueva ubicación';
    document.getElementById('aviso-agregar').style.display = 'block';
    document.getElementById('map').style.cursor = 'crosshair';
}

// Segundo detector de clics en el mapa, este para el modo de editar ubicación.
// Va aparte del de agregar porque escriben en campos distintos y devuelven al
// usuario a modales diferentes.
map.on('click', (e) => {
    if (!modoEditarUbicacion) return;

    document.getElementById('edit-lat').value = e.latlng.lat;
    document.getElementById('edit-lng').value = e.latlng.lng;
    // Se vuelve a mostrar el modal, ya con la coordenada nueva
    document.getElementById('modal-editar-area').style.display = 'flex';

    modoEditarUbicacion = false;
    document.getElementById('aviso-agregar').style.display = 'none';
    document.getElementById('map').style.cursor = '';
});

// Muestra la foto elegida antes de enviarla, para que el usuario confirme que
// seleccionó la correcta. FileReader la lee del disco sin subirla todavía.
function previewFotoMapa(input, placeholderId, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById(placeholderId).style.display = 'none';
            const img = document.getElementById(previewId);
            img.src = e.target.result;   // la imagen en base64
            img.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// ============================================================
//  Arranque
// ============================================================

// Primer dibujado de la lista, con todas las áreas
renderLista(marcadores);

// Leaflet calcula el tamaño del mapa al crearlo. En móvil el contenedor todavía
// no tiene su altura definitiva en ese momento, así que los tiles nunca se
// dibujan y el mapa se ve en blanco. invalidateSize() lo obliga a recalcular.
setTimeout(() => map.invalidateSize(), 200);
window.addEventListener('resize', () => map.invalidateSize());
// Al girar el teléfono también cambian las medidas, y la rotación tarda un poco
window.addEventListener('orientationchange', () => setTimeout(() => map.invalidateSize(), 300));

// Si se llegó desde Explorar con ?area=id, el mapa se acerca a esa área y abre
// su popup, para que el usuario no tenga que buscarla otra vez.
// Los 300ms dan chance a que Leaflet termine de dibujar antes de mover la vista.
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
