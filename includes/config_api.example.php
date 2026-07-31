<?php
// Plantilla de configuración del API.
//
// Copia este archivo como config_api.php en el servidor y pon tus valores.
// Solo hace falta en producción: en local, api.php usa localhost:3000 por
// defecto y el secreto no es necesario.
//
// CFG_API_SECRET debe coincidir con la variable API_SECRET del servicio
// del API (en Railway, pestaña Variables).

define('CFG_API_BASE',   'https://tu-api.up.railway.app/api');
define('CFG_API_SECRET', 'tu_secreto_aqui');
