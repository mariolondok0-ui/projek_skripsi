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

// PERBAIKAN ERROR AMBIGUOUS: Tambahkan inisial "t." di depan nama kolom
$where = "t.deleted_at IS NOT NULL";
if ($filter_jenis !== 'semua') {
    $where .= " AND t.jenis='" . sanitize($filter_jenis) . "'";
}

// PERBAIKAN: Tambahkan alias "t" pada tabel transaksi di query COUNT
$total_rows  = $conn->query("SELECT COUNT(*) as c FROM transaksi t WHERE $where")->fetch_assoc()['c'];
$total_pages = max(1, ceil($total_rows / $per_page));
$offset      = ($page - 1) * $per_page;

// PERBAIKAN: WHERE dipanggil langsung karena inisial "t." sudah ada di variabel string $where
$rows = $conn->query("
    SELECT t.*, k.nama_kategori
    FROM transaksi t
    JOIN kategori k ON t.kategori_id = k.id
    WHERE $where
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
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
<title>Tempat Sampah – <?= APP_NAME ?></title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css?v=1786264272">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* =========================================================
   TAMBAHAN CSS RESPONSIVE AGRESIF UNTUK HP (TEMPAT SAMPAH)
   ========================================================= */
.table-wrapper {
    display: block !important;
    width: 100% !important;
    overflow-x: auto !important;
    -webkit-overflow-scrolling: touch !important;
    border-radius: 12px;
}

/* PERBAIKAN TOTAL UNTUK KOTAK SELECT (DROPDOWN) AGAR ELEGAN */
select.form-select {
    font-family: 'Segoe UI', 'Poppins', Tahoma, Geneva, Verdana, sans-serif !important;
    font-size: 0.95rem !important;
    font-weight: 500 !important;
    color: #1f2937 !important;
    padding: 12px 16px !important;
    border: 1px solid #d1d5db !important;
    border-radius: 8px !important;
    background-color: #f9fafb !important;
    appearance: none !important; /* Menghilangkan panah jelek bawaan browser */
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
    /* Membuat panah custom yang rapi */
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E") !important;
    background-repeat: no-repeat !important;
    background-position: right 14px center !important;
    background-size: 18px !important;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02) !important;
    transition: all 0.2s ease;
}
select.form-select:focus {
    border-color: #1e6eb5 !important;
    outline: none !important;
    box-shadow: 0 0 0 3px rgba(30,110,181,0.15) !important;
    background-color: #ffffff !important;
}
select.form-select option {
    font-family: 'Segoe UI', 'Poppins', sans-serif !important;
    font-weight: 500 !important;
    color: #333 !important;
    padding: 10px !important;
}

@media (max-width: 768px) {
    html, body { overflow-x: hidden !important; max-width: 100vw !important; }
    
    .admin-wrapper { display: block !important; width: 100% !important; overflow-x: hidden !important; }
    .admin-main { width: 100% !important; margin-left: 0 !important; padding: 0 !important; box-sizing: border-box !important; }
    
    .admin-content { width: 100% !important; padding: 12px !important; box-sizing: border-box !important; margin: 0 !important; }
    .topbar { width: 100% !important; box-sizing: border-box !important; padding: 12px 15px !important; }
    .t-name { display: none !important; }

    /* Merapikan Header & Tombol */
    .page-header > div { 
        flex-direction: column !important; 
        align-items: flex-start !important; 
        gap: 15px !important; 
        text-align: left !important; 
    }
    
    .page-header a.btn { 
        width: 100% !important;
        justify-content: center !important; 
        align-items: center !important;
        display: inline-flex !important;
        padding: 12px !important;
        background: var(--bg-card) !important;
        box-shadow: 0 2px 6px rgba(0,0,0,0.04) !important; 
        border-radius: 8px !important;
    }
    
    .page-title { font-size: 1.35rem !important; margin-bottom: 4px !important; }
    .page-subtitle { font-size: 0.85rem !important; margin-bottom: 5px !important; line-height: 1.5 !important; }

    /* 3 Kotak Stat Atas (Total Lebar Penuh, Sisanya Berbagi 50:50) */
    .grid-3 { 
        display: grid !important; 
        grid-template-columns: repeat(2, 1fr) !important; 
        gap: 12px !important; 
        width: 100% !important; 
    }
    .grid-3 > div:first-child { grid-column: span 2 !important; }
    .stat-card { padding: 15px 12px !important; border-radius: 12px !important; width: auto !important; margin: 0 !important; }
    .stat-label { font-size: 0.72rem !important; }
    .stat-value { font-size: 1.05rem !important; margin: 4px 0 !important; }
    .stat-sub { font-size: 0.7rem !important; }

    /* Merapikan Info Banner Peringatan */
    .info-banner {
        flex-direction: column !important;
        text-align: left !important; 
        gap: 15px !important;
        padding: 15px !important;
    }
    .info-banner > div:first-child {
        flex-direction: row !important; 
        justify-content: flex-start !important;
        text-align: left !important;
    }
    .info-banner > div:last-child {
        width: 100% !important;
        flex-direction: column !important; 
        gap: 10px !important;
    }
    .info-banner .btn {
        width: 100% !important;
        justify-content: center !important;
    }

    /* Merapikan Filter Bar (Dropdown) */
    .filter-bar { 
        flex-direction: column !important; 
        padding: 15px !important; 
        border-radius: 12px !important; 
        gap: 12px !important; 
        text-align: left !important; 
        align-items: stretch !important;
    }
    .filter-bar form { 
        flex-direction: column !important; 
        gap: 10px !important; 
        width: 100% !important; 
        align-items: stretch !important;
    }
    .filter-bar .form-control { 
        width: 100% !important; 
        max-width: none !important;
        box-sizing: border-box !important; 
    }
    
    /* Tabel (Dipaksa lebar supaya bisa digeser ke samping) */
    .table-wrapper table { min-width: 800px !important; }
    .table th, .table td { padding: 10px 8px !important; font-size: 0.85rem !important; }
}
/* ========================================================= */
</style>
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
    <div style="margin-bottom:24px; padding-bottom:16px; border-bottom:1px dashed var(--border-light);">
      
      <!-- Baris Atas: Judul & Tombol -->
      <div style="display:flex; align-items:center; justify-content:space-between; gap:15px; margin-bottom:8px;">
        <div style="display:flex; align-items:center; gap:10px;">
          <div style="width:36px; height:36px; background:rgba(239,68,68,0.1); border-radius:8px; display:flex; align-items:center; justify-content:center; color:var(--danger); font-size:1.1rem; flex-shrink:0;">
            <i class="fas fa-trash-alt"></i>
          </div>
          <h1 style="font-size:1.25rem; font-weight:800; color:var(--text-primary); margin:0;">Tempat Sampah</h1>
        </div>
        <a href="<?= APP_URL ?>/admin/dashboard.php" style="flex-shrink:0; background:var(--bg-main); border:1px solid var(--border-light); padding:8px 14px; border-radius:8px; box-shadow:0 1px 2px rgba(0,0,0,0.05); color:var(--text-primary); text-decoration:none; display:flex; align-items:center; gap:6px; font-size:0.85rem; font-weight:600; transition:all 0.2s;">
          <i class="fas fa-arrow-left"></i> Kembali
        </a>
      </div>

      <!-- Baris Bawah: Deskripsi -->
      <p style="font-size:0.82rem; color:var(--text-muted); margin:0; line-height:1.5;">
        Data yang dihapus tersimpan di sini — bisa dipulihkan atau dihapus permanen
      </p>
      
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
    <div class="info-banner" style="background:linear-gradient(135deg,rgba(239,68,68,.08),rgba(245,158,11,.08));border:1px solid rgba(239,68,68,.2);border-radius:var(--radius-lg);padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
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
            <th style="white-space:nowrap;">Tanggal</th>
            <th>Keterangan</th>
            <th>Kategori</th>
            <th>Jenis</th>
            <th class="text-right">Jumlah (Rp)</th>
            <th style="white-space:nowrap;">Dihapus Pada</th>
            <th style="text-align:center;width:120px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($rows && $rows->num_rows > 0):
            $no = $offset + 1;
            while ($r = $rows->fetch_assoc()): ?>
          <tr style="opacity:.85">
            <td class="text-muted"><?= $no++ ?></td>
            <td style="white-space:nowrap;"><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
            <td>
              <span style="text-decoration:line-through;color:var(--text-muted)">
                <?= htmlspecialchars($r['keterangan']) ?>
              </span>
            </td>
            <td><span class="badge badge-primary" style="white-space:nowrap;"><?= htmlspecialchars($r['nama_kategori']) ?></span></td>
            <td style="white-space:nowrap;">
              <?= $r['jenis']=='masuk'
                ? '<span class="badge badge-success"><i class="fas fa-arrow-down"></i> Masuk</span>'
                : '<span class="badge badge-danger"><i class="fas fa-arrow-up"></i> Keluar</span>' ?>
            </td>
            <td class="text-right fw-600 <?= $r['jenis']=='masuk'?'text-success':'text-danger' ?>"
                style="text-decoration:line-through;opacity:.7;white-space:nowrap;">
              <?= ($r['jenis']=='masuk'?'+':'-') . formatRupiah($r['jumlah']) ?>
            </td>
            <td style="font-size:.78rem;color:var(--text-muted);white-space:nowrap;">
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
                <div class="es-icon"><i class="fas fa-check-circle" style="color:var(--info)"></i></div>
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