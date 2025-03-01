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
    
    <div class="card">
        <h2><i class="fas fa-cog"></i> Instrucciones para ajustes dentro de <strong>ICAROSoft</strong></h2>
        <p> </p>
        <p>- Para Helpdesk (Base Url): Soporte Técnico > Helpdesk > Configuración > Api Token</p>
        <p><h4 id="apiHelpdeskUrl" class="url-wrap"><?php echo htmlspecialchars($apiHelpdeskUrl); ?></h4></p>
        <p>- Para Masivos y Notificaciones (url): Mensajeria > WhatsApp > Perfiles Api</p>
        <p><h4 id="apiSendChatUrl" class="url-wrap"><?php echo htmlspecialchars($apiSendChatUrl); ?></h4></p>
    </div>
</div>