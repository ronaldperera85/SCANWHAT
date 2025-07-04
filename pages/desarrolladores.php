<?php
// Cargar las variables de entorno desde el archivo .env
function loadEnv($envPath) {
    if (!file_exists($envPath)) {
        return false; // No hacer nada si el archivo no existe
    }
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Ignorar comentarios
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $_ENV[trim($parts[0])] = trim($parts[1]);
            putenv(trim($parts[0]) . '=' . trim($parts[1])); // Para que funcione con getenv()
        }
    }
    return true; // Indicar que el archivo se cargó correctamente
}

if (!loadEnv(__DIR__ . '/../.env')) {
    // Manejar el error de la carga del archivo .env
    die("Error: No se pudo cargar el archivo .env");
}

$baseUrl = rtrim($_ENV['BACKEND_URL'], '/');
$apiSendChatUrl  = $baseUrl . '/api/send/chat';
$apiHelpdeskUrl = $baseUrl . '/api';

?>
<div class="content-container">
    <h1><i class="fas fa-code"></i> Desarrolladores</h1>

    <div class="card" data-api-send-chat-url="<?php echo htmlspecialchars($apiSendChatUrl); ?>">
        <h2><i class="fas fa-envelope"></i> Prueba de Envío de Mensaje</h2>
        <form id="sendMessageForm">
            <div>
                <label for="apiTokenSend">API Token:</label>
                <input type="text" id="apiTokenSend" name="apiTokenSend" required>
            </div>
            <div>
                <label for="waAccountSend">WhatsApp Account UID:</label>
                <input type="text" id="waAccountSend" name="waAccountSend" required>
            </div>
            <div>
                <label for="recipientAccountSend">Recipient Account:</label>
                <input type="text" id="recipientAccountSend" name="recipientAccountSend" required>
            </div>
            <div>
                <label for="messageTextSend">Message Text:</label>
                <textarea id="messageTextSend" name="messageTextSend" rows="3" required></textarea>
            </div>
            <button type="submit"><i class="fas fa-paper-plane"></i> Enviar Mensaje</button>
        </form>
        <div id="sendMessageResponse"></div>
    </div>

    <div class="card">
        <h2><i class="fas fa-cog"></i> Instrucciones para ajustes dentro de <strong>ICAROSoft</strong></h2>
        <p> </p>
        <p>- Para Masivos y Notificaciones (url): Mensajeria > WhatsApp > Perfiles Api</p>
        <p><h4 id="apiSendChatUrl" class="url-wrap"><?php echo htmlspecialchars($apiSendChatUrl); ?></h4></p>
<p>- Para Helpdesk (Base Url): Soporte Técnico > Helpdesk > Configuración > Api Token</p>
<p><h4 id="apiHelpdeskUrl" class="url-wrap"><?php echo htmlspecialchars($apiHelpdeskUrl); ?></h4></p>
    </div>
</div>

<style>
.card {
    border: 1px solid #ccc;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 5px;
}

.card h2 {
    margin-top: 0;
    margin-bottom: 15px;
}

.card div {
    margin-bottom: 10px;
}

.card label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.card input[type="text"],
.card textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box; /* Important to prevent padding from increasing the overall width */
}

.card button[type="submit"] {
    background-color: #7c5dfa;
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.card button[type="submit"]:hover {
    background-color: #7c5dfa;
}

.success {
    color: #155724;
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
}

.error {
    color: #721c24;
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
}
</style>