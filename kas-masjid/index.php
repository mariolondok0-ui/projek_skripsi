<?php
require_once 'includes/config.php';

// Statistik utama
$total_masuk  = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='masuk'")->fetch_assoc()['t'];
$total_keluar = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='keluar'")->fetch_assoc()['t'];
$saldo        = $total_masuk - $total_keluar;
$total_trx    = (int)$conn->query("SELECT COUNT(*) as t FROM transaksi")->fetch_assoc()['t'];
$bln          = date('Y-m');
$tahun        = (int)date('Y');
$masuk_bln    = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='masuk'  AND DATE_FORMAT(tanggal,'%Y-%m')='$bln'")->fetch_assoc()['t'];
$keluar_bln   = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='keluar' AND DATE_FORMAT(tanggal,'%Y-%m')='$bln'")->fetch_assoc()['t'];
$total_trx_bln= (int)$conn->query("SELECT COUNT(*) as t FROM transaksi WHERE DATE_FORMAT(tanggal,'%Y-%m')='$bln'")->fetch_assoc()['t'];

// Data grafik 6 bulan terakhir (bar)
$chart_labels = $chart_masuk = $chart_keluar = [];
for ($i = 5; $i >= 0; $i--) {
    $b = date('Y-m', strtotime("-$i month"));
    $chart_labels[] = date('M Y', strtotime("-$i month"));
    $chart_masuk[]  = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='masuk'  AND DATE_FORMAT(tanggal,'%Y-%m')='$b'")->fetch_assoc()['t'];
    $chart_keluar[] = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='keluar' AND DATE_FORMAT(tanggal,'%Y-%m')='$b'")->fetch_assoc()['t'];
}

// Data line chart saldo kumulatif 12 bulan
$line_labels = $line_saldo = [];
$kum = 0;
for ($m = 1; $m <= 12; $m++) {
    $b   = sprintf('%04d-%02d', $tahun, $m);
    $mk  = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='masuk'  AND DATE_FORMAT(tanggal,'%Y-%m')='$b'")->fetch_assoc()['t'];
    $kl  = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='keluar' AND DATE_FORMAT(tanggal,'%Y-%m')='$b'")->fetch_assoc()['t'];
    $kum += ($mk - $kl);
    $line_labels[] = date('M', mktime(0,0,0,$m,1));
    $line_saldo[]  = $kum;
}

// Pie pemasukan
$pi  = $conn->query("SELECT k.nama_kategori, COALESCE(SUM(t.jumlah),0) as total FROM transaksi t JOIN kategori k ON t.kategori_id=k.id WHERE t.jenis='masuk' AND YEAR(t.tanggal)=$tahun GROUP BY k.id ORDER BY total DESC LIMIT 8");
$pil = $pid = [];
while ($r = $pi->fetch_assoc()) { $pil[] = $r['nama_kategori']; $pid[] = (float)$r['total']; }

// Pie pengeluaran
$pe  = $conn->query("SELECT k.nama_kategori, COALESCE(SUM(t.jumlah),0) as total FROM transaksi t JOIN kategori k ON t.kategori_id=k.id WHERE t.jenis='keluar' AND YEAR(t.tanggal)=$tahun GROUP BY k.id ORDER BY total DESC LIMIT 8");
$pel = $ped = [];
while ($r = $pe->fetch_assoc()) { $pel[] = $r['nama_kategori']; $ped[] = (float)$r['total']; }

// Transaksi terbaru
$trx_terbaru = $conn->query("SELECT t.*, k.nama_kategori FROM transaksi t JOIN kategori k ON t.kategori_id=k.id ORDER BY t.tanggal DESC, t.id DESC LIMIT 6");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Beranda – <?= APP_NAME ?></title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
/* Slideshow */
.grafik-slide { display:none; animation:slideInChart .55s cubic-bezier(.4,0,.2,1); }
.grafik-slide.active { display:block; }
@keyframes slideInChart {
  from { opacity:0; transform:translateX(50px) scale(.97); }
  to   { opacity:1; transform:translateX(0) scale(1); }
}
.slide-progress-wrap { height:4px; background:rgba(26,122,74,.15); border-radius:99px; overflow:hidden; margin-bottom:20px; }
.slide-progress-bar  { height:100%; background:var(--primary); border-radius:99px; width:0%; transition:width linear; }
.slide-dot { width:10px; height:10px; border-radius:50%; background:var(--border); cursor:pointer; transition:var(--transition); border:none; }
.slide-dot.active { background:var(--primary); width:28px; border-radius:99px; }
.slide-nav-btn { width:38px; height:38px; border-radius:50%; background:var(--bg-main); border:1.5px solid var(--border); color:var(--text-secondary); display:flex; align-items:center; justify-content:center; cursor:pointer; transition:var(--transition-fast); font-size:.85rem; }
.slide-nav-btn:hover { background:var(--primary); border-color:var(--primary); color:#fff; }
.chart-pie-wrap { display:flex; align-items:center; justify-content:center; gap:32px; flex-wrap:wrap; padding:8px 0; }
.pie-legend-item { display:flex; align-items:center; gap:8px; }
.pie-legend-dot { width:14px; height:14px; border-radius:4px; flex-shrink:0; }
</style>
</head>
<body>
<?php include 'includes/partials/navbar-publik.php'; ?>

<!-- ===== HERO ===== -->
<section class="hero">
  <div class="container hero-content">
    <div class="hero-badge"><i class="fas fa-mosque"></i> <?= MASJID_NAME ?></div>
    <h1>Transparansi Keuangan<br><span>Kas Masjid</span> untuk Jamaah</h1>
    <p>Pantau pemasukan, pengeluaran, dan saldo kas masjid secara real-time. Informasi terbuka dan dapat diakses oleh seluruh jamaah kapan saja.</p>
    <div class="hero-actions">
      <a href="laporan-publik.php" class="btn btn-secondary btn-lg"><i class="fas fa-file-alt"></i> Lihat Laporan</a>
      <a href="#grafik-section" class="btn btn-lg" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.4)"><i class="fas fa-chart-bar"></i> Lihat Grafik</a>
    </div>
    <div class="hero-stats">
      <div class="hero-stat"><div class="hs-val" id="heroMasuk">Rp 0</div><div class="hs-label">Total Pemasukan</div></div>
      <div class="hero-divider"></div>
      <div class="hero-stat"><div class="hs-val" id="heroKeluar">Rp 0</div><div class="hs-label">Total Pengeluaran</div></div>
      <div class="hero-divider"></div>
      <div class="hero-stat"><div class="hs-val"><?= $total_trx ?></div><div class="hs-label">Total Transaksi</div></div>
    </div>
  </div>
</section>

<!-- ===== SALDO & STAT CARDS ===== -->
<section class="section" style="padding-top:48px;padding-bottom:32px">
  <div class="container">
    <div class="grid-3" style="align-items:start">

      <!-- Saldo Card -->
      <div class="saldo-card animate-fadeIn">
        <div class="saldo-icon"><i class="fas fa-wallet"></i></div>
        <div class="saldo-label">Saldo Kas Saat Ini</div>
        <div class="saldo-amount"><?= formatRupiah($saldo) ?></div>
        <div class="saldo-update"><i class="fas fa-sync-alt"></i> Diperbarui: <?= date('d M Y, H:i') ?></div>
        <div style="margin-top:20px;display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
          <a href="laporan-publik.php" class="btn btn-sm" style="background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.35)"><i class="fas fa-file-alt"></i> Laporan</a>
          <a href="#grafik-section" class="btn btn-sm" style="background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.35)"><i class="fas fa-chart-pie"></i> Grafik</a>
        </div>
      </div>

      <!-- Stat Masuk/Keluar -->
      <div style="display:flex;flex-direction:column;gap:16px">
        <div class="stat-card green animate-fadeIn delay-1">
          <div class="stat-icon"><i class="fas fa-arrow-down"></i></div>
          <div class="stat-label">Pemasukan Bulan Ini</div>
          <div class="stat-value"><?= formatRupiah($masuk_bln) ?></div>
          <div class="stat-sub"><i class="fas fa-calendar up"></i> <?= date('F Y') ?></div>
        </div>
        <div class="stat-card red animate-fadeIn delay-2">
          <div class="stat-icon"><i class="fas fa-arrow-up"></i></div>
          <div class="stat-label">Pengeluaran Bulan Ini</div>
          <div class="stat-value"><?= formatRupiah($keluar_bln) ?></div>
          <div class="stat-sub"><i class="fas fa-calendar down"></i> <?= date('F Y') ?></div>
        </div>
      </div>

      <!-- Info Masjid -->
      <div class="card animate-fadeIn delay-3" style="padding:28px">
        <div style="text-align:center;margin-bottom:20px">
          <div style="width:64px;height:64px;background:linear-gradient(135deg,var(--primary),var(--accent));border-radius:18px;display:flex;align-items:center;justify-content:center;font-size:1.8rem;color:#fff;margin:0 auto 14px;box-shadow:0 6px 20px rgba(26,122,74,.3)"><i class="fas fa-mosque"></i></div>
          <h3 style="font-size:1rem;font-weight:800;color:var(--text-primary)"><?= MASJID_NAME ?></h3>
          <p style="font-size:.78rem;color:var(--text-muted);margin-top:4px;line-height:1.6"><?= MASJID_ALAMAT ?></p>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div style="text-align:center;padding:12px;background:var(--bg-main);border-radius:var(--radius)">
            <div style="font-size:1.3rem;font-weight:800;color:var(--primary)"><?= $total_trx ?></div>
            <div style="font-size:.72rem;color:var(--text-muted)">Total Transaksi</div>
          </div>
          <div style="text-align:center;padding:12px;background:var(--bg-main);border-radius:var(--radius)">
            <div style="font-size:1.3rem;font-weight:800;color:var(--secondary)"><?= $total_trx_bln ?></div>
            <div style="font-size:.72rem;color:var(--text-muted)">Bulan Ini</div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ===== GRAFIK SLIDESHOW LENGKAP ===== -->
<section class="section" id="grafik-section" style="padding-top:20px;padding-bottom:48px;background:linear-gradient(180deg,#f0f4f0,#e8f5ee)">
  <div class="container">
    <div class="section-header">
      <h2 class="section-title">Visualisasi <span>Data Keuangan</span></h2>
      <div class="section-divider"></div>
      <p class="section-subtitle">Grafik interaktif keuangan kas masjid – berganti otomatis setiap 10 detik</p>
    </div>

    <div class="card animate-fadeIn" style="padding:28px">

      <!-- Progress bar autoplay -->
      <div class="slide-progress-wrap"><div class="slide-progress-bar" id="slideProgress"></div></div>

      <!-- Slide Navigation Header -->
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px">
        <div>
          <div style="display:inline-flex;align-items:center;gap:8px;background:var(--primary);color:#fff;padding:6px 16px;border-radius:99px;font-size:.82rem;font-weight:700" id="slideBadge">
            <i class="fas fa-chart-bar"></i> <span id="slideTitle">Pemasukan vs Pengeluaran</span>
          </div>
          <div style="font-size:.8rem;color:var(--text-muted);margin-top:8px" id="slideDesc">Perbandingan total pemasukan dan pengeluaran 6 bulan terakhir</div>
        </div>
        <div style="display:flex;align-items:center;gap:12px">
          <span style="font-size:.8rem;color:var(--text-muted)" id="slideCounter">1 / 4</span>
          <div style="display:flex;gap:8px">
            <button class="slide-nav-btn" onclick="changeSlide(-1)" title="Sebelumnya"><i class="fas fa-chevron-left"></i></button>
            <button class="slide-nav-btn" onclick="togglePause()" id="btnPause" title="Pause/Play"><i class="fas fa-pause" id="pauseIcon"></i></button>
            <button class="slide-nav-btn" onclick="changeSlide(1)" title="Berikutnya"><i class="fas fa-chevron-right"></i></button>
          </div>
        </div>
      </div>

      <!-- SLIDE 1: Bar Chart 6 Bulan -->
      <div class="grafik-slide active" id="slide-0">
        <div style="position:relative;height:320px"><canvas id="barChart"></canvas></div>
      </div>

      <!-- SLIDE 2: Line Chart Saldo Kumulatif -->
      <div class="grafik-slide" id="slide-1">
        <div style="position:relative;height:320px"><canvas id="lineChart"></canvas></div>
      </div>

      <!-- SLIDE 3: Pie Pemasukan -->
      <div class="grafik-slide" id="slide-2">
        <?php if (count($pid)): ?>
        <div class="chart-pie-wrap">
          <div style="position:relative;width:260px;height:260px;flex-shrink:0"><canvas id="pieIncome"></canvas></div>
          <div id="legIncome" style="display:flex;flex-direction:column;gap:10px;min-width:200px;max-width:320px"></div>
        </div>
        <?php else: ?>
        <div class="empty-state"><div class="es-icon"><i class="fas fa-chart-pie"></i></div><h3>Belum ada data pemasukan tahun <?= $tahun ?></h3></div>
        <?php endif; ?>
      </div>

      <!-- SLIDE 4: Pie Pengeluaran -->
      <div class="grafik-slide" id="slide-3">
        <?php if (count($ped)): ?>
        <div class="chart-pie-wrap">
          <div style="position:relative;width:260px;height:260px;flex-shrink:0"><canvas id="pieExpense"></canvas></div>
          <div id="legExpense" style="display:flex;flex-direction:column;gap:10px;min-width:200px;max-width:320px"></div>
        </div>
        <?php else: ?>
        <div class="empty-state"><div class="es-icon"><i class="fas fa-chart-pie"></i></div><h3>Belum ada data pengeluaran tahun <?= $tahun ?></h3></div>
        <?php endif; ?>
      </div>

      <!-- Dot Indicators -->
      <div style="display:flex;justify-content:center;gap:10px;margin-top:24px">
        <button class="slide-dot active" onclick="goToSlide(0)" title="Grafik 1"></button>
        <button class="slide-dot" onclick="goToSlide(1)" title="Grafik 2"></button>
        <button class="slide-dot" onclick="goToSlide(2)" title="Grafik 3"></button>
        <button class="slide-dot" onclick="goToSlide(3)" title="Grafik 4"></button>
      </div>
    </div>
  </div>
</section>

<!-- ===== TRANSAKSI TERBARU ===== -->
<section class="section" style="padding-top:32px;padding-bottom:48px">
  <div class="container">
    <div class="section-header">
      <h2 class="section-title">Transaksi <span>Terbaru</span></h2>
      <div class="section-divider"></div>
      <p class="section-subtitle">Catatan kas masjid terkini yang dapat diakses oleh seluruh jamaah</p>
    </div>
    <div class="table-wrapper animate-fadeIn">
      <table class="table table-striped">
        <thead>
          <tr><th>Tanggal</th><th>Keterangan</th><th>Kategori</th><th>Jenis</th><th class="text-right">Jumlah</th></tr>
        </thead>
        <tbody>
          <?php if ($trx_terbaru->num_rows): while ($r = $trx_terbaru->fetch_assoc()): ?>
          <tr>
            <td><i class="fas fa-calendar-alt" style="color:var(--text-muted);margin-right:6px"></i><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
            <td><?= htmlspecialchars($r['keterangan']) ?></td>
            <td><span class="badge badge-primary"><?= htmlspecialchars($r['nama_kategori']) ?></span></td>
            <td><?= $r['jenis']=='masuk' ? '<span class="badge badge-success"><i class="fas fa-arrow-down"></i> Masuk</span>' : '<span class="badge badge-danger"><i class="fas fa-arrow-up"></i> Keluar</span>' ?></td>
            <td class="text-right fw-600 <?= $r['jenis']=='masuk'?'text-success':'text-danger' ?>"><?= ($r['jenis']=='masuk'?'+':'-').formatRupiah($r['jumlah']) ?></td>
          </tr>
          <?php endwhile; else: ?>
          <tr><td colspan="5"><div class="empty-state"><div class="es-icon"><i class="fas fa-inbox"></i></div><h3>Belum ada transaksi</h3></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <div style="text-align:center;margin-top:24px">
      <a href="laporan-publik.php" class="btn btn-outline"><i class="fas fa-list"></i> Lihat Semua Transaksi</a>
    </div>
  </div>
</section>

<!-- ===== TRANSPARANSI ===== -->
<section class="section" style="background:linear-gradient(135deg,#f0f7f2,#e8f5ee);padding:52px 0">
  <div class="container">
    <div class="section-header">
      <h2 class="section-title">Komitmen <span>Transparansi</span></h2>
      <div class="section-divider"></div>
      <p class="section-subtitle">Pengelolaan keuangan masjid yang terbuka dan dapat dipertanggungjawabkan kepada jamaah</p>
    </div>
    <div class="grid-3">
      <?php $feats = [
        ['fas fa-eye','green','Keterbukaan Informasi','Seluruh data transaksi pemasukan dan pengeluaran dapat diakses oleh jamaah secara langsung.'],
        ['fas fa-chart-pie','gold','Visualisasi Data','Data keuangan disajikan dalam bentuk grafik interaktif sehingga mudah dipahami.'],
        ['fas fa-file-alt','blue','Laporan Berkala','Tersedia laporan harian, mingguan, bulanan, dan tahunan dengan fitur ekspor PDF.'],
        ['fas fa-shield-alt','green','Data Terverifikasi','Setiap transaksi dicatat oleh pengurus yang bertanggung jawab kepada jamaah.'],
        ['fas fa-clock','gold','Real-time Update','Informasi saldo dan transaksi diperbarui secara langsung.'],
        ['fas fa-mobile-alt','blue','Akses Kapan Saja','Dapat diakses dari perangkat apapun tanpa perlu menginstal aplikasi.'],
      ];
      foreach ($feats as $i => [$ico,$col,$ttl,$dsc]): ?>
      <div class="card animate-fadeIn delay-<?= $i+1 ?>" style="padding:28px">
        <div style="width:56px;height:56px;border-radius:16px;background:<?= $col=='green'?'rgba(26,122,74,.1)':($col=='gold'?'rgba(201,168,76,.1)':'rgba(59,130,246,.1)') ?>;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:<?= $col=='green'?'var(--primary)':($col=='gold'?'var(--secondary)':'var(--info)') ?>;margin-bottom:18px"><i class="<?= $ico ?>"></i></div>
        <h3 style="font-size:1rem;font-weight:700;margin-bottom:10px"><?= $ttl ?></h3>
        <p style="font-size:.875rem;color:var(--text-secondary);line-height:1.7"><?= $dsc ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php include 'includes/partials/footer-publik.php'; ?>

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

// ── Bar Chart ──
new Chart(document.getElementById('barChart').getContext('2d'), {
  type:'bar',
  data:{ labels:<?= json_encode($chart_labels) ?>,
    datasets:[
      {label:'Pemasukan',   data:<?= json_encode($chart_masuk) ?>,  backgroundColor:'rgba(26,122,74,.85)', borderRadius:8, borderSkipped:false},
      {label:'Pengeluaran', data:<?= json_encode($chart_keluar) ?>, backgroundColor:'rgba(239,68,68,.75)', borderRadius:8, borderSkipped:false}
    ]
  },
  options:{responsive:true,maintainAspectRatio:false,
    interaction:{mode:'index',intersect:false},
    plugins:{legend:{position:'bottom',labels:{padding:20,font:{size:12},usePointStyle:true}},
      tooltip:{callbacks:{label:c=>` ${c.dataset.label}: ${fmtRp(c.raw)}`}}},
    scales:{x:{grid:{display:false}},y:{grid:{color:'rgba(0,0,0,.05)'},ticks:{callback:v=>fmtRp(v)}}},
    animation:{duration:900,easing:'easeInOutQuart'}
  }
});

// ── Line Chart ──
new Chart(document.getElementById('lineChart').getContext('2d'), {
  type:'line',
  data:{ labels:<?= json_encode($line_labels) ?>,
    datasets:[{label:'Saldo Kumulatif', data:<?= json_encode($line_saldo) ?>,
      borderColor:'#1a7a4a', backgroundColor:'rgba(26,122,74,.08)',
      borderWidth:3, fill:true, tension:.4,
      pointBackgroundColor:'#1a7a4a', pointBorderColor:'#fff',
      pointBorderWidth:2, pointRadius:6, pointHoverRadius:9
    }]
  },
  options:{responsive:true,maintainAspectRatio:false,
    plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>` Saldo: ${fmtRp(c.raw)}`}}},
    scales:{x:{grid:{display:false}},y:{grid:{color:'rgba(0,0,0,.05)'},ticks:{callback:v=>fmtRp(v)}}},
    animation:{duration:900}
  }
});

// ── Pie Helper ──
function buildPie(cid, lid, labels, data, colors) {
  if (!labels.length) return;
  new Chart(document.getElementById(cid).getContext('2d'), {
    type:'doughnut',
    data:{labels, datasets:[{data, backgroundColor:colors, borderWidth:3, borderColor:'#fff', hoverOffset:14}]},
    options:{responsive:true,maintainAspectRatio:true,cutout:'58%',
      plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>` ${c.label}: ${fmtRp(c.raw)}`}}},
      animation:{duration:900,animateRotate:true,animateScale:true}
    }
  });
  const leg = document.getElementById(lid);
  const total = data.reduce((a,b)=>a+b,0);
  labels.forEach((l,i) => {
    const pct = total > 0 ? ((data[i]/total)*100).toFixed(1) : 0;
    leg.innerHTML += `<div class="pie-legend-item"><span class="pie-legend-dot" style="background:${colors[i]}"></span><div><div style="font-size:.83rem;font-weight:600;color:var(--text-primary)">${l}</div><div style="font-size:.75rem;color:var(--text-muted)">${fmtRp(data[i])} &bull; ${pct}%</div></div></div>`;
  });
}
buildPie('pieIncome',  'legIncome',  <?= json_encode($pil) ?>, <?= json_encode($pid) ?>, CG);
buildPie('pieExpense', 'legExpense', <?= json_encode($pel) ?>, <?= json_encode($ped) ?>, CR);

// ── Counter Animation Hero ──
function animCounter(el, target) {
  let s = 0; const step = target / (1800/16);
  const t = setInterval(() => {
    s += step; if (s >= target) { s = target; clearInterval(t); }
    el.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.floor(s));
  }, 16);
}
window.addEventListener('load', () => {
  animCounter(document.getElementById('heroMasuk'),  <?= $total_masuk ?>);
  animCounter(document.getElementById('heroKeluar'), <?= $total_keluar ?>);
});

// ── SLIDESHOW ENGINE ──
const DURATION  = 10000;
const slides    = document.querySelectorAll('.grafik-slide');
const dots      = document.querySelectorAll('.slide-dot');
const progress  = document.getElementById('slideProgress');
const SLIDE_INFO = [
  {icon:'fas fa-chart-bar',  title:'Pemasukan vs Pengeluaran', desc:'Perbandingan pemasukan dan pengeluaran 6 bulan terakhir'},
  {icon:'fas fa-chart-line', title:'Saldo Kumulatif',          desc:'Tren pertumbuhan saldo kas sepanjang tahun '+<?= $tahun ?>},
  {icon:'fas fa-chart-pie',  title:'Proporsi Pemasukan',       desc:'Distribusi sumber pemasukan berdasarkan kategori'},
  {icon:'fas fa-chart-pie',  title:'Proporsi Pengeluaran',     desc:'Distribusi pengeluaran berdasarkan kategori'},
];
let cur = 0, paused = false, timer = null;

function goToSlide(n) {
  slides[cur].classList.remove('active');
  dots[cur].classList.remove('active');
  cur = (n + slides.length) % slides.length;
  slides[cur].classList.add('active');
  dots[cur].classList.add('active');
  const info = SLIDE_INFO[cur];
  document.getElementById('slideTitle').textContent   = info.title;
  document.getElementById('slideDesc').textContent    = info.desc;
  document.getElementById('slideBadge').querySelector('i').className = info.icon;
  document.getElementById('slideCounter').textContent = `${cur+1} / ${slides.length}`;
  resetProgress();
}

function changeSlide(dir) { goToSlide(cur + dir); if (!paused) startAuto(); }

function startAuto() {
  clearTimeout(timer);
  timer = setTimeout(() => { if (!paused) { goToSlide(cur+1); startAuto(); } }, DURATION);
}

function resetProgress() {
  progress.style.transition = 'none'; progress.style.width = '0%';
  if (!paused) setTimeout(() => { progress.style.transition = `width ${DURATION}ms linear`; progress.style.width='100%'; }, 30);
}

function togglePause() {
  paused = !paused;
  const ico = document.getElementById('pauseIcon');
  if (paused) {
    ico.className = 'fas fa-play';
    clearTimeout(timer);
    const w = getComputedStyle(progress).width;
    progress.style.transition = 'none'; progress.style.width = w;
  } else {
    ico.className = 'fas fa-pause';
    startAuto();
    resetProgress();
  }
}

// Start
startAuto(); resetProgress();

// Keyboard
document.addEventListener('keydown', e => {
  if (e.key==='ArrowRight') changeSlide(1);
  if (e.key==='ArrowLeft')  changeSlide(-1);
  if (e.key===' ') { e.preventDefault(); togglePause(); }
});

// Navbar toggle
document.getElementById('navToggle').addEventListener('click', () => document.getElementById('navLinks').classList.toggle('open'));

// Scroll animation
const obs = new IntersectionObserver(entries => entries.forEach(e => {
  if (e.isIntersecting) { e.target.style.opacity='1'; e.target.style.transform='translateY(0)'; }
}), {threshold:.08});
document.querySelectorAll('.card,.stat-card,.saldo-card,.table-wrapper').forEach((el,i) => {
  el.style.opacity='0'; el.style.transform='translateY(24px)';
  el.style.transition=`opacity .6s ease ${i*.07}s,transform .6s ease ${i*.07}s`;
  obs.observe(el);
});
</script>
</body>
</html>
