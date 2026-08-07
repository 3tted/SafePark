<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explorar - SafePark</title>
    <link rel="icon" href="../Assets/logo.png">
    <link rel="stylesheet" href="../CSS/styles.css">
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>

<?php
require_once '../includes/auth.php';   // define es_invitado() e inicia la sesión
require_once '../includes/api.php';

// Sin candado: esta página se puede ver como invitado.
// Lo único que exige cuenta aquí son los favoritos.
$de_invitado = es_invitado();

$emoji_tipo = ['parque' => '🌳', 'deportivo' => '⚽', 'plaza' => '🏛️'];
$label_tipo = ['parque' => 'Parque', 'deportivo' => 'Deportivo', 'plaza' => 'Plaza'];
$gradientes = [
    'parque'    => 'linear-gradient(135deg, #d8f3dc, #95d5b2)',
    'deportivo' => 'linear-gradient(135deg, #c6f6d5, #52b788)',
    'plaza'     => 'linear-gradient(135deg, #fef3c7, #f4a261)',
];

$areas_raw = api_get('/areas');
$areas = array_map(fn($a) => [
    'id_area'   => $a['id'],
    'nombre'    => $a['nombre'],
    'colonia'   => $a['colonia'],
    'direccion' => $a['direccion'] ?? '',
    'horario'   => $a['horario'] ?? '',
    'tipo'      => $a['tipo'],
    'foto'      => $a['foto'],
    'score'     => $a['score'],
    // Estos dos los calcula el API junto con el score. Si se olvidan aquí,
    // la tarjeta cae en el valor por defecto y dice "sin reportes" siempre.
    'reportes'     => $a['reportes']     ?? 0,
    'total_ciudad' => $a['total_ciudad'] ?? 0,
], $areas_raw);

$nav_base   = '../';
$nav_active = 'explorar';
require_once '../includes/navbar.php';
?>

    <!-- Busqueda -->
    <div class="explorar-hero">
        <h2>🌿 Explorar Áreas Verdes</h2>
        <p>Encuentra el espacio perfecto para ti en Ciudad Juárez</p>
        <div style="position:relative;width:100%;max-width:420px;margin:0 auto;">
            <div class="search-bar-full">
                <input type="text" id="busqueda" placeholder="Buscar por nombre, colonia o tipo de área..." oninput="filtrarAreas(); mostrarSugerenciasExplorar()" autocomplete="off" value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>">
                <button class="btn-search" onclick="filtrarAreas()">Buscar</button>
            </div>
            <!-- Una sola lista: las áreas de SafePark y los lugares de Ciudad
                 Juárez van agrupados dentro de ella, no en cajas separadas. -->
            <div class="sugerencias-box-explorar" id="sugerencias-box-explorar" style="display:none;"></div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="explorar-filtros">
        <span class="filtro-label">Tipo:</span>
        <div class="chip active" onclick="toggleChipGrupo(this,'tipo')" data-valor="todos">Todos</div>
        <div class="chip" onclick="toggleChipGrupo(this,'tipo')" data-valor="parque">🌳 Parques</div>
        <div class="chip" onclick="toggleChipGrupo(this,'tipo')" data-valor="deportivo">⚽ Deportivo</div>
        <div class="chip" onclick="toggleChipGrupo(this,'tipo')" data-valor="plaza">🏛️ Plaza</div>
        <div class="filtro-sep"></div>
        <span class="filtro-label">Seguridad:</span>
        <div class="chip active" onclick="toggleChipGrupo(this,'seguridad')" data-valor="todos">Todas</div>
        <div class="chip chip-seguro" onclick="toggleChipGrupo(this,'seguridad')" data-valor="seguro">🟢 Seguro</div>
        <div class="chip chip-warn" onclick="toggleChipGrupo(this,'seguridad')" data-valor="precaucion">🟡 Precaución</div>
        <div class="chip chip-risk" onclick="toggleChipGrupo(this,'seguridad')" data-valor="riesgo">🔴 Riesgo</div>
    </div>

    <!-- Cuerpo principal -->
    <div class="explorar-body">
        <div class="explorar-header">
            <div class="explorar-count">📍 <strong id="total-areas"><?= count($areas) ?> áreas</strong> encontradas</div>
        </div>

        <div class="explorar-grid" id="explorar-grid">

            <?php if (count($areas) === 0): ?>
                <p style="color:var(--muted);">Aún no hay áreas agregadas. Ve al <a href="../Mapa/index.php">Mapa</a> para agregar la primera.</p>
            <?php else: foreach ($areas as $area):
                $tipo = $area['tipo'] ?: 'parque';
                $emoji = $emoji_tipo[$tipo] ?? '🌳';
                $label = $label_tipo[$tipo] ?? 'Área';
                $gradiente = $gradientes[$tipo] ?? $gradientes['parque'];
                $imagen = $area['foto'] ? "background-image:url('../Assets/fotos/{$area['foto']}');background-size:cover;background-position:center;" : "background:{$gradiente};";
                $score = $area['score'];
                $sem_cls = $score >= 70 ? 'sem-safe' : ($score >= 40 ? 'sem-warn' : 'sem-risk');
                $sem_lbl = $score >= 70 ? '● Seguro' : ($score >= 40 ? '⚠ Precaución' : '✕ Riesgo');
                $foto_modal = $area['foto'] ? '../Assets/fotos/' . htmlspecialchars($area['foto']) : '';

                // Cuántos reportes tiene el área, con el total de la ciudad al
                // lado para dar la escala: ocho reportes dicen poco si no se
                // sabe si en la ciudad hay veinte o dos mil.
                $n_rep    = $area['reportes']     ?? 0;
                $en_ciudad= $area['total_ciudad'] ?? 0;

                $contexto = $n_rep === 0
                    ? 'Sin reportes todavía'
                    : $n_rep . ' de ' . $en_ciudad . ' reportes en la ciudad';
            ?>
                <div class="ecard"
                    data-tipo="<?= $tipo ?>"
                    data-id="<?= $area['id_area'] ?>"
                    data-nombre="<?= htmlspecialchars($area['nombre'], ENT_QUOTES) ?>"
                    data-colonia="<?= htmlspecialchars($area['colonia'], ENT_QUOTES) ?>"
                    data-direccion="<?= htmlspecialchars($area['direccion'] ?? '', ENT_QUOTES) ?>"
                    data-horario="<?= htmlspecialchars($area['horario'] ?? '', ENT_QUOTES) ?>"
                    data-score="<?= $score ?>"
                    data-contexto="<?= htmlspecialchars($contexto, ENT_QUOTES) ?>"
                    data-foto="<?= htmlspecialchars($foto_modal, ENT_QUOTES) ?>"
                    onclick="abrirModalArea(this)">
                    <div class="ecard-img" style="<?= $imagen ?>"><?= $area['foto'] ? '' : $emoji ?></div>
                    <div class="ecard-body">
                        <div class="ecard-name"><?= htmlspecialchars($area['nombre']) ?></div>
                        <div class="ecard-meta">📍 <?= htmlspecialchars($area['colonia']) ?></div>
                        <div class="ecard-tags">
                            <span class="etag"><?= $emoji ?> <?= $label ?></span>
                        </div>
                        <div class="ecard-contexto">📋 <?= htmlspecialchars($contexto) ?></div>
                        <div class="ecard-foot">
                            <div class="semaforo <?= $sem_cls ?>"><?= $sem_lbl ?> <span class="sem-score"><?= $score ?>/100</span></div>
                            <?php if (!$de_invitado): ?>
                            <button class="btn-fav" data-id="<?= $area['id_area'] ?>" onclick="event.stopPropagation(); toggleFav(this)">♡</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; endif; ?>

        </div>

        <!-- Sin resultados -->
        <div class="sin-resultados" id="sin-resultados" style="display:none;">
            <div class="sin-resultados-icon">🔍</div>
            <div class="sin-resultados-texto">No se encontraron áreas con esos filtros</div>
            <div class="sin-resultados-sub">Intenta cambiar los filtros o la búsqueda</div>
        </div>
    </div>

    <div class="footer-bar">SafePark · Explorar áreas · Ciudad Juárez</div>

    <!-- Modal detalle de área -->
    <div class="modal-overlay" id="modal-area" style="display:none;" onclick="if(event.target===this) cerrarModalArea()">
        <div class="modal-area-card">
            <button class="modal-area-close" onclick="cerrarModalArea()">✕</button>

            <div class="modal-area-foto" id="modal-foto"></div>

            <div class="modal-area-body">
                <div class="modal-area-head">
                    <div>
                        <div class="modal-area-nombre" id="modal-nombre"></div>
                        <div class="modal-area-meta" id="modal-meta"></div>
                        <div class="modal-area-meta" id="modal-direccion" style="display:none;"></div>
                        <div class="modal-area-meta" id="modal-horario" style="display:none;"></div>
                    </div>
                    <div class="semaforo" id="modal-semaforo"></div>
                    <div class="modal-contexto" id="modal-contexto"></div>
                </div>

                <div class="modal-area-sec">Reportes recientes</div>
                <div id="modal-reportes">
                    <div class="modal-loading">Cargando reportes...</div>
                </div>

                <a class="modal-area-mapa-btn" id="modal-mapa-link" href="#">🗺️ Ver en el mapa</a>
            </div>
        </div>
    </div>

    <script>
        const ID_USUARIO = <?= (int)($_SESSION['id_usuario'] ?? 0) ?>;   // 0 = invitado
        const API_URL    = '<?= API_DATOS ?>';   // el JS solo usa el API de Datos
    </script>
    <script src="explorar.js?v=2"></script>
</body>
</html>
