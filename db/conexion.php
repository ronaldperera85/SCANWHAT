<?php

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
     return true; //Indicar que el archivo se cargó correctamente
}


// Cargar las variables de entorno desde el archivo .env
if (!loadEnv(__DIR__ . '/../.env')) {
    die("Error: No se pudo cargar el archivo .env"); // Detener la ejecución si falla la carga del archivo
}

$host = $_ENV['DB_HOST'];
$db = $_ENV['DB_DATABASE'];
$user = $_ENV['DB_USERNAME'];
$pass = $_ENV['DB_PASSWORD'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error en la conexión a la base de datos: " . $e->getMessage());
}
?>