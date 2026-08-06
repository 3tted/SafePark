<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - SafePark</title>
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
            <p class="form-subtitle">Crea tu cuenta y comienza a explorar</p>
            <form action="registro.php" method="POST">
                <div class="form-group">
                    <label class="form-label">Nombre</label>
                    <input class="form-input" type="text" name="nombre" placeholder="Tu nombre" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Correo electrónico</label>
                    <input class="form-input" type="email" name="email" placeholder="tucorreo@ejemplo.com" required>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Contraseña</label>
                        <input class="form-input" type="password" name="password" placeholder="........" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirmar</label>
                        <input class="form-input" type="password" name="confirmar" placeholder="........" required>
                    </div>
                </div>
                <div class="form-check">
                    <input type="checkbox" id="terminos" required>
                    <label for="terminos">Acepto los terminos y condiciones</label>
                </div>
                <?php if (isset($_GET['error'])): ?>
                    <?php if ($_GET['error'] === 'passwords'): ?>
                        <p style="color:#E63946;font-size:13px;margin-bottom:12px;">Las contraseñas no coinciden.</p>
                    <?php elseif ($_GET['error'] === 'email'): ?>
                        <p style="color:#E63946;font-size:13px;margin-bottom:12px;">Este correo ya está registrado.</p>
                    <?php else: ?>
                        <p style="color:#E63946;font-size:13px;margin-bottom:12px;">Error al crear la cuenta.</p>
                    <?php endif; ?>
                <?php endif; ?>
                <button class="btn-primary" type="submit">Crear cuenta</button>
            </form>
            <div class="form-footer">
                Ya tienes cuenta? <a href="../Login/index.php">Inicia sesion</a>
            </div>
        </div>
    </div>
</body>
</html>
