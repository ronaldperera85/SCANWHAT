<?php
session_start();

require '../db/conexion.php';

// Función para verificar si un usuario es administrador
function isAdmin($usuario_id, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE id = :id AND admin = 1");
        $stmt->execute(['id' => $usuario_id]);
        $count = $stmt->fetchColumn();

        return $count > 0; // Retorna true si es administrador, false si no lo es
    } catch (PDOException $e) {
        // Manejar el error adecuadamente (ej: loguear)
        error_log("Error al verificar el rol de administrador: " . $e->getMessage());
        return false; // Retornar false en caso de error
    }
}

// Verificar si el usuario tiene permisos de administrador
if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'], $pdo)) {
    header("Location: login.php"); // Redirigir a la página de inicio de sesión si no es administrador
    exit;
}

$message = '';
$numeros = [];
$total_mensajes_enviados = 0;  // Inicializar a 0
$numeros_conectados = 0;

try {
    // Consulta para obtener el total de mensajes enviados por todos los números
    $stmt_total_mensajes = $pdo->prepare("
        SELECT COUNT(m.id) AS total_enviados
        FROM mensajes m
    ");
    $stmt_total_mensajes->execute();
    $total_mensajes_enviados = $stmt_total_mensajes->fetchColumn();

    // Consulta para obtener el total de números conectados
    $stmt_numeros_conectados = $pdo->prepare("
        SELECT COUNT(*) 
        FROM numeros 
        WHERE estado = 'conectado'
    ");
    $stmt_numeros_conectados->execute();
    $numeros_conectados = $stmt_numeros_conectados->fetchColumn();

    // Obtener todos los números, tokens, limite mensajes y cantidad de mensajes enviados
    $stmt = $pdo->prepare("
        SELECT
            u.nombre AS nombre_usuario,
            n.numero AS numero,
            MAX(n.token) AS token,
            (
                SELECT l2.limite_mensajes
                FROM licencias l2
                WHERE l2.uid = n.numero
                ORDER BY l2.fecha_inicio DESC
                LIMIT 1
            ) AS limite_mensajes,
            (
                SELECT SUM(mensajes_enviados)
                FROM licencias l2
                WHERE l2.uid = n.numero
            ) AS total_mensajes_enviados
        FROM numeros n
        INNER JOIN usuarios u ON n.usuario_id = u.id
        LEFT JOIN licencias l ON n.numero = l.uid
        GROUP BY n.numero, u.nombre
        ORDER BY u.nombre, n.numero,  n.fecha_registro DESC
    ");
    $stmt->execute();
    $numeros = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $message = "Error al obtener la información del administrador: " . $e->getMessage();
}

?>

<div class="content-container">
    <h1><i class="fas fa-user-shield"></i> Panel de Administrador</h1>
    <?php if ($message): ?>
        <p class="<?php echo strpos($message, 'Error') !== false ? 'error' : ''; ?>"><?php echo $message; ?></p>
    <?php endif; ?>

    <div class="dashboard-summary">
        <div class="card">
            <i class="fas fa-envelope icon icon-chat" style="color: #7c5dfa;"></i>
            <h2>Mensajes Enviados</h2>
            <?php 
                $total_mensajes_enviados = 0;
                foreach ($numeros as $numero) {
                    $total_mensajes_enviados += $numero['total_mensajes_enviados'];
                }
            ?>
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
        <div class="card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Número</th>
                            <th>Token</th>
                            <th>Límite de Mensajes</th>
                            <th>Mensajes Enviados</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($numeros as $numero): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($numero['nombre_usuario']); ?></td>
                                <td><?php echo htmlspecialchars($numero['numero']); ?></td>
                                <td class="token">
                                    <span class="token-value"><?php echo htmlspecialchars($numero['token']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($numero['limite_mensajes']); ?></td>
                                <td><?php echo htmlspecialchars($numero['total_mensajes_enviados']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>