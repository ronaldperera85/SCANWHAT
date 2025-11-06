<?php
session_start();

// Bloque de protección con redirección
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

include '../db/conexion.php';

// Carga de variables de entorno (sin cambios)
if (!function_exists('loadEnv')) {
    function loadEnv($envPath) {
        if (!file_exists($envPath)) return false;
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            list($name, $value) = explode('=', $line, 2);
            $_ENV[trim($name)] = trim($value);
            putenv(trim($name) . '=' . trim($value));
        }
        return true;
    }
}
if (!loadEnv(__DIR__ . '/../.env')) {
    die("Error: No se pudo cargar el archivo .env");
}
$baseUrl = rtrim($_ENV['BACKEND_URL'], '/');
$apiUrlRegister = $baseUrl . '/api/register';

// ===============================================================
// INICIO DEL BLOQUE DE PROCESAMIENTO AJAX (AJUSTADO)
// ===============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $numero = $_POST['numero'] ?? '';

    // --- VALIDACIÓN ESTRICTA DEL NÚMERO DE TELÉFONO EN EL SERVIDOR ---

    // 1. Limpiamos el número de cualquier caracter no numérico.
    $cleanedNumero = preg_replace('/\D/', '', $numero);

    // 2. Verificamos que no esté vacío después de limpiar.
    if (empty($cleanedNumero)) {
        echo json_encode(['success' => false, 'message' => 'Por favor, ingresa un número de teléfono.']);
        exit;
    }

    // 3. VALIDACIÓN CLAVE: Verificamos que NO empiece con '0'.
    if (substr($cleanedNumero, 0, 1) === '0') {
        echo json_encode(['success' => false, 'message' => 'Formato inválido: El número no puede comenzar con 0. Usa el formato internacional (ej: 58412...).']);
        exit;
    }

    // 4. Verificamos la longitud internacional (entre 10 y 15 dígitos).
    if (strlen($cleanedNumero) < 10 || strlen($cleanedNumero) > 15) {
        echo json_encode(['success' => false, 'message' => 'Formato inválido: El número debe tener entre 10 y 15 dígitos, incluyendo el código de país.']);
        exit;
    }
    // --- FIN DE LA VALIDACIÓN ---

    try {
        // Usamos el número ya limpio y validado para la petición a la API
        $data = [
            'uid' => $cleanedNumero,
            'usuario_id' => $_SESSION['user_id']
        ];

        $ch = curl_init($apiUrlRegister);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception('Error en la conexión con el servidor de API: ' . curl_error($ch));
        }
        curl_close($ch);

        $responseData = json_decode($response, true);

        if (!$responseData || !isset($responseData['success'])) {
            throw new Exception('Respuesta no válida del servidor de API.');
        }

        if ($responseData['success']) {
            $qrCode = $responseData['data']['qrCode'] ?? null;
            $message = '¡Número listo para escanear! Usa WhatsApp para vincular tu dispositivo.';
            echo json_encode(['success' => true, 'message' => $message, 'qrCode' => $qrCode]);
        } else {
            $errorMessage = $responseData['message'] ?? 'Ocurrió un error desconocido.';
            echo json_encode(['success' => false, 'message' => 'Error: ' . $errorMessage]);
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    exit; // Detenemos la ejecución para no renderizar el HTML.
}
?>

<!-- =============================================================== -->
<!-- PARTE HTML (RECOMENDACIÓN DE MEJORA) -->
<!-- =============================================================== -->
<div class="content-container">
    <h1><i class="fas fa-plus-circle"></i> Agregar Nuevo Número</h1>
    
    <form id="registerPhoneForm" novalidate>
        <p>Introduce tu número de WhatsApp con el formato internacional para generar el código QR de vinculación.</p>
    
        <div class="form-group">
            <label for="numero">Número de Teléfono:</label>
            <!-- MEJORA: Usar type="tel" y pattern para validación en el navegador -->
            <input type="tel" 
                   id="numero" 
                   name="numero" 
                   class="form-control" 
                   placeholder="Ej: 584121234567" 
                   required
                   pattern="\d{10,15}"
                   title="Debe ser un número de 10 a 15 dígitos, sin espacios ni símbolos.">
            <!-- MEJORA: Añadir un texto de ayuda -->
            <small>Incluye el código de país. No uses '+' ni empieces con '0'.</small>
        </div>
        
        <!-- MEJORA: Cambiar texto del botón para más claridad -->
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-qrcode"></i> Generar QR
        </button>
    </form>

    <!-- El QR se mostrará en un SweetAlert, por lo que esta sección no es estrictamente necesaria,
         pero la dejamos por si el JS falla, como un fallback. -->
    <div id="qrCodeSection" style="display:none; text-align: center; margin-top: 20px;">
        <h2>Escanea el código QR con WhatsApp:</h2>
        <img id="qrCodeImage" src="" alt="Código QR para WhatsApp" style="max-width: 250px; border: 1px solid #ddd; padding: 5px;">
    </div>
</div>