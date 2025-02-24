<?php
session_start(); // Agregar esta línea
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

        $oldPassword = $_POST['old_password'];
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
                // Verificar la contraseña actual
                if (password_verify($oldPassword, $userData['password'])) {
                    // Hash de la nueva contraseña
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                    // Actualizar la contraseña
                    $stmt = $pdo->prepare("UPDATE usuarios SET password = :password WHERE id = :user_id");
                    $stmt->execute(['password' => $hashedPassword, 'user_id' => $_SESSION['user_id']]);

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
            <h2>
                <p style="text-align: center;">Información de la Cuenta</p>
            </h2>
            <p><strong>
                    <h4>Nombre:</h4>
                </strong> <?php echo htmlspecialchars($user['nombre']); ?></p>
            <p><strong>
                    <h4>Email:</h4>
                </strong> <span class="email-wrap"><?php echo htmlspecialchars($user['email']); ?></span></p>
        </div>
        <div class="card">
            <h2>
                <p style="text-align: center;">Cambiar Contraseña</p>
            </h2>
            <form id="changePasswordForm" style="text-align: center;">
                <input type="hidden" name="change_password" value="1">
                <div class="form-group">
                    <label for="old_password">Contraseña Actual:</label>
                    <input type="password" id="old_password" name="old_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="new_password">Nueva Contraseña:</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirmar Nueva Contraseña:</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Cambiar Contraseña</button>
                <div id="changePasswordResponse"></div>
            </form>
        </div>
    <?php else: ?>
        <p>No se encontraron los datos del usuario.</p>
    <?php endif; ?>
</div>