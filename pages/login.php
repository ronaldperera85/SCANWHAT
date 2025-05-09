<?php
session_start();
include '../db/conexion.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Por favor, complete todos los campos.";
    } else {
        // Verificar si el usuario existe en la base de datos
        $stmt = $pdo->prepare("SELECT id, nombre, password FROM usuarios WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($password, $usuario['password'])) {
            // Credenciales correctas, iniciar sesión
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['user_name'] = $usuario['nombre'];
            header("Location: /scanwhat/menu"); // Ajustado para la URL amigable
            exit;
        } else {
            $error = "Credenciales incorrectas.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCANWHAT</title>
    <link rel="icon" href="/scanwhat/img/small.png" type="image/x-icon">
    <link rel="stylesheet" href="/scanwhat/css/style.css">
        <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="SCANWHAT" />
    <meta property="og:description" content="¡Conecta al Instante!" />
    <meta property="og:image" content="https://scanwhat.icarosoft.com/img/logo.png" />
    <meta property="og:url" content="https://scanwhat.icarosoft.com/" />
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="SCANWHAT" />
    <meta property="og:locale" content="es_ES" />
</head>
<body>
    <div class="login-container">
        <div class="card">
            <div class="logo-container">
                <img src="/scanwhat/img/logo.png" alt="Logo de ScanWhat">
            </div>
            <h2>Iniciar Sesión</h2>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="email">Correo Electrónico:</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Ingrese su correo" required>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña:</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Ingrese su contraseña" required>
                </div>
                <button type="submit" class="btn btn-primary">Entrar</button>
            </form>
            <div class="register-link">
                ¿No tienes una cuenta? <a href="/scanwhat/registro">Regístrate aquí</a> <!-- ajustado para la URL amigable -->
            </div>
        </div>
    </div>
</body>
</html>