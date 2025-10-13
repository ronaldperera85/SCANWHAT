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

try {
    // Obtener los datos del usuario actual
    $stmt = $pdo->prepare("SELECT id, nombre, email FROM usuarios WHERE id = :user_id");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $message = "No se encontraron los datos del usuario.";
    }

    // Manejo del cambio de contraseña
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
            // Obtener la contraseña actual del usuario
            $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = :user_id");
            $stmt->execute(['user_id' => $_SESSION['user_id']]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

             if ($userData) {
/*
                // VERIFICAR CONTRASEÑA EN TEXTO PLANO (RIESGOSO)
                if ($oldPassword === $userData['password']) { // <-- ¡CAMBIO AQUÍ!
                    // GUARDAR LA NUEVA CONTRASEÑA EN TEXTO PLANO (RIESGOSO)
                    $newPasswordPlain = $newPassword; // Usar la nueva contraseña en texto plano

                    // Actualizar la contraseña
*/                  
                if (password_verify($oldPassword, $userData['password'])) { // <-- ¡CAMBIO AQUÍ!
                    // Hashear la nueva contraseña antes de guardarla
                    
                    $newPasswordHashed = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE usuarios SET password = :password WHERE id = :user_id");
/*
                    $stmt->execute(['password' => $newPasswordPlain, 'user_id' => $_SESSION['user_id']]);
*/
                    // Hashear la nueva contraseña antes de guardarla
                    $stmt->execute(['password' => $newPasswordHashed, 'user_id' => $_SESSION['user_id']]); // <-- ¡CAMBIO AQUÍ!

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
        }
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => "Error al obtener los datos de la base de datos: " . $e->getMessage()]);
    exit;
}
?>

<div class="content-container">
    <h1><i class="fas fa-user"></i> Mi Cuenta</h1>
    <div id="accountInfoResponse">
        <?php if ($message): ?>
            <p class="<?php echo strpos($message, 'Error') !== false ? 'error' : ''; ?>"><?php echo $message; ?></p>
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
                <!-- CAMPO OCULTO (TIPO TEXTO PARA MAYOR COMPATIBILIDAD PERO VISUALMENTE OCULTO) -->
<!-- El navegador lo detecta como campo de "usuario" gracias al autocomplete="username" -->
                <input type="text" 
                    name="email" 
                    id="email" 
                    style="display:none; visibility:hidden;"
                    value="<?php echo htmlspecialchars($user['email']); ?>" 
                    autocomplete="username">
                
                <div class="form-group">
                    <label for="old_password">Contraseña Actual:</label>
                    <input type="password" id="old_password" name="old_password" class="form-control" required autocomplete="current-password">
                </div>
                <div class="form-group">
                    <label for="new_password">Nueva Contraseña:</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" required autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirmar Nueva Contraseña:</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-key"></i> Cambiar Contraseña</button>
                <div id="changePasswordResponse"></div>
            </form>
        </div>
    <?php else: ?>
        <p>No se encontraron los datos del usuario.</p>
    <?php endif; ?>
</div>