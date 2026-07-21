<?php
require_once 'includes/config.php';
$tahun = (int)($_GET['tahun'] ?? date('Y'));

// Bar Chart data
$blnlbl = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
$bm = $bk = $sl = [];
$kum = 0;
for ($m = 1; $m <= 12; $m++) {
    $b  = sprintf('%04d-%02d', $tahun, $m);
    $mk = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='masuk'  AND DATE_FORMAT(tanggal,'%Y-%m')='$b'")->fetch_assoc()['t'];
    $kl = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='keluar' AND DATE_FORMAT(tanggal,'%Y-%m')='$b'")->fetch_assoc()['t'];
    $bm[] = $mk; $bk[] = $kl; $kum += ($mk - $kl); $sl[] = $kum;
}

// Pie pemasukan
$pi = $conn->query("SELECT k.nama_kategori, COALESCE(SUM(t.jumlah),0) as total FROM transaksi t JOIN kategori k ON t.kategori_id=k.id WHERE t.jenis='masuk' AND YEAR(t.tanggal)=$tahun GROUP BY k.id ORDER BY total DESC LIMIT 8");
$pil = $pid = [];
while ($r = $pi->fetch_assoc()) { $pil[] = $r['nama_kategori']; $pid[] = (float)$r['total']; }

// Pie pengeluaran
$pe = $conn->query("SELECT k.nama_kategori, COALESCE(SUM(t.jumlah),0) as total FROM transaksi t JOIN kategori k ON t.kategori_id=k.id WHERE t.jenis='keluar' AND YEAR(t.tanggal)=$tahun GROUP BY k.id ORDER BY total DESC LIMIT 8");
$pel = $ped = [];
while ($r = $pe->fetch_assoc()) { $pel[] = $r['nama_kategori']; $ped[] = (float)$r['total']; }

// Summary
$s = $conn->query("SELECT COALESCE(SUM(CASE WHEN jenis='masuk' THEN jumlah END),0) AS m, COALESCE(SUM(CASE WHEN jenis='keluar' THEN jumlah END),0) AS k FROM transaksi WHERE YEAR(tanggal)=$tahun")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Visualisasi Grafik – <?= APP_NAME ?></title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
/* ===== GRAFIK SLIDESHOW ===== */
.grafik-wrapper {
  position: relative;
  overflow: hidden;
  border-radius: var(--radius-xl);
  min-height: 480px;
}
.grafik-slide {
  display: none;
  animation: slideInChart .6s cubic-bezier(.4,0,.2,1);
}
.grafik-slide.active { display: block; }

@keyframes slideInChart {
  from { opacity: 0; transform: translateX(60px) scale(.97); }
  to   { opacity: 1; transform: translateX(0) scale(1); }
}
@keyframes slideOutChart {
  from { opacity: 1; transform: translateX(0); }
  to   { opacity: 0; transform: translateX(-60px); }
}

/* Progress Bar Autoplay */
.slide-progress-wrap {
  height: 4px;
  background: rgba(255,255,255,.2);
  border-radius: 99px;
  overflow: hidden;
  margin-bottom: 20px;
}
.slide-progress-bar {
  height: 100%;
  background: var(--secondary);
  border-radius: 99px;
  width: 0%;
  transition: width linear;
}

/* Slide indicators */
.slide-indicators {
  display: flex;
  justify-content: center;
  gap: 10px;
  margin-top: 24px;
}
.slide-dot {
  width: 10px; height: 10px;
  border-radius: 50%;
  background: var(--border);
  cursor: pointer;
  transition: var(--transition);
  border: none;
}
.slide-dot.active {
  background: var(--primary);
  transform: scale(1.3);
  width: 28px;
  border-radius: 99px;
}

/* Slide nav buttons */
.slide-nav {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  flex-wrap: wrap;
  gap: 12px;
}
.slide-nav-btns { display: flex; gap: 10px; }
.slide-nav-btn {
  width: 40px; height: 40px;
  border-radius: 50%;
  background: var(--bg-card);
  border: 1.5px solid var(--border);
  color: var(--text-secondary);
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: var(--transition-fast);
  font-size: .9rem;
}
.slide-nav-btn:hover {
  background: var(--primary);
  border-color: var(--primary);
  color: #fff;
}

/* Slide title badge */
.slide-badge {
  display: inline-flex; align-items: center; gap: 8px;
  background: var(--primary);
  color: #fff;
  padding: 6px 16px;
  border-radius: 99px;
  font-size: .82rem;
  font-weight: 700;
}
.slide-counter {
  font-size: .8rem;
  color: var(--text-muted);
  font-weight: 500;
}

/* Chart Card */
.chart-card {
  background: var(--bg-card);
  border-radius: var(--radius-xl);
  padding: 28px;
  box-shadow: var(--shadow-lg);
  border: 1px solid var(--border-light);
}
.chart-card-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 24px; flex-wrap: wrap; gap: 10px;
}
.chart-card-title {
  font-size: 1.1rem; font-weight: 800;
  color: var(--text-primary);
  display: flex; align-items: center; gap: 10px;
}
.chart-card-title i { color: var(--primary); }
.chart-card-desc { font-size: .82rem; color: var(--text-muted); margin-top: 2px; }
</style>
</head>
<body>
<?php include 'includes/partials/navbar-publik.php'; ?>

<!-- PAGE HEADER -->
<div style="background:linear-gradient(135deg,#0f3d26,#1a7a4a);padding:48px 0 32px">
  <div class="container">
    <div class="hero-badge"><i class="fas fa-chart-bar"></i> Visualisasi Data Keuangan</div>
    <h1 style="font-size:1.8rem;font-weight:800;color:#fff;margin-top:12px;margin-bottom:8px">Grafik Keuangan Kas Masjid</h1>
    <p style="color:rgba(255,255,255,.75)"><?= MASJID_NAME ?> &bull; Tahun <?= $tahun ?></p>
  </div>
</div>

<div class="container" style="padding-top:36px;padding-bottom:72px">

  <!-- Filter Tahun -->
  <div class="filter-bar mb-3">
    <span class="filter-label"><i class="fas fa-calendar"></i> Tahun:</span>
    <form method="GET" style="display:flex;align-items:center;gap:12px;flex:1;flex-wrap:wrap">
      <select name="tahun" class="form-control form-select" style="max-width:140px" onchange="this.form.submit()">
        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
          <option value="<?= $y ?>" <?= $tahun==$y?'selected':'' ?>><?= $y ?></option>
        <?php endfor; ?>
      </select>
      <span style="font-size:.875rem;color:var(--text-muted)">Menampilkan data keuangan tahun <?= $tahun ?></span>
    </form>
  </div>

  <!-- Summary Cards -->
  <div class="grid-3 mb-3">
    <div class="stat-card green animate-fadeIn">
      <div class="stat-icon"><i class="fas fa-arrow-down"></i></div>
      <div class="stat-label">Total Pemasukan <?= $tahun ?></div>
      <div class="stat-value"><?= formatRupiah($s['m']) ?></div>
    </div>
    <div class="stat-card red animate-fadeIn delay-1">
      <div class="stat-icon"><i class="fas fa-arrow-up"></i></div>
      <div class="stat-label">Total Pengeluaran <?= $tahun ?></div>
      <div class="stat-value"><?= formatRupiah($s['k']) ?></div>
    </div>
    <div class="stat-card gold animate-fadeIn delay-2">
      <div class="stat-icon"><i class="fas fa-balance-scale"></i></div>
      <div class="stat-label">Saldo Tahun <?= $tahun ?></div>
      <div class="stat-value <?= ($s['m']-$s['k'])>=0?'text-success':'text-danger' ?>"><?= formatRupiah($s['m']-$s['k']) ?></div>
    </div>
  </div>

  <!-- ===== GRAFIK SLIDESHOW ===== -->
  <div class="chart-card animate-fadeIn delay-3">

    <!-- Progress bar autoplay -->
    <div class="slide-progress-wrap">
      <div class="slide-progress-bar" id="slideProgress"></div>
    </div>

    <!-- Nav -->
    <div class="slide-nav">
      <div>
        <span class="slide-badge" id="slideBadge"><i class="fas fa-chart-bar"></i> Grafik 1 dari 4</span>
        <div class="chart-card-desc" id="slideDesc" style="margin-top:8px">Perbandingan pemasukan dan pengeluaran per bulan</div>
      </div>
      <div style="display:flex;align-items:center;gap:14px">
        <span class="slide-counter" id="slideCounter">1 / 4</span>
        <div class="slide-nav-btns">
          <button class="slide-nav-btn" id="btnPrev" onclick="changeSlide(-1)"><i class="fas fa-chevron-left"></i></button>
          <button class="slide-nav-btn" id="btnPause" onclick="togglePause()"><i class="fas fa-pause" id="pauseIcon"></i></button>
          <button class="slide-nav-btn" id="btnNext" onclick="changeSlide(1)"><i class="fas fa-chevron-right"></i></button>
        </div>
      </div>
    </div>

    <!-- SLIDE 1: Bar Chart Pemasukan vs Pengeluaran -->
    <div class="grafik-slide active" id="slide-0">
      <div class="chart-card-header">
        <div>
          <div class="chart-card-title"><i class="fas fa-chart-bar"></i> Pemasukan vs Pengeluaran per Bulan</div>
          <div class="chart-card-desc">Perbandingan total pemasukan dan pengeluaran setiap bulan tahun <?= $tahun ?></div>
        </div>
      </div>
      <div style="position:relative;height:340px"><canvas id="barChart"></canvas></div>
    </div>

    <!-- SLIDE 2: Line Chart Saldo Kumulatif -->
    <div class="grafik-slide" id="slide-1">
      <div class="chart-card-header">
        <div>
          <div class="chart-card-title"><i class="fas fa-chart-line"></i> Perkembangan Saldo Kumulatif</div>
          <div class="chart-card-desc">Tren saldo kas masjid yang terakumulasi sepanjang tahun <?= $tahun ?></div>
        </div>
      </div>
      <div style="position:relative;height:340px"><canvas id="lineChart"></canvas></div>
    </div>

    <!-- SLIDE 3: Pie Chart Pemasukan -->
    <div class="grafik-slide" id="slide-2">
      <div class="chart-card-header">
        <div>
          <div class="chart-card-title"><i class="fas fa-chart-pie"></i> Proporsi Sumber Pemasukan</div>
          <div class="chart-card-desc">Distribusi pemasukan berdasarkan kategori tahun <?= $tahun ?></div>
        </div>
      </div>
      <?php if (count($pid)): ?>
      <div style="display:flex;align-items:center;justify-content:center;gap:40px;flex-wrap:wrap;padding:10px 0">
        <div style="position:relative;width:280px;height:280px"><canvas id="pieIncome"></canvas></div>
        <div id="legIncome" style="display:flex;flex-direction:column;gap:10px;max-width:300px"></div>
      </div>
      <?php else: ?>
      <div class="empty-state"><div class="es-icon"><i class="fas fa-chart-pie"></i></div><h3>Belum ada data pemasukan <?= $tahun ?></h3></div>
      <?php endif; ?>
    </div>

    <!-- SLIDE 4: Pie Chart Pengeluaran -->
    <div class="grafik-slide" id="slide-3">
      <div class="chart-card-header">
        <div>
          <div class="chart-card-title"><i class="fas fa-chart-pie"></i> Proporsi Kategori Pengeluaran</div>
          <div class="chart-card-desc">Distribusi pengeluaran berdasarkan kategori tahun <?= $tahun ?></div>
        </div>
      </div>
      <?php if (count($ped)): ?>
      <div style="display:flex;align-items:center;justify-content:center;gap:40px;flex-wrap:wrap;padding:10px 0">
        <div style="position:relative;width:280px;height:280px"><canvas id="pieExpense"></canvas></div>
        <div id="legExpense" style="display:flex;flex-direction:column;gap:10px;max-width:300px"></div>
      </div>
      <?php else: ?>
      <div class="empty-state"><div class="es-icon"><i class="fas fa-chart-pie"></i></div><h3>Belum ada data pengeluaran <?= $tahun ?></h3></div>
      <?php endif; ?>
    </div>

    <!-- Dot Indicators -->
    <div class="slide-indicators">
      <button class="slide-dot active" onclick="goToSlide(0)"></button>
      <button class="slide-dot" onclick="goToSlide(1)"></button>
      <button class="slide-dot" onclick="goToSlide(2)"></button>
      <button class="slide-dot" onclick="goToSlide(3)"></button>
    </div>
  </div>

</div><!-- /container -->

<?php include 'includes/partials/footer-publik.php'; ?>

<script>
// ===== DATA PHP =====
const LABELS  = <?= json_encode($blnlbl) ?>;
const MASUK   = <?= json_encode($bm) ?>;
const KELUAR  = <?= json_encode($bk) ?>;
const SALDO   = <?= json_encode($sl) ?>;
const PIE_IL  = <?= json_encode($pil) ?>;
const PIE_ID  = <?= json_encode($pid) ?>;
const PIE_EL  = <?= json_encode($pel) ?>;
const PIE_ED  = <?= json_encode($ped) ?>;

const fmtRp  = v => 'Rp ' + new Intl.NumberFormat('id-ID').format(v);
const CG = ['#1a7a4a','#22a05e','#c9a84c','#3b82f6','#f59e0b','#8b5cf6','#ec4899','#14b8a6'];
const CR = ['#ef4444','#f87171','#dc2626','#b91c1c','#fca5a5','#fecaca','#ff8080','#c53030'];

Chart.defaults.font.family = "'Poppins', sans-serif";
Chart.defaults.color = '#6b7280';
Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15,61,38,.93)';
Chart.defaults.plugins.tooltip.titleColor      = '#fff';
Chart.defaults.plugins.tooltip.bodyColor       = 'rgba(255,255,255,.85)';
Chart.defaults.plugins.tooltip.padding         = 12;
Chart.defaults.plugins.tooltip.cornerRadius    = 8;

// ===== BUILD CHARTS =====
// Bar Chart
new Chart(document.getElementById('barChart').getContext('2d'), {
  type: 'bar',
  data: {
    labels: LABELS,
    datasets: [
      { label:'Pemasukan',   data:MASUK,  backgroundColor:'rgba(26,122,74,.85)',  borderRadius:8, borderSkipped:false },
      { label:'Pengeluaran', data:KELUAR, backgroundColor:'rgba(239,68,68,.75)',  borderRadius:8, borderSkipped:false }
    ]
  },
  options: {
    responsive:true, maintainAspectRatio:false,
    interaction:{ mode:'index', intersect:false },
    plugins:{
      legend:{ position:'bottom', labels:{ padding:20, font:{size:12}, usePointStyle:true }},
      tooltip:{ callbacks:{ label: c => ` ${c.dataset.label}: ${fmtRp(c.raw)}` }}
    },
    scales:{
      x:{ grid:{ display:false }},
      y:{ grid:{ color:'rgba(0,0,0,.05)' }, ticks:{ callback: v => fmtRp(v) }}
    },
    animation:{ duration:900, easing:'easeInOutQuart' }
  }
});

// Line Chart
new Chart(document.getElementById('lineChart').getContext('2d'), {
  type: 'line',
  data: {
    labels: LABELS,
    datasets: [{
      label:'Saldo Kumulatif', data:SALDO,
      borderColor:'#1a7a4a', backgroundColor:'rgba(26,122,74,.08)',
      borderWidth:3, fill:true, tension:.4,
      pointBackgroundColor:'#1a7a4a', pointBorderColor:'#fff',
      pointBorderWidth:2, pointRadius:6, pointHoverRadius:9
    }]
  },
  options: {
    responsive:true, maintainAspectRatio:false,
    plugins:{
      legend:{ display:false },
      tooltip:{ callbacks:{ label: c => ` Saldo: ${fmtRp(c.raw)}` }}
    },
    scales:{
      x:{ grid:{ display:false }},
      y:{ grid:{ color:'rgba(0,0,0,.05)' }, ticks:{ callback: v => fmtRp(v) }}
    },
    animation:{ duration:900 }
  }
});

// Pie charts helper
function buildPie(cid, lid, labels, data, colors) {
  if (!labels.length) return;
  new Chart(document.getElementById(cid).getContext('2d'), {
    type: 'doughnut',
    data: { labels, datasets:[{ data, backgroundColor:colors, borderWidth:3, borderColor:'#fff', hoverOffset:14 }] },
    options: {
      responsive:true, maintainAspectRatio:true, cutout:'58%',
      plugins:{
        legend:{ display:false },
        tooltip:{ callbacks:{ label: c => ` ${c.label}: ${fmtRp(c.raw)}` }}
      },
      animation:{ duration:900, animateRotate:true, animateScale:true }
    }
  });
  const leg = document.getElementById(lid);
  const total = data.reduce((a,b)=>a+b,0);
  labels.forEach((l,i) => {
    const pct = total > 0 ? ((data[i]/total)*100).toFixed(1) : 0;
    leg.innerHTML += `
      <div style="display:flex;align-items:center;gap:10px">
        <span style="width:14px;height:14px;border-radius:4px;background:${colors[i]};flex-shrink:0;display:inline-block"></span>
        <div>
          <div style="font-size:.82rem;font-weight:600;color:var(--text-primary)">${l}</div>
          <div style="font-size:.75rem;color:var(--text-muted)">${fmtRp(data[i])} &bull; ${pct}%</div>
        </div>
      </div>`;
  });
}

buildPie('pieIncome',  'legIncome',  PIE_IL, PIE_ID, CG);
buildPie('pieExpense', 'legExpense', PIE_EL, PIE_ED, CR);

// ===== SLIDESHOW ENGINE =====
const SLIDE_DURATION = 10000; // 10 detik
const slides = document.querySelectorAll('.grafik-slide');
const dots   = document.querySelectorAll('.slide-dot');
const progress = document.getElementById('slideProgress');
let currentSlide = 0;
let isPaused     = false;
let timer        = null;
let progressTimer = null;
let progressStart = null;

const SLIDE_INFO = [
  { icon:'fas fa-chart-bar',  title:'Pemasukan vs Pengeluaran', desc:'Perbandingan total pemasukan dan pengeluaran per bulan' },
  { icon:'fas fa-chart-line', title:'Saldo Kumulatif',           desc:'Tren pertumbuhan saldo kas sepanjang tahun' },
  { icon:'fas fa-chart-pie',  title:'Proporsi Pemasukan',        desc:'Distribusi sumber pemasukan berdasarkan kategori' },
  { icon:'fas fa-chart-pie',  title:'Proporsi Pengeluaran',      desc:'Distribusi pengeluaran berdasarkan kategori' },
];

function goToSlide(n) {
  slides[currentSlide].classList.remove('active');
  dots[currentSlide].classList.remove('active');
  currentSlide = (n + slides.length) % slides.length;
  slides[currentSlide].classList.add('active');
  dots[currentSlide].classList.add('active');

  // Update info
  const info = SLIDE_INFO[currentSlide];
  document.getElementById('slideBadge').innerHTML    = `<i class="${info.icon}"></i> ${info.title}`;
  document.getElementById('slideDesc').textContent   = info.desc;
  document.getElementById('slideCounter').textContent = `${currentSlide+1} / ${slides.length}`;

  resetProgress();
}

function changeSlide(dir) {
  goToSlide(currentSlide + dir);
  if (!isPaused) startAutoPlay();
}

function startAutoPlay() {
  clearTimeout(timer);
  timer = setTimeout(() => {
    if (!isPaused) { goToSlide(currentSlide + 1); startAutoPlay(); }
  }, SLIDE_DURATION);
  animateProgress();
}

function animateProgress() {
  clearInterval(progressTimer);
  progress.style.transition = 'none';
  progress.style.width = '0%';
  setTimeout(() => {
    progress.style.transition = `width ${SLIDE_DURATION}ms linear`;
    progress.style.width = '100%';
  }, 30);
}

function resetProgress() {
  clearInterval(progressTimer);
  progress.style.transition = 'none';
  progress.style.width = '0%';
  if (!isPaused) {
    setTimeout(() => {
      progress.style.transition = `width ${SLIDE_DURATION}ms linear`;
      progress.style.width = '100%';
    }, 30);
  }
}

function togglePause() {
  isPaused = !isPaused;
  const icon = document.getElementById('pauseIcon');
  if (isPaused) {
    icon.className = 'fas fa-play';
    clearTimeout(timer);
    // Freeze progress bar
    const computed = getComputedStyle(progress).width;
    progress.style.transition = 'none';
    progress.style.width = computed;
  } else {
    icon.className = 'fas fa-pause';
    startAutoPlay();
  }
}

// Start autoplay on load
startAutoPlay();

// Navbar toggle
document.getElementById('navToggle').addEventListener('click', () => {
  document.getElementById('navLinks').classList.toggle('open');
});

// Keyboard navigation
document.addEventListener('keydown', e => {
  if (e.key === 'ArrowRight') changeSlide(1);
  if (e.key === 'ArrowLeft')  changeSlide(-1);
  if (e.key === ' ') { e.preventDefault(); togglePause(); }
});

// Touch/Swipe support
let touchStartX = 0;
document.querySelector('.grafik-wrapper, .chart-card')?.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, {passive:true});
document.querySelector('.grafik-wrapper, .chart-card')?.addEventListener('touchend', e => {
  const diff = touchStartX - e.changedTouches[0].clientX;
  if (Math.abs(diff) > 50) changeSlide(diff > 0 ? 1 : -1);
});
</script>
</body>
</html>
