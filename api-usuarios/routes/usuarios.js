// ============================================================
//  Usuarios: registro, perfil, roles e historial de actividad
//
//  Este archivo y auth.js son los únicos que tocan la tabla USUARIO. Las
//  contraseñas se guardan siempre como hash de bcrypt, nunca en texto plano:
//  si alguien llegara a ver la base, no podría leerlas.
// ============================================================

const router  = require('express').Router();
const bcrypt  = require('bcryptjs');
const db      = require('../db');
const soloPHP = require('../middleware/solo_php');

// GET /api/usuarios — la lista completa, ordenada por quien más ha reportado
//
// Alimenta la tabla del panel de Admin y el ranking de contribuidores de
// Comunidad. Usa LEFT JOIN para que los usuarios sin reportes también salgan
// (con cero) en vez de desaparecer.
//
// Nunca devuelve contrasena_hash: no hay motivo para que salga del servicio.
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

// POST /api/usuarios — crear una cuenta nueva
//
// El correo se revisa antes de insertar para poder responder con un mensaje
// claro. La columna además tiene índice UNIQUE, así que la base es la última
// palabra si dos personas se registran en el mismo instante.
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

        // El 10 son las "rondas" de bcrypt: cuántas veces repite su cálculo.
        // Más rondas hacen el hash más lento de generar y, por lo mismo, mucho
        // más caro de romper por fuerza bruta. 10 es el valor habitual.
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

// PUT /api/usuarios/:id — editar el perfil
//
// La contraseña y la foto son opcionales. Si no vienen, se conservan las que
// ya estaban: el formulario de editar deja esos campos vacíos cuando el usuario
// solo quiere cambiar su nombre o su correo.
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

        // El UPDATE se arma por partes según lo que haya llegado. Así se evita
        // tener que escribir una consulta distinta para cada combinación
        // (solo nombre, nombre y foto, nombre y contraseña, las tres...).
        const campos  = ['nombre = ?', 'email = ?'];
        const valores = [nombre.trim(), email.trim()];

        if (password) {
            campos.push('contrasena_hash = ?');
            valores.push(bcrypt.hashSync(password, 10));
        }
        if (foto_perfil) {
            campos.push('foto_perfil = ?');
            valores.push(foto_perfil);
        }
        valores.push(id);   // el último valor es el del WHERE

        // Los nombres de columna se unen con join, pero los VALORES siguen yendo
        // como parámetros (?) — nunca concatenados. Eso es lo que evita la
        // inyección SQL.
        await db.query(`UPDATE USUARIO SET ${campos.join(', ')} WHERE id_usuario = ?`, valores);
        res.json({ ok: true });
    } catch (err) {
        console.error('PUT /usuarios error:', err.message);
        res.status(500).json({ ok: false, error: 'Error del servidor' });
    }
});

// GET /api/usuarios/:id/actividad — historial del usuario en orden cronológico
//
// Junta las cuatro cosas que puede hacer (reportar, crear eventos, comentar y
// reaccionar) en una sola consulta con UNION, para no hacer cuatro viajes a la
// base. Cada fila trae el área relacionada como contexto: en comentarios y
// reacciones hay que rastrearla a través del reporte o el evento sobre el que
// se hicieron, de ahí los LEFT JOIN encadenados.
router.get('/:id/actividad', async (req, res) => {
    const id = parseInt(req.params.id);
    if (!id) return res.json([]);

    try {
        const [filas] = await db.query(`
            SELECT 'reporte' AS tipo, R.fecha AS fecha, R.tipo AS subtipo,
                   R.descripcion AS detalle, A.nombre AS contexto
            FROM REPORTE R
            LEFT JOIN AREA A ON R.id_area = A.id_area
            WHERE R.id_usuario = ?

            UNION ALL

            SELECT 'evento', E.fecha, NULL, E.nombre, A.nombre
            FROM EVENTO E
            LEFT JOIN AREA A ON E.id_area = A.id_area
            WHERE E.id_usuario = ?

            UNION ALL

            SELECT 'comentario', C.fecha, NULL, C.texto,
                   COALESCE(AR.nombre, AE.nombre)
            FROM COMENTARIO C
            LEFT JOIN REPORTE R  ON C.id_reporte = R.id_reporte
            LEFT JOIN AREA    AR ON R.id_area    = AR.id_area
            LEFT JOIN EVENTO  E  ON C.id_evento  = E.id_evento
            LEFT JOIN AREA    AE ON E.id_area    = AE.id_area
            WHERE C.id_usuario = ?

            UNION ALL

            SELECT 'reaccion', X.fecha, X.emoji, NULL,
                   COALESCE(AR.nombre, AE.nombre)
            FROM REACCION X
            LEFT JOIN REPORTE R  ON X.id_reporte = R.id_reporte
            LEFT JOIN AREA    AR ON R.id_area    = AR.id_area
            LEFT JOIN EVENTO  E  ON X.id_evento  = E.id_evento
            LEFT JOIN AREA    AE ON E.id_area    = AE.id_area
            WHERE X.id_usuario = ?

            ORDER BY fecha DESC
            LIMIT 30
        `, [id, id, id, id]);

        res.json(filas);
    } catch (err) {
        console.error('GET /usuarios/:id/actividad error:', err.message);
        res.status(500).json([]);
    }
});

// GET /api/usuarios/:id/rol — devuelve únicamente el rol
//
// Existe aparte del perfil completo porque el PHP lo consulta en cada carga de
// una página de administración, y traer solo un campo es mucho más barato.
//
// Se verifica contra la base en vez de confiar en la sesión, para que quitarle
// el rol a alguien tenga efecto de inmediato sin esperar a que cierre sesión.
router.get('/:id/rol', async (req, res) => {
    const [filas] = await db.query('SELECT rol FROM USUARIO WHERE id_usuario = ?', [req.params.id]);
    if (!filas.length) return res.status(404).json({ rol: null });
    res.json({ rol: filas[0].rol });
});

// PUT /api/usuarios/:id/rol — ascender o quitar permisos de administrador
//
// Es la ruta más delicada del API: quien pudiera llamarla libremente se haría
// admin y tendría control total. De ahí que exija el secreto compartido.
router.put('/:id/rol', soloPHP, async (req, res) => {
    const { rol } = req.body;
    const validos = ['usuario', 'admin'];

    // Solo se aceptan esos dos valores; la columna es un ENUM y cualquier otro
    // lo rechazaría MySQL con un error poco claro
    if (!validos.includes(rol)) {
        return res.status(400).json({ ok: false, error: 'Rol inválido' });
    }

    try {
        await db.query('UPDATE USUARIO SET rol = ? WHERE id_usuario = ?',
                       [rol, req.params.id]);
        res.json({ ok: true });
    } catch (err) {
        console.error('PUT /usuarios/:id/rol error:', err.message);
        res.status(500).json({ ok: false, error: 'Error del servidor' });
    }
});

// GET /api/usuarios/:id — el perfil completo, con sus puntos acumulados
//
// Va al final por convención: primero las rutas específicas, después las
// genéricas. Aquí no cambiaría nada —/:id cubre un solo segmento y /:id/rol
// tiene dos— pero deja el archivo ordenado de lo particular a lo general.
router.get('/:id', async (req, res) => {
    const [users] = await db.query(
        'SELECT id_usuario, nombre, email, rol, fecha_registro, foto_perfil FROM USUARIO WHERE id_usuario = ?',
        [req.params.id]
    );
    if (!users.length) return res.status(404).json({ error: 'Usuario no encontrado' });

    // Cuántos reportes ha hecho, separados por tipo
    const [reportes] = await db.query(`
        SELECT tipo, COUNT(*) AS total
        FROM REPORTE WHERE id_usuario = ?
        GROUP BY tipo
    `, [req.params.id]);

    // Los puntos premian el esfuerzo de reportar. Un incidente vale más porque
    // exige salir a verificar algo delicado; una sugerencia es solo una idea.
    const valores = { incidente: 15, condicion: 10, sugerencia: 5 };
    const puntos = reportes.reduce(
        (suma, r) => suma + (r.total * (valores[r.tipo] ?? 0)),
        0
    );

    // Se juntan los datos del usuario con su conteo y sus puntos
    res.json({ ...users[0], reportes, puntos });
});

module.exports = router;
