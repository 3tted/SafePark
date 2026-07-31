const express = require('express');
const cors    = require('cors');
require('dotenv').config();

const app = express();

app.use(cors({ origin: '*' }));
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

app.use('/api/auth',        require('./routes/auth'));
app.use('/api/areas',       require('./routes/areas'));
app.use('/api/reportes',    require('./routes/reportes'));
app.use('/api/usuarios',    require('./routes/usuarios'));
app.use('/api/reacciones',  require('./routes/reacciones'));
app.use('/api/favoritos',   require('./routes/favoritos'));
app.use('/api/comentarios', require('./routes/comentarios'));
app.use('/api/eventos',     require('./routes/eventos'));

app.get('/api/health', (req, res) => res.json({ status: 'ok' }));

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`SafePark API corriendo en http://localhost:${PORT}`);
    if (!process.env.API_SECRET) {
        console.warn('AVISO: API_SECRET no esta configurada. Las operaciones de');
        console.warn('escritura (crear/borrar areas, cambiar roles) estan ABIERTAS.');
        console.warn('Configurala antes de exponer el API a internet.');
    }
});
