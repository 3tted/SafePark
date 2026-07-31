<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportar - SafePark</title>
    <link rel="icon" href="../Assets/logo.png">
    <link rel="stylesheet" href="../CSS/styles.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php
require_once '../includes/auth.php';
require_once '../includes/api.php';
requiere_sesion();

$id_usuario = $_SESSION['id_usuario'];
$exito = $_GET['exito'] ?? '';
$error = $_GET['error'] ?? '';

$datos = api_get_multi([
    'areas'    => '/areas',
    'reportes' => '/reportes/usuario/' . $id_usuario,
]);

$areas        = $datos['areas'];
$mis_reportes = array_slice($datos['reportes'], 0, 5);

$iconos_tipo  = ['incidente' => '🚨', 'condicion' => '🏚️', 'sugerencia' => '💡'];
$labels_tipo  = ['incidente' => 'Incidente de seguridad', 'condicion' => 'Condición del área', 'sugerencia' => 'Sugerencia'];
$iconos_estado = ['pendiente' => ['bg' => '#fef3c7', 'icon' => '⚠️'], 'en_proceso' => ['bg' => '#dbeafe', 'icon' => '🔄'], 'resuelto' => ['bg' => '#d8f3dc', 'icon' => '✅']];
$tags_estado  = ['pendiente' => 'tag-pend', 'en_proceso' => 'tag-proc', 'resuelto' => 'tag-aprov'];
$labels_estado = ['pendiente' => 'Pendiente', 'en_proceso' => 'En proceso', 'resuelto' => 'Resuelto'];

$nav_base   = '../';
$nav_active = 'reportar';
require_once '../includes/navbar.php';
?>

    <div class="reportes-layout">

        <!-- Formulario -->
        <div class="form-section">
            <div class="form-title">📋 Enviar reporte ciudadano</div>
            <div class="form-sub">Ayuda a la comunidad informando sobre la seguridad o condición de un área</div>

            <?php if ($exito === '1'): ?>
                <div class="msg-ok">✅ Reporte enviado correctamente. Será revisado pronto.</div>
            <?php elseif ($error === 'campos'): ?>
                <div class="msg-err">❌ Completa todos los campos antes de enviar.</div>
            <?php elseif ($error === 'foto'): ?>
                <div class="msg-err">❌ Solo se permiten imágenes JPG, PNG o WEBP (máx. 5MB).</div>
            <?php elseif ($error === 'servidor'): ?>
                <div class="msg-err">❌ Error del servidor, intenta de nuevo.</div>
            <?php endif; ?>

            <form action="guardar_reporte.php" method="POST" id="form-reporte" enctype="multipart/form-data">
                <input type="hidden" name="tipo" id="input-tipo" value="incidente">

                <div class="form-group">
                    <label class="form-label">¿Qué quieres reportar?</label>
                    <div class="tipo-grid">
                        <div class="tipo-card sel" onclick="selectTipo(this,'incidente')">
                            <span>🚨</span>
                            <p>Incidente de seguridad</p>
                        </div>
                        <div class="tipo-card" onclick="selectTipo(this,'condicion')">
                            <span>🏚️</span>
                            <p>Condición del área</p>
                        </div>
                        <div class="tipo-card" onclick="selectTipo(this,'sugerencia')">
                            <span>💡</span>
                            <p>Sugerencia</p>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Área o zona</label>
                    <select class="form-input" name="id_area" required>
                        <option value="">Selecciona un área...</option>
                        <?php foreach ($areas as $area): ?>
                            <option value="<?= $area['id'] ?>"><?= htmlspecialchars($area['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea class="form-input" name="descripcion" rows="4" placeholder="Describe lo que observaste..." required></textarea>
                </div>



                <div class="form-group">
                    <label class="form-label">Foto (opcional)</label>
                    <div class="foto-upload-area" id="foto-drop" onclick="document.getElementById('input-foto').click()">
                        <div id="foto-placeholder">📷 Adjuntar foto · JPG, PNG o WEBP · Máx. 5MB</div>
                        <img id="foto-preview" src="" alt="Preview" style="display:none;max-width:100%;max-height:200px;border-radius:8px;">
                    </div>
                    <input type="file" id="input-foto" name="foto" accept="image/*" style="display:none" onchange="previewFotoReporte(this)">
                </div>

                <button class="btn-submit" type="submit">Enviar reporte</button>
            </form>
        </div>

        <!-- Panel lateral: mis reportes -->
        <div class="reportes-side">
            <div class="side-title">Mis reportes recientes</div>

            <?php if (empty($mis_reportes)): ?>
                <p style="color:#888;font-size:0.9rem;">Aún no tienes reportes.</p>
            <?php else: foreach ($mis_reportes as $r):
                $tipo    = $r['tipo'] ?? 'incidente';
                $estado  = $r['estado'] ?? 'pendiente';
                $icon_t  = $iconos_tipo[$tipo]  ?? '📋';
                $label_t = $labels_tipo[$tipo]  ?? ucfirst($tipo);
                $icon_e  = $iconos_estado[$estado]['icon'] ?? '⚠️';
                $bg_e    = $iconos_estado[$estado]['bg']   ?? '#fef3c7';
                $tag_cls = $tags_estado[$estado]  ?? 'tag-pend';
                $label_e = $labels_estado[$estado] ?? ucfirst($estado);
                $area_nombre = htmlspecialchars($r['area'] ?? 'Sin área');
                $fecha = date('d/m/Y', strtotime($r['fecha'] ?? 'now'));
            ?>
                <div class="reporte-big">
                    <div class="reporte-big-head">
                        <div class="reporte-big-icon" style="background:<?= $bg_e ?>;"><?= $icon_e ?></div>
                        <div>
                            <div class="reporte-big-tipo"><?= $label_t ?></div>
                            <div class="reporte-big-meta"><?= $area_nombre ?> · <?= $fecha ?></div>
                        </div>
                        <span class="tag <?= $tag_cls ?>"><?= $label_e ?></span>
                    </div>
                    <div class="reporte-big-desc"><?= htmlspecialchars($r['descripcion'] ?? '') ?></div>
                    <?php if (!empty($r['foto'])): ?>
                        <img src="../Assets/fotos/<?= htmlspecialchars($r['foto']) ?>" alt="Foto del reporte" class="reporte-big-foto">
                    <?php endif; ?>
                </div>
            <?php endforeach; endif; ?>
        </div>

    </div>

    <div class="footer-bar">SafePark · Reportar · Ciudad Juárez</div>

    <script src="reportar.js"></script>
    <script>
    function previewFotoReporte(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                document.getElementById('foto-placeholder').style.display = 'none';
                const img = document.getElementById('foto-preview');
                img.src = e.target.result;
                img.style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
    </script>
</body>
</html>
