const router = require('express').Router();
const db = require('../db');

// GET /api/comentarios?id_reporte=X o ?id_evento=X
router.get('/', async (req, res) => {
    const { id_reporte, id_evento } = req.query;
    const [rows] = await db.query(`
        SELECT C.texto, C.fecha, U.nombre
        FROM COMENTARIO C
        JOIN USUARIO U ON C.id_usuario = U.id_usuario
        WHERE C.id_reporte <=> ? AND C.id_evento <=> ?
        ORDER BY C.fecha ASC
    `, [id_reporte || null, id_evento || null]);
    res.json(rows);
});

// POST /api/comentarios — agregar comentario
router.post('/', async (req, res) => {
    const { id_usuario, texto, id_reporte, id_evento } = req.body;
    if (!id_usuario || !texto || (!id_reporte && !id_evento)) {
        return res.status(400).json({ ok: false });
    }

    const [result] = await db.query(
        'INSERT INTO COMENTARIO (id_usuario, texto, id_reporte, id_evento) VALUES (?,?,?,?)',
        [id_usuario, texto, id_reporte || null, id_evento || null]
    );

    const [[comentario]] = await db.query(`
        SELECT C.texto, C.fecha, U.nombre
        FROM COMENTARIO C JOIN USUARIO U ON C.id_usuario = U.id_usuario
        WHERE C.id_comentario = ?
    `, [result.insertId]);

    res.json({ ok: true, comentario });
});

module.exports = router;
