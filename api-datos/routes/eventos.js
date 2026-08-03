// ============================================================
//  Eventos — actividades comunitarias que los usuarios organizan
//  en las áreas verdes (limpiezas, caminatas, reforestaciones).
// ============================================================

const router  = require('express').Router();
const db      = require('../db');
const soloPHP = require('../middleware/solo_php');

// GET /api/eventos — todos los eventos, del más próximo al más lejano
router.get('/', async (req, res) => {
    const [filas] = await db.query(`
        SELECT E.id_evento, E.nombre, E.fecha, E.hora,
               U.nombre AS usuario,     -- quién lo organiza
               A.nombre AS area         -- dónde se hará
        FROM EVENTO E
        JOIN USUARIO U ON E.id_usuario = U.id_usuario
        JOIN AREA    A ON E.id_area    = A.id_area
        ORDER BY E.fecha ASC
    `);
    res.json(filas);
});

// POST /api/eventos — crear un evento
//
// Protegido con soloPHP: solo el servidor PHP puede crearlos, ya con la sesión
// del usuario verificada. Si estuviera abierto, cualquiera podría llenar el
// calendario de eventos falsos a nombre de otros.
router.post('/', soloPHP, async (req, res) => {
    const { id_usuario, id_area, nombre, fecha, hora } = req.body;

    // Todos los campos son obligatorios: un evento sin fecha o sin lugar
    // no le sirve a nadie
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
