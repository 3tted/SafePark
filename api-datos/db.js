// ============================================================
//  Conexión a MySQL
//
//  Se usa un "pool" en vez de una conexión suelta: mantiene varias conexiones
//  listas y las reparte entre las peticiones. Sin él, cada consulta abriría y
//  cerraría su propia conexión, lo cual es lento y agota el límite del servidor
//  cuando entran varios usuarios a la vez.
// ============================================================

const mysql = require('mysql2/promise');
require('dotenv').config();          // lee el archivo .env

const pool = mysql.createPool({
    host:     process.env.DB_HOST,
    // XAMPP usa 3306, pero los MySQL en la nube suelen asignar otro puerto
    port:     process.env.DB_PORT || 3306,
    user:     process.env.DB_USER,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_NAME,
    // utf8mb4 es indispensable: los emojis de las reacciones ocupan 4 bytes y
    // no caben en el utf8 de MySQL, que solo maneja 3
    charset:  'utf8mb4'
});

module.exports = pool;
