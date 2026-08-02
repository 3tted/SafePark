<?php
// Plantilla de configuración de las APIs de SafePark.
//
// Copia este archivo como config_api.php en el servidor y pon tus valores.
// Solo hace falta en producción: en local, api.php usa localhost:3001 y 3002
// por defecto y el secreto no es necesario.
//
// SafePark tiene dos APIs propias, cada una desplegada como su propio servicio:
//   Usuarios -> cuentas, autenticación, perfiles y roles
//   Datos    -> áreas, reportes, eventos, comentarios, reacciones y favoritos
//
// CFG_API_SECRET debe coincidir con la variable API_SECRET de AMBOS servicios.

define('CFG_API_USUARIOS', 'https://tu-api-usuarios.up.railway.app/api');
define('CFG_API_DATOS',    'https://tu-api-datos.up.railway.app/api');
define('CFG_API_SECRET',   'tu_secreto_aqui');
