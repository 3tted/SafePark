const router = require('express').Router();
const db = require('../db');

// GET /api/favoritos/:id_usuario — áreas favoritas del usuario
router.get('/:id_usuario', async (req, res) => {
    const [rows] = await db.query(
        'SELECT id_area FROM FAVORITO WHERE id_usuario = ?',
        [req.params.id_usuario]
    );
    res.json(rows.map(r => r.id_area));
});

// POST /api/favoritos — toggle favorito
router.post('/', async (req, res) => {
    const { id_usuario, id_area } = req.body;
    if (!id_usuario || !id_area) return res.status(400).json({ ok: false });

    const [existe] = await db.query(
        'SELECT 1 FROM FAVORITO WHERE id_usuario = ? AND id_area = ?',
        [id_usuario, id_area]
    );

    if (existe.length) {
        await db.query('DELETE FROM FAVORITO WHERE id_usuario = ? AND id_area = ?', [id_usuario, id_area]);
        res.json({ ok: true, accion: 'removed' });
    } else {
        await db.query('INSERT INTO FAVORITO (id_usuario, id_area) VALUES (?,?)', [id_usuario, id_area]);
        res.json({ ok: true, accion: 'added' });
    }
});

module.exports = router;
