<?php
require_once '../includes/config.php';
requireLogin();

$admin_id = (int)$_SESSION['admin_id'];
$admin    = $conn->query("SELECT * FROM users WHERE id=$admin_id")->fetch_assoc();

// Fungsi Helper Tanggal Indonesia
function tgl_indo_kat($tanggal) {
    if (empty($tanggal) || $tanggal == '0000-00-00') return '-';
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $ts = strtotime($tanggal);
    $tgl = date('d', $ts);
    $bln = $bulan[(int)date('m', $ts)];
    $thn = date('Y', $ts);
    return "$tgl $bln $thn";
}

// Proses Tambah / Edit Kategori
$alert = getAlert();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';
    $nama   = sanitize($_POST['nama_kategori'] ?? '');
    $jenis  = sanitize($_POST['jenis'] ?? 'masuk');
    $id     = (int)($_POST['id'] ?? 0);

    if (empty($nama)) {
        setAlert('error', 'Nama kategori tidak boleh kosong.');
    } else {
        if ($action === 'edit' && $id > 0) {
            $conn->query("UPDATE kategori SET nama_kategori='$nama', jenis='$jenis' WHERE id=$id");
            setAlert('success', 'Kategori berhasil diperbarui.');
        } else {
            $conn->query("INSERT INTO kategori (nama_kategori, jenis) VALUES ('$nama', '$jenis')");
            setAlert('success', 'Kategori baru berhasil ditambahkan.');
        }
    }
    redirect(APP_URL . '/admin/kategori.php');
}

// Proses Hapus Kategori
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $cek = $conn->query("SELECT COUNT(*) as c FROM transaksi WHERE kategori_id=$id AND deleted_at IS NULL")->fetch_assoc()['c'];
    if ($cek > 0) {
        setAlert('error', 'Kategori tidak dapat dihapus karena sedang digunakan oleh data transaksi.');
    } else {
        $conn->query("DELETE FROM kategori WHERE id=$id");
        setAlert('success', 'Kategori berhasil dihapus.');
    }
    redirect(APP_URL . '/admin/kategori.php');
}

// Ambil Data Kategori
$kat_masuk  = $conn->query("SELECT k.*, (SELECT COUNT(*) FROM transaksi t WHERE t.kategori_id=k.id AND t.deleted_at IS NULL) as jml FROM kategori k WHERE k.jenis='masuk' ORDER BY k.nama_kategori ASC");
$kat_keluar = $conn->query("SELECT k.*, (SELECT COUNT(*) FROM transaksi t WHERE t.kategori_id=k.id AND t.deleted_at IS NULL) as jml FROM kategori k WHERE k.jenis='keluar' ORDER BY k.nama_kategori ASC");

$jml_masuk  = $conn->query("SELECT COUNT(*) as c FROM kategori WHERE jenis='masuk'")->fetch_assoc()['c'];
$jml_keluar = $conn->query("SELECT COUNT(*) as c FROM kategori WHERE jenis='keluar'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
<title>Kategori Transaksi – <?= APP_NAME ?></title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css?v=<?= time() ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<!-- FONT MODERN: Plus Jakarta Sans -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* =======================================================
   STYLING MODAL POP-UP KATEGORI MODERN
   ======================================================= */
.modern-modal-overlay {
  position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(4px); z-index: 9999;
  display: flex; align-items: center; justify-content: center;
  padding: 20px; opacity: 0; visibility: hidden; transition: all 0.3s ease;
}
.modern-modal-overlay.active { opacity: 1; visibility: visible; }
.modern-modal-box {
  background: #ffffff; border-radius: 16px; width: 100%; max-width: 480px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  transform: scale(0.95) translateY(10px); transition: all 0.3s ease;
  overflow: hidden; border: none; font-family: 'Plus Jakarta Sans', sans-serif !important;
}
.modern-modal-overlay.active .modern-modal-box { transform: scale(1) translateY(0); }

.modern-modal-header {
  padding: 20px 24px; background: #ffffff; 
  border-bottom: 1px solid #e2e8f0; 
  display: flex; align-items: center; justify-content: space-between;
}
.modern-modal-title { 
  font-size: 1.1rem; font-weight: 700; color: var(--primary); 
  display: flex; align-items: center; gap: 10px;
  letter-spacing: -0.2px;
}
.modern-modal-close {
  width: 32px; height: 32px; background: transparent; border: none;
  display: flex; align-items: center; justify-content: center; 
  color: #94a3b8; font-size: 1.25rem; cursor: pointer; transition: 0.2s;
}
.modern-modal-close:hover { color: #0f172a; }

.modern-modal-body { padding: 24px; background: #ffffff; }

.modern-modal-body .form-label { 
    font-size: 0.85rem; font-weight: 600; color: #334155; 
    margin-bottom: 8px; display: block;
}
.modern-modal-body .form-control { 
    background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; 
    padding: 10px 14px; font-size: 0.9rem; font-weight: 500; color: #0f172a;
    width: 100%; font-family: 'Plus Jakarta Sans', sans-serif !important;
    transition: 0.2s;
}
.modern-modal-body .form-control:focus { 
    background-color: #ffffff; border-color: var(--primary); 
    box-shadow: 0 0 0 3px rgba(30,110,181,0.15); outline: none;
}

/* TOMBOL BATAL DI KIRI & AKSI DI KANAN DENGAN WARNA SELARAS */
.modern-modal-footer {
    display: flex; justify-content: space-between; align-items: center; gap: 12px;
    margin-top: 24px; padding-top: 16px; border-top: 1px solid #f1f5f9;
}
.btn-modal-batal {
    background: #e2e8f0; color: #334155; font-weight: 600; font-size: 0.875rem;
    border: none; padding: 11px 24px; border-radius: 8px; cursor: pointer; transition: 0.2s;
    font-family: 'Plus Jakarta Sans', sans-serif !important;
}
.btn-modal-batal:hover { background: #cbd5e1; color: #0f172a; }

.btn-modal-simpan {
    background: var(--primary); color: #ffffff; font-weight: 600; font-size: 0.875rem;
    border: none; padding: 11px 22px; border-radius: 8px; cursor: pointer; transition: 0.2s;
    display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 6px -1px rgba(30,110,181,0.3);
    font-family: 'Plus Jakarta Sans', sans-serif !important;
}
.btn-modal-simpan:hover { background: var(--primary-dark); transform: translateY(-1px); }

.btn-modal-hapus {
    background: var(--danger); color: #ffffff; font-weight: 600; font-size: 0.875rem;
    border: none; padding: 11px 22px; border-radius: 8px; cursor: pointer; transition: 0.2s;
    display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.3);
    text-decoration: none; font-family: 'Plus Jakarta Sans', sans-serif !important;
}
.btn-modal-hapus:hover { background: #dc2626; transform: translateY(-1px); color: #ffffff; }

/* Tab Navigasi Kategori Model Kapsul */
.kat-tab-btn {
    background: #ffffff; border: 1px solid #cbd5e1; padding: 10px 20px; border-radius: 99px;
    font-size: 0.9rem; font-weight: 600; color: #475569; cursor: pointer; display: inline-flex;
    align-items: center; gap: 10px; transition: 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}
.kat-tab-btn.active {
    background: var(--primary); color: #ffffff; border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(30,110,181,0.25);
}

.table-responsive {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.kat-action-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}

.kat-tabs-wrapper {
    display: flex;
    gap: 10px;
}

/* KODE TAMBAHAN UNTUK FOTO PROFIL DI NAVBAR */
.t-avatar { width: 32px; height: 32px; background: var(--primary); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; font-weight: bold; overflow: hidden; }
.t-avatar img { width: 100%; height: 100%; object-fit: cover; }
.d-avatar { width: 45px; height: 45px; background: var(--primary); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: bold; overflow: hidden; }
.d-avatar img { width: 100%; height: 100%; object-fit: cover; }

@media(max-width: 768px) {
    .admin-main { width: 100% !important; margin-left: 0 !important; }
    .admin-content { padding: 15px !important; }
    
    .kat-action-container {
        flex-direction: column !important;
        align-items: stretch !important;
    }
    .kat-tabs-wrapper {
        flex-direction: column !important;
        width: 100% !important;
    }
    .kat-tab-btn {
        width: 100% !important;
        justify-content: center !important;
    }
}
</style>
</head>
<body>
<div class="admin-wrapper">
<?php include '../includes/partials/sidebar-admin.php'; ?>

<div class="admin-main">
  <!-- TOPBAR -->
  <div class="topbar">
    <div style="display:flex; align-items:center; gap:12px;">
      <div id="sidebarToggle" style="cursor:pointer; font-size:1.1rem; color:var(--text-muted);"><i class="fas fa-bars"></i></div>
      <div class="breadcrumb">
        <i class="fas fa-home"></i> <i class="fas fa-chevron-right" style="font-size:0.6rem; opacity:0.5;"></i> <span class="active">Kategori Transaksi</span>
      </div>
    </div>
    <div class="topbar-right">
      <div class="topbar-date"><i class="fas fa-calendar-alt me-1" style="margin-right: 4px;"></i> <?= tgl_indo_kat(date('Y-m-d')) ?></div>
      
      <!-- DROPDOWN PROFIL USER -->
      <div class="user-dropdown-wrapper" id="userDropdownWrap">
        <div class="topbar-user" id="userDropdownTrigger">
          <div class="t-avatar">
            <?php if (!empty($admin['foto_profil']) && file_exists('../assets/uploads/' . $admin['foto_profil'])): ?>
                <img src="<?= APP_URL ?>/assets/uploads/<?= htmlspecialchars($admin['foto_profil']) ?>" alt="Avatar">
            <?php else: ?>
                <?= strtoupper(substr($admin['nama'], 0, 1)) ?>
            <?php endif; ?>
          </div>
          <div class="t-name"><?= htmlspecialchars($admin['nama']) ?></div>
          <i class="fas fa-chevron-down"></i>
        </div>
        
        <div class="user-dropdown-menu">
          <div class="dropdown-header">
            <div class="d-avatar">
              <?php if (!empty($admin['foto_profil']) && file_exists('../assets/uploads/' . $admin['foto_profil'])): ?>
                  <img src="<?= APP_URL ?>/assets/uploads/<?= htmlspecialchars($admin['foto_profil']) ?>" alt="Avatar">
              <?php else: ?>
                  <?= strtoupper(substr($admin['nama'], 0, 1)) ?>
              <?php endif; ?>
            </div>
            <div class="d-info">
              <div class="d-name"><?= htmlspecialchars($admin['nama']) ?></div>
              <div class="d-role">@admin</div>
            </div>
          </div>
          <div class="dropdown-body">
            <a href="profil.php" class="dropdown-item"><i class="fas fa-user-cog"></i> Pengaturan Akun</a>
            <a href="#" class="dropdown-item text-danger" onclick="openLogoutModal()"><i class="fas fa-sign-out-alt"></i> Logout</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- MAIN CONTENT -->
  <div class="admin-content">
    <?php if ($alert): ?>
    <div class="alert alert-<?= $alert['type'] ?>">
      <i class="fas fa-<?= $alert['type']=='success'?'check-circle':'exclamation-circle' ?>"></i> <?= htmlspecialchars($alert['message']) ?>
    </div>
    <?php endif; ?>

        <!-- TOMBOL TAMBAH KATEGORI & TAB NAVIGASI -->
    <div class="kat-action-container">
      <button type="button" class="kat-tab-btn" onclick="openAddModal()" style="background:var(--primary); color:#fff; border-color:var(--primary); box-shadow:0 4px 12px rgba(30,110,181,0.25);">
        <i class="fas fa-plus"></i> Tambah Kategori
      </button>

      <div class="kat-tabs-wrapper">
        <button class="kat-tab-btn active" id="tabBtnMasuk" onclick="switchTab('masuk')">
          <i class="fas fa-arrow-down" style="color:var(--success)"></i> Pemasukan <span class="badge" style="background:rgba(255,255,255,0.3); padding:2px 8px; border-radius:99px; font-weight:700;"><?= $jml_masuk ?></span>
        </button>
        <button class="kat-tab-btn" id="tabBtnKeluar" onclick="switchTab('keluar')">
          <i class="fas fa-arrow-up" style="color:var(--danger)"></i> Pengeluaran <span class="badge" style="background:rgba(0,0,0,0.08); padding:2px 8px; border-radius:99px; font-weight:700;"><?= $jml_keluar ?></span>
        </button>
      </div>
    </div>

    <!-- KONTEN TAB KATEGORI PEMASUKAN -->
    <div class="kat-content-box" id="boxMasuk">
      <div class="card-modern" style="background:#fff; border-radius:14px; border:1px solid var(--border-color); box-shadow:var(--shadow-sm); overflow:hidden;">
        <div style="padding:18px 24px; background:#f8fafc; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
          <h3 style="font-size:1rem; font-weight:700; color:var(--text-main); display:flex; align-items:center; gap:8px; margin:0;">
            <i class="fas fa-arrow-down" style="color:var(--success);"></i> Kategori Pemasukan
          </h3>
          <span class="badge badge-primary"><?= $jml_masuk ?> Kategori</span>
        </div>
        <div class="table-responsive">
          <table class="table-custom" style="width:100%; border-collapse:collapse; min-width:320px;">
            <tbody>
              <?php if ($kat_masuk->num_rows > 0): while($r = $kat_masuk->fetch_assoc()): ?>
              <tr style="border-bottom:1px solid var(--border-color);">
                <td style="padding:14px 20px; font-weight:600; color:var(--text-main);">
                  <div style="display:flex; align-items:center; gap:10px;">
                    <span style="width:8px; height:8px; border-radius:50%; background:var(--primary); flex-shrink:0;"></span>
                    <span><?= htmlspecialchars($r['nama_kategori']) ?></span>
                  </div>
                </td>
                <td style="padding:14px 20px; text-align:right; width:130px; white-space:nowrap;">
                  <span class="badge-custom badge-blue"><?= $r['jml'] ?> transaksi</span>
                </td>
                <td style="padding:14px 20px; text-align:center; width:100px; white-space:nowrap;">
                  <div style="display:flex; justify-content:center; gap:6px;">
                    <button onclick="openEditModal(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['nama_kategori'])) ?>', 'masuk')" class="btn-icon" title="Edit"><i class="fas fa-edit"></i></button>
                    <?php if($r['jml'] == 0): ?>
                      <button onclick="openHapusModal(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['nama_kategori'])) ?>')" class="btn-icon text-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                    <?php else: ?>
                      <button class="btn-icon" style="opacity:0.4; cursor:not-allowed;" title="Sedang digunakan"><i class="fas fa-lock"></i></button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endwhile; else: ?>
              <tr><td colspan="3" style="text-align:center; padding:30px; color:var(--text-muted);">Belum ada kategori pemasukan</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- KONTEN TAB KATEGORI PENGELUARAN -->
    <div class="kat-content-box" id="boxKeluar" style="display:none;">
      <div class="card-modern" style="background:#fff; border-radius:14px; border:1px solid var(--border-color); box-shadow:var(--shadow-sm); overflow:hidden;">
        <div style="padding:18px 24px; background:#f8fafc; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
          <h3 style="font-size:1rem; font-weight:700; color:var(--text-main); display:flex; align-items:center; gap:8px; margin:0;">
            <i class="fas fa-arrow-up" style="color:var(--danger);"></i> Kategori Pengeluaran
          </h3>
          <span class="badge badge-danger"><?= $jml_keluar ?> Kategori</span>
        </div>
        <div class="table-responsive">
          <table class="table-custom" style="width:100%; border-collapse:collapse; min-width:320px;">
            <tbody>
              <?php if ($kat_keluar->num_rows > 0): while($r = $kat_keluar->fetch_assoc()): ?>
              <tr style="border-bottom:1px solid var(--border-color);">
                <td style="padding:14px 20px; font-weight:600; color:var(--text-main);">
                  <div style="display:flex; align-items:center; gap:10px;">
                    <span style="width:8px; height:8px; border-radius:50%; background:var(--danger); flex-shrink:0;"></span>
                    <span><?= htmlspecialchars($r['nama_kategori']) ?></span>
                  </div>
                </td>
                <td style="padding:14px 20px; text-align:right; width:130px; white-space:nowrap;">
                  <span class="badge-custom badge-blue"><?= $r['jml'] ?> transaksi</span>
                </td>
                <td style="padding:14px 20px; text-align:center; width:100px; white-space:nowrap;">
                  <div style="display:flex; justify-content:center; gap:6px;">
                    <button onclick="openEditModal(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['nama_kategori'])) ?>', 'keluar')" class="btn-icon" title="Edit"><i class="fas fa-edit"></i></button>
                    <?php if($r['jml'] == 0): ?>
                      <button onclick="openHapusModal(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['nama_kategori'])) ?>')" class="btn-icon text-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                    <?php else: ?>
                      <button class="btn-icon" style="opacity:0.4; cursor:not-allowed;" title="Sedang digunakan"><i class="fas fa-lock"></i></button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endwhile; else: ?>
              <tr><td colspan="3" style="text-align:center; padding:30px; color:var(--text-muted);">Belum ada kategori pengeluaran</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>
</div>

<!-- ========================================================= -->
<!-- MODAL POP-UP TAMBAH / EDIT KATEGORI -->
<!-- ========================================================= -->
<div class="modern-modal-overlay" id="katModal">
  <div class="modern-modal-box">
    <div class="modern-modal-header">
      <div class="modern-modal-title" id="modalTitleText">
        <i class="fas fa-tags"></i> Tambah Kategori
      </div>
      <button class="modern-modal-close" onclick="closeKatModal()"><i class="fas fa-times"></i></button>
    </div>
    
    <div class="modern-modal-body">
      <form method="POST" id="katForm">
        <input type="hidden" name="action" id="formAction" value="add">
        <input type="hidden" name="id" id="formId" value="0">

        <div class="form-group mb-3" style="margin-bottom:16px;">
          <label class="form-label">Nama Kategori <span style="color:var(--danger)">*</span></label>
          <input type="text" name="nama_kategori" id="inputNamaKategori" class="form-control" required autocomplete="off">
        </div>

        <div class="form-group mb-3" style="margin-bottom:20px;">
          <label class="form-label">Jenis Kategori <span style="color:var(--danger)">*</span></label>
          <select name="jenis" id="inputJenisKategori" class="form-control form-select">
            <option value="masuk">Kas Masuk</option>
            <option value="keluar">Kas Keluar</option>
          </select>
        </div>

        <div class="modern-modal-footer">
          <button type="button" class="btn-modal-batal" onclick="closeKatModal()">Batal</button>
          <button type="submit" class="btn-modal-simpan" id="btnSubmitText">
            <i class="fas fa-save"></i> Simpan Kategori
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ========================================================= -->
<!-- MODAL POP-UP KONFIRMASI HAPUS KATEGORI -->
<!-- ========================================================= -->
<div class="modern-modal-overlay" id="hapusModal">
  <div class="modern-modal-box">
    <div class="modern-modal-header">
      <div class="modern-modal-title" style="color:var(--danger);">
        <i class="fas fa-exclamation-triangle"></i> Konfirmasi Hapus
      </div>
      <button class="modern-modal-close" onclick="closeHapusModal()"><i class="fas fa-times"></i></button>
    </div>
    <div class="modern-modal-body">
      <p style="font-size:0.9rem; color:#475569; margin-bottom:12px;">Apakah Anda yakin ingin menghapus kategori berikut?</p>
      <div style="background:#fef2f2; border:1px solid #fecaca; padding:14px; border-radius:10px; font-weight:600; color:#991b1b; margin-bottom:12px;" id="namaKategoriHapus"></div>

      <div class="modern-modal-footer">
        <button type="button" class="btn-modal-batal" onclick="closeHapusModal()">Batal</button>
        <a id="btnConfirmHapus" href="#" class="btn-modal-hapus">
          <i class="fas fa-trash"></i> Ya, Hapus
        </a>
      </div>
    </div>
  </div>
</div>

<script>
// Logic Dropdown User Topbar
const userDropdownWrap = document.getElementById('userDropdownWrap');
const userDropdownTrigger = document.getElementById('userDropdownTrigger');
if (userDropdownTrigger && userDropdownWrap) {
    userDropdownTrigger.addEventListener('click', function(e) {
        e.stopPropagation();
        userDropdownWrap.classList.toggle('active');
    });
    document.addEventListener('click', function(e) {
        if (!userDropdownWrap.contains(e.target)) {
            userDropdownWrap.classList.remove('active');
        }
    });
}

// Logic Tab Switcher
function switchTab(jenis) {
    const btnMasuk = document.getElementById('tabBtnMasuk');
    const btnKeluar = document.getElementById('tabBtnKeluar');
    const boxMasuk = document.getElementById('boxMasuk');
    const boxKeluar = document.getElementById('boxKeluar');

    if (jenis === 'masuk') {
        btnMasuk.classList.add('active');
        btnKeluar.classList.remove('active');
        boxMasuk.style.display = 'block';
        boxKeluar.style.display = 'none';
    } else {
        btnKeluar.classList.add('active');
        btnMasuk.classList.remove('active');
        boxKeluar.style.display = 'block';
        boxMasuk.style.display = 'none';
    }
}

// Logic Pop-up Modal Kategori
function openAddModal() {
  document.getElementById('modalTitleText').innerHTML = '<i class="fas fa-tags"></i> Tambah Kategori Baru';
  document.getElementById('formAction').value = 'add';
  document.getElementById('formId').value = '0';
  document.getElementById('inputNamaKategori').value = '';
  document.getElementById('btnSubmitText').innerHTML = '<i class="fas fa-plus"></i> Tambah Kategori';
  document.getElementById('katModal').classList.add('active');
}

function openEditModal(id, nama, jenis) {
  document.getElementById('modalTitleText').innerHTML = '<i class="fas fa-edit"></i> Edit Kategori';
  document.getElementById('formAction').value = 'edit';
  document.getElementById('formId').value = id;
  document.getElementById('inputNamaKategori').value = nama;
  document.getElementById('inputJenisKategori').value = jenis;
  document.getElementById('btnSubmitText').innerHTML = '<i class="fas fa-save"></i> Perbarui Kategori';
  document.getElementById('katModal').classList.add('active');
}

function closeKatModal() {
  document.getElementById('katModal').classList.remove('active');
}

// Logic Pop-up Modal Hapus
function openHapusModal(id, nama) {
  document.getElementById('namaKategoriHapus').textContent = nama;
  document.getElementById('btnConfirmHapus').href = '?hapus=' + id;
  document.getElementById('hapusModal').classList.add('active');
}

function closeHapusModal() {
  document.getElementById('hapusModal').classList.remove('active');
}

document.getElementById('katModal').addEventListener('click', function(e) {
  if (e.target === this) closeKatModal();
});
document.getElementById('hapusModal').addEventListener('click', function(e) {
  if (e.target === this) closeHapusModal();
});

// Sidebar toggle
const sidebar = document.getElementById('adminSidebar') || document.querySelector('.sidebar');
const overlay = document.getElementById('sidebarOverlay');
const sidebarToggle = document.getElementById('sidebarToggle');

if (sidebarToggle) {
    sidebarToggle.addEventListener('click', () => {
      if (window.innerWidth <= 768) {
        if(sidebar) sidebar.classList.toggle('open');
        if(overlay) overlay.classList.toggle('active');
      } else {
        document.querySelector('.admin-wrapper').classList.toggle('toggled');
      }
    });
}
if (overlay) {
    overlay.addEventListener('click',()=>{
      if(sidebar) sidebar.classList.remove('open');
      overlay.classList.remove('active');
    });
}
</script>
</body>
</html>