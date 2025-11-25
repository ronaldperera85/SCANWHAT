<?php
session_start();

// 1. Verificar autenticación
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

require_once __DIR__ . '/../db/conexion.php'; 

// 2. Obtener variables de entorno
$backendUrl = getenv('BACKEND_URL');
if (!$backendUrl) {
    // Si es una petición AJAX, devolvemos JSON
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error crítico: BACKEND_URL no definida.']);
        exit;
    }
    die("Error crítico: BACKEND_URL no definida.");
}

$baseUrl = rtrim($backendUrl, '/');
$apiUrlRegister = $baseUrl . '/api/register';
$apiUrlDisconnect = $baseUrl . '/api/disconnect';

// 3. Procesamiento de Acciones POST (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // IMPORTANTE: Indicamos que la respuesta será JSON
    header('Content-Type: application/json');
    
    $action = $_POST['action'];

    // --- Acción: Conectar ---
    if ($action === 'connect') {
        $numero = isset($_POST['phoneNumber']) ? trim($_POST['phoneNumber']) : '';

        // Validaciones
        if (empty($numero)) {
            echo json_encode(['success' => false, 'message' => 'Por favor, ingresa un número de teléfono.']);
            exit;
        } elseif (!preg_match('/^\d{10,15}$/', $numero)) {
            echo json_encode(['success' => false, 'message' => 'El número debe contener solo dígitos (10-15).']);
            exit;
        }

        $data = [
            'uid' => $numero,
            'usuario_id' => $_SESSION['user_id']
        ];

        // Llamada a la API
        $ch = curl_init($apiUrlRegister);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            echo json_encode(['success' => false, 'message' => 'Error de conexión con API: ' . $curlError]);
            exit;
        }

        $responseData = json_decode($response, true);

        if ($httpCode == 200 && isset($responseData['success']) && $responseData['success']) {
            $qrCode = $responseData['data']['qrCode'] ?? null;

            if ($qrCode) {
                // Asegurar formato Data URI
                if (!str_starts_with($qrCode, 'data:image')) {
                    $qrCode = 'data:image/png;base64,' . $qrCode;
                }
                
                // DEVOLVEMOS EL QR EN EL JSON DIRECTAMENTE
                echo json_encode([
                    'success' => true, 
                    'message' => 'Código QR generado correctamente.',
                    'qrCode' => $qrCode
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'La API respondió OK, pero no devolvió el código QR.']);
            }
        } else {
            $apiMessage = $responseData['message'] ?? $responseData['error'] ?? 'Error desconocido en la API.';
            echo json_encode(['success' => false, 'message' => $apiMessage]);
        }
        exit; // IMPORTANTE: Detener ejecución aquí para no devolver HTML
    }
    
    // --- Acción: Desconectar Usuario ---
    elseif ($action === 'disconnect_user') {
        $phoneNumber = isset($_POST['phoneNumber']) ? trim($_POST['phoneNumber']) : '';

        if (empty($phoneNumber)) {
            echo json_encode(['success' => false, 'message' => 'Número obligatorio.']);
            exit;
        }

        $disconnectUrl = $apiUrlDisconnect . "/" . urlencode($phoneNumber);
        
        $ch = curl_init($disconnectUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $responseData = json_decode($response, true);

        if ($httpCode == 200 || $httpCode == 404) {
             echo json_encode(['success' => true, 'message' => 'Desconexión procesada.']);
        } else {
             $apiMessage = $responseData['message'] ?? 'Error desconocido';
             echo json_encode(['success' => false, 'message' => $apiMessage]);
        }
        exit;
    }
}

// ==========================================================
// A PARTIR DE AQUÍ ES SOLO PARA CARGA HTML (GET)
// ==========================================================

// Leer mensajes flash de sesión (opcional si usas todo por AJAX)
$message = $_SESSION['feedback_message'] ?? "";
$message_type = $_SESSION['feedback_type'] ?? "info";
unset($_SESSION['feedback_message'], $_SESSION['feedback_type']);

// Obtener lista de números
try {
    $stmt = $pdo->prepare("SELECT id, numero, token, estado FROM numeros WHERE usuario_id = :usuario_id ORDER BY id DESC");
    $stmt->execute(['usuario_id' => $_SESSION['user_id']]);
    $numeros = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $numeros = [];
    $message = "Error DB: " . $e->getMessage();
    $message_type = "error";
}
?>

<!-- VISTA HTML -->
<div class="content-container">
    <h1><i class="fas fa-phone"></i> Mis Teléfonos</h1>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type === 'error' ? 'danger' : 'success'; ?>" role="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <button id="add-phone" class="btn btn-primary mb-3"><i class="fas fa-plus"></i> Agregar Nuevo Número</button>
    
    <div class="phone-list row">
        <?php if (empty($numeros)): ?>
            <div class="col-12"><p>Aún no has agregado ningún número.</p></div>
        <?php else: ?>
            <?php foreach ($numeros as $numero_data): ?>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card phone-card h-100">
                        <div class="card-body">
                            <h4 class="card-title"><?php echo htmlspecialchars($numero_data['numero']); ?></h4>
                            <p class="card-text mb-1">
                                <strong>Token:</strong><br/>
                                <span class="token-value"><?php echo htmlspecialchars($numero_data['token'] ?? 'N/A'); ?></span>
                            </p>
                            <p class="card-text">
                                <strong>Estado:</strong><br/>
                                <span class="status-text badge bg-<?php
    switch ($numero_data['estado']) {
        case 'conectado':
            echo 'success';
            break;
        case 'desconectado':
            echo 'secondary';
            break;
        case 'pendiente':
            echo 'warning text-dark';
            break;
        case 'error_vinculacion':
            echo 'danger';
            break;
        default:
            echo 'info';
            break;
    }
?>">
                                    <?php echo htmlspecialchars(ucfirst($numero_data['estado'])); ?>
                                </span>
                            </p>

                            <!-- Botones -->
                            <div class="mt-3">
                                <?php if ($numero_data['estado'] === 'desconectado' || $numero_data['estado'] === 'error_vinculacion'): ?>
                                    <button class="btn btn-success btn-sm connect-btn me-2"
                                            data-phone-number="<?php echo htmlspecialchars($numero_data['numero']); ?>">
                                        <i class="fas fa-link"></i> Conectar
                                    </button>
                                <?php endif; ?>

                                <?php if ($numero_data['estado'] === 'conectado' || $numero_data['estado'] === 'pendiente'): ?>
                                    <button class="btn btn-danger btn-sm delete-btn"
                                            data-phone-number="<?php echo htmlspecialchars($numero_data['numero']); ?>">
                                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                                    </button>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>