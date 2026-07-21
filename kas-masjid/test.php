<?php
require_once 'includes/config.php';
echo '<h2 style="font-family:sans-serif;padding:20px">';
echo 'APP_URL terdeteksi: <strong style="color:green">' . APP_URL . '</strong><br><br>';
echo 'CSS URL: <strong>' . APP_URL . '/assets/css/style.css</strong><br><br>';
echo '<a href="' . APP_URL . '/assets/css/style.css" target="_blank">Klik untuk test CSS file</a>';
echo '</h2>';
?>
