<?php
require_once '../includes/config.php';
requireLogin();

// ---- Handle POST (tambah/edit) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id          = (int)($_POST['id'] ?? 0);
    $tanggal     = sanitize($_POST['tanggal'] ?? '');
    $keterangan  = sanitize($_POST['keterangan'] ?? '');
    $jumlah      = (float)str_replace(['.', ','], ['', '.'], $_POST['jumlah'] ?? 0);
    $kategori_id = (int)($_POST['kategori_id'] ?? 0);
    $user_id     = (int)$_SESSION['admin_id'];

    if (empty($tanggal) || empty($keterangan) || $jumlah <= 0 || !$kategori_id) {
        setAlert('danger', 'Semua field wajib diisi dengan benar.');
    } else {
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE transaksi SET tanggal=?,keterangan=?,jumlah=?,kategori_id=? WHERE id=? AND jenis='masuk'");
            $stmt->bind_param('ssdii', $tanggal, $keterangan, $jumlah, $kategori_id, $id);
            $stmt->execute();
            setAlert('success', 'Data kas masuk berhasil diperbarui.');
        } else {
            $jenis = 'masuk';
            $stmt = $conn->prepare("INSERT INTO transaksi (tanggal,keterangan,jumlah,jenis,kategori_id,user_id) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param('ssdsii', $tanggal, $keterangan, $jumlah, $jenis, $kategori_id, $user_id);
            $stmt->execute();
            setAlert('success', 'Data kas masuk berhasil ditambahkan.');
        }
    }
    redirect(APP_URL . '/admin/kas-masuk.php');
}

// ---- Handle DELETE ----
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM transaksi WHERE id=$id AND jenis='masuk'");
    setAlert('success', 'Data kas masuk berhasil dihapus.');
    redirect(APP_URL . '/admin/kas-masuk.php');
}

// ---- Handle EDIT (prefill form) ----
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $edit_data = $conn->query("SELECT * FROM transaksi WHERE id=$id AND jenis='masuk'")->fetch_assoc();
}

// ---- Kategori Masuk ----
$kategori_list = $conn->query("SELECT * FROM kategori WHERE jenis='masuk' ORDER BY nama_kategori");

// ---- Filter ----
$filter_bulan = sanitize($_GET['bulan'] ?? date('Y-m'));
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;

$where = "t.jenis='masuk' AND DATE_FORMAT(t.tanggal,'%Y-%m')='$filter_bulan'";
$where_simple = "jenis='masuk' AND DATE_FORMAT(tanggal,'%Y-%m')='$filter_bulan'";
$total_rows  = $conn->query("SELECT COUNT(*) as c FROM transaksi WHERE $where_simple")->fetch_assoc()['c'];
$total_pages = max(1, ceil($total_rows / $per_page));
$offset      = ($page - 1) * $per_page;

$rows = $conn->query("SELECT t.*, k.nama_kategori FROM transaksi t JOIN kategori k ON t.kategori_id=k.id WHERE $where ORDER BY t.tanggal DESC, t.id DESC LIMIT $per_page OFFSET $offset");
$summary = $conn->query("SELECT COALESCE(SUM(jumlah),0) as total, COUNT(*) as cnt FROM transaksi WHERE $where_simple")->fetch_assoc();

$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Kas Masuk – <?= APP_NAME ?></title>
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
        <span class="bc-item active">Kas Masuk</span>
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
      <?= htmlspecialchars($alert['message']) ?>
    </div>
    <?php endif; ?>

    <div class="page-header">
      <h1 class="page-title"><i class="fas fa-arrow-circle-down" style="color:var(--success)"></i> Kas Masuk</h1>
      <p class="page-subtitle">Kelola data pemasukan kas <?= MASJID_NAME ?></p>
    </div>

    <!-- Form Tambah/Edit -->
    <div class="card mb-3 animate-fadeIn">
      <div class="card-header">
        <div class="card-title">
          <i class="fas fa-<?= $edit_data ? 'edit' : 'plus-circle' ?>"></i>
          <?= $edit_data ? 'Edit Data Kas Masuk' : 'Tambah Kas Masuk' ?>
        </div>
        <?php if ($edit_data): ?>
        <a href="kas-masuk.php" class="btn btn-ghost btn-sm"><i class="fas fa-times"></i> Batal Edit</a>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <form method="POST" id="formKasMasuk">
          <?php if ($edit_data): ?>
          <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
          <?php endif; ?>
          <div class="grid-2" style="gap:16px">
            <div class="form-group mb-0">
              <label class="form-label">Tanggal <span class="required">*</span></label>
              <div class="input-group">
                <i class="fas fa-calendar input-icon"></i>
                <input type="date" name="tanggal" class="form-control"
                       value="<?= $edit_data['tanggal'] ?? date('Y-m-d') ?>" required>
              </div>
            </div>
            <div class="form-group mb-0">
              <label class="form-label">Kategori <span class="required">*</span></label>
              <div class="input-group">
                <i class="fas fa-tag input-icon"></i>
                <select name="kategori_id" class="form-control form-select" required>
                  <option value="">-- Pilih Kategori --</option>
                  <?php while ($k = $kategori_list->fetch_assoc()): ?>
                  <option value="<?= $k['id'] ?>" <?= ($edit_data['kategori_id']??'')==$k['id']?'selected':'' ?>>
                    <?= htmlspecialchars($k['nama_kategori']) ?>
                  </option>
                  <?php endwhile; ?>
                </select>
              </div>
            </div>
            <div class="form-group mb-0">
              <label class="form-label">Jumlah (Rp) <span class="required">*</span></label>
              <div class="input-group">
                <i class="fas fa-money-bill input-icon"></i>
                <input type="text" name="jumlah" id="jumlahInput" class="form-control"
                       placeholder="0" required
                       value="<?= $edit_data ? number_format($edit_data['jumlah'],0,',','.') : '' ?>">
              </div>
            </div>
            <div class="form-group mb-0">
              <label class="form-label">Keterangan <span class="required">*</span></label>
              <div class="input-group">
                <i class="fas fa-align-left input-icon"></i>
                <input type="text" name="keterangan" class="form-control"
                       placeholder="Contoh: Infak Jumat Minggu 1"
                       value="<?= htmlspecialchars($edit_data['keterangan'] ?? '') ?>" required>
              </div>
            </div>
          </div>
          <div style="margin-top:20px;display:flex;gap:12px">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save"></i> <?= $edit_data ? 'Simpan Perubahan' : 'Tambah Data' ?>
            </button>
            <?php if (!$edit_data): ?>
            <button type="reset" class="btn btn-ghost"><i class="fas fa-undo"></i> Reset</button>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <!-- Summary & Filter -->
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:16px">
      <div style="display:flex;gap:16px;flex-wrap:wrap">
        <div style="background:var(--bg-card);padding:14px 20px;border-radius:var(--radius);box-shadow:var(--shadow-sm);display:flex;align-items:center;gap:10px">
          <i class="fas fa-arrow-down" style="color:var(--success)"></i>
          <div><div style="font-size:.72rem;color:var(--text-muted)">Total Bulan Ini</div>
          <div style="font-weight:800;color:var(--success)"><?= formatRupiah($summary['total']) ?></div></div>
        </div>
        <div style="background:var(--bg-card);padding:14px 20px;border-radius:var(--radius);box-shadow:var(--shadow-sm);display:flex;align-items:center;gap:10px">
          <i class="fas fa-list" style="color:var(--info)"></i>
          <div><div style="font-size:.72rem;color:var(--text-muted)">Jumlah Transaksi</div>
          <div style="font-weight:800;color:var(--info)"><?= $summary['cnt'] ?> transaksi</div></div>
        </div>
      </div>
      <form method="GET" style="display:flex;align-items:center;gap:10px">
        <label style="font-size:.85rem;font-weight:600;color:var(--text-secondary)"><i class="fas fa-filter"></i> Bulan:</label>
        <input type="month" name="bulan" value="<?= $filter_bulan ?>" class="form-control" style="width:160px" onchange="this.form.submit()">
      </form>
    </div>

    <!-- Tabel -->
    <div class="table-wrapper animate-fadeIn">
      <table class="table table-striped">
        <thead>
          <tr><th>#</th><th>Tanggal</th><th>Keterangan</th><th>Kategori</th><th class="text-right">Jumlah (Rp)</th><th style="width:90px">Aksi</th></tr>
        </thead>
        <tbody>
          <?php if ($rows->num_rows):
            $no = $offset + 1;
            while ($r = $rows->fetch_assoc()): ?>
          <tr>
            <td class="text-muted"><?= $no++ ?></td>
            <td><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
            <td><?= htmlspecialchars($r['keterangan']) ?></td>
            <td><span class="badge badge-success"><?= htmlspecialchars($r['nama_kategori']) ?></span></td>
            <td class="text-right fw-600 text-success">+ <?= number_format($r['jumlah'],0,',','.') ?></td>
            <td>
              <div style="display:flex;gap:6px">
                <a href="?edit=<?= $r['id'] ?>" class="btn btn-ghost btn-icon btn-sm" data-tooltip="Edit"><i class="fas fa-edit"></i></a>
                <button onclick="confirmDelete(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['keterangan'])) ?>')"
                        class="btn btn-icon btn-sm" style="background:rgba(239,68,68,.1);color:var(--danger)" data-tooltip="Hapus">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
          <?php endwhile;
          else: ?>
          <tr><td colspan="6"><div class="empty-state"><div class="es-icon"><i class="fas fa-inbox"></i></div><h3>Belum ada data kas masuk</h3><p>Tambahkan data menggunakan form di atas</p></div></td></tr>
          <?php endif; ?>
        </tbody>
        <?php if ($rows->num_rows): ?>
        <tfoot>
          <tr>
            <td colspan="4" class="text-right">Total:</td>
            <td class="text-right text-success">+ <?= number_format($summary['total'],0,',','.') ?></td>
            <td></td>
          </tr>
        </tfoot>
        <?php endif; ?>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div style="display:flex;justify-content:center;margin-top:20px">
      <div class="pagination">
        <?php for ($p=1; $p<=$total_pages; $p++): ?>
        <a href="?bulan=<?= $filter_bulan ?>&page=<?= $p ?>" class="page-btn <?= $p==$page?'active':'' ?>"><?= $p ?></a>
        <?php endfor; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>
</div>

<!-- Confirm Delete Modal -->
<div class="modal-overlay" id="deleteModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title"><i class="fas fa-exclamation-triangle" style="color:var(--danger)"></i> Konfirmasi Hapus</div>
      <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <p>Apakah Anda yakin ingin menghapus data:</p>
      <div style="background:var(--bg-main);padding:14px;border-radius:var(--radius-sm);margin-top:10px;font-weight:600" id="deleteItemName"></div>
      <p style="color:var(--danger);font-size:.875rem;margin-top:12px"><i class="fas fa-warning"></i> Data yang dihapus tidak dapat dikembalikan.</p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal()">Batal</button>
      <a id="deleteConfirmBtn" class="btn btn-danger"><i class="fas fa-trash"></i> Ya, Hapus</a>
    </div>
  </div>
</div>

<script>
// Format currency input
document.getElementById('jumlahInput').addEventListener('input', function() {
  let val = this.value.replace(/\D/g,'');
  this.value = val ? new Intl.NumberFormat('id-ID').format(val) : '';
});

function confirmDelete(id, name) {
  document.getElementById('deleteItemName').textContent = name;
  document.getElementById('deleteConfirmBtn').href = '?delete=' + id;
  document.getElementById('deleteModal').classList.add('active');
}
function closeModal() { document.getElementById('deleteModal').classList.remove('active'); }
document.getElementById('deleteModal').addEventListener('click', function(e){ if(e.target===this) closeModal(); });

// Sidebar toggle
const sidebar = document.getElementById('adminSidebar');
const overlay = document.getElementById('sidebarOverlay');
document.getElementById('sidebarToggle').addEventListener('click',()=>{ sidebar.classList.toggle('open'); overlay.classList.toggle('active'); });
overlay.addEventListener('click',()=>{ sidebar.classList.remove('open'); overlay.classList.remove('active'); });
</script>
</body>
</html>
