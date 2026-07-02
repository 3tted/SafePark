<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesion - SafePark</title>
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
            <p class="form-subtitle">Inicia sesion para explorar areas verdes seguras</p>
            <form action="login.php" method="POST">
                <div class="form-group">
                    <label class="form-label">Correo electronico</label>
                    <input class="form-input" type="email" name="email" placeholder="tucorreo@ejemplo.com" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Contrasena</label>
                    <input class="form-input" type="password" name="password" placeholder="........" required>
                </div>
                <?php if (isset($_GET['error'])): ?>
                    <p style="color:#E63946;font-size:13px;margin-bottom:12px;">Correo o contraseña incorrectos.</p>
                <?php endif; ?>
                <button class="btn-primary" type="submit">Iniciar sesion</button>
            </form>
            <div class="form-footer">
                No tienes cuenta? <a href="../Registro/index.php">Registrate aqui</a>
            </div>
        </div>
    </div>
    <script src="../Javascript/archivo.js"></script>
</body>
</html>
