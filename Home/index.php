<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - SafePark</title>
    <link rel="icon" href="../Assets/logo.png">
    <link rel="stylesheet" href="../CSS/styles.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
</head>
<body>

<?php
require_once '../includes/auth.php';
require_once '../includes/api.php';
// Sin requiere_sesion(): esta página se puede ver como invitado.

$datos = api_get_multi([
    'areas'    => '/areas',
    'usuarios' => '/usuarios',
    'reportes' => '/reportes',
]);

$areas_raw      = $datos['areas'];
$total_areas    = count($areas_raw);
$total_usuarios = count($datos['usuarios']);
$total_reportes = count($datos['reportes']);

$areas_home = array_values(array_map(fn($a) => [
    'nombre'  => $a['nombre'],
    'colonia' => $a['colonia'],
    'tipo'    => $a['tipo'],
    'lat'     => (float)$a['lat'],
    'lng'     => (float)$a['lng'],
    'score'   => $a['score']
], array_filter($areas_raw, fn($a) => $a['lat'] && $a['lng'])));

$nav_base   = '../';
$nav_active = 'inicio';
require_once '../includes/navbar.php';
?>

    <div class="hero">
        <div class="hero-content">
            <h1>Explora áreas verdes<br><span>seguras en Juárez</span></h1>
            <p>Descubre parques y plazas evaluados por la comunidad con un semáforo de seguridad en tiempo real.</p>
            <div style="position:relative;width:100%;max-width:420px;">
                <div class="search-bar">
                    <input type="text" id="home-busqueda" placeholder="Buscar área verde, colonia..." oninput="mostrarSugerenciasHome()" onkeydown="if(event.key==='Enter') irABuscar()" autocomplete="off">
                    <button onclick="irABuscar()">Buscar</button>
                </div>
                <div class="sugerencias-box-home" id="sugerencias-box-home" style="display:none;"></div>
            </div>
            <div class="hero-stats">
                <div class="stat"><div class="stat-n"><?= $total_areas ?></div><div class="stat-l">Áreas registradas</div></div>
                <div class="stat"><div class="stat-n"><?= $total_reportes ?></div><div class="stat-l">Reportes</div></div>
                <div class="stat"><div class="stat-n"><?= $total_usuarios ?></div><div class="stat-l">Usuarios</div></div>
            </div>
            <div id="clima-widget" style="display:flex;align-items:center;gap:8px;color:white;font-size:13px;font-weight:700;margin-top:10px;background:rgba(0,0,0,0.15);padding:6px 16px;border-radius:20px;">
                <span id="clima-icono">⏳</span>
                <span id="clima-texto">Cargando clima...</span>
            </div>
        </div>
    </div>
    <div class="map-section">
        <div id="home-map"></div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const AREAS_HOME = <?= json_encode($areas_home) ?>;

        const homeMap = L.map('home-map').setView([31.6900, -106.4500], 11);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(homeMap);

        function colorScore(score) {
            if (score >= 70) return '#40916C';
            if (score >= 40) return '#F4A261';
            return '#E63946';
        }

        const marcadoresHome = [];

        AREAS_HOME.forEach(area => {
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

            const marker = L.marker([area.lat, area.lng], { icon: icono })
                .addTo(homeMap)
                .bindPopup(`
                    <div style="font-family:Nunito,sans-serif;min-width:160px;">
                        <div style="font-weight:900;font-size:1rem;margin-bottom:4px;">${area.nombre}</div>
                        <div style="color:#6b7280;font-size:0.82rem;margin-bottom:8px;">📍 ${area.colonia}</div>
                        <div style="background:${color};color:white;border-radius:20px;padding:3px 10px;display:inline-block;font-weight:700;font-size:0.82rem;">
                            ${area.score >= 70 ? '● Seguro' : area.score >= 40 ? '⚠ Precaución' : '✕ Riesgo'} · ${area.score}/100
                        </div>
                    </div>
                `);

            marcadoresHome.push({ area, marker });
        });

        setTimeout(() => homeMap.invalidateSize(), 100);
        window.addEventListener('resize', () => homeMap.invalidateSize());

        function irABuscar() {
            const texto = document.getElementById('home-busqueda').value.trim();
            window.location.href = '../Explorar/index.php?busqueda=' + encodeURIComponent(texto);
        }

        function mostrarSugerenciasHome() {
            const texto = document.getElementById('home-busqueda').value.toLowerCase().trim();
            const box = document.getElementById('sugerencias-box-home');

            if (!texto) {
                box.style.display = 'none';
                box.innerHTML = '';
                return;
            }

            const coincidencias = marcadoresHome.filter(({ area }) =>
                area.nombre.toLowerCase().includes(texto) || area.colonia.toLowerCase().includes(texto)
            ).slice(0, 6);

            if (coincidencias.length === 0) {
                box.style.display = 'none';
                box.innerHTML = '';
                return;
            }

            document.querySelector('.search-bar').classList.add('abierto');
            box.innerHTML = coincidencias.map((item, i) => `
                <div class="sugerencia-item-home" onclick="seleccionarSugerenciaHome(${marcadoresHome.indexOf(item)})">
                    <span class="sugerencia-icono-home">📍</span>
                    <div>
                        <div class="sugerencia-nombre-home">${item.area.nombre}</div>
                        <div class="sugerencia-colonia-home">${item.area.colonia}</div>
                    </div>
                </div>
            `).join('');
            box.style.display = 'block';
        }

        function seleccionarSugerenciaHome(idx) {
            const { area, marker } = marcadoresHome[idx];

            document.getElementById('home-busqueda').value = area.nombre;
            document.getElementById('sugerencias-box-home').style.display = 'none';
            document.querySelector('.search-bar').classList.remove('abierto');

            homeMap.setView([area.lat, area.lng], 16);
            marker.openPopup();
        }

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-bar') && !e.target.closest('.sugerencias-box-home')) {
                document.getElementById('sugerencias-box-home').style.display = 'none';
                document.querySelector('.search-bar').classList.remove('abierto');
            }
        });

        homeMap.on('click', () => window.location.href = '../Mapa/index.php');

        let nominatimTimer = null;

        // Autocompletado local — busca en áreas de la BD
        function mostrarSugerenciasHome() {
            const texto = document.getElementById('home-busqueda').value.toLowerCase().trim();
            const box = document.getElementById('sugerencias-box-home');

            if (!texto) { cerrarDropdownHome(); return; }

            const coincidencias = marcadoresHome.filter(({ area }) =>
                area.nombre.toLowerCase().includes(texto) || area.colonia.toLowerCase().includes(texto)
            ).slice(0, 6);

            if (coincidencias.length === 0) { cerrarDropdownHome(); return; }

            const iconoTipo = { parque: '🌳', deportivo: '⚽', plaza: '🏛️' };
            box.innerHTML = coincidencias.map(item => `
                <div class="sugerencia-item-home" onclick="seleccionarSugerenciaHome(${marcadoresHome.indexOf(item)})">
                    <span class="sugerencia-icono-home">${iconoTipo[item.area.tipo] || '📍'}</span>
                    <div>
                        <div class="sugerencia-nombre-home">${item.area.nombre}</div>
                        <div class="sugerencia-colonia-home">${item.area.colonia}</div>
                    </div>
                </div>
            `).join('');
            box.style.display = 'block';
            document.querySelector('.search-bar').classList.add('abierto');
        }

        // Autocompletado Nominatim — listener separado para no interferir con el local
        document.getElementById('home-busqueda').addEventListener('input', function() {
            clearTimeout(nominatimTimer);
            const texto = this.value.trim();
            if (!texto) return;

            nominatimTimer = setTimeout(() => {
                fetch('https://nominatim.openstreetmap.org/search'
                    + '?q=' + encodeURIComponent(texto + ' Ciudad Juarez Chihuahua')
                    + '&format=json&limit=8&countrycodes=mx',
                    { headers: { 'Accept-Language': 'es', 'User-Agent': 'SafePark/1.0' } }
                )
                .then(r => r.json())
                .then(resultados => {
                    if (!resultados.length) return;
                    const box = document.getElementById('sugerencias-box-home');
                    const htmlNom = resultados.map(r => `
                        <div class="sugerencia-item-home" onclick="irANominatim(${r.lat}, ${r.lon}, '${r.display_name.split(',')[0].replace(/'/g, "\\'")}')">
                            <span class="sugerencia-icono-home">📍</span>
                            <div>
                                <div class="sugerencia-nombre-home">${r.display_name.split(',')[0]}</div>
                                <div class="sugerencia-colonia-home">${r.display_name.split(',').slice(1,3).join(',')}</div>
                            </div>
                        </div>
                    `).join('');
                    const sep = '';
                    box.innerHTML = (box.innerHTML || '') + sep + htmlNom;
                    box.style.display = 'block';
                    document.querySelector('.search-bar').classList.add('abierto');
                })
                .catch(() => {});
            }, 200);
        });

        function cerrarDropdownHome() {
            document.getElementById('sugerencias-box-home').style.display = 'none';
            document.querySelector('.search-bar').classList.remove('abierto');
        }

        function irANominatim(lat, lng, nombre) {
            document.getElementById('home-busqueda').value = nombre;
            cerrarDropdownHome();
            homeMap.setView([parseFloat(lat), parseFloat(lng)], 16);
        }

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-bar') && !e.target.closest('.sugerencias-box-home')) {
                cerrarDropdownHome();
            }
        });
    </script>
    <script>
        fetch('clima.php')
            .then(r => r.json())
            .then(data => {
                if (data.cod !== 200) {
                    document.getElementById('clima-texto').textContent = 'Clima no disponible';
                    return;
                }
                const temp    = Math.round(data.main.temp);
                const desc    = data.weather[0].description;
                const humedad = data.main.humidity;
                const codigo  = data.weather[0].id;

                let icono = '🌤️';
                if      (codigo >= 200 && codigo < 300) icono = '⛈️';
                else if (codigo >= 300 && codigo < 400) icono = '🌦️';
                else if (codigo >= 500 && codigo < 600) icono = '🌧️';
                else if (codigo >= 600 && codigo < 700) icono = '❄️';
                else if (codigo >= 700 && codigo < 800) icono = '🌫️';
                else if (codigo === 800)                icono = '☀️';
                else if (codigo > 800)                  icono = '⛅';

                document.getElementById('clima-icono').textContent = icono;
                document.getElementById('clima-texto').textContent  = `${temp}°C · ${desc} · Humedad ${humedad}%`;
            })
            .catch(() => {
                document.getElementById('clima-texto').textContent = 'Clima no disponible';
            });
    </script>
</body>
</html>
