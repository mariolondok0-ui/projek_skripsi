<?php
// Ganti 'admin123' dengan password baru yang kamu inginkan
$password_baru = 'admin123';

// Proses mengubah password menjadi ciphertext (Bcrypt Hash)
$ciphertext = password_hash($password_baru, PASSWORD_BCRYPT);

echo "<h3>Generator Ciphertext Password</h3>";
echo "Password Asli : <b>" . $password_baru . "</b><br><br>";
echo "Ciphertext (Copy teks di bawah ini ke tabel users di phpMyAdmin): <br>";
echo "<textarea rows='3' cols='70'>" . $ciphertext . "</textarea>";
?>