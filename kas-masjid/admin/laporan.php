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
<title>Cetak Laporan - <?= APP_NAME ?></title>
<!-- Memanggil Library HTML to PDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<!-- FontAwesome untuk ikon di tombol -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family:'Segoe UI',Arial,sans-serif; font-size:12px; color:#222; background: #e8f5ee; }
  
  /* --- CONTROL BAR (Menu Atas) --- */
  .control-bar { 
      position: fixed; top: 0; left: 0; right: 0; 
      background: #fff; padding: 15px 30px; 
      display: flex; justify-content: space-between; align-items: center; 
      box-shadow: 0 4px 15px rgba(0,0,0,0.08); z-index: 1000; 
  }
  .control-bar-left { display: flex; gap: 10px; }
  .control-bar-right { display: flex; gap: 10px; }
  
  .btn-print { padding: 10px 18px; border: 1px solid #ccc; background: #fff; cursor: pointer; border-radius: 6px; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; color: #333; transition: 0.2s; font-family: inherit;}
  .btn-print:hover { background: #f0f0f0; }
  .btn-print.pdf { background: #c9a84c; color: #fff; border: none; }
  .btn-print.pdf:hover { background: #b8973e; }
  .btn-print.cetak { background: #1a7a4a; color: #fff; border: none; }
  .btn-print.cetak:hover { background: #145e38; }

  /* --- KERTAS PRINT (A4) --- */
  .print-wrapper { margin-top: 90px; padding-bottom: 40px; }
  .print-container { 
      background: #fff; 
      width: 210mm; 
      min-height: 297mm; 
      margin: 0 auto; 
      padding: 20mm 20mm; 
      box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
  }
  
  /* Typography & Alignment */
  .header { text-align:center; padding-bottom: 15px; border-bottom:3px solid #1a7a4a; margin-bottom:20px; }
  .header h1 { font-size:18px; color:#1a7a4a; font-weight:800; letter-spacing: 1px; }
  .header h2 { font-size:14px; margin-top:5px; color: #333; }
  .header p  { font-size:11px; color:#555; margin-top:5px; line-height: 1.5; }
  
  /* Summary Box */
  .summary { display:flex; gap:15px; margin-bottom:25px; }
  .sum-box { flex:1; border:1px solid #e5e7eb; border-radius:8px; padding:12px; text-align:center; background: #fafafa; }
  .sum-box .label { font-size:10px; color:#6b7280; text-transform:uppercase; font-weight: 600; letter-spacing: 0.5px; }
  .sum-box .value { font-size:15px; font-weight:800; margin-top:6px; }
  .sum-box.green .value { color:#16a34a; }
  .sum-box.red .value { color:#dc2626; }
  .sum-box.blue .value { color:#2563eb; }
  
  /* --- TABEL (RATA KIRI & KANAN) --- */
  table { width:100%; border-collapse:collapse; margin-bottom: 20px; }
  thead th { background:#1a7a4a; color:#fff; padding:10px 12px; font-size:11px; text-transform:uppercase; font-weight: 600; border: 1px solid #145e38; }
  tbody td { padding:9px 12px; border: 1px solid #e5e7eb; font-size: 11px; vertical-align: middle; }
  tbody tr:nth-child(even) { background:#f9fafb; }
  tfoot td { padding:10px 12px; font-weight:700; background:#f0f4f0; border: 1px solid #d1d5db; border-top: 2px solid #1a7a4a; font-size: 11px; }
  
  /* Class Alignment */
  .text-center { text-align: center; }
  .text-left { text-align: left; }
  .text-right { text-align: right; }
  
  .badge-masuk  { color:#059669; font-weight:700; }
  .badge-keluar { color:#dc2626; font-weight:700; }
  .masuk  { color:#059669; font-weight:700; }
  .keluar { color:#dc2626; font-weight:700; }
  
  /* Footer Tanda Tangan */
  .footer-print { margin-top:30px; display:flex; justify-content:space-between; font-size:11px; color:#444; }
  .ttd { text-align:center; }
  .ttd .ttd-box { border-bottom:1px solid #222; width:180px; height:70px; margin:0 auto 5px; }
  
  /* Hilangkan efek website saat masuk mode cetak asli */
  @media print {
    body { background: #fff; margin: 0; padding: 0; }
    .control-bar { display: none !important; }
    .print-wrapper { margin-top: 0; padding: 0; }
    .print-container { width: 100%; margin: 0; padding: 0; box-shadow: none; min-height: auto; border: none; }
    body { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
  }
</style>
</head>
<body>

<!-- Menu Control Bar -->
<div class="control-bar" data-html2canvas-ignore="true">
    <div class="control-bar-left">
        <button onclick="kembali()" class="btn-print"><i class="fas fa-arrow-left"></i> Tutup & Kembali</button>
    </div>
    <div class="control-bar-right">
        <button onclick="window.print()" class="btn-print cetak"><i class="fas fa-print"></i> Cetak Printer</button>
        <button onclick="unduhPDF()" class="btn-print pdf"><i class="fas fa-file-pdf"></i> Unduh PDF</button>
    </div>
</div>

<div class="print-wrapper">
    <!-- Area yang akan diekspor menjadi PDF -->
    <div class="print-container" id="area-cetak">
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
            <div class="label">Total Transaksi</div>
            <div class="value"><?= $sum['total_trx'] ?></div>
          </div>
        </div>
        
        <table>
          <thead>
            <tr>
                <th class="text-center">#</th>
                <th class="text-left">Tanggal</th>
                <th class="text-left">Keterangan</th>
                <th class="text-left">Kategori</th>
                <th class="text-center">Jenis</th>
                <th class="text-right">Jumlah (Rp)</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $no = 1; $all = [];
            while ($r = $rows->fetch_assoc()) { $all[] = $r; }
            foreach ($all as $r):
            ?>
            <tr>
              <td class="text-center"><?= $no++ ?></td>
              <td class="text-left"><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
              <td class="text-left"><?= htmlspecialchars($r['keterangan']) ?></td>
              <td class="text-left"><?= htmlspecialchars($r['nama_kategori']) ?></td>
              <td class="text-center"><span class="badge-<?= $r['jenis'] ?>"><?= ucfirst($r['jenis']) ?></span></td>
              <td class="text-right <?= $r['jenis'] ?>"><?= ($r['jenis']=='masuk'?'+':'-') . number_format($r['jumlah'],0,',','.') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr><td colspan="5" class="text-right">Total Pemasukan:</td><td class="text-right masuk">+ <?= number_format($sum['total_masuk'],0,',','.') ?></td></tr>
            <tr><td colspan="5" class="text-right">Total Pengeluaran:</td><td class="text-right keluar">- <?= number_format($sum['total_keluar'],0,',','.') ?></td></tr>
            <tr><td colspan="5" class="text-right">Saldo Akhir:</td><td class="text-right"><?= formatRupiah($sum['total_masuk']-$sum['total_keluar']) ?></td></tr>
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
    </div>
</div>

<script>
    function kembali() {
        // Menutup tab saat tombol kembali diklik
        if (window.history.length > 1 && document.referrer !== "") {
            window.history.back();
        } else {
            window.close();
        }
    }

    // Fungsi Generate PDF Rapi
    function unduhPDF() {
        const elemen = document.getElementById('area-cetak');
        const opsi = {
            margin:       [5, 5, 5, 5], 
            filename:     'Laporan_Keuangan_Masjid_<?= date("dMy") ?>.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true, letterRendering: true },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        
        html2pdf().set(opsi).from(elemen).save();
    }

    window.onload = () => {
        // Kosong agar PDF dan Print tidak jalan otomatis
    };
</script>
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
<title>Laporan <?= APP_NAME ?></title>
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
        
        <!-- Tombol Kembali dan Cetak/PDF yang sudah disatukan -->
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
          <a href="javascript:history.back()" class="btn btn-ghost" style="border: 1.5px solid var(--border); background: var(--bg-card);">
            <i class="fas fa-arrow-left"></i> Kembali
          </a>
          <a href="?<?= http_build_query(array_merge($_GET,['print'=>1])) ?>" target="_blank" class="btn btn-secondary">
            <i class="fas fa-print"></i> Cetak / Ekspor PDF
          </a>
        </div>
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

    <!-- Tabel Laporan -->
    <div class="table-wrapper animate-fadeIn">
      <table class="table table-striped" id="laporanTable">
        <thead>
          <tr>
            <th class="text-center">#</th>
            <th class="text-left">Tanggal</th>
            <th class="text-left">Keterangan</th>
            <th class="text-left">Kategori</th>
            <th class="text-center">Jenis</th>
            <th class="text-right">Jumlah (Rp)</th>
            <th class="text-left">Dicatat Oleh</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $no = 1; $all_rows = [];
          while ($r = $rows->fetch_assoc()) { $all_rows[] = $r; }
          if (count($all_rows)): foreach ($all_rows as $r): ?>
          <tr>
            <td class="text-center text-muted"><?= $no++ ?></td>
            <td class="text-left"><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
            <td class="text-left"><?= htmlspecialchars($r['keterangan']) ?></td>
            <td class="text-left"><span class="badge badge-primary"><?= htmlspecialchars($r['nama_kategori']) ?></span></td>
            <td class="text-center">
              <?= $r['jenis']=='masuk'
                ? '<span class="badge badge-success"><i class="fas fa-arrow-down"></i> Masuk</span>'
                : '<span class="badge badge-danger"><i class="fas fa-arrow-up"></i> Keluar</span>' ?>
            </td>
            <td class="text-right fw-600 <?= $r['jenis']=='masuk'?'text-success':'text-danger' ?>">
              <?= ($r['jenis']=='masuk'?'+':'-') . ' ' . number_format($r['jumlah'],0,',','.') ?>
            </td>
            <td class="text-left text-muted" style="font-size:.8rem"><?= htmlspecialchars($r['nama_user']) ?></td>
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

document.getElementById('sidebarToggle').addEventListener('click', () => {
  if (window.innerWidth <= 768) {
    sidebar.classList.toggle('open');
    overlay.classList.toggle('active');
  } else {
    document.querySelector('.admin-wrapper').classList.toggle('toggled');
  }
});

overlay.addEventListener('click',()=>{
  sidebar.classList.remove('open');
  overlay.classList.remove('active');
});
</script>
</body>
</html>