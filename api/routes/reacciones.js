const router = require('express').Router();
const db = require('../db');

// GET /api/reacciones?id_reporte=X o ?id_evento=X
router.get('/', async (req, res) => {
    const { id_reporte, id_evento } = req.query;
    const [rows] = await db.query(`
        SELECT emoji, COUNT(*) AS total
        FROM REACCION
        WHERE id_reporte <=> ? AND id_evento <=> ?
        GROUP BY emoji
    `, [id_reporte || null, id_evento || null]);
    res.json(rows);
});

// POST /api/reacciones — toggle reacción
router.post('/', async (req, res) => {
    const { id_usuario, emoji, id_reporte, id_evento } = req.body;
    if (!id_usuario || !emoji || (!id_reporte && !id_evento)) {
        return res.status(400).json({ ok: false });
    }

    const r = id_reporte || null;
    const e = id_evento  || null;

    const [existe] = await db.query(
        'SELECT id_reaccion FROM REACCION WHERE id_usuario=? AND emoji=? AND id_reporte<=>? AND id_evento<=>?',
        [id_usuario, emoji, r, e]
    );

    if (existe.length) {
        await db.query('DELETE FROM REACCION WHERE id_reaccion=?', [existe[0].id_reaccion]);
    } else {
        await db.query(
            'INSERT INTO REACCION (id_usuario, emoji, id_reporte, id_evento) VALUES (?,?,?,?)',
            [id_usuario, emoji, r, e]
        );
    }

    const [[{ total }]] = await db.query(
        'SELECT COUNT(*) AS total FROM REACCION WHERE emoji=? AND id_reporte<=>? AND id_evento<=>?',
        [emoji, r, e]
    );

    res.json({ ok: true, accion: existe.length ? 'removed' : 'added', total });
});

module.exports = router;
