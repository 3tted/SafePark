// ============================================================
//  Comentarios del feed de Comunidad
//
//  Un comentario cuelga de un reporte O de un evento, nunca de los dos: la
//  columna que no aplica queda en NULL. Por eso las consultas comparan con
//  <=> en vez de = — ese operador de MySQL sí considera iguales dos NULL,
//  mientras que "NULL = NULL" da NULL (ni verdadero ni falso) y no encontraría
//  nada.
// ============================================================

const router = require('express').Router();
const db     = require('../db');

// GET /api/comentarios?id_reporte=X  o  ?id_evento=X
// Devuelve los comentarios de una publicación, del más viejo al más nuevo,
// que es el orden natural de una conversación.
router.get('/', async (req, res) => {
    const { id_reporte, id_evento } = req.query;

    const [filas] = await db.query(`
        SELECT C.texto, C.fecha, U.nombre
        FROM COMENTARIO C
        JOIN USUARIO U ON C.id_usuario = U.id_usuario
        WHERE C.id_reporte <=> ? AND C.id_evento <=> ?
        ORDER BY C.fecha ASC
    `, [id_reporte || null, id_evento || null]);

    res.json(filas);
});

// POST /api/comentarios — publicar un comentario
//
// Sin protección por secreto: el JavaScript del navegador lo llama directo al
// enviar. Si se protegiera, el secreto tendría que ir escrito en la página y
// cualquiera podría leerlo, así que no aportaría seguridad real.
router.post('/', async (req, res) => {
    const { id_usuario, texto, id_reporte, id_evento } = req.body;

    // Tiene que venir de alguien, decir algo, y pertenecer a un reporte o evento
    if (!id_usuario || !texto || (!id_reporte && !id_evento)) {
        return res.status(400).json({ ok: false });
    }

    const [resultado] = await db.query(
        'INSERT INTO COMENTARIO (id_usuario, texto, id_reporte, id_evento) VALUES (?,?,?,?)',
        [id_usuario, texto, id_reporte || null, id_evento || null]
    );

    // Se relee el comentario recién guardado para devolverlo con el nombre del
    // autor y la fecha que puso la base. Así la página lo pinta de inmediato
    // sin recargar ni inventarse esos datos.
    const [[comentario]] = await db.query(`
        SELECT C.texto, C.fecha, U.nombre
        FROM COMENTARIO C
        JOIN USUARIO U ON C.id_usuario = U.id_usuario
        WHERE C.id_comentario = ?
    `, [resultado.insertId]);

    res.json({ ok: true, comentario });
});

module.exports = router;
