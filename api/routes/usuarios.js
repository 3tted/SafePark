const router = require('express').Router();
const db = require('../db');

// GET /api/usuarios — lista de usuarios con conteo de reportes
router.get('/', async (req, res) => {
    const [rows] = await db.query(`
        SELECT U.id_usuario, U.nombre, U.email, U.rol, U.fecha_registro,
               COUNT(R.id_reporte) AS total_reportes
        FROM USUARIO U
        LEFT JOIN REPORTE R ON U.id_usuario = R.id_usuario
        GROUP BY U.id_usuario
        ORDER BY total_reportes DESC
    `);
    res.json(rows);
});

// PUT /api/usuarios/:id/rol — cambiar rol
router.put('/:id/rol', async (req, res) => {
    const { rol } = req.body;
    const validos = ['usuario', 'admin'];
    if (!validos.includes(rol)) return res.status(400).json({ ok: false, error: 'Rol inválido' });
    try {
        await db.query('UPDATE USUARIO SET rol = ? WHERE id_usuario = ?', [rol, req.params.id]);
        res.json({ ok: true });
    } catch (err) {
        res.status(500).json({ ok: false, error: err.message });
    }
});

// GET /api/usuarios/:id — perfil de un usuario con puntos
router.get('/:id', async (req, res) => {
    const [users] = await db.query(
        'SELECT id_usuario, nombre, email, rol FROM USUARIO WHERE id_usuario = ?',
        [req.params.id]
    );
    if (!users.length) return res.status(404).json({ error: 'Usuario no encontrado' });

    const [reportes] = await db.query(`
        SELECT tipo, COUNT(*) AS total
        FROM REPORTE WHERE id_usuario = ?
        GROUP BY tipo
    `, [req.params.id]);

    const pts = { incidente: 15, condicion: 10, sugerencia: 5 };
    const puntos = reportes.reduce((sum, r) => sum + (r.total * (pts[r.tipo] ?? 0)), 0);

    res.json({ ...users[0], reportes, puntos });
});

module.exports = router;
