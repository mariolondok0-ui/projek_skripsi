<?php
require_once 'includes/config.php';
$filter_periode = $_GET['periode'] ?? 'semua';
$filter_jenis   = $_GET['jenis']   ?? 'semua';
$filter_bulan   = $_GET['bulan']   ?? date('Y-m');
$filter_tahun   = $_GET['tahun']   ?? date('Y');
$page           = max(1,(int)($_GET['page'] ?? 1));
$per_page       = 10;

$where = ["t.deleted_at IS NULL"]; // Tidak tampilkan yang di trash
if ($filter_jenis !== 'semua') $where[] = "t.jenis='" . sanitize($filter_jenis) . "'";
switch ($filter_periode) {
    case 'bulan':  $where[] = "DATE_FORMAT(t.tanggal,'%Y-%m')='" . sanitize($filter_bulan) . "'"; break;
    case 'tahun':  $where[] = "YEAR(t.tanggal)=" . (int)$filter_tahun; break;
    case 'minggu': $where[] = "t.tanggal>=DATE_SUB(CURDATE(),INTERVAL 7 DAY)"; break;
    case 'hari':   $where[] = "t.tanggal=CURDATE()"; break;
}
$ws          = implode(' AND ', $where);
$total_rows  = $conn->query("SELECT COUNT(*) as c FROM transaksi t WHERE $ws")->fetch_assoc()['c'];
$total_pages = max(1, ceil($total_rows / $per_page));
$offset      = ($page - 1) * $per_page;
$rows        = $conn->query("SELECT t.*,k.nama_kategori FROM transaksi t JOIN kategori k ON t.kategori_id=k.id WHERE $ws ORDER BY t.tanggal DESC,t.id DESC LIMIT $per_page OFFSET $offset");
$sum         = $conn->query("SELECT COALESCE(SUM(CASE WHEN jenis='masuk' THEN jumlah END),0) AS tm, COALESCE(SUM(CASE WHEN jenis='keluar' THEN jumlah END),0) AS tk FROM transaksi t WHERE $ws")->fetch_assoc();
$saldo_total = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='masuk' AND deleted_at IS NULL")->fetch_assoc()['t']
             - (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='keluar' AND deleted_at IS NULL")->fetch_assoc()['t'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Laporan Keuangan – <?= APP_NAME ?></title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ===== LAPORAN PUBLIK ===== */
.lap-header {
  background: linear-gradient(135deg,#0f3d26 0%,#1a7a4a 55%,#0d9488 100%);
  padding: 40px 0 28px;
  position: relative; overflow: hidden;
}
.lap-header::before {
  content:''; position:absolute; top:-60px; right:-60px;
  width:200px; height:200px; background:rgba(255,255,255,.05); border-radius:50%;
}
.lap-header::after {
  content:''; position:absolute; bottom:-40px; left:-40px;
  width:150px; height:150px; background:rgba(201,168,76,.08); border-radius:50%;
}
.lap-header .container { position:relative; z-index:1; }

/* Saldo banner */
.saldo-banner {
  background: rgba(255,255,255,.12);
  border: 1px solid rgba(255,255,255,.2);
  border-radius: var(--radius-xl);
  padding: 20px 24px;
  display: flex; align-items: center; gap: 16px;
  margin-top: 20px; backdrop-filter: blur(6px);
}
.saldo-banner .sb-icon {
  width: 52px; height: 52px; flex-shrink: 0;
  background: rgba(255,255,255,.15); border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.4rem; color: #fff;
}
.saldo-banner .sb-val { font-size: 1.6rem; font-weight: 800; color: #fff; }
.saldo-banner .sb-lbl { font-size: .78rem; color: rgba(255,255,255,.75); }

/* Filter mobile */
.filter-mobile { display:none; }
.filter-desktop { display:flex; }

/* Card transaksi mobile */
.trx-card {
  background: var(--bg-card);
  border-radius: var(--radius-lg);
  padding: 16px;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border-light);
  display: flex; align-items: center; gap: 14px;
  transition: var(--transition);
}
.trx-card:hover { box-shadow: var(--shadow); transform: translateY(-1px); }
.trx-card .trx-icon {
  width: 44px; height: 44px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1rem; flex-shrink: 0;
}
.trx-card .trx-body { flex: 1; min-width: 0; }
.trx-card .trx-name { font-size: .875rem; font-weight: 600; color: var(--text-primary); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.trx-card .trx-meta { font-size: .75rem; color: var(--text-muted); margin-top: 2px; display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.trx-card .trx-amount { font-size: 1rem; font-weight: 800; text-align: right; flex-shrink: 0; }

/* Responsive */
@media (max-width: 768px) {
  .stat-cards-grid { grid-template-columns: 1fr !important; gap: 10px !important; }
  .saldo-banner { flex-direction: column; text-align: center; padding: 16px; }
  .saldo-banner .sb-icon { margin: 0 auto; }
  .lap-header { padding: 28px 0 20px; }
  .table-wrapper { display: none; }
  .trx-list-mobile { display: flex; flex-direction: column; gap: 10px; }
  .filter-mobile { display: block; }
  .filter-desktop { display: none; }
  .filter-mobile form { display: flex; flex-direction: column; gap: 10px; }
  .lap-summary-grid { grid-template-columns: 1fr 1fr !important; }
}
@media (min-width: 769px) {
  .trx-list-mobile { display: none; }
}
</style>
</head>
<body>
<?php include 'includes/partials/navbar-publik.php'; ?>

<!-- ===== HEADER ===== -->
<div class="lap-header">
  <div class="container">
    <a href="<?= APP_URL ?>/index.php"
       style="display:inline-flex;align-items:center;gap:7px;color:rgba(255,255,255,.75);font-size:.82rem;font-weight:500;transition:color .15s;text-decoration:none"
       onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,.75)'">
      <i class="fas fa-arrow-left"></i> Kembali ke Beranda
    </a>
    <div style="margin-top:14px">
      <div style="display:inline-flex;align-items:center;gap:7px;background:rgba(255,255,255,.15);color:rgba(255,255,255,.9);padding:5px 14px;border-radius:99px;font-size:.78rem;font-weight:600;border:1px solid rgba(255,255,255,.2)">
        <i class="fas fa-file-alt"></i> Laporan Keuangan Publik
      </div>
      <h1 style="font-size:clamp(1.3rem,3vw,1.8rem);font-weight:800;color:#fff;margin:10px 0 4px">
        Laporan Keuangan Kas Masjid
      </h1>
      <p style="color:rgba(255,255,255,.75);font-size:.875rem">
        <?= MASJID_NAME ?> &bull; Data terbuka untuk seluruh jamaah
      </p>
    </div>

    <!-- Saldo Banner -->
    <div class="saldo-banner">
      <div class="sb-icon"><i class="fas fa-wallet"></i></div>
      <div style="flex:1">
        <div class="sb-lbl">Saldo Kas Masjid Saat Ini</div>
        <div class="sb-val"><?= formatRupiah($saldo_total) ?></div>
      </div>
      <div style="text-align:right;flex-shrink:0">
        <div style="font-size:.72rem;color:rgba(255,255,255,.6)">Diperbarui</div>
        <div style="font-size:.8rem;font-weight:600;color:rgba(255,255,255,.9)"><?= date('d M Y') ?></div>
      </div>
    </div>
  </div>
</div>

<!-- ===== KONTEN ===== -->
<div class="container" style="padding-top:28px;padding-bottom:60px">

  <!-- Summary Cards -->
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:24px" class="stat-cards-grid">
    <div class="stat-card green animate-fadeIn" style="padding:18px">
      <div class="stat-icon" style="width:40px;height:40px;font-size:1.1rem;margin-bottom:10px"><i class="fas fa-arrow-down"></i></div>
      <div class="stat-label">Pemasukan</div>
      <div class="stat-value" style="font-size:1.1rem"><?= formatRupiah($sum['tm']) ?></div>
    </div>
    <div class="stat-card red animate-fadeIn delay-1" style="padding:18px">
      <div class="stat-icon" style="width:40px;height:40px;font-size:1.1rem;margin-bottom:10px"><i class="fas fa-arrow-up"></i></div>
      <div class="stat-label">Pengeluaran</div>
      <div class="stat-value" style="font-size:1.1rem"><?= formatRupiah($sum['tk']) ?></div>
    </div>
    <div class="stat-card blue animate-fadeIn delay-2" style="padding:18px">
      <div class="stat-icon" style="width:40px;height:40px;font-size:1.1rem;margin-bottom:10px"><i class="fas fa-balance-scale"></i></div>
      <div class="stat-label">Saldo Periode</div>
      <div class="stat-value <?= ($sum['tm']-$sum['tk'])>=0?'text-success':'text-danger' ?>" style="font-size:1.1rem"><?= formatRupiah($sum['tm']-$sum['tk']) ?></div>
    </div>
  </div>

  <!-- Filter DESKTOP -->
  <div class="filter-bar filter-desktop" style="margin-bottom:20px">
    <span class="filter-label"><i class="fas fa-filter"></i> Filter:</span>
    <form method="GET" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;flex:1">
      <select name="periode" class="form-control form-select" style="min-width:140px" onchange="this.form.submit()">
        <option value="semua" <?= $filter_periode=='semua'?'selected':'' ?>>Semua Periode</option>
        <option value="hari"  <?= $filter_periode=='hari'?'selected':'' ?>>Hari Ini</option>
        <option value="minggu"<?= $filter_periode=='minggu'?'selected':'' ?>>7 Hari Terakhir</option>
        <option value="bulan" <?= $filter_periode=='bulan'?'selected':'' ?>>Per Bulan</option>
        <option value="tahun" <?= $filter_periode=='tahun'?'selected':'' ?>>Per Tahun</option>
      </select>
      <?php if($filter_periode=='bulan'): ?>
      <input type="month" name="bulan" value="<?= $filter_bulan ?>" class="form-control" onchange="this.form.submit()">
      <?php endif; ?>
      <?php if($filter_periode=='tahun'): ?>
      <select name="tahun" class="form-control form-select" onchange="this.form.submit()">
        <?php for($y=date('Y');$y>=2020;$y--): ?><option value="<?= $y ?>" <?= $filter_tahun==$y?'selected':'' ?>><?= $y ?></option><?php endfor; ?>
      </select>
      <?php endif; ?>
      <select name="jenis" class="form-control form-select" style="min-width:130px" onchange="this.form.submit()">
        <option value="semua"  <?= $filter_jenis=='semua'?'selected':'' ?>>Semua Jenis</option>
        <option value="masuk"  <?= $filter_jenis=='masuk'?'selected':'' ?>>Kas Masuk</option>
        <option value="keluar" <?= $filter_jenis=='keluar'?'selected':'' ?>>Kas Keluar</option>
      </select>
      <span style="font-size:.8rem;color:var(--text-muted);white-space:nowrap;background:var(--bg-main);padding:8px 12px;border-radius:var(--radius-sm)">
        <i class="fas fa-database"></i> <?= $total_rows ?> data
      </span>
    </form>
  </div>

  <!-- Filter MOBILE -->
  <div class="filter-mobile" style="margin-bottom:16px">
    <form method="GET">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div>
          <label style="font-size:.75rem;font-weight:600;color:var(--text-muted);margin-bottom:4px;display:block">Periode</label>
          <select name="periode" class="form-control form-select" onchange="this.form.submit()">
            <option value="semua" <?= $filter_periode=='semua'?'selected':'' ?>>Semua</option>
            <option value="hari"  <?= $filter_periode=='hari'?'selected':'' ?>>Hari Ini</option>
            <option value="minggu"<?= $filter_periode=='minggu'?'selected':'' ?>>7 Hari</option>
            <option value="bulan" <?= $filter_periode=='bulan'?'selected':'' ?>>Per Bulan</option>
            <option value="tahun" <?= $filter_periode=='tahun'?'selected':'' ?>>Per Tahun</option>
          </select>
        </div>
        <div>
          <label style="font-size:.75rem;font-weight:600;color:var(--text-muted);margin-bottom:4px;display:block">Jenis</label>
          <select name="jenis" class="form-control form-select" onchange="this.form.submit()">
            <option value="semua"  <?= $filter_jenis=='semua'?'selected':'' ?>>Semua</option>
            <option value="masuk"  <?= $filter_jenis=='masuk'?'selected':'' ?>>Masuk</option>
            <option value="keluar" <?= $filter_jenis=='keluar'?'selected':'' ?>>Keluar</option>
          </select>
        </div>
      </div>
      <?php if($filter_periode=='bulan'): ?>
      <input type="month" name="bulan" value="<?= $filter_bulan ?>" class="form-control" style="margin-top:10px" onchange="this.form.submit()">
      <?php endif; ?>
    </form>
    <div style="font-size:.78rem;color:var(--text-muted);margin-top:8px;text-align:right">
      <i class="fas fa-database"></i> <?= $total_rows ?> transaksi ditemukan
    </div>
  </div>

  <!-- ===== TABEL (Desktop) ===== -->
  <div class="table-wrapper animate-fadeIn">
    <table class="table table-striped">
      <thead>
        <tr><th>#</th><th>Tanggal</th><th>Keterangan</th><th>Kategori</th><th>Jenis</th><th class="text-right">Jumlah (Rp)</th></tr>
      </thead>
      <tbody>
        <?php
        $rows->data_seek(0);
        if ($rows->num_rows): $no=$offset+1; while ($r=$rows->fetch_assoc()): ?>
        <tr>
          <td class="text-muted"><?= $no++ ?></td>
          <td style="white-space:nowrap"><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
          <td><?= htmlspecialchars($r['keterangan']) ?></td>
          <td><span class="badge badge-primary"><?= htmlspecialchars($r['nama_kategori']) ?></span></td>
          <td><?= $r['jenis']=='masuk' ? '<span class="badge badge-success"><i class="fas fa-arrow-down"></i> Masuk</span>' : '<span class="badge badge-danger"><i class="fas fa-arrow-up"></i> Keluar</span>' ?></td>
          <td class="text-right fw-600 <?= $r['jenis']=='masuk'?'text-success':'text-danger' ?>"><?= ($r['jenis']=='masuk'?'+':'-').' '.number_format($r['jumlah'],0,',','.') ?></td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="6"><div class="empty-state"><div class="es-icon"><i class="fas fa-search"></i></div><h3>Tidak ada data</h3><p>Coba ubah filter pencarian</p></div></td></tr>
        <?php endif; ?>
      </tbody>
      <tfoot>
        <tr><td colspan="5" class="text-right">Total Pemasukan:</td><td class="text-right text-success fw-600">+ <?= number_format($sum['tm'],0,',','.') ?></td></tr>
        <tr><td colspan="5" class="text-right">Total Pengeluaran:</td><td class="text-right text-danger fw-600">- <?= number_format($sum['tk'],0,',','.') ?></td></tr>
        <tr><td colspan="5" class="text-right fw-700">Saldo:</td><td class="text-right fw-700 <?= ($sum['tm']-$sum['tk'])>=0?'text-success':'text-danger' ?>"><?= formatRupiah($sum['tm']-$sum['tk']) ?></td></tr>
      </tfoot>
    </table>
  </div>

  <!-- ===== CARD LIST (Mobile) ===== -->
  <div class="trx-list-mobile">
    <?php
    $rows->data_seek(0);
    if ($rows->num_rows): while ($r=$rows->fetch_assoc()):
      $isMasuk = $r['jenis'] === 'masuk';
    ?>
    <div class="trx-card animate-fadeIn">
      <div class="trx-icon" style="background:<?= $isMasuk?'rgba(16,185,129,.1)':'rgba(239,68,68,.1)' ?>;color:<?= $isMasuk?'var(--success)':'var(--danger)' ?>">
        <i class="fas fa-arrow-<?= $isMasuk?'down':'up' ?>"></i>
      </div>
      <div class="trx-body">
        <div class="trx-name"><?= htmlspecialchars($r['keterangan']) ?></div>
        <div class="trx-meta">
          <span><i class="fas fa-calendar-alt"></i> <?= date('d M Y', strtotime($r['tanggal'])) ?></span>
          <span class="badge badge-primary" style="font-size:.68rem"><?= htmlspecialchars($r['nama_kategori']) ?></span>
        </div>
      </div>
      <div class="trx-amount <?= $isMasuk?'text-success':'text-danger' ?>">
        <?= $isMasuk?'+':'-' ?>Rp <?= number_format($r['jumlah'],0,',','.') ?>
      </div>
    </div>
    <?php endwhile; else: ?>
    <div class="empty-state">
      <div class="es-icon"><i class="fas fa-search"></i></div>
      <h3>Tidak ada data</h3>
      <p>Coba ubah filter pencarian</p>
    </div>
    <?php endif; ?>

    <!-- Summary Mobile -->
    <?php if ($rows->num_rows): ?>
    <div style="background:var(--bg-card);border-radius:var(--radius-lg);padding:16px;box-shadow:var(--shadow-sm);border:1px solid var(--border-light);margin-top:4px">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;text-align:center">
        <div>
          <div style="font-size:.7rem;color:var(--text-muted)">Total Masuk</div>
          <div style="font-size:.95rem;font-weight:800;color:var(--success)">+<?= number_format($sum['tm'],0,',','.') ?></div>
        </div>
        <div>
          <div style="font-size:.7rem;color:var(--text-muted)">Total Keluar</div>
          <div style="font-size:.95rem;font-weight:800;color:var(--danger)">-<?= number_format($sum['tk'],0,',','.') ?></div>
        </div>
      </div>
      <div style="border-top:1px solid var(--border-light);margin-top:12px;padding-top:12px;text-align:center">
        <div style="font-size:.7rem;color:var(--text-muted)">Saldo Periode</div>
        <div style="font-size:1.1rem;font-weight:800;<?= ($sum['tm']-$sum['tk'])>=0?'color:var(--success)':'color:var(--danger)' ?>"><?= formatRupiah($sum['tm']-$sum['tk']) ?></div>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Pagination -->
  <?php if ($total_pages > 1): ?>
  <div style="display:flex;justify-content:center;margin-top:24px">
    <div class="pagination">
      <?php if($page>1): ?>
      <a href="?<?= http_build_query(array_merge($_GET,['page'=>1])) ?>" class="page-btn"><i class="fas fa-angle-double-left"></i></a>
      <a href="?<?= http_build_query(array_merge($_GET,['page'=>$page-1])) ?>" class="page-btn"><i class="fas fa-angle-left"></i></a>
      <?php endif; ?>
      <?php for($p=max(1,$page-2);$p<=min($total_pages,$page+2);$p++): ?>
      <a href="?<?= http_build_query(array_merge($_GET,['page'=>$p])) ?>" class="page-btn <?= $p==$page?'active':'' ?>"><?= $p ?></a>
      <?php endfor; ?>
      <?php if($page<$total_pages): ?>
      <a href="?<?= http_build_query(array_merge($_GET,['page'=>$page+1])) ?>" class="page-btn"><i class="fas fa-angle-right"></i></a>
      <a href="?<?= http_build_query(array_merge($_GET,['page'=>$total_pages])) ?>" class="page-btn"><i class="fas fa-angle-double-right"></i></a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

</div><!-- /container -->

<?php include 'includes/partials/footer-publik.php'; ?>
<script>
document.getElementById('navToggle').addEventListener('click', () => {
  document.getElementById('navLinks').classList.toggle('open');
});
const obs = new IntersectionObserver(es => es.forEach(e => {
  if (e.isIntersecting) { e.target.style.opacity='1'; e.target.style.transform='translateY(0)'; }
}), {threshold:.05});
document.querySelectorAll('.trx-card,.stat-card').forEach((el,i) => {
  el.style.opacity='0'; el.style.transform='translateY(20px)';
  el.style.transition=`opacity .5s ease ${i*.04}s,transform .5s ease ${i*.04}s`;
  obs.observe(el);
});
</script>
</body>
</html>
