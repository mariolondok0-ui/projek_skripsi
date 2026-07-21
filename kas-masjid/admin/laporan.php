<?php
require_once '../includes/config.php';
requireLogin();

// Filter
$filter_periode = $_GET['periode'] ?? 'bulan';
$filter_jenis   = $_GET['jenis']   ?? 'semua';
$filter_bulan   = $_GET['bulan']   ?? date('Y-m');
$filter_tahun   = (int)($_GET['tahun']  ?? date('Y'));
$tgl_dari       = $_GET['dari']    ?? date('Y-m-01');
$tgl_sampai     = $_GET['sampai']  ?? date('Y-m-d');

// Build WHERE
$where = ['1=1'];
if ($filter_jenis !== 'semua') $where[] = "t.jenis = '" . sanitize($filter_jenis) . "'";
switch ($filter_periode) {
    case 'bulan':  $where[] = "DATE_FORMAT(t.tanggal,'%Y-%m') = '" . sanitize($filter_bulan) . "'"; break;
    case 'tahun':  $where[] = "YEAR(t.tanggal) = $filter_tahun"; break;
    case 'custom': $where[] = "t.tanggal BETWEEN '" . sanitize($tgl_dari) . "' AND '" . sanitize($tgl_sampai) . "'"; break;
    case 'hari':   $where[] = "t.tanggal = CURDATE()"; break;
    case 'minggu': $where[] = "t.tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"; break;
}
$where_sql = implode(' AND ', $where);

// Data
$rows = $conn->query("
    SELECT t.*, k.nama_kategori, u.nama as nama_user
    FROM transaksi t
    JOIN kategori k ON t.kategori_id = k.id
    JOIN users u ON t.user_id = u.id
    WHERE $where_sql
    ORDER BY t.tanggal DESC, t.id DESC
");

// Summary
$sum = $conn->query("
    SELECT
        COALESCE(SUM(CASE WHEN jenis='masuk'  THEN jumlah END),0) AS total_masuk,
        COALESCE(SUM(CASE WHEN jenis='keluar' THEN jumlah END),0) AS total_keluar,
        COUNT(*) AS total_trx
    FROM transaksi t WHERE $where_sql
")->fetch_assoc();

// Periode label untuk header PDF
$periode_label = match($filter_periode) {
    'bulan'  => 'Bulan ' . date('F Y', strtotime($filter_bulan . '-01')),
    'tahun'  => 'Tahun ' . $filter_tahun,
    'hari'   => 'Hari Ini, ' . date('d F Y'),
    'minggu' => '7 Hari Terakhir',
    'custom' => date('d M Y', strtotime($tgl_dari)) . ' s/d ' . date('d M Y', strtotime($tgl_sampai)),
    default  => 'Semua Periode',
};

$alert = getAlert();

// ============================================================
// CETAK PDF (print view)
// ============================================================
if (isset($_GET['print'])):
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Keuangan – <?= APP_NAME ?></title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family:'Segoe UI',Arial,sans-serif; font-size:11px; color:#222; }
  .header { text-align:center; padding:16px 0 12px; border-bottom:3px solid #1a7a4a; margin-bottom:16px; }
  .header h1 { font-size:16px; color:#1a7a4a; font-weight:700; }
  .header h2 { font-size:13px; margin-top:4px; }
  .header p  { font-size:10px; color:#666; margin-top:3px; }
  .summary   { display:flex; gap:12px; margin-bottom:16px; }
  .sum-box   { flex:1; border:1px solid #ddd; border-radius:6px; padding:10px 14px; text-align:center; }
  .sum-box .label { font-size:9px; color:#666; text-transform:uppercase; letter-spacing:.4px; }
  .sum-box .value { font-size:14px; font-weight:700; margin-top:3px; }
  .sum-box.green .value { color:#1a7a4a; }
  .sum-box.red   .value { color:#ef4444; }
  .sum-box.blue  .value { color:#3b82f6; }
  table { width:100%; border-collapse:collapse; }
  thead th { background:#1a7a4a; color:#fff; padding:8px 10px; text-align:left; font-size:10px; text-transform:uppercase; }
  tbody tr:nth-child(even) { background:#f5faf7; }
  tbody td { padding:7px 10px; border-bottom:1px solid #eee; }
  tfoot td { padding:8px 10px; font-weight:700; background:#f0f4f0; border-top:2px solid #1a7a4a; }
  .badge-masuk  { background:#d1fae5; color:#065f46; padding:2px 8px; border-radius:99px; font-size:9px; font-weight:600; }
  .badge-keluar { background:#fee2e2; color:#991b1b; padding:2px 8px; border-radius:99px; font-size:9px; font-weight:600; }
  .text-right { text-align:right; }
  .masuk  { color:#1a7a4a; font-weight:700; }
  .keluar { color:#ef4444; font-weight:700; }
  .footer-print { margin-top:24px; display:flex; justify-content:space-between; font-size:10px; color:#666; }
  .ttd { text-align:center; }
  .ttd .ttd-box { border-bottom:1px solid #333; width:160px; height:60px; margin:0 auto 4px; }
  @media print {
    body { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
  }
</style>
</head>
<body>
<div class="header">
  <h1>LAPORAN KEUANGAN KAS MASJID</h1>
  <h2><?= strtoupper(MASJID_NAME) ?></h2>
  <p><?= MASJID_ALAMAT ?></p>
  <p style="margin-top:6px">Periode: <strong><?= $periode_label ?></strong>
  <?php if ($filter_jenis !== 'semua'): ?> &bull; Jenis: <strong><?= ucfirst($filter_jenis) ?></strong><?php endif; ?>
  </p>
</div>

<div class="summary">
  <div class="sum-box green">
    <div class="label">Total Pemasukan</div>
    <div class="value"><?= formatRupiah($sum['total_masuk']) ?></div>
  </div>
  <div class="sum-box red">
    <div class="label">Total Pengeluaran</div>
    <div class="value"><?= formatRupiah($sum['total_keluar']) ?></div>
  </div>
  <div class="sum-box blue">
    <div class="label">Saldo / Selisih</div>
    <div class="value"><?= formatRupiah($sum['total_masuk'] - $sum['total_keluar']) ?></div>
  </div>
  <div class="sum-box">
    <div class="label">Jumlah Transaksi</div>
    <div class="value"><?= $sum['total_trx'] ?></div>
  </div>
</div>

<table>
  <thead>
    <tr><th>#</th><th>Tanggal</th><th>Keterangan</th><th>Kategori</th><th>Jenis</th><th class="text-right">Jumlah (Rp)</th></tr>
  </thead>
  <tbody>
    <?php
    $no = 1; $all = [];
    while ($r = $rows->fetch_assoc()) { $all[] = $r; }
    foreach ($all as $r):
    ?>
    <tr>
      <td><?= $no++ ?></td>
      <td><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
      <td><?= htmlspecialchars($r['keterangan']) ?></td>
      <td><?= htmlspecialchars($r['nama_kategori']) ?></td>
      <td><span class="badge-<?= $r['jenis'] ?>"><?= ucfirst($r['jenis']) ?></span></td>
      <td class="text-right <?= $r['jenis'] ?>"><?= ($r['jenis']=='masuk'?'+':'-') . number_format($r['jumlah'],0,',','.') ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
  <tfoot>
    <tr><td colspan="5" class="text-right">Total Pemasukan:</td><td class="text-right masuk">+ <?= number_format($sum['total_masuk'],0,',','.') ?></td></tr>
    <tr><td colspan="5" class="text-right">Total Pengeluaran:</td><td class="text-right keluar">- <?= number_format($sum['total_keluar'],0,',','.') ?></td></tr>
    <tr><td colspan="5" class="text-right">Saldo:</td><td class="text-right"><?= formatRupiah($sum['total_masuk']-$sum['total_keluar']) ?></td></tr>
  </tfoot>
</table>

<div class="footer-print">
  <div>Dicetak: <?= date('d F Y, H:i') ?> WIB</div>
  <div class="ttd">
    <div>Bendahara Masjid,</div>
    <div class="ttd-box"></div>
    <div><strong>(_____________________)</strong></div>
  </div>
</div>

<script>window.onload = () => window.print();</script>
</body>
</html>
<?php
    exit();
endif;
// END PRINT VIEW
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Laporan – <?= APP_NAME ?></title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="admin-wrapper">
<?php include '../includes/partials/sidebar-admin.php'; ?>
<div class="admin-main">

  <div class="topbar">
    <div class="topbar-left">
      <div class="topbar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></div>
      <div class="breadcrumb">
        <span class="bc-item"><i class="fas fa-home"></i></span>
        <span class="bc-sep"><i class="fas fa-chevron-right"></i></span>
        <span class="bc-item active">Laporan Keuangan</span>
      </div>
    </div>
    <div class="topbar-right">
      <div class="topbar-date"><i class="fas fa-calendar-alt"></i> <?= date('d F Y') ?></div>
    </div>
  </div>

  <div class="admin-content">
    <?php if ($alert): ?>
    <div class="alert alert-<?= $alert['type'] ?>"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($alert['message']) ?></div>
    <?php endif; ?>

    <div class="page-header">
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
          <h1 class="page-title"><i class="fas fa-file-invoice-dollar"></i> Laporan Keuangan</h1>
          <p class="page-subtitle">Filter, cetak, dan ekspor laporan keuangan kas masjid</p>
        </div>
        <a href="?<?= http_build_query(array_merge($_GET,['print'=>1])) ?>" target="_blank" class="btn btn-secondary">
          <i class="fas fa-print"></i> Cetak / Ekspor PDF
        </a>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid-4 mb-3">
      <div class="stat-card green animate-fadeIn">
        <div class="stat-icon"><i class="fas fa-arrow-down"></i></div>
        <div class="stat-label">Total Pemasukan</div>
        <div class="stat-value"><?= formatRupiah($sum['total_masuk']) ?></div>
      </div>
      <div class="stat-card red animate-fadeIn delay-1">
        <div class="stat-icon"><i class="fas fa-arrow-up"></i></div>
        <div class="stat-label">Total Pengeluaran</div>
        <div class="stat-value"><?= formatRupiah($sum['total_keluar']) ?></div>
      </div>
      <div class="stat-card gold animate-fadeIn delay-2">
        <div class="stat-icon"><i class="fas fa-wallet"></i></div>
        <div class="stat-label">Saldo Periode</div>
        <div class="stat-value <?= ($sum['total_masuk']-$sum['total_keluar'])>=0?'text-success':'text-danger' ?>">
          <?= formatRupiah($sum['total_masuk'] - $sum['total_keluar']) ?>
        </div>
      </div>
      <div class="stat-card blue animate-fadeIn delay-3">
        <div class="stat-icon"><i class="fas fa-list"></i></div>
        <div class="stat-label">Total Transaksi</div>
        <div class="stat-value"><?= number_format($sum['total_trx']) ?></div>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar mb-3">
      <span class="filter-label"><i class="fas fa-filter"></i> Filter:</span>
      <form method="GET" style="display:contents" id="filterForm">
        <select name="periode" class="form-control form-select" onchange="this.form.submit()">
          <option value="hari"   <?= $filter_periode=='hari'?'selected':'' ?>>Hari Ini</option>
          <option value="minggu" <?= $filter_periode=='minggu'?'selected':'' ?>>7 Hari Terakhir</option>
          <option value="bulan"  <?= $filter_periode=='bulan'?'selected':'' ?>>Per Bulan</option>
          <option value="tahun"  <?= $filter_periode=='tahun'?'selected':'' ?>>Per Tahun</option>
          <option value="custom" <?= $filter_periode=='custom'?'selected':'' ?>>Rentang Tanggal</option>
        </select>
        <?php if ($filter_periode=='bulan'): ?>
          <input type="month" name="bulan" value="<?= $filter_bulan ?>" class="form-control" onchange="this.form.submit()">
        <?php elseif ($filter_periode=='tahun'): ?>
          <select name="tahun" class="form-control form-select" onchange="this.form.submit()">
            <?php for ($y=date('Y'); $y>=2020; $y--): ?>
              <option value="<?= $y ?>" <?= $filter_tahun==$y?'selected':'' ?>><?= $y ?></option>
            <?php endfor; ?>
          </select>
        <?php elseif ($filter_periode=='custom'): ?>
          <input type="date" name="dari" value="<?= $tgl_dari ?>" class="form-control">
          <span style="font-size:.85rem;color:var(--text-muted)">s/d</span>
          <input type="date" name="sampai" value="<?= $tgl_sampai ?>" class="form-control">
          <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
        <?php endif; ?>
        <select name="jenis" class="form-control form-select" onchange="this.form.submit()">
          <option value="semua"  <?= $filter_jenis=='semua'?'selected':'' ?>>Semua Jenis</option>
          <option value="masuk"  <?= $filter_jenis=='masuk'?'selected':'' ?>>Kas Masuk</option>
          <option value="keluar" <?= $filter_jenis=='keluar'?'selected':'' ?>>Kas Keluar</option>
        </select>
        <span style="font-size:.8rem;color:var(--text-muted);white-space:nowrap;background:var(--bg-main);padding:6px 12px;border-radius:var(--radius-sm)">
          <i class="fas fa-tag"></i> <?= $periode_label ?>
        </span>
      </form>
    </div>

    <!-- Tabel -->
    <div class="table-wrapper animate-fadeIn">
      <table class="table table-striped" id="laporanTable">
        <thead>
          <tr><th>#</th><th>Tanggal</th><th>Keterangan</th><th>Kategori</th><th>Jenis</th><th class="text-right">Jumlah (Rp)</th><th>Dicatat Oleh</th></tr>
        </thead>
        <tbody>
          <?php
          $no = 1; $all_rows = [];
          while ($r = $rows->fetch_assoc()) { $all_rows[] = $r; }
          if (count($all_rows)): foreach ($all_rows as $r): ?>
          <tr>
            <td class="text-muted"><?= $no++ ?></td>
            <td><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
            <td><?= htmlspecialchars($r['keterangan']) ?></td>
            <td><span class="badge badge-primary"><?= htmlspecialchars($r['nama_kategori']) ?></span></td>
            <td>
              <?= $r['jenis']=='masuk'
                ? '<span class="badge badge-success"><i class="fas fa-arrow-down"></i> Masuk</span>'
                : '<span class="badge badge-danger"><i class="fas fa-arrow-up"></i> Keluar</span>' ?>
            </td>
            <td class="text-right fw-600 <?= $r['jenis']=='masuk'?'text-success':'text-danger' ?>">
              <?= ($r['jenis']=='masuk'?'+':'-') . ' ' . number_format($r['jumlah'],0,',','.') ?>
            </td>
            <td class="text-muted" style="font-size:.8rem"><?= htmlspecialchars($r['nama_user']) ?></td>
          </tr>
          <?php endforeach; else: ?>
          <tr><td colspan="7"><div class="empty-state"><div class="es-icon"><i class="fas fa-file-search"></i></div><h3>Tidak ada data</h3><p>Coba ubah filter pencarian Anda</p></div></td></tr>
          <?php endif; ?>
        </tbody>
        <?php if (count($all_rows)): ?>
        <tfoot>
          <tr><td colspan="5" class="text-right">Total Pemasukan:</td><td class="text-right text-success fw-600">+ <?= number_format($sum['total_masuk'],0,',','.') ?></td><td></td></tr>
          <tr><td colspan="5" class="text-right">Total Pengeluaran:</td><td class="text-right text-danger fw-600">- <?= number_format($sum['total_keluar'],0,',','.') ?></td><td></td></tr>
          <tr><td colspan="5" class="text-right fw-700">Saldo:</td>
            <td class="text-right fw-700 <?= ($sum['total_masuk']-$sum['total_keluar'])>=0?'text-success':'text-danger' ?>">
              <?= formatRupiah($sum['total_masuk']-$sum['total_keluar']) ?>
            </td><td></td></tr>
        </tfoot>
        <?php endif; ?>
      </table>
    </div>

  </div>
</div>
</div>
<script>
const sidebar = document.getElementById('adminSidebar');
const overlay = document.getElementById('sidebarOverlay');
document.getElementById('sidebarToggle').addEventListener('click',()=>{ sidebar.classList.toggle('open'); overlay.classList.toggle('active'); });
overlay.addEventListener('click',()=>{ sidebar.classList.remove('open'); overlay.classList.remove('active'); });
</script>
</body>
</html>
