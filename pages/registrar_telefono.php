<?php
session_start();

// Bloque de protección con redirección
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Si no ha iniciado sesión, lo redirigimos a la página de login.
    header("Location: login"); // <-- ¡La línea clave!
    exit(); // Detener la ejecución del script.
}

include '../db/conexion.php';

// =======================================================================
// ESTE BLOQUE DE CÓDIGO NO NECESITA CAMBIOS
// =======================================================================
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
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $_ENV[trim($parts[0])] = trim($parts[1]);
                putenv(trim($parts[0]) . '=' . trim($parts[1]));
            }
        }
        return true;
    }
}

if (!loadEnv(__DIR__ . '/../.env')) {
    die("Error: No se pudo cargar el archivo .env");
}

$baseUrl = rtrim($_ENV['BACKEND_URL'], '/');
$apiUrlRegister = $baseUrl . '/api/register';
// =======================================================================
// FIN DEL BLOQUE SIN CAMBIOS
// =======================================================================


// =======================================================================
// INICIO DEL BLOQUE AJUSTADO (SOLO SE EJECUTA EN SOLICITUDES AJAX)
// =======================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $numero = $_POST['numero'] ?? '';

    // Validar el número de teléfono en el servidor
    if (empty($numero)) {
        echo json_encode(['success' => false, 'message' => 'Por favor, ingresa un número de teléfono.']);
        exit;
    }

    if (!preg_match('/^\d{8,15}$/', $numero)) {
        echo json_encode(['success' => false, 'message' => 'Por favor, ingrese un número de teléfono válido en formato WhatsApp (ej: 584125927917).']);
        exit;
    }

    try {
        $data = [
            'uid' => $numero,
            'usuario_id' => $_SESSION['user_id'] ?? null
        ];

        $ch = curl_init($apiUrlRegister);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            // AJUSTE 1: El mensaje de error de cURL ya es texto simple, esto está bien.
            throw new Exception('Error en la conexión: ' . curl_error($ch));
        }

        curl_close($ch);

        $responseData = json_decode($response, true);

        if (!$responseData || !isset($responseData['success'])) {
            // AJUSTE 2: Se elimina el HTML del mensaje de la excepción.
            throw new Exception('Error al procesar la respuesta del servidor. Respuesta no válida.');
        }

        if ($responseData['success']) {
            $qrCode = $responseData['data']['qrCode'] ?? null;
            
            // AJUSTE 3: El mensaje de éxito ahora es texto simple.
            $message = '¡Número registrado exitosamente! Escanea este código QR para vincular tu número.';

            echo json_encode(['success' => true, 'message' => $message, 'qrCode' => $qrCode]);
        } else {
            $errorMessage = $responseData['message'] ?? '¡Número de teléfono ya registrado, intente con otro!';

            // AJUSTE 4: El mensaje de error ahora es texto simple.
            echo json_encode(['success' => false, 'message' => 'Error: ' . $errorMessage]);
        }

    } catch (Exception $e) {
        // AJUSTE 5: Se asegura que la respuesta del catch sea JSON con el mensaje de texto de la excepción.
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    exit; // Detiene la ejecución para no renderizar el HTML de abajo.
}
?>

<!-- ======================================================================= -->
<!-- ESTA PARTE HTML NO CAMBIA. SE MUESTRA AL CARGAR LA PÁGINA NORMALMENTE -->
<!-- ======================================================================= -->
<div class="content-container">
    <h1><i class="fas fa-plus-circle"></i> Agregar Nuevo Número</h1>
    
    <!-- Este div ya no se usará para mostrar mensajes, pero no molesta dejarlo -->
    <div id="registerPhoneResponse"></div> 
    
    <form id="registerPhoneForm" novalidate> <!-- Añadido 'novalidate' para desactivar validación del navegador -->
        <p></p>
    
        <div class="form-group">
            <label for="numero">Número de Teléfono:</label>
            <input type="text" id="numero" name="numero" class="form-control" placeholder="Número de teléfono con el prefijo del país: 584123456789" required>
        </div>
        <button type="submit" class="btn btn-primary">Generar QR</button>
    </form>

    <!-- Esta sección ya no es necesaria porque el QR se mostrará en el SweetAlert -->
    <div id="qrCodeSection" style="display:none;">
        <h2>Escanea el código QR con WhatsApp:</h2>
        <img id="qrCodeImage" src="" alt="QR Code">
    </div>
</div>