<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - SafePark</title>
    <link rel="icon" href="../Assets/logo.png">
    <link rel="stylesheet" href="../CSS/styles.css">
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>

<?php
require_once '../includes/auth.php';
require_once '../includes/api.php';
requiere_sesion();

$id = $_SESSION['id_usuario'];

// Perfil, reportes, favoritos y actividad se piden en paralelo
$datos = api_get_multi([
    'perfil'    => '/usuarios/' . $id,
    'reportes'  => '/reportes/usuario/' . $id,
    'favoritos' => '/favoritos/' . $id,
    'actividad' => '/usuarios/' . $id . '/actividad',
]);

$perfil_api = $datos['perfil'];

$nombre  = htmlspecialchars($perfil_api['nombre'] ?? '');
$email   = htmlspecialchars($perfil_api['email'] ?? '');
$fecha   = date('d/m/Y', strtotime($perfil_api['fecha_registro'] ?? 'now'));
$inicial = strtoupper(mb_substr($nombre, 0, 1));
$foto    = $perfil_api['foto_perfil'] ?? null;

$puntos_usuario         = $perfil_api['puntos'] ?? 0;
$total_reportes_usuario = array_sum(array_column($perfil_api['reportes'] ?? [], 'total'));
$mis_reportes           = $datos['reportes'];
$total_favoritos        = count($datos['favoritos']);
$actividad              = $datos['actividad'];

// Cómo se dibuja cada tipo de actividad: ícono, color de fondo y frase
$estilo_actividad = [
    'reporte'    => ['bg' => '#fef3c7', 'verbo' => 'Reportaste en'],
    'evento'     => ['bg' => '#dbeafe', 'verbo' => 'Organizaste un evento en'],
    'comentario' => ['bg' => '#ede9fe', 'verbo' => 'Comentaste en'],
    'reaccion'   => ['bg' => '#fee2e2', 'verbo' => 'Reaccionaste en'],
];
$iconos_reporte = ['incidente' => '🚨', 'condicion' => '🏚️', 'sugerencia' => '💡'];

$nav_base   = '../';
$nav_active = 'perfil';
require_once '../includes/navbar.php';
?>

    <!-- Hero del perfil -->
    <div class="perfil-hero">
        <div class="perfil-avatar">
            <?php if ($foto): ?>
                <img src="../Assets/fotos/<?= htmlspecialchars($foto) ?>" alt="Foto de perfil" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
            <?php else: ?>
                <?= $inicial ?>
            <?php endif; ?>
        </div>
        <div class="perfil-hero-info">
            <h2><?= $nombre ?></h2>
            <p class="perfil-email"><?= $email ?></p>
            <p class="perfil-fecha">Miembro desde <?= $fecha ?></p>
            <div class="perfil-badges">
                <span class="pbadge">🌱 Ciudadano activo</span>
                <span class="pbadge">📍 Ciudad Juárez</span>
            </div>
        </div>
        <div class="perfil-stats">
            <div class="pstat"><div class="pstat-n"><?= $total_reportes_usuario ?></div><div class="pstat-l">Reportes</div></div>
            <div class="pstat"><div class="pstat-n"><?= $total_favoritos ?></div><div class="pstat-l">Favoritos</div></div>
            <div class="pstat"><div class="pstat-n"><?= $puntos_usuario ?></div><div class="pstat-l">Puntos</div></div>
        </div>
    </div>

    <!-- Layout principal -->
    <div class="perfil-layout">

        <!-- Columna izquierda: datos y logros -->
        <div class="perfil-side">

            <div class="pcard">
                <div class="pcard-title">Información personal</div>
                <div class="pinfo-row">
                    <span class="pinfo-label">Nombre</span>
                    <span class="pinfo-val"><?= $nombre ?></span>
                </div>
                <div class="pinfo-row">
                    <span class="pinfo-label">Correo</span>
                    <span class="pinfo-val"><?= $email ?></span>
                </div>
                <div class="pinfo-row">
                    <span class="pinfo-label">Miembro desde</span>
                    <span class="pinfo-val"><?= $fecha ?></span>
                </div>
                <div class="pinfo-row">
                    <span class="pinfo-label">Ciudad</span>
                    <span class="pinfo-val">Ciudad Juárez</span>
                </div>
                <a class="btn-editar" href="editar.php">✏️ Editar perfil</a>
            </div>

            <div class="pcard">
                <div class="pcard-title">⭐ Cómo se ganan puntos</div>
                <div class="logro-item">
                    <div class="logro-icon">🚨</div>
                    <div class="logro-info">
                        <div class="logro-nombre">Incidente de seguridad</div>
                        <div class="logro-desc">Exige salir a verificar algo delicado</div>
                    </div>
                    <div class="logro-pts">15 pts</div>
                </div>
                <div class="logro-item">
                    <div class="logro-icon">🏚️</div>
                    <div class="logro-info">
                        <div class="logro-nombre">Condición del área</div>
                        <div class="logro-desc">Constatar un desperfecto del lugar</div>
                    </div>
                    <div class="logro-pts">10 pts</div>
                </div>
                <div class="logro-item">
                    <div class="logro-icon">💡</div>
                    <div class="logro-info">
                        <div class="logro-nombre">Sugerencia</div>
                        <div class="logro-desc">Una idea para mejorar el área</div>
                    </div>
                    <div class="logro-pts">5 pts</div>
                </div>
            </div>

        </div>

        <!-- Columna derecha: actividad -->
        <div class="perfil-main">

            <div class="perfil-tabs">
                <div class="perfil-tab active" onclick="cambiarTab(this,'reportes')">📋 Mis reportes</div>
                <div class="perfil-tab" onclick="cambiarTab(this,'favoritos')">❤️ Favoritos</div>
                <div class="perfil-tab" onclick="cambiarTab(this,'actividad')">📰 Actividad</div>
            </div>

            <!-- Tab: Mis reportes -->
            <div class="ptab-panel" id="tab-reportes">
                <?php if (empty($mis_reportes)): ?>
                <div class="pempty">
                    <div class="pempty-icon">📋</div>
                    <div class="pempty-text">Aún no has enviado reportes</div>
                    <div class="pempty-sub">Ayuda a la comunidad reportando áreas verdes</div>
                    <a class="btn-ir" href="../Reportar/index.php">Crear reporte</a>
                </div>
                <?php else:
                    // Cómo se ve cada estado. El borde de color a la izquierda
                    // permite reconocerlo sin leer la etiqueta.
                    $ESTADOS = [
                        'pendiente'  => ['⏳ Pendiente',  '#92400e', '#fef3c7', '#f59e0b'],
                        'en_proceso' => ['🔧 En proceso', '#1e40af', '#dbeafe', '#3b82f6'],
                        'resuelto'   => ['✅ Resuelto',   '#166534', '#dcfce7', '#22c55e'],
                    ];

                    // Cuántos ya atendió un administrador. Es el dato que cierra
                    // el ciclo del proyecto: reportar, atender, enterarse.
                    $resueltos = 0;
                    $en_proceso = 0;
                    foreach ($mis_reportes as $r) {
                        if (($r['estado'] ?? '') === 'resuelto')   $resueltos++;
                        if (($r['estado'] ?? '') === 'en_proceso') $en_proceso++;
                    }
                ?>

                <?php if ($resueltos > 0 || $en_proceso > 0): ?>
                <div style="background:#dcfce7;border-left:3px solid #22c55e;border-radius:0 10px 10px 0;
                            padding:12px 16px;margin-bottom:14px;font-size:0.86rem;color:#166534;">
                    <strong>
                        <?php if ($resueltos > 0): ?>
                            <?= $resueltos ?> de tus reportes ya <?= $resueltos === 1 ? 'fue atendido' : 'fueron atendidos' ?>
                        <?php else: ?>
                            <?= $en_proceso ?> de tus reportes <?= $en_proceso === 1 ? 'está' : 'están' ?> en proceso
                        <?php endif; ?>
                    </strong>
                    <?php if ($resueltos > 0 && $en_proceso > 0): ?>
                        <span style="opacity:.85;">y <?= $en_proceso ?> más <?= $en_proceso === 1 ? 'está' : 'están' ?> en proceso.</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div style="display:flex;flex-direction:column;gap:12px;">
                    <?php foreach ($mis_reportes as $r):
                        $tipo_label = ['incidente'=>'🚨 Incidente','condicion'=>'⚠️ Condición','sugerencia'=>'💡 Sugerencia'][$r['tipo']] ?? $r['tipo'];
                        $fecha_r = date('d/m/Y', strtotime($r['fecha']));
                        [$et_txt, $et_color, $et_fondo, $et_borde] = $ESTADOS[$r['estado'] ?? 'pendiente'] ?? $ESTADOS['pendiente'];
                    ?>
                    <div style="background:#f9fafb;border-radius:12px;padding:14px 16px;
                                border:1px solid #e5e7eb;border-left:4px solid <?= $et_borde ?>;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;gap:8px;">
                            <span style="font-size:0.8rem;font-weight:700;color:#1B4332;"><?= $tipo_label ?></span>
                            <span style="font-size:0.75rem;color:#9ca3af;"><?= $fecha_r ?></span>
                        </div>
                        <div style="font-size:0.85rem;color:#374151;margin-bottom:6px;"><?= htmlspecialchars($r['descripcion']) ?></div>
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;">
                            <?php if ($r['area']): ?>
                            <div style="font-size:0.75rem;color:#6b7280;">📍 <?= htmlspecialchars($r['area']) ?></div>
                            <?php else: ?><span></span><?php endif; ?>
                            <span style="font-size:0.72rem;font-weight:700;padding:3px 10px;border-radius:20px;
                                         color:<?= $et_color ?>;background:<?= $et_fondo ?>;"><?= $et_txt ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Tab: Favoritos -->
            <div class="ptab-panel" id="tab-favoritos" style="display:none;">
                <div id="favoritos-contenido">
                    <div style="text-align:center;padding:30px;color:var(--muted);font-size:13px;">Cargando favoritos...</div>
                </div>
            </div>

            <!-- Tab: Actividad -->
            <div class="ptab-panel" id="tab-actividad" style="display:none;">
                <?php if (empty($actividad)): ?>
                <div class="pempty">
                    <div class="pempty-icon">📰</div>
                    <div class="pempty-text">Sin actividad reciente</div>
                    <div class="pempty-sub">Tus reportes, comentarios y reacciones aparecerán aquí</div>
                    <a class="btn-ir" href="../Comunidad/index.php">Ver comunidad</a>
                </div>
                <?php else: ?>
                <div class="actividad-lista">
                    <?php foreach ($actividad as $act):
                        $tipo   = $act['tipo'];
                        $estilo = $estilo_actividad[$tipo] ?? ['bg' => '#f3f4f6', 'verbo' => 'Actividad en'];

                        // Las reacciones muestran el emoji que se usó; los reportes,
                        // el ícono de su categoría; el resto, uno fijo.
                        if ($tipo === 'reaccion') {
                            $icono = $act['subtipo'] ?: '👍';
                        } elseif ($tipo === 'reporte') {
                            $icono = $iconos_reporte[$act['subtipo']] ?? '📋';
                        } else {
                            $icono = $tipo === 'evento' ? '📅' : '💬';
                        }

                        $fecha_act = date('d/m/Y H:i', strtotime($act['fecha']));
                        $contexto  = $act['contexto'] ?? null;
                        $detalle   = trim((string)($act['detalle'] ?? ''));
                    ?>
                    <div class="act-item">
                        <div class="act-icono" style="background:<?= $estilo['bg'] ?>;"><?= $icono ?></div>
                        <div class="act-cuerpo">
                            <div class="act-titulo">
                                <?= $estilo['verbo'] ?>
                                <span class="act-area"><?= htmlspecialchars($contexto ?: 'la comunidad') ?></span>
                            </div>
                            <?php if ($detalle !== ''): ?>
                                <div class="act-detalle">"<?= htmlspecialchars(mb_substr($detalle, 0, 90)) ?><?= mb_strlen($detalle) > 90 ? '…' : '' ?>"</div>
                            <?php endif; ?>
                            <div class="act-fecha"><?= $fecha_act ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <div class="footer-bar">SafePark · Mi Perfil · Ciudad Juárez</div>

    <script>
        const ID_USUARIO = <?= $id ?>;
        const API_URL    = '<?= API_DATOS ?>';   // el JS solo usa el API de Datos
    </script>
    <script src="perfil.js"></script>
    <script>
    let favsCargados = false;

    const _tabOriginal = window.cambiarTab;
    window.cambiarTab = function(el, tab) {
        _tabOriginal(el, tab);
        if (tab === 'favoritos' && !favsCargados) cargarFavoritos();
    };

    function cargarFavoritos() {
        favsCargados = true;
        const el = document.getElementById('favoritos-contenido');

        fetch(API_URL + '/favoritos/' + ID_USUARIO)
        .then(r => r.json())
        .then(ids => {
            if (!ids.length) {
                el.innerHTML = `<div class="pempty">
                    <div class="pempty-icon">❤️</div>
                    <div class="pempty-text">Aún no tienes favoritos</div>
                    <div class="pempty-sub">Explora áreas verdes y guárdalas aquí</div>
                    <a class="btn-ir" href="../Explorar/index.php">Explorar áreas</a>
                </div>`;
                return;
            }
            return fetch(API_URL + '/areas')
            .then(r => r.json())
            .then(areas => {
                const favs = areas.filter(a => ids.includes(a.id));
                const iconos = { parque:'🌳', deportivo:'⚽', plaza:'🏛️' };
                el.innerHTML = `<div style="display:flex;flex-direction:column;gap:10px;">` +
                    favs.map(a => {
                        const sem = a.score >= 70 ? '#d8f3dc' : (a.score >= 40 ? '#fef3c7' : '#fee2e2');
                        const semTxt = a.score >= 70 ? '● Seguro' : (a.score >= 40 ? '⚠ Precaución' : '✕ Riesgo');
                        return `<div style="background:#f9fafb;border-radius:12px;padding:14px 16px;border:1px solid #e5e7eb;display:flex;align-items:center;gap:12px;cursor:pointer;" onclick="window.location.href='../Explorar/index.php'">
                            <div style="font-size:28px;">${iconos[a.tipo]||'🌿'}</div>
                            <div style="flex:1">
                                <div style="font-weight:800;font-size:14px;color:#1a1a1a;">${a.nombre}</div>
                                <div style="font-size:12px;color:#6b7280;">📍 ${a.colonia}</div>
                            </div>
                            <div style="background:${sem};padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;">${semTxt} · ${a.score}/100</div>
                        </div>`;
                    }).join('') + `</div>`;
            });
        })
        .catch(() => { el.innerHTML = '<div style="text-align:center;color:var(--muted);padding:20px;">Error al cargar favoritos.</div>'; });
    }
    </script>
</body>
</html>
