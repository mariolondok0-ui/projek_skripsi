<?php
require_once 'includes/config.php';
$total_masuk  = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='masuk' AND deleted_at IS NULL")->fetch_assoc()['t'];
$total_keluar = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='keluar' AND deleted_at IS NULL")->fetch_assoc()['t'];
$saldo        = $total_masuk - $total_keluar;
$total_trx    = (int)$conn->query("SELECT COUNT(*) as t FROM transaksi WHERE deleted_at IS NULL")->fetch_assoc()['t'];
$bln          = date('Y-m');
$tahun        = (int)date('Y');
$masuk_bln    = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='masuk' AND deleted_at IS NULL AND DATE_FORMAT(tanggal,'%Y-%m')='$bln'")->fetch_assoc()['t'];
$keluar_bln   = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='keluar' AND deleted_at IS NULL AND DATE_FORMAT(tanggal,'%Y-%m')='$bln'")->fetch_assoc()['t'];
$trx_bln      = (int)$conn->query("SELECT COUNT(*) as t FROM transaksi WHERE deleted_at IS NULL AND DATE_FORMAT(tanggal,'%Y-%m')='$bln'")->fetch_assoc()['t'];

$chart_labels = $chart_masuk = $chart_keluar = [];
for ($i = 5; $i >= 0; $i--) {
    $b = date('Y-m', strtotime("-$i month"));
    $chart_labels[] = date('M', strtotime("-$i month"));
    $chart_masuk[]  = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='masuk' AND deleted_at IS NULL AND DATE_FORMAT(tanggal,'%Y-%m')='$b'")->fetch_assoc()['t'];
    $chart_keluar[] = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='keluar' AND deleted_at IS NULL AND DATE_FORMAT(tanggal,'%Y-%m')='$b'")->fetch_assoc()['t'];
}

$line_labels = $line_saldo = []; $kum = 0;
for ($m = 1; $m <= 12; $m++) {
    $b  = sprintf('%04d-%02d', $tahun, $m);
    $mk = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='masuk' AND deleted_at IS NULL AND DATE_FORMAT(tanggal,'%Y-%m')='$b'")->fetch_assoc()['t'];
    $kl = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='keluar' AND deleted_at IS NULL AND DATE_FORMAT(tanggal,'%Y-%m')='$b'")->fetch_assoc()['t'];
    $kum += ($mk - $kl); $line_labels[] = date('M', mktime(0,0,0,$m,1)); $line_saldo[] = $kum;
}

$pi  = $conn->query("SELECT k.nama_kategori,COALESCE(SUM(t.jumlah),0) as total FROM transaksi t JOIN kategori k ON t.kategori_id=k.id WHERE t.jenis='masuk' AND t.deleted_at IS NULL AND YEAR(t.tanggal)=$tahun GROUP BY k.id ORDER BY total DESC LIMIT 8");
$pil = $pid = [];
while ($r = $pi->fetch_assoc()) { $pil[] = $r['nama_kategori']; $pid[] = (float)$r['total']; }

$pe  = $conn->query("SELECT k.nama_kategori,COALESCE(SUM(t.jumlah),0) as total FROM transaksi t JOIN kategori k ON t.kategori_id=k.id WHERE t.jenis='keluar' AND t.deleted_at IS NULL AND YEAR(t.tanggal)=$tahun GROUP BY k.id ORDER BY total DESC LIMIT 8");
$pel = $ped = [];
while ($r = $pe->fetch_assoc()) { $pel[] = $r['nama_kategori']; $ped[] = (float)$r['total']; }

$trx_terbaru = $conn->query("SELECT t.*,k.nama_kategori FROM transaksi t JOIN kategori k ON t.kategori_id=k.id WHERE t.deleted_at IS NULL ORDER BY t.tanggal DESC,t.id DESC LIMIT 6");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= APP_NAME ?> – Transparansi Keuangan Masjid</title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css?v=1786264272">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
/* ===== LANDING PAGE ===== */
.hero-section{
  min-height:100svh;display:flex;align-items:center;position:relative;overflow:hidden;padding:80px 0 56px;
  background-color:#0a1f35;
}
.hero-section::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/svg%3E")}
.hero-c1{position:absolute;top:-80px;right:-80px;width:300px;height:300px;background:rgba(255,255,255,.04);border-radius:50%;pointer-events:none}
.hero-c2{position:absolute;bottom:-60px;left:-60px;width:220px;height:220px;background:rgba(201,168,76,.08);border-radius:50%;pointer-events:none}
.hero-badge-pub{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.12);color:rgba(255,255,255,.9);padding:6px 16px;border-radius:99px;font-size:.78rem;font-weight:600;border:1px solid rgba(255,255,255,.2);margin-bottom:20px;animation:fadeIn .8s ease}
.hero-title{font-size:clamp(1.9rem,5vw,3.2rem);font-weight:800;color:#fff;line-height:1.2;margin-bottom:16px;animation:fadeIn .8s ease .1s both}
.hero-title span{color:#f0c96a}
.hero-desc{font-size:clamp(.88rem,2vw,1rem);color:rgba(255,255,255,.8);max-width:540px;line-height:1.75;margin-bottom:32px;animation:fadeIn .8s ease .2s both}
.hero-btns{display:flex;gap:12px;flex-wrap:wrap;animation:fadeIn .8s ease .3s both}
.hbtn-gold{background:var(--secondary);color:#fff;padding:12px 24px;border-radius:12px;font-weight:700;font-size:.9rem;display:inline-flex;align-items:center;gap:8px;box-shadow:0 4px 16px rgba(201,168,76,.4);transition:var(--transition);text-decoration:none}
.hbtn-gold:hover{background:var(--secondary-light);transform:translateY(-2px)}
.hbtn-ghost{background:rgba(255,255,255,.12);color:#fff;padding:12px 24px;border-radius:12px;font-weight:700;font-size:.9rem;display:inline-flex;align-items:center;gap:8px;border:1.5px solid rgba(255,255,255,.3);transition:var(--transition);text-decoration:none}
.hbtn-ghost:hover{background:rgba(255,255,255,.22);transform:translateY(-2px)}
.hero-stats-bar{display:flex;margin-top:36px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);border-radius:16px;overflow:hidden;animation:fadeIn .8s ease .4s both}
.hstat{flex:1;padding:14px 12px;text-align:center;border-right:1px solid rgba(255,255,255,.12)}
.hstat:last-child{border-right:none}
.hstat .hv{font-size:clamp(.9rem,2.5vw,1.3rem);font-weight:800;color:#fff}
.hstat .hl{font-size:.67rem;color:rgba(255,255,255,.65);margin-top:3px}
.saldo-card-pub{background:linear-gradient(135deg,#1e6eb5,#5ba3d9);border-radius:22px;padding:24px 20px;color:#fff;position:relative;overflow:hidden;box-shadow:0 12px 36px rgba(30,110,181,.35)}
.saldo-card-pub::before{content:'';position:absolute;top:-30px;right:-30px;width:110px;height:110px;background:rgba(255,255,255,.07);border-radius:50%}
.mstat{background:var(--bg-card);border-radius:14px;padding:15px;box-shadow:var(--shadow);border:1px solid var(--border-light);display:flex;align-items:center;gap:12px;transition:var(--transition)}
.mstat:hover{transform:translateY(-2px);box-shadow:var(--shadow-lg)}
.mstat .mi{width:40px;height:40px;border-radius:11px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:1rem}
.mstat .mv{font-size:.95rem;font-weight:800;color:var(--text-primary)}
.mstat .ml{font-size:.68rem;color:var(--text-muted);margin-top:1px}
.trx-row{display:flex;align-items:center;gap:12px;padding:13px 15px;background:var(--bg-card);border-radius:14px;box-shadow:var(--shadow-sm);border:1px solid var(--border-light);transition:var(--transition)}
.trx-row:hover{box-shadow:var(--shadow);transform:translateY(-1px)}
.trx-row .ti{width:40px;height:40px;border-radius:11px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:.9rem}
.trx-row .tn{font-size:.85rem;font-weight:600;color:var(--text-primary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.trx-row .tm{font-size:.7rem;color:var(--text-muted);margin-top:1px}
.trx-row .ta{font-size:.9rem;font-weight:800;flex-shrink:0;text-align:right}
.fitur-card{background:var(--bg-card);border-radius:18px;padding:22px;box-shadow:var(--shadow);border:1px solid var(--border-light);transition:var(--transition);text-align:center}
.fitur-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-lg)}
.fitur-card .fi{width:52px;height:52px;border-radius:14px;margin:0 auto 12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem}
.pub-sec{padding:52px 0}
.sec-tag{display:inline-flex;align-items:center;gap:7px;background:rgba(30,110,181,.1);color:var(--primary);padding:5px 14px;border-radius:99px;font-size:.75rem;font-weight:700;margin-bottom:10px;border:1px solid rgba(30,110,181,.15)}
.sec-h{font-size:clamp(1.4rem,3vw,1.9rem);font-weight:800;color:var(--text-primary);margin-bottom:8px}
.sec-h span{color:var(--primary)}
.sec-sub{color:var(--text-secondary);font-size:.875rem;max-width:500px;margin:0 auto;line-height:1.7}
.grafik-slide{display:none;animation:slideInChart .5s cubic-bezier(.4,0,.2,1)}
.grafik-slide.active{display:block}
@keyframes slideInChart{from{opacity:0;transform:translateX(40px) scale(.97)}to{opacity:1;transform:translateX(0) scale(1)}}
.sp-wrap{height:3px;background:rgba(30,110,181,.15);border-radius:99px;overflow:hidden;margin-bottom:14px}
.sp-bar{height:100%;background:var(--primary);border-radius:99px;width:0%;transition:width linear}
.sdot{width:8px;height:8px;border-radius:50%;background:var(--border);cursor:pointer;transition:var(--transition);border:none}
.sdot.active{background:var(--primary);width:22px;border-radius:99px}
.snbtn{width:32px;height:32px;border-radius:50%;background:var(--bg-main);border:1.5px solid var(--border);color:var(--text-secondary);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:var(--transition-fast);font-size:.78rem}
.snbtn:hover{background:var(--primary);border-color:var(--primary);color:#fff}
.piewrap{display:flex;align-items:center;justify-content:center;gap:20px;flex-wrap:wrap;padding:6px 0}
.pleg{display:flex;align-items:center;gap:7px;font-size:.78rem;color:var(--text-secondary)}
.pdot{width:11px;height:11px;border-radius:3px;flex-shrink:0;display:inline-block}
@media(max-width:768px){
  .hero-section{padding:70px 0 48px;min-height:auto}
  .hstat{padding:10px 6px}
  .hstat .hv{font-size:.9rem}
  .hstat .hl{font-size:.62rem}
  .g2{grid-template-columns:1fr!important}
  .g3{grid-template-columns:1fr 1fr!important}
  .piewrap{flex-direction:column}
  .piewrap>div:first-child{width:200px!important;height:200px!important}
}
@media(max-width:480px){
  .hero-btns{flex-direction:column}
  .hbtn-gold,.hbtn-ghost{justify-content:center}
  .g3{grid-template-columns:1fr!important}
}
</style>
</head>
<body>
<?php include 'includes/partials/navbar-publik.php'; ?>

<!-- HERO -->
<section class="hero-section" style="background: linear-gradient(135deg, rgba(10,31,53,0.78) 0%, rgba(30,110,181,0.70) 60%, rgba(91,163,217,0.60) 100%), url('<?= APP_URL ?>/assets/img/masjid.jpg') center center / cover no-repeat; animation:none">
  <div class="hero-c1"></div>
  <div class="hero-c2"></div>
  <div class="container" style="position:relative;z-index:2;width:100%">
    
    <!-- BUNGKUSAN POSISI TENGAH -->
    <div style="display: flex; flex-direction: column; align-items: center; text-align: center;">
      <div class="hero-badge-pub" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(4px);">
          <i class="fas fa-mosque"></i> <?= MASJID_NAME ?>
      </div>
      <h1 class="hero-title" style="color: white; text-shadow: 2px 2px 4px rgba(0,0,0,0.8);">
          Transparansi Keuangan<br><span style="color: #f6d365;">Kas Masjid</span> untuk Jamaah
      </h1>
      <p class="hero-desc" style="color: #f8fafc; text-shadow: 1px 1px 4px rgba(0,0,0,0.8);">
          Pantau pemasukan, pengeluaran, dan saldo kas masjid secara real-time. Informasi terbuka dan dapat diakses oleh seluruh jamaah kapan saja.
      </p>
    </div>
    
    <!-- STATISTIK (Beri jarak atas sedikit agar tidak terlalu mepet dengan teks) -->
    <div class="hero-stats-bar" style="margin-top: 40px;">
      <div class="hstat"><div class="hv" id="heroMasuk">Rp 0</div><div class="hl"><i class="fas fa-arrow-down" style="color:#93c5fd;margin-right:2px"></i>Total Pemasukan</div></div>
      <div class="hstat"><div class="hv" id="heroKeluar">Rp 0</div><div class="hl"><i class="fas fa-arrow-up" style="color:#fca5a5;margin-right:2px"></i>Total Pengeluaran</div></div>
      <div class="hstat"><div class="hv"><?= $total_trx ?></div><div class="hl"><i class="fas fa-exchange-alt" style="color:#93c5fd;margin-right:2px"></i>Total Transaksi</div></div>
    </div>
  </div>
</section>

<!-- RINGKASAN -->
<section class="pub-sec" style="background:#fff;padding-top:48px;padding-bottom:48px">
  <div class="container">
    <div style="text-align:center;margin-bottom:28px">
      <div class="sec-tag"><i class="fas fa-wallet"></i> Ringkasan Keuangan</div>
      <h2 class="sec-h">Kondisi Kas <span>Saat Ini</span></h2>
      <p class="sec-sub">Data real-time keuangan <?= MASJID_NAME ?> untuk seluruh jamaah</p>
    </div>
    <div style="display:grid;grid-template-columns:1.1fr 1fr;gap:20px;align-items:start" class="g2">
      <!-- Saldo Card -->
      <div class="saldo-card-pub animate-fadeIn">
        <div style="position:relative;z-index:1">
          <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px">
            <div style="width:46px;height:46px;background:rgba(255,255,255,.2);border-radius:13px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;animation:float 3s ease-in-out infinite;flex-shrink:0"><i class="fas fa-wallet"></i></div>
            <div>
              <div style="font-size:.75rem;opacity:.8;margin-bottom:4px">Saldo Kas Masjid</div>
              <div style="font-size:clamp(1.3rem,3vw,1.8rem);font-weight:800"><?= formatRupiah($saldo) ?></div>
            </div>
          </div>
          <div style="background:rgba(255,255,255,.12);border-radius:11px;padding:12px 14px;border:1px solid rgba(255,255,255,.15);display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
            <div>
              <div style="font-size:.67rem;opacity:.75"><i class="fas fa-arrow-down" style="color:#93c5fd;margin-right:3px"></i>Masuk Bulan Ini</div>
              <div style="font-size:.88rem;font-weight:700;margin-top:2px"><?= formatRupiah($masuk_bln) ?></div>
            </div>
            <div>
              <div style="font-size:.67rem;opacity:.75"><i class="fas fa-arrow-up" style="color:#fca5a5;margin-right:3px"></i>Keluar Bulan Ini</div>
              <div style="font-size:.88rem;font-weight:700;margin-top:2px"><?= formatRupiah($keluar_bln) ?></div>
            </div>
          </div>
          <div style="font-size:.7rem;opacity:.65"><i class="fas fa-sync-alt" style="margin-right:4px"></i>Diperbarui: <?= date('d M Y, H:i') ?></div>
          <div style="display:flex;gap:8px;margin-top:14px">
            <a href="laporan-publik.php" style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px;background:rgba(255,255,255,.15);color:#fff;padding:9px;border-radius:10px;font-size:.76rem;font-weight:600;border:1px solid rgba(255,255,255,.25);text-decoration:none;transition:background .15s" onmouseover="this.style.background='rgba(255,255,255,.25)'" onmouseout="this.style.background='rgba(255,255,255,.15)'"><i class="fas fa-file-alt"></i> Laporan</a>
            <a href="grafik-publik.php" style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px;background:rgba(255,255,255,.15);color:#fff;padding:9px;border-radius:10px;font-size:.76rem;font-weight:600;border:1px solid rgba(255,255,255,.25);text-decoration:none;transition:background .15s" onmouseover="this.style.background='rgba(255,255,255,.25)'" onmouseout="this.style.background='rgba(255,255,255,.15)'"><i class="fas fa-chart-pie"></i> Grafik</a>
          </div>
        </div>
      </div>
      <!-- Mini Stats -->
      <div style="display:flex;flex-direction:column;gap:11px">
        <?php
        $stats = [
          ['fas fa-arrow-down','rgba(59,130,246,.1)','var(--success)',formatRupiah($masuk_bln),'Pemasukan '.date('F Y')],
          ['fas fa-arrow-up','rgba(239,68,68,.1)','var(--danger)',formatRupiah($keluar_bln),'Pengeluaran '.date('F Y')],
          ['fas fa-exchange-alt','rgba(59,130,246,.1)','var(--info)',$trx_bln.' transaksi','Aktivitas '.date('F Y')],
          ['fas fa-list','rgba(201,168,76,.1)','var(--secondary)',$total_trx.' transaksi','Total semua periode'],
        ];
        foreach ($stats as $i => [$ico,$bg,$clr,$val,$lbl]): ?>
        <div class="mstat animate-fadeIn delay-<?= $i+1 ?>">
          <div class="mi" style="background:<?= $bg ?>;color:<?= $clr ?>"><i class="<?= $ico ?>"></i></div>
          <div style="flex:1;min-width:0"><div class="mv" style="color:<?= $clr ?>"><?= $val ?></div><div class="ml"><?= $lbl ?></div></div>
          <i class="fas fa-chevron-right" style="color:var(--border);font-size:.68rem"></i>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- GRAFIK SLIDESHOW -->
<section class="pub-sec" style="background:linear-gradient(180deg,#f0f4f8,#e8f0f8);padding-top:44px;padding-bottom:44px" id="grafik-section">
  <div class="container">
    <div style="text-align:center;margin-bottom:24px">
      <div class="sec-tag"><i class="fas fa-chart-bar"></i> Visualisasi Data</div>
      <h2 class="sec-h">Grafik <span>Keuangan Masjid</span></h2>
      <p class="sec-sub">Grafik interaktif berganti otomatis setiap 10 detik – klik panah atau dot untuk navigasi</p>
    </div>
    <div class="card animate-fadeIn" style="padding:22px">
      <div class="sp-wrap"><div class="sp-bar" id="spBar"></div></div>
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:18px">
        <div>
          <div style="display:inline-flex;align-items:center;gap:7px;background:var(--primary);color:#fff;padding:5px 14px;border-radius:99px;font-size:.78rem;font-weight:700"><i id="slideIcon" class="fas fa-chart-bar"></i> <span id="slideTitle">Pemasukan vs Pengeluaran</span></div>
          <div style="font-size:.76rem;color:var(--text-muted);margin-top:7px" id="slideDesc">Perbandingan 6 bulan terakhir</div>
        </div>
        <div style="display:flex;align-items:center;gap:10px">
          <span style="font-size:.72rem;color:rgba(255,255,255,.6);background:rgba(255,255,255,.1);padding:3px 10px;border-radius:99px" id="slideCtr">1 / 4</span>
        </div>
      </div>
      <div class="grafik-slide active" id="slide-0"><div style="position:relative;height:280px"><canvas id="barChart"></canvas></div></div>
      <div class="grafik-slide" id="slide-1"><div style="position:relative;height:280px"><canvas id="lineChart"></canvas></div></div>
      <div class="grafik-slide" id="slide-2">
        <?php if(count($pid)):?>
        <div class="piewrap"><div style="position:relative;width:240px;height:240px;flex-shrink:0"><canvas id="pieIncome"></canvas></div><div id="legIncome" style="display:flex;flex-direction:column;gap:8px;max-width:280px"></div></div>
        <?php else:?><div class="empty-state"><div class="es-icon"><i class="fas fa-chart-pie"></i></div><h3>Belum ada data</h3></div><?php endif;?>
      </div>
      <div class="grafik-slide" id="slide-3">
        <?php if(count($ped)):?>
        <div class="piewrap"><div style="position:relative;width:240px;height:240px;flex-shrink:0"><canvas id="pieExpense"></canvas></div><div id="legExpense" style="display:flex;flex-direction:column;gap:8px;max-width:280px"></div></div>
        <?php else:?><div class="empty-state"><div class="es-icon"><i class="fas fa-chart-pie"></i></div><h3>Belum ada data</h3></div><?php endif;?>
      </div>
      <div style="display:flex;justify-content:center;gap:8px;margin-top:20px">
        <button class="sdot active" onclick="goToSlide(0)"></button>
        <button class="sdot" onclick="goToSlide(1)"></button>
        <button class="sdot" onclick="goToSlide(2)"></button>
        <button class="sdot" onclick="goToSlide(3)"></button>
      </div>
    </div>
  </div>
</section>

<!-- TRANSAKSI TERBARU -->
<section class="pub-sec" style="background:#fff;padding-top:44px;padding-bottom:44px">
  <div class="container">
    <div style="text-align:center;margin-bottom:24px">
      <div class="sec-tag"><i class="fas fa-history"></i> Riwayat</div>
      <h2 class="sec-h">Transaksi <span>Terbaru</span></h2>
      <p class="sec-sub">Catatan kas masjid terkini yang dapat diakses oleh seluruh jamaah</p>
    </div>
    <div style="display:flex;flex-direction:column;gap:10px" class="animate-fadeIn">
      <?php if($trx_terbaru->num_rows): while($r=$trx_terbaru->fetch_assoc()):
        $isMasuk = $r['jenis']==='masuk'; ?>
      <div class="trx-row">
        <div class="ti" style="background:<?= $isMasuk?'rgba(59,130,246,.1)':'rgba(239,68,68,.1)' ?>;color:<?= $isMasuk?'var(--success)':'var(--danger)' ?>">
          <i class="fas fa-arrow-<?= $isMasuk?'down':'up' ?>"></i>
        </div>
        <div style="flex:1;min-width:0">
          <div class="tn"><?= htmlspecialchars($r['keterangan']) ?></div>
          <div class="tm">
            <i class="fas fa-calendar-alt" style="margin-right:3px"></i><?= date('d M Y',strtotime($r['tanggal'])) ?>
            &bull; <span class="badge badge-primary" style="font-size:.65rem;padding:2px 8px"><?= htmlspecialchars($r['nama_kategori']) ?></span>
          </div>
        </div>
        <div class="ta <?= $isMasuk?'text-success':'text-danger' ?>">
          <?= $isMasuk?'+':'-' ?>Rp<?= number_format($r['jumlah'],0,',','.') ?>
        </div>
      </div>
      <?php endwhile; else: ?>
      <div class="empty-state"><div class="es-icon"><i class="fas fa-inbox"></i></div><h3>Belum ada transaksi</h3></div>
      <?php endif; ?>
    </div>
    <div style="text-align:center;margin-top:22px">
      <a href="laporan-publik.php" class="btn btn-outline"><i class="fas fa-list"></i> Lihat Semua Transaksi</a>
    </div>
  </div>
</section>

<!-- FITUR TRANSPARANSI -->
<section class="pub-sec" style="background:linear-gradient(135deg,#f0f5fa,#e8f0f8);padding-top:44px;padding-bottom:44px">
  <div class="container">
    <div style="text-align:center;margin-bottom:28px">
      <div class="sec-tag"><i class="fas fa-shield-alt"></i> Komitmen</div>
      <h2 class="sec-h">Transparansi <span>Keuangan</span></h2>
      <p class="sec-sub">Pengelolaan keuangan masjid yang terbuka dan dapat dipertanggungjawabkan kepada jamaah</p>
    </div>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px" class="g3">
      <?php $fiturs=[
        ['fas fa-eye','rgba(30,110,181,.1)','var(--primary)','Keterbukaan','Seluruh transaksi dapat diakses jamaah secara langsung.'],
        ['fas fa-chart-pie','rgba(201,168,76,.1)','var(--secondary)','Visualisasi Data','Grafik interaktif yang mudah dipahami semua kalangan.'],
        ['fas fa-file-alt','rgba(59,130,246,.1)','var(--info)','Laporan Berkala','Tersedia laporan harian, bulanan, dan tahunan.'],
        ['fas fa-shield-alt','rgba(30,110,181,.1)','var(--primary)','Terverifikasi','Setiap transaksi dicatat pengurus yang bertanggung jawab.'],
        ['fas fa-clock','rgba(201,168,76,.1)','var(--secondary)','Real-time','Informasi saldo diperbarui secara langsung.'],
        ['fas fa-mobile-alt','rgba(59,130,246,.1)','var(--info)','Akses HP','Bisa diakses dari perangkat apapun kapan saja.'],
      ]; foreach($fiturs as $i=>[$ico,$bg,$clr,$ttl,$dsc]): ?>
      <div class="fitur-card animate-fadeIn delay-<?= ($i%3)+1 ?>">
        <div class="fi" style="background:<?= $bg ?>;color:<?= $clr ?>"><i class="<?= $ico ?>"></i></div>
        <h3 style="font-size:.9rem;font-weight:700;margin-bottom:6px"><?= $ttl ?></h3>
        <p style="font-size:.8rem;color:var(--text-secondary);line-height:1.6"><?= $dsc ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php include 'includes/partials/footer-publik.php'; ?>

<script>
Chart.defaults.font.family="'Poppins',sans-serif";
Chart.defaults.plugins.tooltip.backgroundColor='rgba(15,45,74,.93)';
Chart.defaults.plugins.tooltip.titleColor='#fff';
Chart.defaults.plugins.tooltip.bodyColor='rgba(255,255,255,.85)';
Chart.defaults.plugins.tooltip.padding=12;
Chart.defaults.plugins.tooltip.cornerRadius=8;
const fmtRp=v=>'Rp '+new Intl.NumberFormat('id-ID').format(v);
const CG=['#1e6eb5','#2d86d4','#c9a84c','#3b82f6','#f59e0b','#8b5cf6','#ec4899','#14b8a6'];
const CR=['#ef4444','#f87171','#dc2626','#b91c1c','#fca5a5','#ff8080','#fecaca','#c53030'];

new Chart(document.getElementById('barChart').getContext('2d'),{
  type:'bar',
  data:{labels:<?=json_encode($chart_labels)?>,datasets:[
    {label:'Pemasukan',data:<?=json_encode($chart_masuk)?>,backgroundColor:'rgba(30,110,181,.85)',borderRadius:7,borderSkipped:false},
    {label:'Pengeluaran',data:<?=json_encode($chart_keluar)?>,backgroundColor:'rgba(239,68,68,.75)',borderRadius:7,borderSkipped:false}
  ]},
  options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},
    plugins:{legend:{position:'bottom',labels:{padding:18,font:{size:11},usePointStyle:true}},tooltip:{callbacks:{label:c=>` ${c.dataset.label}: ${fmtRp(c.raw)}`}}},
    scales:{x:{grid:{display:false}},y:{grid:{color:'rgba(0,0,0,.05)'},ticks:{callback:v=>fmtRp(v)}}},
    animation:{duration:900,easing:'easeInOutQuart'}}
});

new Chart(document.getElementById('lineChart').getContext('2d'),{
  type:'line',
  data:{labels:<?=json_encode($line_labels)?>,datasets:[{
    label:'Saldo Kumulatif',data:<?=json_encode($line_saldo)?>,
    borderColor:'#1e6eb5',backgroundColor:'rgba(30,110,181,.08)',
    borderWidth:3,fill:true,tension:.4,
    pointBackgroundColor:'#1e6eb5',pointBorderColor:'#fff',pointBorderWidth:2,pointRadius:5,pointHoverRadius:8
  }]},
  options:{responsive:true,maintainAspectRatio:false,
    plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>` Saldo: ${fmtRp(c.raw)}`}}},
    scales:{x:{grid:{display:false}},y:{grid:{color:'rgba(0,0,0,.05)'},ticks:{callback:v=>fmtRp(v)}}},
    animation:{duration:900}}
});

function buildPie(cid,lid,labels,data,colors){
  if(!labels.length) return;
  new Chart(document.getElementById(cid).getContext('2d'),{
    type:'doughnut',
    data:{labels,datasets:[{data,backgroundColor:colors,borderWidth:3,borderColor:'#fff',hoverOffset:12}]},
    options:{responsive:true,maintainAspectRatio:true,cutout:'58%',
      plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>` ${c.label}: ${fmtRp(c.raw)}`}}},
      animation:{duration:900,animateRotate:true,animateScale:true}}
  });
  const leg=document.getElementById(lid),total=data.reduce((a,b)=>a+b,0);
  labels.forEach((l,i)=>{
    const pct=total>0?((data[i]/total)*100).toFixed(1):0;
    leg.innerHTML+=`<div class="pleg"><span class="pdot" style="background:${colors[i]}"></span><div><div style="font-size:.8rem;font-weight:600">${l}</div><div style="font-size:.72rem;color:var(--text-muted)">${fmtRp(data[i])} &bull; ${pct}%</div></div></div>`;
  });
}
buildPie('pieIncome','legIncome',<?=json_encode($pil)?>,<?=json_encode($pid)?>,CG);
buildPie('pieExpense','legExpense',<?=json_encode($pel)?>,<?=json_encode($ped)?>,CR);

// Counter
function animCtr(el,target){
  let s=0;const step=target/(1600/16);
  const t=setInterval(()=>{s+=step;if(s>=target){s=target;clearInterval(t);}
    el.textContent='Rp '+new Intl.NumberFormat('id-ID').format(Math.floor(s));},16);
}
window.addEventListener('load',()=>{
  animCtr(document.getElementById('heroMasuk'),<?=$total_masuk?>);
  animCtr(document.getElementById('heroKeluar'),<?=$total_keluar?>);
});

// Slideshow
const DUR=10000,slides=document.querySelectorAll('.grafik-slide'),dots=document.querySelectorAll('.sdot'),spBar=document.getElementById('spBar');
const INFO=[
  {icon:'fas fa-chart-bar',title:'Pemasukan vs Pengeluaran',desc:'Perbandingan 6 bulan terakhir'},
  {icon:'fas fa-chart-line',title:'Saldo Kumulatif',desc:'Tren saldo kas masjid tahun <?=$tahun?>'},
  {icon:'fas fa-chart-pie',title:'Proporsi Pemasukan',desc:'Distribusi sumber pemasukan tahun <?=$tahun?>'},
  {icon:'fas fa-chart-pie',title:'Proporsi Pengeluaran',desc:'Distribusi pengeluaran tahun <?=$tahun?>'},
];
let cur=0,paused=false,timer=null;
function goToSlide(n){
  slides[cur].classList.remove('active');dots[cur].classList.remove('active');
  cur=(n+slides.length)%slides.length;
  slides[cur].classList.add('active');dots[cur].classList.add('active');
  document.getElementById('slideIcon').className=INFO[cur].icon;
  document.getElementById('slideTitle').textContent=INFO[cur].title;
  document.getElementById('slideDesc').textContent=INFO[cur].desc;
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
  paused=!paused;const i=document.getElementById('pauseIco');
  if(paused){i.className='fas fa-play';clearTimeout(timer);const w=getComputedStyle(spBar).width;spBar.style.transition='none';spBar.style.width=w;}
  else{i.className='fas fa-pause';startAuto();resetProg();}
}
startAuto();resetProg();

// Navbar toggle
document.getElementById('navToggle').addEventListener('click',()=>document.getElementById('navLinks').classList.toggle('open'));

// Scroll animation
const obs=new IntersectionObserver(es=>es.forEach(e=>{
  if(e.isIntersecting){e.target.style.opacity='1';e.target.style.transform='translateY(0)';}
}),{threshold:.06});
document.querySelectorAll('.card,.mstat,.saldo-card-pub,.trx-row,.fitur-card,.stat-card').forEach((el,i)=>{
  el.style.opacity='0';el.style.transform='translateY(22px)';
  el.style.transition=`opacity .6s ease ${i*.05}s,transform .6s ease ${i*.05}s`;
  obs.observe(el);
});

// Keyboard
document.addEventListener('keydown',e=>{
  if(e.key==='ArrowRight')changeSlide(1);
  if(e.key==='ArrowLeft')changeSlide(-1);
  if(e.key===' '){e.preventDefault();togglePause();}
});
</script>
</body>
</html>
