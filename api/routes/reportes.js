const router = require('express').Router();
const db = require('../db');

// GET /api/reportes — todos los reportes recientes
router.get('/', async (req, res) => {
    const [rows] = await db.query(`
        SELECT R.id_reporte, R.tipo, R.descripcion, R.estado, R.fecha, R.id_area, R.foto,
               U.nombre AS usuario, A.nombre AS area
        FROM REPORTE R
        JOIN USUARIO U ON R.id_usuario = U.id_usuario
        JOIN AREA A ON R.id_area = A.id_area
        ORDER BY R.fecha DESC
        LIMIT 50
    `);
    res.json(rows);
});

// GET /api/reportes/area/:id — reportes de un área específica
router.get('/area/:id', async (req, res) => {
    const [rows] = await db.query(`
        SELECT R.id_reporte, R.tipo, R.descripcion, R.fecha,
               U.nombre AS usuario
        FROM REPORTE R
        JOIN USUARIO U ON R.id_usuario = U.id_usuario
        WHERE R.id_area = ?
        ORDER BY R.fecha DESC
    `, [req.params.id]);
    res.json(rows);
});

// GET /api/reportes/usuario/:id — reportes de un usuario
router.get('/usuario/:id', async (req, res) => {
    const [rows] = await db.query(`
        SELECT R.id_reporte, R.tipo, R.descripcion, R.fecha, R.estado, R.foto, A.nombre AS area
        FROM REPORTE R
        JOIN AREA A ON R.id_area = A.id_area
        WHERE R.id_usuario = ?
        ORDER BY R.fecha DESC
    `, [req.params.id]);
    res.json(rows);
});

// PUT /api/reportes/:id — actualizar estado
router.put('/:id', async (req, res) => {
    const { estado } = req.body;
    const validos = ['pendiente', 'en_proceso', 'resuelto'];
    if (!validos.includes(estado)) return res.status(400).json({ ok: false, error: 'Estado inválido' });
    try {
        await db.query('UPDATE REPORTE SET estado = ? WHERE id_reporte = ?', [estado, req.params.id]);
        res.json({ ok: true });
    } catch (err) {
        res.status(500).json({ ok: false, error: err.message });
    }
});

// POST /api/reportes — crear reporte
router.post('/', async (req, res) => {
    const { id_usuario, id_area, tipo, descripcion, foto } = req.body;
    const tipos_validos = ['incidente', 'condicion', 'sugerencia'];

    if (!id_usuario || !id_area || !tipos_validos.includes(tipo) || !descripcion?.trim()) {
        return res.status(400).json({ ok: false, error: 'Datos inválidos' });
    }

    try {
        await db.query(
            'INSERT INTO REPORTE (id_usuario, id_area, tipo, descripcion, estado, foto) VALUES (?,?,?,?,?,?)',
            [id_usuario, id_area, tipo, descripcion.trim(), 'pendiente', foto || null]
        );
        res.json({ ok: true });
    } catch (err) {
        console.error('POST /reportes error:', err.message);
        res.status(500).json({ ok: false, error: err.message });
    }
});

module.exports = router;
