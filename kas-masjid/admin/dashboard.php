<?php
require_once '../includes/config.php';
requireLogin();

// ---- Summary Cards ----
$total_masuk  = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='masuk'")->fetch_assoc()['t'];
$total_keluar = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='keluar'")->fetch_assoc()['t'];
$saldo        = $total_masuk - $total_keluar;
$total_trx    = (int)$conn->query("SELECT COUNT(*) as t FROM transaksi")->fetch_assoc()['t'];

// Bulan ini
$bln = date('Y-m');
$masuk_bln  = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='masuk'  AND DATE_FORMAT(tanggal,'%Y-%m')='$bln'")->fetch_assoc()['t'];
$keluar_bln = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='keluar' AND DATE_FORMAT(tanggal,'%Y-%m')='$bln'")->fetch_assoc()['t'];
$trx_bln    = (int)$conn->query("SELECT COUNT(*) as t FROM transaksi WHERE DATE_FORMAT(tanggal,'%Y-%m')='$bln'")->fetch_assoc()['t'];

// ---- Chart: 6 bulan terakhir ----
$chart_labels = $chart_masuk = $chart_keluar = [];
for ($i = 5; $i >= 0; $i--) {
    $b = date('Y-m', strtotime("-$i month"));
    $chart_labels[] = date('M Y', strtotime("-$i month"));
    $chart_masuk[]  = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='masuk'  AND DATE_FORMAT(tanggal,'%Y-%m')='$b'")->fetch_assoc()['t'];
    $chart_keluar[] = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='keluar' AND DATE_FORMAT(tanggal,'%Y-%m')='$b'")->fetch_assoc()['t'];
}

// ---- Pie: Kategori pemasukan bulan ini ----
$pie_masuk = $conn->query("SELECT k.nama_kategori, COALESCE(SUM(t.jumlah),0) as total FROM transaksi t JOIN kategori k ON t.kategori_id=k.id WHERE t.jenis='masuk' AND DATE_FORMAT(t.tanggal,'%Y-%m')='$bln' GROUP BY k.id ORDER BY total DESC LIMIT 6");
$pm_labels = $pm_data = [];
while ($r = $pie_masuk->fetch_assoc()) { $pm_labels[] = $r['nama_kategori']; $pm_data[] = (float)$r['total']; }

// ---- Transaksi terbaru ----
$trx_recent = $conn->query("SELECT t.*, k.nama_kategori FROM transaksi t JOIN kategori k ON t.kategori_id=k.id ORDER BY t.tanggal DESC, t.id DESC LIMIT 8");

$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Dashboard – <?= APP_NAME ?></title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
        <div class="stat-value" id="statSaldo"><?= formatRupiah($saldo) ?></div>
        <div class="stat-sub"><i class="fas fa-sync up"></i> Diperbarui hari ini</div>
      </div>
      <div class="stat-card green animate-fadeIn delay-2">
        <div class="stat-icon"><i class="fas fa-arrow-down"></i></div>
        <div class="stat-label">Total Pemasukan</div>
        <div class="stat-value"><?= formatRupiah($total_masuk) ?></div>
        <div class="stat-sub"><i class="fas fa-calendar-alt up"></i> Semua periode</div>
      </div>
      <div class="stat-card red animate-fadeIn delay-3">
        <div class="stat-icon"><i class="fas fa-arrow-up"></i></div>
        <div class="stat-label">Total Pengeluaran</div>
        <div class="stat-value"><?= formatRupiah($total_keluar) ?></div>
        <div class="stat-sub"><i class="fas fa-calendar-alt down"></i> Semua periode</div>
      </div>
      <div class="stat-card blue animate-fadeIn delay-4">
        <div class="stat-icon"><i class="fas fa-exchange-alt"></i></div>
        <div class="stat-label">Total Transaksi</div>
        <div class="stat-value"><?= number_format($total_trx) ?></div>
        <div class="stat-sub"><i class="fas fa-list"></i> <?= $trx_bln ?> transaksi bulan ini</div>
      </div>
    </div>

    <!-- BULAN INI SUMMARY -->
    <div class="card mb-3 animate-fadeIn" style="background:linear-gradient(135deg,var(--primary),var(--accent));color:#fff;border:none">
      <div class="card-body" style="padding:24px 28px">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px">
          <div>
            <div style="font-size:.8rem;opacity:.75;font-weight:500;text-transform:uppercase;letter-spacing:.5px">Ringkasan Bulan Ini</div>
            <div style="font-size:1.2rem;font-weight:800;margin-top:4px"><?= date('F Y') ?></div>
          </div>
          <div style="display:flex;gap:32px;flex-wrap:wrap">
            <div style="text-align:center">
              <div style="font-size:1.4rem;font-weight:800"><?= formatRupiah($masuk_bln) ?></div>
              <div style="font-size:.75rem;opacity:.75"><i class="fas fa-arrow-down"></i> Pemasukan</div>
            </div>
            <div style="width:1px;background:rgba(255,255,255,.2)"></div>
            <div style="text-align:center">
              <div style="font-size:1.4rem;font-weight:800"><?= formatRupiah($keluar_bln) ?></div>
              <div style="font-size:.75rem;opacity:.75"><i class="fas fa-arrow-up"></i> Pengeluaran</div>
            </div>
            <div style="width:1px;background:rgba(255,255,255,.2)"></div>
            <div style="text-align:center">
              <div style="font-size:1.4rem;font-weight:800 <?= ($masuk_bln-$keluar_bln)<0?';color:#fca5a5':'' ?>"><?= formatRupiah($masuk_bln - $keluar_bln) ?></div>
              <div style="font-size:.75rem;opacity:.75"><i class="fas fa-balance-scale"></i> Selisih</div>
            </div>
          </div>
          <div style="display:flex;gap:10px">
            <a href="kas-masuk.php" class="btn btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3)"><i class="fas fa-plus"></i> Kas Masuk</a>
            <a href="kas-keluar.php" class="btn btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3)"><i class="fas fa-minus"></i> Kas Keluar</a>
          </div>
        </div>
      </div>
    </div>

    <!-- CHARTS ROW -->
    <div class="grid-2 mb-3">
      <div class="card animate-fadeIn delay-1">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-chart-bar"></i> Pemasukan vs Pengeluaran</div>
          <span style="font-size:.75rem;color:var(--text-muted)">6 Bulan Terakhir</span>
        </div>
        <div class="card-body">
          <div class="chart-container" style="height:260px">
            <canvas id="barChart"></canvas>
          </div>
        </div>
      </div>
      <div class="card animate-fadeIn delay-2">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-chart-pie"></i> Sumber Pemasukan</div>
          <span style="font-size:.75rem;color:var(--text-muted)"><?= date('F Y') ?></span>
        </div>
        <div class="card-body" style="display:flex;flex-direction:column;align-items:center">
          <?php if (count($pm_data)): ?>
          <div class="chart-container" style="max-width:220px;width:100%"><canvas id="pieChart"></canvas></div>
          <div class="chart-legend" id="pieLegend"></div>
          <?php else: ?>
          <div class="empty-state"><div class="es-icon"><i class="fas fa-chart-pie"></i></div><h3>Belum ada data bulan ini</h3></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- TRANSAKSI TERBARU -->
    <div class="card animate-fadeIn delay-3">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-history"></i> Transaksi Terbaru</div>
        <a href="laporan.php" class="btn btn-ghost btn-sm"><i class="fas fa-list"></i> Lihat Semua</a>
      </div>
      <div class="card-body" style="padding:0">
        <table class="table">
          <thead>
            <tr><th>Tanggal</th><th>Keterangan</th><th>Kategori</th><th>Jenis</th><th class="text-right">Jumlah</th><th>Aksi</th></tr>
          </thead>
          <tbody>
            <?php while ($r = $trx_recent->fetch_assoc()): ?>
            <tr>
              <td><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
              <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($r['keterangan']) ?></td>
              <td><span class="badge badge-primary"><?= htmlspecialchars($r['nama_kategori']) ?></span></td>
              <td>
                <?= $r['jenis']=='masuk'
                  ? '<span class="badge badge-success"><i class="fas fa-arrow-down"></i> Masuk</span>'
                  : '<span class="badge badge-danger"><i class="fas fa-arrow-up"></i> Keluar</span>' ?>
              </td>
              <td class="text-right fw-600 <?= $r['jenis']=='masuk'?'text-success':'text-danger' ?>">
                <?= ($r['jenis']=='masuk'?'+':'-') . formatRupiah($r['jumlah']) ?>
              </td>
              <td>
                <a href="<?= $r['jenis']=='masuk'?'kas-masuk':'kas-keluar' ?>.php?edit=<?= $r['id'] ?>"
                   class="btn btn-ghost btn-icon btn-sm" data-tooltip="Edit">
                  <i class="fas fa-edit"></i>
                </a>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div><!-- /admin-content -->
</div><!-- /admin-main -->
</div><!-- /admin-wrapper -->

<script>
Chart.defaults.font.family = 'Poppins, sans-serif';
const fmtRp = v => 'Rp ' + new Intl.NumberFormat('id-ID').format(v);

// Bar Chart
new Chart(document.getElementById('barChart').getContext('2d'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($chart_labels) ?>,
    datasets: [
      { label:'Pemasukan', data:<?= json_encode($chart_masuk) ?>, backgroundColor:'rgba(26,122,74,.8)', borderRadius:6, borderSkipped:false },
      { label:'Pengeluaran', data:<?= json_encode($chart_keluar) ?>, backgroundColor:'rgba(239,68,68,.7)', borderRadius:6, borderSkipped:false }
    ]
  },
  options: {
    responsive:true, maintainAspectRatio:false,
    interaction:{mode:'index',intersect:false},
    plugins:{ legend:{position:'bottom',labels:{padding:16,font:{size:11}}}, tooltip:{callbacks:{label:c=>` ${c.dataset.label}: ${fmtRp(c.raw)}`}} },
    scales:{ x:{grid:{display:false},ticks:{font:{size:10}}}, y:{grid:{color:'rgba(0,0,0,0.05)'},ticks:{callback:v=>fmtRp(v),font:{size:10}}} }
  }
});

// Pie Chart
<?php if (count($pm_data)): ?>
const PIE_COLORS = ['#1a7a4a','#22a05e','#c9a84c','#3b82f6','#f59e0b','#8b5cf6'];
const pieChart = new Chart(document.getElementById('pieChart').getContext('2d'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode($pm_labels) ?>,
    datasets: [{ data: <?= json_encode($pm_data) ?>, backgroundColor: PIE_COLORS, borderWidth:3, borderColor:'#fff', hoverOffset:10 }]
  },
  options: {
    responsive:true, maintainAspectRatio:true, cutout:'55%',
    plugins:{ legend:{display:false}, tooltip:{callbacks:{label:c=>` ${c.label}: ${fmtRp(c.raw)}`}} }
  }
});
const leg = document.getElementById('pieLegend');
<?= json_encode($pm_labels) ?>.forEach((l,i) => {
  leg.innerHTML += `<div class="legend-item"><span class="legend-dot" style="background:${PIE_COLORS[i]}"></span>${l}</div>`;
});
<?php endif; ?>

// Sidebar toggle
const sidebar = document.getElementById('adminSidebar');
const overlay = document.getElementById('sidebarOverlay');
document.getElementById('sidebarToggle').addEventListener('click', () => {
  sidebar.classList.toggle('open');
  overlay.classList.toggle('active');
});
overlay.addEventListener('click', () => {
  sidebar.classList.remove('open');
  overlay.classList.remove('active');
});
</script>
</body>
</html>
