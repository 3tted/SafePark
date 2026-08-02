const router    = require('express').Router();
const db        = require('../db');
const soloPHP   = require('../middleware/solo_php');

// Las fotos las guarda PHP en Assets/fotos/ y aquí sólo se registra el nombre
// del archivo. Así la imagen vive en el mismo servidor que la sirve.

// GET /api/areas
router.get('/', async (req, res) => {
    const [rows] = await db.query(`
        SELECT a.id_area, a.nombre, a.colonia, a.direccion, a.horario,
               a.tipo, a.lat, a.lng, a.foto, a.id_usuario,
            COALESCE(SUM(r.tipo = 'incidente'), 0) AS n_incidentes,
            COALESCE(SUM(r.tipo = 'condicion'), 0) AS n_condiciones,
            COALESCE(SUM(r.tipo = 'sugerencia'), 0) AS n_sugerencias
        FROM AREA a
        LEFT JOIN REPORTE r ON r.id_area = a.id_area
        GROUP BY a.id_area
    `);

    const areas = rows.map(a => {
        const score = 100 * Math.pow(0.92, a.n_incidentes) * Math.pow(0.95, a.n_condiciones) * Math.pow(0.98, a.n_sugerencias);
        return {
            id:          a.id_area,
            nombre:      a.nombre,
            colonia:     a.colonia ?? '',
            direccion:   a.direccion ?? '',
            horario:     a.horario ?? '',
            tipo:        a.tipo ?? 'parque',
            lat:         parseFloat(a.lat),
            lng:         parseFloat(a.lng),
            foto:        a.foto ?? null,
            id_usuario:  a.id_usuario,
            score:       Math.max(10, Math.round(score))
        };
    });

    res.json(areas);
});

// GET /api/areas/:id
router.get('/:id', async (req, res) => {
    const [rows] = await db.query('SELECT * FROM AREA WHERE id_area = ?', [req.params.id]);
    if (!rows.length) return res.status(404).json({ error: 'Área no encontrada' });
    res.json(rows[0]);
});

// POST /api/areas — crear área (foto opcional, ya guardada por PHP)
router.post('/', soloPHP, async (req, res) => {
    const { id_usuario, nombre, colonia, direccion, horario, tipo, lat, lng, foto } = req.body;
    const tipos_validos = ['parque', 'deportivo', 'plaza'];

    if (!id_usuario || !nombre?.trim() || !colonia?.trim() || !tipos_validos.includes(tipo) || !lat || !lng) {
        return res.status(400).json({ ok: false, error: 'Datos inválidos' });
    }

    try {
        await db.query(
            'INSERT INTO AREA (nombre, colonia, direccion, horario, tipo, lat, lng, id_usuario, foto) VALUES (?,?,?,?,?,?,?,?,?)',
            [nombre.trim(), colonia.trim(), direccion?.trim() || null, horario?.trim() || null,
             tipo, lat, lng, id_usuario, foto || null]
        );
        res.json({ ok: true });
    } catch (err) {
        console.error('POST /areas error:', err.message);
        res.status(500).json({ ok: false, error: err.message });
    }
});

// PUT /api/areas/:id — actualizar área (foto opcional, ya guardada por PHP)
router.put('/:id', soloPHP, async (req, res) => {
    const { id_usuario, nombre, colonia, direccion, horario, tipo, lat, lng, foto } = req.body;
    const id_area = parseInt(req.params.id);
    const tipos_validos = ['parque', 'deportivo', 'plaza'];

    if (!id_area || !nombre?.trim() || !colonia?.trim() || !tipos_validos.includes(tipo) || !lat || !lng) {
        return res.status(400).json({ ok: false, error: 'Datos inválidos' });
    }

    // Verificar que el usuario es dueño o admin
    const [[area]] = await db.query('SELECT id_usuario FROM AREA WHERE id_area = ?', [id_area]);
    if (!area) return res.status(404).json({ ok: false, error: 'Área no encontrada' });

    const [[user]] = await db.query('SELECT rol FROM USUARIO WHERE id_usuario = ?', [id_usuario]);
    const esAdmin  = user?.rol === 'admin';

    if (!esAdmin && parseInt(area.id_usuario) !== parseInt(id_usuario)) {
        return res.status(403).json({ ok: false, error: 'Sin permiso' });
    }

    const campos = [nombre.trim(), colonia.trim(), direccion?.trim() || null,
                    horario?.trim() || null, tipo, lat, lng];

    // Sin foto nueva se conserva la que ya tenía
    if (foto) {
        await db.query(
            'UPDATE AREA SET nombre=?, colonia=?, direccion=?, horario=?, tipo=?, lat=?, lng=?, foto=? WHERE id_area=?',
            [...campos, foto, id_area]
        );
    } else {
        await db.query(
            'UPDATE AREA SET nombre=?, colonia=?, direccion=?, horario=?, tipo=?, lat=?, lng=? WHERE id_area=?',
            [...campos, id_area]
        );
    }
    res.json({ ok: true });
});

// DELETE /api/areas/:id — eliminar área y registros relacionados
router.delete('/:id', soloPHP, async (req, res) => {
    const id_area = parseInt(req.params.id);
    if (!id_area) return res.status(400).json({ ok: false });
    try {
        for (const tabla of ['EVENTO', 'REPORTE', 'FAVORITO']) {
            await db.query(`DELETE FROM ${tabla} WHERE id_area = ?`, [id_area]);
        }
        await db.query('DELETE FROM AREA WHERE id_area = ?', [id_area]);
        res.json({ ok: true });
    } catch (err) {
        res.status(500).json({ ok: false, error: err.message });
    }
});

// GET /api/areas/stats — estadísticas generales
router.get('/stats/resumen', async (req, res) => {
    const [[{ total_areas }]]    = await db.query('SELECT COUNT(*) AS total_areas FROM AREA');
    const [[{ total_reportes }]] = await db.query('SELECT COUNT(*) AS total_reportes FROM REPORTE');
    const [[{ pendientes }]]     = await db.query("SELECT COUNT(*) AS pendientes FROM REPORTE WHERE estado='pendiente'");
    const [[{ total_usuarios }]] = await db.query('SELECT COUNT(*) AS total_usuarios FROM USUARIO');
    res.json({ total_areas, total_reportes, pendientes, total_usuarios });
});

module.exports = router;
