<?php
// ACTIVAR REPORTE DE ERRORES TEMPORAL PARA DEBUG (Solo si sigue fallando, quita los //)
// ini_set('display_errors', 1); error_reporting(E_ALL);

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

// ==========================================================
// 3. Procesamiento de Acciones POST (AJAX)
// ==========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Limpiamos buffer de salida para asegurar que solo salga JSON
    while (ob_get_level()) { ob_end_clean(); }
    
    header('Content-Type: application/json');
    
    // Evitar que Warnings rompan el JSON, pero permitiendo log de errores fatales
    ini_set('display_errors', 0);
    
    $action = $_POST['action'];

    try {
        // --- Acción: Conectar ---
        if ($action === 'connect') {
            // Aumentar tiempo de espera de PHP
            set_time_limit(300); 
            ini_set('max_execution_time', 300); 

            $numero = isset($_POST['phoneNumber']) ? trim($_POST['phoneNumber']) : '';

            // Validaciones básicas
            if (empty($numero)) {
                throw new Exception('Por favor, ingresa un número de teléfono.');
            }

            $data = [
                'uid' => $numero,
                'usuario_id' => $_SESSION['user_id']
            ];

            // Configuración cURL
            $ch = curl_init($apiUrlRegister);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 200);        
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);  
            
            $response = curl_exec($ch);
            
            if (curl_errno($ch)) {
                throw new Exception('Error de conexión cURL: ' . curl_error($ch));
            }
            
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Decodificar JSON
            $responseData = json_decode($response, true);

            if (!$responseData) {
                // Si falla el decode, mostramos lo que llegó (útil para debug)
                // Usamos base64 para evitar romper el JSON si hay caracteres raros
                $preview = base64_encode(substr($response, 0, 100));
                throw new Exception("Backend devolvió respuesta inválida (HTTP $httpCode). Preview B64: $preview");
            }

            if ($httpCode == 200 && isset($responseData['success']) && $responseData['success']) {
                $qrCode = $responseData['data']['qrCode'] ?? null;

                if ($qrCode) {
                    // CORRECCIÓN PARA PHP 7.X: Usar strncmp en vez de str_starts_with
                    // str_starts_with($qrCode, 'data:image') -> Solo PHP 8+
                    if (strncmp($qrCode, 'data:image', 10) !== 0) {
                        $qrCode = 'data:image/png;base64,' . $qrCode;
                    }
                    
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Código QR generado correctamente.',
                        'qrCode' => $qrCode
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Conectado, pero no se recibió QR.']);
                }
            } else {
                $apiMessage = $responseData['message'] ?? $responseData['error'] ?? 'Error desconocido en la API.';
                echo json_encode(['success' => false, 'message' => $apiMessage]);
            }
        }
        
        // --- Acción: Desconectar Usuario ---
        elseif ($action === 'disconnect_user') {
            $phoneNumber = isset($_POST['phoneNumber']) ? trim($_POST['phoneNumber']) : '';

            if (empty($phoneNumber)) {
                throw new Exception('Número obligatorio.');
            }

            $disconnectUrl = $apiUrlDisconnect . "/" . urlencode($phoneNumber);
            
            $ch = curl_init($disconnectUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);

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
        }

    } catch (Exception $e) {
        // Capturamos cualquier error lógico y lo devolvemos como JSON limpio
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } catch (Throwable $t) {
        // Capturamos errores fatales (PHP 7+) para evitar el Error 500 silencioso
        echo json_encode(['success' => false, 'message' => 'Error Fatal PHP: ' . $t->getMessage()]);
    }
    
    exit; // DETENER EJECUCIÓN
}

// ==========================================================
// VISTA HTML (GET)
// ==========================================================

$message = $_SESSION['feedback_message'] ?? "";
$message_type = $_SESSION['feedback_type'] ?? "info";
unset($_SESSION['feedback_message'], $_SESSION['feedback_type']);

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

    <button id="add-phone" class="btn btn-primary mb-3"><i class="fas fa-plus"></i> Agregar Nuevo Número</button><p></p>
    
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
        case 'conectado': echo 'success'; break;
        case 'desconectado': echo 'secondary'; break;
        case 'pendiente': echo 'warning text-dark'; break;
        case 'error_vinculacion': echo 'danger'; break;
        default: echo 'info'; break;
    }
?>">
                                    <?php echo htmlspecialchars(ucfirst($numero_data['estado'])); ?>
                                </span>
                            </p>

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