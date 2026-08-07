// ============================================================
//  Eventos — actividades comunitarias que los usuarios organizan
//  en las áreas verdes (limpiezas, caminatas, reforestaciones).
// ============================================================

const router  = require('express').Router();
const db      = require('../db');
const soloPHP = require('../middleware/solo_php');

// GET /api/eventos — todos los eventos, del más próximo al más lejano
//
// Las fechas salen con DATE_FORMAT y no como columna DATE a secas: el driver
// convierte una DATE en objeto Date de JavaScript y al viajar como JSON se
// vuelve "2026-08-16T00:00:00.000Z". Esa Z arrastra la zona horaria y puede
// correr el día, y PHP no la sabe leer si se le pega la hora detrás.
//
// fecha         = cuándo se hará el evento
// fecha_creacion = cuándo se anunció, que es lo que ordena el feed
router.get('/', async (req, res) => {
    const [filas] = await db.query(`
        SELECT E.id_evento, E.nombre,
               DATE_FORMAT(E.fecha, '%Y-%m-%d')                   AS fecha,
               DATE_FORMAT(E.hora,  '%H:%i:%s')                   AS hora,
               DATE_FORMAT(E.fecha_creacion, '%Y-%m-%d %H:%i:%s') AS fecha_creacion,
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
