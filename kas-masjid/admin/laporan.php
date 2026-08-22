<?php
require_once '../includes/config.php';
requireLogin();

$admin_id = (int)$_SESSION['admin_id'];
$admin    = $conn->query("SELECT * FROM users WHERE id=$admin_id")->fetch_assoc();

// Fungsi Helper Format Bulan Indonesia
function formatBulanIndo($bulan_angka) {$bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    return $bulan[(int)$bulan_angka] ?? '';
}

function formatTanggalIndo($tanggal) {$bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $pecah = explode('-', date('Y-m-d', strtotime($tanggal)));
    return $pecah[2] . ' ' .$bulan[(int)$pecah[1]] . ' ' .$pecah[0];
}

// Filter
$filter_periode = $_GET['periode'] ?? 'semua';
$filter_jenis   = $_GET['jenis']   ?? 'semua';

// Pemisahan Bulan dan Tahun dari Input Select (Real-time)
$filter_bln_num = sprintf('%02d', (int)($_GET['bln_num'] ?? date('m')));
$filter_thn_num = (int)($_GET['thn_num'] ?? date('Y'));
$filter_bulan   = $filter_thn_num . '-' . $filter_bln_num;

$filter_tahun   = (int)($_GET['tahun']  ?? date('Y'));
$tgl_dari       = $_GET['dari']    ?? date('Y-m-01');
$tgl_sampai     = $_GET['sampai']  ?? date('Y-m-d');

// Build WHERE
$where = ['t.deleted_at IS NULL'];
if ($filter_jenis !== 'semua') $where[] = "t.jenis = '" . sanitize($filter_jenis) . "'";

switch ($filter_periode) {
    case 'semua':  break;
    case 'bulan':  
        $where[] = "DATE_FORMAT(t.tanggal,'%Y-%m') = '" . sanitize($filter_bulan) . "'"; 
        break;
    case 'tahun':  
        $where[] = "YEAR(t.tanggal) = $filter_tahun"; 
        break;
    case 'custom': 
        $where[] = "t.tanggal BETWEEN '" . sanitize($tgl_dari) . "' AND '" . sanitize($tgl_sampai) . "'"; 
        break;
    case 'minggu': 
        $where[] = "t.tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"; 
        break;
}
$where_sql = implode(' AND ',$where);

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

// Periode label Bahasa Indonesia
$periode_label = match($filter_periode) {
    'bulan'  => formatBulanIndo($filter_bln_num) . ' ' . $filter_thn_num,
    'tahun'  => 'Tahun ' . $filter_tahun,
    'minggu' => '7 Hari Terakhir',
    'custom' => formatTanggalIndo($tgl_dari) . ' s/d ' . formatTanggalIndo($tgl_sampai),
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

  .print-wrapper { margin-top: 90px; padding-bottom: 40px; }
  .print-container { 
    background: #fff; 
    width: 210mm; 
    min-height: 297mm; 
    margin: 0 auto; 
    padding: 15mm 20mm; 
    box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
  }
  
  .header { display: flex; align-items: center; border-bottom: 3px double #000; padding-bottom: 12px; margin-bottom: 18px; }
  .header-logo img { width: 75px; height: 75px; object-fit: contain; border-radius: 6px; margin-right: 15px; }
  .header-text { text-align: center; width: 100%; margin-right: 75px; }
  .header-text h1 { font-size: 15px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; color: #000; }
  .header-text h2 { font-size: 14px; font-weight: bold; text-transform: uppercase; margin-top: 2px; color: #000; }
  .header-text p  { font-size: 11px; margin-top: 2px; color: #000; }
  
  .sub-header { text-align: center; margin-bottom: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase; color: #000; letter-spacing: 0.5px; }

  table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
  thead th { background: #000; color: #fff; padding: 8px 10px; font-size: 11.5px; text-transform: uppercase; border: 1px solid #000; font-weight: bold; letter-spacing: 0.3px; }
  tbody td { padding: 7px 10px; border: 1px solid #000; font-size: 12px; vertical-align: middle; color: #000; }
  tbody tr:nth-child(even) { background: #f9f9f9; }
  tfoot td { padding: 8px 10px; font-weight: bold; border: 1px solid #000; font-size: 12px; background: #f1f1f1; color: #000; }
  
  .text-center { text-align: center; }
  .text-left { text-align: left; }
  .text-right { text-align: right; }
  
  .footer-print { margin-top: 35px; display: flex; justify-content: flex-end; font-size: 12px; page-break-inside: avoid; color: #000; }
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

<div class="control-bar" data-html2canvas-ignore="true">
    <div class="control-bar-left">
        <button onclick="kembali()" class="btn-print"><i class="fas fa-arrow-left"></i> Tutup </button>
    </div>
    
    <div class="control-bar-center" style="font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; font-weight: 600; color: #555;">
        <span>Mode Cetak: <?= $periode_label ?></span>
    </div>

    <div class="control-bar-right">
        <div class="dropdown">
            <button class="btn-print cetak" onclick="toggleDropdown(event, 'printDropdown')">
                <i class="fas fa-print"></i> Cetak Printer 
                <i class="fas fa-chevron-down" style="margin-left:6px; font-size:11px;"></i>
            </button>
            <div id="printDropdown" class="dropdown-content">
                <a href="?periode=semua&print=1&auto=print"><i class="fas fa-list"></i> Semua Periode</a>
                <a href="?periode=bulan&bln_num=<?= date('m') ?>&thn_num=<?= date('Y') ?>&print=1&auto=print"><i class="fas fa-calendar-alt"></i> Per Bulan Ini</a>
                <a href="?periode=minggu&print=1&auto=print"><i class="fas fa-calendar-week"></i> Per Minggu Ini</a>
            </div>
        </div>

        <div class="dropdown">
            <button class="btn-print pdf" onclick="toggleDropdown(event, 'pdfDropdown')">
                <i class="fas fa-file-pdf"></i> Unduh PDF 
                <i class="fas fa-chevron-down" style="margin-left:6px; font-size:11px;"></i>
            </button>
            <div id="pdfDropdown" class="dropdown-content">
                <a href="?periode=semua&print=1&auto=pdf"><i class="fas fa-list"></i> Semua Periode</a>
                <a href="?periode=bulan&bln_num=<?= date('m') ?>&thn_num=<?= date('Y') ?>&print=1&auto=pdf"><i class="fas fa-calendar-alt"></i> Per Bulan Ini</a>
                <a href="?periode=minggu&print=1&auto=pdf"><i class="fas fa-calendar-week"></i> Per Minggu Ini</a>
            </div>
        </div>
    </div>
</div>

<div class="print-wrapper">
    <div class="print-container" id="area-cetak">
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
              <td><?= formatTanggalIndo($r['tanggal']) ?></td>
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
                <td class="text-right">
                  <?php 
                    $selisih_print = $sum['total_masuk'] - $sum['total_keluar'];
                    if ($selisih_print < 0) {
                        echo '(Rp ' . number_format(abs($selisih_print), 0, ',', '.') . ')';
                    } else {
                        echo 'Rp ' . number_format($selisih_print, 0, ',', '.');
                    }
                  ?>
                </td>
            </tr>
          </tfoot>
        </table>
        
        <div class="footer-print">
          <div class="ttd">
            <div>Garut, <?= formatTanggalIndo(date('Y-m-d')) ?></div>
            <div style="margin-top: 4px;">Bendahara Masjid,</div>
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
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
<title>Laporan <?= APP_NAME ?></title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css?v=<?= time() ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<!-- FONT MODERN: Plus Jakarta Sans -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* =======================================================
   STYLING POP-UP MODAL FILTER LAPORAN MODERN
   ======================================================= */
.modern-modal-overlay {
  position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(4px); z-index: 9999;
  display: flex; align-items: center; justify-content: center;
  padding: 20px; opacity: 0; visibility: hidden; transition: all 0.3s ease;
}
.modern-modal-overlay.active { opacity: 1; visibility: visible; }
.modern-modal-box {
  background: #ffffff; border-radius: 16px; width: 100%; max-width: 540px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  transform: scale(0.95) translateY(10px); transition: all 0.3s ease;
  overflow: hidden; border: none; font-family: 'Plus Jakarta Sans', sans-serif !important;
}
.modern-modal-overlay.active .modern-modal-box { transform: scale(1) translateY(0); }

.modern-modal-header {
  padding: 20px 24px; background: #ffffff; 
  border-bottom: 1px solid #e2e8f0; 
  display: flex; align-items: center; justify-content: space-between;
}
.modern-modal-title { 
  font-size: 1.1rem; font-weight: 700; color: var(--primary); 
  display: flex; align-items: center; gap: 10px;
  letter-spacing: -0.2px;
}

.modern-modal-close {
  width: 32px; height: 32px; background: transparent; border: none;
  display: flex; align-items: center; justify-content: center; 
  color: #94a3b8; font-size: 1.25rem; cursor: pointer; transition: 0.2s;
}
.modern-modal-close:hover { color: #0f172a; }

.modern-modal-body { padding: 24px; background: #ffffff; }

.modern-modal-body .form-label { 
    font-size: 0.85rem; font-weight: 600; color: #334155; 
    margin-bottom: 8px; letter-spacing: -0.1px; display: block;
}
.modern-modal-body .form-control { 
    background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; 
    padding: 10px 14px; font-size: 0.9rem; font-weight: 500; color: #0f172a;
    box-shadow: none; transition: 0.2s; font-family: 'Plus Jakarta Sans', sans-serif !important;
    width: 100%;
}
.modern-modal-body .form-control:focus { 
    background-color: #ffffff; border-color: var(--primary); 
    box-shadow: 0 0 0 3px rgba(30,110,181,0.15); 
}

.modern-modal-footer {
    display: flex; justify-content: space-between; align-items: center; gap: 12px;
    margin-top: 24px; padding-top: 16px; border-top: 1px solid #f1f5f9;
}

.btn-modal-batal {
    background: #f1f5f9; color: #475569; font-weight: 600; font-size: 0.875rem;
    border: none; padding: 11px 24px; border-radius: 8px; cursor: pointer; transition: 0.2s;
    font-family: 'Plus Jakarta Sans', sans-serif !important;
}
.btn-modal-batal:hover { background: #e2e8f0; color: #0f172a; }

.btn-modal-simpan {
    background: var(--primary); color: #ffffff; font-weight: 600; font-size: 0.875rem;
    border: none; padding: 11px 24px; border-radius: 8px; cursor: pointer; transition: 0.2s;
    display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 6px -1px rgba(30,110,181,0.3);
    font-family: 'Plus Jakarta Sans', sans-serif !important;
}
.btn-modal-simpan:hover { background: var(--primary-dark); transform: translateY(-1px); box-shadow: 0 6px 8px -1px rgba(30,110,181,0.4); }

/* PERBAIKAN RESPONSIF TABEL DI HP (AGAR TIDAK TERPOTONG) */
.table-wrapper {
    width: 100% !important;
    overflow-x: auto !important;
    -webkit-overflow-scrolling: touch !important;
    border-radius: 12px;
    background: #fff;
    border: 1px solid #e2e8f0;
    box-shadow: var(--shadow-sm);
    margin-bottom: 20px;
}
.table-wrapper table {
    width: 100% !important;
    min-width: 650px !important; /* Pas di layar HP tanpa meluber berlebihan */
    border-collapse: collapse !important;
}

/* KARTU SUMMARY RESPONSIF & RATA TENGAH SEMPURNA */
.summary-container { display: flex; align-items: center; justify-content: center !important; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; }
.summary-cards-wrapper { display: flex; gap: 12px; flex-wrap: wrap; width: 100%; justify-content: center !important; }
.summary-bar-item {
  background: var(--bg-card); padding: 12px 18px; border-radius: var(--radius);
  box-shadow: var(--shadow-sm); display: flex; align-items: center !important; gap: 12px; flex: 1; min-width: 220px;
}

.t-avatar { width: 32px; height: 32px; background: var(--primary); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; font-weight: bold; overflow: hidden; }
.t-avatar img { width: 100%; height: 100%; object-fit: cover; }
.d-avatar { width: 45px; height: 45px; background: var(--primary); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: bold; overflow: hidden; }
.d-avatar img { width: 100%; height: 100%; object-fit: cover; }

.laporan-action-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  width: 100%;
}

@media(max-width: 640px) {
  .summary-container { flex-direction: column !important; align-items: stretch !important; gap: 12px !important; }
  .summary-cards-wrapper { flex-direction: column !important; width: 100% !important; align-items: center !important; }
  .summary-bar-item { width: 100% !important; align-items: center !important; justify-content: center !important; text-align: center !important; }

  .laporan-action-row {
    flex-direction: row !important;
    gap: 8px !important;
  }
  .laporan-action-row .btn {
    flex: 1 !important;
    justify-content: center !important;
    padding: 10px 12px !important;
    font-size: 0.8rem !important;
    white-space: nowrap !important;
  }
}

@media (max-width: 768px) {
    html, body { overflow-x: hidden !important; max-width: 100vw !important; }
    .admin-wrapper { display: block !important; width: 100% !important; overflow-x: hidden !important; }
    .admin-main { width: 100% !important; margin-left: 0 !important; padding: 0 !important; box-sizing: border-box !important; }
    .admin-content { width: 100% !important; padding: 12px !important; box-sizing: border-box !important; margin: 0 !important; }
    .topbar { width: 100% !important; box-sizing: border-box !important; padding: 0 12px !important; }
    .t-name, .topbar-date, .fa-chevron-down { display: none !important; }
    .table th, .table td { padding: 10px 8px !important; font-size: 0.8rem !important; }
}
</style>
</head>
<body>
<div class="admin-wrapper">
<?php include '../includes/partials/sidebar-admin.php'; ?>
<div class="admin-main">
  <div class="topbar">
    <div style="display:flex; align-items:center; gap:12px;">
      <div id="sidebarToggle" style="cursor:pointer; font-size:1.1rem; color:var(--text-muted);"><i class="fas fa-bars"></i></div>
      <div class="breadcrumb">
        <span class="bc-item"><i class="fas fa-home"></i></span>
        <span class="bc-sep"><i class="fas fa-chevron-right"></i></span>
        <span class="bc-item active">Laporan Keuangan</span>
      </div>
    </div>
    <div class="topbar-right">
      <div class="topbar-date"><i class="fas fa-calendar-alt me-1" style="margin-right: 4px;"></i> <?= formatTanggalIndo(date('Y-m-d')) ?></div>
      
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

  <div class="admin-content">
    <?php if ($alert): ?>
    <div class="alert alert-<?= $alert['type'] ?>"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($alert['message']) ?></div>
    <?php endif; ?>
    
    <div class="page-header" style="position: relative; margin-bottom: 14px;">
      <div class="laporan-action-row">
        <!-- TOMBOL POP-UP FILTER LAPORAN -->
        <button type="button" class="btn" onclick="openFilterModal()" style="background:#ffffff; color:var(--text-primary); border:1px solid #cbd5e1; box-shadow:0 1px 2px rgba(0,0,0,0.05); display:inline-flex; align-items:center; gap:8px;">
          <i class="fas fa-filter" style="color:var(--primary);"></i> Filter Laporan
        </button>
        
        <a href="?<?= http_build_query(array_merge($_GET,['print'=>1])) ?>" target="_blank" class="btn" style="background-color: #d4af37; color: white; border: none; display:inline-flex; align-items:center; gap:8px;">
          <i class="fas fa-print"></i> Cetak / Ekspor PDF
        </a>
      </div>
    </div>

    <!-- Status Tag Filter Aktif -->
    <div style="margin-bottom:16px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
      <span class="badge badge-primary" style="padding:6px 14px; font-size:0.8rem; font-weight:600;">
        <i class="fas fa-calendar-alt me-1" style="margin-right: 4px;"></i> Periode: <?= $periode_label ?>
      </span>
      <span class="badge badge-info" style="padding:6px 14px; font-size:0.8rem; font-weight:600;">
        <i class="fas fa-list me-1" style="margin-right: 4px;"></i> Jenis: <?= ucfirst($filter_jenis) ?>
      </span>
    </div>

    <!-- Summary Cards (Rata Tengah Sempurna) -->
    <div class="summary-container">
      <div class="summary-cards-wrapper">
        <div class="summary-bar-item" style="border-left:3px solid var(--success)">
          <i class="fas fa-arrow-down" style="color:var(--success);font-size:1.3rem"></i>
          <div>
            <div style="font-size:.7rem;color:#0f172a;font-weight:700;">Total Pemasukan</div>
            <div style="font-weight:800;color:var(--success);font-size:.95rem"><?= formatRupiah($sum['total_masuk']) ?></div>
          </div>
        </div>
        <div class="summary-bar-item" style="border-left:3px solid var(--danger)">
          <i class="fas fa-arrow-up" style="color:var(--danger);font-size:1.3rem"></i>
          <div>
            <div style="font-size:.7rem;color:#0f172a;font-weight:700;">Total Pengeluaran</div>
            <div style="font-weight:800;color:var(--danger);font-size:.95rem"><?= formatRupiah($sum['total_keluar']) ?></div>
          </div>
        </div>
        <div class="summary-bar-item" style="border-left:3px solid var(--info)">
          <i class="fas fa-wallet" style="color:var(--info);font-size:1.3rem"></i>
          <div>
            <div style="font-size:.7rem;color:#0f172a;font-weight:700;">Saldo Periode</div>
            <div style="font-weight:800;color:var(--info);font-size:.95rem"><?= formatRupiah($sum['total_masuk'] - $sum['total_keluar']) ?></div>
          </div>
        </div>
        <div class="summary-bar-item" style="border-left:3px solid #64748b">
          <i class="fas fa-list" style="color:#64748b;font-size:1.3rem"></i>
          <div>
            <div style="font-size:.7rem;color:#0f172a;font-weight:700;">Total Transaksi</div>
            <div style="font-weight:800;color:#64748b;font-size:.95rem"><?= number_format($sum['total_trx']) ?> transaksi</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabel Bersih & Responsif -->
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
            <td class="text-left" style="white-space: nowrap;"><?= formatTanggalIndo($r['tanggal']) ?></td>
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

<!-- ========================================================= -->
<!-- MODAL POP-UP FILTER LAPORAN BAHASA INDONESIA -->
<!-- ========================================================= -->
<div class="modern-modal-overlay" id="filterModal">
  <div class="modern-modal-box">
    <div class="modern-modal-header">
      <div class="modern-modal-title">
        <i class="fas fa-filter"></i> Filter Laporan Keuangan
      </div>
      <button class="modern-modal-close" onclick="closeFilterModal()"><i class="fas fa-times"></i></button>
    </div>
    
    <div class="modern-modal-body">
      <form method="GET" id="filterFormModal">
        <div class="form-group mb-3">
          <label class="form-label">Pilih Periode Laporan</label>
          <select name="periode" id="modalSelectPeriode" class="form-control form-select" onchange="togglePeriodeInputs(this.value)">
            <option value="semua"  <?= $filter_periode=='semua'?'selected':'' ?>>Semua Periode</option>
            <option value="minggu" <?= $filter_periode=='minggu'?'selected':'' ?>>7 Hari Terakhir</option>
            <option value="bulan"  <?= $filter_periode=='bulan'?'selected':'' ?>>Per Bulan</option>
            <option value="tahun"  <?= $filter_periode=='tahun'?'selected':'' ?>>Per Tahun</option>
          </select>
        </div>

        <!-- INPUT DUA SELECT UNTUK BULAN & TAHUN REALTIME -->
        <div class="form-group mb-3" id="inputBulanBox" style="display: <?= $filter_periode=='bulan'?'block':'none' ?>;">
          <label class="form-label">Pilih Bulan & Tahun</label>
          <div style="display:grid; grid-template-columns: 2fr 1fr; gap:10px;">
            <select name="bln_num" class="form-control form-select">
              <?php 
              for ($m=1; $m<=12; $m++): 
                $m_str = sprintf('%02d', $m);
              ?>
                <option value="<?= $m_str ?>" <?= $filter_bln_num==$m_str?'selected':'' ?>><?= formatBulanIndo($m) ?></option>
              <?php endfor; ?>
            </select>
            <select name="thn_num" class="form-control form-select">
              <?php for ($y=2027; $y>=2026; $y--): ?>
                <option value="<?= $y ?>" <?= $filter_thn_num==$y?'selected':'' ?>><?= $y ?></option>
              <?php endfor; ?>
            </select>
          </div>
        </div>

        <!-- INPUT TAHUN REALTIME -->
        <div class="form-group mb-3" id="inputTahunBox" style="display: <?= $filter_periode=='tahun'?'block':'none' ?>;">
          <label class="form-label">Pilih Tahun</label>
          <select name="tahun" class="form-control form-select">
            <?php for ($y=2027; $y>=2026; $y--): ?>
              <option value="<?= $y ?>" <?= $filter_tahun==$y?'selected':'' ?>><?= $y ?></option>
            <?php endfor; ?>
          </select>
        </div>

        <!-- PILIHAN JENIS KAS -->
        <div class="form-group mb-3">
          <label class="form-label">Pilih Jenis Kas</label>
          <select name="jenis" class="form-control form-select">
            <option value="semua"  <?= $filter_jenis=='semua'?'selected':'' ?>>Semua Jenis (Masuk & Keluar)</option>
            <option value="masuk"  <?= $filter_jenis=='masuk'?'selected':'' ?>>Kas Masuk</option>
            <option value="keluar" <?= $filter_jenis=='keluar'?'selected':'' ?>>Kas Keluar</option>
          </select>
        </div>

        <!-- Footer Modal -->
        <div class="modern-modal-footer">
          <button type="button" class="btn-modal-batal" onclick="closeFilterModal()">Batal</button>
          <button type="submit" class="btn-modal-simpan">
            <i class="fas fa-search"></i> Terapkan Filter
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Logic Dropdown User Topbar
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

// Logic Pop-up Modal Filter
function openFilterModal() {
  document.getElementById('filterModal').classList.add('active');
}
function closeFilterModal() {
  document.getElementById('filterModal').classList.remove('active');
}
document.getElementById('filterModal').addEventListener('click', function(e) {
  if (e.target === this) closeFilterModal();
});

// Toggle Tampilan Input Dinamis di Modal
function togglePeriodeInputs(val) {
  document.getElementById('inputBulanBox').style.display  = (val === 'bulan')  ? 'block' : 'none';
  document.getElementById('inputTahunBox').style.display  = (val === 'tahun')  ? 'block' : 'none';
}

// Sidebar toggle
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