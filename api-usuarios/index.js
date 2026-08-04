// ============================================================
//  SafePark — API de Usuarios
//
//  Servicio independiente encargado de todo lo relacionado con las cuentas:
//  registro, inicio de sesión (con contraseña o con Google), perfiles, roles
//  y el historial de actividad de cada usuario.
//
//  Es el único que verifica contraseñas: los hashes nunca salen de aquí.
// ============================================================

const express = require('express');
const cors    = require('cors');
require('dotenv').config();

const app = express();

app.use(cors({ origin: '*' }));
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

app.use('/api/auth',     require('./routes/auth'));
app.use('/api/usuarios', require('./routes/usuarios'));

app.get('/api/health', (req, res) => res.json({ status: 'ok', servicio: 'usuarios' }));

const PORT = process.env.PORT || 3001;
app.listen(PORT, () => {
    console.log(`SafePark API Usuarios corriendo en http://localhost:${PORT}`);
    if (!process.env.API_SECRET) {
        console.warn('AVISO: API_SECRET no esta configurada. El registro, el login');
        console.warn('y el cambio de roles estan ABIERTOS a cualquiera.');
        console.warn('Configurala antes de exponer el servicio a internet.');
    }
});
