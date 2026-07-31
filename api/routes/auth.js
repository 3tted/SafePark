const router  = require('express').Router();
const bcrypt  = require('bcryptjs');
const db      = require('../db');
const soloPHP = require('../middleware/solo_php');

// POST /api/auth/login — verifica credenciales
//
// La contrasena se compara aqui y nunca sale del API: el PHP solo recibe
// "si o no" mas los datos del usuario. Los hashes de PHP ($2y$) y los de
// bcryptjs son compatibles, asi que los usuarios ya registrados entran igual.
router.post('/login', soloPHP, async (req, res) => {
    const { email, password } = req.body;

    if (!email?.trim() || !password) {
        return res.status(400).json({ ok: false, error: 'Faltan datos' });
    }

    try {
        const [filas] = await db.query(
            'SELECT id_usuario, nombre, rol, contrasena_hash FROM USUARIO WHERE email = ?',
            [email.trim()]
        );

        // Mismo mensaje si el correo no existe o si la contrasena no coincide,
        // para no revelar que correos estan registrados
        if (!filas.length || !bcrypt.compareSync(password, filas[0].contrasena_hash)) {
            return res.json({ ok: false, error: 'Credenciales incorrectas' });
        }

        const u = filas[0];
        res.json({
            ok: true,
            usuario: { id_usuario: u.id_usuario, nombre: u.nombre, rol: u.rol }
        });
    } catch (err) {
        console.error('POST /auth/login error:', err.message);
        res.status(500).json({ ok: false, error: 'Error del servidor' });
    }
});

module.exports = router;
