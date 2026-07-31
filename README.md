<div align="center">

# 🌿 SafePark

**Descubre. Evalúa. Explora con seguridad.**

[![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![Node.js](https://img.shields.io/badge/Node.js-339933?style=for-the-badge&logo=node.js&logoColor=white)](https://nodejs.org/)
[![Express](https://img.shields.io/badge/Express-000000?style=for-the-badge&logo=express&logoColor=white)](https://expressjs.com/)
[![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://developer.mozilla.org/es/docs/Web/JavaScript)

> Proyecto integrador de la asignatura **Desarrollo Web Orientado a Servicios** — UTCJ

Plataforma web para **descubrir y evaluar áreas verdes seguras** en Ciudad Juárez, Chihuahua. Los usuarios pueden explorar parques, plazas y espacios verdes con un semáforo de seguridad en tiempo real basado en reportes de la comunidad.

</div>

---

## 👥 Equipo de desarrollo

| Integrante |
|---|
| Edgar |
| Arleth |
| Pedro |
| Ivan |

---

## 🧱 Arquitectura orientada a servicios (SOA)

SafePark separa el frontend del backend mediante una API REST independiente:

```
Navegador / PHP (frontend)
        ↕ HTTP
API REST — Node.js + Express (puerto 3000)
        ↕ mysql2
Base de datos — MySQL (safepark_db)
```

- **PHP** maneja autenticación con sesiones y renderiza el HTML
- **Node.js + Express** expone todos los datos vía API REST
- **PHP no consulta la base de datos directamente** — todo pasa por el API

---

## 🗂️ Stack tecnológico

| Capa | Tecnología |
|---|---|
| Frontend / vistas | PHP + HTML5 + CSS3 + JavaScript (Vanilla) |
| Mapas | Leaflet.js + OpenStreetMap |
| Clima | OpenWeatherMap API |
| Geocodificación / Autocompletado | Nominatim (OpenStreetMap) |
| Backend / API REST | Node.js + Express (puerto 3000) |
| Base de datos | MySQL (XAMPP) |
| ORM / driver | mysql2 |
| Tipografía | Nunito (Google Fonts) |
| Control de versiones | Git / GitHub |

---

## ✨ Funcionalidades

### 🔐 Autenticación
- Inicio de sesión con correo y contraseña
- Registro de nuevos usuarios
- Sesiones PHP persistentes
- Control de roles: `usuario` / `admin`

### 🗺️ Mapa interactivo
- Visualización de áreas verdes en Ciudad Juárez con Leaflet.js
- Filtros por tipo: Parques, Deportivos, Plazas
- Semáforo de seguridad calculado con reportes de la comunidad
- Agregar y editar áreas directamente desde el mapa

### 🏠 Dashboard principal
- Buscador con autocompletado via Nominatim
- Estadísticas en tiempo real: áreas, reportes y usuarios
- Clima actual (OpenWeatherMap)

### 📋 Reportes comunitarios
- Enviar reportes de incidentes, condiciones o sugerencias por área
- Los reportes alimentan el semáforo de seguridad
- Panel lateral con historial de reportes propios

### 🌐 Comunidad
- Feed de actividad: reportes y eventos de la comunidad
- Reacciones con emojis estilo Discord (toggle, conteo en tiempo real)
- Comentarios por reporte/evento
- Crear eventos comunitarios
- Top contribuidores

### 👤 Perfil de usuario
- Ver puntos acumulados por reportes
- Historial de reportes propios

### ⚙️ Panel de administración
- Estadísticas generales (usuarios, reportes, pendientes, áreas)
- Gestión de reportes: cambiar estado (pendiente / en proceso / resuelto)
- Gestión de usuarios: cambiar rol
- Gestión de áreas: editar y eliminar

---

## 📡 API REST — Endpoints

Base URL: `http://localhost:3000/api`

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/areas` | Todas las áreas con score de seguridad |
| GET | `/areas/:id` | Área específica |
| POST | `/areas` | Crear área (con foto) |
| PUT | `/areas/:id` | Actualizar área (con foto) |
| DELETE | `/areas/:id` | Eliminar área y registros relacionados |
| GET | `/areas/stats/resumen` | Estadísticas generales |
| GET | `/reportes` | Todos los reportes recientes |
| GET | `/reportes/area/:id` | Reportes de un área |
| GET | `/reportes/usuario/:id` | Reportes de un usuario |
| POST | `/reportes` | Crear reporte |
| PUT | `/reportes/:id` | Actualizar estado de reporte |
| GET | `/usuarios` | Lista de usuarios |
| GET | `/usuarios/:id` | Perfil con puntos |
| PUT | `/usuarios/:id/rol` | Cambiar rol de usuario |
| GET | `/eventos` | Todos los eventos |
| POST | `/eventos` | Crear evento |
| GET | `/reacciones` | Reacciones por reporte/evento |
| POST | `/reacciones` | Agregar o quitar reacción (toggle) |
| GET | `/comentarios` | Comentarios por reporte/evento |
| POST | `/comentarios` | Agregar comentario |
| GET | `/health` | Estado del servidor |

---

## 📁 Estructura del proyecto

```
SafePark/
├── api/                            ← API REST (Node.js + Express)
│   ├── index.js                    ← Servidor principal (puerto 3000)
│   ├── db.js                       ← Pool de conexión MySQL (mysql2)
│   ├── .env                        ← Variables de entorno (no subir a git)
│   ├── package.json
│   └── routes/
│       ├── areas.js                ← GET, POST, PUT, DELETE + stats
│       ├── reportes.js             ← GET, POST, PUT (estado)
│       ├── usuarios.js             ← GET, PUT (rol)
│       ├── eventos.js              ← GET, POST
│       ├── reacciones.js           ← GET, POST (toggle)
│       └── comentarios.js          ← GET, POST
├── Admin/                          ← Panel de administración
│   ├── index.php                   ← Vista principal del admin
│   ├── actualizar_reporte.php      ← Cambia estado de reporte via API
│   ├── actualizar_area.php         ← Edita área via API
│   ├── cambiar_rol.php             ← Cambia rol de usuario via API
│   ├── eliminar_area.php           ← Elimina área via API
│   ├── admin.js
│   └── style.css

├── Comunidad/                      ← Feed, eventos, reacciones, comentarios
│   ├── index.php                   ← Feed de actividad
│   ├── guardar_evento.php          ← Crea evento via API
│   ├── comunidad.js                ← Reacciones y comentarios (llama API directo)
│   └── style.css
├── Explorar/                       ← Búsqueda y filtrado de áreas
│   ├── index.php
│   ├── explorar.js
│   └── style.css
├── Home/                           ← Dashboard principal
│   ├── index.php
│   ├── clima.php                   ← Proxy OpenWeatherMap
│   ├── nominatim_proxy.php         ← Proxy Nominatim (autocompletado)
│   └── style.css
├── Login/                          ← Autenticación
│   ├── index.php
│   ├── login.php
│   ├── logout.php
│   └── style.css
├── Mapa/                           ← Mapa interactivo (Leaflet.js)
│   ├── index.php
│   ├── archivo.js                  ← Lógica del mapa y marcadores
│   ├── guardar_area.php            ← Crea área via API
│   ├── actualizar_area_usuario.php ← Edita área (usuario) via API
│   └── style.css
├── Perfil/                         ← Perfil de usuario
│   ├── index.php
│   ├── editar.php
│   ├── actualizar.php
│   ├── perfil.js
│   └── style.css
├── Registro/                       ← Registro de nuevos usuarios
│   ├── index.php
│   ├── registro.php
│   └── style.css
├── Reportar/                       ← Envío de reportes
│   ├── index.php
│   ├── guardar_reporte.php         ← Envía reporte via API
│   ├── reportar.js
│   └── style.css
├── Assets/                         ← Logo, fotos de áreas
├── CSS/styles.css                  ← Estilos globales (variables, navbar, reset)
├── database/
│   └── conexion.php                ← Conexión PHP (solo para auth/sesiones)
└── includes/
    ├── api.php                     ← Helpers: api_get(), api_post(), api_put()
    ├── auth.php                    ← Funciones de sesión y permisos
    ├── navbar.php                  ← Navbar compartida
    ├── modal_editar_area.php       ← Modal reutilizable para editar áreas
    ├── config_clima.php            ← API key de OpenWeatherMap (no subir a git)
    └── config_clima.example.php    ← Plantilla de configuración del clima
```

---

## 🚀 Cómo levantar el proyecto

### Requisitos
- XAMPP (Apache + MySQL)
- Node.js

### Pasos

1. **Iniciar XAMPP** — encender Apache y MySQL

2. **Levantar la API REST:**
```bash
cd SafePark/api
node index.js
```
El API corre en `http://localhost:3000`

3. **Abrir el proyecto** en el navegador:
```
http://localhost/SafePark/Login/index.php
```

---

## 🚦 Semáforo de seguridad

El score de cada área se calcula automáticamente en la API según sus reportes:

```
score = 100 × 0.92^incidentes × 0.95^condiciones × 0.98^sugerencias
```

| Nivel | Score | Color |
|---|---|---|
| 🟢 Seguro | ≥ 70 | `#40916C` |
| 🟡 Precaución | 40 – 69 | `#F4A261` |
| 🔴 Riesgo | < 40 | `#E63946` |

---

## 🎨 Paleta de colores

| Variable | Descripción | Color | Hex |
|---|---|---|---|
| `--g1` | Verde muy oscuro (navbar, fondos) | 🟢 | `#1B4332` |
| `--g2` | Verde oscuro (gradientes) | 🟢 | `#2D6A4F` |
| `--g3` | Verde medio (botones, accents) | 🟢 | `#52B788` |
| `--g4` | Verde claro (hover, bordes) | 🟢 | `#95D5B2` |
| `--g5` | Verde muy claro (fondos suaves) | 🟢 | `#D8F3DC` |
| `--safe` | Verde seguro (semáforo) | 🟢 | `#40916C` |
| `--warn` | Naranja precaución (semáforo) | 🟠 | `#F4A261` |
| `--risk` | Rojo riesgo (semáforo) | 🔴 | `#E63946` |

---

## 📊 Estado del proyecto

| Módulo | Estado |
|---|---|
| Login / Registro | ✅ Completo |
| Home / Dashboard | ✅ Completo |
| Mapa interactivo | ✅ Completo |
| Explorar áreas | ✅ Completo |
| Reportar | ✅ Completo |
| Perfil de usuario | ✅ Completo |
| Comunidad (feed) | ✅ Completo |
| Reacciones emoji | ✅ Completo |
| Comentarios | ✅ Completo |
| Eventos comunitarios | ✅ Completo |
| Panel de administración | ✅ Completo |
| API REST (Node.js) | ✅ Completo |
| Clima en tiempo real (OpenWeatherMap) | ✅ Completo |
| Geocodificación (Nominatim) | ✅ Completo |
| Autenticación JWT en API | ⏳ Pendiente |
| Subida de foto en reportes | ⏳ Pendiente |
