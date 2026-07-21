<?php
require_once '../includes/config.php';
requireLogin();

// ---- Handle RESTORE ----
if (isset($_GET['restore'])) {
    $sampah_id = (int)$_GET['restore'];
    $stmt = $conn->prepare("INSERT INTO transaksi (tanggal, keterangan, jumlah, jenis, kategori_id, user_id) SELECT tanggal, keterangan, jumlah, jenis, kategori_id, user_id FROM transaksi_sampah WHERE sampah_id = ?");
    $stmt->bind_param('i', $sampah_id);
    if ($stmt->execute()) {
        $conn->query("DELETE FROM transaksi_sampah WHERE sampah_id = $sampah_id");
        setAlert('success', 'Data berhasil dipulihkan (Restore).');
    }
    redirect(APP_URL . '/admin/tempat-sampah.php');
}

// ---- Handle DELETE PERMANENT ----
if (isset($_GET['delete'])) {
    $sampah_id = (int)$_GET['delete'];
    $conn->query("DELETE FROM transaksi_sampah WHERE sampah_id = $sampah_id");
    setAlert('success', 'Data berhasil dihapus selamanya.');
    redirect(APP_URL . '/admin/tempat-sampah.php');
}

// ---- Handle KOSONGKAN SAMPAH ----
if (isset($_GET['empty'])) {
    $conn->query("TRUNCATE TABLE transaksi_sampah");
    setAlert('success', 'Tempat sampah berhasil dikosongkan.');
    redirect(APP_URL . '/admin/tempat-sampah.php');
}

$rows = $conn->query("SELECT ts.*, k.nama_kategori FROM transaksi_sampah ts JOIN kategori k ON ts.kategori_id=k.id ORDER BY ts.deleted_at DESC");
$alert = getAlert();
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Tempat Sampah - <?= APP_NAME ?></title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="admin-wrapper">
<?php include '../includes/partials/sidebar-admin.php'; ?>
<div class="admin-main">
  <div class="topbar">
    <div class="topbar-left">
      <div class="topbar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></div>
      <div class="breadcrumb">
        <span class="bc-item"><i class="fas fa-home"></i></span>
        <span class="bc-sep"><i class="fas fa-chevron-right"></i></span>
        <span class="bc-item active">Tempat Sampah</span>
      </div>
    </div>
  </div>

  <div class="admin-content">
    <?php if ($alert): ?>
    <div class="alert alert-<?= $alert['type'] ?>"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($alert['message']) ?></div>
    <?php endif; ?>
    
    <div class="page-header" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
      <div>
        <h1 class="page-title"><i class="fas fa-trash-alt"></i> Tempat Sampah</h1>
        <p class="page-subtitle">Pulihkan atau hapus permanen data yang telah dihapus</p>
      </div>
      
      <div style="display:flex; gap:10px;">
        <a href="javascript:history.back()" class="btn btn-ghost" style="border: 1.5px solid var(--border); background: var(--bg-card);"><i class="fas fa-arrow-left"></i> Kembali</a>
        <?php if($rows->num_rows > 0): ?>
        <a href="?empty=1" class="btn btn-danger" onclick="return confirm('Yakin ingin mengosongkan seluruh tempat sampah?')"><i class="fas fa-ban"></i> Kosongkan Sampah</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="table-wrapper animate-fadeIn">
      <table class="table table-striped">
        <thead>
          <tr><th>Waktu Dihapus</th><th>Keterangan</th><th>Kategori</th><th>Jenis</th><th class="text-right">Jumlah</th><th style="width:120px">Aksi</th></tr>
        </thead>
        <tbody>
          <?php if ($rows->num_rows): while ($r = $rows->fetch_assoc()): ?>
          <tr>
            <td><?= date('d M Y, H:i', strtotime($r['deleted_at'])) ?></td>
            <td><?= htmlspecialchars($r['keterangan']) ?></td>
            <td><span class="badge badge-primary"><?= htmlspecialchars($r['nama_kategori']) ?></span></td>
            <td><?= $r['jenis']=='masuk' ? '<span class="badge badge-success"><i class="fas fa-arrow-down"></i> Masuk</span>' : '<span class="badge badge-danger"><i class="fas fa-arrow-up"></i> Keluar</span>' ?></td>
            <td class="text-right fw-600 <?= $r['jenis']=='masuk'?'text-success':'text-danger' ?>"><?= formatRupiah($r['jumlah']) ?></td>
            <td>
              <div style="display:flex;gap:6px">
                <a href="?restore=<?= $r['sampah_id'] ?>" class="btn btn-ghost btn-icon btn-sm" style="color:var(--success)" data-tooltip="Pulihkan (Restore)"><i class="fas fa-trash-restore"></i></a>
                <a href="?delete=<?= $r['sampah_id'] ?>" onclick="return confirm('Hapus permanen data ini?')" class="btn btn-icon btn-sm" style="background:rgba(239,68,68,.1);color:var(--danger)" data-tooltip="Hapus Permanen"><i class="fas fa-times"></i></a>
              </div>
            </td>
          </tr>
          <?php endwhile; else: ?>
          <tr><td colspan="6"><div class="empty-state"><div class="es-icon"><i class="fas fa-trash"></i></div><h3>Tempat Sampah Kosong</h3></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>
</div>

<script>
const sidebar = document.getElementById('adminSidebar');
const overlay = document.getElementById('sidebarOverlay');
document.getElementById('sidebarToggle').addEventListener('click', () => {
  if (window.innerWidth <= 768) { sidebar.classList.toggle('open'); overlay.classList.toggle('active'); } 
  else { document.querySelector('.admin-wrapper').classList.toggle('toggled'); }
});
overlay.addEventListener('click',()=>{ sidebar.classList.remove('open'); overlay.classList.remove('active'); });
</script>
</body>
</html>