// ============================================================
//  Favoritos — áreas que cada usuario guardó para volver luego
//
//  La tabla FAVORITO solo relaciona un usuario con un área; su clave primaria
//  es la pareja (id_usuario, id_area), así que no puede repetirse.
// ============================================================

const router = require('express').Router();
const db     = require('../db');

// GET /api/favoritos/:id_usuario
// Devuelve solo los IDs de las áreas favoritas, no las áreas completas.
// Quien llama ya tiene la lista de áreas y solo necesita saber cuáles marcar.
router.get('/:id_usuario', async (req, res) => {
    const [filas] = await db.query(
        'SELECT id_area FROM FAVORITO WHERE id_usuario = ?',
        [req.params.id_usuario]
    );
    // De [{id_area: 3}, {id_area: 7}] a [3, 7]
    res.json(filas.map(f => f.id_area));
});

// POST /api/favoritos — pone o quita el favorito según cómo esté
//
// Funciona como interruptor: el mismo botón sirve para guardar y para quitar.
// No lleva protección por secreto porque el JavaScript del navegador lo llama
// directo, y marcar un favorito no tiene consecuencias graves.
router.post('/', async (req, res) => {
    const { id_usuario, id_area } = req.body;

    if (!id_usuario || !id_area) return res.status(400).json({ ok: false });

    const [existe] = await db.query(
        'SELECT 1 FROM FAVORITO WHERE id_usuario = ? AND id_area = ?',
        [id_usuario, id_area]
    );

    if (existe.length) {
        // Ya estaba guardada: se quita
        await db.query('DELETE FROM FAVORITO WHERE id_usuario = ? AND id_area = ?',
                       [id_usuario, id_area]);
        res.json({ ok: true, accion: 'removed' });
    } else {
        // No estaba: se guarda
        await db.query('INSERT INTO FAVORITO (id_usuario, id_area) VALUES (?,?)',
                       [id_usuario, id_area]);
        res.json({ ok: true, accion: 'added' });
    }
});

module.exports = router;
