// ============================================================
//  Rutas a pie — cómo llegar de donde estás al área que elegiste
//
//  Este endpoint no consulta la base de datos: hace de intermediario con
//  OpenRouteService, el servicio que calcula el trayecto.
//
//  Se hace desde aquí y no desde el JavaScript del navegador por una razón:
//  la llave de OpenRouteService quedaría a la vista de cualquiera que abra
//  el inspector, y con ella podrían gastarse la cuota diaria. Viviendo en
//  el servidor, nunca sale de aquí.
// ============================================================

const router = require('express').Router();

const ORS = 'https://api.openrouteservice.org/v2/directions';

// Perfiles que aceptamos. La lista es cerrada a propósito: el valor viaja
// dentro de la URL que se le pide a OpenRouteService, así que no puede
// venir suelto desde el navegador.
const PERFILES = ['foot-walking', 'driving-car', 'cycling-regular'];

// Convierte "31.6904,-106.4245" en [lng, lat], que es el orden que usa
// OpenRouteService (al revés del de Leaflet, que es la confusión clásica).
// Devuelve null si el texto no trae dos números dentro de rango.
function coordenada(texto) {
    if (typeof texto !== 'string') return null;

    const partes = texto.split(',');
    if (partes.length !== 2) return null;

    const lat = Number(partes[0]);
    const lng = Number(partes[1]);

    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
    if (lat < -90 || lat > 90 || lng < -180 || lng > 180) return null;

    return [lng, lat];
}

// GET /api/rutas?desde=lat,lng&hasta=lat,lng&perfil=foot-walking
//
// Responde con la línea del trayecto ya lista para dibujarse en Leaflet,
// más la distancia en metros y la duración en segundos.
router.get('/', async (req, res) => {
    if (!process.env.ORS_API_KEY) {
        // 503 y no 500: el servicio está bien, solo le falta configuración.
        // El mensaje le dice al front que ofrezca el enlace a Google Maps.
        return res.status(503).json({
            ok: false,
            error: 'El calculo de rutas no esta configurado en el servidor'
        });
    }

    const desde = coordenada(req.query.desde);
    const hasta = coordenada(req.query.hasta);

    if (!desde || !hasta) {
        return res.status(400).json({ ok: false, error: 'Coordenadas invalidas' });
    }

    const perfil = PERFILES.includes(req.query.perfil) ? req.query.perfil : 'foot-walking';

    try {
        // OpenRouteService responde GeoJSON cuando se le pide /geojson
        const r = await fetch(`${ORS}/${perfil}/geojson`, {
            method: 'POST',
            headers: {
                'Authorization': process.env.ORS_API_KEY,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ coordinates: [desde, hasta] }),
            // Sin límite de tiempo, una caída del servicio dejaría al usuario
            // esperando el spinner para siempre
            signal: AbortSignal.timeout(12000)
        });

        if (!r.ok) {
            const detalle = await r.text();
            console.error('OpenRouteService respondio', r.status, detalle.slice(0, 300));

            // 429 es cuota agotada; se distingue porque el aviso al usuario
            // debe ser distinto ("intenta mas tarde", no "no se pudo")
            const codigo = r.status === 429 ? 429 : 502;
            return res.status(codigo).json({
                ok: false,
                error: r.status === 429
                    ? 'Se agoto la cuota de rutas por ahora'
                    : 'El servicio de rutas no respondio'
            });
        }

        const geo = await r.json();
        const tramo = geo.features && geo.features[0];

        if (!tramo) {
            // Pasa cuando no hay calles que conecten los dos puntos
            return res.status(404).json({ ok: false, error: 'No se encontro una ruta' });
        }

        const resumen = tramo.properties.summary || {};

        // Leaflet quiere [lat, lng] y el GeoJSON viene en [lng, lat]
        const linea = tramo.geometry.coordinates.map(([lng, lat]) => [lat, lng]);

        res.json({
            ok: true,
            perfil,
            distancia: resumen.distance || 0,   // metros
            duracion:  resumen.duration || 0,   // segundos
            linea
        });

    } catch (e) {
        // Aquí caen el timeout y los fallos de red hacia OpenRouteService
        console.error('Error pidiendo la ruta:', e.message);
        res.status(504).json({ ok: false, error: 'El servicio de rutas tardo demasiado' });
    }
});

module.exports = router;
