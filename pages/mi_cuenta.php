<?php
session_start();

// Bloque de protección con redirección
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Si no ha iniciado sesión, lo redirigimos a la página de login.
    header("Location: login"); // <-- ¡La línea clave!
    exit(); // Detener la ejecución del script.
}

include '../db/conexion.php';

$message = "";
$user = null;

// Manejo de la lógica POST (para evitar que se imprima HTML en una respuesta JSON)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    header('Content-Type: application/json'); // Indicar JSON response

    $oldPassword = trim($_POST['old_password']);
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Validar la contraseña
    if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
        echo json_encode(['success' => false, 'message' => "Todos los campos son requeridos."]);
        exit;
    } else if ($newPassword !== $confirmPassword) {
        echo json_encode(['success' => false, 'message' => "Las nuevas contraseñas no coinciden."]);
        exit;
    } else if (strlen($newPassword) < 6) {
        echo json_encode(['success' => false, 'message' => "La contraseña debe tener al menos 6 caracteres."]);
        exit;
    } else {
        try {
            // Obtener la contraseña actual del usuario
            $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = :user_id");
            $stmt->execute(['user_id' => $_SESSION['user_id']]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($userData) {
                if (password_verify($oldPassword, $userData['password'])) { 
                    // Hashear la nueva contraseña antes de guardarla
                    $newPasswordHashed = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE usuarios SET password = :password WHERE id = :user_id");
                    $stmt->execute(['password' => $newPasswordHashed, 'user_id' => $_SESSION['user_id']]); 

                    echo json_encode(['success' => true, 'message' => "Contraseña actualizada con éxito."]);
                    exit;
                } else {
                    echo json_encode(['success' => false, 'message' => "La contraseña actual es incorrecta."]);
                    exit;
                }
            } else {
                echo json_encode(['success' => false, 'message' => "Error al obtener los datos del usuario."]);
                exit;
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => "Error de base de datos durante la actualización: " . $e->getMessage()]);
            exit;
        }
    }
}


// Carga de datos del usuario
try {
    $stmt = $pdo->prepare("SELECT id, nombre, email FROM usuarios WHERE id = :user_id");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $message = "No se encontraron los datos del usuario.";
    }
} catch (PDOException $e) {
    // Si la carga de datos falla, se maneja el error, pero el flujo principal debe continuar.
    $message = "Error al obtener los datos de la base de datos: " . $e->getMessage();
}


?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Cuenta - SCANWHAT</title>
    <!-- Asumiendo que el head completo está en tu plantilla, aseguramos Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> 
    <link rel="stylesheet" href="./css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
</head>
<body>
<!-- Contenedor principal de la página (o el fragmento HTML a mostrar) -->
<div class="content-container"> 
    <h1><i class="fas fa-user"></i> Mi Cuenta</h1>
    <div id="accountInfoResponse">
        <?php if ($message): ?>
            <p class="<?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>"><?php echo $message; ?></p>
        <?php endif; ?>
    </div>
    <?php if ($user): ?>
        <div class="card">
            <h2 style="text-align: center;">Información de la Cuenta</h2>
            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($user['nombre']); ?></p>
            <p><strong>Email:</strong> <span class="email-wrap"><?php echo htmlspecialchars($user['email']); ?></span></p>
        </div>
        <div class="card">
            <h2 style="text-align: center;">Cambiar Contraseña</h2>
            <form id="changePasswordForm" style="text-align: center;" novalidate>
                <input type="hidden" name="change_password" value="1">
                
                <!-- Campo oculto para el gestor de contraseñas -->
                <input type="text" 
                    name="email" 
                    id="email" 
                    style="display:none; visibility:hidden;"
                    value="<?php echo htmlspecialchars($user['email']); ?>" 
                    autocomplete="username">
                
                <!-- 1. Contraseña Actual (Icono de candado con llave) -->
                <div class="form-group">
                    <label for="old_password">Contraseña Actual:</label>
                    <div class="input-icon-group">
                        <i class="fas fa-key icon"></i>
                        <input type="password" 
                               id="old_password" 
                               name="old_password" 
                               class="form-control" 
                               required 
                               autocomplete="current-password"
                               placeholder="Ingrese su contraseña actual">
                    </div>
                </div>
                
                <!-- 2. Nueva Contraseña (Icono de candado) -->
                <div class="form-group">
                    <label for="new_password">Nueva Contraseña:</label>
                    <div class="input-icon-group">
                        <i class="fas fa-lock icon"></i>
                        <input type="password" 
                               id="new_password" 
                               name="new_password" 
                               class="form-control" 
                               required 
                               autocomplete="new-password"
                               placeholder="Cree una nueva contraseña">
                    </div>
                </div>
                
                <!-- 3. Confirmar Nueva Contraseña (Icono de candado) -->
                <div class="form-group">
                    <label for="confirm_password">Confirmar Nueva Contraseña:</label>
                    <div class="input-icon-group">
                        <i class="fas fa-lock icon"></i>
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password" 
                               class="form-control" 
                               required 
                               autocomplete="new-password"
                               placeholder="Confirme la nueva contraseña">
                    </div>
                </div>
                
                <!-- Botón de acción con span para proteger el icono -->
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> 
                    <span id="changeButtonText">Cambiar Contraseña</span>
                </button>
                <div id="changePasswordResponse"></div>
            </form>
        </div>
    <?php else: ?>
        <p>No se encontraron los datos del usuario.</p>
    <?php endif; ?>
</div>

<!-- Script para manejar el formulario AJAX de cambio de contraseña -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const changePasswordForm = document.getElementById('changePasswordForm');
        
        if (changePasswordForm) {
            changePasswordForm.addEventListener('submit', async function(event) {
                event.preventDefault();

                const submitButton = changePasswordForm.querySelector('button[type="submit"]');
                const buttonText = document.getElementById('changeButtonText'); // ID del span
                const originalText = buttonText.textContent;

                // Simple check
                if (document.getElementById('old_password').value === "" || 
                    document.getElementById('new_password').value === "" || 
                    document.getElementById('confirm_password').value === "") {
                    Swal.fire({ icon: 'warning', title: 'Campos Vacíos', text: 'Por favor, complete todos los campos de contraseña.' });
                    return;
                }

                submitButton.disabled = true;
                buttonText.textContent = 'Guardando...';

                try {
                    const formData = new URLSearchParams(new FormData(changePasswordForm));
                    
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
                            title: 'Éxito',
                            text: data.message,
                        }).then(() => {
                            // Limpiar los campos del formulario después del éxito
                            changePasswordForm.reset(); 
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message,
                        });
                    }
                } catch (error) {
                    console.error('Error de red o de servidor:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de Conexión',
                        text: 'Ocurrió un error de red.',
                    });
                } finally {
                    submitButton.disabled = false;
                    buttonText.textContent = originalText;
                }
            });
        }
    });
</script>
</body>
</html>