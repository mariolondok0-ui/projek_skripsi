<?php
require_once '../includes/config.php';
requireLogin();

// Handle POST
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
    redirect(APP_URL.'/admin/kategori.php');
}

// Handle DELETE
if (isset($_GET['delete'])) {
    $id   = (int)$_GET['delete'];
    $used = (int)$conn->query("SELECT COUNT(*) as c FROM transaksi WHERE kategori_id=$id")->fetch_assoc()['c'];
    if ($used > 0) {
        setAlert('danger', 'Kategori tidak dapat dihapus karena masih digunakan oleh '.$used.' transaksi.');
    } else {
        $conn->query("DELETE FROM kategori WHERE id=$id");
        setAlert('success', 'Kategori berhasil dihapus.');
    }
    redirect(APP_URL.'/admin/kategori.php');
}

// Edit prefill
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $edit_data = $conn->query("SELECT * FROM kategori WHERE id=$id")->fetch_assoc();
}

$list_masuk  = $conn->query("SELECT k.*, COUNT(t.id) as jml FROM kategori k LEFT JOIN transaksi t ON t.kategori_id=k.id WHERE k.jenis='masuk'  GROUP BY k.id ORDER BY k.nama_kategori");
$list_keluar = $conn->query("SELECT k.*, COUNT(t.id) as jml FROM kategori k LEFT JOIN transaksi t ON t.kategori_id=k.id WHERE k.jenis='keluar' GROUP BY k.id ORDER BY k.nama_kategori");
$total_masuk  = $list_masuk->num_rows;
$total_keluar = $list_keluar->num_rows;
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
<style>
/* ===== KATEGORI PAGE ===== */
.cat-hero {
  background: linear-gradient(135deg,#0f3d26 0%,#1a7a4a 60%,#0d9488 100%);
  border-radius: var(--radius-xl);
  padding: 24px 22px;
  color: #fff;
  display: flex; align-items: center; gap: 18px;
  position: relative; overflow: hidden;
  margin-bottom: 24px;
}
.cat-hero::before {
  content:''; position:absolute; top:-30px; right:-30px;
  width:110px; height:110px; background:rgba(255,255,255,.07); border-radius:50%;
}
.cat-hero-icon {
  width:56px; height:56px; flex-shrink:0;
  background:rgba(255,255,255,.15);
  border:2px solid rgba(255,255,255,.3);
  border-radius:16px;
  display:flex; align-items:center; justify-content:center;
  font-size:1.4rem;
}
.cat-stat {
  background:rgba(255,255,255,.12);
  border:1px solid rgba(255,255,255,.2);
  border-radius:var(--radius);
  padding:8px 14px;
  text-align:center;
}
.cat-stat .cs-val { font-size:1.2rem; font-weight:800; }
.cat-stat .cs-lbl { font-size:.7rem; opacity:.75; margin-top:1px; }

/* Form Card */
.kat-card {
  background:var(--bg-card);
  border-radius:var(--radius-xl);
  box-shadow:var(--shadow);
  border:1px solid var(--border-light);
  overflow:hidden;
  margin-bottom:20px;
}
.kat-card-header {
  padding:16px 20px;
  border-bottom:1px solid var(--border-light);
  display:flex; align-items:center; justify-content:space-between;
  background:var(--bg-main);
}
.kat-card-title {
  display:flex; align-items:center; gap:10px;
  font-size:.9rem; font-weight:700; color:var(--text-primary);
}
.kat-card-title .kct-icon {
  width:32px; height:32px; border-radius:var(--radius-sm);
  display:flex; align-items:center; justify-content:center;
  font-size:.85rem; color:#fff;
}
.kat-card-body { padding:20px; }

/* Radio Jenis */
.jenis-radio {
  display:flex; gap:12px; flex-wrap:wrap;
}
.jenis-label {
  flex:1; min-width:130px;
  display:flex; align-items:center; gap:8px;
  padding:12px 14px;
  border:2px solid var(--border);
  border-radius:var(--radius);
  cursor:pointer;
  transition:var(--transition-fast);
  font-size:.875rem; font-weight:600;
}
.jenis-label:hover { border-color:var(--border); background:var(--bg-main); }
.jenis-label.selected-masuk  { border-color:var(--success); background:rgba(16,185,129,.06); }
.jenis-label.selected-keluar { border-color:var(--danger);  background:rgba(239,68,68,.06); }

/* Kategori List Item */
.kat-item {
  display:flex; align-items:center; gap:12px;
  padding:12px 16px;
  border-bottom:1px solid var(--border-light);
  transition:var(--transition-fast);
}
.kat-item:last-child { border-bottom:none; }
.kat-item:hover { background:var(--bg-main); }
.kat-item-dot {
  width:10px; height:10px; border-radius:50%; flex-shrink:0;
}
.kat-item-name {
  flex:1; font-size:.875rem; font-weight:500; color:var(--text-primary);
  overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
}
.kat-item-actions { display:flex; gap:6px; flex-shrink:0; }
.kat-btn {
  width:32px; height:32px; border-radius:var(--radius-sm);
  display:flex; align-items:center; justify-content:center;
  font-size:.8rem; cursor:pointer; border:none;
  transition:var(--transition-fast);
}
.kat-btn-edit   { background:rgba(59,130,246,.1); color:var(--info); }
.kat-btn-edit:hover  { background:var(--info); color:#fff; }
.kat-btn-del    { background:rgba(239,68,68,.1);  color:var(--danger); }
.kat-btn-del:hover   { background:var(--danger);  color:#fff; }
.kat-btn-lock   { background:var(--border-light); color:var(--text-muted); cursor:not-allowed; opacity:.5; }

/* Tab switcher mobile */
.tab-switcher {
  display:flex; gap:0; background:var(--bg-main);
  border-radius:var(--radius); padding:4px;
  margin-bottom:16px;
}
.tab-btn {
  flex:1; padding:8px 12px; border-radius:var(--radius-sm);
  font-size:.82rem; font-weight:600; cursor:pointer;
  border:none; background:transparent; color:var(--text-muted);
  transition:var(--transition-fast); text-align:center;
}
.tab-btn.active { background:var(--bg-card); color:var(--text-primary); box-shadow:var(--shadow-sm); }

@media (max-width:768px) {
  .cat-hero { flex-direction:column; text-align:center; gap:14px; }
  .cat-hero-icon { margin:0 auto; }
  .kat-layout { grid-template-columns:1fr !important; }
}
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
        <span class="bc-item">Pengaturan</span>
        <span class="bc-sep"><i class="fas fa-chevron-right"></i></span>
        <span class="bc-item active">Kategori</span>
      </div>
    </div>
    <div class="topbar-right">
      <div class="topbar-date"><i class="fas fa-calendar-alt"></i> <?= date('d M Y') ?></div>
    </div>
  </div>

  <div class="admin-content">
    <?php if ($alert): ?>
    <div class="alert alert-<?= $alert['type'] ?>">
      <i class="fas fa-<?= $alert['type']=='success'?'check-circle':'exclamation-circle' ?>"></i>
      <?= htmlspecialchars($alert['message']) ?>
    </div>
    <?php endif; ?>

    <!-- HERO -->
    <div class="cat-hero animate-fadeIn">
      <div class="cat-hero-icon"><i class="fas fa-tags"></i></div>
      <div style="position:relative;z-index:1;flex:1">
        <div style="font-size:1.1rem;font-weight:800;margin-bottom:3px">Kelola Kategori</div>
        <div style="font-size:.8rem;opacity:.8">Atur kategori pemasukan dan pengeluaran kas masjid</div>
        <div style="display:flex;gap:10px;margin-top:12px;flex-wrap:wrap">
          <div class="cat-stat">
            <div class="cs-val"><?= $total_masuk ?></div>
            <div class="cs-lbl"><i class="fas fa-arrow-down" style="margin-right:3px"></i>Pemasukan</div>
          </div>
          <div class="cat-stat">
            <div class="cs-val"><?= $total_keluar ?></div>
            <div class="cs-lbl"><i class="fas fa-arrow-up" style="margin-right:3px"></i>Pengeluaran</div>
          </div>
          <div class="cat-stat">
            <div class="cs-val"><?= $total_masuk + $total_keluar ?></div>
            <div class="cs-lbl"><i class="fas fa-list" style="margin-right:3px"></i>Total</div>
          </div>
        </div>
      </div>
      <div style="position:relative;z-index:1;flex-shrink:0">
        <a href="<?= APP_URL ?>/admin/dashboard.php"
           style="display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.15);color:#fff;padding:8px 16px;border-radius:99px;font-size:.78rem;font-weight:600;border:1px solid rgba(255,255,255,.25);text-decoration:none;transition:background .15s"
           onmouseover="this.style.background='rgba(255,255,255,.25)'"
           onmouseout="this.style.background='rgba(255,255,255,.15)'">
          <i class="fas fa-arrow-left"></i> Kembali
        </a>
      </div>
    </div>

    <!-- LAYOUT: Form + Daftar -->
    <div style="display:grid;grid-template-columns:1fr 1.2fr;gap:20px;align-items:start" class="kat-layout">

      <!-- ===== FORM ===== -->
      <div class="kat-card animate-fadeIn delay-1">
        <div class="kat-card-header">
          <div class="kat-card-title">
            <div class="kct-icon" style="background:<?= $edit_data?'var(--warning)':'var(--primary)' ?>">
              <i class="fas fa-<?= $edit_data?'edit':'plus' ?>"></i>
            </div>
            <?= $edit_data ? 'Edit Kategori' : 'Tambah Kategori' ?>
          </div>
          <?php if ($edit_data): ?>
          <a href="kategori.php" class="btn btn-ghost btn-sm"><i class="fas fa-times"></i> Batal</a>
          <?php endif; ?>
        </div>
        <div class="kat-card-body">
          <form method="POST" id="formKategori">
            <?php if ($edit_data): ?><input type="hidden" name="id" value="<?= $edit_data['id'] ?>"><?php endif; ?>

            <div class="form-group">
              <label class="form-label">Nama Kategori <span class="required">*</span></label>
              <div class="input-group">
                <i class="fas fa-tag input-icon"></i>
                <input type="text" name="nama_kategori" class="form-control"
                       id="inputNama"
                       placeholder="Contoh: Infak Jumat"
                       value="<?= htmlspecialchars($edit_data['nama_kategori'] ?? '') ?>" required>
              </div>
            </div>

            <div class="form-group" style="margin-bottom:20px">
              <label class="form-label">Jenis Kategori <span class="required">*</span></label>
              <div class="jenis-radio">
                <label class="jenis-label <?= ($edit_data['jenis']??'masuk')=='masuk'?'selected-masuk':'' ?>" id="lblMasuk">
                  <input type="radio" name="jenis" value="masuk"
                         <?= ($edit_data['jenis']??'masuk')=='masuk'?'checked':'' ?>
                         style="accent-color:var(--success)">
                  <i class="fas fa-arrow-down" style="color:var(--success)"></i>
                  Kas Masuk
                </label>
                <label class="jenis-label <?= ($edit_data['jenis']??'')=='keluar'?'selected-keluar':'' ?>" id="lblKeluar">
                  <input type="radio" name="jenis" value="keluar"
                         <?= ($edit_data['jenis']??'')=='keluar'?'checked':'' ?>
                         style="accent-color:var(--danger)">
                  <i class="fas fa-arrow-up" style="color:var(--danger)"></i>
                  Kas Keluar
                </label>
              </div>
            </div>

            <div style="display:flex;gap:10px">
              <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center">
                <i class="fas fa-save"></i> <?= $edit_data ? 'Simpan' : 'Tambah' ?>
              </button>
              <?php if (!$edit_data): ?>
              <button type="reset" class="btn btn-ghost" onclick="resetForm()">
                <i class="fas fa-undo"></i>
              </button>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>

      <!-- ===== DAFTAR ===== -->
      <div class="animate-fadeIn delay-2">

        <!-- Tab Switcher (untuk HP) -->
        <div class="tab-switcher">
          <button class="tab-btn active" id="tabMasukBtn" onclick="switchTab('masuk')">
            <i class="fas fa-arrow-down" style="color:var(--success);margin-right:5px"></i>
            Pemasukan <span id="badgeMasuk" style="background:rgba(16,185,129,.15);color:var(--success);padding:1px 7px;border-radius:99px;font-size:.7rem;margin-left:4px"><?= $total_masuk ?></span>
          </button>
          <button class="tab-btn" id="tabKeluarBtn" onclick="switchTab('keluar')">
            <i class="fas fa-arrow-up" style="color:var(--danger);margin-right:5px"></i>
            Pengeluaran <span id="badgeKeluar" style="background:rgba(239,68,68,.12);color:var(--danger);padding:1px 7px;border-radius:99px;font-size:.7rem;margin-left:4px"><?= $total_keluar ?></span>
          </button>
        </div>

        <!-- List Masuk -->
        <div class="kat-card" id="listMasuk">
          <div class="kat-card-header">
            <div class="kat-card-title">
              <div class="kct-icon" style="background:var(--success)"><i class="fas fa-arrow-down"></i></div>
              Kategori Pemasukan
            </div>
            <span class="badge badge-success"><?= $total_masuk ?></span>
          </div>
          <div>
            <?php if ($list_masuk->num_rows): while ($k = $list_masuk->fetch_assoc()): ?>
            <div class="kat-item">
              <div class="kat-item-dot" style="background:var(--success)"></div>
              <div class="kat-item-name"><?= htmlspecialchars($k['nama_kategori']) ?></div>
              <span class="badge badge-info" style="margin-right:6px;font-size:.7rem"><?= $k['jml'] ?>x</span>
              <div class="kat-item-actions">
                <a href="?edit=<?= $k['id'] ?>" class="kat-btn kat-btn-edit" title="Edit">
                  <i class="fas fa-pen"></i>
                </a>
                <?php if ($k['jml'] == 0): ?>
                <button onclick="confirmDelete(<?= $k['id'] ?>,'<?= htmlspecialchars(addslashes($k['nama_kategori'])) ?>')"
                        class="kat-btn kat-btn-del" title="Hapus">
                  <i class="fas fa-trash"></i>
                </button>
                <?php else: ?>
                <button class="kat-btn kat-btn-lock" title="Tidak bisa dihapus">
                  <i class="fas fa-lock"></i>
                </button>
                <?php endif; ?>
              </div>
            </div>
            <?php endwhile; else: ?>
            <div class="empty-state" style="padding:32px">
              <div class="es-icon"><i class="fas fa-tags"></i></div>
              <h3>Belum ada kategori</h3>
              <p>Tambahkan kategori pemasukan lewat form</p>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- List Keluar -->
        <div class="kat-card" id="listKeluar" style="display:none;margin-top:0">
          <div class="kat-card-header">
            <div class="kat-card-title">
              <div class="kct-icon" style="background:var(--danger)"><i class="fas fa-arrow-up"></i></div>
              Kategori Pengeluaran
            </div>
            <span class="badge badge-danger"><?= $total_keluar ?></span>
          </div>
          <div>
            <?php if ($list_keluar->num_rows): while ($k = $list_keluar->fetch_assoc()): ?>
            <div class="kat-item">
              <div class="kat-item-dot" style="background:var(--danger)"></div>
              <div class="kat-item-name"><?= htmlspecialchars($k['nama_kategori']) ?></div>
              <span class="badge badge-info" style="margin-right:6px;font-size:.7rem"><?= $k['jml'] ?>x</span>
              <div class="kat-item-actions">
                <a href="?edit=<?= $k['id'] ?>" class="kat-btn kat-btn-edit" title="Edit">
                  <i class="fas fa-pen"></i>
                </a>
                <?php if ($k['jml'] == 0): ?>
                <button onclick="confirmDelete(<?= $k['id'] ?>,'<?= htmlspecialchars(addslashes($k['nama_kategori'])) ?>')"
                        class="kat-btn kat-btn-del" title="Hapus">
                  <i class="fas fa-trash"></i>
                </button>
                <?php else: ?>
                <button class="kat-btn kat-btn-lock" title="Tidak bisa dihapus">
                  <i class="fas fa-lock"></i>
                </button>
                <?php endif; ?>
              </div>
            </div>
            <?php endwhile; else: ?>
            <div class="empty-state" style="padding:32px">
              <div class="es-icon"><i class="fas fa-tags"></i></div>
              <h3>Belum ada kategori</h3>
              <p>Tambahkan kategori pengeluaran lewat form</p>
            </div>
            <?php endif; ?>
          </div>
        </div>

      </div><!-- /daftar -->
    </div><!-- /layout -->
  </div>
</div>
</div>

<!-- Modal Hapus -->
<div class="modal-overlay" id="deleteModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title"><i class="fas fa-trash" style="color:var(--danger)"></i> Hapus Kategori</div>
      <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <div style="text-align:center;margin-bottom:16px">
        <div style="width:56px;height:56px;background:rgba(239,68,68,.1);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:var(--danger);margin:0 auto 12px">
          <i class="fas fa-trash"></i>
        </div>
        <p style="font-size:.9rem">Hapus kategori: <strong id="deleteItemName"></strong>?</p>
        <p style="color:var(--danger);font-size:.8rem;margin-top:8px"><i class="fas fa-exclamation-triangle"></i> Tindakan ini tidak dapat dibatalkan.</p>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal()">Batal</button>
      <a id="deleteConfirmBtn" class="btn btn-danger"><i class="fas fa-trash"></i> Hapus</a>
    </div>
  </div>
</div>

<script>
// Tab switcher
function switchTab(tab) {
  const isMasuk = tab === 'masuk';
  document.getElementById('listMasuk').style.display  = isMasuk ? 'block' : 'none';
  document.getElementById('listKeluar').style.display = isMasuk ? 'none'  : 'block';
  document.getElementById('tabMasukBtn').classList.toggle('active',  isMasuk);
  document.getElementById('tabKeluarBtn').classList.toggle('active', !isMasuk);
}

// Jika edit data dari jenis keluar, otomatis buka tab keluar
<?php if ($edit_data && $edit_data['jenis'] === 'keluar'): ?>
switchTab('keluar');
<?php endif; ?>

// Radio highlight
function updateRadio() {
  const val = document.querySelector('input[name="jenis"]:checked')?.value;
  document.getElementById('lblMasuk').className  = 'jenis-label' + (val==='masuk'  ? ' selected-masuk'  : '');
  document.getElementById('lblKeluar').className = 'jenis-label' + (val==='keluar' ? ' selected-keluar' : '');
}
document.querySelectorAll('input[name="jenis"]').forEach(r => r.addEventListener('change', updateRadio));
updateRadio();

// Reset form
function resetForm() {
  document.getElementById('inputNama').value = '';
  document.querySelectorAll('input[name="jenis"]').forEach(r => { r.checked = r.value === 'masuk'; });
  updateRadio();
}

// Delete modal
function confirmDelete(id, name) {
  document.getElementById('deleteItemName').textContent = name;
  document.getElementById('deleteConfirmBtn').href = '?delete=' + id;
  document.getElementById('deleteModal').classList.add('active');
}
function closeModal() { document.getElementById('deleteModal').classList.remove('active'); }
document.getElementById('deleteModal').addEventListener('click', e => { if(e.target===document.getElementById('deleteModal')) closeModal(); });
document.addEventListener('keydown', e => { if(e.key==='Escape') closeModal(); });

// Sidebar
const sidebar=document.getElementById('adminSidebar');
const overlay=document.getElementById('sidebarOverlay');
document.getElementById('sidebarToggle').addEventListener('click',()=>{sidebar.classList.toggle('open');overlay.classList.toggle('active');});
overlay.addEventListener('click',()=>{sidebar.classList.remove('open');overlay.classList.remove('active');});
</script>
</body>
</html>
