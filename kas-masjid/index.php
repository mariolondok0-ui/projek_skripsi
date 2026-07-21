<?php
require_once 'includes/config.php';
$total_masuk  = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='masuk'")->fetch_assoc()['t'];
$total_keluar = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='keluar'")->fetch_assoc()['t'];
$saldo        = $total_masuk - $total_keluar;
$total_trx    = (int)$conn->query("SELECT COUNT(*) as t FROM transaksi")->fetch_assoc()['t'];
$bln          = date('Y-m');
$masuk_bln    = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='masuk'  AND DATE_FORMAT(tanggal,'%Y-%m')='$bln'")->fetch_assoc()['t'];
$keluar_bln   = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='keluar' AND DATE_FORMAT(tanggal,'%Y-%m')='$bln'")->fetch_assoc()['t'];
$trx_terbaru  = $conn->query("SELECT t.*,k.nama_kategori FROM transaksi t JOIN kategori k ON t.kategori_id=k.id ORDER BY t.tanggal DESC,t.id DESC LIMIT 5");
$chart_labels=$chart_masuk=$chart_keluar=[];
for($i=5;$i>=0;$i--){
  $b=date('Y-m',strtotime("-$i month"));
  $chart_labels[]=date('M Y',strtotime("-$i month"));
  $chart_masuk[]=(float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='masuk'  AND DATE_FORMAT(tanggal,'%Y-%m')='$b'")->fetch_assoc()['t'];
  $chart_keluar[]=(float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='keluar' AND DATE_FORMAT(tanggal,'%Y-%m')='$b'")->fetch_assoc()['t'];
}
?>
<!DOCTYPE html><html lang="id"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Beranda – <?= APP_NAME ?></title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head><body>
<?php include 'includes/partials/navbar-publik.php'; ?>

<section class="hero">
  <div class="container hero-content">
    <div class="hero-badge"><i class="fas fa-mosque"></i> <?= MASJID_NAME ?></div>
    <h1>Transparansi Keuangan<br><span>Kas Masjid</span> untuk Jamaah</h1>
    <p>Pantau pemasukan, pengeluaran, dan saldo kas masjid secara real-time. Informasi terbuka dan dapat diakses oleh seluruh jamaah kapan saja.</p>
    <div class="hero-actions">
      <a href="laporan-publik.php" class="btn btn-secondary btn-lg"><i class="fas fa-file-alt"></i> Lihat Laporan</a>
      <a href="grafik-publik.php"  class="btn btn-lg" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.4)"><i class="fas fa-chart-bar"></i> Lihat Grafik</a>
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

<section class="section" style="padding-top:52px">
  <div class="container">
    <div class="grid-3" style="align-items:start">
      <div class="saldo-card">
        <div class="saldo-icon"><i class="fas fa-wallet"></i></div>
        <div class="saldo-label">Saldo Kas Saat Ini</div>
        <div class="saldo-amount"><?= formatRupiah($saldo) ?></div>
        <div class="saldo-update"><i class="fas fa-sync-alt"></i> Diperbarui: <?= date('d M Y, H:i') ?></div>
        <div style="margin-top:20px;display:flex;gap:10px;justify-content:center">
          <a href="laporan-publik.php" class="btn btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3)"><i class="fas fa-file-alt"></i> Laporan</a>
          <a href="grafik-publik.php"  class="btn btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3)"><i class="fas fa-chart-pie"></i> Grafik</a>
        </div>
      </div>
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
      <div class="card animate-fadeIn delay-3">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-chart-line"></i> Tren 6 Bulan</div>
          <a href="grafik-publik.php" class="btn btn-ghost btn-sm"><i class="fas fa-expand-alt"></i> Lengkap</a>
        </div>
        <div class="card-body">
          <div class="chart-container"><canvas id="miniChart"></canvas></div>
          <div style="display:flex;justify-content:center;gap:20px;margin-top:12px">
            <div style="display:flex;align-items:center;gap:6px;font-size:.78rem;color:var(--text-muted)">
              <span style="width:12px;height:12px;border-radius:3px;background:rgba(26,122,74,.85);display:inline-block"></span>Pemasukan
            </div>
            <div style="display:flex;align-items:center;gap:6px;font-size:.78rem;color:var(--text-muted)">
              <span style="width:12px;height:12px;border-radius:3px;background:rgba(239,68,68,.7);display:inline-block"></span>Pengeluaran
            </div>
          </div>
        </div>
        <div class="card-footer" style="text-align:center">
          <a href="grafik-publik.php" style="font-size:.8rem;color:var(--primary);font-weight:600"><i class="fas fa-chart-bar"></i> Lihat Grafik Lengkap dengan 4 Jenis Grafik →</a>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section" style="padding-top:0;padding-bottom:52px">
  <div class="container">
    <div class="section-header">
      <h2 class="section-title">Transaksi <span>Terbaru</span></h2>
      <div class="section-divider"></div>
      <p class="section-subtitle">Catatan transaksi kas masjid terkini yang dapat diakses oleh seluruh jamaah</p>
    </div>
    <div class="table-wrapper animate-fadeIn">
      <table class="table table-striped">
        <thead><tr><th>Tanggal</th><th>Keterangan</th><th>Kategori</th><th>Jenis</th><th class="text-right">Jumlah</th></tr></thead>
        <tbody>
          <?php if($trx_terbaru->num_rows): while($r=$trx_terbaru->fetch_assoc()): ?>
          <tr>
            <td><i class="fas fa-calendar-alt" style="color:var(--text-muted);margin-right:6px"></i><?= date('d M Y',strtotime($r['tanggal'])) ?></td>
            <td><?= htmlspecialchars($r['keterangan']) ?></td>
            <td><span class="badge badge-primary"><?= htmlspecialchars($r['nama_kategori']) ?></span></td>
            <td><?= $r['jenis']=='masuk'?'<span class="badge badge-success"><i class="fas fa-arrow-down"></i> Masuk</span>':'<span class="badge badge-danger"><i class="fas fa-arrow-up"></i> Keluar</span>' ?></td>
            <td class="text-right fw-600 <?= $r['jenis']=='masuk'?'text-success':'text-danger' ?>"><?= ($r['jenis']=='masuk'?'+':'-').formatRupiah($r['jumlah']) ?></td>
          </tr>
          <?php endwhile; else: ?>
          <tr><td colspan="5"><div class="empty-state"><div class="es-icon"><i class="fas fa-inbox"></i></div><h3>Belum ada transaksi</h3></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <div style="text-align:center;margin-top:24px"><a href="laporan-publik.php" class="btn btn-outline"><i class="fas fa-list"></i> Lihat Semua Transaksi</a></div>
  </div>
</section>

<section class="section" style="background:linear-gradient(135deg,#f0f7f2,#e8f5ee);padding:60px 0">
  <div class="container">
    <div class="section-header">
      <h2 class="section-title">Komitmen <span>Transparansi</span></h2>
      <div class="section-divider"></div>
      <p class="section-subtitle">Kami berkomitmen mengelola keuangan masjid secara terbuka dan bertanggung jawab</p>
    </div>
    <div class="grid-3">
      <?php $features=[
        ['fas fa-eye','green','Keterbukaan Informasi','Seluruh data transaksi pemasukan dan pengeluaran dapat diakses jamaah secara langsung.'],
        ['fas fa-chart-pie','gold','Visualisasi Data','Data keuangan disajikan dalam bentuk grafik interaktif sehingga mudah dipahami.'],
        ['fas fa-file-alt','blue','Laporan Berkala','Tersedia periode harian, mingguan, bulanan, tahunan dengan fitur ekspor PDF.'],
        ['fas fa-shield-alt','green','Data Terverifikasi','Setiap transaksi dicatat oleh pengurus yang bertanggung jawab kepada jamaah.'],
        ['fas fa-clock','gold','Real-time Update','Informasi saldo dan transaksi diperbarui secara langsung.'],
        ['fas fa-mobile-alt','blue','Akses Kapan Saja','Dapat diakses dari perangkat apapun tanpa perlu menginstal aplikasi.'],
      ]; foreach($features as $i=>[$ico,$col,$ttl,$dsc]): $d=$i+1; ?>
      <div class="card animate-fadeIn delay-<?= $d ?>" style="padding:28px">
        <div style="width:56px;height:56px;border-radius:16px;background:<?= $col=='green'?'rgba(26,122,74,.1)':($col=='gold'?'rgba(201,168,76,.1)':'rgba(59,130,246,.1)') ?>;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:<?= $col=='green'?'var(--primary)':($col=='gold'?'var(--secondary)':'var(--info)') ?>;margin-bottom:18px">
          <i class="<?= $ico ?>"></i></div>
        <h3 style="font-size:1rem;font-weight:700;margin-bottom:10px"><?= $ttl ?></h3>
        <p style="font-size:.875rem;color:var(--text-secondary);line-height:1.7"><?= $dsc ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php include 'includes/partials/footer-publik.php'; ?>
<script>
function animCounter(el,target){
  let s=0; const step=target/(1800/16);
  const t=setInterval(()=>{s+=step;if(s>=target){s=target;clearInterval(t);}
    el.textContent='Rp '+new Intl.NumberFormat('id-ID').format(Math.floor(s));},16);
}
window.addEventListener('load',()=>{
  animCounter(document.getElementById('heroMasuk'),<?= $total_masuk ?>);
  animCounter(document.getElementById('heroKeluar'),<?= $total_keluar ?>);
});
Chart.defaults.font.family="'Poppins',sans-serif";
new Chart(document.getElementById('miniChart').getContext('2d'),{
  type:'bar',
  data:{labels:<?= json_encode($chart_labels) ?>,datasets:[
    {label:'Pemasukan',data:<?= json_encode($chart_masuk) ?>,backgroundColor:'rgba(26,122,74,.8)',borderRadius:6,borderSkipped:false},
    {label:'Pengeluaran',data:<?= json_encode($chart_keluar) ?>,backgroundColor:'rgba(239,68,68,.7)',borderRadius:6,borderSkipped:false}
  ]},
  options:{responsive:true,maintainAspectRatio:true,plugins:{legend:{position:'bottom',labels:{font:{size:11},padding:12}}},
    scales:{x:{grid:{display:false},ticks:{font:{size:10}}},y:{grid:{color:'rgba(0,0,0,.05)'},ticks:{font:{size:10},callback:v=>'Rp '+new Intl.NumberFormat('id-ID').format(v)}}}}
});
document.getElementById('navToggle').addEventListener('click',()=>document.getElementById('navLinks').classList.toggle('open'));
const obs=new IntersectionObserver(es=>es.forEach(e=>{if(e.isIntersecting){e.target.style.opacity=1;e.target.style.transform='translateY(0)'}}),{threshold:.1});
document.querySelectorAll('.card,.stat-card,.saldo-card').forEach(el=>{el.style.opacity=0;el.style.transform='translateY(24px)';el.style.transition='opacity .6s ease,transform .6s ease';obs.observe(el);});
</script>
</body></html>
