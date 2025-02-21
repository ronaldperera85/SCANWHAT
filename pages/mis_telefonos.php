<?php
session_start(); // Asegurar inicio de sesión

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

$message = "";
$qrCode = null;

$baseUrl = rtrim($_ENV['BACKEND_URL'], '/');
$apiUrlRegister = $baseUrl . '/api/register';
$apiUrlDisconnect = $baseUrl . '/api/disconnect';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'connect') {
            $numero = isset($_POST['phoneNumber']) ? trim($_POST['phoneNumber']) : '';
            $app = isset($_POST['app']) ? trim($_POST['app']) : 'default'; // Identificador de la app

            if (empty($numero)) {
                $message = "Por favor, ingresa un número de teléfono.";
            } elseif (!preg_match('/^\d{10,15}$/', $numero)) {
                $message = "El número de teléfono debe contener entre 10 y 15 dígitos.";
            } else {
                $data = ['uid' => $numero, 'app' => $app];
                $ch = curl_init($apiUrlRegister);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                $response = curl_exec($ch);

                if (curl_errno($ch)) {
                    $message = 'Error en la conexión: ' . curl_error($ch);
                } else {
                    $responseData = json_decode($response, true);
                    if (!$responseData || !isset($responseData['success'])) {
                        $message = "Error al procesar la respuesta del servidor.";
                    } else {
                        if ($responseData['success']) {
                            $qrCode = $responseData['data']['qrCode'] ?? null;
                            $token = $responseData['data']['token'] ?? null;

                            try {
                                $stmt = $pdo->prepare("UPDATE numeros SET estado = 'conectado', token = :token WHERE numero = :numero");
                                $stmt->execute(['numero' => $numero, 'token' => $token]);

                                $_SESSION['sessions'][$numero][$app] = [
                                    'qrCode' => $qrCode,
                                    'token' => $token
                                ];
                            } catch (PDOException $e) {
                                $message = "Error al actualizar la base de datos: " . $e->getMessage();
                            }
                        } else {
                            $message = "Error al registrar el usuario: " . ($responseData['message'] ?? 'Respuesta inesperada.');
                        }
                    }
                }
                curl_close($ch);
            }
        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? '';
            $phoneNumber = $_POST['phoneNumber'] ?? '';
            $app = $_POST['app'] ?? 'default';

            if (empty($id) || empty($phoneNumber)) {
                $message = "El ID y el número de teléfono son obligatorios.";
            } else {
                $ch = curl_init($apiUrlDisconnect . "/" . $phoneNumber);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                $response = curl_exec($ch);

                if (curl_errno($ch)) {
                    $message = 'Error en la conexión: ' . curl_error($ch);
                } else {
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $responseData = json_decode($response, true);

                    if ($httpCode >= 400 && !($httpCode === 404 && strpos($responseData['message'] ?? '', 'Session not found') !== false)) {
                        $message = "Error al desconectar: HTTP $httpCode";
                    } else {
                        try {
                            $stmt = $pdo->prepare("DELETE FROM numeros WHERE id = :id");
                            $stmt->execute(['id' => $id]);

                            if ($stmt->rowCount() > 0) {
                                unset($_SESSION['sessions'][$phoneNumber][$app]);
                            } else {
                                $message = "No se pudo cerrar sesión.";
                            }
                        } catch (PDOException $e) {
                            $message = "Error en la base de datos: " . $e->getMessage();
                        }
                    }
                }
                curl_close($ch);
            }
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM numeros WHERE usuario_id = :usuario_id");
$stmt->execute(['usuario_id' => $_SESSION['user_id']]);
$numeros = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-container">
    <h1><i class="fas fa-phone"></i> Mis Teléfonos</h1>
    <?php if ($message): ?>
        <p class="<?php echo strpos($message, 'Error') !== false ? 'error' : ''; ?>"><?php echo $message; ?></p>
    <?php endif; ?>

    <?php foreach ($_SESSION['sessions'] ?? [] as $phone => $apps): ?>
        <?php foreach ($apps as $app => $session): ?>
            <div class="card">  <!-- Aplicar clase "card" aquí -->
                <p>QR para <?php echo htmlspecialchars($phone); ?> (<?php echo htmlspecialchars($app); ?>)</p>
                <img src="<?php echo $session['qrCode']; ?>" alt="QR Code">
            </div> <!-- Cerrar div "card" -->
            <?php unset($_SESSION['sessions'][$phone][$app]); ?>
        <?php endforeach; ?>
    <?php endforeach; ?>

    <button id="add-phone" class="btn btn-primary" onclick="loadContent('pages/registrar_telefono.php')">+ Agregar Nuevo Número</button><p></p>
    <div class="phone-list">
        <?php foreach ($numeros as $numero): ?>
            <div class="card phone-card" data-phone-id="<?php echo $numero['id']; ?>" data-phone-number="<?php echo $numero['numero']; ?>">  <!-- Apply card class here -->
                <p><strong>Número:</strong><br/> <span class="phone-number"><?php echo $numero['numero']; ?></span></p>
                <p><strong>Token:</strong><br/> <span class="token"><?php echo $numero['token']; ?></span></p>
                <p><strong>Estado:</strong><br/> <span class="status-text"><?php echo htmlspecialchars($numero['estado'] === 'conectado' ? 'Conectado' : 'Desconectado'); ?></span></p>

                <?php if ($numero['estado'] !== 'conectado'): ?>  <!-- Check estado from $numero -->
                    <button class="connect-btn" data-phone-number="<?php echo htmlspecialchars($numero['numero']); ?>" data-app="whatsapp">Conectar</button>
                <?php endif; ?>

                <button class="delete-btn" data-phone-id="<?php echo htmlspecialchars($numero['id']); ?>" data-phone-number="<?php echo htmlspecialchars($numero['numero']); ?>" data-app="whatsapp">Cerrar Sesión</button>
            </div> <!-- End card -->
        <?php endforeach; ?>
    </div>
</div>