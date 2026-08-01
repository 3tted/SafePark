<?php
// Plantilla de configuración de "Iniciar sesión con Google".
//
// Copia este archivo como config_google.php y pon tus credenciales. Se obtienen
// en console.cloud.google.com → APIs y servicios → Credenciales → ID de cliente
// de OAuth (tipo "Aplicación web").
//
// La URI de redireccionamiento debe estar dada de alta en esa misma pantalla y
// coincidir EXACTAMENTE con la de aquí, incluyendo https y el dominio.
//
// Este archivo no se sube al repositorio: contiene el secreto de cliente.

define('GOOGLE_CLIENT_ID',     'tu-id-de-cliente.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'tu-secreto-de-cliente');
define('GOOGLE_REDIRECT_URI',  'https://safepark.rf.gd/Login/google_callback.php');
