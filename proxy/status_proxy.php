<?php
// Proxy seguro para consultar el endpoint /api/status/:uid desde el servidor
// Protege la clave del backend y evita problemas de CORS o autenticación desde el navegador.
session_start();

// Requerir sesión para evitar que cualquiera use el proxy
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$uid = $_GET['uid'] ?? null;
if (!$uid) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'UID ausente']);
    exit;
}

// ---------------- CONFIGURACIÓN DE VARIABLES ----------------
// 1. Intentar obtener URL del entorno. Si falla, usar localhost:3000 por defecto.
$envBackend = getenv('BACKEND_URL');
$backend = $envBackend ? $envBackend : 'http://localhost:3000';

// 2. Intentar obtener API KEY. Si falla, dejar en blanco o poner tu clave fija.
$envApiKey = getenv('BACKEND_API_KEY');
// SI TU BACKEND REQUIERE CLAVE, CAMBIA LAS COMILLAS VACÍAS '' POR TU CLAVE:
$apiKey = $envApiKey ? $envApiKey : ''; 
// ------------------------------------------------------------

if (!$backend) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'CONFIG ERROR: BACKEND_URL no definido']);
    exit;
}

$url = rtrim($backend, '/') . '/api/status/' . rawurlencode($uid);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

// Headers
$headers = ['Accept: application/json'];
if (!empty($apiKey)) {
    $headers[] = 'Authorization: Bearer ' . $apiKey; // O 'x-api-key: ...' según tu backend
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

header('Content-Type: application/json; charset=utf-8');

if ($curlErr) {
    // Error de conexión de PHP hacia Node (puerto cerrado, node apagado, etc)
    http_response_code(502);
    echo json_encode(['success' => false, 'message' => 'Error de conexión con Backend: ' . $curlErr]);
    exit;
}

// Reenviar código de estado y cuerpo tal cual respondió Node.js
http_response_code($httpCode ?: 200);
echo $response;
?>