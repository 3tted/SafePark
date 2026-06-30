<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explorar - SafePark</title>
    <link rel="icon" href="../Assets/logo.png">
    <link rel="stylesheet" href="../CSS/styles.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php
session_start();
require_once '../database/conexion.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../Login/index.php');
    exit;
}

// Áreas con coordenadas (las mismas que se ven en el Mapa)
$result = $conn->query("SELECT id_area, nombre, colonia, tipo, foto FROM AREA WHERE lat IS NOT NULL AND lng IS NOT NULL ORDER BY id_area DESC");

$emoji_tipo = ['parque' => '🌳', 'deportivo' => '⚽', 'plaza' => '🏛️'];
$label_tipo = ['parque' => 'Parque', 'deportivo' => 'Deportivo', 'plaza' => 'Plaza'];
$gradientes = [
    'parque'    => 'linear-gradient(135deg, #d8f3dc, #95d5b2)',
    'deportivo' => 'linear-gradient(135deg, #c6f6d5, #52b788)',
    'plaza'     => 'linear-gradient(135deg, #fef3c7, #f4a261)',
];

$areas = [];
while ($row = $result->fetch_assoc()) {
    $areas[] = $row;
}

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
            ?>
                <div class="ecard" data-tipo="<?= $tipo ?>" onclick="window.location.href='../Mapa/index.php'">
                    <div class="ecard-img" style="<?= $imagen ?>"><?= $area['foto'] ? '' : $emoji ?></div>
                    <div class="ecard-body">
                        <div class="ecard-name"><?= htmlspecialchars($area['nombre']) ?></div>
                        <div class="ecard-meta">📍 <?= htmlspecialchars($area['colonia']) ?></div>
                        <div class="ecard-tags">
                            <span class="etag"><?= $emoji ?> <?= $label ?></span>
                        </div>
                        <div class="ecard-foot">
                            <div class="semaforo sem-safe">● Ver en el mapa</div>
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

    <script src="explorar.js"></script>
</body>
</html>
