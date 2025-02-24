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
    <!-- Eliminar este div -->
    <!-- <div class="container">  -->
         <!--<p>Desde esta sección podrás probar el envío de mensajes.</p>

        <div class="card" data-api-send-chat-url="<?php echo htmlspecialchars($apiSendChatUrl); ?>">
            <h2>Probar envío de mensajes</h2>
            <form id="sendMessageForm">
                <div class="form-group">
                    <label for="apiTokenSend">API Token</label>
                    <input type="text" id="apiTokenSend" name="apiTokenSend" class="form-control"
                           placeholder="Lo encontrarás en 'Mis teléfonos > Token'" required>
                </div>
                <div class="form-group">
                    <label for="waAccountSend">Tu cuenta de WA</label>
                    <input type="text" id="waAccountSend" name="waAccountSend" class="form-control"
                           placeholder="Número de teléfono con el prefijo del país: 584125927917" required>
                </div>
                <div class="form-group">
                    <label for="recipientAccountSend">Cuenta WA del destinatario</label>
                    <input type="text" id="recipientAccountSend" name="recipientAccountSend" class="form-control"
                           placeholder="Número de teléfono con el prefijo del país: 584125927917" required>
                </div>
                 <div class="form-group">
                    <label for="messageTextSend">Texto del mensaje a enviar</label>
                    <textarea id="messageTextSend" name="messageTextSend" class="form-control"
                              placeholder="Hola mundo!" required></textarea>
                </div>
                <button type="submit"  class="btn btn-primary">Enviar</button>
                <div id="sendMessageResponse" class="response"></div>
            </form>
        </div>-->

        <div class="card">
        <h2><i class="fas fa-cog"></i> Instrucciones para ajustes dentro de <strong>ICAROSoft</strong></h2>
            <p> </p>
            <p>- Para Helpdesk (Base Url): Soporte Técnico > Helpdesk > Configuración > Api Token</p>
            <p><h4 id="apiHelpdeskUrl" class="url-wrap"><?php echo htmlspecialchars($apiHelpdeskUrl); ?></h4></p>
            <p>- Para Masivos y Notificaciones (url): Mensajeria > WhatsApp > Perfiles Api</p>
            <p><h4 id="apiSendChatUrl" class="url-wrap"><?php echo htmlspecialchars($apiSendChatUrl); ?></h4></p>
        </div>
    <!-- Eliminar este div -->
    <!-- </div> -->
</div>