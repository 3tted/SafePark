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
require_once '../database/conexion.php';
require_once '../includes/auth.php';
requiere_sesion();

$id_usuario = $_SESSION['id_usuario'];
$exito = $_GET['exito'] ?? '';
$error = $_GET['error'] ?? '';

// Cargar áreas para el select
$areas = $conn->query("SELECT id_area, nombre FROM AREA ORDER BY nombre");

// Cargar reportes del usuario
$stmt = $conn->prepare("
    SELECT R.tipo, R.descripcion, R.estado, R.fecha, A.nombre AS area_nombre
    FROM REPORTE R
    JOIN AREA A ON R.id_area = A.id_area
    WHERE R.id_usuario = ?
    ORDER BY R.fecha DESC
    LIMIT 5
");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$mis_reportes = $stmt->get_result();

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
            <?php elseif ($error === 'servidor'): ?>
                <div class="msg-err">❌ Error del servidor, intenta de nuevo.</div>
            <?php endif; ?>

            <form action="guardar_reporte.php" method="POST" id="form-reporte">
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
                        <?php if ($areas && $areas->num_rows > 0):
                            while ($area = $areas->fetch_assoc()): ?>
                            <option value="<?= $area['id_area'] ?>"><?= htmlspecialchars($area['nombre']) ?></option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea class="form-input" name="descripcion" rows="4" placeholder="Describe lo que observaste..." required></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Foto (opcional)</label>
                    <div class="upload-area">
                        📷 Próximamente disponible
                    </div>
                </div>

                <button class="btn-submit" type="submit">Enviar reporte</button>
            </form>
        </div>

        <!-- Panel lateral: mis reportes -->
        <div class="reportes-side">
            <div class="side-title">Mis reportes recientes</div>

            <div class="reporte-big">
                <div class="reporte-big-head">
                    <div class="reporte-big-icon" style="background:#d8f3dc;">✅</div>
                    <div>
                        <div class="reporte-big-tipo">Condición del área</div>
                        <div class="reporte-big-meta">Parque Central · Hace 2 días</div>
                    </div>
                    <span class="tag tag-aprov">Aprobado</span>
                </div>
                <div class="reporte-big-desc">Parque bien mantenido, bancas limpias y buena iluminación nocturna.</div>
            </div>

            <div class="reporte-big">
                <div class="reporte-big-head">
                    <div class="reporte-big-icon" style="background:#fef3c7;">⚠️</div>
                    <div>
                        <div class="reporte-big-tipo">Incidente seguridad</div>
                        <div class="reporte-big-meta">Plaza Norte · Hace 5 días</div>
                    </div>
                    <span class="tag tag-pend">Pendiente</span>
                </div>
                <div class="reporte-big-desc">Poca iluminación en el acceso principal después de las 9pm.</div>
            </div>

            <div class="reporte-big">
                <div class="reporte-big-head">
                    <div class="reporte-big-icon" style="background:#fee2e2;">❌</div>
                    <div>
                        <div class="reporte-big-tipo">Sugerencia</div>
                        <div class="reporte-big-meta">Bosque Sur · Hace 8 días</div>
                    </div>
                    <span class="tag tag-rech">Rechazado</span>
                </div>
                <div class="reporte-big-desc">Reporte duplicado. Ya existe uno similar aprobado.</div>
            </div>
        </div>

    </div>

    <div class="footer-bar">SafePark · Reportar · Ciudad Juárez</div>

    <script src="reportar.js"></script>
</body>
</html>
