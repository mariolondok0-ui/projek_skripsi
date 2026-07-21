<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'kas_masjid');
define('APP_NAME', 'Kas Masjid Baiturrohman');
define('MASJID_NAME', 'Masjid Baiturrohman');
define('MASJID_ALAMAT', 'Kp. Cimencek, Desa Cintaasih, Kec. Samarang, Kab. Garut');

// ---- AUTO-DETECT APP_URL ----
// Supaya tidak perlu diubah manual, URL otomatis menyesuaikan lokasi folder
$_protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_script   = $_SERVER['SCRIPT_NAME'] ?? '';

// Cari posisi folder /kas-masjid/ dalam path
$_base = '';
if (preg_match('#(.*?/kas-masjid)#i', $_script, $m)) {
    $_base = $m[1];
} else {
    // Fallback: ambil 2 level dari script name
    $_base = rtrim(dirname(dirname($_script)), '/\\');
    if ($_base === '' || $_base === '.') $_base = '/kas-masjid';
}
define('APP_URL', $_protocol . '://' . $_host . $_base);

// ---- KONEKSI DATABASE ----
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:30px;background:#fee;border:2px solid red;margin:20px;border-radius:10px">
        <h3 style="color:red">&#9888; Koneksi Database Gagal</h3>
        <p>Error: ' . $conn->connect_error . '</p>
        <p>Pastikan XAMPP MySQL sudah berjalan dan database <strong>kas_masjid</strong> sudah diimport.</p>
    </div>');
}
$conn->set_charset('utf8mb4');
date_default_timezone_set('Asia/Jakarta');
if (session_status() === PHP_SESSION_NONE) session_start();

// ---- HELPER FUNCTIONS ----
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/login.php');
        exit();
    }
}
function redirect($url) {
    header('Location: ' . $url);
    exit();
}
function sanitize($data) {
    global $conn;
    return $conn->real_escape_string(htmlspecialchars(trim($data)));
}
function setAlert($type, $msg) {
    $_SESSION['alert'] = ['type' => $type, 'message' => $msg];
}
function getAlert() {
    if (isset($_SESSION['alert'])) {
        $a = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return $a;
    }
    return null;
}
function getSaldo($conn) {
    $m = $conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='masuk'")->fetch_assoc()['t'];
    $k = $conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='keluar'")->fetch_assoc()['t'];
    return $m - $k;
}
?>
