<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunidad - SafePark</title>
    <link rel="icon" href="../Assets/logo.png">
    <link rel="stylesheet" href="../CSS/styles.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php
require_once '../includes/auth.php';
require_once '../includes/api.php';
requiere_sesion();

// Las cinco consultas se lanzan juntas: en fila tardaban ~4s, así ~0.8s
$datos = api_get_multi([
    'reportes'   => '/reportes',
    'eventos'    => '/eventos',
    'usuarios'   => '/usuarios',
    'reacciones' => '/reacciones/agrupadas',
    'areas'      => '/areas',
]);

$reportes_api = $datos['reportes'];
$eventos_api  = $datos['eventos'];
$usuarios_api = $datos['usuarios'];

$total_usuarios = count($usuarios_api);
$total_reportes = count($reportes_api);
$total_eventos  = count($eventos_api);

// Mezcla reportes y eventos ordenados por fecha para el feed
$actividad = [];
foreach ($reportes_api as $r) {
    $actividad[] = ['tipo_actividad'=>'reporte','id_item'=>$r['id_reporte'],'id_evento'=>null,
        'subtipo'=>$r['tipo'],'detalle'=>$r['descripcion'],'fecha'=>$r['fecha'],
        'usuario'=>$r['usuario'],'area'=>$r['area'],'foto'=>$r['foto']??null];
}
foreach ($eventos_api as $e) {
    $actividad[] = ['tipo_actividad'=>'evento','id_item'=>null,'id_evento'=>$e['id_evento'],
        'subtipo'=>null,'detalle'=>$e['nombre'],'fecha'=>$e['fecha'].' '.$e['hora'],
        'usuario'=>$e['usuario'],'area'=>$e['area']];
}
usort($actividad, fn($a,$b) => strcmp($b['fecha'], $a['fecha']));
$actividad = array_slice($actividad, 0, 10);

// Reacciones agrupadas, indexadas por publicación para pintarlas en el feed
$reacciones_db = [];
foreach ($datos['reacciones'] as $row) {
    $key = $row['id_reporte'] ? 'r_'.$row['id_reporte'] : 'e_'.$row['id_evento'];
    $reacciones_db[$key][$row['emoji']] = (int)$row['total'];
}

$eventos_sidebar = array_slice($eventos_api, 0, 3);
$top_contribuidores = array_slice($usuarios_api, 0, 4);

// Áreas para el modal (solo necesitamos id y nombre)
$areas_modal_arr = $datos['areas'];

$labels_tipo = ['incidente' => 'reportó un incidente', 'condicion' => 'reportó la condición', 'sugerencia' => 'hizo una sugerencia'];

$exito = $_GET['exito'] ?? '';
$error = $_GET['error'] ?? '';

$nav_base   = '../';
$nav_active = 'comunidad';
require_once '../includes/navbar.php';
?>

    <div class="comunidad-hero">
        <div class="comunidad-hero-text">
            <h2>🌿 Comunidad SafePark</h2>
            <p>Juntos hacemos Ciudad Juárez más verde y segura</p>
        </div>
        <div class="comunidad-stats">
            <div class="cstat"><div class="cstat-n"><?= number_format($total_usuarios) ?></div><div class="cstat-l">Miembros</div></div>
            <div class="cstat"><div class="cstat-n"><?= number_format($total_reportes) ?></div><div class="cstat-l">Reportes</div></div>
            <div class="cstat"><div class="cstat-n"><?= $total_eventos ?></div><div class="cstat-l">Eventos</div></div>
        </div>
    </div>

    <!-- Layout principal -->
    <div class="comunidad-layout">

        <!-- Feed principal -->
        <div class="comunidad-main">

            <div class="feed-tabs">
                <div class="feed-tab active" onclick="cambiarTab(this,'actividad')">📰 Actividad reciente</div>
                <div class="feed-tab" onclick="cambiarTab(this,'eventos')">📅 Próximos eventos</div>
                <div class="feed-tab" onclick="cambiarTab(this,'logros')">🏆 Logros</div>
            </div>

            <!-- Actividad -->
            <div class="feed-panel" id="tab-actividad">
                <div class="feed-panel-header">
                    <span>Últimas acciones de la comunidad</span>
                    <button class="btn-crear-evento" onclick="document.getElementById('modal-evento').style.display='flex'">+ Crear evento</button>
                </div>

                <!-- Actividad real de la DB -->
                <?php
                $colores = ['#d8f3dc', '#fef3c7', '#dbeafe', '#ede9fe', '#fee2e2', '#e9f5ee'];
                $i = 0;
                foreach ($actividad as $r):
                    $color   = $colores[$i % count($colores)];
                    $inicial = strtoupper(mb_substr($r['usuario'], 0, 1));
                    $fecha   = date('d/m/Y H:i', strtotime($r['fecha']));
                    $i++;

                    if ($r['tipo_actividad'] === 'reporte'):
                        $accion = $labels_tipo[$r['subtipo']] ?? 'reportó algo';
                        $desc   = mb_substr($r['detalle'], 0, 80);
                        $emoji  = '📋';
                    else:
                        $accion = 'organizó un evento';
                        $desc   = $r['detalle'];
                        $emoji  = '📅';
                    endif;
                ?>
                <?php
                    $id_reporte_feed = ($r['tipo_actividad'] === 'reporte') ? (int)$r['id_item'] : null;
                    $id_evento_feed  = ($r['tipo_actividad'] === 'evento')  ? (int)$r['id_evento'] : null;
                    $key_feed = $id_reporte_feed ? 'r_'.$id_reporte_feed : 'e_'.$id_evento_feed;
                    $reacciones_item = $reacciones_db[$key_feed] ?? [];
                    $data_attr = 'data-id-reporte="'.($id_reporte_feed ?? '').'" data-id-evento="'.($id_evento_feed ?? '').'"';
                ?>
                <div class="feed-item">
                    <div class="feed-avatar" style="background:<?= $color ?>;color:var(--g1);font-weight:900;display:flex;align-items:center;justify-content:center;font-size:1rem;"><?= $inicial ?></div>
                    <div class="feed-content">
                        <div class="feed-user"><?= htmlspecialchars($r['usuario']) ?></div>
                        <div class="feed-action"><?= $emoji ?> <?= $accion ?> en <span class="feed-area"><?= htmlspecialchars($r['area']) ?></span> — "<?= htmlspecialchars($desc) ?><?= ($r['tipo_actividad']==='reporte' && strlen($r['detalle']) > 80) ? '...' : '' ?>"</div>
                        <?php if (!empty($r['foto'])): ?>
                            <img src="../Assets/fotos/<?= htmlspecialchars($r['foto']) ?>" alt="Foto del reporte" style="width:100%;max-height:180px;object-fit:cover;border-radius:10px;margin:8px 0;">
                        <?php endif; ?>
                        <div class="feed-time"><?= $fecha ?></div>
                        <div class="feed-reactions" <?= $data_attr ?>>
                            <?php foreach ($reacciones_item as $em => $total): ?>
                                <button class="reaction-btn" onclick="reaccionar(this,'<?= htmlspecialchars($em) ?>')" <?= $data_attr ?>><?= $em ?> <?= $total ?></button>
                            <?php endforeach; ?>
                            <button class="reaction-add" onclick="togglePicker(this)" <?= $data_attr ?>>+</button>
                            <button class="reaction-btn comment-toggle-btn" onclick="toggleComentarios(this)" <?= $data_attr ?>>💬 Comentarios</button>
                        </div>
                        <div class="comentarios-section" style="display:none;" <?= $data_attr ?>>
                            <div class="comentarios-lista"></div>
                            <div class="comentario-form">
                                <input type="text" class="comentario-input" placeholder="Escribe un comentario...">
                                <button class="comentario-send" onclick="enviarComentario(this)">Enviar</button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if ($i === 0): ?>
                <div style="text-align:center;padding:40px;color:var(--muted);">
                    <div style="font-size:2rem;">📰</div>
                    <div style="font-weight:700;margin-top:8px;">No hay actividad reciente</div>
                    <div style="font-size:0.85rem;margin-top:4px;">¡Sé el primero en reportar o crear un evento!</div>
                </div>
                <?php endif; ?>

            </div>

            <div class="feed-panel" id="tab-eventos" style="display:none;">
                <div class="feed-panel-header">
                    <span>Eventos programados en la ciudad</span>
                    <button class="btn-crear-evento" onclick="document.getElementById('modal-evento').style.display='flex'">+ Crear evento</button>
                </div>

                <?php if ($exito === 'evento'): ?>
                    <div class="msg-ok-feed">✅ Evento creado correctamente.</div>
                <?php elseif ($error === 'evento'): ?>
                    <div class="msg-err-feed">❌ Error al crear el evento, intenta de nuevo.</div>
                <?php endif; ?>

                <?php if (count($eventos_api) === 0): ?>
                    <div style="text-align:center;padding:40px;color:var(--muted);">
                        <div style="font-size:2rem;">📅</div>
                        <div style="font-weight:700;margin-top:8px;">No hay eventos próximos</div>
                        <div style="font-size:0.85rem;margin-top:4px;">¡Sé el primero en crear uno!</div>
                    </div>
                <?php else:
                    foreach ($eventos_api as $ev):
                        $dia = date('d', strtotime($ev['fecha']));
                        $mes = strtoupper(date('M', strtotime($ev['fecha'])));
                        $hora = date('h:i A', strtotime($ev['hora']));
                ?>
                    <?php
                        $key_ev = 'e_'.$ev['id_evento'];
                        $reacciones_ev = $reacciones_db[$key_ev] ?? [];
                        $data_ev = 'data-id-reporte="" data-id-evento="'.$ev['id_evento'].'"';
                    ?>
                    <div class="evento-card-big">
                        <div class="evento-fecha-big"><div class="evento-num"><?= $dia ?></div><div class="evento-mes"><?= $mes ?></div></div>
                        <div class="evento-info-big">
                            <div class="evento-nombre-big"><?= htmlspecialchars($ev['nombre']) ?></div>
                            <div class="evento-meta-big">📍 <?= htmlspecialchars($ev['area']) ?> · ⏰ <?= $hora ?></div>
                            <div class="evento-asistentes">👤 Organizado por <?= htmlspecialchars($ev['usuario']) ?></div>
                            <div class="feed-reactions" <?= $data_ev ?>>
                                <?php foreach ($reacciones_ev as $em => $total): ?>
                                    <button class="reaction-btn" onclick="reaccionar(this,'<?= htmlspecialchars($em) ?>')" <?= $data_ev ?>><?= $em ?> <?= $total ?></button>
                                <?php endforeach; ?>
                                <button class="reaction-add" onclick="togglePicker(this)" <?= $data_ev ?>>+</button>
                                <button class="reaction-btn comment-toggle-btn" onclick="toggleComentarios(this)" <?= $data_ev ?>>💬 Comentarios</button>
                            </div>
                            <div class="comentarios-section" style="display:none;" <?= $data_ev ?>>
                                <div class="comentarios-lista"></div>
                                <div class="comentario-form">
                                    <input type="text" class="comentario-input" placeholder="Escribe un comentario...">
                                    <button class="comentario-send" onclick="enviarComentario(this)">Enviar</button>
                                </div>
                            </div>
                        </div>
                        <button class="btn-asistir" onclick="this.textContent='✅ Confirmado'; this.classList.add('confirmado')">Asistir</button>
                    </div>
                <?php endforeach; endif; ?>
            </div>

            <!-- Tab: Logros -->
            <div class="feed-panel" id="tab-logros" style="display:none;">
                <div class="feed-panel-header">
                    <span>Logros desbloqueables por la comunidad</span>
                </div>
                <div class="logro-row">
                    <div class="logro-icon-big">🛡️</div>
                    <div class="logro-info"><div class="logro-nombre">Guardián del Parque</div><div class="logro-desc">Envía 10 reportes aprobados consecutivos</div></div>
                    <div class="logro-pts">+50 pts</div>
                </div>
                <div class="logro-row">
                    <div class="logro-icon-big">📸</div>
                    <div class="logro-info"><div class="logro-nombre">Fotógrafo Ciudadano</div><div class="logro-desc">Sube 20 fotos a la plataforma</div></div>
                    <div class="logro-pts">+30 pts</div>
                </div>
                <div class="logro-row">
                    <div class="logro-icon-big">🌱</div>
                    <div class="logro-info"><div class="logro-nombre">Primer reporte</div><div class="logro-desc">Envía tu primer reporte ciudadano</div></div>
                    <div class="logro-pts">+10 pts</div>
                </div>
                <div class="logro-row">
                    <div class="logro-icon-big">⭐</div>
                    <div class="logro-info"><div class="logro-nombre">Explorador</div><div class="logro-desc">Visita 5 áreas verdes distintas</div></div>
                    <div class="logro-pts">+20 pts</div>
                </div>
                <div class="logro-row">
                    <div class="logro-icon-big">🤝</div>
                    <div class="logro-info"><div class="logro-nombre">Organizador</div><div class="logro-desc">Crea tu primer evento comunitario</div></div>
                    <div class="logro-pts">+40 pts</div>
                </div>
            </div>

        </div>

        <!-- Sidebar -->
        <div class="comunidad-side">

            <div class="side-section-title">📅 Próximos eventos</div>
            <?php if (empty($eventos_sidebar)): ?>
                <div style="color:var(--muted);font-size:0.82rem;padding:8px 0;">Sin eventos próximos.</div>
            <?php else:
                foreach ($eventos_sidebar as $es):
                    $dia  = date('d', strtotime($es['fecha']));
                    $mes  = strtoupper(date('M', strtotime($es['fecha'])));
                    $hora = date('h:i A', strtotime($es['hora']));
            ?>
                <div class="evento-card">
                    <div class="evento-fecha"><div class="evento-num"><?= $dia ?></div><div class="evento-mes"><?= $mes ?></div></div>
                    <div class="evento-info">
                        <div class="evento-nombre"><?= htmlspecialchars($es['nombre']) ?></div>
                        <div class="evento-meta"><?= htmlspecialchars($es['area']) ?> · <?= $hora ?></div>
                    </div>
                </div>
            <?php endforeach; endif; ?>

            <div class="side-section-title" style="margin-top:24px;">🏆 Top contribuidores</div>
            <?php
            $medallas = ['🥇','🥈','🥉'];
            $colores_rank = ['#fef3c7', 'var(--g4)', 'var(--g3)', '#e9f5ee'];
            $pos = 0;
            foreach ($top_contribuidores as $top):
                $medalla = $medallas[$pos] ?? ($pos + 1);
                $color   = $colores_rank[$pos] ?? '#f3f4f6';
                $inicial = strtoupper(mb_substr($top['nombre'], 0, 1));
                $pos++;
            ?>
            <div class="ranking-item">
                <div class="ranking-num"><?= $medalla ?></div>
                <div class="ranking-avatar" style="background:<?= $color ?>;color:var(--g1);font-weight:900;font-size:13px;display:flex;align-items:center;justify-content:center;"><?= $inicial ?></div>
                <div class="ranking-name"><?= htmlspecialchars($top['nombre']) ?></div>
                <div class="ranking-count"><?= $top['total_reportes'] ?> reportes</div>
            </div>
            <?php endforeach; ?>

            <div class="side-section-title" style="margin-top:24px;">🏅 Logros disponibles</div>
            <div class="logro-item">
                <div class="logro-icon">🛡️</div>
                <div class="logro-info"><div class="logro-nombre">Guardián del Parque</div><div class="logro-desc">10 reportes aprobados</div></div>
                <div class="logro-pts">+50 pts</div>
            </div>
            <div class="logro-item">
                <div class="logro-icon">🌱</div>
                <div class="logro-info"><div class="logro-nombre">Primer reporte</div><div class="logro-desc">Enviar tu primer reporte</div></div>
                <div class="logro-pts">+10 pts</div>
            </div>
            <div class="logro-item">
                <div class="logro-icon">🤝</div>
                <div class="logro-info"><div class="logro-nombre">Organizador</div><div class="logro-desc">Crea tu primer evento</div></div>
                <div class="logro-pts">+40 pts</div>
            </div>

        </div>
    </div>

    <div class="footer-bar">SafePark · Comunidad · Ciudad Juárez</div>

    <!-- Modal: Crear evento -->
    <div class="modal-overlay" id="modal-evento" style="display:none;" onclick="if(event.target===this)this.style.display='none'">
        <div class="modal-card">
            <div class="modal-title">📅 Crear evento</div>
            <form action="guardar_evento.php" method="POST">

                <div class="form-group">
                    <label class="modal-label">Nombre del evento</label>
                    <input class="modal-input" type="text" name="nombre" placeholder="Ej. Caminata matutina..." autocomplete="off" required>
                </div>

                <div class="form-group">
                    <label class="modal-label">Área o zona</label>
                    <select class="modal-input" name="id_area" required>
                        <option value="">Selecciona un área...</option>
                        <?php foreach ($areas_modal_arr as $a): ?>
                            <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="modal-row">
                    <div class="form-group" style="flex:1">
                        <label class="modal-label">Fecha</label>
                        <input class="modal-input" type="date" name="fecha" required>
                    </div>
                    <div class="form-group" style="flex:1">
                        <label class="modal-label">Hora</label>
                        <input class="modal-input" type="time" name="hora" required>
                    </div>
                </div>

                <div class="modal-btns">
                    <button type="button" class="btn-cancelar-modal" onclick="document.getElementById('modal-evento').style.display='none'">Cancelar</button>
                    <button type="submit" class="btn-guardar-modal">Crear evento</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/emoji-mart@5.6.0/dist/browser.js"></script>
    <script>
        const ID_USUARIO = <?= $_SESSION['id_usuario'] ?>;
        const API_URL    = '<?= API_DATOS ?>';   // el JS solo usa el API de Datos
    </script>
    <script src="comunidad.js"></script>
</body>
</html>
