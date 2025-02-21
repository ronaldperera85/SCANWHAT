<?php
session_start();
include '../db/conexion.php';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $numero = $_POST['numero'] ?? '';

    // Validar el número de teléfono en el servidor
    if (empty($numero)) {
        echo json_encode(['success' => false, 'message' => 'Por favor, ingresa un número de teléfono.']);
        exit;
    }

    // Regular expression for 584XXXXXXXXX or 584XXXXXXXXXX
    if (!preg_match('/^584\d{9,10}$/', $numero)) {
        echo json_encode(['success' => false, 'message' => 'Por favor, ingresa un número de teléfono válido en formato venezolano (ej: 584123456789 o 584241234567). Debe comenzar con 584 y tener entre 12 y 13 dígitos.']);
        exit;
    }

    try {
        $data = [
            'uid' => $numero
        ];

        $ch = curl_init($apiUrlRegister);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception('Error en la conexión: ' . curl_error($ch));
        }

        curl_close($ch);

        $responseData = json_decode($response, true);

        if (!$responseData || !isset($responseData['success'])) {
            throw new Exception('Error al procesar la respuesta del servidor.');
        }

        if ($responseData['success']) {
            $qrCode = $responseData['data']['qrCode'] ?? null;
            $token = $responseData['data']['token'] ?? null;
            $estado = 'conectado';
            $message = '<div class="alert alert-success">¡Número registrado exitosamente! Escanea este código QR para vincular tu número.</div>';

            $stmt = $pdo->prepare("INSERT INTO numeros (usuario_id, numero, token, estado, hooks_url) VALUES (:usuario_id, :numero, :token, :estado, '')");
            $stmt->execute([
            'usuario_id' => $_SESSION['user_id'],
            'numero' => $numero,
            'token' => $token,
            'estado' => $estado,
            ]);

            echo json_encode(['success' => true, 'message' => $message, 'qrCode' => $qrCode]);
        } else {
            $errorMessage = $responseData['message'] ?? '¡Número de teléfono ya registrado, intente con otro!';

            // Check if the API error message indicates a duplicate number
            //if (strpos($errorMessage, 'duplicate') !== false || strpos($errorMessage, 'exist') !== false) {
                //$errorMessage = 'Número de teléfono ya registrado, intente con otro!';
            //}

            echo json_encode(['success' => false, 'message' => '<div class="alert alert-danger">Error: ' . $errorMessage . '</div>']);
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    exit;
}
?>

<div class="content-container">
    <h1><i class="fas fa-plus-circle"></i> Agregar Nuevo Número</h1>
    <div id="registerPhoneResponse"></div>
    <form id="registerPhoneForm">
        <p></p>
        <div class="form-group">
            <label for="numero">Número de Teléfono:</label>
            <input type="text" id="numero" name="numero" class="form-control" placeholder="Número de teléfono con el prefijo del país: 584123456789" required>
        </div>
        <button type="submit" class="btn btn-primary">Generar QR</button>
    </form>
</div>