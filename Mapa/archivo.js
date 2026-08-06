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
// El "typeof" cubre el caso de que AREAS_DB no esté definida.
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

    // Leaflet vuelve a dibujar el popup desde su HTML original cada vez que se
    // abre, así que el panel de la ruta se pierde al cerrarlo. Si esta área ya
    // tiene una ruta trazada, se repinta con lo que se calculó antes: el
    // usuario vuelve a donde estaba en vez de tener que empezar de nuevo.
    marker.on('popupopen', () => {
        if (!areaDeLaRuta || areaDeLaRuta.id !== area.id || !ultimoResultado) return;
        const estado = panelDeRuta(marker);
        if (estado) pintarResumenRuta(estado, area, ultimoResultado);
    });

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

// El punto azul que marca dónde está el usuario.
//
// Es uno solo para toda la página, compartido entre el botón "Mi ubicación" y
// el trazado de rutas. Guardarlo en esta variable permite moverlo y quitarlo
// en vez de ir dejando marcadores sueltos por el mapa.
let marcadorUsuario = null;

// Lo coloca la primera vez y de ahí en adelante solo lo mueve
function ponerPuntoUsuario(lat, lng) {
    if (marcadorUsuario) {
        marcadorUsuario.setLatLng([lat, lng]);
    } else {
        marcadorUsuario = L.circleMarker([lat, lng], {
            radius: 10, fillColor: '#3b82f6', color: 'white',
            weight: 3, fillOpacity: 1
        }).addTo(map).bindPopup('📍 Tu ubicación');
    }
    return marcadorUsuario;
}

function quitarPuntoUsuario() {
    if (marcadorUsuario) {
        map.removeLayer(marcadorUsuario);
        marcadorUsuario = null;
    }
}

// Centra el mapa en donde está el usuario, si da permiso.
// El navegador siempre le pregunta antes; aquí no se puede forzar.
function centrarUsuario() {
    if (!navigator.geolocation) return;   // navegador sin soporte

    navigator.geolocation.getCurrentPosition(
        pos => {
            const { latitude, longitude } = pos.coords;
            map.setView([latitude, longitude], 14);
            ponerPuntoUsuario(latitude, longitude).openPopup();
        },
        err => {
            // Avisa por qué falló, distinguiendo el permiso denegado del resto
            const motivo = err && err.code === err.PERMISSION_DENIED
                ? 'Diste permiso denegado para ver tu ubicación'
                : 'No pudimos obtener tu ubicación';
            alert(motivo + '. Revisa el permiso de ubicación del navegador; si usas VPN, apágala e intenta de nuevo.');
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 }
    );
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

// Identificador del seguimiento por GPS. Hay que guardarlo para poder
// apagarlo: watchPosition deja el GPS despierto y eso gasta batería.
let vigilanciaId = null;

// Cómo se va a llegar. El API acepta estos tres perfiles y ninguno más.
//
// La diferencia no es cosmética: una ruta a pie usa andadores y callejones que
// un coche no puede tomar, así que el trazo y el tiempo cambian bastante.
const PERFILES = {
    'foot-walking':    { icono: '🚶', texto: 'a pie',    google: 'walking'   },
    'driving-car':     { icono: '🚗', texto: 'en carro', google: 'driving'   },
    'cycling-regular': { icono: '🚲', texto: 'en bici',  google: 'bicycling' },
};

let perfilRuta = 'driving-car';   // el que se usa por defecto

// Se guardan para poder recalcular al cambiar de perfil sin volver a pedirle
// la ubicación al navegador, que es lento y vuelve a mostrar el permiso.
let ultimaUbicacion = null;
let areaDeLaRuta    = null;

// El último resultado del API, para poder repintar el panel cuando el usuario
// cierra y vuelve a abrir el popup sin tener que pedir la ruta otra vez.
let ultimoResultado = null;

function limpiarRuta() {
    if (vigilanciaId !== null) {
        navigator.geolocation.clearWatch(vigilanciaId);
        vigilanciaId = null;
    }
    if (rutaDibujada) { map.removeLayer(rutaDibujada); rutaDibujada = null; }
    quitarPuntoUsuario();

    // Se olvida la ubicación guardada: si no, los botones de medio seguirían
    // funcionando sobre una ruta que el usuario ya quitó
    ultimaUbicacion = null;
    areaDeLaRuta    = null;
    ultimoResultado = null;
}

// Crea (o encuentra) el espacio dentro del popup donde se informa del avance
// y se pintan los botones de medio de transporte.
function panelDeRuta(marker) {
    const popup = marker.getPopup() && marker.getPopup().getElement();
    if (!popup) return null;

    let estado = popup.querySelector('.sp-estado-ruta');
    if (!estado) {
        estado = document.createElement('div');
        estado.className = 'sp-estado-ruta';
        estado.style.cssText = 'margin-top:8px;font-size:0.78rem;color:#374151;';
        popup.querySelector('.leaflet-popup-content').appendChild(estado);
    }
    return estado;
}

// Pinta el resumen de la ruta con sus botones. Se usa tanto al terminar de
// calcularla como al reabrir el popup, para no obligar a empezar de nuevo.
function pintarResumenRuta(estado, area, datos) {
    const km  = (datos.distancia / 1000).toFixed(1);
    const min = Math.round(datos.duracion / 60);
    const p   = PERFILES[perfilRuta];

    estado.innerHTML = `
        <div style="font-weight:700;">${p.icono} ${km} km · ${min} min ${p.texto}</div>
        <div style="margin:6px 0 4px;">${botonesPerfil()}</div>
        <div style="color:#6b7280;font-size:0.72rem;">Tu punto se mueve contigo mientras avanzas.</div>
        <div style="margin-top:4px;">
            <a href="${enlaceGoogleMaps(area)}" target="_blank" rel="noopener">Navegar en Google Maps</a>
            · <a href="#" onclick="limpiarRuta();return false;">Quitar ruta</a>
        </div>`;
}

// Mantiene el punto azul pegado a donde va el usuario mientras camina.
//
// Solo se mueve el punto: la ruta se queda como se dibujó. Recalcularla en
// cada paso gastaría la cuota de OpenRouteService en minutos —el límite son
// 40 peticiones por minuto— y no le serviría de nada al usuario, porque el
// camino no cambia mientras lo siga.
function seguirUsuario() {
    if (!navigator.geolocation || vigilanciaId !== null) return;

    vigilanciaId = navigator.geolocation.watchPosition(
        pos => {
            if (!marcadorUsuario) return;   // la ruta ya se quitó
            marcadorUsuario.setLatLng([pos.coords.latitude, pos.coords.longitude]);
        },
        // Si el GPS falla a media caminata no se avisa nada: el punto
        // simplemente se queda en la última posición conocida
        () => {},
        { enableHighAccuracy: true, maximumAge: 5000 }
    );
}

// Enlace a Google Maps con la ruta ya planteada, en el mismo modo que se está
// viendo aquí. Es el plan B de todo lo de abajo, y en celular abre la app con
// navegación por voz.
function enlaceGoogleMaps(area) {
    const modo = (PERFILES[perfilRuta] || PERFILES['driving-car']).google;
    return `https://www.google.com/maps/dir/?api=1&destination=${area.lat},${area.lng}&travelmode=${modo}`;
}

// Vuelve a trazar la misma ruta con otro medio de transporte.
// Reutiliza la ubicación que ya se obtuvo: no vuelve a pedir permiso.
function cambiarPerfilRuta(perfil) {
    if (!PERFILES[perfil] || !ultimaUbicacion || !areaDeLaRuta) return;
    perfilRuta = perfil;

    const item = marcadores.find(m => m.area.id === areaDeLaRuta.id);
    if (!item) return;
    const estado = panelDeRuta(item.marker);
    if (!estado) return;

    // encuadrar = false: el mapa se queda donde está. Reencuadrar aquí movería
    // la vista y cerraría el popup justo cuando el usuario está eligiendo.
    trazarRuta(ultimaUbicacion, areaDeLaRuta, item.marker, estado, false);
}

// Los botones para cambiar de medio. El activo se marca y no hace nada.
function botonesPerfil() {
    return Object.entries(PERFILES).map(([clave, p]) => {
        const activo = clave === perfilRuta;
        return `<button onclick="cambiarPerfilRuta('${clave}')" ${activo ? 'disabled' : ''}
            style="border:1px solid ${activo ? '#3b82f6' : '#d1d5db'};
                   background:${activo ? '#3b82f6' : '#fff'};
                   color:${activo ? '#fff' : '#374151'};
                   border-radius:6px;padding:3px 8px;margin-right:4px;
                   font-family:Nunito,sans-serif;font-size:0.72rem;font-weight:700;
                   cursor:${activo ? 'default' : 'pointer'};">${p.icono}</button>`;
    }).join('');
}

function comoLlegar(id) {
    const item = marcadores.find(m => m.area.id === id);
    if (!item) return;
    const { area, marker } = item;

    // Espacio dentro del popup donde se irá informando del avance
    const estado = panelDeRuta(marker);
    if (!estado) return;

    if (!navigator.geolocation) {
        estado.innerHTML = `Tu navegador no comparte la ubicación. <a href="${enlaceGoogleMaps(area)}" target="_blank" rel="noopener">Abrir en Google Maps</a>`;
        return;
    }

    // La ruta anterior se borra ANTES de pedir la ubicación, no después:
    // limpiarRuta() apaga el watchPosition, y mientras uno siga encendido el
    // getCurrentPosition nuevo queda esperando turno hasta agotar su plazo.
    limpiarRuta();

    estado.textContent = '📍 Buscando tu ubicación…';

    navigator.geolocation.getCurrentPosition(
        pos => trazarRuta(pos.coords, area, marker, estado),
        err => {
            // El usuario negó el permiso, o el GPS no respondió a tiempo
            const motivo = err && err.code === err.TIMEOUT
                ? 'La ubicación tardó demasiado'
                : 'No pudimos obtener tu ubicación';
            estado.innerHTML = `${motivo}. <a href="${enlaceGoogleMaps(area)}" target="_blank" rel="noopener">Abrir en Google Maps</a>`;
        },
        // maximumAge permite reutilizar una posición de hasta medio minuto en
        // vez de despertar el GPS otra vez. Para trazar una ruta de kilómetros
        // esa diferencia no importa, y la respuesta es inmediata.
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 }
    );
}

// encuadrar: sólo la primera vez. Al cambiar de medio de transporte el mapa
// se queda como está, porque moverlo cerraría el popup que el usuario tiene
// abierto justo para elegir.
async function trazarRuta(coords, area, marker, estado, encuadrar = true) {
    estado.textContent = '🧭 Calculando la ruta…';

    // Se recuerdan para poder recalcular al cambiar de medio de transporte
    ultimaUbicacion = coords;
    areaDeLaRuta    = area;

    const desde = `${coords.latitude},${coords.longitude}`;
    const hasta = `${area.lat},${area.lng}`;

    try {
        const r = await fetch(`${API_URL}/rutas?desde=${desde}&hasta=${hasta}&perfil=${perfilRuta}`);
        const datos = await r.json();

        if (!r.ok || !datos.ok) throw new Error(datos.error || 'Sin respuesta');

        // Se borra la anterior antes de dibujar: al cambiar de medio se vuelve
        // a entrar aquí, y sin esto quedarían las dos líneas encimadas.
        if (rutaDibujada) { map.removeLayer(rutaDibujada); rutaDibujada = null; }

        rutaDibujada = L.polyline(datos.linea, {
            color: '#3b82f6', weight: 5, opacity: 0.8
        }).addTo(map);

        // El mismo punto azul de siempre, movido al inicio del recorrido
        ponerPuntoUsuario(coords.latitude, coords.longitude);

        // A partir de aquí el punto azul sigue al usuario mientras se mueve
        seguirUsuario();

        // Encuadra la ruta completa, con margen para que no quede pegada al borde
        if (encuadrar) map.fitBounds(rutaDibujada.getBounds(), { padding: [50, 50] });

        // Se guarda para poder repintar el panel al reabrir el popup
        ultimoResultado = datos;

        pintarResumenRuta(estado, area, datos);

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
