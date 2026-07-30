<?php
require_once '../includes/config.php';
requireLogin();

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
for ($i = 5; $i >= 0; $i--) {
    $b = date('Y-m', strtotime("-$i month"));
    $chart_labels[] = date('M Y', strtotime("-$i month"));
    $chart_masuk[]  = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='masuk' AND deleted_at IS NULL AND DATE_FORMAT(tanggal,'%Y-%m')='$b'")->fetch_assoc()['t'];
    $chart_keluar[] = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='keluar' AND deleted_at IS NULL AND DATE_FORMAT(tanggal,'%Y-%m')='$b'")->fetch_assoc()['t'];
}

// Line chart saldo kumulatif 12 bulan
$line_labels = $line_saldo = [];
$kum = 0;
for ($m = 1; $m <= 12; $m++) {
    $b  = sprintf('%04d-%02d', $tahun, $m);
    $mk = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='masuk' AND deleted_at IS NULL AND DATE_FORMAT(tanggal,'%Y-%m')='$b'")->fetch_assoc()['t'];
    $kl = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='keluar' AND deleted_at IS NULL AND DATE_FORMAT(tanggal,'%Y-%m')='$b'")->fetch_assoc()['t'];
    $kum += ($mk - $kl);
    $line_labels[] = date('M', mktime(0,0,0,$m,1));
    $line_saldo[]  = $kum;
}

// Pie pemasukan bulan ini
$pi_q = $conn->query("SELECT k.nama_kategori, COALESCE(SUM(t.jumlah),0) as total FROM transaksi t JOIN kategori k ON t.kategori_id=k.id WHERE t.jenis='masuk' AND t.deleted_at IS NULL AND YEAR(t.tanggal)=$tahun GROUP BY k.id ORDER BY total DESC LIMIT 8");
$pil = $pid = [];
while ($r = $pi_q->fetch_assoc()) { $pil[] = $r['nama_kategori']; $pid[] = (float)$r['total']; }

// Pie pengeluaran tahun ini
$pe_q = $conn->query("SELECT k.nama_kategori, COALESCE(SUM(t.jumlah),0) as total FROM transaksi t JOIN kategori k ON t.kategori_id=k.id WHERE t.jenis='keluar' AND t.deleted_at IS NULL AND YEAR(t.tanggal)=$tahun GROUP BY k.id ORDER BY total DESC LIMIT 8");
$pel = $ped = [];
while ($r = $pe_q->fetch_assoc()) { $pel[] = $r['nama_kategori']; $ped[] = (float)$r['total']; }

// Transaksi terbaru
$trx_recent = $conn->query("SELECT t.*, k.nama_kategori FROM transaksi t JOIN kategori k ON t.kategori_id=k.id WHERE t.deleted_at IS NULL ORDER BY t.tanggal DESC, t.id DESC LIMIT 7");

$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
<title>Dashboard – <?= APP_NAME ?></title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css?v=2026">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
.grafik-slide{display:none;animation:slideInChart .5s cubic-bezier(.4,0,.2,1);}
.grafik-slide.active{display:block;}
@keyframes slideInChart{from{opacity:0;transform:translateX(40px) scale(.97)}to{opacity:1;transform:translateX(0) scale(1)}}
.slide-progress-wrap{height:3px;background:var(--border-light);border-radius:99px;overflow:hidden;margin-bottom:16px}
.slide-progress-bar{height:100%;background:var(--primary);border-radius:99px;width:0%;transition:width linear}
.slide-dot{width:9px;height:9px;border-radius:50%;background:var(--border);cursor:pointer;transition:var(--transition);border:none}
.slide-dot.active{background:var(--primary);width:24px;border-radius:99px}
.slide-nav-btn{width:34px;height:34px;border-radius:50%;background:var(--bg-main);border:1.5px solid var(--border);color:var(--text-secondary);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:var(--transition-fast);font-size:.8rem}
.slide-nav-btn:hover{background:var(--primary);border-color:var(--primary);color:#fff}
.pie-legend-item{display:flex;align-items:center;gap:8px}
.pie-legend-dot{width:12px;height:12px;border-radius:3px;flex-shrink:0;display:inline-block}

/* =========================================================
   TAMBAHAN CSS RESPONSIVE AGRESIF UNTUK HP (SUPER REFINED)
   ========================================================= */
.table-responsive {
    display: block !important;
    width: 100% !important;
    overflow-x: auto !important;
    -webkit-overflow-scrolling: touch !important;
}

@media (max-width: 768px) {
    /* Mencegah layar geser kanan-kiri yang tidak disengaja */
    html, body {
        overflow-x: hidden !important;
        max-width: 100vw !important;
    }

    .admin-wrapper {
        display: block !important;
        width: 100% !important;
        overflow-x: hidden !important;
    }
    
    .admin-main {
        width: 100% !important;
        margin-left: 0 !important;
        padding: 0 !important;
        box-sizing: border-box !important;
    }

    /* Merapikan padding utama agar lega */
    .admin-content {
        width: 100% !important;
        padding: 12px !important; 
        box-sizing: border-box !important;
        margin: 0 !important;
    }

    /* Tipografi hirarki header */
    .page-title {
        font-size: 1.35rem !important;
        margin-bottom: 4px !important;
    }
    .page-subtitle {
        font-size: 0.85rem !important;
        margin-bottom: 15px !important;
    }

    .topbar {
        width: 100% !important;
        box-sizing: border-box !important;
        padding: 12px 15px !important;
    }

    /* 4 Kotak Stat Atas dijadikan 2 Kolom (Kotak kecil rapi) */
    .grid-4 {
        display: grid !important;
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 12px !important;
        width: 100% !important;
    }
    
    .stat-card {
        padding: 15px 12px !important;
        border-radius: 12px !important;
    }
    .stat-label { font-size: 0.72rem !important; }
    .stat-value { font-size: 1.05rem !important; margin: 4px 0 !important; }
    
    /* Layout Banner Ringkasan Bulan Ini (Yang bermasalah di SS) */
    .banner-header {
        flex-direction: column !important;
        text-align: center !important;
        gap: 15px !important;
    }
    .banner-stats {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important; /* Kiri Kanan */
        gap: 15px !important;
        width: 100% !important;
    }
    .banner-sep {
        display: none !important; /* Hapus garis vertikal yang nyasar */
    }
    .selisih-box {
        grid-column: span 2 !important; /* Paksa menempati 2 kolom di bawahnya */
        padding-top: 15px !important;
        border-top: 1px dashed rgba(255,255,255,0.3) !important; /* Garis horizontal baru */
    }
    .banner-actions {
        justify-content: center !important;
        width: 100% !important;
        margin-top: 5px !important;
    }

    /* Layout Card & Table */
    .card {
        width: 100% !important;
        box-sizing: border-box !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
        border-radius: 12px !important; 
    }
    
    .table-responsive .table {
        min-width: 650px !important; 
    }
    
    .table th, .table td {
        padding: 10px 8px !important;
        font-size: 0.85rem !important;
    }
    
    .card-header {
        display: flex !important;
        flex-direction: row !important;
        justify-content: space-between !important;
        align-items: center !important;
        gap: 8px !important;
        padding: 12px 15px !important;
    }
    
    .t-name {
        display: none !important;
    }
    
    .grafik-slide > div {
        height: 230px !important;
    }
    .grafik-slide > div[style*="display:flex"] {
        flex-direction: column !important;
        gap: 12px !important;
    }
    .slide-nav-btn { transform: scale(1.05); margin: 0 2px; }
}
/* ========================================================= */
</style>
</head>
<body>
<div class="admin-wrapper">
<?php include '../includes/partials/sidebar-admin.php'; ?>
<div class="admin-main">

  <!-- TOPBAR -->
  <div class="topbar">
    <div class="topbar-left">
      <div class="topbar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></div>
      <div class="breadcrumb">
        <span class="bc-item"><i class="fas fa-home"></i></span>
        <span class="bc-sep"><i class="fas fa-chevron-right"></i></span>
        <span class="bc-item active">Dashboard</span>
      </div>
    </div>
    <div class="topbar-right">
      <div class="topbar-date"><i class="fas fa-calendar-alt"></i> <?= date('d F Y') ?></div>
      <div class="topbar-user">
        <div class="t-avatar"><?= strtoupper(substr($_SESSION['admin_nama'],0,1)) ?></div>
        <div class="t-name"><?= htmlspecialchars($_SESSION['admin_nama']) ?></div>
      </div>
    </div>
  </div>

  <div class="admin-content">
    <?php if ($alert): ?>
    <div class="alert alert-<?= $alert['type'] ?>">
      <i class="fas fa-<?= $alert['type']=='success'?'check-circle':'exclamation-circle' ?>"></i>
      <?= htmlspecialchars($alert['message']) ?>
    </div>
    <?php endif; ?>

    <div class="page-header">
      <h1 class="page-title"><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
      <p class="page-subtitle">Ringkasan keuangan kas <?= MASJID_NAME ?> – <?= date('F Y') ?></p>
    </div>

    <!-- STAT CARDS -->
    <div class="grid-4 mb-3">
      <div class="stat-card gold animate-fadeIn delay-1">
        <div class="stat-icon"><i class="fas fa-wallet"></i></div>
        <div class="stat-label">Saldo Kas</div>
        <div class="stat-value"><?= formatRupiah($saldo) ?></div>
        <div class="stat-sub"><i class="fas fa-sync up"></i> Real-time</div>
      </div>
      <div class="stat-card green animate-fadeIn delay-2">
        <div class="stat-icon"><i class="fas fa-arrow-down"></i></div>
        <div class="stat-label">Total Pemasukan</div>
        <div class="stat-value"><?= formatRupiah($total_masuk) ?></div>
        <div class="stat-sub"><i class="fas fa-calendar-alt up"></i> Real-time</div>
      </div>
      <div class="stat-card red animate-fadeIn delay-3">
        <div class="stat-icon"><i class="fas fa-arrow-up"></i></div>
        <div class="stat-label">Total Pengeluaran</div>
        <div class="stat-value"><?= formatRupiah($total_keluar) ?></div>
        <div class="stat-sub"><i class="fas fa-calendar-alt down"></i> Real-time</div>
      </div>
      <div class="stat-card blue animate-fadeIn delay-4">
        <div class="stat-icon"><i class="fas fa-exchange-alt"></i></div>
        <div class="stat-label">Total Transaksi</div>
        <div class="stat-value"><?= number_format($total_trx) ?></div>
        <div class="stat-sub"><i class="fas fa-list"></i> <?= $trx_bln ?> Real-time</div>
      </div>
    </div>

    <!-- BANNER BULAN INI -->
    <div class="card mb-3 animate-fadeIn" style="background:linear-gradient(135deg,var(--primary),var(--accent));color:#fff;border:none;">
      <div class="card-body" style="padding:22px 28px">
        <div class="banner-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px">
          <div>
            <div style="font-size:.75rem;opacity:.75;text-transform:uppercase;letter-spacing:.5px">Ringkasan Bulan Ini</div>
            <!-- MENAMBAHKAN TANGGAL HARI INI SECARA LENGKAP -->
            <div style="font-size:1.1rem;font-weight:800;margin-top:3px"><?= date('d F Y') ?></div>
          </div>
          <!-- STRUKTUR CLASS BARU UNTUK CSS OVERRIDE -->
          <div class="banner-stats" style="display:flex;gap:28px;flex-wrap:wrap;align-items:center;">
            <div style="text-align:center"><div style="font-size:1.3rem;font-weight:800"><?= formatRupiah($masuk_bln) ?></div><div style="font-size:.72rem;opacity:.75"><i class="fas fa-arrow-down"></i> Pemasukan</div></div>
            <div class="banner-sep" style="width:1px;height:40px;background:rgba(255,255,255,.2)"></div>
            <div style="text-align:center"><div style="font-size:1.3rem;font-weight:800"><?= formatRupiah($keluar_bln) ?></div><div style="font-size:.72rem;opacity:.75"><i class="fas fa-arrow-up"></i> Pengeluaran</div></div>
            <div class="banner-sep" style="width:1px;height:40px;background:rgba(255,255,255,.2)"></div>
            <div class="selisih-box" style="text-align:center"><div style="font-size:1.3rem;font-weight:800;<?= ($masuk_bln-$keluar_bln)<0?'color:#fca5a5':'' ?>"><?= formatRupiah($masuk_bln-$keluar_bln) ?></div><div style="font-size:.72rem;opacity:.75"><i class="fas fa-balance-scale"></i> Selisih</div></div>
          </div>
          <div class="banner-actions" style="display:flex;gap:8px">
            <a href="kas-masuk.php" class="btn btn-sm" style="background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.3)"><i class="fas fa-plus"></i> Kas Masuk</a>
            <a href="kas-keluar.php" class="btn btn-sm" style="background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.3)"><i class="fas fa-minus"></i> Kas Keluar</a>
          </div>
        </div>
      </div>
    </div>

    <!-- GRAFIK SLIDESHOW LENGKAP -->
    <div class="card mb-3 animate-fadeIn">
      <div class="card-header" style="padding-bottom:16px;border-bottom:1px solid var(--border-light)">
        <div class="card-title"><i class="fas fa-chart-bar"></i> Visualisasi Data Keuangan</div>
      </div>
      <div class="card-body">

        <!-- Progress bar -->
        <div class="slide-progress-wrap"><div class="slide-progress-bar" id="slideProgress"></div></div>

        <!-- Slide Nav -->
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:18px">
          <div>
            <div style="display:inline-flex;align-items:center;gap:7px;background:var(--primary);color:#fff;padding:5px 14px;border-radius:99px;font-size:.78rem;font-weight:700">
              <i id="slideIcon" class="fas fa-chart-bar"></i> <span id="slideTitle">Pemasukan vs Pengeluaran</span>
            </div>
            <div style="font-size:.75rem;color:var(--text-muted);margin-top:6px" id="slideDesc">6 bulan terakhir</div>
          </div>
          <div style="display:flex;align-items:center;gap:10px">
            <span style="font-size:.78rem;color:var(--text-muted)" id="slideCounter">1 / 4</span>
            <div style="display:flex;gap:6px">
              <button class="slide-nav-btn" onclick="changeSlide(-1)"><i class="fas fa-chevron-left"></i></button>
              <button class="slide-nav-btn" onclick="togglePause()" id="btnPause"><i class="fas fa-pause" id="pauseIcon"></i></button>
              <button class="slide-nav-btn" onclick="changeSlide(1)"><i class="fas fa-chevron-right"></i></button>
            </div>
          </div>
        </div>

        <!-- Slide 1: Bar -->
        <div class="grafik-slide active" id="slide-0">
          <div style="position:relative;height:280px"><canvas id="barChart"></canvas></div>
        </div>
        <!-- Slide 2: Line -->
        <div class="grafik-slide" id="slide-1">
          <div style="position:relative;height:280px"><canvas id="lineChart"></canvas></div>
        </div>
        <!-- Slide 3: Pie Masuk -->
        <div class="grafik-slide" id="slide-2">
          <?php if (count($pid)): ?>
          <div style="display:flex;align-items:center;justify-content:center;gap:32px;flex-wrap:wrap;padding:8px 0">
            <div style="position:relative;width:230px;height:230px;flex-shrink:0"><canvas id="pieIncome"></canvas></div>
            <div id="legIncome" style="display:flex;flex-direction:column;gap:9px;max-width:280px"></div>
          </div>
          <?php else: ?>
          <div class="empty-state"><div class="es-icon"><i class="fas fa-chart-pie"></i></div><h3>Belum ada data pemasukan <?= $tahun ?></h3></div>
          <?php endif; ?>
        </div>
        <!-- Slide 4: Pie Keluar -->
        <div class="grafik-slide" id="slide-3">
          <?php if (count($ped)): ?>
          <div style="display:flex;align-items:center;justify-content:center;gap:32px;flex-wrap:wrap;padding:8px 0">
            <div style="position:relative;width:230px;height:230px;flex-shrink:0"><canvas id="pieExpense"></canvas></div>
            <div id="legExpense" style="display:flex;flex-direction:column;gap:9px;max-width:280px"></div>
          </div>
          <?php else: ?>
          <div class="empty-state"><div class="es-icon"><i class="fas fa-chart-pie"></i></div><h3>Belum ada data pengeluaran <?= $tahun ?></h3></div>
          <?php endif; ?>
        </div>

        <!-- Dots -->
        <div style="display:flex;justify-content:center;gap:8px;margin-top:20px">
          <button class="slide-dot active" onclick="goToSlide(0)"></button>
          <button class="slide-dot" onclick="goToSlide(1)"></button>
          <button class="slide-dot" onclick="goToSlide(2)"></button>
          <button class="slide-dot" onclick="goToSlide(3)"></button>
        </div>
      </div>
    </div>

    <!-- TRANSAKSI TERBARU -->
    <div class="card animate-fadeIn">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-history"></i> Transaksi Terbaru</div>
        <a href="laporan.php" class="btn btn-ghost btn-sm"><i class="fas fa-list"></i> Lihat Semua</a>
      </div>
      <div class="card-body" style="padding:0">
        <!-- TABEL RESPONSIVE -->
        <div class="table-responsive">
            <table class="table">
              <thead>
                <tr><th>Tanggal</th><th>Keterangan</th><th>Kategori</th><th>Jenis</th><th class="text-right">Jumlah</th><th>Aksi</th></tr>
              </thead>
              <tbody>
                <?php while ($r = $trx_recent->fetch_assoc()): ?>
                <tr>
                  <td style="white-space:nowrap"><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
                  <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($r['keterangan']) ?></td>
                  <td><span class="badge badge-primary" style="white-space:nowrap;"><?= htmlspecialchars($r['nama_kategori']) ?></span></td>
                  <td><?= $r['jenis']=='masuk' ? '<span class="badge badge-success" style="white-space:nowrap;"><i class="fas fa-arrow-down"></i> Masuk</span>' : '<span class="badge badge-danger" style="white-space:nowrap;"><i class="fas fa-arrow-up"></i> Keluar</span>' ?></td>
                  <td class="text-right fw-600 <?= $r['jenis']=='masuk'?'text-success':'text-danger' ?>" style="white-space:nowrap;"><?= ($r['jenis']=='masuk'?'+':'-').formatRupiah($r['jumlah']) ?></td>
                  <td><a href="<?= $r['jenis']=='masuk'?'kas-masuk':'kas-keluar' ?>.php?edit=<?= $r['id'] ?>" class="btn btn-ghost btn-icon btn-sm" data-tooltip="Edit"><i class="fas fa-edit"></i></a></td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
        </div>
      </div>
    </div>

  </div><!-- /admin-content -->
</div><!-- /admin-main -->
</div><!-- /admin-wrapper -->

<script>
Chart.defaults.font.family = "'Poppins',sans-serif";
Chart.defaults.color = '#6b7280';
Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15,61,38,.93)';
Chart.defaults.plugins.tooltip.titleColor = '#fff';
Chart.defaults.plugins.tooltip.bodyColor   = 'rgba(255,255,255,.85)';
Chart.defaults.plugins.tooltip.padding     = 12;
Chart.defaults.plugins.tooltip.cornerRadius = 8;

const fmtRp = v => 'Rp ' + new Intl.NumberFormat('id-ID').format(v);
const CG = ['#1a7a4a','#22a05e','#c9a84c','#3b82f6','#f59e0b','#8b5cf6','#ec4899','#14b8a6'];
const CR = ['#ef4444','#f87171','#dc2626','#b91c1c','#fca5a5','#ff8080','#fecaca','#c53030'];

// Bar Chart
new Chart(document.getElementById('barChart').getContext('2d'), {
  type:'bar',
  data:{labels:<?= json_encode($chart_labels) ?>,datasets:[
    {label:'Pemasukan',   data:<?= json_encode($chart_masuk) ?>,  backgroundColor:'rgba(26,122,74,.85)',borderRadius:7,borderSkipped:false},
    {label:'Pengeluaran', data:<?= json_encode($chart_keluar) ?>, backgroundColor:'rgba(239,68,68,.75)', borderRadius:7,borderSkipped:false}
  ]},
  options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},
    plugins:{legend:{position:'bottom',labels:{padding:18,font:{size:11},usePointStyle:true}},
      tooltip:{callbacks:{label:c=>` ${c.dataset.label}: ${fmtRp(c.raw)}`}}},
    scales:{x:{grid:{display:false}},y:{grid:{color:'rgba(0,0,0,.05)'},ticks:{callback:v=>fmtRp(v)}}},
    animation:{duration:900,easing:'easeInOutQuart'}
  }
});

// Line Chart
new Chart(document.getElementById('lineChart').getContext('2d'), {
  type:'line',
  data:{labels:<?= json_encode($line_labels) ?>,datasets:[{
    label:'Saldo Kumulatif',data:<?= json_encode($line_saldo) ?>,
    borderColor:'#1a7a4a',backgroundColor:'rgba(26,122,74,.08)',
    borderWidth:3,fill:true,tension:.4,
    pointBackgroundColor:'#1a7a4a',pointBorderColor:'#fff',pointBorderWidth:2,pointRadius:5,pointHoverRadius:8
  }]},
  options:{responsive:true,maintainAspectRatio:false,
    plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>` Saldo: ${fmtRp(c.raw)}`}}},
    scales:{x:{grid:{display:false}},y:{grid:{color:'rgba(0,0,0,.05)'},ticks:{callback:v=>fmtRp(v)}}},
    animation:{duration:900}
  }
});

// Pie Helper
function buildPie(cid, lid, labels, data, colors) {
  if (!labels.length) return;
  new Chart(document.getElementById(cid).getContext('2d'), {
    type:'doughnut',
    data:{labels,datasets:[{data,backgroundColor:colors,borderWidth:3,borderColor:'#fff',hoverOffset:12}]},
    options:{responsive:true,maintainAspectRatio:true,cutout:'58%',
      plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>` ${c.label}: ${fmtRp(c.raw)}`}}},
      animation:{duration:900,animateRotate:true,animateScale:true}
    }
  });
  const leg = document.getElementById(lid), total = data.reduce((a,b)=>a+b,0);
  labels.forEach((l,i) => {
    const pct = total>0?((data[i]/total)*100).toFixed(1):0;
    leg.innerHTML += `<div class="pie-legend-item"><span class="pie-legend-dot" style="background:${colors[i]}"></span><div><div style="font-size:.8rem;font-weight:600;color:var(--text-primary)">${l}</div><div style="font-size:.72rem;color:var(--text-muted)">${fmtRp(data[i])} &bull; ${pct}%</div></div></div>`;
  });
}
buildPie('pieIncome',  'legIncome',  <?= json_encode($pil) ?>, <?= json_encode($pid) ?>, CG);
buildPie('pieExpense', 'legExpense', <?= json_encode($pel) ?>, <?= json_encode($ped) ?>, CR);

// Slideshow
const DURATION = 10000;
const slides   = document.querySelectorAll('.grafik-slide');
const dots     = document.querySelectorAll('.slide-dot');
const progress = document.getElementById('slideProgress');
const SLIDE_INFO = [
  {icon:'fas fa-chart-bar',  title:'Pemasukan vs Pengeluaran', desc:'Perbandingan 6 bulan terakhir'},
  {icon:'fas fa-chart-line', title:'Saldo Kumulatif',           desc:'Tren saldo sepanjang tahun '+<?= $tahun ?>},
  {icon:'fas fa-chart-pie',  title:'Proporsi Pemasukan',       desc:'Distribusi pemasukan tahun '+<?= $tahun ?>},
  {icon:'fas fa-chart-pie',  title:'Proporsi Pengeluaran',     desc:'Distribusi pengeluaran tahun '+<?= $tahun ?>},
];
let cur=0, paused=false, timer=null;

function goToSlide(n) {
  slides[cur].classList.remove('active'); dots[cur].classList.remove('active');
  cur = (n+slides.length)%slides.length;
  slides[cur].classList.add('active'); dots[cur].classList.add('active');
  const info = SLIDE_INFO[cur];
  document.getElementById('slideIcon').className  = info.icon;
  document.getElementById('slideTitle').textContent = info.title;
  document.getElementById('slideDesc').textContent  = info.desc;
  document.getElementById('slideCounter').textContent = `${cur+1} / ${slides.length}`;
  resetProgress();
}
function changeSlide(dir) { goToSlide(cur+dir); if(!paused) startAuto(); }
function startAuto() { clearTimeout(timer); timer=setTimeout(()=>{ if(!paused){goToSlide(cur+1);startAuto();} },DURATION); }
function resetProgress() {
  progress.style.transition='none'; progress.style.width='0%';
  if(!paused) setTimeout(()=>{ progress.style.transition=`width ${DURATION}ms linear`; progress.style.width='100%'; },30);
}
function togglePause() {
  paused=!paused;
  const ico=document.getElementById('pauseIcon');
  if(paused){ ico.className='fas fa-play'; clearTimeout(timer); const w=getComputedStyle(progress).width; progress.style.transition='none'; progress.style.width=w; }
  else { ico.className='fas fa-pause'; startAuto(); resetProgress(); }
}
startAuto(); resetProgress();

// Sidebar toggle
const sidebar=document.getElementById('adminSidebar');
const overlay=document.getElementById('sidebarOverlay');
document.getElementById('sidebarToggle').addEventListener('click',()=>{ sidebar.classList.toggle('open'); overlay.classList.toggle('active'); });
if(overlay){ overlay.addEventListener('click',()=>{ sidebar.classList.remove('open'); overlay.classList.remove('active'); }); }
</script>
</body>
</html>