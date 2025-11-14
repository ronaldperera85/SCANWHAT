<?php
session_start();

// 1. Verificar si el usuario está autenticado (esto estaba bien)
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

// 2. Incluir la conexión a la base de datos (esto estaba bien)
//    Este archivo ya se encarga de las variables de entorno y de crear $pdo.
require_once __DIR__ . '/../db/conexion.php'; 

// --- SECCIÓN CORREGIDA ---
// Se eliminó toda la lógica duplicada de loadEnv.
// Ahora leemos la variable de entorno BACKEND_URL directamente.
// getenv() funcionará tanto en local (gracias a conexion.php) como en CapRover.

$backendUrl = getenv('BACKEND_URL');

// Es una buena práctica verificar que la variable exista antes de usarla.
if (!$backendUrl) {
    // Si esta variable no está en CapRover, la página fallará con un error claro.
    die("Error crítico de configuración: La variable de entorno BACKEND_URL no está definida.");
}

$baseUrl = rtrim($backendUrl, '/');
$apiSendChatUrl  = $baseUrl . '/api/send/chat';
$apiHelpdeskUrl = $baseUrl . '/api';

// 3. Obtener los teléfonos de la base de datos (esto estaba bien)
$telefonos_conectados = [];
try {
    $stmt = $pdo->prepare("SELECT numero, token FROM numeros WHERE usuario_id = :usuario_id AND estado = 'conectado' ORDER BY numero ASC");
    $stmt->execute(['usuario_id' => $_SESSION['user_id']]);
    $telefonos_conectados = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener teléfonos para desarrolladores: " . $e->getMessage());
}

?>
<div class="content-container">
    <h1><i class="fas fa-code"></i> Desarrolladores</h1>

    <!-- ===================================================================== -->
    <!--   LÍNEA CORREGIDA: Se añadió data-api-base-url                      -->
    <!-- ===================================================================== -->
    <div class="card" 
         data-api-send-chat-url="<?php echo htmlspecialchars($apiSendChatUrl); ?>" 
         data-api-base-url="<?php echo htmlspecialchars($baseUrl); ?>">

        <h2><i class="fas fa-envelope"></i> Prueba de Envío de Mensaje</h2>
        <form id="sendMessageForm" novalidate>
            <div class="form-group">
                <label for="waAccountSend">UID de la cuenta de WhatsApp:</label>
                <select id="waAccountSend" name="waAccountSend" class="form-control" required>
                    <option value="" disabled selected>-- Seleccione un número --</option>
                    <?php if (empty($telefonos_conectados)): ?>
                        <option value="" disabled>No tienes números conectados</option>
                    <?php else: ?>
                        <?php foreach ($telefonos_conectados as $telefono): ?>
                            <option value="<?php echo htmlspecialchars($telefono['numero']); ?>" data-token="<?php echo htmlspecialchars($telefono['token']); ?>">
                                <?php echo htmlspecialchars($telefono['numero']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="apiTokenSend">Token de API:</label>
                <div class="input-icon-group">
                    <i class="fas fa-fingerprint icon"></i> 
                    <input type="text" id="apiTokenSend" name="apiTokenSend" class="form-control" required readonly placeholder="Se autocompletará al seleccionar un número">
                </div>
            </div>

            <div class="form-group">
                <label for="groupSelect">Grupos (Opcional):</label>
                <div class="input-icon-group">
                    <i class="fas fa-users icon"></i>
                    <select id="groupSelect" name="groupSelect" class="form-control" disabled>
                        <option value="">-- Seleccione un número primero --</option>
                    </select>
                    <span id="groupLoader" style="display: none; margin-left: 10px;">Cargando...</span>
                </div>
            </div>

            <div class="form-group">
                <label for="recipientAccountSend">Cuenta del destinatario:</label>
                <div class="input-icon-group">
                    <i class="fas fa-phone icon"></i>
                    <input type="text" id="recipientAccountSend" name="recipientAccountSend" class="form-control" required placeholder="Ej: 593991234567">
                </div>
            </div>
            
            <div class="form-group">
                <label for="messageTextSend">Texto del mensaje:</label>
                <textarea id="messageTextSend" name="messageTextSend" class="form-control" rows="3" required placeholder="Mensaje..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i> 
                <span id="sendButtonText">Enviar Mensaje</span>
            </button>
        </form>
        <div id="sendMessageResponse" style="margin-top: 15px;"></div>
    </div>

    <div class="card">
        <h2><i class="fas fa-cog"></i> Instrucciones para implementar en Helpdesk de <strong>ICAROSoft</strong></h2>
        <p>- (Url) para el campo de Envío:</p>        
        <div class="url-wrap">
            <i class="fas fa-link url-icon"></i>
            <span><?php echo htmlspecialchars($apiSendChatUrl); ?></span>
        </div>
        <p>- (Base Url) para el campo de Recepción:</p>
        <div class="url-wrap" style="margin-top:15px;">
            <i class="fas fa-link url-icon"></i>
            <span><?php echo htmlspecialchars($apiHelpdeskUrl); ?></span>
        </div>
    </div>
</div>

<style>

.card {
    border: 1px solid #ccc;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 5px;
}
.card h2 { margin-top: 0; margin-bottom: 15px; }
/* Ajustado para no afectar a los .url-wrap si son divs */
.card form div { margin-bottom: 10px; } 
.card label { display: block; margin-bottom: 5px; font-weight: bold; }

/* Estilos unificados para todos los campos del formulario */
.card input[type="text"],
.card textarea,
.card select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box; 
    background-color: #fff; /* Fondo blanco */
    color: #333;           /* Texto oscuro */
}

/* Estilo para campos de solo lectura */
.card input[readonly] {
    background-color: #f0f0f0; /* Un gris claro para diferenciar */
    cursor: not-allowed;
}

.card button[type="submit"] {
    background-color: #7c5dfa;
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}
.card button[type="submit"]:hover { background-color: #6a48e5; }

#sendMessageResponse p {
    padding: 10px;
    border-radius: 4px;
    margin: 0;
}
.success { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; }
.error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; }

/* Estilo para las URLs, ahora imitando a los inputs */
.url-wrap {
    /* Propiedades copiadas de los inputs para consistencia */
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box; 
    background-color: #fff; /* Fondo blanco, como los inputs */
    color: #1a1a2e;            /* Texto oscuro, como los inputs */
    
    /* Propiedades específicas para la URL */
    font-family: monospace;
    font-size: 0.9em;
    word-break: break-all;
    
    /* Reset para que se vea bien si usas <h4> o <div> */
    margin: 0;
    font-weight: normal; 
}

/* IMPORTANTE: Hemos ELIMINADO la regla 'body.dark-theme .url-wrap' 
   para que se mantenga blanco en ambos modos. */
</style>