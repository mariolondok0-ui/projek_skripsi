<?php
require_once '../includes/config.php';
requireLogin();

// Filter
$filter_periode = $_GET['periode'] ?? 'semua';
$filter_jenis   = $_GET['jenis']   ?? 'semua';
$filter_bulan   = $_GET['bulan']   ?? date('Y-m');
$filter_tahun   = (int)($_GET['tahun']  ?? date('Y'));
$tgl_dari       = $_GET['dari']    ?? date('Y-m-01');
$tgl_sampai     = $_GET['sampai']  ?? date('Y-m-d');

// Build WHERE
$where = ['t.deleted_at IS NULL'];
if ($filter_jenis !== 'semua') $where[] = "t.jenis = '" . sanitize($filter_jenis) . "'";

switch ($filter_periode) {
    case 'semua':  break;
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

// Periode label untuk header PDF dan Tag Filter
$periode_label = match($filter_periode) {
    'bulan'  => date('01 F Y', strtotime($filter_bulan . '-01')) . ' s/d ' . date('t F Y', strtotime($filter_bulan . '-01')),
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: 'Times New Roman', Times, serif; font-size: 13px; color: #000; background: #e8f0f8; -webkit-font-smoothing: antialiased; }
  
  /* --- CONTROL BAR (Menu Atas) --- */
  .control-bar { 
      position: fixed; top: 0; left: 0; right: 0; 
      background: #fff; padding: 15px 30px; 
      display: flex; justify-content: space-between; align-items: center; 
      box-shadow: 0 4px 15px rgba(0,0,0,0.08); z-index: 1000; 
      font-family: 'Segoe UI', Arial, sans-serif;
  }
  .control-bar-left { display: flex; gap: 10px; }
  .control-bar-right { display: flex; gap: 12px; align-items: center; }
  
  .btn-print { padding: 10px 18px; border: 1px solid #ccc; background: #fff; cursor: pointer; border-radius: 6px; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; color: #333; transition: 0.2s;}
  .btn-print:hover { background: #f0f0f0; }
  .btn-print.pdf { background: #000; color: #fff; border: none; }
  .btn-print.pdf:hover { background: #333; }
  .btn-print.cetak { background: #000; color: #fff; border: none; }
  .btn-print.cetak:hover { background: #333; }

  .dropdown { position: relative; display: inline-block; }
  .dropdown-content {
      display: none; position: absolute; right: 0; top: 100%;
      background-color: #fff; min-width: 180px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.15); z-index: 1001;
      border-radius: 8px; overflow: hidden; margin-top: 8px;
      border: 1px solid #ddd;
  }
  .dropdown-content.show { display: block; }
  .dropdown-content a {
      color: #333; padding: 12px 16px; text-decoration: none; display: flex; align-items: center; gap: 10px;
      font-size: 12px; font-weight: 600; border-bottom: 1px solid #f5f5f5;
  }
  .dropdown-content a:hover { background-color: #f1f1f1; color: #000; }

  /* --- KERTAS PRINT (A4) --- */
  .print-wrapper { margin-top: 90px; padding-bottom: 40px; }
  .print-container { 
      background: #fff; 
      width: 210mm; 
      min-height: 297mm; 
      margin: 0 auto; 
      padding: 15mm 20mm; 
      box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
  }
  
  /* Kop Surat Hitam Pekat */
  .header { display: flex; align-items: center; border-bottom: 3px double #000; padding-bottom: 12px; margin-bottom: 18px; }
  .header-logo img { width: 75px; height: 75px; object-fit: contain; border-radius: 6px; margin-right: 15px; }
  .header-text { text-align: center; width: 100%; margin-right: 75px; }
  .header-text h1 { font-size: 15px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; color: #000; }
  .header-text h2 { font-size: 14px; font-weight: bold; text-transform: uppercase; margin-top: 2px; color: #000; }
  .header-text p  { font-size: 11px; margin-top: 2px; color: #000; }
  
  .sub-header { text-align: center; margin-bottom: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase; color: #000; letter-spacing: 0.5px; }

  /* Summary Box */
  .summary { display: flex; gap: 10px; margin-bottom: 22px; }
  .sum-box { flex: 1; border: 1px solid #000; border-top: 3px solid #000; border-radius: 6px; padding: 10px 8px; text-align: center; background: #fff; }
  .sum-box .label { font-size: 9.5px; text-transform: uppercase; font-weight: bold; color: #000; letter-spacing: 0.3px; }
  .sum-box .value { font-size: 12.5px; font-weight: bold; margin-top: 5px; color: #000; }
  
  /* --- TABEL --- */
  table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
  thead th { background: #000; color: #fff; padding: 8px 10px; font-size: 11.5px; text-transform: uppercase; border: 1px solid #000; font-weight: bold; letter-spacing: 0.3px; }
  tbody td { padding: 7px 10px; border: 1px solid #000; font-size: 12px; vertical-align: middle; color: #000; }
  tbody tr:nth-child(even) { background: #f9f9f9; }
  tfoot td { padding: 8px 10px; font-weight: bold; border: 1px solid #000; font-size: 12px; background: #f1f1f1; color: #000; }
  
  .text-center { text-align: center; }
  .text-left { text-align: left; }
  .text-right { text-align: right; }
  
  /* Footer Tanda Tangan (Tanpa kurung & garis tunggal) */
  .footer-print { margin-top: 35px; display: flex; justify-content: space-between; font-size: 12px; page-break-inside: avoid; color: #000; }
  .ttd { text-align: center; width: 210px; }
  .ttd .space { height: 60px; }
  .ttd strong { border-bottom: 1px solid #000; padding-bottom: 1px; display: inline-block; width: 100%; font-weight: bold; }
  
  @media print {
    body { background: #fff; margin: 0; padding: 0; }
    .control-bar { display: none !important; }
    .print-wrapper { margin-top: 0; padding: 0; }
    .print-container { width: 100%; margin: 0; padding: 0; box-shadow: none; min-height: auto; border: none; }
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  }
</style>
</head>
<body>

<!-- Menu Control Bar -->
<div class="control-bar" data-html2canvas-ignore="true">
    <div class="control-bar-left">
        <button onclick="kembali()" class="btn-print"><i class="fas fa-arrow-left"></i> Tutup </button>
    </div>
    
    <div class="control-bar-center" style="font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; font-weight: 600; color: #555;">
        <span>Mode Cetak: <?= $periode_label ?></span>
    </div>

    <div class="control-bar-right">
        <!-- Dropdown Cetak Printer dengan parameter auto=print -->
        <div class="dropdown">
            <button class="btn-print cetak" onclick="toggleDropdown(event, 'printDropdown')">
                <i class="fas fa-print"></i> Cetak Printer 
                <i class="fas fa-chevron-down" style="margin-left:6px; font-size:11px;"></i>
            </button>
            <div id="printDropdown" class="dropdown-content">
                <a href="?periode=semua&print=1&auto=print"><i class="fas fa-list"></i> Semua Periode</a>
                <a href="?periode=bulan&bulan=<?= date('Y-m') ?>&print=1&auto=print"><i class="fas fa-calendar-alt"></i> Per Bulan Ini</a>
                <a href="?periode=minggu&print=1&auto=print"><i class="fas fa-calendar-week"></i> Per Minggu Ini</a>
            </div>
        </div>

        <!-- Dropdown Unduh PDF dengan parameter auto=pdf -->
        <div class="dropdown">
            <button class="btn-print pdf" onclick="toggleDropdown(event, 'pdfDropdown')">
                <i class="fas fa-file-pdf"></i> Unduh PDF 
                <i class="fas fa-chevron-down" style="margin-left:6px; font-size:11px;"></i>
            </button>
            <div id="pdfDropdown" class="dropdown-content">
                <a href="?periode=semua&print=1&auto=pdf"><i class="fas fa-list"></i> Semua Periode</a>
                <a href="?periode=bulan&bulan=<?= date('Y-m') ?>&print=1&auto=pdf"><i class="fas fa-calendar-alt"></i> Per Bulan Ini</a>
                <a href="?periode=minggu&print=1&auto=pdf"><i class="fas fa-calendar-week"></i> Per Minggu Ini</a>
            </div>
        </div>
    </div>
</div>

<div class="print-wrapper">
    <!-- Area Cetak -->
    <div class="print-container" id="area-cetak">
        
        <!-- Kop Surat -->
        <div class="header">
          <div class="header-logo">
            <img src="../assets/img/logo.jpg" alt="Logo Masjid">
          </div>
          <div class="header-text">
            <h1>LAPORAN KEUANGAN KAS MASJID</h1>
            <h2>MASJID BAETURRAHMAN</h2>
            <p><?= MASJID_ALAMAT ?></p>
          </div>
        </div>

        <div class="sub-header">
          PERIODE: <?= strtoupper($periode_label) ?>
          <?php if ($filter_jenis !== 'semua'): ?> &bull; JENIS: <?= strtoupper($filter_jenis) ?><?php endif; ?>
        </div>
        
        <!-- Summary Boxes Profesional -->
        <div class="summary">
          <div class="sum-box">
            <div class="label">Total Pemasukan</div>
            <div class="value"><?= formatRupiah($sum['total_masuk']) ?></div>
          </div>
          <div class="sum-box">
            <div class="label">Total Pengeluaran</div>
            <div class="value"><?= formatRupiah($sum['total_keluar']) ?></div>
          </div>
          <div class="sum-box">
            <div class="label">Saldo / Selisih</div>
            <div class="value"><?= formatRupiah($sum['total_masuk'] - $sum['total_keluar']) ?></div>
          </div>
          <div class="sum-box">
            <div class="label">Total Transaksi</div>
            <div class="value"><?= $sum['total_trx'] ?></div>
          </div>
        </div>
        
        <!-- Tabel Data -->
        <table>
          <thead>
            <tr>
                <th class="text-center" width="5%">No</th>
                <th class="text-left" width="15%">Tanggal</th>
                <th class="text-left">Keterangan</th>
                <th class="text-left" width="22%">Kategori</th>
                <th class="text-center" width="12%">Jenis</th>
                <th class="text-right" width="18%">Jumlah (Rp)</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $no = 1; $all = [];
            while ($r = $rows->fetch_assoc()) { $all[] = $r; }
            if(count($all) > 0):
                foreach ($all as $r):
            ?>
            <tr>
              <td class="text-center"><?= $no++ ?></td>
              <td><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
              <td><?= htmlspecialchars($r['keterangan']) ?></td>
              <td><?= htmlspecialchars($r['nama_kategori']) ?></td>
              <td class="text-center"><?= ucfirst($r['jenis']) ?></td>
              <td class="text-right"><?= ($r['jenis']=='masuk'?'+':'-') . number_format($r['jumlah'],0,',','.') ?></td>
            </tr>
            <?php 
                endforeach; 
            else:
            ?>
            <tr>
              <td colspan="6" class="text-center" style="padding: 15px; color: #666;">Tidak ada data transaksi pada periode ini.</td>
            </tr>
            <?php endif; ?>
          </tbody>
          <tfoot>
            <tr>
                <td colspan="5" class="text-right">Total Pemasukan:</td>
                <td class="text-right">+ <?= number_format($sum['total_masuk'],0,',','.') ?></td>
            </tr>
            <tr>
                <td colspan="5" class="text-right">Total Pengeluaran:</td>
                <td class="text-right">- <?= number_format($sum['total_keluar'],0,',','.') ?></td>
            </tr>
            <tr>
                <td colspan="5" class="text-right">Saldo Akhir:</td>
                <td class="text-right"><?= formatRupiah($sum['total_masuk']-$sum['total_keluar']) ?></td>
            </tr>
          </tfoot>
        </table>
        
        <!-- Tanda Tangan / Footer (Tanpa kurung & garis tunggal) -->
        <div class="footer-print">
          <div>Dicetak pada: <?= date('d F Y, H:i') ?> WIB</div>
          <div class="ttd">
            <div>Bendahara Masjid,</div>
            <div class="space"></div>
            <strong></strong>
          </div>
        </div>
    </div>
</div>

<script>
    function toggleDropdown(event, id) {
        event.stopPropagation();
        document.querySelectorAll('.dropdown-content').forEach(el => {
            if(el.id !== id) el.classList.remove('show');
        });
        document.getElementById(id).classList.toggle('show');
    }

    window.onclick = function(event) {
        if (!event.target.matches('.btn-print') && !event.target.matches('.fas')) {
            document.querySelectorAll('.dropdown-content').forEach(el => {
                el.classList.remove('show');
            });
        }
    }

    function kembali() {
        if (window.history.length > 1 && document.referrer !== "") {
            window.history.back();
        } else {
            window.close();
        }
    }

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
        <?php if(isset($_GET['auto']) && $_GET['auto'] == 'print'): ?>
            setTimeout(() => { window.print(); }, 800);
        <?php elseif(isset($_GET['auto']) && $_GET['auto'] == 'pdf'): ?>
            setTimeout(() => { unduhPDF(); }, 800);
        <?php endif; ?>
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
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
<title>Laporan <?= APP_NAME ?></title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css?v=1786264272">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.table-wrapper {
    display: block !important;
    width: 100% !important;
    overflow-x: auto !important;
    -webkit-overflow-scrolling: touch !important;
    border-radius: 12px;
}
@media (max-width: 768px) {
    html, body { overflow-x: hidden !important; max-width: 100vw !important; }
    .admin-wrapper { display: block !important; width: 100% !important; overflow-x: hidden !important; }
    .admin-main { width: 100% !important; margin-left: 0 !important; padding: 0 !important; box-sizing: border-box !important; }
    .admin-content { width: 100% !important; padding: 12px !important; box-sizing: border-box !important; margin: 0 !important; }
    .topbar { width: 100% !important; box-sizing: border-box !important; padding: 12px 15px !important; }
    .t-name { display: none !important; }
    .page-header > div { flex-direction: column !important; align-items: stretch !important; gap: 15px !important; text-align: center; }
    .page-header > div > div:last-child { display: flex !important; flex-direction: row !important; width: 100% !important; gap: 10px !important; }
    .page-header a.btn { flex: 1 !important; justify-content: center !important; align-items: center !important; display: inline-flex !important; padding: 10px 5px !important; font-size: 0.8rem !important; white-space: nowrap !important; }
    .page-title { font-size: 1.35rem !important; margin-bottom: 4px !important; }
    .page-subtitle { font-size: 0.85rem !important; margin-bottom: 5px !important; }
    .grid-4 { display: grid !important; grid-template-columns: repeat(2, 1fr) !important; gap: 12px !important; width: 100% !important; }
    .stat-card { padding: 15px 12px !important; border-radius: 12px !important; width: auto !important; margin: 0 !important; }
    .stat-label { font-size: 0.72rem !important; }
    .stat-value { font-size: 1.05rem !important; margin: 4px 0 !important; }
    .filter-bar { display: flex !important; flex-direction: column !important; padding: 15px !important; border-radius: 12px !important; gap: 12px !important; text-align: center; }
    #filterForm { display: flex !important; flex-direction: column !important; gap: 10px !important; width: 100% !important; }
    .filter-bar .form-control { width: 100% !important; box-sizing: border-box !important; }
    .table-wrapper table { min-width: 800px !important; }
    .table th, .table td { padding: 10px 8px !important; font-size: 0.85rem !important; }
}
@media (min-width: 576px) and (max-width: 991px) {
    .grid-4 { display: grid !important; grid-template-columns: repeat(2, 1fr) !important; gap: 15px !important; }
}
</style>
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
        
        <div style="display:flex; gap:10px; flex-wrap:wrap; width: 100%;">
          <a href="<?= APP_URL ?>/admin/dashboard.php" class="btn btn-ghost" style="border:1.5px solid var(--border);background:var(--bg-card)">
            <i class="fas fa-arrow-left"></i> Kembali
          </a>
          <a href="?<?= http_build_query(array_merge($_GET,['print'=>1])) ?>" target="_blank" class="btn" style="background-color: #d4af37; color: white; border: none;">
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
          <option value="semua"  <?= $filter_periode=='semua'?'selected':'' ?>>Semua Periode</option>
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
          <button type="submit" class="btn btn-primary btn-sm" style="padding: 10px; width: 100%;"><i class="fas fa-search"></i> Cari Rentang</button>
        <?php endif; ?>
        
        <select name="jenis" class="form-control form-select" onchange="this.form.submit()">
          <option value="semua"  <?= $filter_jenis=='semua'?'selected':'' ?>>Semua Jenis</option>
          <option value="masuk"  <?= $filter_jenis=='masuk'?'selected':'' ?>>Kas Masuk</option>
          <option value="keluar" <?= $filter_jenis=='keluar'?'selected':'' ?>>Kas Keluar</option>
        </select>
        <span style="font-size:.8rem;color:var(--text-muted);white-space:nowrap;background:var(--bg-main);padding:8px 12px;border-radius:var(--radius-sm)">
          <i class="fas fa-tag"></i> <?= $periode_label ?>
        </span>
      </form>
    </div>

    <!-- Tabel Bersih -->
    <div class="table-wrapper animate-fadeIn">
      <table class="table table-striped" id="laporanTable">
        <thead>
          <tr>
            <th class="text-center">No</th>
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
          $rows->data_seek(0);
          while ($r = $rows->fetch_assoc()) { $all_rows[] = $r; }
          if (count($all_rows)): foreach ($all_rows as $r): ?>
          <tr>
            <td class="text-center text-muted"><?= $no++ ?></td>
            <td class="text-left" style="white-space: nowrap;"><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
            <td class="text-left"><?= htmlspecialchars($r['keterangan']) ?></td>
            <td class="text-left"><span class="badge badge-primary"><?= htmlspecialchars($r['nama_kategori']) ?></span></td>
            <td class="text-center" style="white-space: nowrap;">
              <?= $r['jenis']=='masuk'
                ? '<span class="badge badge-success"><i class="fas fa-arrow-down"></i> Masuk</span>'
                : '<span class="badge badge-danger"><i class="fas fa-arrow-up"></i> Keluar</span>' ?>
            </td>
            <td class="text-right fw-600 <?= $r['jenis']=='masuk'?'text-success':'text-danger' ?>" style="white-space: nowrap;">
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
          <tr><td colspan="5" class="text-right">Total Pemasukan:</td><td class="text-right text-success fw-600" style="white-space: nowrap;">+ <?= number_format($sum['total_masuk'],0,',','.') ?></td><td></td></tr>
          <tr><td colspan="5" class="text-right">Total Pengeluaran:</td><td class="text-right text-danger fw-600" style="white-space: nowrap;">- <?= number_format($sum['total_keluar'],0,',','.') ?></td><td></td></tr>
          <tr><td colspan="5" class="text-right fw-700">Saldo:</td>
            <td class="text-right fw-700 <?= ($sum['total_masuk']-$sum['total_keluar'])>=0?'text-success':'text-danger' ?>" style="white-space: nowrap;">
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