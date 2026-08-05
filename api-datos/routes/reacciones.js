// ============================================================
//  Reacciones con emoji, estilo Discord
//
//  Igual que los comentarios, una reacción cuelga de un reporte O de un evento,
//  y la columna que no aplica queda en NULL. De ahí el operador <=>, que en
//  MySQL sí considera iguales dos NULL (con "=" nunca coincidirían).
//
//  Dato importante de la base: la columna emoji usa la colación utf8mb4_bin,
//  que compara byte a byte. Con la colación normal (utf8mb4_general_ci) MySQL
//  considera que TODOS los emojis son el mismo carácter, y las consultas de
//  abajo dejarían de distinguir uno de otro.
// ============================================================

const router = require('express').Router();
const db     = require('../db');

// GET /api/reacciones?id_reporte=X  o  ?id_evento=X
// Cuántas veces se usó cada emoji en una publicación concreta.
router.get('/', async (req, res) => {
    const { id_reporte, id_evento } = req.query;

    const [filas] = await db.query(`
        SELECT emoji, COUNT(*) AS total
        FROM REACCION
        WHERE id_reporte <=> ? AND id_evento <=> ?
        GROUP BY emoji
    `, [id_reporte || null, id_evento || null]);

    res.json(filas);
});

// GET /api/reacciones/agrupadas — el resumen de TODAS las publicaciones
//
// Comunidad pinta el feed desde el servidor y necesita las reacciones de las
// diez publicaciones a la vez. Pedirlas una por una serían diez llamadas; así
// es una sola.
router.get('/agrupadas', async (req, res) => {
    const [filas] = await db.query(`
        SELECT id_reporte, id_evento, emoji, COUNT(*) AS total
        FROM REACCION
        GROUP BY id_reporte, id_evento, emoji
    `);
    res.json(filas);
});

// POST /api/reacciones — pone o quita la reacción según cómo esté
//
// Funciona como interruptor por usuario y emoji: si ya reaccionaste con 🔥 y
// vuelves a tocarlo, se quita. Pero puedes tener varios emojis distintos en la
// misma publicación, porque cada uno es una fila aparte.
router.post('/', async (req, res) => {
    const { id_usuario, emoji, id_reporte, id_evento } = req.body;

    if (!id_usuario || !emoji || (!id_reporte && !id_evento)) {
        return res.status(400).json({ ok: false });
    }

    // Se normalizan a NULL para que el operador <=> funcione correctamente
    const rep = id_reporte || null;
    const eve = id_evento  || null;

    // ¿Este usuario ya había reaccionado con ESTE emoji a ESTA publicación?
    const [existe] = await db.query(
        `SELECT id_reaccion FROM REACCION
         WHERE id_usuario = ? AND emoji = ? AND id_reporte <=> ? AND id_evento <=> ?`,
        [id_usuario, emoji, rep, eve]
    );

    if (existe.length) {
        await db.query('DELETE FROM REACCION WHERE id_reaccion = ?', [existe[0].id_reaccion]);
    } else {
        await db.query(
            'INSERT INTO REACCION (id_usuario, emoji, id_reporte, id_evento) VALUES (?,?,?,?)',
            [id_usuario, emoji, rep, eve]
        );
    }

    // Se recuenta después de cambiar, para que la página muestre el número real
    // sin tener que recargar
    const [[{ total }]] = await db.query(
        `SELECT COUNT(*) AS total FROM REACCION
         WHERE emoji = ? AND id_reporte <=> ? AND id_evento <=> ?`,
        [emoji, rep, eve]
    );

    res.json({ ok: true, accion: existe.length ? 'removed' : 'added', total });
});

module.exports = router;
