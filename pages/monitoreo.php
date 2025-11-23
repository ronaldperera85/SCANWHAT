<?php
session_start();

// 1) Protección básica: redirigir si no está autenticado (mismo patrón que otras páginas)
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

// 2) Incluir la conexión común (proporciona $pdo y permite getenv())
require_once __DIR__ . '/../db/conexion.php';

// 3) Obtener URL del backend (API)
$backendUrl = getenv('BACKEND_URL') ?: '';
$apiBase = rtrim($backendUrl, '/');

// 4) Obtener los números del usuario
try {
    $stmt = $pdo->prepare("SELECT numero, estado FROM numeros WHERE usuario_id = :usuario_id ORDER BY id DESC");
    $stmt->execute(['usuario_id' => $_SESSION['user_id']]);
    $telefonos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('MONITOREO: error fetching numeros - ' . $e->getMessage());
    $telefonos = [];
}

?>

<div class="content-container">
    <h1><i class="fas fa-signal"></i> Monitoreo de Conexiones</h1>

    <p class="subtitle-gray">Estado en casi tiempo real de tus números. Actualización automática cada 60 segundos.</p>

    <div style="margin: 12px 0;">
        <button id="btn-refresh-now" class="btn btn-primary">Actualizar ahora</button>
    </div>

    <div id="monitor-grid" class="monitor-grid" data-api-base="<?php echo htmlspecialchars($apiBase, ENT_QUOTES); ?>" data-phones='<?php echo json_encode(array_column($telefonos, "numero")); ?>'>
    <?php if (empty($telefonos)): ?>
        <div class="card" style="text-align: center; padding: 20px;">
            <p>No tienes números registrados.</p>
        </div>
    <?php else: ?>
        <?php foreach ($telefonos as $t): 
            $uid = htmlspecialchars($t['numero']);
            $estado_inicial = htmlspecialchars($t['estado']);
        ?>
            <!-- La clase phone-card ahora tiene el estilo gris y centrado -->
            <div id="card-<?php echo $uid; ?>" class="card phone-card h-100">
                
                <!-- Título centrado y en negrita -->
                <h4 class="card-title">
                    <i class="fas fa-mobile-alt"></i> <?php echo $uid; ?>
                </h4>

                <!-- Estado centrado (gracias al CSS de .status-display con margin auto) -->
                <div id="status-display-<?php echo $uid; ?>" class="status-display status-inicializando">
                    <div class="status-dot"></div>
                    <span id="status-text-<?php echo $uid; ?>">
                        <?php echo $estado_inicial ?: 'Verificando...'; ?>
                    </span>
                </div>
				<hr class="separator"> <!-- Una línea separadora sutil -->

                <!-- Fecha última comprobación -->
                <div id="last-checked-<?php echo $uid; ?>" class="last-checked">
                    Última comprobación: <span>Pendiente</span>
                </div>

            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

        </div> <!-- .content-container -->


