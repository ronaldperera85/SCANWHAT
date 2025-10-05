<?php
session_start();

// Bloque de protección con redirección
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Si no ha iniciado sesión, lo redirigimos a la página de login.
    header("Location: login"); // <-- ¡La línea clave!
    exit(); // Detener la ejecución del script.
}

require '../db/conexion.php';

$message = '';
$total_mensajes_enviados = 0;
$numeros_conectados = 0;
$licencias = [];

try {
    // Obtener el ID del usuario actual
    $usuario_id = $_SESSION['user_id'];

    // Consulta para obtener los números asociados al usuario
    $stmt = $pdo->prepare("SELECT * FROM numeros WHERE usuario_id = :usuario_id");
    $stmt->execute(['usuario_id' => $usuario_id]);
    $numeros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Inicializar el conteo de los mensajes enviados y estado de conexión
    foreach ($numeros as $numero) {
        if ($numero['estado'] === 'conectado'){
            $numeros_conectados++;
        }

        // Consulta para obtener el total de mensajes enviados por cada número CONECTADO
        $query_mensajes = "SELECT SUM(l.mensajes_enviados) AS total_enviados FROM licencias l WHERE l.uid = :numero";
        $stmt_mensajes = $pdo->prepare($query_mensajes);
        $stmt_mensajes->execute(['numero' => $numero['numero']]);
        $mensajes_data = $stmt_mensajes->fetch(PDO::FETCH_ASSOC);
        $total_mensajes_enviados +=  $mensajes_data['total_enviados'] ?? 0;

        // Consulta para obtener la información de la licencia de cada número
        $query_licencia = "SELECT tipo_licencia, limite_mensajes, mensajes_enviados, estado_licencia, fecha_inicio, fecha_fin FROM licencias WHERE uid = :uid";
        $stmt_licencia = $pdo->prepare($query_licencia);
        $stmt_licencia->execute(['uid' => $numero['numero']]);
        $licencia = $stmt_licencia->fetch(PDO::FETCH_ASSOC) ?? [];
        $licencias[] = $licencia;

    }
} catch (PDOException $e) {
    $message = "Error al obtener la información del dashboard: " . $e->getMessage();
}
?>

<div class="content-container">
    <h1><i class="fas fa-home"></i> Tablero</h1>
    <?php if ($message): ?>
        <p class="<?php echo strpos($message, 'Error') !== false ? 'error' : ''; ?>"><?php echo $message; ?></p>
    <?php endif; ?>

    <div class="dashboard-summary">
        <div class="card">
            <i class="fas fa-envelope icon icon-chat" style="color: #7c5dfa;"></i>
            <h2>Mensajes Enviados</h2>
            <p style="text-align: center;"><h1><?php echo $total_mensajes_enviados; ?></h1></p>
        </div>
        <div class="card">
            <i class="fas fa-phone-alt icon icon-instances" style="color: #7c5dfa;"></i>
            <h2>Números Conectados</h2>
            <p style="text-align: center;"><h1><?php echo $numeros_conectados; ?></h1></p>
        </div>
    </div>

    <?php if (empty($numeros)): ?>
        <p>No hay números registrados en el sistema.</p>
    <?php else: ?>
        <?php foreach ($numeros as $index => $numero): ?>
            <div class="card">
                <h2 style="text-align: center;">Licencia para el número <?php echo $numero['numero'] ?></h2>
                <?php if (!empty($licencias[$index])):
                    $licencia = $licencias[$index];
                    $mensajes_disponibles = !empty($licencia) ? max(0, $licencia['limite_mensajes'] - $licencia['mensajes_enviados']) : 0;
                    ?>
                    <p><strong>Tipo:</strong> <?php echo htmlspecialchars($licencia['tipo_licencia']); ?></p>
                    <p><strong>Límite de Mensajes:</strong> <?php echo htmlspecialchars($licencia['limite_mensajes']); ?></p>
                    <p><strong>Mensajes Disponibles:</strong> <?php echo htmlspecialchars($mensajes_disponibles); ?></p>
                    <p><strong>Estado:</strong> <?php echo htmlspecialchars($licencia['estado_licencia']); ?></p>
                    <p><strong>Inicio:</strong> <?php echo htmlspecialchars($licencia['fecha_inicio']); ?></p>
                    <?php if ($licencia['fecha_fin']): ?>
                        <p><strong>Fin:</strong> <?php echo htmlspecialchars($licencia['fecha_fin']); ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p>No hay información de licencia disponible.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>