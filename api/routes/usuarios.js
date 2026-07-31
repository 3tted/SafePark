const router  = require('express').Router();
const bcrypt  = require('bcryptjs');
const db      = require('../db');
const soloPHP = require('../middleware/solo_php');

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

// POST /api/usuarios — registrar usuario nuevo
router.post('/', soloPHP, async (req, res) => {
    const { nombre, email, password } = req.body;

    if (!nombre?.trim() || !email?.trim() || !password) {
        return res.status(400).json({ ok: false, error: 'Faltan datos' });
    }

    try {
        const [existe] = await db.query('SELECT 1 FROM USUARIO WHERE email = ?', [email.trim()]);
        if (existe.length) {
            return res.json({ ok: false, error: 'email_duplicado' });
        }

        const hash = bcrypt.hashSync(password, 10);
        const [r] = await db.query(
            'INSERT INTO USUARIO (nombre, email, contrasena_hash) VALUES (?,?,?)',
            [nombre.trim(), email.trim(), hash]
        );
        res.json({ ok: true, id_usuario: r.insertId });
    } catch (err) {
        console.error('POST /usuarios error:', err.message);
        res.status(500).json({ ok: false, error: 'Error del servidor' });
    }
});

// PUT /api/usuarios/:id — actualizar perfil
// La contrasena y la foto son opcionales: si no vienen, se conservan.
router.put('/:id', soloPHP, async (req, res) => {
    const { nombre, email, password, foto_perfil } = req.body;
    const id = parseInt(req.params.id);

    if (!id || !nombre?.trim() || !email?.trim()) {
        return res.status(400).json({ ok: false, error: 'Faltan datos' });
    }

    try {
        // El correo no puede estar tomado por OTRO usuario
        const [dup] = await db.query(
            'SELECT 1 FROM USUARIO WHERE email = ? AND id_usuario != ?',
            [email.trim(), id]
        );
        if (dup.length) return res.json({ ok: false, error: 'email_duplicado' });

        const campos = ['nombre = ?', 'email = ?'];
        const valores = [nombre.trim(), email.trim()];

        if (password) {
            campos.push('contrasena_hash = ?');
            valores.push(bcrypt.hashSync(password, 10));
        }
        if (foto_perfil) {
            campos.push('foto_perfil = ?');
            valores.push(foto_perfil);
        }
        valores.push(id);

        await db.query(`UPDATE USUARIO SET ${campos.join(', ')} WHERE id_usuario = ?`, valores);
        res.json({ ok: true });
    } catch (err) {
        console.error('PUT /usuarios error:', err.message);
        res.status(500).json({ ok: false, error: 'Error del servidor' });
    }
});

// GET /api/usuarios/:id/rol — solo el rol, para verificar permisos de admin
router.get('/:id/rol', async (req, res) => {
    const [filas] = await db.query('SELECT rol FROM USUARIO WHERE id_usuario = ?', [req.params.id]);
    if (!filas.length) return res.status(404).json({ rol: null });
    res.json({ rol: filas[0].rol });
});

// PUT /api/usuarios/:id/rol — cambiar rol
router.put('/:id/rol', soloPHP, async (req, res) => {
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
        'SELECT id_usuario, nombre, email, rol, fecha_registro, foto_perfil FROM USUARIO WHERE id_usuario = ?',
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
