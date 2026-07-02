<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - SafePark</title>
    <link rel="icon" href="../Assets/logo.png">
    <link rel="stylesheet" href="../CSS/styles.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php
require_once '../database/conexion.php';
require_once '../includes/auth.php';
requiere_admin($conn);

// Estadísticas generales
$total_usuarios = $conn->query("SELECT COUNT(*) FROM USUARIO")->fetch_row()[0];
$total_reportes = $conn->query("SELECT COUNT(*) FROM REPORTE")->fetch_row()[0];
$reportes_pendientes = $conn->query("SELECT COUNT(*) FROM REPORTE WHERE estado = 'pendiente'")->fetch_row()[0];
$total_areas = $conn->query("SELECT COUNT(*) FROM AREA")->fetch_row()[0];

// Reportes recientes
$reportes = $conn->query("
    SELECT R.id_reporte, R.tipo, R.descripcion, R.estado, R.fecha,
           U.nombre AS usuario, A.nombre AS area
    FROM REPORTE R
    JOIN USUARIO U ON R.id_usuario = U.id_usuario
    JOIN AREA A ON R.id_area = A.id_area
    ORDER BY R.fecha DESC
    LIMIT 20
");

// Usuarios recientes
$usuarios = $conn->query("
    SELECT id_usuario, nombre, email, rol, fecha_registro
    FROM USUARIO
    ORDER BY fecha_registro DESC
    LIMIT 10
");

// Áreas
$areas_admin = $conn->query("
    SELECT id_area, nombre, colonia, tipo, lat, lng
    FROM AREA
    ORDER BY id_area DESC
");

$exito = $_GET['exito'] ?? '';
$error = $_GET['error'] ?? '';

$labels_tipo  = ['incidente' => '🚨 Incidente', 'condicion' => '🏚️ Condición', 'sugerencia' => '💡 Sugerencia'];
$labels_estado = ['pendiente' => 'Pendiente', 'en_proceso' => 'En proceso', 'resuelto' => 'Resuelto'];
$tags_estado   = ['pendiente' => 'tag-pend', 'en_proceso' => 'tag-proc', 'resuelto' => 'tag-aprov'];

$nav_base   = '../';
$nav_active = 'admin';
require_once '../includes/navbar.php';
?>

    <div class="admin-hero">
        <h2>⚙️ Panel de Administración</h2>
        <p>Gestiona reportes, usuarios y áreas de SafePark</p>
    </div>

    <?php if ($exito === '1'): ?>
        <div class="msg-ok-global">✅ Reporte actualizado correctamente.</div>
    <?php elseif ($error === 'servidor'): ?>
        <div class="msg-err-global">❌ Error del servidor, intenta de nuevo.</div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="admin-stats">
        <div class="astat">
            <div class="astat-n"><?= $total_usuarios ?></div>
            <div class="astat-l">👤 Usuarios</div>
        </div>
        <div class="astat">
            <div class="astat-n"><?= $total_reportes ?></div>
            <div class="astat-l">📋 Reportes totales</div>
        </div>
        <div class="astat astat-warn">
            <div class="astat-n"><?= $reportes_pendientes ?></div>
            <div class="astat-l">⏳ Pendientes</div>
        </div>
        <div class="astat">
            <div class="astat-n"><?= $total_areas ?></div>
            <div class="astat-l">📍 Áreas</div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="admin-layout">

        <div class="admin-tabs">
            <div class="admin-tab active" onclick="cambiarTab(this,'reportes')">📋 Reportes</div>
            <div class="admin-tab" onclick="cambiarTab(this,'usuarios')">👤 Usuarios</div>
            <div class="admin-tab" onclick="cambiarTab(this,'areas')">📍 Áreas</div>
        </div>

        <!-- Tab Reportes -->
        <div class="atab-panel" id="tab-reportes">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tipo</th>
                        <th>Usuario</th>
                        <th>Área</th>
                        <th>Descripción</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($reportes->num_rows === 0): ?>
                    <tr><td colspan="8" class="tabla-vacia">No hay reportes aún.</td></tr>
                <?php else:
                    while ($r = $reportes->fetch_assoc()):
                        $tag = $tags_estado[$r['estado']] ?? '';
                        $label = $labels_estado[$r['estado']] ?? $r['estado'];
                        $tipo_label = $labels_tipo[$r['tipo']] ?? $r['tipo'];
                        $fecha = date('d/m/Y H:i', strtotime($r['fecha']));
                ?>
                    <tr>
                        <td><?= $r['id_reporte'] ?></td>
                        <td><?= $tipo_label ?></td>
                        <td><?= htmlspecialchars($r['usuario']) ?></td>
                        <td><?= htmlspecialchars($r['area']) ?></td>
                        <td class="desc-cell"><?= htmlspecialchars(mb_substr($r['descripcion'], 0, 60)) ?>...</td>
                        <td><?= $fecha ?></td>
                        <td><span class="tag <?= $tag ?>"><?= $label ?></span></td>
                        <td>
                            <form action="actualizar_reporte.php" method="POST" class="form-estado">
                                <input type="hidden" name="id_reporte" value="<?= $r['id_reporte'] ?>">
                                <select name="estado" class="select-estado" onchange="this.form.submit()">
                                    <option value="pendiente"  <?= $r['estado']==='pendiente'  ? 'selected':'' ?>>Pendiente</option>
                                    <option value="en_proceso" <?= $r['estado']==='en_proceso' ? 'selected':'' ?>>En proceso</option>
                                    <option value="resuelto"   <?= $r['estado']==='resuelto'   ? 'selected':'' ?>>Resuelto</option>
                                </select>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Tab Usuarios -->
        <div class="atab-panel" id="tab-usuarios" style="display:none;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($usuarios->num_rows === 0): ?>
                    <tr><td colspan="6" class="tabla-vacia">No hay usuarios.</td></tr>
                <?php else:
                    while ($u = $usuarios->fetch_assoc()):
                        $fecha_u = date('d/m/Y', strtotime($u['fecha_registro']));
                ?>
                    <tr>
                        <td><?= $u['id_usuario'] ?></td>
                        <td><?= htmlspecialchars($u['nombre']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <span class="tag <?= $u['rol']==='admin' ? 'tag-admin' : 'tag-pend' ?>">
                                <?= $u['rol'] === 'admin' ? '⚙️ Admin' : '👤 Usuario' ?>
                            </span>
                        </td>
                        <td><?= $fecha_u ?></td>
                        <td>
                            <form action="cambiar_rol.php" method="POST" class="form-estado">
                                <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                                <select name="rol" class="select-estado" onchange="this.form.submit()">
                                    <option value="usuario" <?= $u['rol']==='usuario' ? 'selected':'' ?>>Usuario</option>
                                    <option value="admin"   <?= $u['rol']==='admin'   ? 'selected':'' ?>>Admin</option>
                                </select>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Tab Áreas -->
        <div class="atab-panel" id="tab-areas" style="display:none;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Colonia</th>
                        <th>Tipo</th>
                        <th>Coordenadas</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($areas_admin->num_rows === 0): ?>
                    <tr><td colspan="6" class="tabla-vacia">No hay áreas.</td></tr>
                <?php else:
                    while ($a = $areas_admin->fetch_assoc()):
                ?>
                    <tr>
                        <td><?= $a['id_area'] ?></td>
                        <td><?= htmlspecialchars($a['nombre']) ?></td>
                        <td><?= htmlspecialchars($a['colonia'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($a['tipo'] ?? '—') ?></td>
                        <td style="font-size:0.78rem;color:var(--muted);"><?= $a['lat'] ?? '—' ?>, <?= $a['lng'] ?? '—' ?></td>
                        <td>
                            <button class="btn-actualizar" onclick='abrirEditarArea(<?= json_encode($a) ?>)'>✏️ Editar</button>
                            <form action="eliminar_area.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar esta área?')">
                                <input type="hidden" name="id_area" value="<?= $a['id_area'] ?>">
                                <button class="btn-actualizar" style="background:var(--risk);" type="submit">🗑️</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <?php
    $accion_editar_area = 'actualizar_area.php';
    $mostrar_btn_ubicacion = false;
    require '../includes/modal_editar_area.php';
    ?>

    <div class="footer-bar">SafePark · Panel Admin · Ciudad Juárez</div>

    <script src="admin.js"></script>
</body>
</html>
