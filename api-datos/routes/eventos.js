const router  = require('express').Router();
const db      = require('../db');
const soloPHP = require('../middleware/solo_php');

// GET /api/eventos
router.get('/', async (req, res) => {
    const [rows] = await db.query(`
        SELECT E.id_evento, E.nombre, E.fecha, E.hora,
               U.nombre AS usuario, A.nombre AS area
        FROM EVENTO E
        JOIN USUARIO U ON E.id_usuario = U.id_usuario
        JOIN AREA A ON E.id_area = A.id_area
        ORDER BY E.fecha ASC
    `);
    res.json(rows);
});

// POST /api/eventos — crear evento
router.post('/', soloPHP, async (req, res) => {
    const { id_usuario, id_area, nombre, fecha, hora } = req.body;

    if (!id_usuario || !id_area || !nombre?.trim() || !fecha || !hora) {
        return res.status(400).json({ ok: false, error: 'Datos inválidos' });
    }

    await db.query(
        'INSERT INTO EVENTO (id_usuario, id_area, nombre, fecha, hora) VALUES (?,?,?,?,?)',
        [id_usuario, id_area, nombre.trim(), fecha, hora]
    );
    res.json({ ok: true });
});

module.exports = router;
