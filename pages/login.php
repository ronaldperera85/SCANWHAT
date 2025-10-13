<?php
session_start();
include '../db/conexion.php'; // Asegúrate que esta ruta es correcta desde login.php

// Si la solicitud es POST Y existen las variables de email o password (es una solicitud de login AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['email']) || isset($_POST['password']))) {
    
    // Configurar la respuesta como JSON y evitar que se imprima HTML
    header('Content-Type: application/json');

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        // Devolver JSON: Campos incompletos
        echo json_encode(['success' => false, 'message' => "Por favor, complete todos los campos."]);
        exit;
    }

    try {
        // Verificar si el usuario existe en la base de datos
        $stmt = $pdo->prepare("SELECT id, nombre, password FROM usuarios WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
/*
        // Compara contraseñas
        if ($usuario && $password === $usuario['password']) {
            // Credenciales correctas, iniciar sesión
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['user_name'] = $usuario['nombre'];
            
            // Devolver JSON de éxito con la URL de redirección
            echo json_encode(['success' => true, 'redirect' => 'menu']); 
            exit;
        } else {
            // Devolver JSON: Credenciales incorrectas
            echo json_encode(['success' => false, 'message' => "Credenciales incorrectas."]);
            exit;
        }
*/
  // ==========================================================
        //  AQUÍ ESTÁ LA MEJORA: USAR password_verify
        // ==========================================================
        if ($usuario && password_verify($password, $usuario['password'])) {
            
            // Credenciales correctas, iniciar sesión
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['user_name'] = $usuario['nombre'];
            
            // Devolver JSON de éxito con la URL de redirección
            echo json_encode(['success' => true, 'redirect' => 'menu']); 
            exit;
            
        } else {
            // Devolver JSON: Credenciales incorrectas
            echo json_encode(['success' => false, 'message' => "Credenciales incorrectas."]);
            exit;
        }
        // ==========================================================
    } catch (PDOException $e) {
        // Error de base de datos
        echo json_encode(['success' => false, 'message' => "Error de base de datos: " . $e->getMessage()]);
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
    
    <!-- Nuevo: INCLUIR FONT AWESOME PARA LOS ICONOS (USUARIO Y CANDADO) -->
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
                <p class="subtitle-gray">¡Conecta al Instante!</p>
            </div>
            
            <h2>Iniciar Sesión</h2>
            
            <form id="loginForm" novalidate>
                <div class="form-group">
                    <label for="email">Correo Electrónico:</label>
                    
                    <!-- Contenedor para el icono y el input -->
                    <div class="input-icon-group">
                        <i class="fas fa-user icon"></i> 
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="form-control" 
                               placeholder="Ingrese su correo" 
                               required 
                               autocomplete="username"> 
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña:</label>
                    
                    <!-- Contenedor para el icono y el input -->
                    <div class="input-icon-group">
                        <i class="fas fa-lock icon"></i>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-control" 
                               placeholder="Ingrese su contraseña" 
                               required 
                               autocomplete="current-password">
                    </div>
                </div>

                <!-- CAMBIO CLAVE: Icono y texto envuelto en span para manipulación JS -->
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> 
                    <span id="buttonText">Entrar</span>
                </button>
            </form>
            <div class="register-link">
                ¿No tienes una cuenta? <a href="registro">Regístrate aquí</a>
            </div>
        </div>
    </div>
    
    <!-- Script para manejar el formulario con Fetch y SweetAlert2 -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            
            if (loginForm) {
                loginForm.addEventListener('submit', async function(event) {
                    event.preventDefault();

                    const email = document.getElementById('email').value;
                    const password = document.getElementById('password').value;
                    const submitButton = loginForm.querySelector('button[type="submit"]');
                    
                    // Obtenemos referencia al texto dentro del botón
                    const buttonText = document.getElementById('buttonText'); 

                    if (!email || !password) {
                        Swal.fire({
                            icon: 'warning',
                            title: '¡Campos Incompletos!',
                            text: 'Por favor, ingrese su correo electrónico y contraseña.',
                        });
                        return;
                    }

                    const originalText = buttonText.textContent; // Guarda solo "Entrar"
                    submitButton.disabled = true;
                    buttonText.textContent = 'Verificando...'; // Solo cambia el texto, no el icono

                    try {
                        const formData = new URLSearchParams();
                        formData.append('email', email);
                        formData.append('password', password);

                        // Envía los datos al mismo archivo PHP
                        const response = await fetch('', { 
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: formData
                        });
                        
                        const data = await response.json(); 
                        
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Sesión Iniciada!',
                                text: 'Redirigiendo al menú principal...',
                                showConfirmButton: false,
                                timer: 1000
                            }).then(() => {
                                window.location.href = data.redirect;
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error de Inicio de Sesión',
                                text: data.message,
                            });
                        }
                    } catch (error) {
                        console.error('Error de red o de servidor:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de Conexión',
                            text: 'Ocurrió un error de red al intentar iniciar sesión. Inténtalo de nuevo.',
                        });
                    } finally {
                        submitButton.disabled = false;
                        buttonText.textContent = originalText; // Restaura solo el texto
                    }
                });
            }
        });
    </script>
</body>
</html>