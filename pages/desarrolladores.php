<?php
session_start();

// Bloque de protección con redirección
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Si no ha iniciado sesión, lo redirigimos a la página de login.
    header("Location: login"); // <-- ¡La línea clave!
    exit(); // Detener la ejecución del script.
}


// NUEVO: Incluir la conexión a la base de datos (asegúrate que la ruta sea correcta)
require_once __DIR__ . '/../db/conexion.php'; 

// --- La función para cargar el .env se queda, pero es mejor centralizarla como te comenté ---
// Si ya la moviste a otro archivo como `init.php`, puedes eliminar esta función de aquí
// y en su lugar poner: require_once __DIR__ . '/../config/init.php';
if (!function_exists('loadEnv')) {
    function loadEnv($envPath) {
        if (!file_exists($envPath)) {
            return false;
        }
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
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
$apiSendChatUrl  = $baseUrl . '/api/send/chat';
$apiHelpdeskUrl = $baseUrl . '/api';

// NUEVO: Consultar la base de datos para obtener los teléfonos CONECTADOS del usuario
$telefonos_conectados = [];
try {
    $stmt = $pdo->prepare("SELECT numero, token FROM numeros WHERE usuario_id = :usuario_id AND estado = 'conectado' ORDER BY numero ASC");
    $stmt->execute(['usuario_id' => $_SESSION['user_id']]);
    $telefonos_conectados = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // En caso de error, el array $telefonos_conectados quedará vacío, lo cual es manejado en el HTML.
    error_log("Error al obtener teléfonos para desarrolladores: " . $e->getMessage());
}

?>
<div class="content-container">
    <h1><i class="fas fa-code"></i> Desarrolladores</h1>

    <div class="card" data-api-send-chat-url="<?php echo htmlspecialchars($apiSendChatUrl); ?>">
        <h2><i class="fas fa-envelope"></i> Prueba de Envío de Mensaje</h2>
        <form id="sendMessageForm" novalidate>
            <!-- CAMPO: WhatsApp Account UID (SELECT - No lleva icono interno) -->
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
            
            <!-- CAMPO: API Token (READONLY - Con input-icon-group) -->
            <div class="form-group">
                <label for="apiTokenSend">Token de API:</label>
                <div class="input-icon-group">
                    <i class="fas fa-fingerprint icon"></i> 
                    <input type="text" id="apiTokenSend" name="apiTokenSend" class="form-control" required readonly placeholder="Se autocompletará al seleccionar un número">
                </div>
            </div>

            <!-- CAMPO: Recipient Account (Con input-icon-group) -->
            <div class="form-group">
                <label for="recipientAccountSend">Cuenta del destinatario:</label>
                <div class="input-icon-group">
                    <i class="fas fa-phone icon"></i>
                    <input type="text" id="recipientAccountSend" name="recipientAccountSend" class="form-control" required placeholder="Ej: 593991234567">
                </div>
            </div>
            
            <!-- CAMPO: Message Text (TEXTAREA - Solo añadimos form-group por consistencia) -->
            <div class="form-group">
                <label for="messageTextSend">Texto del mensaje:</label>
                <textarea id="messageTextSend" name="messageTextSend" class="form-control" rows="3" required placeholder="Mensaje..."></textarea>
            </div>
            
            <!-- Botón de envío con SPAN para proteger el icono -->
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i> 
                <span id="sendButtonText">Enviar Mensaje</span>
            </button>
        </form>
        <!-- Contenedor de respuesta con estilos iniciales -->
        <div id="sendMessageResponse" style="margin-top: 15px;"></div>
    </div>

    <div class="card">
    <h2><i class="fas fa-cog"></i> Instrucciones para implementar en Helpdesk de <strong>ICAROSoft</strong></h2>
    <p> </p>
    <p>- (Url) para el campo de Envío: Configuración > Mensajería > Perfiles / Api - Token WhatsApp > Perfil de Envío</p>        
    <div class="url-wrap">
        <i class="fas fa-link url-icon"></i> <!-- ÍCONO DE ENLACE -->
        <span><?php echo htmlspecialchars($apiSendChatUrl); ?></span>
    </div>
    <p>- (Base Url) para el campo de Recepción: Configuración > Mensajería > Perfiles / Api - Token WhatsApp > Perfil de Recepción</p>
    <div class="url-wrap" style="margin-top:15px;">
        <i class="fas fa-link url-icon"></i> <!-- ÍCONO DE ENLACE -->
        <span><?php echo htmlspecialchars($apiHelpdeskUrl); ?></span>
    </div>
</div>
</div>

<!-- Estilos (Si este código no está dentro de tu <head> y ya tienes el CSS cargado, puedes eliminar esta sección <style>) -->
<style>
/* Tus estilos existentes con algunas mejoras */
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

<!-- Script para el formulario de prueba de envío de mensaje -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sendMessageForm = document.getElementById('sendMessageForm');
        
        if (sendMessageForm) {
            sendMessageForm.addEventListener('submit', async function(event) {
                event.preventDefault();

                const submitButton = sendMessageForm.querySelector('button[type="submit"]');
                const buttonText = document.getElementById('sendButtonText'); 
                const responseContainer = document.getElementById('sendMessageResponse');

                const originalText = buttonText.textContent;
                submitButton.disabled = true;
                buttonText.textContent = 'Enviando...';
                responseContainer.innerHTML = ''; // Limpiar respuesta anterior

                // Recolección de datos
                const waAccount = document.getElementById('waAccountSend').value;
                const apiToken = document.getElementById('apiTokenSend').value;
                const recipientAccount = document.getElementById('recipientAccountSend').value;
                const messageText = document.getElementById('messageTextSend').value;

                // Simple Validación (Cliente)
                if (!waAccount || !apiToken || !recipientAccount || !messageText) {
                    responseContainer.innerHTML = '<p class="error">Por favor, complete todos los campos.</p>';
                    submitButton.disabled = false;
                    buttonText.textContent = originalText;
                    return;
                }
                
                // === Lógica de envío (AJAX / Fetch) ===
                try {
                    const apiSendChatUrl = sendMessageForm.closest('.card').getAttribute('data-api-send-chat-url');
                    
                    const formData = new URLSearchParams();
                    formData.append('waAccount', waAccount);
                    formData.append('token', apiToken); // Asegúrate de que tu API espera 'token' o 'apiToken'
                    formData.append('recipient', recipientAccount);
                    formData.append('message', messageText);

                    // Envío real (Ajustar según cómo espera tu backend los datos)
                    const response = await fetch(apiSendChatUrl, { 
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: formData
                    });
                    
                    const data = await response.json(); 

                    if (data.success) {
                        responseContainer.innerHTML = `<p class="success">Éxito: ${data.message}</p>`;
                    } else {
                        responseContainer.innerHTML = `<p class="error">Error: ${data.message || 'Error desconocido del servidor.'}</p>`;
                    }
                    
                } catch (error) {
                    console.error('Error de red o de servidor:', error);
                    responseContainer.innerHTML = '<p class="error">Error de Conexión: Ocurrió un error de red o la URL API es inaccesible.</p>';
                } finally {
                    submitButton.disabled = false;
                    buttonText.textContent = originalText;
                }
            });
        }

        // Script para auto-completar el token al seleccionar el número
        const waAccountSelect = document.getElementById('waAccountSend');
        const apiTokenInput = document.getElementById('apiTokenSend');

        if (waAccountSelect && apiTokenInput) {
            waAccountSelect.addEventListener('change', function() {
                // Asegúrate de que el input tenga la clase form-control para que el CSS funcione
                apiTokenInput.classList.add('form-control');
                
                const selectedOption = waAccountSelect.options[waAccountSelect.selectedIndex];
                const token = selectedOption.getAttribute('data-token');
                apiTokenInput.value = token ? token : '';
            });
        }
    });

</script>