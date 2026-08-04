<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa - SafePark</title>
    <link rel="icon" href="../Assets/logo.png">
    <link rel="stylesheet" href="../CSS/styles.css">
    <link rel="stylesheet" href="style.css?v=2">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
</head>
<body>

<?php
require_once '../includes/auth.php';
require_once '../includes/api.php';
requiere_sesion();

$es_admin  = es_admin();
$areas_raw = api_get('/areas');

$areas_db = array_values(array_map(fn($a) => [
    'id'          => $a['id'],
    'nombre'      => $a['nombre'],
    'colonia'     => $a['colonia'],
    'direccion'   => $a['direccion'] ?? '',
    'horario'     => $a['horario'] ?? '',
    'tipo'        => $a['tipo'],
    'lat'         => (float)$a['lat'],
    'lng'         => (float)$a['lng'],
    'foto'        => $a['foto'] ? '../Assets/fotos/' . $a['foto'] : null,
    'id_usuario'  => 0,
    'puede_editar'=> $es_admin,
    'score'       => $a['score']
], array_filter($areas_raw, fn($a) => $a['lat'] && $a['lng'])));

$exito = $_GET['exito'] ?? '';
$error = $_GET['error'] ?? '';

$nav_base   = '../';
$nav_active = 'mapa';
require_once '../includes/navbar.php';
?>

    <?php if ($exito === '1'): ?>
        <div class="msg-ok-mapa">✅ Área agregada correctamente.</div>
    <?php elseif ($error === 'foto'): ?>
        <div class="msg-err-mapa">❌ Solo se permiten imágenes JPG, PNG o WEBP (máx. 3MB).</div>
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
                    <input class="modal-input" type="text" name="nombre" placeholder="Ej. Parque Las Flores..." autocomplete="off" required>
                </div>

                <div class="form-group">
                    <label class="modal-label">Colonia</label>
                    <input class="modal-input" type="text" name="colonia" placeholder="Ej. Col. Centro..." autocomplete="off" required>
                </div>

                <div class="form-group">
                    <label class="modal-label">Dirección <span style="font-weight:400;color:var(--muted);">(opcional)</span></label>
                    <input class="modal-input" type="text" name="direccion" placeholder="Ej. Av. Tecnológico 1340..." autocomplete="off">
                </div>

                <div class="form-group">
                    <label class="modal-label">Horario <span style="font-weight:400;color:var(--muted);">(opcional)</span></label>
                    <input class="modal-input" type="text" name="horario" placeholder="Ej. Lun a Dom 6:00 - 22:00" autocomplete="off">
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
                    <div class="foto-upload-area" id="drop-agregar" onclick="document.getElementById('input-foto-agregar').click()">
                        <div id="placeholder-agregar">📷 Adjuntar foto · JPG, PNG o WEBP · Máx. 3MB</div>
                        <img id="preview-agregar" src="" alt="Preview" style="display:none;max-width:100%;max-height:180px;border-radius:8px;">
                    </div>
                    <input type="file" id="input-foto-agregar" name="foto" accept="image/*" style="display:none" onchange="previewFotoMapa(this,'placeholder-agregar','preview-agregar')">
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
        const API_URL  = '<?= API_DATOS ?>';   // el JS solo usa el API de Datos
    </script>
    <script src="archivo.js?v=7"></script>
</body>
</html>
