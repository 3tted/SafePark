<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - SafePark</title>
    <link rel="icon" href="../Assets/logo.png">
    <link rel="stylesheet" href="../CSS/styles.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="editar.css">
</head>
<body>

<?php
require_once '../database/conexion.php';
require_once '../includes/auth.php';
requiere_sesion();

$id = $_SESSION['id_usuario'];
$stmt = $conn->prepare("SELECT nombre, email, foto_perfil FROM USUARIO WHERE id_usuario = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

$nombre     = htmlspecialchars($usuario['nombre']);
$email      = htmlspecialchars($usuario['email']);
$foto       = $usuario['foto_perfil'];
$inicial    = strtoupper(mb_substr($nombre, 0, 1));

$error   = $_GET['error']   ?? '';
$exito   = $_GET['exito']   ?? '';

$nav_base   = '../';
$nav_active = 'perfil';
require_once '../includes/navbar.php';
?>

    <div class="editar-wrap">
        <div class="editar-card">
            <h2 class="editar-title">✏️ Editar perfil</h2>

            <?php if ($exito === '1'): ?>
                <div class="msg-ok">✅ Perfil actualizado correctamente.</div>
            <?php endif; ?>
            <?php if ($error === 'email'): ?>
                <div class="msg-err">❌ Ese correo ya está en uso.</div>
            <?php elseif ($error === 'passwords'): ?>
                <div class="msg-err">❌ Las contraseñas no coinciden.</div>
            <?php elseif ($error === 'foto'): ?>
                <div class="msg-err">❌ Solo se permiten imágenes JPG, PNG o WEBP (máx. 2MB).</div>
            <?php elseif ($error === 'servidor'): ?>
                <div class="msg-err">❌ Error del servidor, intenta de nuevo.</div>
            <?php endif; ?>

            <form action="actualizar.php" method="POST" enctype="multipart/form-data">

                <!-- Foto de perfil -->
                <div class="foto-section">
                    <div class="foto-preview" id="foto-preview">
                        <?php if ($foto): ?>
                            <img src="../Assets/fotos/<?= htmlspecialchars($foto) ?>" alt="Foto de perfil">
                        <?php else: ?>
                            <span class="foto-inicial"><?= $inicial ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="foto-actions">
                        <label class="btn-foto" for="foto_perfil">📷 Cambiar foto</label>
                        <input type="file" id="foto_perfil" name="foto_perfil" accept="image/*" onchange="previewFoto(this)">
                        <p class="foto-hint">JPG, PNG o WEBP · Máx. 2MB</p>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Nombre</label>
                    <input class="form-input" type="text" name="nombre" value="<?= $nombre ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Correo electrónico</label>
                    <input class="form-input" type="email" name="email" value="<?= $email ?>" required>
                </div>

                <div class="editar-sep">Cambiar contraseña <span>(dejar en blanco para no cambiar)</span></div>

                <div class="form-group">
                    <label class="form-label">Nueva contraseña</label>
                    <input class="form-input" type="password" name="password" placeholder="Nueva contraseña...">
                </div>

                <div class="form-group">
                    <label class="form-label">Confirmar contraseña</label>
                    <input class="form-input" type="password" name="confirmar" placeholder="Confirmar contraseña...">
                </div>

                <div class="editar-btns">
                    <a class="btn-cancelar" href="index.php">Cancelar</a>
                    <button class="btn-guardar" type="submit">Guardar cambios</button>
                </div>

            </form>
        </div>
    </div>

    <div class="footer-bar">SafePark · Editar perfil · Ciudad Juárez</div>

    <script>
    function previewFoto(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                const preview = document.getElementById('foto-preview');
                preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
    </script>
</body>
</html>
