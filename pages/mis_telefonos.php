<?php
session_start(); // Asegurar inicio de sesión

// Redirigir si el usuario no está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php'); // O tu página de login
    exit;
}

include '../db/conexion.php'; // Conexión PDO

// --- Carga de Variables de Entorno ---
if (!function_exists('loadEnv')) {
    function loadEnv($envPath) {
        if (!file_exists($envPath)) {
            return false;
        }
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
            putenv(trim($key) . '=' . trim($value)); // También para getenv() si se usa
        }
        return true;
    }
}

if (!loadEnv(__DIR__ . '/../.env')) {
    // Considera un manejo de error más robusto o valores por defecto
    error_log("Error crítico: No se pudo cargar el archivo .env");
    die("Error de configuración del servidor.");
}

$message = ""; // Para mensajes de feedback al usuario
$message_type = "info"; // Para aplicar clases CSS (info, error, success)

// URLs de la API Backend (Node.js)
$baseUrl = rtrim(getenv('BACKEND_URL') ?: 'http://localhost:3000', '/'); // Usa getenv y valor por defecto
$apiUrlRegister = $baseUrl . '/api/register';
$apiUrlDisconnect = $baseUrl . '/api/disconnect'; // La URL base para desconectar (se añadirá /:uid)

// --- Procesamiento de Acciones POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $app = isset($_POST['app']) ? trim($_POST['app']) : 'default'; // Identificador de app (si se usa)

    // --- Acción: Conectar (Iniciar Vinculación/Registro) ---
    if ($action === 'connect') {
        $numero = isset($_POST['phoneNumber']) ? trim($_POST['phoneNumber']) : '';

        if (empty($numero)) {
            $message = "Por favor, ingresa un número de teléfono.";
            $message_type = "error";
        } elseif (!preg_match('/^\d{10,15}$/', $numero)) { // Validación básica
            $message = "El número de teléfono debe contener solo dígitos (10-15).";
            $message_type = "error";
        } else {
            // Prepara datos para la API de registro
            $data = [
                'uid' => $numero,
                'usuario_id' => $_SESSION['user_id'] // Envía el usuario_id a la API
                // 'app' => $app // Descomentar si la API necesita 'app'
            ];

            $ch = curl_init($apiUrlRegister);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); // Enviar como JSON
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 65); // Timeout más largo para esperar QR

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                $message = 'Error de conexión con la API de registro: ' . $curlError;
                $message_type = "error";
                error_log("cURL Error (Register API): " . $curlError);
            } else {
                $responseData = json_decode($response, true);

                if ($httpCode == 200 && isset($responseData['success']) && $responseData['success']) {
                    // Éxito al obtener QR y Token desde la API
                    $qrCode = $responseData['data']['qrCode'] ?? null;
                    $token = $responseData['data']['token'] ?? null; // El token (nuevo o existente)

                    if ($qrCode && $token) {
                        // Guardar QR en sesión para mostrarlo temporalmente
                        $_SESSION['show_qr'][$numero] = $qrCode;
                        $message = "Escanea el código QR para vincular el número " . htmlspecialchars($numero);
                        $message_type = "info"; // O "success"
                        // No actualizamos estado aquí, registerUser/createSession lo pone en 'pendiente'
                        // y el evento 'ready' de whatsapp-web.js lo pone en 'conectado'
                    } else {
                        $message = "Respuesta exitosa de la API, pero faltan datos (QR o Token).";
                        $message_type = "warning";
                        error_log("Register API Success but missing data: " . $response);
                    }

                } else {
                    // Error desde la API de registro
                    $apiMessage = $responseData['message'] ?? $responseData['error'] ?? 'Respuesta inesperada de la API.';
                    $message = "Error al iniciar la vinculación: " . htmlspecialchars($apiMessage) . " (HTTP: $httpCode)";
                    $message_type = "error";
                    error_log("Register API Error (HTTP: $httpCode): " . $response);
                }
            }
        }
    // --- Acción: Desconectar Usuario ---
    } elseif ($action === 'disconnect_user') { // Coincide con el JS actualizado
        $phoneNumber = isset($_POST['phoneNumber']) ? trim($_POST['phoneNumber']) : '';

        if (empty($phoneNumber)) {
            $message = "El número de teléfono es obligatorio para desconectar.";
            $message_type = "error";
        } else {
            // Llama a la API Node.js para desconectar (UPDATE estado = 'desconectado')
            $disconnectUrl = $apiUrlDisconnect . "/" . urlencode($phoneNumber); // Construye la URL correctamente

            $ch = curl_init($disconnectUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST'); // Asegúrate que coincida con el método en Node.js
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Timeout razonable para desconectar
            // curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']); // Añadir si es necesario
            // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([])); // Enviar cuerpo si es POST

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                $message = 'Error de conexión con la API de desconexión: ' . $curlError;
                $message_type = "error";
                error_log("cURL Error (Disconnect API): " . $curlError);
            } else {
                $responseData = json_decode($response, true);

                // Considera éxito si es 200 OK o 404 (Sesión no encontrada, ya estaba desconectada)
                if ($httpCode == 200 || $httpCode == 404) {
                    // Éxito (o ya estaba desconectado)
                    $message = $responseData['message'] ?? "Solicitud de desconexión procesada.";
                    $message_type = "success";

                    // Limpiar QR de la sesión PHP si estaba pendiente
                    if (isset($_SESSION['show_qr'][$phoneNumber])) {
                        unset($_SESSION['show_qr'][$phoneNumber]);
                    }

                    // ***** NO HAY DELETE FROM numeros *****
                    // La API de Node.js ya hizo UPDATE numeros SET estado = 'desconectado'

                } else {
                    // Hubo un error en la API de Node.js
                    $apiMessage = $responseData['message'] ?? $responseData['error'] ?? "Error desconocido (HTTP $httpCode)";
                    $message = "Error al procesar la desconexión: " . htmlspecialchars($apiMessage);
                    $message_type = "error";
                    error_log("Disconnect API Error (HTTP: $httpCode): " . $response);
                }
            }
        }
    }
    // Añadir aquí otras acciones si es necesario (elseif...)

    // Limpiar variables POST para evitar reenvíos accidentales (opcional)
    // unset($_POST); // Puede ser muy agresivo, usar con cuidado
}

// --- Obtener la lista ACTUALIZADA de números del usuario desde la DB ---
// *** Se quita el filtro AND estado != 'pendiente' para mostrar TODOS ***
try {
    $stmt = $pdo->prepare("SELECT id, numero, token, estado FROM numeros WHERE usuario_id = :usuario_id ORDER BY id DESC");
    $stmt->execute(['usuario_id' => $_SESSION['user_id']]);
    $numeros = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error al recuperar la lista de números: " . $e->getMessage();
    $message_type = "error";
    error_log("DB Error fetching numeros: " . $e->getMessage());
    $numeros = []; // Asegurar que $numeros sea un array vacío en caso de error
}

?>

<div class="content-container">
    <h1><i class="fas fa-phone"></i> Mis Teléfonos</h1>

    <?php // Mostrar mensajes de feedback ?>
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type === 'error' ? 'danger' : ($message_type === 'success' ? 'success' : 'info'); ?>" role="alert">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php // Mostrar QR si está en la sesión ?>
    <?php foreach ($_SESSION['show_qr'] ?? [] as $phone => $qrCodeValue): ?>
        <div class="card mb-3">
            <div class="card-body text-center">
                <h5 class="card-title">Escanea QR para <?php echo htmlspecialchars($phone); ?></h5>
                <img src="<?php echo $qrCodeValue; ?>" alt="QR Code para <?php echo htmlspecialchars($phone); ?>" style="max-width: 200px; height: auto;">
            </div>
        </div>
        <?php unset($_SESSION['show_qr'][$phone]); // Limpiar después de mostrar ?>
    <?php endforeach; ?>

    <?php // Botón para agregar nuevo número ?>
    <button id="add-phone" class="btn btn-primary mb-3" onclick="loadContent('pages/registrar_telefono.php')"><i class="fas fa-plus"></i> Agregar Nuevo Número</button><p></p>

    <?php // Lista de números existentes ?>
    <div class="phone-list row"> <?php // Usar row para layout de cards ?>
        <?php if (empty($numeros)): ?>
            <div class="col-12">
                <p>Aún no has agregado ningún número.</p>
            </div>
        <?php else: ?>
            <?php foreach ($numeros as $numero): ?>
                <div class="col-md-6 col-lg-4 mb-3"> <?php // Layout responsivo de cards ?>
                    <div class="card phone-card h-100"> <?php // h-100 para igualar altura ?>
                        <div class="card-body">
                            <class="card-title">Número: <?php echo htmlspecialchars($numero['numero']); ?></class>
                            <p class="card-text mb-1">
                                <strong>Token:</strong><br/>
                                <span class="token-value" style="word-break: break-all; cursor: pointer;" title="Click para ver completo/ocultar"><?php echo htmlspecialchars($numero['token']); ?></span>
                            </p>
                            <p class="card-text">
                                <strong>Estado:</strong><br/>
                                <span class="status-text badge bg-<?php
                                    switch ($numero['estado']) {
                                        case 'conectado': echo 'success'; break;
                                        case 'desconectado': echo 'secondary'; break;
                                        case 'pendiente': echo 'warning text-dark'; break;
                                        case 'error_vinculacion': echo 'danger'; break;
                                        default: echo 'info';
                                    }
                                ?>">
                                    <?php
                                        switch ($numero['estado']) {
                                            case 'conectado': echo 'Conectado'; break;
                                            case 'desconectado': echo 'Desconectado'; break;
                                            case 'pendiente': echo 'Pendiente QR'; break;
                                            case 'error_vinculacion': echo 'Error Vínculo'; break;
                                            default: echo htmlspecialchars(ucfirst($numero['estado']));
                                        }
                                    ?>
                                </span>
                            </p>

                            <?php // Botón Conectar: si está desconectado o hubo error ?>
                            <?php if ($numero['estado'] === 'desconectado' || $numero['estado'] === 'error_vinculacion'): ?>
                                <button class="btn btn-success btn-sm connect-btn me-2"
                                        data-phone-number="<?php echo htmlspecialchars($numero['numero']); ?>"
                                        data-app="whatsapp">
                                    <i class="fas fa-link"></i> Conectar
                                </button>
                            <?php endif; ?>

                            <?php // Botón Cerrar Sesión: si está conectado o pendiente (para cancelar) ?>
                            <?php if ($numero['estado'] === 'conectado' || $numero['estado'] === 'pendiente'): ?>
                                <button class="btn btn-danger btn-sm delete-btn"
                                        data-phone-number="<?php echo htmlspecialchars($numero['numero']); ?>"
                                        data-app="whatsapp"> <?php // No necesita phone-id ?>
                                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div> <?php // Fin de phone-list ?>
</div>