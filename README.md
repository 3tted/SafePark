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

### 🟢 Proyecto terminado y en línea

**[safepark.rf.gd](https://safepark.rf.gd)**

Frontend en InfinityFree · APIs y base de datos en Railway

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
        ┌───────────────┴───────────────┐
        ↓                               ↓
API Usuarios (3001)            API Datos (3002)
auth, usuarios                 areas, reportes, eventos,
                               comentarios, reacciones, favoritos
        └───────────────┬───────────────┘
                        ↕ mysql2
              Base de datos — MySQL
```

SafePark expone **dos APIs propias**, cada una desplegada como un servicio
independiente:

- **API de Usuarios** — cuentas, autenticación (contraseña y Google), perfiles,
  roles y actividad. Es la única que maneja contraseñas: los hashes se comparan
  ahí dentro y nunca salen del servicio.
- **API de Datos** — áreas verdes, reportes, eventos, comentarios, reacciones y
  favoritos. Calcula el semáforo de seguridad de cada área.

El PHP no sabe cuál servicio atiende cada llamada: la función `api_url()` de
`includes/api.php` lo resuelve por el prefijo de la ruta.

- **PHP** mantiene la sesión del usuario y renderiza el HTML
- **Node.js + Express** expone todos los datos vía API REST y es el único que
  habla con MySQL — incluida la verificación de contraseñas
- **PHP no consulta la base de datos directamente**: no existe ninguna conexión
  MySQL en el código PHP, todo pasa por el API

Esto permite alojar el frontend y el API en servidores distintos, y que la base
de datos nunca quede expuesta a internet.

---

## 🗂️ Stack tecnológico

| Capa | Tecnología |
|---|---|
| Frontend / vistas | PHP + HTML5 + CSS3 + JavaScript (Vanilla) |
| Mapas | Leaflet.js + OpenStreetMap |
| Clima | OpenWeatherMap API |
| Geocodificación / Autocompletado | Nominatim (OpenStreetMap) |
| Rutas y navegación | OpenRouteService |
| Inicio de sesión externo | Google OAuth 2.0 |
| Backend / APIs REST | Node.js + Express (dos servicios: 3001 y 3002) |
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
- Marcadores que muestran el puntaje del semáforo dentro del círculo
- Filtros por tipo: Parques, Deportivos, Plazas
- Semáforo de seguridad calculado con reportes de la comunidad
- Agregar y editar áreas directamente desde el mapa

### 🧭 Cómo llegar
- Trazado de ruta desde tu ubicación hasta cualquier área (OpenRouteService)
- Tres medios de transporte: 🚶 a pie, 🚗 en carro, 🚲 en bici
- Distancia y tiempo estimado por medio
- Punto azul que sigue tu posición mientras te mueves
- Respaldo automático con Google Maps si falla la geolocalización o el servicio

### 🏠 Dashboard principal
- Buscador con autocompletado via Nominatim
- Estadísticas en tiempo real: áreas, reportes y usuarios
- Clima actual (OpenWeatherMap)

### 🔎 Explorar
- Tarjetas con foto, nivel de seguridad, **puntaje** y **número de reportes**
- Filtros cruzados: texto, tipo de área y nivel de seguridad
- Buscador único que agrupa las áreas de SafePark y los lugares de la ciudad
- Favoritos y detalle del área con sus reportes paginados

### 📋 Reportes comunitarios
- Enviar reportes de incidentes, condiciones o sugerencias por área
- Foto opcional en cada reporte
- Los reportes alimentan el semáforo de seguridad
- Panel lateral con historial de reportes propios

### 🌐 Comunidad
- Feed de actividad ordenado por cuándo ocurrió cada acción
- Reacciones con emojis estilo Discord (toggle, conteo en tiempo real)
- Comentarios por reporte/evento, con el conteo visible antes de abrirlos
- Estado de cada reporte en el feed: ⏳ Pendiente / 🔧 En proceso / ✅ Resuelto
- Crear eventos comunitarios, con la fecha programada visible
- Tabla de colaboradores calculada con los puntos reales de cada persona
- Paginación de 10 en 10, sin recargar la página

### 👤 Perfil de usuario
- Puntos acumulados y desglose de cómo se ganan
- Historial de reportes propios con su estado actual
- Áreas favoritas y actividad reciente

### ⚙️ Panel de administración
- Estadísticas generales (usuarios, reportes, pendientes, áreas)
- Gestión de reportes: cambiar estado (pendiente / en proceso / resuelto)
- Gestión de usuarios: cambiar rol
- Gestión de áreas: editar y eliminar

---

## 📡 API REST — Endpoints

En local: **Usuarios** en `http://localhost:3001/api` y **Datos** en
`http://localhost:3002/api`.

Las rutas marcadas con 🔒 exigen la cabecera `X-API-Secret` y solo las llama el
servidor PHP. Las de lectura son públicas porque el JavaScript del navegador las
consume directamente.

Las rutas de `/auth` y `/usuarios` las atiende la **API de Usuarios**; todas las
demás, la **API de Datos**.

| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | `/auth/login` 🔒 | Verifica credenciales y devuelve el usuario |
| POST | `/auth/google` 🔒 | Entra o registra con una cuenta de Google verificada |
| GET | `/areas` | Todas las áreas con score de seguridad |
| GET | `/areas/:id` | Área específica |
| POST | `/areas` 🔒 | Crear área |
| PUT | `/areas/:id` 🔒 | Actualizar área |
| DELETE | `/areas/:id` 🔒 | Eliminar área y registros relacionados |
| GET | `/areas/stats/resumen` | Estadísticas generales |
| GET | `/reportes` | Todos los reportes recientes |
| GET | `/reportes/area/:id` | Reportes de un área |
| GET | `/reportes/usuario/:id` | Reportes de un usuario |
| POST | `/reportes` 🔒 | Crear reporte |
| PUT | `/reportes/:id` 🔒 | Actualizar estado de reporte |
| GET | `/usuarios` | Lista de usuarios |
| GET | `/usuarios/:id` | Perfil con puntos y datos personales |
| GET | `/usuarios/:id/rol` | Rol de un usuario (control de permisos) |
| GET | `/usuarios/:id/actividad` | Reportes, comentarios y reacciones de un usuario |
| POST | `/usuarios` 🔒 | Registrar usuario nuevo |
| PUT | `/usuarios/:id` 🔒 | Actualizar perfil (nombre, correo, contraseña, foto) |
| PUT | `/usuarios/:id/rol` 🔒 | Cambiar rol de usuario |
| GET | `/eventos` | Todos los eventos |
| POST | `/eventos` 🔒 | Crear evento |
| GET | `/reacciones` | Reacciones de un reporte/evento |
| GET | `/reacciones/agrupadas` | Todas las reacciones contadas (feed) |
| POST | `/reacciones` | Agregar o quitar reacción (toggle) |
| GET | `/comentarios` | Comentarios por reporte/evento |
| GET | `/comentarios/agrupadas` | Cuántos comentarios tiene cada publicación (feed) |
| POST | `/comentarios` | Agregar comentario |
| GET | `/favoritos/:id_usuario` | Áreas favoritas de un usuario |
| POST | `/favoritos` | Guardar o quitar favorito (toggle) |
| GET | `/rutas` | Trayecto entre dos puntos (proxy de OpenRouteService) |
| GET | `/health` | Estado del servidor |

---

## 📁 Estructura del proyecto

```
SafePark/
├── api-usuarios/                   ← API 1: cuentas y autenticación
│   ├── index.js                    ← Servidor (puerto 3001)
│   ├── db.js                       ← Pool de conexión MySQL (mysql2)
│   ├── .env                        ← Variables de entorno (no subir a git)
│   ├── package.json                ← incluye bcryptjs
│   ├── middleware/
│   │   └── solo_php.js             ← Exige X-API-Secret en las escrituras
│   └── routes/
│       ├── auth.js                 ← POST /login y /google
│       └── usuarios.js             ← registro, perfil, roles, actividad
├── api-datos/                      ← API 2: contenido de la plataforma
│   ├── index.js                    ← Servidor (puerto 3002)
│   ├── db.js
│   ├── .env
│   ├── package.json
│   ├── middleware/
│   │   └── solo_php.js
│   └── routes/
│       ├── areas.js                ← GET, POST, PUT, DELETE + semáforo
│       ├── reportes.js             ← GET, POST, PUT (estado)
│       ├── eventos.js              ← GET, POST
│       ├── comentarios.js          ← GET, POST
│       ├── reacciones.js           ← GET, POST (toggle)
│       ├── favoritos.js            ← GET, POST (toggle)
│       └── rutas.js                ← Proxy de OpenRouteService (esconde la llave)
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
│   └── style.css
├── Login/                          ← Autenticación
│   ├── index.php
│   ├── login.php
│   ├── logout.php
│   ├── google.php                  ← Inicia el flujo OAuth de Google
│   ├── google_callback.php         ← Recibe la respuesta y valida el state
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
├── CSS/                            ← Estilos globales
│   ├── styles.css                  ← Agregador (importa los otros dos)
│   ├── base.css                    ← Variables de color, tipografía, reset
│   └── navbar.css                  ← Barra de navegación
├── database/
│   └── SafePark.sql                ← Esquema completo de la base de datos
└── includes/
    ├── api.php                     ← Helpers: api_get/post/put/delete + secreto
    ├── auth.php                    ← Sesión y permisos (consulta el rol al API)
    ├── fotos.php                   ← Guarda y valida las fotos subidas
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

2. **Levantar las dos APIs**, cada una en su propia terminal:
```bash
cd SafePark/api-usuarios
npm install
node index.js
```
```bash
cd SafePark/api-datos
npm install
node index.js
```
Quedan en `http://localhost:3001` y `http://localhost:3002`.

3. **Abrir el proyecto** en el navegador:
```
http://localhost/SafePark/Login/index.php
```

En local no hace falta configurar nada más: el PHP usa esos dos puertos por
defecto y las APIs dejan pasar las escrituras sin secreto (avisan al arrancar).

### Variables de entorno para producción

| Dónde | Variable | Para qué |
|---|---|---|
| Ambas APIs | `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASSWORD`, `DB_NAME` | Conexión a MySQL |
| Ambas APIs | `API_SECRET` | Protege las rutas de escritura (el mismo valor en las dos) |
| API de Datos | `ORS_API_KEY` | Llave de OpenRouteService para el botón "Cómo llegar". Si falta, el mapa ofrece Google Maps en su lugar |
| PHP | `SAFEPARK_API_USUARIOS` | URL de la API de Usuarios |
| PHP | `SAFEPARK_API_DATOS` | URL de la API de Datos |
| PHP | `SAFEPARK_API_SECRET` | Debe coincidir con `API_SECRET` |

En el hosting PHP se definen con un `.htaccess` en la raíz, o —si el hosting no
soporta `SetEnv`, como InfinityFree— con el archivo `includes/config_api.php`
(ver `config_api.example.php`).

```apache
SetEnv SAFEPARK_API_USUARIOS https://tu-api-usuarios.up.railway.app/api
SetEnv SAFEPARK_API_DATOS    https://tu-api-datos.up.railway.app/api
SetEnv SAFEPARK_API_SECRET   tu_secreto
```

---

## 🚦 Semáforo de seguridad

El score de cada área se calcula automáticamente en la API según sus reportes:

```
score = 100 × 0.97^incidentes × 0.98^condiciones × 0.99^sugerencias
```

| Nivel | Score | Color |
|---|---|---|
| 🟢 Seguro | ≥ 70 | `#40916C` |
| 🟡 Precaución | 40 – 69 | `#F4A261` |
| 🔴 Riesgo | < 40 | `#E63946` |

Cada reporte descuenta un porcentaje distinto según su gravedad: un **incidente**
3 %, una **condición** 2 % y una **sugerencia** 1 %. Se multiplica en lugar de
restar para que el castigo sea proporcional —el primer incidente pesa más que el
décimo— y para que el puntaje nunca caiga a números negativos. Hay un piso de 10:
un cero absoluto daría a entender que no hay información, cuando es lo contrario.

Con estos pesos hacen falta **12 incidentes para salir del verde y 31 para llegar
al rojo**, calibrado así para que las pruebas en grupo no derrumben todas las
zonas de golpe.

El score **no se guarda en la base de datos**: se calcula al vuelo cada vez que se
piden las áreas, así nunca queda desactualizado. El estado de un reporte
(pendiente / en proceso / resuelto) no lo modifica, porque el semáforo mide
cuánto ha ocurrido en un área, no qué tan bien la han atendido.

---

## ⭐ Puntos de la comunidad

Los puntos premian a quien aporta; el semáforo califica al lugar. Un mismo reporte
hace las dos cosas: baja el puntaje del área y sube los puntos de quien lo envió.

| Aportación | Puntos |
|---|---|
| 🚨 Incidente de seguridad | 15 |
| 🏚️ Condición del área | 10 |
| 💡 Sugerencia | 5 |

No valen igual porque no cuestan igual: un incidente exige salir a verificar algo
delicado, una condición es constatar un desperfecto y una sugerencia es una idea.
Al igual que el score, se calculan al vuelo y no se almacenan.

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

> **Proyecto terminado.** Todos los módulos planteados están construidos,
> desplegados y funcionando en [safepark.rf.gd](https://safepark.rf.gd).

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
| Subida de foto en reportes | ✅ Completo |
| Favoritos | ✅ Completo |
| Filtros por nivel de seguridad | ✅ Completo |
| Protección del API con secreto compartido | ✅ Completo |
| Autenticación centralizada en el API | ✅ Completo |
| Inicio de sesión con Google (OAuth 2.0) | ✅ Completo |
| Rutas «Cómo llegar» con tres medios de transporte | ✅ Completo |
| Estado de reportes visible para quien los envió | ✅ Completo |
| Conteo de comentarios en el feed | ✅ Completo |
| Tabla de colaboradores por puntos | ✅ Completo |
| Paginación en Comunidad y Explorar | ✅ Completo |
| Diseño adaptable a celular | ✅ Completo |
| Despliegue del API y la base de datos | ✅ En Railway |
| Despliegue del frontend PHP | ✅ En InfinityFree |

### Ideas para más adelante

Nada de esto hace falta para que la plataforma funcione; queda anotado por si el
proyecto se retoma.

| Idea | Por qué |
|---|---|
| Tokens JWT por usuario | Hoy el secreto compartido cubre el caso: el PHP es el único cliente que escribe |
| Moderación de reportes falsos | Un reporte falso baja el puntaje igual que uno real; hoy solo un admin puede cerrarlo |
| Que el puntaje se recupere con el tiempo | Los reportes resueltos siguen contando, así que un área solo puede bajar |
| Almacenamiento externo para las fotos | Hoy viven en el disco del hosting PHP |
| Llave foránea en `area.id_usuario` | Es la única de las 17 relaciones sin proteger |
