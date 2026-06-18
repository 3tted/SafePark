<div align="center">

# 🌿 SafePark

**Descubre. Evalúa. Explora con seguridad.**

[![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)](https://developer.mozilla.org/es/docs/Web/HTML)
[![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)](https://developer.mozilla.org/es/docs/Web/CSS)
[![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://developer.mozilla.org/es/docs/Web/JavaScript)
[![Mapbox](https://img.shields.io/badge/Mapbox-000000?style=for-the-badge&logo=mapbox&logoColor=white)](https://www.mapbox.com/)
[![OpenWeather](https://img.shields.io/badge/OpenWeather-EB6E4B?style=for-the-badge&logo=openweathermap&logoColor=white)](https://openweathermap.org/)

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

## 🧱 Stack tecnológico

| Capa | Tecnología |
|---|---|
| Frontend | HTML5 + CSS3 + JavaScript (Vanilla) |
| Mapas | Mapbox GL JS |
| Clima | OpenWeatherMap API |
| Lugares | Google Places API |
| Backend / API REST | API propia (`api.safepark.com.mx/v1`) |
| Tipografía | Nunito (Google Fonts) |
| Control de versiones | Git / GitHub |

---

## ✨ Funcionalidades

### 🔐 Autenticación
- Inicio de sesión con correo y contraseña
- Registro de nuevos usuarios con validación de campos
- Sesión persistente (pendiente integración con API de usuarios)

### 🗺️ Mapa interactivo
- Visualización de áreas verdes en Ciudad Juárez con Mapbox GL JS
- Filtros por tipo: Parques, Deportivo, Plaza
- Semáforo de seguridad por área: 🟢 Seguro / 🟡 Precaución / 🔴 Riesgo

### 🏠 Dashboard principal
- Buscador de áreas verdes con resultados en mapa
- Estadísticas en tiempo real: áreas registradas, reportes y usuarios
- Lista de áreas destacadas con badge de seguridad

### 📋 Reportes comunitarios
- Los usuarios pueden reportar incidentes o condiciones en áreas verdes
- Los reportes alimentan el semáforo de seguridad de cada área

### 🌤️ Clima en tiempo real *(pendiente)*
- Condiciones climáticas actuales integradas con OpenWeatherMap
- Visible en el detalle de cada área verde

---

## 📁 Estructura del proyecto

```
SafePark/
├── CSS/styles.css          ← estilos compartidos (reset, navbar, variables)
├── Javascript/archivo.js   ← JS compartido
├── Login/
│   ├── index.html
│   └── style.css
├── Registro/
│   ├── index.html
│   └── style.css
├── Home/
│   ├── index.html
│   └── style.css
├── Mapa/
│   ├── index.html
│   ├── style.css
│   └── archivo.js          ← geolocalización + Google Maps embed
└── README.md
```

---

## 🎨 Paleta de colores

| Variable | Color | Hex |
|---|---|---|
| `--g1` | Verde oscuro | `#1B4332` |
| `--g2` | Verde medio | `#2D6A4F` |
| `--g3` | Verde principal | `#52B788` |
| `--g4` | Verde claro | `#95D5B2` |
| `--g5` | Verde muy claro | `#D8F3DC` |
| `--safe` | Seguro | `#40916C` |
| `--warn` | Precaución | `#F4A261` |
| `--risk` | Riesgo | `#E63946` |

---

## 🚦 Semáforo de seguridad

El nivel de seguridad de cada área se calcula con base en los reportes de la comunidad:

| Nivel | Color | Significado |
|---|---|---|
| 🟢 Seguro | `#40916C` | Área sin reportes negativos recientes |
| 🟡 Precaución | `#F4A261` | Reportes menores o condiciones a mejorar |
| 🔴 Riesgo | `#E63946` | Reportes activos de inseguridad o peligro |

---

## 📊 Estado del proyecto

| Vista / Módulo | Estado |
|---|---|
| Login | ✅ Prototipo listo |
| Registro | ✅ Prototipo listo |
| Home / Dashboard | ✅ Prototipo listo |
| Mapa interactivo | ✅ Prototipo listo |
| Integración Mapbox GL JS | ⏳ Pendiente |
| API de usuarios | ⏳ Pendiente |
| API de áreas y reportes | ⏳ Pendiente |
| OpenWeatherMap | ⏳ Pendiente |
| Google Places | ⏳ Pendiente |
| Perfil de usuario | ⏳ Pendiente |
| Panel de administración | ⏳ Pendiente |

---

## 🛠️ Cómo correr el proyecto

No requiere instalación de dependencias. Solo necesitas un navegador y un servidor local.

### Opción 1 — VS Code + Live Server (recomendado)
1. Clona el repositorio:
   ```bash
   git clone https://github.com/[usuario]/SafePark.git
   ```
2. Abre la carpeta `SafePark/` en VS Code.
3. Instala la extensión **Live Server** (ritwickdey.liveserver).
4. Clic derecho en `index.html` → **Open with Live Server**.

### Opción 2 — Doble clic
Abre cualquier archivo `.html` directamente desde el explorador de archivos.  
> ⚠️ Algunos recursos pueden no cargar correctamente sin servidor local.

---

## 🔑 Variables de entorno *(para cuando se integren las APIs)*

Crea un archivo `.env` o configura las claves directamente en los archivos de `/api/`:

```
MAPBOX_TOKEN=pk.xxxxxxxxxxxxxxxxxxxxxxxx
OPENWEATHER_API_KEY=xxxxxxxxxxxxxxxxxxxxxxxx
GOOGLE_PLACES_KEY=xxxxxxxxxxxxxxxxxxxxxxxx
```

> ⚠️ Nunca subas claves reales al repositorio. Están incluidas en `.gitignore`.

---

<div align="center">

Hecho con 💚 y Vanilla JS por el equipo SafePark · UTCJ 2026

</div>
