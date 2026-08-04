// ============================================================
//  Middleware de seguridad: deja pasar solo al servidor PHP
//
//  Protege las operaciones que modifican datos (crear, editar, borrar) y el
//  inicio de sesión. Sin esto, cualquiera con la URL del API podría borrar
//  áreas o cambiarse el rol a administrador desde internet.
//
//  Las rutas de LECTURA quedan sin proteger a propósito: el JavaScript del
//  navegador las llama directo, y un secreto escrito en la página sería
//  visible para todos, así que no daría seguridad real.
// ============================================================

module.exports = function soloDesdePHP(req, res, next) {
    const esperado = process.env.API_SECRET;

    // Sin secreto configurado no bloquea, para que el desarrollo local funcione
    // sin tener que preparar nada. index.js avisa al arrancar cuando falta.
    if (!esperado) return next();

    // El PHP manda este encabezado en cada llamada de escritura
    if (req.get('X-API-Secret') === esperado) return next();

    res.status(403).json({ ok: false, error: 'No autorizado' });
};
