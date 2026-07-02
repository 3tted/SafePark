-- ============================================================
--  SafePark — Base de datos completa
--  Copiar y pegar en phpMyAdmin > pestaña SQL
-- ============================================================

CREATE DATABASE IF NOT EXISTS safepark_db;
USE safepark_db;

-- ------------------------------------------------------------
--  USUARIO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS USUARIO (
    id_usuario      INT          PRIMARY KEY AUTO_INCREMENT,
    nombre          VARCHAR(100) NOT NULL,
    email           VARCHAR(100) NOT NULL UNIQUE,
    contrasena_hash VARCHAR(255) NOT NULL,
    rol             ENUM('usuario','admin') DEFAULT 'usuario',
    foto_perfil     VARCHAR(255) DEFAULT NULL,
    fecha_registro  DATETIME     DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
--  AREA
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS AREA (
    id_area    INT          PRIMARY KEY AUTO_INCREMENT,
    nombre     VARCHAR(100) NOT NULL,
    colonia    VARCHAR(100) DEFAULT NULL,
    tipo       ENUM('parque','deportivo','plaza') DEFAULT 'parque',
    lat        DECIMAL(10,7) DEFAULT NULL,
    lng        DECIMAL(10,7) DEFAULT NULL,
    foto       VARCHAR(255)  DEFAULT NULL,
    id_usuario INT           DEFAULT NULL,
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario) ON DELETE SET NULL
);

-- ------------------------------------------------------------
--  FAVORITO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS FAVORITO (
    id_usuario INT NOT NULL,
    id_area    INT NOT NULL,
    PRIMARY KEY (id_usuario, id_area),
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_area)    REFERENCES AREA(id_area)       ON DELETE CASCADE
);

-- ------------------------------------------------------------
--  REPORTE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS REPORTE (
    id_reporte  INT  PRIMARY KEY AUTO_INCREMENT,
    id_usuario  INT  NOT NULL,
    id_area     INT  NOT NULL,
    tipo        ENUM('incidente','condicion','sugerencia') DEFAULT 'incidente',
    descripcion TEXT DEFAULT NULL,
    estado      ENUM('pendiente','en_proceso','resuelto')  DEFAULT 'pendiente',
    fecha       DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_area)    REFERENCES AREA(id_area)       ON DELETE CASCADE
);

-- ------------------------------------------------------------
--  EVENTO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS EVENTO (
    id_evento  INT          PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT          NOT NULL,
    id_area    INT          DEFAULT NULL,
    nombre     VARCHAR(150) NOT NULL,
    descripcion TEXT        DEFAULT NULL,
    fecha      DATE         NOT NULL,
    hora       TIME         NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_area)    REFERENCES AREA(id_area)       ON DELETE SET NULL
);

-- ------------------------------------------------------------
--  EVENTO_ASISTENTE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS EVENTO_ASISTENTE (
    id_evento  INT NOT NULL,
    id_usuario INT NOT NULL,
    PRIMARY KEY (id_evento, id_usuario),
    FOREIGN KEY (id_evento)  REFERENCES EVENTO(id_evento)   ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario) ON DELETE CASCADE
);

-- ============================================================
--  DATOS DE PRUEBA — Áreas verdes reales de Ciudad Juárez
-- ============================================================
INSERT INTO AREA (nombre, colonia, tipo, lat, lng) VALUES
('Parque El Chamizal',         'Partido Díaz',          'parque',    31.7281,  -106.4689),
('Parque Central',             'Centro',                 'parque',    31.7380,  -106.4850),
('Parque Borunda',             'Zona Centro',            'parque',    31.7350,  -106.4780),
('Parque Insurgentes',         'Insurgentes',            'parque',    31.7200,  -106.4600),
('Parque de los Nogales',      'Los Nogales',            'parque',    31.7500,  -106.4400),
('Parque Oriente',             'Partido Romero',         'parque',    31.6960,  -106.3900),
('Parque de los Patos',        'Partido Romero',         'parque',    31.6044,  -106.3377),
('Estadio Olímpico Benito Juárez', 'Centro',             'deportivo', 31.7260,  -106.4820),
('Unidad Deportiva Plutarco',  'Plutarco Elías Calles',  'deportivo', 31.7100,  -106.4200),
('Plaza de Armas',             'Centro Histórico',       'plaza',     31.7390,  -106.4870),
('Parque Eco 2000',            'Eco 2000',               'parque',    31.6700,  -106.3800),
('Parque La Cantera',          'La Cantera',             'parque',    31.7600,  -106.4300);
