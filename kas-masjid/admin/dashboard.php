<?php
require_once '../includes/config.php';

// Proteksi halaman admin: Jika belum login, lempar ke halaman login
if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

$admin_id = (int)$_SESSION['admin_id'];
$admin    = $conn->query("SELECT * FROM users WHERE id=$admin_id")->fetch_assoc();

function tgl_indo($tanggal){
    $bulan = array (
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    $pecahkan = explode('-', date('Y-m-d', strtotime($tanggal)));
    return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
}

// Stat cards
$total_masuk  = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='masuk' AND deleted_at IS NULL")->fetch_assoc()['t'];
$total_keluar = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='keluar' AND deleted_at IS NULL")->fetch_assoc()['t'];
$saldo        = $total_masuk - $total_keluar;
$total_trx    = (int)$conn->query("SELECT COUNT(*) as t FROM transaksi WHERE deleted_at IS NULL")->fetch_assoc()['t'];
$bln          = date('Y-m');
$tahun        = (int)date('Y');
$masuk_bln    = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='masuk' AND deleted_at IS NULL AND DATE_FORMAT(tanggal,'%Y-%m')='$bln'")->fetch_assoc()['t'];
$keluar_bln   = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='keluar' AND deleted_at IS NULL AND DATE_FORMAT(tanggal,'%Y-%m')='$bln'")->fetch_assoc()['t'];
$trx_bln      = (int)$conn->query("SELECT COUNT(*) as t FROM transaksi WHERE deleted_at IS NULL AND DATE_FORMAT(tanggal,'%Y-%m')='$bln'")->fetch_assoc()['t'];

// Bar chart 6 bulan
$chart_labels = $chart_masuk = $chart_keluar = [];
$nama_bulan_singkat = [1=>'Jan', 2=>'Feb', 3=>'Mar', 4=>'Apr', 5=>'Mei', 6=>'Jun', 7=>'Jul', 8=>'Agt', 9=>'Sep', 10=>'Okt', 11=>'Nov', 12=>'Des'];

for ($i = 5; $i >= 0; $i--) {
    $b = date('Y-m', strtotime("-$i month"));
    $bulan_angka = (int)date('m', strtotime("-$i month"));
    $chart_labels[] = $nama_bulan_singkat[$bulan_angka];
    
    $chart_masuk[]  = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='masuk' AND deleted_at IS NULL AND DATE_FORMAT(tanggal,'%Y-%m')='$b'")->fetch_assoc()['t'];
    $chart_keluar[] = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='keluar' AND deleted_at IS NULL AND DATE_FORMAT(tanggal,'%Y-%m')='$b'")->fetch_assoc()['t'];
}

// Line chart 12 bulan
$line_labels = $line_masuk = $line_keluar = [];
for ($m = 1; $m <= 12; $m++) {
    $b  = sprintf('%04d-%02d', $tahun, $m);
    $mk = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='masuk' AND deleted_at IS NULL AND DATE_FORMAT(tanggal,'%Y-%m')='$b'")->fetch_assoc()['t'];
    $kl = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='keluar' AND deleted_at IS NULL AND DATE_FORMAT(tanggal,'%Y-%m')='$b'")->fetch_assoc()['t'];
    
    $line_labels[] = $nama_bulan_singkat[$m];
    $line_masuk[]  = $mk;
    $line_keluar[] = $kl;
}

// Data Donat Gabungan
$pie_combined_labels = ['Total Pemasukan', 'Total Pengeluaran'];
$pie_combined_data   = [$total_masuk, $total_keluar];

// Transaksi terbaru
$trx_recent = $conn->query("SELECT t.*, k.nama_kategori FROM transaksi t JOIN kategori k ON t.kategori_id=k.id WHERE t.deleted_at IS NULL ORDER BY t.tanggal DESC, t.id DESC LIMIT 6");

$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
<title>Dashboard – <?= APP_NAME ?></title>
<link rel="stylesheet" href="../assets/css/style.css?v=<?= time() ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
:root {
  --primary: #1e6eb5;
  --primary-light: #3b82f6;
  --success: #10b981;
  --danger: #ef4444;
  --bg-body: #f8fafc;
  --bg-card: #ffffff;
  --text-main: #0f172a;
  --text-muted: #64748b;
  --border-color: #e2e8f0;
  --shadow-sm: 0 1px 2px rgba(0,0,0,0.04);
  --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.04);
  --radius-lg: 14px;
  --radius-md: 10px;
}

* { box-sizing: border-box; }
body { font-family: 'Inter', sans-serif; background-color: var(--bg-body); color: var(--text-main); margin: 0; }
a { text-decoration: none; }
button, input, select, textarea { font-family: inherit; }

/* LAYOUT ADMIN */
.admin-wrapper { display: flex; min-height: 100vh; overflow-x: hidden; }
.admin-main { flex: 1; display: flex; flex-direction: column; width: calc(100% - 260px); }
.admin-content { padding: 30px; flex: 1; max-width: 1300px; width: 100%; margin: 0 auto; }

/* TOPBAR */
.topbar {
  background: var(--bg-card); height: 65px; display: flex; align-items: center; justify-content: space-between;
  padding: 0 30px; border-bottom: 1px solid var(--border-color); z-index: 10;
}
.breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 500; color: var(--text-muted); }
.breadcrumb .active { color: var(--primary); font-weight: 600; }
.topbar-right { display: flex; align-items: center; gap: 15px; }
.topbar-date { font-size: 0.85rem; font-weight: 500; color: var(--text-muted); background: var(--bg-body); padding: 6px 12px; border-radius: 6px; border: 1px solid var(--border-color); }

/* DROPDOWN PROFIL MODERN */
.user-dropdown-wrapper { position: relative; }
.topbar-user { 
    cursor: pointer; padding: 5px 10px; border-radius: 8px; transition: background 0.2s; 
    display: flex; align-items: center; gap: 10px; 
}
.topbar-user:hover { background: rgba(0,0,0,0.04); }
.t-avatar { width: 32px; height: 32px; background: var(--primary); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; font-weight: bold; overflow: hidden; }
.t-avatar img { width: 100%; height: 100%; object-fit: cover; }
.t-name { font-weight: 600; font-size: 0.9rem; color: var(--text-main); }
.topbar-user i.fa-chevron-down { font-size: 0.7rem; color: var(--text-muted); transition: transform 0.3s; }
.user-dropdown-wrapper.active .topbar-user i.fa-chevron-down { transform: rotate(180deg); }

.user-dropdown-menu {
  position: absolute; top: calc(100% + 15px); right: 0; background: var(--bg-card);
  border: 1px solid var(--border-color); border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);
  width: 250px; opacity: 0; visibility: hidden; transform: translateY(-10px);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); z-index: 1000;
}
.user-dropdown-wrapper.active .user-dropdown-menu { opacity: 1; visibility: visible; transform: translateY(0); }

.dropdown-header { padding: 18px 20px; display: flex; align-items: center; gap: 15px; border-bottom: 1px solid var(--border-color); background: #f8fafc; border-radius: 16px 16px 0 0; }
.d-avatar { width: 45px; height: 45px; background: var(--primary); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: bold; overflow: hidden; }
.d-avatar img { width: 100%; height: 100%; object-fit: cover; }
.d-info { flex: 1; overflow: hidden; }
.d-name { font-weight: 700; color: var(--text-main); font-size: 1rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.d-role { font-size: 0.8rem; color: var(--text-muted); margin-top: 2px; font-weight: 500;}

.dropdown-body { padding: 10px 0; }
.dropdown-item { display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: var(--text-main); font-size: 0.9rem; font-weight: 600; transition: all 0.2s ease; text-decoration: none; }
.dropdown-item i { font-size: 1.1rem; color: var(--text-muted); width: 20px; text-align: center; transition: 0.2s; }
.dropdown-item:hover { background: #f1f5f9; color: var(--primary); padding-left: 25px; }
.dropdown-item:hover i { color: var(--primary); }

.dropdown-item.text-danger { color: var(--danger); }
.dropdown-item.text-danger i { color: var(--danger); }
.dropdown-item.text-danger:hover { background: #fef2f2; color: var(--danger); padding-left: 25px; }

/* 4 KOTAK STATISTIK */
.grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
.stat-card {
  background: var(--bg-card); padding: 18px 20px; border-radius: var(--radius-lg); 
  border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); 
  transition: all 0.2s ease; display: flex; align-items: center; gap: 16px;
}
.stat-card:hover { border-color: #cbd5e1; box-shadow: var(--shadow-md); transform: translateY(-2px); }
.stat-icon-wrap { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
.stat-info { flex: 1; min-width: 0; }
.stat-label { font-size: 0.75rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
.stat-value { font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin: 2px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.stat-sub { font-size: 0.72rem; color: var(--text-muted); font-weight: 500; }

.c-saldo .stat-icon-wrap { background: #fef3c7; color: #d97706; }
.c-masuk .stat-icon-wrap { background: #dbeafe; color: #1e6eb5; }
.c-keluar .stat-icon-wrap { background: #fee2e2; color: #ef4444; }
.c-trx .stat-icon-wrap { background: #f3e8ff; color: #7c3aed; }

/* BANNER RINGKASAN BULAN INI */
.banner-card {
  background: linear-gradient(135deg, #1e6eb5 0%, #3b82f6 50%, #60a5fa 100%);
  border-radius: var(--radius-lg); padding: 22px 28px; color: #fff;
  display: flex; align-items: center; justify-content: space-between;
  box-shadow: 0 10px 25px rgba(30, 110, 181, 0.25); margin-bottom: 24px;
}
.banner-left h4 { font-size: 0.75rem; font-weight: 600; opacity: 0.9; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 3px; }
.banner-left h2 { font-size: 1.25rem; font-weight: 800; }
.banner-stats { display: flex; gap: 36px; align-items: center; }
.b-stat-item { text-align: center; }
.b-stat-item .v { font-size: 1.2rem; font-weight: 800; }
.b-stat-item .l { font-size: 0.72rem; opacity: 0.85; margin-top: 2px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
.banner-sep { width: 1px; height: 32px; background: rgba(255,255,255,0.3); }

/* SLIDESHOW GRAFIK */
.grafik-slide { display: none; animation: fadeIn 0.3s ease; }
.grafik-slide.active { display: flex; flex-direction: column; align-items: center; justify-content: center; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

.slide-progress-wrap { height: 3px; background: var(--border-color); border-radius: 99px; overflow: hidden; margin-bottom: 12px; }
.slide-progress-bar { height: 100%; background: var(--primary); width: 0%; transition: width linear; }

.slide-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 10px; }
.slide-title-box { display: inline-flex; align-items: center; gap: 6px; background: #f1f5f9; color: var(--text-main); padding: 5px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
.slide-counter { font-size: 0.7rem; font-weight: 600; color: var(--text-muted); background: var(--bg-body); padding: 3px 8px; border-radius: 6px; }

.slide-dots { display: flex; justify-content: center; gap: 6px; margin-top: 12px; }
.sdot { width: 6px; height: 6px; border-radius: 50%; background: #cbd5e1; border: none; cursor: pointer; transition: 0.3s; }
.sdot.active { background: var(--primary); width: 18px; border-radius: 99px; }

/* CARDS (GRAFIK & TABEL) */
.card-modern {
  background: var(--bg-card); border-radius: var(--radius-lg); border: 1px solid var(--border-color);
  box-shadow: var(--shadow-sm); padding: 20px 24px; margin-bottom: 24px;
}
.card-header-modern { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color); }
.card-header-modern h3 { font-size: 1rem; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 8px; }
.card-header-modern h3 i { color: var(--primary); }

/* TABEL MINIMALIS */
.table-responsive { width: 100%; overflow-x: auto; }
.table-custom { width: 100%; border-collapse: collapse; }
.table-custom th { 
  background: #f8fafc; color: var(--text-muted); font-weight: 600; font-size: 0.72rem; 
  text-transform: uppercase; letter-spacing: 0.5px; padding: 10px 14px; text-align: left; 
  border-bottom: 1px solid var(--border-color); white-space: nowrap;
}
.table-custom td { padding: 12px 14px; border-bottom: 1px solid var(--border-color); font-size: 0.85rem; color: var(--text-main); vertical-align: middle; }
.table-custom tr:last-child td { border-bottom: none; }
.table-custom tr:hover td { background-color: #f8fafc; }

.badge-custom { padding: 4px 10px; border-radius: 6px; font-size: 0.72rem; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
.badge-blue { background: #eff6ff; color: #1e6eb5; }
.badge-green { background: #ebf5ff; color: #1e6eb5; }
.badge-red { background: #fef2f2; color: #ef4444; }

/* Alert */
.alert { padding: 12px 16px; border-radius: var(--radius-md); margin-bottom: 20px; font-size: 0.85rem; font-weight: 500; display: flex; align-items: center; gap: 10px; }
.alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
.alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

/* RESPONSIF HP YANG DISEMPURNAKAN */
@media (max-width: 768px) {
  html, body { overflow-x: hidden !important; max-width: 100vw !important; }
  .admin-wrapper { display: block !important; width: 100% !important; }
  .grid-4 { grid-template-columns: repeat(2, 1fr) !important; gap: 10px !important; margin-bottom: 16px !important; }
  .stat-card { padding: 12px 10px !important; gap: 10px !important; }
  .stat-icon-wrap { width: 34px !important; height: 34px !important; font-size: 0.9rem !important; border-radius: 8px !important; }
  .stat-label { font-size: 0.65rem !important; }
  .stat-value { font-size: 0.95rem !important; }
  .stat-sub { font-size: 0.6rem !important; }

  .dashboard-top-row {
    display: flex !important;
    flex-direction: column !important;
    gap: 16px !important;
    margin-bottom: 16px !important;
  }

  /* Perbaikan total banner agar rapi dan tidak bertumpuk di HP */
  .banner-card {
    flex-direction: column !important;
    align-items: flex-start !important;
    gap: 14px !important;
    padding: 16px 18px !important;
    border-radius: var(--radius-lg) !important;
  }
  .banner-left h4 { font-size: 0.68rem !important; margin-bottom: 2px !important; }
  .banner-left h2 { font-size: 1rem !important; }
  
  .banner-stats {
    width: 100% !important;
    display: flex !important;
    flex-direction: row !important;
    justify-content: space-between !important;
    align-items: center !important;
    gap: 4px !important;
    background: rgba(255, 255, 255, 0.12);
    padding: 10px 12px;
    border-radius: 10px;
  }
  .banner-sep { display: block !important; width: 1px; height: 24px; background: rgba(255,255,255,0.3); }
  .b-stat-item { text-align: center !important; flex: 1; }
  .b-stat-item .v { font-size: 0.82rem !important; font-weight: 800 !important; white-space: nowrap; }
  .b-stat-item .l { font-size: 0.58rem !important; }

  .card-modern { padding: 16px !important; }
  .card-modern .card-header-modern { margin-bottom: 10px !important; padding-bottom: 8px !important; }
  .card-modern .card-header-modern h3 { font-size: 0.85rem !important; }
  
  .admin-main { width: 100% !important; margin-left: 0 !important; }
  .admin-content { padding: 15px !important; }
  .topbar { padding: 0 15px !important; }
  .t-name, .topbar-date, .fa-chevron-down { display: none !important; }
}
</style>
</head>
<body>

<div class="admin-wrapper">
  <!-- SIDEBAR -->
  <?php include '../includes/partials/sidebar-admin.php'; ?>

  <div class="admin-main">
    <!-- TOPBAR -->
    <div class="topbar">
      <div style="display:flex; align-items:center; gap:12px;">
        <div id="sidebarToggle" style="cursor:pointer; font-size:1.1rem; color:var(--text-muted);"><i class="fas fa-bars"></i></div>
        <div class="breadcrumb">
          <i class="fas fa-home"></i> <i class="fas fa-chevron-right" style="font-size:0.6rem; opacity:0.5;"></i> <span class="active">Dashboard</span>
        </div>
      </div>
      <div class="topbar-right">
        <div class="topbar-date"><i class="fas fa-calendar-alt me-1" style="margin-right: 4px;"></i> <?= tgl_indo(date('Y-m-d')) ?></div>
        
        <!-- DROPDOWN PROFIL USER -->
        <div class="user-dropdown-wrapper" id="userDropdownWrap">
          <div class="topbar-user" id="userDropdownTrigger">
            <div class="t-avatar">
              <?php if (!empty($admin['foto_profil']) && file_exists('../assets/uploads/' . $admin['foto_profil'])): ?>
                  <img src="<?= APP_URL ?>/assets/uploads/<?= htmlspecialchars($admin['foto_profil']) ?>" alt="Avatar">
              <?php else: ?>
                  <?= strtoupper(substr($admin['nama'], 0, 1)) ?>
              <?php endif; ?>
            </div>
            <div class="t-name"><?= htmlspecialchars($admin['nama']) ?></div>
            <i class="fas fa-chevron-down"></i>
          </div>
          
          <div class="user-dropdown-menu">
            <div class="dropdown-header">
              <div class="d-avatar">
                <?php if (!empty($admin['foto_profil']) && file_exists('../assets/uploads/' . $admin['foto_profil'])): ?>
                    <img src="<?= APP_URL ?>/assets/uploads/<?= htmlspecialchars($admin['foto_profil']) ?>" alt="Avatar">
                <?php else: ?>
                    <?= strtoupper(substr($admin['nama'], 0, 1)) ?>
                <?php endif; ?>
              </div>
              <div class="d-info">
                <div class="d-name"><?= htmlspecialchars($admin['nama']) ?></div>
                <div class="d-role">@admin</div>
              </div>
            </div>
            <div class="dropdown-body">
              <a href="profil.php" class="dropdown-item">
                <i class="fas fa-user-cog"></i> Pengaturan Akun
              </a>
              <a href="#" onclick="openLogoutModal(); return false;" class="dropdown-item text-danger">
                <i class="fas fa-sign-out-alt"></i> Logout
              </a>
            </div>
          </div>
        </div>
        <!-- END DROPDOWN -->

      </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="admin-content">
      
      <?php if ($alert): ?>
      <div class="alert alert-<?= $alert['type'] ?>">
        <i class="fas fa-<?= $alert['type']=='success'?'check-circle':'exclamation-circle' ?>"></i> <?= htmlspecialchars($alert['message']) ?>
      </div>
      <?php endif; ?>

      <!-- 4 STAT CARDS -->
      <div class="grid-4">
        <div class="stat-card c-saldo">
          <div class="stat-icon-wrap"><i class="fas fa-wallet"></i></div>
          <div class="stat-info">
            <div class="stat-label">Saldo Kas</div>
            <div class="stat-value"><?= formatRupiah($saldo) ?></div>
            <div class="stat-sub">Real-time</div>
          </div>
        </div>
        <div class="stat-card c-masuk">
          <div class="stat-icon-wrap"><i class="fas fa-arrow-down"></i></div>
          <div class="stat-info">
            <div class="stat-label">Pemasukan</div>
            <div class="stat-value"><?= formatRupiah($total_masuk) ?></div>
            <div class="stat-sub">Total Keseluruhan</div>
          </div>
        </div>
        <div class="stat-card c-keluar">
          <div class="stat-icon-wrap"><i class="fas fa-arrow-up"></i></div>
          <div class="stat-info">
            <div class="stat-label">Pengeluaran</div>
            <div class="stat-value"><?= formatRupiah($total_keluar) ?></div>
            <div class="stat-sub">Total Keseluruhan</div>
          </div>
        </div>
        <div class="stat-card c-trx">
          <div class="stat-icon-wrap"><i class="fas fa-exchange-alt"></i></div>
          <div class="stat-info">
            <div class="stat-label">Transaksi</div>
            <div class="stat-value"><?= number_format($total_trx) ?></div>
            <div class="stat-sub">Total Tercatat</div>
          </div>
        </div>
      </div>

      <!-- KELOMPOK ROW ATAS (BANNER & GRAFIK) -->
      <div class="dashboard-top-row">
        <!-- BANNER RINGKASAN BULAN INI -->
        <div class="banner-card">
          <div class="banner-left">
            <h4>Bulan Ini</h4>
            <h2><?= tgl_indo(date('Y-m-d')) ?></h2>
          </div>
          <div class="banner-stats">
            <div class="b-stat-item">
              <div class="v"><?= formatRupiah($masuk_bln) ?></div>
              <div class="l">Pemasukan</div>
            </div>
            <div class="banner-sep"></div>
            <div class="b-stat-item">
              <div class="v"><?= formatRupiah($keluar_bln) ?></div>
              <div class="l">Pengeluaran</div>
            </div>
            <div class="banner-sep"></div>
            <div class="b-stat-item selisih-box">
              <div class="v" style="<?= ($masuk_bln-$keluar_bln)<0 ? 'color:#fecaca;' : '' ?>"><?= formatRupiah($masuk_bln-$keluar_bln) ?></div>
              <div class="l">Selisih</div>
            </div>
          </div>
        </div>

        <!-- GRAFIK SLIDESHOW -->
        <div class="card-modern">
          <div class="card-header-modern">
            <h3><i class="fas fa-chart-area"></i> Visualisasi Keuangan</h3>
          </div>
          
          <div class="slide-progress-wrap"><div class="slide-progress-bar" id="slideProgress"></div></div>

          <div class="slide-nav">
            <div class="slide-title-box">
              <i id="slideIcon" class="fas fa-chart-bar"></i> <span id="slideTitle">Pemasukan vs Pengeluaran (6 Bulan)</span>
            </div>
            <div class="slide-counter" id="slideCounter">1 / 3</div>
          </div>

          <!-- Slide 1 -->
          <div class="grafik-slide active" id="slide-0">
            <div style="position:relative; height:240px; width:100%;"><canvas id="barChart"></canvas></div>
          </div>

          <!-- Slide 2 -->
          <div class="grafik-slide" id="slide-1">
            <div style="position:relative; height:240px; width:100%;"><canvas id="lineChart"></canvas></div>
          </div>

          <!-- Slide 3 -->
          <div class="grafik-slide" id="slide-2">
            <?php if ($total_masuk > 0 || $total_keluar > 0): ?>
              <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:center; gap:20px; width:100%;">
                <div style="position:relative; width:180px; height:180px;"><canvas id="pieCombined"></canvas></div>
                <div id="legCombined" style="display:flex; flex-direction:column; gap:8px;"></div>
              </div>
            <?php else: ?>
              <div style="text-align:center; padding:20px; color:var(--text-muted);"><p>Belum ada data keuangan</p></div>
            <?php endif; ?>
          </div>

          <div class="slide-dots">
            <button class="sdot active" onclick="goToSlide(0)"></button>
            <button class="sdot" onclick="goToSlide(1)"></button>
            <button class="sdot" onclick="goToSlide(2)"></button>
          </div>
        </div>
      </div>

      <!-- TRANSAKSI TERBARU -->
      <div class="card-modern">
        <div class="card-header-modern">
          <h3><i class="fas fa-list-ul"></i> Transaksi Terbaru</h3>
          <a href="laporan.php" style="font-size:0.8rem; font-weight:600; color:var(--primary);">Lihat Semua &rarr;</a>
        </div>
        <div class="table-responsive">
          <table class="table-custom">
            <thead>
              <tr>
                <th style="width: 50px;">No</th>
                <th>Tanggal</th>
                <th>Keterangan</th>
                <th>Kategori</th>
                <th>Jenis</th>
                <th style="text-align: right;">Jumlah</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $no = 1;
              while ($r = $trx_recent->fetch_assoc()): 
              ?>
              <tr>
                <td style="color: var(--text-muted); font-weight: 500;"><?= $no++ ?></td>
                <td style="white-space:nowrap; color:var(--text-muted);"><?= tgl_indo($r['tanggal']) ?></td>
                <td style="max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-weight:500;"><?= htmlspecialchars($r['keterangan']) ?></td>
                <td><span class="badge-custom badge-blue"><?= htmlspecialchars($r['nama_kategori']) ?></span></td>
                <td>
                  <?php if($r['jenis'] == 'masuk'): ?>
                    <span class="badge-custom badge-green">Masuk</span>
                  <?php else: ?>
                    <span class="badge-custom badge-red">Keluar</span>
                  <?php endif; ?>
                </td>
                <td style="text-align: right; font-weight: 600; color: <?= $r['jenis']=='masuk' ? '#1e6eb5' : '#ef4444' ?>;">
                  <?= ($r['jenis']=='masuk'?'+':'-') . formatRupiah($r['jumlah']) ?>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
const userDropdownWrap = document.getElementById('userDropdownWrap');
const userDropdownTrigger = document.getElementById('userDropdownTrigger');

if (userDropdownTrigger && userDropdownWrap) {
    userDropdownTrigger.addEventListener('click', function(e) {
        e.stopPropagation();
        userDropdownWrap.classList.toggle('active');
    });
    
    document.addEventListener('click', function(e) {
        if (!userDropdownWrap.contains(e.target)) {
            userDropdownWrap.classList.remove('active');
        }
    });
}

const sidebarToggle = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('adminSidebar') || document.querySelector('.sidebar');
const overlay = document.getElementById('sidebarOverlay');

if (sidebarToggle) {
    sidebarToggle.addEventListener('click', function(e) {
        e.stopPropagation();
        if (window.innerWidth <= 768) {
            if (sidebar) sidebar.classList.toggle('open');
            if (overlay) overlay.classList.toggle('active');
        } else {
            const wrapper = document.querySelector('.admin-wrapper');
            if (wrapper) wrapper.classList.toggle('toggled');
        }
    });
}

Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.font.size = 11;
Chart.defaults.color = '#64748b';
Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15,23,42,0.9)';
Chart.defaults.plugins.tooltip.padding = 10;
Chart.defaults.plugins.tooltip.cornerRadius = 6;

const getAxisConfig = () => ({
  grid: { color: '#f1f5f9' },
  ticks: { display: false }
});

new Chart(document.getElementById('barChart').getContext('2d'), {
  type:'bar',
  data:{labels:<?= json_encode($chart_labels) ?>,datasets:[
    {label:'Pemasukan', data:<?= json_encode($chart_masuk) ?>, backgroundColor:'#1e6eb5', borderRadius:4},
    {label:'Pengeluaran', data:<?= json_encode($chart_keluar) ?>, backgroundColor:'#ef4444', borderRadius:4}
  ]},
  options:{
    responsive:true,
    maintainAspectRatio:false,
    plugins:{legend:{position:'bottom',labels:{usePointStyle:true, padding:10, font:{weight:'500'}}}},
    scales:{
      x:{
        grid:{display:false},
        ticks:{ maxRotation: 0, autoSkip: false }
      },
      y: getAxisConfig()
    }
  }
});

new Chart(document.getElementById('lineChart').getContext('2d'), {
  type:'line',
  data:{
    labels:<?= json_encode($line_labels) ?>,
    datasets:[
      {
        label:'Pemasukan',
        data:<?= json_encode($line_masuk) ?>,
        borderColor:'#1e6eb5',
        backgroundColor:'rgba(30,110,181,0.05)',
        borderWidth:2.5,
        fill:true,
        tension:0.3,
        pointBackgroundColor:'#1e6eb5',
        pointRadius:3
      },
      {
        label:'Pengeluaran',
        data:<?= json_encode($line_keluar) ?>,
        borderColor:'#ef4444',
        backgroundColor:'rgba(239,68,68,0.05)',
        borderWidth:2.5,
        fill:true,
        tension:0.3,
        pointBackgroundColor:'#ef4444',
        pointRadius:3
      }
    ]
  },
  options:{
    responsive:true,
    maintainAspectRatio:false,
    plugins:{
      legend:{
        display:true,
        position:'bottom',
        labels:{usePointStyle:true, padding:10, font:{weight:'500'}}
      }
    },
    scales:{
      x:{
        grid:{display:false},
        ticks:{ maxRotation: 0, autoSkip: true, maxTicksLimit: window.innerWidth <= 768 ? 6 : 12 }
      },
      y: getAxisConfig()
    }
  }
});

const pieLabels = <?= json_encode($pie_combined_labels) ?>;
const pieData   = <?= json_encode($pie_combined_data) ?>;
const pieColors = ['#1e6eb5', '#ef4444'];
const fmtRpFull = v => 'Rp ' + new Intl.NumberFormat('id-ID').format(v);

if (document.getElementById('pieCombined')) {
  new Chart(document.getElementById('pieCombined').getContext('2d'), {
    type:'doughnut',
    data:{
      labels: pieLabels,
      datasets:[{
        data: pieData,
        backgroundColor: pieColors,
        borderWidth: 0
      }]
    },
    options:{
      responsive: true,
      maintainAspectRatio: false,
      cutout: '68%',
      plugins:{ legend:{ display: false } }
    }
  });

  const leg = document.getElementById('legCombined');
  const total = pieData.reduce((a,b)=>a+b,0);
  pieLabels.forEach((l, i) => {
    const pct = total > 0 ? ((pieData[i] / total) * 100).toFixed(1) : 0;
    leg.innerHTML += `
      <div style="display:flex;align-items:center;gap:8px;font-size:0.75rem;font-weight:600;color:var(--text-main);">
        <span style="width:8px;height:8px;border-radius:50%;background:${pieColors[i]}"></span>
        ${l}
        <span style="margin-left:auto;color:var(--text-muted);font-weight:500;">${fmtRpFull(pieData[i])} (${pct}%)</span>
      </div>`;
  });
}

const DURATION = 10000;
const slides = document.querySelectorAll('.grafik-slide'), dots = document.querySelectorAll('.sdot'), progress = document.getElementById('slideProgress');
const SLIDE_INFO = [
  {title:'Pemasukan vs Pengeluaran (6 Bulan)'},
  {title:'Trend Pemasukan & Pengeluaran (12 Bulan)'},
  {title:'Proporsi Keseluruhan Keuangan'},
];
let cur=0, paused=false, timer=null;

function goToSlide(n) {
  slides[cur].classList.remove('active'); dots[cur].classList.remove('active');
  cur = (n + slides.length) % slides.length;
  slides[cur].classList.add('active'); dots[cur].classList.add('active');
  document.getElementById('slideTitle').textContent = SLIDE_INFO[cur].title;
  document.getElementById('slideCounter').textContent = `${cur+1} / 3`;
  resetProgress();
}
function startAuto() { clearTimeout(timer); timer=setTimeout(()=>{ if(!paused){goToSlide(cur+1);startAuto();} },DURATION); }
function resetProgress() {
  progress.style.transition='none'; progress.style.width='0%';
  if(!paused) setTimeout(()=>{ progress.style.transition=`width ${DURATION}ms linear`; progress.style.width='100%'; },30);
}
function togglePause() {
  paused=!paused;
  if(paused){ clearTimeout(timer); progress.style.transition='none'; progress.style.width=getComputedStyle(progress).width; progress.style.opacity='0.4'; }
  else { progress.style.opacity='1'; startAuto(); resetProgress(); }
}
startAuto(); resetProgress();

document.querySelectorAll('.grafik-slide').forEach(el => {
  el.style.cursor = 'pointer';
  el.addEventListener('click', function(e) { if (!e.target.closest('.sdot,a,button')) togglePause(); });
});
</script>
</body>
</html>