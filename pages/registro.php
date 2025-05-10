<?php
session_start();
include '../db/conexion.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($nombre) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Todos los campos son obligatorios.";
    } elseif ($password !== $confirm_password) {
        $error = "Las contraseñas no coinciden.";
    } else {
        // Verificar si el correo ya está registrado
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->rowCount() > 0) {
            $error = "El correo ya está registrado.";
        } else {
            // Registrar al usuario
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password) VALUES (:nombre, :email, :password)");
            $stmt->execute([
                'nombre' => $nombre,
                'email' => $email,
                'password' => $hashed_password,
            ]);

            // Redireccionar al usuario a login.php
            header("Location: /login"); // Ajustado para la URL amigable
            exit(); // Asegura que el script se detenga después de la redirección
            // $success = "Usuario registrado exitosamente. <a href='login.php'>Inicia sesión</a>."; // Ya no es necesario
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
    <link rel="icon" href="/img/small.png" type="image/x-icon">
    <link rel="stylesheet" href="/css/style.css">
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

<div class="login-container"> <!-- Usamos la misma clase que en login.php para el contenedor -->
    <div class="card"> <!-- Usamos la clase "card" para la tarjeta -->
        <h2>Registro de Usuario</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php elseif ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="nombre">Nombre Completo:</label>
                <input type="text" id="nombre" name="nombre" class="form-control" placeholder="Ingrese su nombre" required>
            </div>
            <div class="form-group">
                <label for="email">Correo Electrónico:</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="Ingrese su correo" required>
            </div>
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Cree una contraseña" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirmar Contraseña:</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirme su contraseña" required>
            </div>
            <button type="submit" class="btn btn-primary">Registrarse</button>
        </form>
        <div class="register-link">
            ¿Ya tienes una cuenta? <a href="/login">Inicia sesión aquí</a> <!-- ajustado para la URL amigable -->
        </div>
    </div>
</div>
</body>
</html>