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

        // Mismo mensaje si el correo no existe, si la cuenta es de Google (no
        // tiene contraseña propia) o si la contraseña no coincide: así no se
        // revela qué correos están registrados ni de qué tipo son.
        if (!filas.length ||
            !filas[0].contrasena_hash ||
            !bcrypt.compareSync(password, filas[0].contrasena_hash)) {
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

// POST /api/auth/google — entra (o registra) a alguien con su cuenta de Google
//
// Quien verificó la identidad es Google: el PHP ya intercambió el código por un
// token y confirmó el correo antes de llamar aquí. Por eso este endpoint no
// recibe contraseña, y por eso exige el secreto compartido — si estuviera
// abierto, cualquiera podría entrar mandando el correo de otra persona.
router.post('/google', soloPHP, async (req, res) => {
    const { email, nombre } = req.body;

    if (!email?.trim()) {
        return res.status(400).json({ ok: false, error: 'Falta el correo' });
    }

    const correo = email.trim().toLowerCase();

    try {
        const [existentes] = await db.query(
            'SELECT id_usuario, nombre, rol FROM USUARIO WHERE email = ?',
            [correo]
        );

        // Si el correo ya estaba registrado (con contraseña o con Google) se
        // reutiliza esa cuenta en vez de crear una duplicada.
        if (existentes.length) {
            const u = existentes[0];
            return res.json({
                ok: true,
                nuevo: false,
                usuario: { id_usuario: u.id_usuario, nombre: u.nombre, rol: u.rol }
            });
        }

        // Cuenta nueva: sin contraseña, porque se entra siempre por Google
        const [r] = await db.query(
            'INSERT INTO USUARIO (nombre, email, contrasena_hash) VALUES (?,?,NULL)',
            [(nombre || correo.split('@')[0]).trim().slice(0, 100), correo]
        );

        res.json({
            ok: true,
            nuevo: true,
            usuario: { id_usuario: r.insertId, nombre: nombre || correo.split('@')[0], rol: 'usuario' }
        });
    } catch (err) {
        console.error('POST /auth/google error:', err.message);
        res.status(500).json({ ok: false, error: 'Error del servidor' });
    }
});

module.exports = router;
