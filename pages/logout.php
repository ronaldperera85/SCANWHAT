<?php
session_start();
session_unset();
session_destroy();
header("Location: login"); // Ajustado para la URL amigable
exit;
?>
