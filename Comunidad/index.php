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
session_start();
require_once '../database/conexion.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../Login/index.php');
    exit;
}

// Stats reales
$total_usuarios = $conn->query("SELECT COUNT(*) FROM USUARIO")->fetch_row()[0];
$total_reportes = $conn->query("SELECT COUNT(*) FROM REPORTE")->fetch_row()[0];
$total_eventos  = $conn->query("SELECT COUNT(*) FROM EVENTO")->fetch_row()[0];

// Actividad reciente: reportes y eventos mezclados por fecha
$actividad_db = $conn->query("
    SELECT 'reporte' AS tipo_actividad, R.tipo AS subtipo, R.descripcion AS detalle, R.fecha, U.nombre AS usuario, A.nombre AS area
    FROM REPORTE R
    JOIN USUARIO U ON R.id_usuario = U.id_usuario
    JOIN AREA A ON R.id_area = A.id_area
    UNION ALL
    SELECT 'evento' AS tipo_actividad, NULL AS subtipo, E.nombre AS detalle,
           CONCAT(E.fecha, ' ', E.hora) AS fecha, U.nombre AS usuario, A.nombre AS area
    FROM EVENTO E
    JOIN USUARIO U ON E.id_usuario = U.id_usuario
    JOIN AREA A ON E.id_area = A.id_area
    ORDER BY fecha DESC
    LIMIT 10
");

// Eventos de la DB
$eventos_db = $conn->query("
    SELECT E.id_evento, E.nombre, E.fecha, E.hora, U.nombre AS usuario, A.nombre AS area
    FROM EVENTO E
    JOIN USUARIO U ON E.id_usuario = U.id_usuario
    JOIN AREA A ON E.id_area = A.id_area
    ORDER BY E.fecha ASC
");

// Áreas para el modal
$areas_modal = $conn->query("SELECT id_area, nombre FROM AREA ORDER BY nombre");

// Eventos para sidebar (separado para no consumir el mismo resultado)
$eventos_sidebar = $conn->query("
    SELECT E.nombre, E.fecha, E.hora, A.nombre AS area
    FROM EVENTO E
    JOIN AREA A ON E.id_area = A.id_area
    ORDER BY E.fecha ASC
    LIMIT 3
");

// Top contribuidores
$top_contribuidores = $conn->query("
    SELECT U.nombre, COUNT(R.id_reporte) AS total
    FROM USUARIO U
    LEFT JOIN REPORTE R ON U.id_usuario = R.id_usuario
    GROUP BY U.id_usuario, U.nombre
    ORDER BY total DESC
    LIMIT 4
");

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
                while ($r = $actividad_db->fetch_assoc()):
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
                <div class="feed-item">
                    <div class="feed-avatar" style="background:<?= $color ?>;color:var(--g1);font-weight:900;display:flex;align-items:center;justify-content:center;font-size:1rem;"><?= $inicial ?></div>
                    <div class="feed-content">
                        <div class="feed-user"><?= htmlspecialchars($r['usuario']) ?></div>
                        <div class="feed-action"><?= $emoji ?> <?= $accion ?> en <span class="feed-area"><?= htmlspecialchars($r['area']) ?></span> — "<?= htmlspecialchars($desc) ?><?= ($r['tipo_actividad']==='reporte' && strlen($r['detalle']) > 80) ? '...' : '' ?>"</div>
                        <div class="feed-time"><?= $fecha ?></div>
                        <div class="feed-reactions">
                            <button class="reaction-btn" onclick="reaccionar(this)">👍 0</button>
                            <button class="reaction-btn">💬 0</button>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>

                <!-- 3 items hardcodeados -->
                <div class="feed-item">
                    <div class="feed-avatar" style="background:var(--g4);"></div>
                    <div class="feed-content">
                        <div class="feed-user">maria_cj</div>
                        <div class="feed-action">organizó un evento en <span class="feed-area">Parque Central</span> — "Limpieza comunitaria sábado 7am"</div>
                        <div class="feed-time">Hace 1 hora</div>
                        <div class="feed-reactions">
                            <button class="reaction-btn" onclick="reaccionar(this)">❤️ 38</button>
                            <button class="reaction-btn">💬 11</button>
                            <button class="reaction-btn reaction-active">✅ Asistiré</button>
                        </div>
                    </div>
                </div>

                <div class="feed-item">
                    <div class="feed-avatar" style="background:#fef3c7;"></div>
                    <div class="feed-content">
                        <div class="feed-user">roberto_dev</div>
                        <div class="feed-action">marcó como favorito <span class="feed-area">Área Deportiva Norte</span> y dejó una reseña ★★★★★</div>
                        <div class="feed-time">Hace 2 horas</div>
                        <div class="feed-reactions">
                            <button class="reaction-btn" onclick="reaccionar(this)">👍 7</button>
                            <button class="reaction-btn">💬 1</button>
                        </div>
                    </div>
                </div>

                <div class="feed-item">
                    <div class="feed-avatar" style="background:#e9f5ee;"></div>
                    <div class="feed-content">
                        <div class="feed-user">ivan_tech</div>
                        <div class="feed-action">desbloqueó el logro <strong>🏅 Guardián del Parque</strong> — 10 reportes aprobados consecutivos</div>
                        <div class="feed-time">Hace 5 horas</div>
                        <div class="feed-reactions">
                            <button class="reaction-btn" onclick="reaccionar(this)">🎉 52</button>
                            <button class="reaction-btn">💬 8</button>
                        </div>
                    </div>
                </div>

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

                <?php if ($eventos_db->num_rows === 0): ?>
                    <div style="text-align:center;padding:40px;color:var(--muted);">
                        <div style="font-size:2rem;">📅</div>
                        <div style="font-weight:700;margin-top:8px;">No hay eventos próximos</div>
                        <div style="font-size:0.85rem;margin-top:4px;">¡Sé el primero en crear uno!</div>
                    </div>
                <?php else:
                    while ($ev = $eventos_db->fetch_assoc()):
                        $dia = date('d', strtotime($ev['fecha']));
                        $mes = strtoupper(date('M', strtotime($ev['fecha'])));
                        $hora = date('h:i A', strtotime($ev['hora']));
                ?>
                    <div class="evento-card-big">
                        <div class="evento-fecha-big"><div class="evento-num"><?= $dia ?></div><div class="evento-mes"><?= $mes ?></div></div>
                        <div class="evento-info-big">
                            <div class="evento-nombre-big"><?= htmlspecialchars($ev['nombre']) ?></div>
                            <div class="evento-meta-big">📍 <?= htmlspecialchars($ev['area']) ?> · ⏰ <?= $hora ?></div>
                            <div class="evento-asistentes">👤 Organizado por <?= htmlspecialchars($ev['usuario']) ?></div>
                        </div>
                        <button class="btn-asistir" onclick="this.textContent='✅ Confirmado'; this.classList.add('confirmado')">Asistir</button>
                    </div>
                <?php endwhile; endif; ?>
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
            <?php if ($eventos_sidebar->num_rows === 0): ?>
                <div style="color:var(--muted);font-size:0.82rem;padding:8px 0;">Sin eventos próximos.</div>
            <?php else:
                while ($es = $eventos_sidebar->fetch_assoc()):
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
            <?php endwhile; endif; ?>

            <div class="side-section-title" style="margin-top:24px;">🏆 Top contribuidores</div>
            <?php
            $medallas = ['🥇','🥈','🥉'];
            $colores_rank = ['#fef3c7', 'var(--g4)', 'var(--g3)', '#e9f5ee'];
            $pos = 0;
            while ($top = $top_contribuidores->fetch_assoc()):
                $medalla = $medallas[$pos] ?? ($pos + 1);
                $color   = $colores_rank[$pos] ?? '#f3f4f6';
                $inicial = strtoupper(mb_substr($top['nombre'], 0, 1));
                $pos++;
            ?>
            <div class="ranking-item">
                <div class="ranking-num"><?= $medalla ?></div>
                <div class="ranking-avatar" style="background:<?= $color ?>;color:var(--g1);font-weight:900;font-size:13px;display:flex;align-items:center;justify-content:center;"><?= $inicial ?></div>
                <div class="ranking-name"><?= htmlspecialchars($top['nombre']) ?></div>
                <div class="ranking-count"><?= $top['total'] ?> reportes</div>
            </div>
            <?php endwhile; ?>

            <div class="side-section-title" style="margin-top:24px;">🏅 Logros recientes</div>
            <div class="logro-item">
                <div class="logro-icon">🛡️</div>
                <div class="logro-info"><div class="logro-nombre">Guardián del Parque</div><div class="logro-desc">10 reportes aprobados</div></div>
                <div class="logro-pts">+50 pts</div>
            </div>
            <div class="logro-item">
                <div class="logro-icon">📸</div>
                <div class="logro-info"><div class="logro-nombre">Fotógrafo Ciudadano</div><div class="logro-desc">Subir 20 fotos</div></div>
                <div class="logro-pts">+30 pts</div>
            </div>
            <div class="logro-item">
                <div class="logro-icon">🌱</div>
                <div class="logro-info"><div class="logro-nombre">Primer reporte</div><div class="logro-desc">Enviar tu primer reporte</div></div>
                <div class="logro-pts">+10 pts</div>
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
                    <input class="modal-input" type="text" name="nombre" placeholder="Ej. Caminata matutina..." required>
                </div>

                <div class="form-group">
                    <label class="modal-label">Área o zona</label>
                    <select class="modal-input" name="id_area" required>
                        <option value="">Selecciona un área...</option>
                        <?php while ($a = $areas_modal->fetch_assoc()): ?>
                            <option value="<?= $a['id_area'] ?>"><?= htmlspecialchars($a['nombre']) ?></option>
                        <?php endwhile; ?>
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

    <script src="comunidad.js"></script>
</body>
</html>
