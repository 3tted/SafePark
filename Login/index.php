<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - SafePark</title>
    <link rel="icon" href="../Assets/logo.png">
    <link rel="stylesheet" href="../CSS/styles.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="form-page">
        <div class="form-box">
            <div class="form-logo">
                <img src="../Assets/logo.png" height="100" alt="SafePark logo">
                <h1>Safe<span>Park</span></h1>
            </div>
            <p class="form-subtitle">Inicia sesión para explorar áreas verdes seguras</p>
            <form action="login.php" method="POST">
                <div class="form-group">
                    <label class="form-label">Correo electrónico</label>
                    <input class="form-input" type="email" name="email" placeholder="tucorreo@ejemplo.com" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Contraseña</label>
                    <input class="form-input" type="password" name="password" placeholder="........" required>
                </div>
                <?php
                // Mensajes según de dónde venga el error
                $err = $_GET['error'] ?? '';
                $mensajes = [
                    'credenciales'        => 'Correo o contraseña incorrectos.',
                    'google'              => 'No se pudo completar el inicio con Google. Intenta de nuevo.',
                    'google_cancelado'    => 'Cancelaste el inicio de sesión con Google.',
                    'google_sin_verificar'=> 'Ese correo de Google no está verificado.',
                    'google_config'       => 'El inicio con Google no está configurado en el servidor.',
                    'servidor'            => 'Error del servidor, intenta de nuevo.',
                ];
                if ($err !== ''): ?>
                    <p style="color:#E63946;font-size:13px;margin-bottom:12px;">
                        <?= htmlspecialchars($mensajes[$err] ?? $mensajes['credenciales']) ?>
                    </p>
                <?php endif; ?>
                <button class="btn-primary" type="submit">Iniciar sesión</button>
            </form>

            <div class="separador-o"><span>o</span></div>

            <a class="btn-google" href="google.php">
                <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true">
                    <path fill="#4285F4" d="M45.1 24.5c0-1.6-.1-3.1-.4-4.5H24v8.5h11.8c-.5 2.7-2 5-4.3 6.6v5.5h7c4.1-3.8 6.6-9.4 6.6-16.1z"/>
                    <path fill="#34A853" d="M24 46c5.8 0 10.7-1.9 14.3-5.2l-7-5.5c-1.9 1.3-4.4 2.1-7.3 2.1-5.6 0-10.4-3.8-12.1-8.9H4.7v5.6C8.3 41.3 15.6 46 24 46z"/>
                    <path fill="#FBBC05" d="M11.9 28.5c-.4-1.3-.7-2.7-.7-4.5s.3-3.2.7-4.5v-5.6H4.7C3.1 17.1 2.2 20.4 2.2 24s.9 6.9 2.5 10.1l7.2-5.6z"/>
                    <path fill="#EA4335" d="M24 9.5c3.2 0 6 1.1 8.2 3.2l6.2-6.2C34.7 3.1 29.8 1 24 1 15.6 1 8.3 5.7 4.7 13.9l7.2 5.6c1.7-5.1 6.5-8.9 12.1-8.9z"/>
                </svg>
                Continuar con Google
            </a>

            <div class="form-footer">
                ¿No tienes cuenta? <a href="../Registro/">Regístrate aquí</a>
            </div>
        </div>
    </div>
</body>
</html>
