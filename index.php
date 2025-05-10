<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: /login"); // Ajustado para la URL amigable
    exit;
}

require 'db/conexion.php';  // Asegúrate de que este archivo defina la conexión $pdo

// Función para verificar si el usuario es administrador
function isAdmin($usuario_id, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE id = :id AND admin = 1");
        $stmt->execute(['id' => $usuario_id]);
        $count = $stmt->fetchColumn();

        return $count > 0;
    } catch (PDOException $e) {
        // Manejar el error adecuadamente (log, mensaje, etc.)
        error_log("Error al verificar rol de admin: " . $e->getMessage());
        return false; // Considerar no-admin en caso de error
    }
}

// Definir la ruta al favicon (ajusta la ruta si es necesario)
$faviconPath = "img/small.png";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCANWHAT</title>
    <!-- Favicon -->
    <link rel="icon" href="<?php echo $faviconPath; ?>" type="image/x-icon">
    <link rel="stylesheet" href="./css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
          integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
          crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css">
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
</head>
<body>
<div class="container"> <!--  Añade el contenedor principal -->
    <aside class="sidebar expanded">
        <header>
            <div class="logo">
                <img src="img/small.png" alt="Logo de ScanWhat">
            </div>
            <div class="codinglab">
                <p>SCANWHAT</p>
                <p>¡Conecta al Instante!</p>
            </div>
            <button class="toggle-btn"><i class="fas fa-angle-left"></i></button>
        </header>
        <nav>
            <ul>
                <li><a href="#" data-page="pages/dashboard.php"><i class="fas fa-home"></i><span>Tablero</span></a></li>
                <li><a href="#" data-page="pages/mi_cuenta.php"><i class="fas fa-user"></i><span>Mi cuenta</span></a></li>
                <li><a href="#" data-page="pages/mis_telefonos.php"><i class="fas fa-phone"></i><span>Mis teléfonos</span></a></li>
                <li><a href="#" data-page="pages/desarrolladores.php"><i class="fas fa-code"></i><span>Desarrolladores</span></a></li>
                <?php if (isAdmin($_SESSION['user_id'], $pdo)): ?>
                    <li><a href="#" data-page="pages/admin.php"><i class="fas fa-user-shield"></i><span>Administrador</span></a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <div class="bottom-section">
            <div class="theme-toggle">
                <i class="fas fa-moon"></i>
                <label class="switch">
                    <input type="checkbox" id="theme-checkbox">
                    <span class="slider round"></span>
                </label>
                <i class="fas fa-sun"></i>
            </div>
            <div class="separator"></div>
            <div class="logout">
                <span id="logout-link">
                <i class="fas fa-sign-out-alt" id="logout-icon"></i><a href="/logout">Cerrar sesión</a>
                </span>
            </div>
        </div>
    </aside>

    <div class="content-area">
        <div id="content-placeholder">
            <!-- El contenido dinámico se cargará aquí -->
        </div>
    </div>
</div> <!-- Cierra el contenedor principal -->
<script src="script.js"></script>
</body>
</html>