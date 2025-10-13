<?php
session_start();

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    // Si el usuario YA ha iniciado sesión, no tiene sentido que se registre.
    // Lo redirigimos al menú principal.
    header("Location: menu");
    exit();
}

include '../db/conexion.php'; // Asegúrate que esta ruta es correcta desde registro.php

// Si la solicitud es POST Y se esperan datos de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['email']) || isset($_POST['nombre']))) {
    
    // Configurar la respuesta como JSON
    header('Content-Type: application/json');

    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // 1. Validaciones PHP
    if (empty($nombre) || empty($email) || empty($password) || empty($confirm_password)) {
        echo json_encode(['success' => false, 'message' => "Todos los campos son obligatorios."]);
        exit;
    } elseif ($password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => "Las contraseñas no coinciden."]);
        exit;
    }

    try {
        // 2. Verificar si el correo ya está registrado
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => "El correo ya está registrado."]);
            exit;
        } else {
            // ==========================================================
            //  IMPLEMENTACIÓN DE HASHING (SEGURIDAD)
            // ==========================================================
            
            // Hashing de la contraseña con el algoritmo por defecto (bcrypt, el más seguro)
            $password_hasheado = password_hash($password, PASSWORD_DEFAULT);
            
            // 3. Registrar al usuario usando el hash
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password) VALUES (:nombre, :email, :password_hash)");
            $stmt->execute([
                'nombre' => $nombre,
                'email' => $email,
                'password_hash' => $password_hasheado, // <-- Insertamos el hash
            ]);
            // 4. Devolver JSON de éxito con la URL de redirección
            echo json_encode(['success' => true, 'message' => "Registro exitoso. Serás redirigido al inicio de sesión.", 'redirect' => 'login']); 
            exit;
        }
    } catch (PDOException $e) {
        // Error de base de datos
        echo json_encode(['success' => false, 'message' => "Error al registrar el usuario: " . $e->getMessage()]);
        exit;
    }
}
// Definir la ruta al favicon (ajusta la ruta si es necesario)
$faviconPath = "./img/small.png";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCANWHAT - ¡Conecta al Instante!</title>
    <link rel="icon" href="./img/small.png" type="image/x-icon">
    <link rel="stylesheet" href="./css/style.css">
    
    <!-- AÑADIR FONT AWESOME AQUÍ -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> 

    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="SCANWHAT" />
    <meta property="og:description" content="¡Conecta al Instante!" />
    <meta property="og:image" content="https://scanwhat.icarosoft.com/img/logo.png" />
    <meta property="og:url" content="https://scanwhat.icarosoft.com/" />
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="SCANWHAT" />
    <meta property="og:locale" content="es_ES" />
    <!-- INCLUIR SWEETALERT2 CDN AQUÍ -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
</head>
<body>

<div class="login-container">
    <div class="card">
        <div class="logo-container">
            <img src="./img/logo.png" alt="Logo de ScanWhat">
        </div>
        <h2>Registro de Usuario</h2>
        
        <form id="registerForm" novalidate>
            <!-- CAMPO: NOMBRE COMPLETO -->
            <div class="form-group">
                <label for="nombre">Nombre Completo:</label>
                <div class="input-icon-group">
                    <i class="fas fa-user icon"></i> 
                    <input type="text" 
                           id="nombre" 
                           name="nombre" 
                           class="form-control" 
                           placeholder="Ingrese su nombre" 
                           required 
                           autocomplete="name">
                </div>
            </div>
            
            <!-- CAMPO: CORREO ELECTRÓNICO -->
            <div class="form-group">
                <label for="email">Correo Electrónico:</label>
                <div class="input-icon-group">
                    <i class="fas fa-envelope icon"></i> <!-- Icono de correo -->
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-control" 
                           placeholder="Ingrese su correo" 
                           required 
                           autocomplete="username"> 
                </div>
            </div>
            
            <!-- CAMPO: CONTRASEÑA -->
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <div class="input-icon-group">
                    <i class="fas fa-lock icon"></i> 
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           placeholder="Cree una contraseña" 
                           required 
                           autocomplete="new-password"> 
                </div>
            </div>
            
            <!-- CAMPO: CONFIRMAR CONTRASEÑA -->
            <div class="form-group">
                <label for="confirm_password">Confirmar Contraseña:</label>
                <div class="input-icon-group">
                    <i class="fas fa-lock icon"></i> 
                    <input type="password" 
                           id="confirm_password" 
                           name="confirm_password" 
                           class="form-control" 
                           placeholder="Confirme su contraseña" 
                           required 
                           autocomplete="new-password"> 
                </div>
            </div>
            
            <!-- BOTÓN DE REGISTRO CON ICONO Y SPAN -->
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> <!-- Icono para registrar/añadir usuario -->
                <span id="buttonText">Registrarse</span>
            </button>
        </form>
        
        <div class="register-link">
            ¿Ya tienes una cuenta? <a href="login">Inicia sesión aquí</a>
        </div>
    </div>
</div>

<!-- Script para manejar el formulario con Fetch y SweetAlert2 -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const registerForm = document.getElementById('registerForm');
        
        if (registerForm) {
            registerForm.addEventListener('submit', async function(event) {
                event.preventDefault();

                const nombre = document.getElementById('nombre').value;
                const email = document.getElementById('email').value;
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                const submitButton = registerForm.querySelector('button[type="submit"]');
                
                // Referencia al span para modificar solo el texto
                const buttonText = document.getElementById('buttonText');

                if (!nombre || !email || !password || !confirmPassword) {
                    Swal.fire({
                        icon: 'warning',
                        title: '¡Campos Incompletos!',
                        text: 'Por favor, completa todos los campos del formulario.',
                    });
                    return;
                }
                
                if (password !== confirmPassword) {
                    Swal.fire({
                        icon: 'error',
                        title: '¡Contraseñas no Coinciden!',
                        text: 'La contraseña y la confirmación de contraseña no coinciden.',
                    });
                    return;
                }

                // Mostrar estado de carga en el botón
                const originalText = buttonText.textContent; // Guardamos solo el texto
                submitButton.disabled = true;
                buttonText.textContent = 'Registrando...'; // Modificamos solo el texto

                let data; // Definir data aquí para que esté disponible en el finally
                try {
                    const formData = new URLSearchParams();
                    formData.append('nombre', nombre);
                    formData.append('email', email);
                    formData.append('password', password);
                    formData.append('confirm_password', confirmPassword);

                    // Envía los datos al mismo archivo PHP
                    const response = await fetch('', { 
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: formData
                    });
                    
                    data = await response.json(); 
                    
                    if (data.success) {
                        // Éxito: Muestra SweetAlert y redirige al login
                        Swal.fire({
                            icon: 'success',
                            title: '¡Registro Exitoso!',
                            text: data.message,
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            window.location.href = data.redirect; // Redirige a /login
                        });
                    } else {
                        // Error: Muestra SweetAlert con el mensaje del servidor
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de Registro',
                            text: data.message,
                        });
                    }
                } catch (error) {
                    console.error('Error de red o de servidor:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de Conexión',
                        text: 'Ocurrió un error de red al intentar registrarte. Inténtalo de nuevo.',
                    });
                } finally {
                    // Restaura el botón solo si no fue un éxito total (para que no se restaure
                    // mientras el SweetAlert está mostrando el éxito antes de la redirección)
                    if (!data || !data.success) {
                        submitButton.disabled = false;
                        buttonText.textContent = originalText;
                    }
                }
            });
        }
    });
</script>
</body>
</html>