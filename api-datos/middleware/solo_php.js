// Protege las operaciones que unicamente el servidor PHP debe poder ejecutar:
// crear/editar/borrar areas, cambiar el estado de reportes y cambiar roles.
//
// Las rutas que el navegador llama directo (reacciones, comentarios, favoritos)
// quedan SIN proteger a proposito: el JS corre en el cliente, asi que cualquier
// secreto que le pasaramos seria visible en el codigo fuente de la pagina.
//
// El secreto se configura con la variable de entorno API_SECRET.

module.exports = function soloDesdePHP(req, res, next) {
    const esperado = process.env.API_SECRET;

    // Sin secreto configurado no bloquea, para que el desarrollo local siga
    // funcionando sin tener que configurar nada. index.js avisa al arrancar.
    if (!esperado) return next();

    if (req.get('X-API-Secret') === esperado) return next();

    res.status(403).json({ ok: false, error: 'No autorizado' });
};
