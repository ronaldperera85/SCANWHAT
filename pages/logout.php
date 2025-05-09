<?php
session_start();
session_unset();
session_destroy();
header("Location: /scanwhat/login"); // Ajustado para la URL amigable
exit;
?>
