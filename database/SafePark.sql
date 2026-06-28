CREATE DATABASE IF NOT EXISTS safepark_db;
USE safepark_db;

CREATE TABLE USUARIO (
    id_usuario INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    contrasena_hash VARCHAR(255) NOT NULL,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE AREA (
    id_area INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    tipo ENUM('parque', 'plaza', 'jardin', 'otro') NOT NULL,
    direccion VARCHAR(200),
    horario VARCHAR(100),
    latitud DECIMAL(10, 7),
    longitud DECIMAL(10, 7)
);

CREATE TABLE FAVORITO (
    id_usuario INT NOT NULL,
    id_area INT NOT NULL,
    PRIMARY KEY (id_usuario, id_area),
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario),
    FOREIGN KEY (id_area) REFERENCES AREA(id_area)
);

CREATE TABLE REPORTE (
    id_reporte INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    id_area INT NOT NULL,
    tipo ENUM('basura', 'dano', 'iluminacion', 'otro') NOT NULL,
    descripcion TEXT,
    estado ENUM('pendiente', 'en_proceso', 'resuelto') DEFAULT 'pendiente',
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario),
    FOREIGN KEY (id_area) REFERENCES AREA(id_area)
);

CREATE TABLE EVENTO (
    id_evento INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    id_area INT NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario),
    FOREIGN KEY (id_area) REFERENCES AREA(id_area)
);

CREATE TABLE EVENTO_ASISTENTE (
    id_evento INT NOT NULL,
    id_usuario INT NOT NULL,
    PRIMARY KEY (id_evento, id_usuario),
    FOREIGN KEY (id_evento) REFERENCES EVENTO(id_evento),
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario)
);