<?php
require_once 'includes/config.php';
$filter_periode=$_GET['periode']??'semua'; $filter_jenis=$_GET['jenis']??'semua';
$filter_bulan=$_GET['bulan']??date('Y-m'); $filter_tahun=$_GET['tahun']??date('Y');
$page=max(1,(int)($_GET['page']??1)); $per_page=15;
$where=['1=1'];
if($filter_jenis!=='semua') $where[]="t.jenis='".sanitize($filter_jenis)."'";
switch($filter_periode){
  case 'bulan':  $where[]="DATE_FORMAT(t.tanggal,'%Y-%m')='".sanitize($filter_bulan)."'"; break;
  case 'tahun':  $where[]="YEAR(t.tanggal)=".(int)$filter_tahun; break;
  case 'minggu': $where[]="t.tanggal>=DATE_SUB(CURDATE(),INTERVAL 7 DAY)"; break;
  case 'hari':   $where[]="t.tanggal=CURDATE()"; break;
}
$ws=implode(' AND ',$where);
$total_rows=$conn->query("SELECT COUNT(*) as c FROM transaksi t WHERE $ws")->fetch_assoc()['c'];
$total_pages=max(1,ceil($total_rows/$per_page)); $offset=($page-1)*$per_page;
$rows=$conn->query("SELECT t.*,k.nama_kategori FROM transaksi t JOIN kategori k ON t.kategori_id=k.id WHERE $ws ORDER BY t.tanggal DESC,t.id DESC LIMIT $per_page OFFSET $offset");
$sum=$conn->query("SELECT COALESCE(SUM(CASE WHEN jenis='masuk' THEN jumlah END),0) AS tm, COALESCE(SUM(CASE WHEN jenis='keluar' THEN jumlah END),0) AS tk, COUNT(*) AS tc FROM transaksi t WHERE $ws")->fetch_assoc();
?>
<!DOCTYPE html><html lang="id"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Laporan Keuangan – <?= APP_NAME ?></title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head><body>
<?php include 'includes/partials/navbar-publik.php'; ?>
<div style="background:linear-gradient(135deg,#0f3d26,#1a7a4a);padding:48px 0 32px">
  <div class="container"><div class="hero-badge"><i class="fas fa-file-alt"></i> Laporan Keuangan Publik</div>
    <h1 style="font-size:1.8rem;font-weight:800;color:#fff;margin-top:12px;margin-bottom:8px">Laporan Keuangan Kas Masjid</h1>
    <p style="color:rgba(255,255,255,.75)"><?= MASJID_NAME ?> &bull; Data terbuka untuk seluruh jamaah</p>
  </div>
</div>
<div class="container" style="padding-top:32px;padding-bottom:60px">
  <div class="grid-3 mb-3">
    <div class="stat-card green animate-fadeIn"><div class="stat-icon"><i class="fas fa-arrow-down"></i></div><div class="stat-label">Total Pemasukan</div><div class="stat-value"><?= formatRupiah($sum['tm']) ?></div></div>
    <div class="stat-card red animate-fadeIn delay-1"><div class="stat-icon"><i class="fas fa-arrow-up"></i></div><div class="stat-label">Total Pengeluaran</div><div class="stat-value"><?= formatRupiah($sum['tk']) ?></div></div>
    <div class="stat-card blue animate-fadeIn delay-2"><div class="stat-icon"><i class="fas fa-wallet"></i></div><div class="stat-label">Saldo Periode</div><div class="stat-value <?= ($sum['tm']-$sum['tk'])>=0?'text-success':'text-danger' ?>"><?= formatRupiah($sum['tm']-$sum['tk']) ?></div></div>
  </div>
  <div class="filter-bar">
    <span class="filter-label"><i class="fas fa-filter"></i> Filter:</span>
    <form method="GET" style="display:contents">
      <select name="periode" class="form-control form-select" onchange="this.form.submit()">
        <option value="semua" <?= $filter_periode=='semua'?'selected':'' ?>>Semua Periode</option>
        <option value="hari"  <?= $filter_periode=='hari'?'selected':'' ?>>Hari Ini</option>
        <option value="minggu"<?= $filter_periode=='minggu'?'selected':'' ?>>7 Hari Terakhir</option>
        <option value="bulan" <?= $filter_periode=='bulan'?'selected':'' ?>>Per Bulan</option>
        <option value="tahun" <?= $filter_periode=='tahun'?'selected':'' ?>>Per Tahun</option>
      </select>
      <?php if($filter_periode=='bulan'): ?><input type="month" name="bulan" value="<?= $filter_bulan ?>" class="form-control" onchange="this.form.submit()"><?php endif; ?>
      <?php if($filter_periode=='tahun'): ?><select name="tahun" class="form-control form-select" onchange="this.form.submit()"><?php for($y=date('Y');$y>=2020;$y--): ?><option value="<?= $y ?>" <?= $filter_tahun==$y?'selected':'' ?>><?= $y ?></option><?php endfor; ?></select><?php endif; ?>
      <select name="jenis" class="form-control form-select" onchange="this.form.submit()">
        <option value="semua" <?= $filter_jenis=='semua'?'selected':'' ?>>Semua Jenis</option>
        <option value="masuk" <?= $filter_jenis=='masuk'?'selected':'' ?>>Kas Masuk</option>
        <option value="keluar"<?= $filter_jenis=='keluar'?'selected':'' ?>>Kas Keluar</option>
      </select>
      <span style="font-size:.8rem;color:var(--text-muted);white-space:nowrap"><i class="fas fa-database"></i> <?= $total_rows ?> data</span>
    </form>
  </div>
  <div class="table-wrapper animate-fadeIn">
    <table class="table table-striped">
      <thead><tr><th>#</th><th>Tanggal</th><th>Keterangan</th><th>Kategori</th><th>Jenis</th><th class="text-right">Jumlah (Rp)</th></tr></thead>
      <tbody>
        <?php if($rows->num_rows): $no=$offset+1; while($r=$rows->fetch_assoc()): ?>
        <tr>
          <td class="text-muted"><?= $no++ ?></td>
          <td><i class="fas fa-calendar-alt" style="color:var(--text-muted);margin-right:5px"></i><?= date('d M Y',strtotime($r['tanggal'])) ?></td>
          <td><?= htmlspecialchars($r['keterangan']) ?></td>
          <td><span class="badge badge-primary"><?= htmlspecialchars($r['nama_kategori']) ?></span></td>
          <td><?= $r['jenis']=='masuk'?'<span class="badge badge-success"><i class="fas fa-arrow-down"></i> Masuk</span>':'<span class="badge badge-danger"><i class="fas fa-arrow-up"></i> Keluar</span>' ?></td>
          <td class="text-right fw-600 <?= $r['jenis']=='masuk'?'text-success':'text-danger' ?>"><?= ($r['jenis']=='masuk'?'+':'-').' '.number_format($r['jumlah'],0,',','.') ?></td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="6"><div class="empty-state"><div class="es-icon"><i class="fas fa-search"></i></div><h3>Tidak ada data</h3><p>Coba ubah filter pencarian</p></div></td></tr>
        <?php endif; ?>
      </tbody>
      <tfoot>
        <tr><td colspan="5" class="text-right">Total Pemasukan:</td><td class="text-right text-success">+ <?= number_format($sum['tm'],0,',','.') ?></td></tr>
        <tr><td colspan="5" class="text-right">Total Pengeluaran:</td><td class="text-right text-danger">- <?= number_format($sum['tk'],0,',','.') ?></td></tr>
        <tr><td colspan="5" class="text-right fw-700">Saldo:</td><td class="text-right fw-700 <?= ($sum['tm']-$sum['tk'])>=0?'text-success':'text-danger' ?>"><?= formatRupiah($sum['tm']-$sum['tk']) ?></td></tr>
      </tfoot>
    </table>
  </div>
  <?php if($total_pages>1): ?>
  <div style="display:flex;justify-content:center;margin-top:24px"><div class="pagination">
    <?php if($page>1): ?><a href="?<?= http_build_query(array_merge($_GET,['page'=>1])) ?>" class="page-btn"><i class="fas fa-angle-double-left"></i></a><a href="?<?= http_build_query(array_merge($_GET,['page'=>$page-1])) ?>" class="page-btn"><i class="fas fa-angle-left"></i></a><?php endif; ?>
    <?php for($p=max(1,$page-2);$p<=min($total_pages,$page+2);$p++): ?><a href="?<?= http_build_query(array_merge($_GET,['page'=>$p])) ?>" class="page-btn <?= $p==$page?'active':'' ?>"><?= $p ?></a><?php endfor; ?>
    <?php if($page<$total_pages): ?><a href="?<?= http_build_query(array_merge($_GET,['page'=>$page+1])) ?>" class="page-btn"><i class="fas fa-angle-right"></i></a><a href="?<?= http_build_query(array_merge($_GET,['page'=>$total_pages])) ?>" class="page-btn"><i class="fas fa-angle-double-right"></i></a><?php endif; ?>
  </div></div>
  <?php endif; ?>
</div>
<?php include 'includes/partials/footer-publik.php'; ?>
<script>document.getElementById('navToggle').addEventListener('click',()=>document.getElementById('navLinks').classList.toggle('open'));</script>
</body></html>
