<?php
require_once 'includes/config.php';

// Helper Format Tanggal & Bulan Indonesia
function tgl_indo_pub($tanggal, $dengan_waktu = false) {
    if (empty($tanggal) || $tanggal == '0000-00-00') return '-';
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $ts = strtotime($tanggal);
    $tgl = date('d', $ts);
    $bln = $bulan[(int)date('m', $ts)];
    $thn = date('Y', $ts);
    if ($dengan_waktu) {
        return "$tgl $bln $thn " . date('H:i', $ts);
    }
    return "$tgl $bln $thn";
}

function bln_indo_pub($bulan_angka) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    return $bulan[(int)$bulan_angka] ?? '';
}

$total_masuk  = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='masuk' AND deleted_at IS NULL")->fetch_assoc()['t'];
$total_keluar = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='keluar' AND deleted_at IS NULL")->fetch_assoc()['t'];
$saldo        = $total_masuk - $total_keluar;
$total_trx    = (int)$conn->query("SELECT COUNT(*) as t FROM transaksi WHERE deleted_at IS NULL")->fetch_assoc()['t'];
$bln          = date('Y-m');
$tahun        = (int)date('Y');
$masuk_bln    = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='masuk' AND deleted_at IS NULL AND DATE_FORMAT(tanggal,'%Y-%m')='$bln'")->fetch_assoc()['t'];
$keluar_bln   = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='keluar' AND deleted_at IS NULL AND DATE_FORMAT(tanggal,'%Y-%m')='$bln'")->fetch_assoc()['t'];
$trx_bln      = (int)$conn->query("SELECT COUNT(*) as t FROM transaksi WHERE deleted_at IS NULL AND DATE_FORMAT(tanggal,'%Y-%m')='$bln'")->fetch_assoc()['t'];

$nama_bulan_singkat = [1=>'Jan', 2=>'Feb', 3=>'Mar', 4=>'Apr', 5=>'Mei', 6=>'Jun', 7=>'Jul', 8=>'Agt', 9=>'Sep', 10=>'Okt', 11=>'Nov', 12=>'Des'];

// Bar Chart 6 Bulan
$chart_labels = $chart_masuk = $chart_keluar = [];
for ($i = 5; $i >= 0; $i--) {
    $b = date('Y-m', strtotime("-$i month"));
    $bulan_angka = (int)date('m', strtotime("-$i month"));
    $chart_labels[] = $nama_bulan_singkat[$bulan_angka] . ' ' . date('Y', strtotime("-$i month"));
    $chart_masuk[]  = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='masuk' AND deleted_at IS NULL AND DATE_FORMAT(tanggal,'%Y-%m')='$b'")->fetch_assoc()['t'];
    $chart_keluar[] = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='keluar' AND deleted_at IS NULL AND DATE_FORMAT(tanggal,'%Y-%m')='$b'")->fetch_assoc()['t'];
}

// Line Chart 12 Bulan (Pemasukan & Pengeluaran)
$line_labels = $line_masuk = $line_keluar = [];
for ($m = 1; $m <= 12; $m++) {
    $b  = sprintf('%04d-%02d', $tahun, $m);
    $mk = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='masuk' AND deleted_at IS NULL AND DATE_FORMAT(tanggal,'%Y-%m')='$b'")->fetch_assoc()['t'];
    $kl = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='keluar' AND deleted_at IS NULL AND DATE_FORMAT(tanggal,'%Y-%m')='$b'")->fetch_assoc()['t'];
    
    $line_labels[] = $nama_bulan_singkat[$m];
    $line_masuk[]  = $mk;
    $line_keluar[] = $kl;
}

// Data Donat Gabungan (Pemasukan vs Pengeluaran)
$pie_combined_labels = ['Total Pemasukan', 'Total Pengeluaran'];
$pie_combined_data   = [$total_masuk, $total_keluar];

$trx_terbaru = $conn->query("SELECT t.*,k.nama_kategori FROM transaksi t JOIN kategori k ON t.kategori_id=k.id WHERE t.deleted_at IS NULL ORDER BY t.tanggal DESC,t.id DESC LIMIT 6");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= APP_NAME ?> – Transparansi Keuangan Masjid</title>
<!-- Integrasi Font Awesome & Chart.js -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
/* ===== ROOT VARIABLES ===== */
:root {
  --primary: #1e6eb5;
  --primary-dark: #155a96;
  --secondary: #c9a84c;
  --secondary-light: #dcb858;
  --bg-main: #f4f7fb;
  --bg-card: #ffffff;
  --text-primary: #1e293b;
  --text-secondary: #475569;
  --text-muted: #64748b;
  --border-light: #e2e8f0;
  --danger: #ef4444;
  --success: #10b981;
  --info: #0ea5e9;
  --shadow-sm: 0 4px 10px rgba(30, 110, 181, 0.05);
  --shadow: 0 10px 25px rgba(30, 110, 181, 0.08);
  --shadow-lg: 0 15px 35px rgba(30, 110, 181, 0.12);
  --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* ===== GLOBAL STYLES & FONT ===== */
* { 
  box-sizing: border-box; 
  margin: 0; 
  padding: 0; 
}
body, h1, h2, h3, h4, h5, h6, p, span, div, a, button, input, table, th, td {
    font-family: 'Cambria', 'Times New Roman', Times, serif;
}
.fas, .far, .fab, .fa-solid, .fa-regular, .fa-brands {
    font-family: "Font Awesome 6 Free" !important;
}

body { background-color: var(--bg-main); color: var(--text-primary); overflow-x: hidden; }
.container { width: 100%; max-width: 1140px; margin: 0 auto; padding: 0 20px; }
a { text-decoration: none; }

/* ===== NAVBAR GLASSMORPHISM ===== */
.navbar-custom {
  position: fixed;
  top: 0;
  width: 100%;
  background: rgba(15, 43, 72, 0.85);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.15);
  padding: 12px 4%; 
  z-index: 1000;
  transition: all 0.3s ease;
}

.navbar-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
  max-width: 1600px; 
  margin: 0 auto;
}

.navbar-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  color: #ffffff;
  text-decoration: none;
}

.img-logo {
  width: 45px;
  height: 45px;
  border-radius: 12px;
  object-fit: cover;
  border: 2px solid rgba(255, 255, 255, 0.5);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

.brand-text {
  display: flex;
  flex-direction: column;
}

.brand-text .title {
  font-size: 1.15rem;
  font-weight: bold;
  letter-spacing: 0.5px;
  line-height: 1.2;
}

.brand-text .subtitle {
  font-size: 0.8rem;
  color: rgba(255, 255, 255, 0.8);
}

.btn-login-nav, .btn-admin-nav {
  background: var(--primary);
  color: #fff;
  text-decoration: none;
  padding: 10px 22px;
  border-radius: 10px;
  font-weight: bold;
  font-size: 0.95rem;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: 0.3s;
  box-shadow: 0 4px 12px rgba(30, 110, 181, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.btn-login-nav:hover, .btn-admin-nav:hover {
  background: var(--primary-dark);
  transform: translateY(-2px);
  box-shadow: 0 6px 15px rgba(30, 110, 181, 0.4);
}

/* ===== HERO SECTION ===== */
.hero-section {
  min-height: 100svh;
  display: flex;
  align-items: center;
  position: relative;
  padding: 120px 0 60px;
  background: linear-gradient(135deg, rgba(10,31,53,0.85) 0%, rgba(30,110,181,0.75) 60%, rgba(91,163,217,0.65) 100%), 
              url('<?= APP_URL ?>/assets/img/masjid.jpg') center center / cover no-repeat;
}

.hero-content {
  position: relative;
  z-index: 2;
  text-align: center;
  width: 100%;
}

.hero-badge-pub {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: rgba(255, 255, 255, 0.15);
  color: #fff;
  padding: 8px 20px;
  border-radius: 99px;
  font-size: 1rem;
  font-weight: bold;
  border: 1px solid rgba(255, 255, 255, 0.3);
  margin-bottom: 25px;
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  animation: fadeInDown 0.8s ease;
}

.badge-logo {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  object-fit: cover;
}

.hero-title {
  font-size: clamp(2.2rem, 5vw, 3.8rem);
  font-weight: bold;
  color: #ffffff;
  line-height: 1.25;
  margin-bottom: 20px;
  text-shadow: 2px 4px 8px rgba(0,0,0,0.4);
  animation: fadeIn 0.8s ease 0.1s both;
}
.hero-title span { color: #f6d365; }

.hero-desc {
  font-size: clamp(1rem, 2vw, 1.15rem);
  color: rgba(255, 255, 255, 0.9);
  max-width: 650px;
  line-height: 1.6;
  margin: 0 auto 40px;
  text-shadow: 1px 2px 4px rgba(0,0,0,0.4);
  animation: fadeIn 0.8s ease 0.2s both;
}

.hero-stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
  max-width: 900px;
  margin: 0 auto;
  animation: fadeIn 0.8s ease 0.4s both;
}

.glass-stat-card {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.25);
  border-radius: 16px;
  padding: 25px 15px;
  text-align: center;
  transition: var(--transition);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}
.glass-stat-card:hover {
  transform: translateY(-5px);
  background: rgba(255, 255, 255, 0.15);
  box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
}

.glass-stat-card .hv {
  font-size: clamp(1.4rem, 3vw, 2rem);
  font-weight: bold;
  color: #ffffff;
  margin-bottom: 8px;
  text-shadow: 1px 2px 4px rgba(0,0,0,0.3);
}
.glass-stat-card .hl {
  font-size: 0.9rem;
  color: rgba(255, 255, 255, 0.85);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

/* ===== GENERAL SECTIONS ===== */
.pub-sec { padding: 70px 0; }
.sec-tag {
  display: inline-flex; align-items: center; gap: 8px;
  background: rgba(30, 110, 181, 0.1); color: var(--primary);
  padding: 6px 16px; border-radius: 99px;
  font-size: 0.9rem; font-weight: bold; margin-bottom: 12px;
  border: 1px solid rgba(30, 110, 181, 0.2);
}
.sec-h {
  font-size: clamp(1.8rem, 4vw, 2.4rem); font-weight: bold;
  color: var(--text-primary); margin-bottom: 12px;
}
.sec-h span { color: var(--primary); }

/* ===== SALDO CARD UTAMA & KOTAK GABUNGAN (DISAMAKAN PERSIS) ===== */
.saldo-card-pub, .mstat-card-combined {
  background: linear-gradient(135deg, #1e6eb5, #4ca1e0);
  border-radius: 20px; padding: 30px; color: #fff;
  position: relative; overflow: hidden;
  box-shadow: 0 15px 35px rgba(30, 110, 181, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.2);
}
.saldo-card-pub::after, .mstat-card-combined::after {
  content: ''; position: absolute; top: -40px; right: -40px;
  width: 150px; height: 150px; background: rgba(255,255,255,.1); border-radius: 50%;
}

.mstat-card-combined {
  display: flex; flex-direction: column; gap: 20px;
}

.mstat-item-row {
  display: flex; align-items: center; gap: 16px;
  padding-bottom: 18px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}
.mstat-item-row:last-child {
  padding-bottom: 0;
  border-bottom: none;
}

.mstat-item-row .mi {
  width: 48px; height: 48px; border-radius: 14px; flex-shrink: 0;
  background: rgba(255, 255, 255, 0.2); color: #ffffff;
  display: flex; align-items: center; justify-content: center; font-size: 1.15rem;
}

.mstat-item-row.is-keluar .mi i {
  color: #ef4444 !important;
}

.mstat-item-row .mv { font-size: 1.25rem; font-weight: bold; color: #ffffff; }
.mstat-item-row .ml { font-size: 0.9rem; color: rgba(255, 255, 255, 0.85); margin-top: 3px; }

/* ===== GRAFIK ===== */
.chart-card {
  background: var(--bg-card); border-radius: 20px; padding: 30px;
  box-shadow: var(--shadow); border: 1px solid var(--border-light);
}
.grafik-slide { display: none; animation: slideInChart .5s ease; }
.grafik-slide.active { display: block; }
@keyframes slideInChart { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }

.sp-wrap { height: 4px; background: rgba(30,110,181,.15); border-radius: 99px; overflow: hidden; margin-bottom: 20px; }
.sp-bar { height: 100%; background: var(--primary); border-radius: 99px; width: 0%; transition: width linear; }
.sdot { width: 10px; height: 10px; border-radius: 50%; background: var(--border-light); cursor: pointer; transition: var(--transition); border: none; }
.sdot.active { background: var(--primary); width: 25px; border-radius: 99px; }

/* ===== TRANSAKSI ===== */
.trx-row {
  display: flex; align-items: center; gap: 15px; padding: 16px 20px;
  background: var(--bg-card); border-radius: 16px; box-shadow: var(--shadow-sm);
  border: 1px solid var(--border-light); transition: var(--transition);
}
.trx-row:hover { box-shadow: var(--shadow); transform: translateY(-2px); border-color: #cbd5e1; }
.trx-row .ti {
  width: 45px; height: 45px; border-radius: 12px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
}
.trx-row .tn { font-size: 1rem; font-weight: bold; color: var(--text-primary); }
.trx-row .tm { font-size: 0.85rem; color: var(--text-muted); margin-top: 4px; }
.trx-row .ta { font-size: 1.1rem; font-weight: bold; flex-shrink: 0; text-align: right; }

.btn-outline {
  display: inline-flex; align-items: center; gap: 8px;
  border: 2px solid var(--primary); color: var(--primary);
  padding: 12px 25px; border-radius: 12px; font-weight: bold; font-size: 1rem;
  transition: var(--transition); background: transparent;
}
.btn-outline:hover { background: var(--primary); color: #fff; box-shadow: var(--shadow); }

/* ===== FITUR ===== */
.fitur-card {
  background: var(--bg-card); border-radius: 20px; padding: 30px 25px;
  box-shadow: var(--shadow); border: 1px solid var(--border-light);
  transition: var(--transition); text-align: center;
}
.fitur-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); border-color: #bfdbfe; }
.fitur-card .fi {
  width: 60px; height: 60px; border-radius: 16px; margin: 0 auto 18px;
  display: flex; align-items: center; justify-content: center; font-size: 1.5rem;
}

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes fadeInDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
.animate-fadeIn { opacity: 0; }

@media(max-width: 850px){
  .hero-stats-grid { grid-template-columns: 1fr; max-width: 400px; }
  .g2 { grid-template-columns: 1fr !important; }
  .g3 { grid-template-columns: 1fr 1fr !important; }
}
@media(max-width: 480px){
  .g3 { grid-template-columns: 1fr !important; }
  .trx-row { flex-direction: column; align-items: flex-start; text-align: left; }
  .trx-row .ta { width: 100%; text-align: left; margin-top: 5px; }
  .navbar-brand .subtitle { display: none; }
  .btn-login-nav span, .btn-admin-nav span { display: none; }
}
</style>
</head>
<body>

<nav class="navbar-custom">
  <div class="navbar-content"> 
    <a href="<?= APP_URL ?>/index.php" class="navbar-brand">
      <img src="<?= APP_URL ?>/assets/img/masjid.jpg" alt="Logo" class="img-logo">
      <div class="brand-text">
        <span class="title">Sistem Informasi</span>
        <span class="subtitle">Kas Masjid Baeturrohman</span>
      </div>
    </a>
    
    <?php if (isLoggedIn()): ?>
        <a href="<?= APP_URL ?>/admin/dashboard.php" class="btn-admin-nav">
          <i class="fa-solid fa-arrow-left-long"></i> <span>Kembali</span>
        </a>
    <?php else: ?>
        <a href="<?= APP_URL ?>/login.php" class="btn-login-nav">
          <i class="fas fa-sign-in-alt"></i> <span>Login Admin</span>
        </a>
    <?php endif; ?>
  </div>
</nav>

<!-- HERO SECTION -->
<section class="hero-section">
  <div class="container hero-content">
    
    <div class="hero-badge-pub">
        <img src="<?= APP_URL ?>/assets/img/masjid.jpg" alt="Logo" class="badge-logo"> Masjid Baeturrohman
    </div>
    <h1 class="hero-title">
        Transparansi Keuangan<br><span>Kas Masjid</span> untuk Jamaah
    </h1>
    <p class="hero-desc">
        Pantau pemasukan, pengeluaran, dan saldo kas masjid secara real time. Informasi terbuka dan dapat diakses oleh seluruh jamaah kapan saja.
    </p>
    
    <div class="hero-stats-grid">
      <div class="glass-stat-card">
        <div class="hv" id="heroMasuk">Rp 0</div>
        <div class="hl"><i class="fas fa-arrow-up" style="color:#93c5fd;"></i> Total Pemasukan</div>
      </div>
      <div class="glass-stat-card">
        <div class="hv" id="heroKeluar">Rp 0</div>
        <div class="hl"><i class="fas fa-arrow-down" style="color:#fca5a5;"></i> Total Pengeluaran</div>
      </div>
      <div class="glass-stat-card">
        <div class="hv"><?= $total_trx ?></div>
        <div class="hl"><i class="fas fa-exchange-alt" style="color:#93c5fd;"></i> Total Transaksi</div>
      </div>
    </div>

  </div>
</section>

<!-- RINGKASAN SECTION -->
<section class="pub-sec" style="background:#fff;">
  <div class="container">
    <div style="text-align:center;margin-bottom:40px">
      <div class="sec-tag"><i class="fas fa-wallet"></i> Ringkasan Keuangan</div>
      <h2 class="sec-h">Kondisi Kas <span>Saat Ini</span></h2>
    </div>

    <div style="display:grid;grid-template-columns:1.2fr 1fr;gap:25px;align-items:stretch" class="g2">
      <!-- Saldo Card Utama (Teks "Saldo Kas Masjid" disingkat menjadi "Saldo") -->
      <div class="saldo-card-pub animate-fadeIn">
        <div style="position:relative;z-index:1">
          <div style="display:flex;align-items:center;gap:15px;margin-bottom:20px">
            <div style="width:55px;height:55px;background:rgba(255,255,255,.2);border-radius:15px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0"><i class="fas fa-wallet"></i></div>
            <div>
              <div style="font-size:0.9rem;opacity:.8;margin-bottom:4px">Saldo</div>
              <div style="font-size:clamp(1.6rem,4vw,2.2rem);font-weight:bold"><?= formatRupiah($saldo) ?></div>
            </div>
          </div>
          <div style="background:rgba(255,255,255,.15);border-radius:14px;padding:15px 18px;border:1px solid rgba(255,255,255,.2);display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:20px">
            <div>
              <div style="font-size:0.85rem;opacity:.9"><i class="fas fa-arrow-up" style="color:#93c5fd;margin-right:5px"></i>Masuk Bulan Ini</div>
              <div style="font-size:1.1rem;font-weight:bold;margin-top:5px"><?= formatRupiah($masuk_bln) ?></div>
            </div>
            <div>
              <div style="font-size:0.85rem;opacity:.9"><i class="fas fa-arrow-down" style="color:#fca5a5;margin-right:5px"></i>Keluar Bulan Ini</div>
              <div style="font-size:1.1rem;font-weight:bold;margin-top:5px"><?= formatRupiah($keluar_bln) ?></div>
            </div>
          </div>
          <div style="font-size:0.85rem;opacity:.8;"><i class="fas fa-sync-alt" style="margin-right:6px"></i>Diperbarui: <?= tgl_indo_pub(date('Y-m-d'), true) ?></div>
        </div>
      </div>

      <!-- Kotak Gabungan Statistik Kanan (Diselaraskan persis dengan Kotak Saldo Kiri) -->
      <div class="mstat-card-combined animate-fadeIn">
        <?php
        $stats = [
          ['fas fa-arrow-up', formatRupiah($masuk_bln), 'Pemasukan ' . bln_indo_pub(date('m')) . ' ' . date('Y'), false],
          ['fas fa-arrow-down', formatRupiah($keluar_bln), 'Pengeluaran ' . bln_indo_pub(date('m')) . ' ' . date('Y'), true],
          ['fas fa-exchange-alt', $trx_bln.' transaksi', 'Aktivitas ' . bln_indo_pub(date('m')) . ' ' . date('Y'), false],
        ];
        foreach ($stats as [$ico, $val, $lbl, $isKeluar]): ?>
        <div class="mstat-item-row <?= $isKeluar ? 'is-keluar' : '' ?>">
          <div class="mi"><i class="<?= $ico ?>"></i></div>
          <div style="flex:1;min-width:0">
            <div class="mv"><?= $val ?></div>
            <div class="ml"><?= $lbl ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- GRAFIK SLIDESHOW SECTION -->
<section class="pub-sec" id="grafik-section" style="background:var(--bg-main)">
  <div class="container">
    <div style="text-align:center;margin-bottom:35px">
      <div class="sec-tag"><i class="fas fa-chart-bar"></i> Visualisasi Data</div>
      <h2 class="sec-h">Grafik <span>Keuangan Masjid</span></h2>
    </div>

    <div class="chart-card animate-fadeIn">
      <div class="sp-wrap"><div class="sp-bar" id="spBar"></div></div>
      
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:15px;margin-bottom:25px">
        <div>
          <div style="display:inline-flex;align-items:center;gap:8px;background:var(--primary);color:#fff;padding:6px 16px;border-radius:99px;font-size:0.9rem;font-weight:bold"><i id="slideIcon" class="fas fa-chart-bar"></i> <span id="slideTitle">Pemasukan vs Pengeluaran</span></div>
        </div>
        <div style="display:flex;align-items:center;gap:10px">
          <span style="font-size:0.85rem;color:var(--text-secondary);background:var(--bg-main);border:1px solid var(--border-light);padding:5px 14px;border-radius:99px;font-weight:bold" id="slideCtr">1 / 3</span>
        </div>
      </div>

      <!-- Slide 1: Bar Chart 6 Bulan -->
      <div class="grafik-slide active" id="slide-0"><div style="position:relative;height:320px"><canvas id="barChart"></canvas></div></div>
      
      <!-- Slide 2: Line Chart 12 Bulan (Pemasukan & Pengeluaran) -->
      <div class="grafik-slide" id="slide-1"><div style="position:relative;height:320px"><canvas id="lineChart"></canvas></div></div>
      
      <!-- Slide 3: Doughnut Chart Gabungan -->
      <div class="grafik-slide" id="slide-2">
        <?php if($total_masuk > 0 || $total_keluar > 0):?>
        <div style="display:flex;align-items:center;justify-content:center;gap:30px;flex-wrap:wrap;padding:10px 0">
            <div style="position:relative;width:240px;height:240px;flex-shrink:0"><canvas id="pieCombined"></canvas></div>
            <div id="legCombined" style="display:flex;flex-direction:column;gap:12px;max-width:320px"></div>
        </div>
        <?php else:?><div style="text-align:center;padding:40px;color:var(--text-muted);"><i class="fas fa-chart-pie" style="font-size:3rem;margin-bottom:15px;opacity:0.5"></i><h3>Belum ada data</h3></div><?php endif;?>
      </div>

      <div style="display:flex;justify-content:center;gap:10px;margin-top:30px">
        <button class="sdot active" onclick="goToSlide(0)"></button>
        <button class="sdot" onclick="goToSlide(1)"></button>
        <button class="sdot" onclick="goToSlide(2)"></button>
      </div>
    </div>
  </div>
</section>

<!-- TRANSAKSI TERBARU SECTION -->
<section class="pub-sec" style="background:#fff;">
  <div class="container">
    <div style="text-align:center;margin-bottom:35px">
      <div class="sec-tag"><i class="fas fa-history"></i> Riwayat</div>
      <h2 class="sec-h">Transaksi <span>Terbaru</span></h2>
      <p class="sec-sub">Catatan kas masjid terkini yang dapat diakses oleh seluruh jamaah</p>
    </div>

    <div style="display:flex;flex-direction:column;gap:15px" class="animate-fadeIn">
      <?php if($trx_terbaru->num_rows): while($r=$trx_terbaru->fetch_assoc()):
        $isMasuk = $r['jenis']==='masuk'; ?>
      <div class="trx-row">
        <div class="ti" style="background:<?= $isMasuk?'rgba(30, 110, 181, 0.1)':'rgba(239, 68, 68, 0.1)' ?>;color:<?= $isMasuk?'var(--primary)':'var(--danger)' ?>">
          <i class="fas fa-arrow-<?= $isMasuk?'up':'down' ?>"></i>
        </div>
        <div style="flex:1;min-width:0">
          <div class="tn"><?= htmlspecialchars($r['keterangan']) ?></div>
          <div class="tm">
            <i class="fas fa-calendar-alt" style="margin-right:5px"></i><?= tgl_indo_pub($r['tanggal']) ?>
            &bull; <span style="background:rgba(30,110,181,.1);color:var(--primary);padding:3px 10px;border-radius:6px;font-size:0.75rem;font-weight:bold;margin-left:5px"><?= htmlspecialchars($r['nama_kategori']) ?></span>
          </div>
        </div>
        <div class="ta" style="color:<?= $isMasuk?'var(--primary)':'var(--danger)' ?>">
          <?= $isMasuk?'+':'-' ?> Rp <?= number_format($r['jumlah'],0,',','.') ?>
        </div>
      </div>
      <?php endwhile; else: ?>
      <div style="text-align:center;padding:40px;color:var(--text-muted);background:var(--bg-main);border-radius:16px"><i class="fas fa-inbox" style="font-size:2.5rem;margin-bottom:15px;opacity:0.5"></i><h3>Belum ada transaksi</h3></div>
      <?php endif; ?>
    </div>

    <div style="text-align:center;margin-top:35px">
      <a href="laporan-publik.php" class="btn-outline"><i class="fas fa-list"></i> Lihat Semua Transaksi</a>
    </div>
  </div>
</section>

<!-- FITUR TRANSPARANSI SECTION -->
<section class="pub-sec">
  <div class="container">
    <div style="text-align:center;margin-bottom:40px">
      <div class="sec-tag"><i class="fas fa-shield-alt"></i> Komitmen</div>
      <h2 class="sec-h">Transparansi <span>Keuangan</span></h2>
      <p class="sec-sub">Pengelolaan keuangan masjid yang terbuka dan dapat dipertanggungjawabkan kepada jamaah</p>
    </div>

    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:25px" class="g3">
      <?php $fiturs=[
        ['fas fa-eye','rgba(30,110,181,.1)','var(--primary)','Keterbukaan','Seluruh transaksi dapat diakses jamaah secara langsung tanpa login.'],
        ['fas fa-chart-pie','rgba(201,168,76,.1)','var(--secondary)','Visualisasi Data','Grafik interaktif yang rapi dan mudah dipahami semua kalangan.'],
        ['fas fa-file-alt','rgba(16,185,129,.1)','var(--success)','Laporan Berkala','Tersedia laporan keuangan harian, bulanan, hingga tahunan.'],
        ['fas fa-check-circle','rgba(30,110,181,.1)','var(--primary)','Terverifikasi','Setiap transaksi dicatat dan diverifikasi oleh pengurus resmi.'],
        ['fas fa-clock','rgba(201,168,76,.1)','var(--secondary)','Real-time','Informasi pemasukan dan pengeluaran diperbarui saat itu juga.'],
        ['fas fa-mobile-alt','rgba(14,165,233,.1)','var(--info)','Akses Responsif','Tampilan optimal dan ringan diakses dari perangkat HP atau Laptop.'],
      ]; foreach($fiturs as $i=>[$ico,$bg,$clr,$ttl,$dsc]): ?>
      <div class="fitur-card animate-fadeIn">
        <div class="fi" style="background:<?= $bg ?>;color:<?= $clr ?>"><i class="<?= $ico ?>"></i></div>
        <h3 style="font-size:1.1rem;font-weight:bold;margin-bottom:8px;color:var(--text-primary)"><?= $ttl ?></h3>
        <p style="font-size:0.95rem;color:var(--text-secondary);line-height:1.6"><?= $dsc ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php include 'includes/partials/footer-publik.php'; ?>

<script>
// CHART JS KONFIGURASI BIRU & MERAH KONSISTEN
Chart.defaults.font.family = "'Cambria', 'Times New Roman', Times, serif";
Chart.defaults.font.size = 13;
Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15,45,74,.95)';
Chart.defaults.plugins.tooltip.titleColor = '#fff';
Chart.defaults.plugins.tooltip.bodyColor = 'rgba(255,255,255,.9)';
Chart.defaults.plugins.tooltip.padding = 14;
Chart.defaults.plugins.tooltip.cornerRadius = 8;
const fmtRp = v => 'Rp ' + new Intl.NumberFormat('id-ID').format(v);

// 1. Bar Chart (Pemasukan: Biru #1e6eb5, Pengeluaran: Merah #ef4444)
new Chart(document.getElementById('barChart').getContext('2d'),{
  type:'bar',
  data:{labels:<?=json_encode($chart_labels)?>,datasets:[
    {label:'Pemasukan',data:<?=json_encode($chart_masuk)?>,backgroundColor:'#1e6eb5',borderRadius:6},
    {label:'Pengeluaran',data:<?=json_encode($chart_keluar)?>,backgroundColor:'#ef4444',borderRadius:6}
  ]},
  options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},
    plugins:{legend:{position:'bottom',labels:{padding:20,usePointStyle:true}},tooltip:{callbacks:{label:c=>` ${c.dataset.label}: ${fmtRp(c.raw)}`}}},
    scales:{x:{grid:{display:false}},y:{grid:{color:'rgba(0,0,0,.05)'},ticks:{callback:v=>fmtRp(v)}}},
    animation:{duration:1000,easing:'easeOutQuart'}}
});

// 2. Line Chart 12 Bulan (Pemasukan: Biru, Pengeluaran: Merah)
new Chart(document.getElementById('lineChart').getContext('2d'),{
  type:'line',
  data:{
    labels:<?=json_encode($line_labels)?>,
    datasets:[
      {
        label:'Pemasukan',
        data:<?=json_encode($line_masuk)?>,
        borderColor:'#1e6eb5',
        backgroundColor:'rgba(30,110,181,.08)',
        borderWidth:2.5,
        fill:true,
        tension:.3,
        pointBackgroundColor:'#1e6eb5',
        pointBorderColor:'#fff',
        pointBorderWidth:2,
        pointRadius:4
      },
      {
        label:'Pengeluaran',
        data:<?=json_encode($line_keluar)?>,
        borderColor:'#ef4444',
        backgroundColor:'rgba(239,68,68,.08)',
        borderWidth:2.5,
        fill:true,
        tension:.3,
        pointBackgroundColor:'#ef4444',
        pointBorderColor:'#fff',
        pointBorderWidth:2,
        pointRadius:4
      }
    ]
  },
  options:{
    responsive:true,
    maintainAspectRatio:false,
    plugins:{
      legend:{display:true, position:'bottom', labels:{padding:20, usePointStyle:true}},
      tooltip:{callbacks:{label:c=>` ${c.dataset.label}: ${fmtRp(c.raw)}`}}
    },
    scales:{x:{grid:{display:false}},y:{grid:{color:'rgba(0,0,0,.05)'},ticks:{callback:v=>fmtRp(v)}}},
    animation:{duration:1000}
  }
});

// 3. Doughnut Chart Gabungan (Pemasukan vs Pengeluaran)
const pieLabels = <?= json_encode($pie_combined_labels) ?>;
const pieData   = <?= json_encode($pie_combined_data) ?>;
const pieColors = ['#1e6eb5', '#ef4444'];

if (document.getElementById('pieCombined')) {
  new Chart(document.getElementById('pieCombined').getContext('2d'), {
    type:'doughnut',
    data:{
      labels: pieLabels,
      datasets:[{
        data: pieData,
        backgroundColor: pieColors,
        borderWidth: 3,
        borderColor: '#fff',
        hoverOffset: 8
      }]
    },
    options:{
      responsive: true,
      maintainAspectRatio: true,
      cutout: '65%',
      plugins:{ legend:{ display: false } },
      animation:{ duration: 1000, animateRotate: true, animateScale: true }
    }
  });

  const leg = document.getElementById('legCombined');
  const total = pieData.reduce((a,b)=>a+b,0);
  pieLabels.forEach((l, i) => {
    const pct = total > 0 ? ((pieData[i] / total) * 100).toFixed(1) : 0;
    leg.innerHTML += `
      <div style="display:flex;align-items:center;gap:10px;">
        <span style="width:14px;height:14px;border-radius:4px;background:${pieColors[i]};flex-shrink:0;"></span>
        <div>
          <div style="font-size:0.95rem;font-weight:bold;color:var(--text-primary);">${l}</div>
          <div style="font-size:0.85rem;color:var(--text-muted);">${fmtRp(pieData[i])} &bull; ${pct}%</div>
        </div>
      </div>`;
  });
}

// Animasi Counter Angka
function animCtr(el,target){
  let s=0;const step=target/(1500/16);
  const t=setInterval(()=>{s+=step;if(s>=target){s=target;clearInterval(t);}
    el.textContent='Rp '+new Intl.NumberFormat('id-ID').format(Math.floor(s));},16);
}
window.addEventListener('load',()=>{
  animCtr(document.getElementById('heroMasuk'),<?=$total_masuk?>);
  animCtr(document.getElementById('heroKeluar'),<?=$total_keluar?>);
});

// Logika Slideshow Grafik (3 Slide)
const DUR=10000,slides=document.querySelectorAll('.grafik-slide'),dots=document.querySelectorAll('.sdot'),spBar=document.getElementById('spBar');
const INFO=[
  {icon:'fas fa-chart-bar',title:'Pemasukan vs Pengeluaran'},
  {icon:'fas fa-chart-line',title:'Trend Keuangan 12 Bulan'},
  {icon:'fas fa-chart-pie',title:'Proporsi Keseluruhan Keuangan'},
];
let cur=0,paused=false,timer=null;
function goToSlide(n){
  slides[cur].classList.remove('active');dots[cur].classList.remove('active');
  cur=(n+slides.length)%slides.length;
  slides[cur].classList.add('active');dots[cur].classList.add('active');
  document.getElementById('slideIcon').className=INFO[cur].icon;
  document.getElementById('slideTitle').textContent=INFO[cur].title;
  document.getElementById('slideCtr').textContent=`${cur+1} / ${slides.length}`;
  resetProg();
}
function changeSlide(dir){goToSlide(cur+dir);if(!paused)startAuto();}
function startAuto(){clearTimeout(timer);timer=setTimeout(()=>{if(!paused){goToSlide(cur+1);startAuto();}},DUR);}
function resetProg(){
  spBar.style.transition='none';spBar.style.width='0%';
  if(!paused)setTimeout(()=>{spBar.style.transition=`width ${DUR}ms linear`;spBar.style.width='100%';},30);
}
function togglePause(){
  paused=!paused;
  if(paused){
    clearTimeout(timer);
    const w=getComputedStyle(spBar).width;
    spBar.style.transition='none';
    spBar.style.width=w;
    spBar.style.opacity='0.4';
  } else {
    spBar.style.opacity='1';
    startAuto();
    resetProg();
  }
}
startAuto();resetProg();
document.getElementById('grafik-section').addEventListener('click', function(e){
  if(!e.target.closest('.sdot,a,button')) togglePause();
});

// Animasi Fade-In saat di-scroll
const obs = new IntersectionObserver(es => es.forEach(e => {
  if(e.isIntersecting) {
    e.target.style.opacity = '1';
    e.target.style.transform = 'translateY(0)';
  }
}), {threshold: 0.1});
document.querySelectorAll('.animate-fadeIn').forEach((el,i) => {
  el.style.opacity = '0';
  el.style.transform = 'translateY(30px)';
  el.style.transition = `all 0.6s cubic-bezier(0.4, 0, 0.2, 1) ${i*0.1}s`;
  obs.observe(el);
});
</script>
</body>
</html>