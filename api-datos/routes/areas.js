// ============================================================
//  Áreas verdes y semáforo de seguridad
//
//  Aquí vive el cálculo que le da sentido al proyecto: convertir los reportes
//  ciudadanos en un número del 0 al 100 que dice qué tan segura está un área.
//
//  Las fotos las guarda PHP en Assets/fotos/ y aquí solo se registra el nombre
//  del archivo, para que la imagen viva en el mismo servidor que la muestra.
// ============================================================

const router  = require('express').Router();
const db      = require('../db');
const soloPHP = require('../middleware/solo_php');

// GET /api/areas — todas las áreas, cada una con su score de seguridad
//
// La consulta cuenta de una vez los reportes de cada tipo por área:
//   LEFT JOIN  para que las áreas sin reportes también aparezcan
//   SUM(r.tipo = 'x')  cuenta cuántos son de ese tipo (cada coincidencia da 1)
//   COALESCE(...,0)    convierte el NULL de las áreas sin reportes en cero
router.get('/', async (req, res) => {
    const [filas] = await db.query(`
        SELECT a.id_area, a.nombre, a.colonia, a.direccion, a.horario,
               a.tipo, a.lat, a.lng, a.foto, a.id_usuario,
            COALESCE(SUM(r.tipo = 'incidente'),  0) AS n_incidentes,
            COALESCE(SUM(r.tipo = 'condicion'),  0) AS n_condiciones,
            COALESCE(SUM(r.tipo = 'sugerencia'), 0) AS n_sugerencias
        FROM AREA a
        LEFT JOIN REPORTE r ON r.id_area = a.id_area
        GROUP BY a.id_area
    `);

    const areas = filas.map(a => {
        const n_incidentes  = Number(a.n_incidentes);
        const n_condiciones = Number(a.n_condiciones);
        const n_sugerencias = Number(a.n_sugerencias);

        // El semáforo parte de 100 y cada reporte lo va reduciendo un porcentaje.
        // Se multiplica en vez de restar para que el castigo sea proporcional:
        // el primer incidente pesa más que el décimo, y el score nunca baja de 0.
        //
        // Un incidente quita 3% (queda el 97%), una condición 2%, una sugerencia 1%,
        // porque no es lo mismo un robo que una banca rota o una idea de mejora.
        // Con estos pesos hacen falta 12 incidentes para salir del verde y 31
        // para llegar al rojo: un puñado de reportes no derrumba un área.
        const score = 100
            * Math.pow(0.97, n_incidentes)
            * Math.pow(0.98, n_condiciones)
            * Math.pow(0.99, n_sugerencias);

        return {
            id:         a.id_area,
            nombre:     a.nombre,
            colonia:    a.colonia   ?? '',
            direccion:  a.direccion ?? '',
            horario:    a.horario   ?? '',
            tipo:       a.tipo      ?? 'parque',
            lat:        parseFloat(a.lat),   // MySQL los da como texto
            lng:        parseFloat(a.lng),
            foto:       a.foto ?? null,
            id_usuario: a.id_usuario,
            reportes:   n_incidentes + n_condiciones + n_sugerencias,
            // Piso de 10: un área con muchísimos reportes tendería a 0, y un
            // cero absoluto daría a entender que no hay información
            score: Math.max(10, Math.round(score))
        };
    });

    // Contexto para acompañar al puntaje: cuántos reportes tiene el área frente
    // al total de la ciudad, y en qué lugar queda comparada con las demás.
    //
    // El puntaje sigue calculándose por área y sin mirar a las otras: eso lo
    // mantiene estable en el tiempo. Esto va aparte, sólo como referencia.
    const totalCiudad = areas.reduce((suma, a) => suma + a.reportes, 0);

    const conReportes = areas.filter(a => a.reportes > 0)
                             .sort((x, y) => y.reportes - x.reportes);

    // Las áreas empatadas comparten posición: dos con 8 reportes son ambas 1ª
    let posicion = 0, anterior = null;
    conReportes.forEach((a, i) => {
        if (a.reportes !== anterior) { posicion = i + 1; anterior = a.reportes; }
        a.posicion = posicion;
    });

    areas.forEach(a => {
        a.total_ciudad = totalCiudad;
        if (a.reportes === 0) a.posicion = null;   // sin reportes no hay ranking
    });

    res.json(areas);
});

// GET /api/areas/stats/resumen — los números del panel de Admin
//
// Se declara antes que /:id por costumbre de poner las rutas específicas
// primero. En este caso concreto no haría diferencia, porque /:id solo cubre
// un segmento y esta ruta tiene dos ("stats" y "resumen"), pero si algún día
// se agrega /areas/stats a secas, el orden ya está listo.
router.get('/stats/resumen', async (req, res) => {
    const [[{ total_areas }]]    = await db.query('SELECT COUNT(*) AS total_areas FROM AREA');
    const [[{ total_reportes }]] = await db.query('SELECT COUNT(*) AS total_reportes FROM REPORTE');
    const [[{ pendientes }]]     = await db.query("SELECT COUNT(*) AS pendientes FROM REPORTE WHERE estado='pendiente'");
    const [[{ total_usuarios }]] = await db.query('SELECT COUNT(*) AS total_usuarios FROM USUARIO');

    res.json({ total_areas, total_reportes, pendientes, total_usuarios });
});

// GET /api/areas/:id — una sola área con todos sus campos
router.get('/:id', async (req, res) => {
    const [filas] = await db.query('SELECT * FROM AREA WHERE id_area = ?', [req.params.id]);
    if (!filas.length) return res.status(404).json({ error: 'Área no encontrada' });
    res.json(filas[0]);
});

// POST /api/areas — registrar un área nueva
router.post('/', soloPHP, async (req, res) => {
    const { id_usuario, nombre, colonia, direccion, horario, tipo, lat, lng, foto } = req.body;
    const tipos_validos = ['parque', 'deportivo', 'plaza'];

    // Sin coordenadas no se puede pintar en el mapa, así que son obligatorias.
    // Dirección y horario sí son opcionales.
    if (!id_usuario || !nombre?.trim() || !colonia?.trim() ||
        !tipos_validos.includes(tipo) || !lat || !lng) {
        return res.status(400).json({ ok: false, error: 'Datos inválidos' });
    }

    try {
        await db.query(
            `INSERT INTO AREA (nombre, colonia, direccion, horario, tipo, lat, lng, id_usuario, foto)
             VALUES (?,?,?,?,?,?,?,?,?)`,
            [nombre.trim(), colonia.trim(), direccion?.trim() || null, horario?.trim() || null,
             tipo, lat, lng, id_usuario, foto || null]
        );
        res.json({ ok: true });
    } catch (err) {
        console.error('POST /areas error:', err.message);
        res.status(500).json({ ok: false, error: 'Error del servidor' });
    }
});

// PUT /api/areas/:id — editar un área
//
// Solo puede editarla quien la creó, o un administrador. Ese permiso se revisa
// AQUÍ y no solo en el PHP: si estuviera únicamente del lado del frontend,
// bastaría con llamar al API directamente para saltárselo.
router.put('/:id', soloPHP, async (req, res) => {
    const { id_usuario, nombre, colonia, direccion, horario, tipo, lat, lng, foto } = req.body;
    const id_area = parseInt(req.params.id);
    const tipos_validos = ['parque', 'deportivo', 'plaza'];

    if (!id_area || !nombre?.trim() || !colonia?.trim() ||
        !tipos_validos.includes(tipo) || !lat || !lng) {
        return res.status(400).json({ ok: false, error: 'Datos inválidos' });
    }

    const [[area]] = await db.query('SELECT id_usuario FROM AREA WHERE id_area = ?', [id_area]);
    if (!area) return res.status(404).json({ ok: false, error: 'Área no encontrada' });

    const [[usuario]] = await db.query('SELECT rol FROM USUARIO WHERE id_usuario = ?', [id_usuario]);
    const esAdmin = usuario?.rol === 'admin';

    if (!esAdmin && parseInt(area.id_usuario) !== parseInt(id_usuario)) {
        return res.status(403).json({ ok: false, error: 'Sin permiso' });
    }

    const campos = [nombre.trim(), colonia.trim(), direccion?.trim() || null,
                    horario?.trim() || null, tipo, lat, lng];

    // Si no mandaron foto nueva se conserva la anterior, en vez de borrarla:
    // el formulario deja ese campo vacío cuando solo se edita el texto
    if (foto) {
        await db.query(
            `UPDATE AREA SET nombre=?, colonia=?, direccion=?, horario=?, tipo=?, lat=?, lng=?, foto=?
             WHERE id_area=?`,
            [...campos, foto, id_area]
        );
    } else {
        await db.query(
            `UPDATE AREA SET nombre=?, colonia=?, direccion=?, horario=?, tipo=?, lat=?, lng=?
             WHERE id_area=?`,
            [...campos, id_area]
        );
    }

    res.json({ ok: true });
});

// DELETE /api/areas/:id — eliminar un área
//
// Primero se borra todo lo que apunta a ella. Sin esto, MySQL rechazaría el
// borrado por las llaves foráneas: no se puede eliminar un área si todavía
// existen reportes o eventos que la referencian.
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
        console.error('DELETE /areas error:', err.message);
        res.status(500).json({ ok: false, error: 'Error del servidor' });
    }
});

module.exports = router;
