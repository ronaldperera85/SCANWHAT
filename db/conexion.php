<?php
// db/conexion.php

/**
 * Función para cargar el archivo .env SOLO para desarrollo local.
 * En producción (CapRover), esta función no se usará.
 */
function loadEnv($envPath) {
    if (!file_exists($envPath)) {
        return; // Simplemente retorna si el archivo no existe.
    }
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        // Establecer la variable de entorno si no ha sido ya establecida por el servidor
        if (!getenv($name)) {
            putenv("$name=$value");
        }
    }
}

// --- LÓGICA PRINCIPAL ---

// En CapRover, las variables de entorno ya existen.
// En local, necesitamos cargar el archivo .env.
// La función `getenv` lee las variables que CapRover nos da.
// Si `getenv` no encuentra nada, cargamos el archivo .env como respaldo.
if (getenv('DB_HOST') === false) {
    // Estamos en un entorno local, cargamos el .env
    loadEnv(__DIR__ . '/../.env');
}


// Ahora, leemos las variables directamente del entorno.
// En CapRover, `getenv` leerá las variables que configuraste en el panel.
// En local, `getenv` leerá las variables que `loadEnv` acaba de cargar.
$host = getenv('DB_HOST');
$db   = getenv('DB_DATABASE');
$user = getenv('DB_USERNAME');
$pass = getenv('DB_PASSWORD');
$port = getenv('DB_PORT') ?: '3306'; // Usamos 3306 por defecto si no se especifica

// Validar que las variables necesarias existan para evitar errores.
if (!$host || !$db || !$user) {
    // No muestres errores detallados en producción.
    // Esto es más seguro. CapRover te mostrará los logs si algo falla.
    http_response_code(500);
    die("Error de configuración del servidor: Faltan variables de entorno para la base de datos.");
}

try {
    // Construir el DSN (Data Source Name)
    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8";
    
    // Opciones de PDO para una conexión más robusta
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);

} catch (PDOException $e) {
    // En producción, es mejor registrar el error que mostrarlo al usuario.
    error_log("Error de conexión a la base de datos: " . $e->getMessage());
    http_response_code(500);
    die("Error de servicio. Por favor, inténtelo de nuevo más tarde.");
}

// ¡Conexión exitosa! La variable $pdo está lista para ser usada.
?>