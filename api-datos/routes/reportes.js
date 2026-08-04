// ============================================================
//  Reportes ciudadanos
//
//  Es el corazón de SafePark: los vecinos reportan lo que ven en un área y esos
//  reportes alimentan el semáforo de seguridad (que se calcula en areas.js).
//
//  Cada reporte es de uno de tres tipos, y pesan distinto en el semáforo:
//    incidente  — algo de seguridad (robo, pelea). Es el que más baja el score.
//    condicion  — el estado del lugar (basura, luminaria rota)
//    sugerencia — una propuesta de mejora. Casi no afecta el score.
//
//  Y pasa por tres estados que el administrador va cambiando:
//  pendiente → en_proceso → resuelto
// ============================================================

const router  = require('express').Router();
const db      = require('../db');
const soloPHP = require('../middleware/solo_php');

// GET /api/reportes — los más recientes de toda la plataforma
//
// Alimenta el feed de Comunidad y la tabla del panel de Admin. Se limita a 50
// para que la página no crezca sin control conforme se acumulen reportes.
router.get('/', async (req, res) => {
    const [filas] = await db.query(`
        SELECT R.id_reporte, R.tipo, R.descripcion, R.estado, R.fecha, R.id_area, R.foto,
               U.nombre AS usuario,   -- quién lo reportó
               A.nombre AS area       -- de qué lugar habla
        FROM REPORTE R
        JOIN USUARIO U ON R.id_usuario = U.id_usuario
        JOIN AREA    A ON R.id_area    = A.id_area
        ORDER BY R.fecha DESC
        LIMIT 50
    `);
    res.json(filas);
});

// GET /api/reportes/area/:id — los reportes de un área concreta
// Lo usa el modal de Explorar al tocar una tarjeta.
router.get('/area/:id', async (req, res) => {
    const [filas] = await db.query(`
        SELECT R.id_reporte, R.tipo, R.descripcion, R.fecha, R.foto,
               U.nombre AS usuario
        FROM REPORTE R
        JOIN USUARIO U ON R.id_usuario = U.id_usuario
        WHERE R.id_area = ?
        ORDER BY R.fecha DESC
    `, [req.params.id]);
    res.json(filas);
});

// GET /api/reportes/usuario/:id — el historial de una persona
// Lo usan la pestaña "Mis reportes" del perfil y el panel lateral de Reportar.
router.get('/usuario/:id', async (req, res) => {
    const [filas] = await db.query(`
        SELECT R.id_reporte, R.tipo, R.descripcion, R.fecha, R.estado, R.foto,
               A.nombre AS area
        FROM REPORTE R
        JOIN AREA A ON R.id_area = A.id_area
        WHERE R.id_usuario = ?
        ORDER BY R.fecha DESC
    `, [req.params.id]);
    res.json(filas);
});

// PUT /api/reportes/:id — cambiar el estado (solo administradores)
//
// El estado se valida contra una lista fija en vez de confiar en lo que llegue:
// la columna es un ENUM y un valor fuera de esos tres lo rechazaría MySQL con
// un error feo. Mejor devolver un mensaje claro.
router.put('/:id', soloPHP, async (req, res) => {
    const { estado } = req.body;
    const validos = ['pendiente', 'en_proceso', 'resuelto'];

    if (!validos.includes(estado)) {
        return res.status(400).json({ ok: false, error: 'Estado inválido' });
    }

    try {
        await db.query('UPDATE REPORTE SET estado = ? WHERE id_reporte = ?',
                       [estado, req.params.id]);
        res.json({ ok: true });
    } catch (err) {
        console.error('PUT /reportes error:', err.message);
        res.status(500).json({ ok: false, error: 'Error del servidor' });
    }
});

// POST /api/reportes — enviar un reporte nuevo
//
// La foto llega como nombre de archivo, no como imagen: PHP ya la guardó en
// Assets/fotos/ antes de llamar aquí. Se hace así para que la imagen quede en
// el mismo servidor que la muestra — el API vive en otro host y su disco se
// borra en cada despliegue.
router.post('/', soloPHP, async (req, res) => {
    const { id_usuario, id_area, tipo, descripcion, foto } = req.body;
    const tipos_validos = ['incidente', 'condicion', 'sugerencia'];

    if (!id_usuario || !id_area || !tipos_validos.includes(tipo) || !descripcion?.trim()) {
        return res.status(400).json({ ok: false, error: 'Datos inválidos' });
    }

    try {
        // Todo reporte nace como 'pendiente'; el admin lo mueve desde su panel
        await db.query(
            `INSERT INTO REPORTE (id_usuario, id_area, tipo, descripcion, estado, foto)
             VALUES (?,?,?,?,?,?)`,
            [id_usuario, id_area, tipo, descripcion.trim(), 'pendiente', foto || null]
        );
        res.json({ ok: true });
    } catch (err) {
        console.error('POST /reportes error:', err.message);
        res.status(500).json({ ok: false, error: 'Error del servidor' });
    }
});

module.exports = router;
