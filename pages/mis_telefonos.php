<?php
session_start();

// 1. Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

// 2. Incluir la conexión y el cargador de variables de entorno
//    Esta línea ya nos da la variable $pdo y acceso a getenv() para todas las variables.
require_once __DIR__ . '/../db/conexion.php'; 

// --- SECCIÓN CORREGIDA ---
// Se eliminó el bloque problemático de 'loadEnv'.
// Ahora simplemente usamos getenv() para obtener la URL del backend.

// 3. Obtener la URL del backend
$backendUrl = getenv('BACKEND_URL');

// Es una buena práctica verificar que la variable exista.
if (!$backendUrl) {
    die("Error crítico: La variable de entorno BACKEND_URL no está definida en el servidor.");
}

// 4. Leer y limpiar los mensajes de la sesión (lógica original, está bien)
$message = $_SESSION['feedback_message'] ?? "";
$message_type = $_SESSION['feedback_type'] ?? "info";
unset($_SESSION['feedback_message'], $_SESSION['feedback_type']);

// 5. Construir las URLs de la API (lógica original, está bien)
$baseUrl = rtrim($backendUrl, '/');
$apiUrlRegister = $baseUrl . '/api/register';
$apiUrlDisconnect = $baseUrl . '/api/disconnect';

// --- El resto de tu lógica PHP (procesamiento POST y obtención de números) puede continuar aquí ---
// El código para manejar el POST y para hacer la consulta a la base de datos ya está bien
// y no necesita cambios.

// --- Procesamiento de Acciones POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $temp_message = ""; // Mensaje temporal para la acción POST
    $temp_message_type = "info"; // Tipo temporal

    // --- Acción: Conectar (Iniciar Vinculación/Registro) ---
    if ($action === 'connect') {
        $numero = isset($_POST['phoneNumber']) ? trim($_POST['phoneNumber']) : '';
        $valid = true; // Flag de validación

        if (empty($numero)) {
            $temp_message = "Por favor, ingresa un número de teléfono.";
            $temp_message_type = "error";
            $valid = false;
        } elseif (!preg_match('/^\d{10,15}$/', $numero)) { // Validación básica
            $temp_message = "El número de teléfono debe contener solo dígitos (10-15).";
            $temp_message_type = "error";
            $valid = false;
        }

        if ($valid) {
            $data = [
                'uid' => $numero,
                'usuario_id' => $_SESSION['user_id']
            ];

            error_log("MIS_TELEFONOS (POST Connect): Llamando a API Register URL: " . $apiUrlRegister);
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
                $temp_message = 'Error cURL: ' . $curlError;
                $temp_message_type = "error";
                error_log("cURL Error (Register API): " . $curlError);
            } else {
                $responseData = json_decode($response, true);
                error_log("Register API Response (HTTP $httpCode) for $numero: " . $response);

                if ($httpCode == 200 && isset($responseData['success']) && $responseData['success']) {
                    $qrCode = $responseData['data']['qrCode'] ?? null;
                    $token = $responseData['data']['token'] ?? null;

                    if ($qrCode) { // Solo necesitamos el QR
                        // Asegurar formato Data URI
                        if (!str_starts_with($qrCode, 'data:image')) {
                            $qrCode = 'data:image/png;base64,' . $qrCode;
                            error_log("QR Code para $numero: Prefijo Data URI añadido.");
                        } else {
                            error_log("QR Code para $numero: Ya tenía prefijo Data URI.");
                        }
                        // GUARDAR QR EN SESIÓN
                        $_SESSION['show_qr'] = $_SESSION['show_qr'] ?? []; // Inicializa si no existe
                        $_SESSION['show_qr'][$numero] = $qrCode;
                        $temp_message = "Escanea el código QR para vincular el número " . htmlspecialchars($numero);
                        $temp_message_type = "info";
                        error_log("QR Code guardado en sesión para $numero.");
                        // Node.js ya puso el estado en 'pendiente'
                    } else {
                        $temp_message = "API OK, pero no devolvió QR.";
                        $temp_message_type = "warning";
                        error_log("Register API Success but missing QR for $numero: " . $response);
                    }
                } else {
                    $apiMessage = $responseData['message'] ?? $responseData['error'] ?? 'Respuesta inesperada.';
                    $temp_message = "Error al iniciar la vinculación: " . htmlspecialchars($apiMessage) . " (HTTP: $httpCode)";
                    $temp_message_type = "error";
                    error_log("Register API Error (HTTP: $httpCode) for $numero: " . $response);
                }
            }
        }
        // $temp_message y $temp_message_type ya están seteados si hubo error de validación
    }
    // --- Acción: Desconectar Usuario ---
    elseif ($action === 'disconnect_user') {
        $phoneNumber = isset($_POST['phoneNumber']) ? trim($_POST['phoneNumber']) : '';

        if (empty($phoneNumber)) {
            $temp_message = "El número de teléfono es obligatorio para desconectar.";
            $temp_message_type = "error";
        } else {
            $disconnectUrl = $apiUrlDisconnect . "/" . urlencode($phoneNumber);
            error_log("MIS_TELEFONOS (POST Disconnect): Llamando a API Disconnect URL: " . $disconnectUrl);

            $ch = curl_init($disconnectUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                $temp_message = 'Error de conexión con la API de desconexión: ' . $curlError;
                $temp_message_type = "error";
                error_log("cURL Error (Disconnect API): " . $curlError);
            } else {
                $responseData = json_decode($response, true);
                error_log("Disconnect API Response (HTTP $httpCode) for $phoneNumber: " . $response);

                if ($httpCode == 200 || $httpCode == 404) {
                    $temp_message = $responseData['message'] ?? "Solicitud de desconexión procesada.";
                    $temp_message_type = "success";
                    // Limpiar QR de la sesión si estaba pendiente
                    if (isset($_SESSION['show_qr'][$phoneNumber])) {
                        unset($_SESSION['show_qr'][$phoneNumber]);
                        error_log("QR pendiente eliminado de sesión durante desconexión para $phoneNumber.");
                    }
                } else {
                    $apiMessage = $responseData['message'] ?? $responseData['error'] ?? "Error desconocido";
                    $temp_message = "Error al procesar la desconexión: " . htmlspecialchars($apiMessage) . " (HTTP $httpCode)";
                    $temp_message_type = "error";
                    error_log("Disconnect API Error (HTTP: $httpCode) for $phoneNumber: " . $response);
                }
            }
        }
    }

    // --- GUARDAR MENSAJE TEMPORAL EN SESIÓN ---
    if ($temp_message) {
        $_SESSION['feedback_message'] = $temp_message;
        $_SESSION['feedback_type'] = $temp_message_type;
    }

    // --- IMPORTANTE: Terminar ejecución después de POST ---
    exit(); // Previene renderizado HTML en respuesta POST

} // Fin del bloque if ($_SERVER['REQUEST_METHOD'] === 'POST')

// --- Obtener la lista ACTUALIZADA de números (para la carga GET) ---
try {
    $stmt = $pdo->prepare("SELECT id, numero, token, estado FROM numeros WHERE usuario_id = :usuario_id ORDER BY id DESC");
    $stmt->execute(['usuario_id' => $_SESSION['user_id']]);
    $numeros = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Establece el mensaje directamente aquí para la carga GET si hay error DB
    $message = "Error al recuperar la lista de números: " . $e->getMessage();
    $message_type = "error";
    error_log("DB Error fetching numeros: " . $e->getMessage());
    $numeros = [];
}

?>

<!-- ========= HTML PARA LA CARGA GET ========= -->
<div class="content-container">
    <h1><i class="fas fa-phone"></i> Mis Teléfonos</h1>

    <?php // Mostrar mensajes de feedback (leído desde sesión al inicio) ?>
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type === 'error' ? 'danger' : ($message_type === 'success' ? 'success' : 'info'); ?>" role="alert">
            <?php echo htmlspecialchars($message); // Usar htmlspecialchars aquí también ?>
        </div>
    <?php endif; ?>

    <?php // ¡¡ SE ELIMINA EL BUCLE DE QR SUPERIOR !! ?>
    <?php /* foreach ($_SESSION['show_qr'] ?? [] as $phone => $qrCodeValue): ... endforeach; */ ?>

    <?php // Botón para agregar nuevo número ?>
    <button id="add-phone" class="btn btn-primary mb-3"><i class="fas fa-plus"></i> Agregar Nuevo Número</button><p></p>
    
    <?php // Lista de números existentes ?>
    <div class="phone-list row">
        <?php if (empty($numeros)): ?>
            <div class="col-12">
                <p>Aún no has agregado ningún número.</p>
            </div>
        <?php else: ?>
            <?php foreach ($numeros as $numero_data): // Renombrar variable para claridad ?>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card phone-card h-100">
                        <div class="card-body">
                            <?php ?>
                            <h4 class="card-title">Número: <?php echo htmlspecialchars($numero_data['numero']); ?></h4>
                            <p class="card-text mb-1">
                                <strong>Token:</strong><br/>
                                <span class="token-value" style="word-break: break-all; cursor: pointer;" title="Click para ver completo/ocultar"><?php echo htmlspecialchars($numero_data['token'] ?? 'N/A'); ?></span>
                            </p>
                            <p class="card-text">
                                <strong>Estado:</strong><br/>
                                <span class="status-text badge bg-<?php
                                        switch ($numero_data['estado']) {
                                        case 'conectado': echo 'success'; break;
                                        case 'desconectado': echo 'secondary'; break;
                                        case 'pendiente': echo 'warning text-dark'; break;
                                        case 'error_vinculacion': echo 'danger'; break;
                                        default: echo 'info';
                                    }
                                ?>">
                                    <?php
                                            switch ($numero_data['estado']) {
                                            case 'conectado': echo 'Conectado'; break;
                                            case 'desconectado': echo 'Desconectado'; break;
                                            case 'pendiente': echo 'Pendiente QR'; break;
                                            case 'error_vinculacion': echo 'Error Vínculo'; break;
                                            default: echo htmlspecialchars(ucfirst($numero_data['estado']));
                                        }
                                    ?>
                                </span>
                            </p>

                            <?php
                            // --- INICIO: MOSTRAR QR CONDICIONALMENTE DENTRO DE LA CARD ---
                            $current_phone = $numero_data['numero']; // Obtener número actual
                            if (isset($_SESSION['show_qr'][$current_phone])) {
                                $qrCodeValue = $_SESSION['show_qr'][$current_phone];
                                // Limpiar inmediatamente para mostrar solo una vez
                                unset($_SESSION['show_qr'][$current_phone]);
                                error_log("Mostrando QR para $current_phone dentro de su card.");

                                // Asegurar formato Data URI (redundante si se hizo en POST, pero seguro)
                                if (!str_starts_with($qrCodeValue, 'data:image')) {
                                    $qrCodeValue = 'data:image/png;base64,' . $qrCodeValue;
                                }
                            ?>
<div class="qr-code-display text-center my-3 p-2">
    <h4 class="qr-title mb-1">¡Escanea Ahora!</h4>
    <img src="<?php echo $qrCodeValue; ?>" alt="QR Code para <?php echo htmlspecialchars($current_phone); ?>" class="qr-image" style="max-width: 180px; height: auto; display: block; margin: 5px 0;">
    <h4 class="qr-subtitle text-muted d-block mt-1">Antes que el código expire.</h4>
</div>
                            <?php
                            } // Fin if (isset($_SESSION['show_qr']))
                            // --- FIN: MOSTRAR QR CONDICIONALMENTE ---
                            ?>

                            <?php // --- Botones Condicionales --- ?>
                            <?php if ($numero_data['estado'] === 'desconectado' || $numero_data['estado'] === 'error_vinculacion'): ?>
                                <button class="btn btn-success btn-sm connect-btn me-2"
                                        data-phone-number="<?php echo htmlspecialchars($numero_data['numero']); ?>">
                                    <i class="fas fa-link"></i> Conectar
                                </button>
                            <?php endif; ?>

                            <?php // Muestra "Cerrar Sesión" si está conectado O pendiente ?>
                            <?php if ($numero_data['estado'] === 'conectado' || $numero_data['estado'] === 'pendiente'): ?>
                                <button class="btn btn-danger btn-sm delete-btn"
                                        data-phone-number="<?php echo htmlspecialchars($numero_data['numero']); ?>">
                                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                                </button>
                            <?php endif; ?>

                        </div> <!-- Fin card-body -->
                    </div> <!-- Fin card -->
                </div> <!-- Fin col -->
            <?php endforeach; ?>
        <?php endif; ?>
    </div> <!-- Fin de phone-list -->
</div> <!-- Fin content-container -->

<?php
// Log para verificar el estado final de la sesión QR al final de la carga de la página
error_log("MIS_TELEFONOS (GET End): Estado final de \$_SESSION['show_qr']: " . print_r($_SESSION['show_qr'] ?? [], true));
?>