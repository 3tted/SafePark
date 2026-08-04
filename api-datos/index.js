// ============================================================
//  SafePark — API de Datos
//
//  Servicio independiente con el contenido de la plataforma: áreas verdes,
//  reportes ciudadanos, eventos, comentarios, reacciones y favoritos.
//
//  Calcula también el semáforo de seguridad de cada área a partir de sus
//  reportes. No maneja contraseñas: de eso se encarga la API de Usuarios.
// ============================================================

const express = require('express');
const cors    = require('cors');
require('dotenv').config();

const app = express();

app.use(cors({ origin: '*' }));
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

app.use('/api/areas',       require('./routes/areas'));
app.use('/api/reportes',    require('./routes/reportes'));
app.use('/api/eventos',     require('./routes/eventos'));
app.use('/api/comentarios', require('./routes/comentarios'));
app.use('/api/reacciones',  require('./routes/reacciones'));
app.use('/api/favoritos',   require('./routes/favoritos'));
app.use('/api/rutas',       require('./routes/rutas'));

app.get('/api/health', (req, res) => res.json({ status: 'ok', servicio: 'datos' }));

const PORT = process.env.PORT || 3002;
app.listen(PORT, () => {
    console.log(`SafePark API Datos corriendo en http://localhost:${PORT}`);
    if (!process.env.ORS_API_KEY) {
        console.warn('AVISO: ORS_API_KEY no esta configurada. El boton "Como llegar"');
        console.warn('no dibujara la ruta; ofrecera abrir Google Maps en su lugar.');
    }
    if (!process.env.API_SECRET) {
        console.warn('AVISO: API_SECRET no esta configurada. Crear, editar y borrar');
        console.warn('areas, reportes y eventos esta ABIERTO a cualquiera.');
        console.warn('Configurala antes de exponer el servicio a internet.');
    }
});
