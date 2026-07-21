<?php
require_once '../includes/config.php';
requireLogin();

// Handle POST (tambah/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id            = (int)($_POST['id'] ?? 0);
    $nama_kategori = sanitize($_POST['nama_kategori'] ?? '');
    $jenis         = sanitize($_POST['jenis'] ?? '');

    if (empty($nama_kategori) || !in_array($jenis, ['masuk','keluar'])) {
        setAlert('danger', 'Nama kategori dan jenis wajib diisi.');
    } else {
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE kategori SET nama_kategori=?, jenis=? WHERE id=?");
            $stmt->bind_param('ssi', $nama_kategori, $jenis, $id);
            $stmt->execute();
            setAlert('success', 'Kategori berhasil diperbarui.');
        } else {
            $stmt = $conn->prepare("INSERT INTO kategori (nama_kategori, jenis) VALUES (?,?)");
            $stmt->bind_param('ss', $nama_kategori, $jenis);
            $stmt->execute();
            setAlert('success', 'Kategori berhasil ditambahkan.');
        }
    }
    redirect(APP_URL . '/admin/kategori.php');
}

// Handle DELETE
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Cek apakah masih ada transaksi yang pakai kategori ini
    $used = $conn->query("SELECT COUNT(*) as c FROM transaksi WHERE kategori_id=$id")->fetch_assoc()['c'];
    if ($used > 0) {
        setAlert('danger', 'Kategori tidak dapat dihapus karena masih digunakan oleh ' . $used . ' transaksi.');
    } else {
        $conn->query("DELETE FROM kategori WHERE id=$id");
        setAlert('success', 'Kategori berhasil dihapus.');
    }
    redirect(APP_URL . '/admin/kategori.php');
}

// Edit prefill
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $edit_data = $conn->query("SELECT * FROM kategori WHERE id=$id")->fetch_assoc();
}

// Data semua kategori dengan hitungan transaksi
$list_masuk  = $conn->query("SELECT k.*, COUNT(t.id) as jml FROM kategori k LEFT JOIN transaksi t ON t.kategori_id=k.id WHERE k.jenis='masuk'  GROUP BY k.id ORDER BY k.nama_kategori");
$list_keluar = $conn->query("SELECT k.*, COUNT(t.id) as jml FROM kategori k LEFT JOIN transaksi t ON t.kategori_id=k.id WHERE k.jenis='keluar' GROUP BY k.id ORDER BY k.nama_kategori");

$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Kategori – <?= APP_NAME ?></title>
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
        <span class="bc-item active">Kelola Kategori</span>
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
      <h1 class="page-title"><i class="fas fa-tags"></i> Kelola Kategori</h1>
      <p class="page-subtitle">Atur kategori pemasukan dan pengeluaran kas masjid</p>
    </div>

    <div class="grid-2" style="align-items:start">

      <!-- Form -->
      <div class="card animate-fadeIn">
        <div class="card-header">
          <div class="card-title">
            <i class="fas fa-<?= $edit_data ? 'edit' : 'plus-circle' ?>"></i>
            <?= $edit_data ? 'Edit Kategori' : 'Tambah Kategori Baru' ?>
          </div>
          <?php if ($edit_data): ?>
          <a href="kategori.php" class="btn btn-ghost btn-sm"><i class="fas fa-times"></i> Batal</a>
          <?php endif; ?>
        </div>
        <div class="card-body">
          <form method="POST">
            <?php if ($edit_data): ?><input type="hidden" name="id" value="<?= $edit_data['id'] ?>"><?php endif; ?>
            <div class="form-group">
              <label class="form-label">Nama Kategori <span class="required">*</span></label>
              <div class="input-group">
                <i class="fas fa-tag input-icon"></i>
                <input type="text" name="nama_kategori" class="form-control"
                       placeholder="Contoh: Infak Jumat"
                       value="<?= htmlspecialchars($edit_data['nama_kategori'] ?? '') ?>" required>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Jenis <span class="required">*</span></label>
              <div style="display:flex;gap:16px;margin-top:4px">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:12px 16px;border:1.5px solid var(--border);border-radius:var(--radius-sm);flex:1;transition:var(--transition-fast)" id="lblMasuk">
                  <input type="radio" name="jenis" value="masuk" <?= ($edit_data['jenis']??'masuk')=='masuk'?'checked':'' ?> style="accent-color:var(--success)">
                  <i class="fas fa-arrow-down" style="color:var(--success)"></i>
                  <span style="font-weight:600;font-size:.875rem">Kas Masuk</span>
                </label>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:12px 16px;border:1.5px solid var(--border);border-radius:var(--radius-sm);flex:1;transition:var(--transition-fast)" id="lblKeluar">
                  <input type="radio" name="jenis" value="keluar" <?= ($edit_data['jenis']??'')=='keluar'?'checked':'' ?> style="accent-color:var(--danger)">
                  <i class="fas fa-arrow-up" style="color:var(--danger)"></i>
                  <span style="font-weight:600;font-size:.875rem">Kas Keluar</span>
                </label>
              </div>
            </div>
            <div style="display:flex;gap:10px;margin-top:4px">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> <?= $edit_data ? 'Simpan Perubahan' : 'Tambah Kategori' ?>
              </button>
              <?php if (!$edit_data): ?>
              <button type="reset" class="btn btn-ghost"><i class="fas fa-undo"></i> Reset</button>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>

      <!-- Daftar Kategori -->
      <div style="display:flex;flex-direction:column;gap:20px">

        <!-- Kategori Masuk -->
        <div class="card animate-fadeIn delay-1">
          <div class="card-header">
            <div class="card-title" style="color:var(--success)"><i class="fas fa-arrow-down"></i> Kategori Pemasukan</div>
            <span class="badge badge-success"><?= $list_masuk->num_rows ?> kategori</span>
          </div>
          <div class="card-body" style="padding:0">
            <table class="table">
              <thead>
                <tr><th>Nama Kategori</th><th class="text-center">Digunakan</th><th style="width:80px">Aksi</th></tr>
              </thead>
              <tbody>
                <?php if ($list_masuk->num_rows): while ($k = $list_masuk->fetch_assoc()): ?>
                <tr>
                  <td>
                    <div style="display:flex;align-items:center;gap:8px">
                      <span style="width:8px;height:8px;border-radius:50%;background:var(--success);flex-shrink:0"></span>
                      <?= htmlspecialchars($k['nama_kategori']) ?>
                    </div>
                  </td>
                  <td class="text-center"><span class="badge badge-info"><?= $k['jml'] ?>x</span></td>
                  <td>
                    <div style="display:flex;gap:4px">
                      <a href="?edit=<?= $k['id'] ?>" class="btn btn-ghost btn-icon btn-sm" data-tooltip="Edit"><i class="fas fa-edit"></i></a>
                      <?php if ($k['jml'] == 0): ?>
                      <button onclick="confirmDelete(<?= $k['id'] ?>,'<?= htmlspecialchars(addslashes($k['nama_kategori'])) ?>')"
                              class="btn btn-icon btn-sm" style="background:rgba(239,68,68,.1);color:var(--danger)" data-tooltip="Hapus">
                        <i class="fas fa-trash"></i>
                      </button>
                      <?php else: ?>
                      <span class="btn btn-icon btn-sm" style="opacity:.3;cursor:not-allowed" data-tooltip="Tidak dapat dihapus">
                        <i class="fas fa-lock"></i>
                      </span>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="3"><div class="empty-state" style="padding:24px"><div class="es-icon"><i class="fas fa-tags"></i></div><h3>Belum ada kategori</h3></div></td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Kategori Keluar -->
        <div class="card animate-fadeIn delay-2">
          <div class="card-header">
            <div class="card-title" style="color:var(--danger)"><i class="fas fa-arrow-up"></i> Kategori Pengeluaran</div>
            <span class="badge badge-danger"><?= $list_keluar->num_rows ?> kategori</span>
          </div>
          <div class="card-body" style="padding:0">
            <table class="table">
              <thead>
                <tr><th>Nama Kategori</th><th class="text-center">Digunakan</th><th style="width:80px">Aksi</th></tr>
              </thead>
              <tbody>
                <?php if ($list_keluar->num_rows): while ($k = $list_keluar->fetch_assoc()): ?>
                <tr>
                  <td>
                    <div style="display:flex;align-items:center;gap:8px">
                      <span style="width:8px;height:8px;border-radius:50%;background:var(--danger);flex-shrink:0"></span>
                      <?= htmlspecialchars($k['nama_kategori']) ?>
                    </div>
                  </td>
                  <td class="text-center"><span class="badge badge-info"><?= $k['jml'] ?>x</span></td>
                  <td>
                    <div style="display:flex;gap:4px">
                      <a href="?edit=<?= $k['id'] ?>" class="btn btn-ghost btn-icon btn-sm" data-tooltip="Edit"><i class="fas fa-edit"></i></a>
                      <?php if ($k['jml'] == 0): ?>
                      <button onclick="confirmDelete(<?= $k['id'] ?>,'<?= htmlspecialchars(addslashes($k['nama_kategori'])) ?>')"
                              class="btn btn-icon btn-sm" style="background:rgba(239,68,68,.1);color:var(--danger)" data-tooltip="Hapus">
                        <i class="fas fa-trash"></i>
                      </button>
                      <?php else: ?>
                      <span class="btn btn-icon btn-sm" style="opacity:.3;cursor:not-allowed" data-tooltip="Tidak dapat dihapus"><i class="fas fa-lock"></i></span>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="3"><div class="empty-state" style="padding:24px"><div class="es-icon"><i class="fas fa-tags"></i></div><h3>Belum ada kategori</h3></div></td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
</div>

<!-- Modal Delete -->
<div class="modal-overlay" id="deleteModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title"><i class="fas fa-trash" style="color:var(--danger)"></i> Hapus Kategori</div>
      <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <p>Hapus kategori: <strong id="deleteItemName"></strong>?</p>
      <p style="color:var(--danger);font-size:.875rem;margin-top:10px"><i class="fas fa-warning"></i> Tindakan ini tidak dapat dibatalkan.</p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal()">Batal</button>
      <a id="deleteConfirmBtn" class="btn btn-danger"><i class="fas fa-trash"></i> Hapus</a>
    </div>
  </div>
</div>

<script>
function confirmDelete(id, name) {
  document.getElementById('deleteItemName').textContent = name;
  document.getElementById('deleteConfirmBtn').href = '?delete=' + id;
  document.getElementById('deleteModal').classList.add('active');
}
function closeModal() { document.getElementById('deleteModal').classList.remove('active'); }
document.getElementById('deleteModal').addEventListener('click', function(e){ if(e.target===this) closeModal(); });

// Radio jenis highlight
document.querySelectorAll('input[name="jenis"]').forEach(r => {
  r.addEventListener('change', () => {
    document.getElementById('lblMasuk').style.borderColor  = r.value==='masuk'  ? 'var(--success)' : 'var(--border)';
    document.getElementById('lblKeluar').style.borderColor = r.value==='keluar' ? 'var(--danger)'  : 'var(--border)';
  });
});

const sidebar = document.getElementById('adminSidebar');
const overlay = document.getElementById('sidebarOverlay');
document.getElementById('sidebarToggle').addEventListener('click',()=>{ sidebar.classList.toggle('open'); overlay.classList.toggle('active'); });
overlay.addEventListener('click',()=>{ sidebar.classList.remove('open'); overlay.classList.remove('active'); });
</script>
</body>
</html>
