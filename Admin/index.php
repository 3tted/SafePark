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
require_once '../includes/auth.php';
require_once '../includes/api.php';
requiere_admin();

$datos = api_get_multi([
    'stats'    => '/areas/stats/resumen',
    'reportes' => '/reportes',
    'usuarios' => '/usuarios',
    'areas'    => '/areas',
]);

$stats           = $datos['stats'];
$total_usuarios  = $stats['total_usuarios'] ?? 0;
$total_reportes  = $stats['total_reportes'] ?? 0;
$reportes_pendientes = $stats['pendientes'] ?? 0;
$total_areas     = $stats['total_areas'] ?? 0;

$reportes    = $datos['reportes'];
$usuarios    = $datos['usuarios'];
$areas_admin = $datos['areas'];

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
        <div class="msg-ok-global">✅ Cambios guardados correctamente.</div>
    <?php elseif ($error === 'foto'): ?>
        <div class="msg-err-global">❌ Solo se permiten imágenes JPG, PNG o WEBP (máx. 3MB).</div>
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
                <?php if (empty($reportes)): ?>
                    <tr><td colspan="8" class="tabla-vacia">No hay reportes aún.</td></tr>
                <?php else: foreach ($reportes as $r):
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
                <?php endforeach; endif; ?>
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
                <?php if (empty($usuarios)): ?>
                    <tr><td colspan="6" class="tabla-vacia">No hay usuarios.</td></tr>
                <?php else: foreach ($usuarios as $u):
                        $fecha_u = date('d/m/Y', strtotime($u['fecha_registro'] ?? 'now'));
                        $email_u = $u['email'] ?? '—';
                ?>
                    <tr>
                        <td><?= $u['id_usuario'] ?></td>
                        <td><?= htmlspecialchars($u['nombre']) ?></td>
                        <td><?= htmlspecialchars($email_u) ?></td>
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
                <?php endforeach; endif; ?>
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
                <?php if (empty($areas_admin)): ?>
                    <tr><td colspan="6" class="tabla-vacia">No hay áreas.</td></tr>
                <?php else: foreach ($areas_admin as $a): ?>
                    <tr>
                        <td><?= $a['id'] ?></td>
                        <td><?= htmlspecialchars($a['nombre']) ?></td>
                        <td><?= htmlspecialchars($a['colonia'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($a['tipo'] ?? '—') ?></td>
                        <td style="font-size:0.78rem;color:var(--muted);"><?= $a['lat'] ?? '—' ?>, <?= $a['lng'] ?? '—' ?></td>
                        <td>
                            <button class="btn-actualizar" onclick='abrirEditarArea(<?= htmlspecialchars(json_encode(['id_area'=>$a['id'],'nombre'=>$a['nombre'],'colonia'=>$a['colonia'],'direccion'=>$a['direccion']??'','horario'=>$a['horario']??'','tipo'=>$a['tipo'],'lat'=>$a['lat'],'lng'=>$a['lng']]), ENT_QUOTES) ?>)'>✏️ Editar</button>
                            <form action="eliminar_area.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar esta área?')">
                                <input type="hidden" name="id_area" value="<?= $a['id'] ?>">
                                <button class="btn-actualizar" style="background:var(--risk);" type="submit">🗑️</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
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
