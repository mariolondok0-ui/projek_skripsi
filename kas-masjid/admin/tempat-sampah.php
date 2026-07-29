<?php
require_once '../includes/config.php';
requireLogin();

// ---- Pulihkan 1 data ----
if (isset($_GET['pulihkan'])) {
    $id = (int)$_GET['pulihkan'];
    $conn->query("UPDATE transaksi SET deleted_at=NULL WHERE id=$id AND deleted_at IS NOT NULL");
    setAlert('success', 'Data berhasil dipulihkan kembali.');
    redirect(APP_URL . '/admin/tempat-sampah.php');
}

// ---- Hapus Permanen 1 data ----
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $conn->query("DELETE FROM transaksi WHERE id=$id AND deleted_at IS NOT NULL");
    setAlert('success', 'Data berhasil dihapus secara permanen.');
    redirect(APP_URL . '/admin/tempat-sampah.php');
}

// ---- Pulihkan SEMUA ----
if (isset($_GET['pulihkan_semua'])) {
    $conn->query("UPDATE transaksi SET deleted_at=NULL WHERE deleted_at IS NOT NULL");
    setAlert('success', 'Semua data berhasil dipulihkan.');
    redirect(APP_URL . '/admin/tempat-sampah.php');
}

// ---- Kosongkan Tempat Sampah (hapus permanen semua) ----
if (isset($_GET['kosongkan'])) {
    $conn->query("DELETE FROM transaksi WHERE deleted_at IS NOT NULL");
    setAlert('success', 'Tempat sampah berhasil dikosongkan.');
    redirect(APP_URL . '/admin/tempat-sampah.php');
}

// ---- Filter ----
$filter_jenis = sanitize($_GET['jenis'] ?? 'semua');
$page         = max(1, (int)($_GET['page'] ?? 1));
$per_page     = 12;

$where = "deleted_at IS NOT NULL";
if ($filter_jenis !== 'semua') $where .= " AND jenis='" . sanitize($filter_jenis) . "'";

$total_rows  = $conn->query("SELECT COUNT(*) as c FROM transaksi WHERE $where")->fetch_assoc()['c'];
$total_pages = max(1, ceil($total_rows / $per_page));
$offset      = ($page - 1) * $per_page;

$rows = $conn->query("
    SELECT t.*, k.nama_kategori
    FROM transaksi t
    JOIN kategori k ON t.kategori_id = k.id
    WHERE t.$where
    ORDER BY t.deleted_at DESC
    LIMIT $per_page OFFSET $offset
");

// Summary sampah
$jml_masuk  = (int)$conn->query("SELECT COUNT(*) as c FROM transaksi WHERE deleted_at IS NOT NULL AND jenis='masuk'")->fetch_assoc()['c'];
$jml_keluar = (int)$conn->query("SELECT COUNT(*) as c FROM transaksi WHERE deleted_at IS NOT NULL AND jenis='keluar'")->fetch_assoc()['c'];
$jml_total  = $jml_masuk + $jml_keluar;

$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Tempat Sampah – <?= APP_NAME ?></title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
        <span class="bc-item active">Tempat Sampah</span>
      </div>
    </div>
    <div class="topbar-right">
      <div class="topbar-date"><i class="fas fa-calendar-alt"></i> <?= date('d F Y') ?></div>
    </div>
  </div>

  <div class="admin-content">
    <?php if ($alert): ?>
    <div class="alert alert-<?= $alert['type'] ?>">
      <i class="fas fa-<?= $alert['type']=='success'?'check-circle':'exclamation-circle' ?>"></i>
      <?= $alert['message'] ?>
    </div>
    <?php endif; ?>

    <!-- PAGE HEADER -->
    <div class="page-header">
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
          <h1 class="page-title">
            <i class="fas fa-trash-alt" style="color:var(--danger)"></i> Tempat Sampah
          </h1>
          <p class="page-subtitle">Data yang dihapus tersimpan di sini — bisa dipulihkan atau dihapus permanen</p>
        </div>
        <a href="<?= APP_URL ?>/admin/dashboard.php" class="btn btn-ghost">
          <i class="fas fa-arrow-left"></i> Kembali
        </a>
      </div>
    </div>

    <!-- STAT CARDS -->
    <div class="grid-3 mb-3">
      <div class="stat-card blue animate-fadeIn">
        <div class="stat-icon"><i class="fas fa-trash"></i></div>
        <div class="stat-label">Total Data Sampah</div>
        <div class="stat-value"><?= $jml_total ?> data</div>
        <div class="stat-sub"><i class="fas fa-info-circle"></i> Menunggu tindakan</div>
      </div>
      <div class="stat-card green animate-fadeIn delay-1">
        <div class="stat-icon"><i class="fas fa-arrow-down"></i></div>
        <div class="stat-label">Kas Masuk Terhapus</div>
        <div class="stat-value"><?= $jml_masuk ?> data</div>
        <div class="stat-sub"><i class="fas fa-undo"></i> Bisa dipulihkan</div>
      </div>
      <div class="stat-card red animate-fadeIn delay-2">
        <div class="stat-icon"><i class="fas fa-arrow-up"></i></div>
        <div class="stat-label">Kas Keluar Terhapus</div>
        <div class="stat-value"><?= $jml_keluar ?> data</div>
        <div class="stat-sub"><i class="fas fa-undo"></i> Bisa dipulihkan</div>
      </div>
    </div>

    <!-- INFO BANNER -->
    <?php if ($jml_total > 0): ?>
    <div style="background:linear-gradient(135deg,rgba(239,68,68,.08),rgba(245,158,11,.08));border:1px solid rgba(239,68,68,.2);border-radius:var(--radius-lg);padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
      <div style="display:flex;align-items:center;gap:10px">
        <i class="fas fa-exclamation-triangle" style="color:var(--warning);font-size:1.2rem"></i>
        <div>
          <div style="font-weight:700;font-size:.9rem;color:var(--text-primary)">
            Ada <?= $jml_total ?> data di tempat sampah
          </div>
          <div style="font-size:.78rem;color:var(--text-muted)">
            Data akan tetap tersimpan sampai Anda menghapus secara permanen
          </div>
        </div>
      </div>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="?pulihkan_semua=1"
           onclick="return confirm('Pulihkan semua <?= $jml_total ?> data?')"
           class="btn btn-success btn-sm">
          <i class="fas fa-undo"></i> Pulihkan Semua
        </a>
        <a href="?kosongkan=1"
           onclick="return confirm('HAPUS PERMANEN semua <?= $jml_total ?> data? Tindakan ini tidak bisa dibatalkan!')"
           class="btn btn-danger btn-sm">
          <i class="fas fa-fire"></i> Kosongkan Sampah
        </a>
      </div>
    </div>
    <?php endif; ?>

    <!-- FILTER -->
    <div class="filter-bar">
      <span class="filter-label"><i class="fas fa-filter"></i> Filter:</span>
      <form method="GET" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
        <select name="jenis" class="form-control form-select" style="max-width:180px" onchange="this.form.submit()">
          <option value="semua"  <?= $filter_jenis=='semua'?'selected':'' ?>>Semua Jenis</option>
          <option value="masuk"  <?= $filter_jenis=='masuk'?'selected':'' ?>>Kas Masuk</option>
          <option value="keluar" <?= $filter_jenis=='keluar'?'selected':'' ?>>Kas Keluar</option>
        </select>
        <span style="font-size:.8rem;color:var(--text-muted)">
          <i class="fas fa-database"></i> <?= $total_rows ?> data
        </span>
      </form>
    </div>

    <!-- TABEL -->
    <div class="table-wrapper animate-fadeIn">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>#</th>
            <th>Tanggal</th>
            <th>Keterangan</th>
            <th>Kategori</th>
            <th>Jenis</th>
            <th class="text-right">Jumlah (Rp)</th>
            <th>Dihapus Pada</th>
            <th style="text-align:center;width:120px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($rows && $rows->num_rows > 0):
            $no = $offset + 1;
            while ($r = $rows->fetch_assoc()): ?>
          <tr style="opacity:.85">
            <td class="text-muted"><?= $no++ ?></td>
            <td><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
            <td>
              <span style="text-decoration:line-through;color:var(--text-muted)">
                <?= htmlspecialchars($r['keterangan']) ?>
              </span>
            </td>
            <td><span class="badge badge-primary"><?= htmlspecialchars($r['nama_kategori']) ?></span></td>
            <td>
              <?= $r['jenis']=='masuk'
                ? '<span class="badge badge-success"><i class="fas fa-arrow-down"></i> Masuk</span>'
                : '<span class="badge badge-danger"><i class="fas fa-arrow-up"></i> Keluar</span>' ?>
            </td>
            <td class="text-right fw-600 <?= $r['jenis']=='masuk'?'text-success':'text-danger' ?>"
                style="text-decoration:line-through;opacity:.7">
              <?= ($r['jenis']=='masuk'?'+':'-') . formatRupiah($r['jumlah']) ?>
            </td>
            <td style="font-size:.78rem;color:var(--text-muted)">
              <i class="fas fa-clock"></i>
              <?= date('d M Y H:i', strtotime($r['deleted_at'])) ?>
            </td>
            <td>
              <div style="display:flex;gap:6px;justify-content:center">
                <!-- Tombol Pulihkan -->
                <a href="?pulihkan=<?= $r['id'] ?>"
                   class="btn btn-success btn-sm"
                   data-tooltip="Pulihkan"
                   onclick="return confirm('Pulihkan data ini?')">
                  <i class="fas fa-undo"></i>
                </a>
                <!-- Tombol Hapus Permanen -->
                <button onclick="confirmHapusPermanent(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['keterangan'])) ?>')"
                        class="btn btn-danger btn-sm"
                        data-tooltip="Hapus Permanen">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
          <?php endwhile;
          else: ?>
          <tr>
            <td colspan="8">
              <div class="empty-state" style="padding:60px 20px">
                <div class="es-icon"><i class="fas fa-check-circle" style="color:var(--success)"></i></div>
                <h3>Tempat Sampah Kosong!</h3>
                <p>Tidak ada data yang dihapus saat ini. Semua data Anda aman.</p>
              </div>
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- PAGINATION -->
    <?php if ($total_pages > 1): ?>
    <div style="display:flex;justify-content:center;margin-top:20px">
      <div class="pagination">
        <?php if ($page > 1): ?>
          <a href="?jenis=<?= $filter_jenis ?>&page=<?= $page-1 ?>" class="page-btn"><i class="fas fa-angle-left"></i></a>
        <?php endif; ?>
        <?php for ($p = max(1,$page-2); $p <= min($total_pages,$page+2); $p++): ?>
          <a href="?jenis=<?= $filter_jenis ?>&page=<?= $p ?>" class="page-btn <?= $p==$page?'active':'' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?>
          <a href="?jenis=<?= $filter_jenis ?>&page=<?= $page+1 ?>" class="page-btn"><i class="fas fa-angle-right"></i></a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- INFO BOX -->
    <div style="margin-top:24px;background:var(--bg-main);border-radius:var(--radius-lg);padding:20px 24px;border:1px solid var(--border-light)">
      <h4 style="font-size:.875rem;font-weight:700;color:var(--text-primary);margin-bottom:12px">
        <i class="fas fa-info-circle" style="color:var(--info)"></i> Informasi Tempat Sampah
      </h4>
      <ul style="display:flex;flex-direction:column;gap:8px">
        <?php $infos=[
          ['fas fa-undo','success','Pulihkan','Kembalikan data ke kas masuk/keluar seperti semula'],
          ['fas fa-trash','danger','Hapus Permanen','Data dihapus selamanya dan tidak bisa dikembalikan'],
          ['fas fa-fire','warning','Kosongkan Sampah','Hapus semua data di tempat sampah sekaligus'],
          ['fas fa-check-circle','success','Data Aman','Data yang di-trash tidak mempengaruhi laporan dan saldo'],
        ]; foreach($infos as [$ico,$col,$judul,$desc]): ?>
        <li style="display:flex;align-items:flex-start;gap:10px;font-size:.82rem;color:var(--text-secondary)">
          <i class="<?= $ico ?>" style="color:var(--<?= $col ?>);margin-top:2px;flex-shrink:0;width:16px;text-align:center"></i>
          <div><strong><?= $judul ?>:</strong> <?= $desc ?></div>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>

  </div><!-- /admin-content -->
</div><!-- /admin-main -->
</div><!-- /admin-wrapper -->

<!-- Modal Konfirmasi Hapus Permanen -->
<div class="modal-overlay" id="modalHapus">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">
        <i class="fas fa-exclamation-triangle" style="color:var(--danger)"></i>
        Hapus Permanen
      </div>
      <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <div style="text-align:center;margin-bottom:16px">
        <div style="width:64px;height:64px;background:rgba(239,68,68,.1);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.8rem;color:var(--danger);margin:0 auto 14px">
          <i class="fas fa-trash"></i>
        </div>
        <h3 style="font-size:1rem;font-weight:700;color:var(--text-primary);margin-bottom:8px">
          Yakin ingin menghapus permanen?
        </h3>
        <p style="font-size:.875rem;color:var(--text-muted)">Data berikut akan dihapus selamanya:</p>
      </div>
      <div style="background:var(--bg-main);padding:14px;border-radius:var(--radius-sm);text-align:center;font-weight:600;color:var(--text-primary);margin-bottom:12px" id="namaDataHapus"></div>
      <div class="alert alert-danger" style="margin-bottom:0">
        <i class="fas fa-warning"></i>
        <strong>Peringatan:</strong> Tindakan ini tidak dapat dibatalkan!
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal()">
        <i class="fas fa-times"></i> Batal
      </button>
      <a id="btnHapusPermanent" class="btn btn-danger">
        <i class="fas fa-trash"></i> Ya, Hapus Permanen
      </a>
    </div>
  </div>
</div>

<script>
function confirmHapusPermanent(id, nama) {
  document.getElementById('namaDataHapus').textContent  = nama;
  document.getElementById('btnHapusPermanent').href = '?hapus=' + id + '&jenis=<?= $filter_jenis ?>&page=<?= $page ?>';
  document.getElementById('modalHapus').classList.add('active');
}
function closeModal() {
  document.getElementById('modalHapus').classList.remove('active');
}
document.getElementById('modalHapus').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

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
