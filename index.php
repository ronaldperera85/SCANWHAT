<?php
session_start();

// Si el usuario no está autenticado, redirigir a la página de inicio de sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: pages/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCANWHAT</title>
    <link rel="stylesheet" href="./css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
          integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
          crossorigin="anonymous" referrerpolicy="no-referrer"/>
</head>
<body>
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
            <br><br/>
            <div class="logout">
            <span id="logout-link">
                <i class="fas fa-sign-out-alt" id="logout-icon"></i><a href="pages/logout.php">Cerrar sesión</a>
            </span>
            </div>
        </div>
        <p></p>
    </aside>

<div class="content-area">
    <div id="content-placeholder">
        <!-- El contenido dinámico se cargará aquí -->
    </div>
</div>

<script src="script.js"></script>
</body>
</html>