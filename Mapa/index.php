<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa - SafePark</title>
    <link rel="icon" href="../Assets/logo.png">
    <link rel="stylesheet" href="../CSS/styles.css">
    <link rel="stylesheet" href="style.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
</head>
<body>

<?php
require_once '../database/conexion.php';
require_once '../includes/auth.php';
requiere_sesion();

$es_admin = es_admin($conn, $_SESSION['id_usuario']);

// Áreas con coordenadas para el mapa
$result = $conn->query("SELECT id_area, nombre, colonia, tipo, lat, lng, foto, id_usuario FROM AREA WHERE lat IS NOT NULL AND lng IS NOT NULL");
$areas_db = [];
while ($row = $result->fetch_assoc()) {
    $areas_db[] = [
        'id'         => (int)$row['id_area'],
        'nombre'     => $row['nombre'],
        'colonia'    => $row['colonia'] ?? '',
        'tipo'       => $row['tipo'] ?? 'parque',
        'lat'        => (float)$row['lat'],
        'lng'        => (float)$row['lng'],
        'foto'       => $row['foto'] ? '../Assets/fotos/' . $row['foto'] : null,
        'id_usuario' => (int)$row['id_usuario'],
        'puede_editar' => ($es_admin || (int)$row['id_usuario'] === (int)$_SESSION['id_usuario']),
        'score'      => 70 // valor inicial, se calcula con reportes despues
    ];
}

$exito = $_GET['exito'] ?? '';
$error = $_GET['error'] ?? '';

$nav_base   = '../';
$nav_active = 'mapa';
require_once '../includes/navbar.php';
?>

    <?php if ($exito === '1'): ?>
        <div class="msg-ok-mapa">✅ Área agregada correctamente.</div>
    <?php elseif ($error === '1'): ?>
        <div class="msg-err-mapa">❌ Error al agregar el área.</div>
    <?php endif; ?>

    <div class="mapa-page">
        <!-- Panel lateral -->
        <div class="mapa-panel">
            <div class="mapa-panel-head">
                <h3>Áreas verdes</h3>
                <input class="panel-search" type="text" id="panel-search" placeholder="Buscar área o colonia..." oninput="filtrarLista()">
                <div class="filter-chips">
                    <button class="chip active" onclick="filtrarChip(this,'todos')">Todos</button>
                    <button class="chip" onclick="filtrarChip(this,'parque')">Parques</button>
                    <button class="chip" onclick="filtrarChip(this,'deportivo')">Deportivo</button>
                    <button class="chip" onclick="filtrarChip(this,'plaza')">Plaza</button>
                </div>
            </div>
            <div class="mapa-lista" id="mapa-lista"></div>
        </div>

        <!-- Mapa -->
        <div class="mapa-contenedor">
            <div id="map"></div>
            <div class="leyenda">
                <div class="leyenda-item"><div class="dot safe"></div> Seguro</div>
                <div class="leyenda-item"><div class="dot warn"></div> Precaución</div>
                <div class="leyenda-item"><div class="dot risk"></div> Riesgo</div>
            </div>
            <button class="btn-ubicacion" onclick="centrarUsuario()">📍 Mi ubicación</button>
            <div class="aviso-agregar" id="aviso-agregar">📍 Haz clic en el mapa para elegir la ubicación de tu nueva área</div>
            <button class="btn-agregar-area" onclick="activarAgregarArea()">+ Agregar área</button>
        </div>
    </div>

    <!-- Modal: agregar área -->
    <div class="modal-overlay" id="modal-area" style="display:none;" onclick="if(event.target===this) cerrarModalArea()">
        <div class="modal-card">
            <div class="modal-title">📍 Agregar nueva área</div>
            <form action="guardar_area.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="lat" id="input-lat">
                <input type="hidden" name="lng" id="input-lng">

                <div class="form-group">
                    <label class="modal-label">Nombre del área</label>
                    <input class="modal-input" type="text" name="nombre" placeholder="Ej. Parque Las Flores..." required>
                </div>

                <div class="form-group">
                    <label class="modal-label">Colonia</label>
                    <input class="modal-input" type="text" name="colonia" placeholder="Ej. Col. Centro..." required>
                </div>

                <div class="form-group">
                    <label class="modal-label">Tipo</label>
                    <select class="modal-input" name="tipo" required>
                        <option value="parque">🌳 Parque</option>
                        <option value="deportivo">⚽ Deportivo</option>
                        <option value="plaza">🏛️ Plaza</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="modal-label">Foto (opcional)</label>
                    <input class="modal-input" type="file" name="foto" accept="image/*">
                </div>

                <div class="modal-btns">
                    <button type="button" class="btn-cancelar-modal" onclick="cerrarModalArea()">Cancelar</button>
                    <button type="submit" class="btn-guardar-modal">Agregar área</button>
                </div>
            </form>
        </div>
    </div>

    <?php
    $accion_editar_area = 'actualizar_area_usuario.php';
    $mostrar_btn_ubicacion = true;
    require '../includes/modal_editar_area.php';
    ?>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const AREAS_DB = <?= json_encode($areas_db) ?>;
    </script>
    <script src="archivo.js?v=5"></script>
</body>
</html>
