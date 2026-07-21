<?php
require_once 'includes/config.php';
$tahun=(int)($_GET['tahun']??date('Y'));
$blnlbl=['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
$bm=$bk=$sl=[];$kum=0;
for($m=1;$m<=12;$m++){
  $b=sprintf('%04d-%02d',$tahun,$m);
  $mk=(float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='masuk'  AND DATE_FORMAT(tanggal,'%Y-%m')='$b'")->fetch_assoc()['t'];
  $kl=(float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='keluar' AND DATE_FORMAT(tanggal,'%Y-%m')='$b'")->fetch_assoc()['t'];
  $bm[]=$mk;$bk[]=$kl;$kum+=($mk-$kl);$sl[]=$kum;
}
$pi=$conn->query("SELECT k.nama_kategori,COALESCE(SUM(t.jumlah),0) as total FROM transaksi t JOIN kategori k ON t.kategori_id=k.id WHERE t.jenis='masuk' AND YEAR(t.tanggal)=$tahun GROUP BY k.id ORDER BY total DESC LIMIT 8");
$pil=$pid=[];while($r=$pi->fetch_assoc()){$pil[]=$r['nama_kategori'];$pid[]=(float)$r['total'];}
$pe=$conn->query("SELECT k.nama_kategori,COALESCE(SUM(t.jumlah),0) as total FROM transaksi t JOIN kategori k ON t.kategori_id=k.id WHERE t.jenis='keluar' AND YEAR(t.tanggal)=$tahun GROUP BY k.id ORDER BY total DESC LIMIT 8");
$pel=$ped=[];while($r=$pe->fetch_assoc()){$pel[]=$r['nama_kategori'];$ped[]=(float)$r['total'];}
$s=$conn->query("SELECT COALESCE(SUM(CASE WHEN jenis='masuk' THEN jumlah END),0) AS m,COALESCE(SUM(CASE WHEN jenis='keluar' THEN jumlah END),0) AS k FROM transaksi WHERE YEAR(tanggal)=$tahun")->fetch_assoc();
?>
<!DOCTYPE html><html lang="id"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Visualisasi Grafik – <?= APP_NAME ?></title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head><body>
<?php include 'includes/partials/navbar-publik.php'; ?>
<div style="background:linear-gradient(135deg,#0f3d26,#1a7a4a);padding:48px 0 32px">
  <div class="container"><div class="hero-badge"><i class="fas fa-chart-bar"></i> Visualisasi Data Keuangan</div>
    <h1 style="font-size:1.8rem;font-weight:800;color:#fff;margin-top:12px;margin-bottom:8px">Grafik Keuangan Kas Masjid</h1>
    <p style="color:rgba(255,255,255,.75)"><?= MASJID_NAME ?> &bull; Tahun <?= $tahun ?></p>
  </div>
</div>
<div class="container" style="padding-top:32px;padding-bottom:64px">
  <div class="filter-bar mb-3">
    <span class="filter-label"><i class="fas fa-calendar"></i> Pilih Tahun:</span>
    <form method="GET" style="display:flex;align-items:center;gap:12px;flex:1;flex-wrap:wrap">
      <select name="tahun" class="form-control form-select" style="max-width:140px" onchange="this.form.submit()">
        <?php for($y=date('Y');$y>=2020;$y--): ?><option value="<?= $y ?>" <?= $tahun==$y?'selected':'' ?>><?= $y ?></option><?php endfor; ?>
      </select>
    </form>
  </div>
  <div class="grid-3 mb-3">
    <div class="stat-card green animate-fadeIn"><div class="stat-icon"><i class="fas fa-arrow-down"></i></div><div class="stat-label">Total Pemasukan <?= $tahun ?></div><div class="stat-value"><?= formatRupiah($s['m']) ?></div></div>
    <div class="stat-card red animate-fadeIn delay-1"><div class="stat-icon"><i class="fas fa-arrow-up"></i></div><div class="stat-label">Total Pengeluaran <?= $tahun ?></div><div class="stat-value"><?= formatRupiah($s['k']) ?></div></div>
    <div class="stat-card gold animate-fadeIn delay-2"><div class="stat-icon"><i class="fas fa-balance-scale"></i></div><div class="stat-label">Saldo Tahun <?= $tahun ?></div><div class="stat-value <?= ($s['m']-$s['k'])>=0?'text-success':'text-danger' ?>"><?= formatRupiah($s['m']-$s['k']) ?></div></div>
  </div>
  <div class="card mb-3 animate-fadeIn">
    <div class="card-header"><div class="card-title"><i class="fas fa-chart-bar"></i> Pemasukan vs Pengeluaran per Bulan – <?= $tahun ?></div></div>
    <div class="card-body"><div class="chart-container" style="height:340px"><canvas id="barChart"></canvas></div></div>
  </div>
  <div class="card mb-3 animate-fadeIn delay-1">
    <div class="card-header"><div class="card-title"><i class="fas fa-chart-line"></i> Perkembangan Saldo Kumulatif – <?= $tahun ?></div></div>
    <div class="card-body"><div class="chart-container" style="height:300px"><canvas id="lineChart"></canvas></div></div>
  </div>
  <div class="grid-2 animate-fadeIn delay-2">
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fas fa-chart-pie"></i> Proporsi Sumber Pemasukan</div></div>
      <div class="card-body" style="display:flex;flex-direction:column;align-items:center">
        <?php if(count($pid)): ?><div class="chart-container" style="max-width:280px;width:100%"><canvas id="pieIncome"></canvas></div><div class="chart-legend" id="legIncome"></div>
        <?php else: ?><div class="empty-state"><div class="es-icon"><i class="fas fa-chart-pie"></i></div><h3>Belum ada data</h3></div><?php endif; ?>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fas fa-chart-pie"></i> Proporsi Kategori Pengeluaran</div></div>
      <div class="card-body" style="display:flex;flex-direction:column;align-items:center">
        <?php if(count($ped)): ?><div class="chart-container" style="max-width:280px;width:100%"><canvas id="pieExpense"></canvas></div><div class="chart-legend" id="legExpense"></div>
        <?php else: ?><div class="empty-state"><div class="es-icon"><i class="fas fa-chart-pie"></i></div><h3>Belum ada data</h3></div><?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php include 'includes/partials/footer-publik.php'; ?>
<script>
Chart.defaults.font.family="'Poppins',sans-serif";
const fmtRp=v=>'Rp '+new Intl.NumberFormat('id-ID').format(v);
new Chart(document.getElementById('barChart').getContext('2d'),{
  type:'bar',data:{labels:<?= json_encode($blnlbl) ?>,datasets:[
    {label:'Pemasukan',data:<?= json_encode($bm) ?>,backgroundColor:'rgba(26,122,74,.85)',borderRadius:8,borderSkipped:false},
    {label:'Pengeluaran',data:<?= json_encode($bk) ?>,backgroundColor:'rgba(239,68,68,.75)',borderRadius:8,borderSkipped:false}
  ]},options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},
    plugins:{legend:{position:'top',labels:{padding:20,font:{size:12}}},tooltip:{callbacks:{label:c=>` ${c.dataset.label}: ${fmtRp(c.raw)}`}}},
    scales:{x:{grid:{display:false}},y:{grid:{color:'rgba(0,0,0,.05)'},ticks:{callback:v=>fmtRp(v)}}}}
});
new Chart(document.getElementById('lineChart').getContext('2d'),{
  type:'line',data:{labels:<?= json_encode($blnlbl) ?>,datasets:[{
    label:'Saldo Kumulatif',data:<?= json_encode($sl) ?>,borderColor:'#1a7a4a',backgroundColor:'rgba(26,122,74,.08)',
    borderWidth:3,fill:true,tension:.4,pointBackgroundColor:'#1a7a4a',pointBorderColor:'#fff',pointRadius:6,pointHoverRadius:9
  }]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>` Saldo: ${fmtRp(c.raw)}`}}},
    scales:{x:{grid:{display:false}},y:{grid:{color:'rgba(0,0,0,.05)'},ticks:{callback:v=>fmtRp(v)}}}}
});
const CG=['#1a7a4a','#22a05e','#c9a84c','#3b82f6','#f59e0b','#8b5cf6','#ec4899','#14b8a6'];
const CR=['#ef4444','#f87171','#dc2626','#b91c1c','#fca5a5','#fecaca','#ff7f7f','#c53030'];
function buildPie(cid,lid,labels,data,colors){
  new Chart(document.getElementById(cid).getContext('2d'),{type:'doughnut',
    data:{labels,datasets:[{data,backgroundColor:colors,borderWidth:3,borderColor:'#fff',hoverOffset:10}]},
    options:{responsive:true,maintainAspectRatio:true,cutout:'55%',plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>` ${c.label}: ${fmtRp(c.raw)}`}}}}
  });
  const leg=document.getElementById(lid);
  labels.forEach((l,i)=>{leg.innerHTML+=`<div class="legend-item"><span class="legend-dot" style="background:${colors[i]}"></span>${l}</div>`;});
}
<?php if(count($pid)): ?>buildPie('pieIncome','legIncome',<?= json_encode($pil) ?>,<?= json_encode($pid) ?>,CG);<?php endif; ?>
<?php if(count($ped)): ?>buildPie('pieExpense','legExpense',<?= json_encode($pel) ?>,<?= json_encode($ped) ?>,CR);<?php endif; ?>
document.getElementById('navToggle').addEventListener('click',()=>document.getElementById('navLinks').classList.toggle('open'));
</script>
</body></html>
