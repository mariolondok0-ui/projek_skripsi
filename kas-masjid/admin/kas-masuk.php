<?php
require_once '../includes/config.php';
requireLogin();

// Fungsi Helper Bulan & Tanggal Indonesia
function formatBulanIndoPub($bulan_angka) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    return $bulan[(int)$bulan_angka] ?? '';
}

function tglIndoKasMasuk($tanggal) {
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

// ---- Handle DELETE → Soft Delete ke Tempat Sampah ----
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("UPDATE transaksi SET deleted_at=NOW() WHERE id=$id AND jenis='masuk' AND deleted_at IS NULL");
    setAlert('success', 'Data dipindahkan ke <a href="'.APP_URL.'/admin/tempat-sampah.php" style="color:inherit;font-weight:700;text-decoration:underline">Tempat Sampah</a>. Bisa dipulihkan kapan saja.');
    redirect(APP_URL . '/admin/kas-masuk.php');
}

// ---- Kategori Masuk ----
$kategori_list = $conn->query("SELECT * FROM kategori WHERE jenis='masuk' ORDER BY nama_kategori");

// ---- Filter ----
$default_filter = '2026-08';
$filter_bulan = sanitize($_GET['bulan'] ?? $default_filter);
$f_year = (int)substr($filter_bulan, 0, 4);
if ($f_year < 2026 || $f_year > 2027) {
    $filter_bulan = $default_filter;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;

$where        = "t.jenis='masuk' AND DATE_FORMAT(t.tanggal,'%Y-%m')='$filter_bulan' AND t.deleted_at IS NULL";
$where_simple = "jenis='masuk' AND DATE_FORMAT(tanggal,'%Y-%m')='$filter_bulan' AND deleted_at IS NULL";

$total_rows  = $conn->query("SELECT COUNT(*) as c FROM transaksi WHERE $where_simple")->fetch_assoc()['c'];
$total_pages = max(1, ceil($total_rows / $per_page));
$offset      = ($page - 1) * $per_page;

$rows = $conn->query("SELECT t.*, k.nama_kategori FROM transaksi t JOIN kategori k ON t.kategori_id=k.id WHERE $where ORDER BY t.tanggal DESC, t.id DESC LIMIT $per_page OFFSET $offset");
$summary = $conn->query("SELECT COALESCE(SUM(jumlah),0) as total, COUNT(*) as cnt FROM transaksi WHERE $where_simple")->fetch_assoc();

$alert = getAlert();

// Format label tombol filter aktif
$parts_f = explode('-', $filter_bulan);
$label_filter_aktif = (isset($parts_f[0]) && isset($parts_f[1])) ? formatBulanIndoPub($parts_f[1]) . ' ' . $parts_f[0] : 'Pilih Bulan';
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Kas Masuk - <?= APP_NAME ?></title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css?v=<?= time() ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<!-- FONT MODERN: Plus Jakarta Sans -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* =======================================================
   STYLING MODAL & MOBILE RESPONSIVE PERBAIKAN
   ======================================================= */
.modern-modal-overlay {
  position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(4px); z-index: 9999;
  display: flex; align-items: center; justify-content: center;
  padding: 15px; opacity: 0; visibility: hidden; transition: all 0.3s ease;
}
.modern-modal-overlay.active { opacity: 1; visibility: visible; }
.modern-modal-box {
  background: #ffffff; border-radius: 16px; width: 100%; max-width: 580px;
  max-height: 90vh; display: flex; flex-direction: column;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  transform: scale(0.95) translateY(10px); transition: all 0.3s ease;
  overflow: hidden; border: none; font-family: 'Plus Jakarta Sans', sans-serif !important;
}
.modern-modal-overlay.active .modern-modal-box { transform: scale(1) translateY(0); }

.modern-modal-header {
  padding: 18px 24px; background: #ffffff; 
  border-bottom: 1px solid #e2e8f0; flex-shrink: 0;
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

.modern-modal-body { 
  padding: 24px; background: #ffffff; overflow-y: auto; flex: 1; 
}

.modern-modal-body .form-label { 
    font-size: 0.85rem; font-weight: 600; color: #334155; 
    margin-bottom: 8px; letter-spacing: -0.1px; 
}
.modern-modal-body .form-control { 
    background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; 
    padding: 11px 16px 11px 42px; font-size: 0.9rem; font-weight: 500; color: #0f172a;
    box-shadow: none; transition: 0.2s; font-family: 'Plus Jakarta Sans', sans-serif !important;
    width: 100%;
}
.modern-modal-body .form-control::placeholder {
    color: #94a3b8; font-weight: 400;
}
.modern-modal-body .form-control:focus { 
    background-color: #ffffff; border-color: var(--primary); 
    box-shadow: 0 0 0 3px rgba(30,110,181,0.15); 
}
.modern-modal-body .input-icon { color: #94a3b8; }

/* TOMBOL BATAL DI KIRI & AKSI DI KANAN */
.modern-modal-footer {
    display: flex; justify-content: space-between; align-items: center; gap: 12px;
    margin-top: 24px; padding-top: 16px; border-top: 1px solid #f1f5f9; flex-shrink: 0;
}
.btn-modal-batal {
    background: #e2e8f0; color: #334155; font-weight: 600; font-size: 0.875rem;
    border: none; padding: 11px 24px; border-radius: 8px; cursor: pointer; transition: 0.2s;
    font-family: 'Plus Jakarta Sans', sans-serif !important;
}
.btn-modal-batal:hover { background: #cbd5e1; color: #0f172a; }

.btn-modal-simpan {
    background: var(--primary); color: #ffffff; font-weight: 600; font-size: 0.875rem;
    border: none; padding: 11px 24px; border-radius: 8px; cursor: pointer; transition: 0.2s;
    display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 6px -1px rgba(30,110,181,0.3);
    font-family: 'Plus Jakarta Sans', sans-serif !important;
}
.btn-modal-simpan:hover { background: var(--primary-dark); transform: translateY(-1px); box-shadow: 0 6px 8px -1px rgba(30,110,181,0.4); }

.modal-grid-row {
  display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;
}

.page-header-flex {
  display: flex; 
  align-items: center; 
  justify-content: space-between; 
  flex-wrap: wrap; 
  gap: 12px;
}

.summary-container {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 16px;
}

.summary-cards-wrapper {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.summary-bar-item {
  background: var(--bg-card);
  padding: 12px 18px;
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm);
  display: flex;
  align-items: center !important;
  justify-content: center !important;
  text-align: center !important;
  gap: 12px;
}

@media(max-width: 640px) {
  .page-header-flex {
    flex-direction: column !important;
    align-items: stretch !important;
    gap: 10px !important;
  }
  .page-header-flex .btn-primary {
    width: 100% !important;
    justify-content: center !important;
  }

  .summary-container {
    flex-direction: column !important;
    align-items: stretch !important;
    gap: 12px !important;
  }
  .summary-cards-wrapper {
    flex-direction: column !important;
    width: 100% !important;
  }
  .summary-cards-wrapper > div {
    width: 100% !important;
  }
  .summary-container > div:last-child {
    width: 100% !important;
  }
  .summary-container > div:last-child button {
    width: 100% !important;
    justify-content: center !important;
  }
  
  .modal-grid-row {
    grid-template-columns: 1fr !important;
    gap: 12px !important;
    margin-bottom: 12px !important;
  }
  .modern-modal-box {
    max-width: 100% !important;
    max-height: 95vh !important;
    margin: 10px;
  }
  .modern-modal-body {
    padding: 16px !important;
  }
  
  /* Perbaikan Card List Mobile */
  .trx-m-item {
    display: flex !important;
    flex-direction: column !important;
    align-items: flex-start !important;
    position: relative !important;
    padding: 14px !important;
    gap: 10px !important;
  }
  .trx-m-item .trx-m-icon {
    position: absolute;
    top: 14px;
    left: 14px;
  }
  .trx-m-item .trx-m-body {
    padding-left: 42px !important;
    width: 100% !important;
  }
  .trx-m-item .trx-m-right {
    display: flex !important;
    width: 100% !important;
    justify-content: space-between !important;
    align-items: center !important;
    border-top: 1px solid #f1f5f9;
    padding-top: 10px;
    margin-top: 4px;
  }

  /* Perbaikan Modal Hapus di HP agar tidak meluber */
  #deleteModal .modal {
    width: 92% !important;
    max-width: 100% !important;
    margin: auto;
    padding: 16px !important;
  }
  #deleteModal .modal-footer {
    flex-direction: column !important;
    align-items: stretch !important;
    gap: 8px !important;
  }
  #deleteModal .modal-footer > div {
    display: flex !important;
    flex-direction: column !important;
    gap: 8px !important;
    width: 100% !important;
  }
  #deleteModal .modal-footer .btn,
  #deleteModal .modal-footer a {
    width: 100% !important;
    justify-content: center !important;
    text-align: center !important;
  }
}
</style>
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
      <div class="topbar-date"><i class="fas fa-calendar-alt me-1" style="margin-right: 4px;"></i> <?= tglIndoKasMasuk(date('Y-m-d')) ?></div>
      
      <!-- DROPDOWN PROFIL USER -->
      <div class="user-dropdown-wrapper" id="userDropdownWrap">
        <div class="topbar-user" id="userDropdownTrigger">
          <div class="t-avatar"><?= strtoupper(substr($_SESSION['admin_nama'],0,1)) ?></div>
          <div class="t-name"><?= htmlspecialchars($_SESSION['admin_nama']) ?></div>
          <i class="fas fa-chevron-down"></i>
        </div>
        
        <div class="user-dropdown-menu">
          <div class="dropdown-header">
            <div class="d-avatar"><?= strtoupper(substr($_SESSION['admin_nama'],0,1)) ?></div>
            <div class="d-info">
              <div class="d-name"><?= htmlspecialchars($_SESSION['admin_nama']) ?></div>
              <div class="d-role">@admin</div>
            </div>
          </div>
          <div class="dropdown-body">
            <a href="profil.php" class="dropdown-item">
              <i class="fas fa-user-cog"></i> Pengaturan Akun
            </a>
            <a href="#" class="dropdown-item text-danger" onclick="openLogoutModal()">
              <i class="fas fa-sign-out-alt"></i> Logout
            </a>
          </div>
        </div>
      </div>
      <!-- END DROPDOWN -->
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
      <div class="page-header-flex">
                <div>
          <button onclick="openTambahModal()" class="btn btn-primary" style="border:none; display:inline-flex; align-items:center; gap:8px;">
            <i class="fas fa-plus-circle"></i> Tambah Data
          </button>
        </div>
      </div>
    </div>

    <!-- Summary & Filter -->
    <div class="summary-container">
      <div class="summary-cards-wrapper">
        <div class="summary-bar-item" style="border-left:3px solid var(--success)">
          <i class="fas fa-arrow-up" style="color:var(--info);font-size:1.3rem"></i>
          <div><div style="font-size:.7rem;color:#0f172a;font-weight:700;">Total Pemasukan Bulan Ini</div>
          <div style="font-weight:800;color:var(--info);font-size:.95rem"><?= formatRupiah($summary['total']) ?></div></div>
        </div>
        <div class="summary-bar-item" style="border-left:3px solid var(--info)">
          <i class="fas fa-list" style="color:var(--info);font-size:1.3rem"></i>
          <div><div style="font-size:.7rem;color:#0f172a;font-weight:700;">Jumlah Transaksi Bulan Ini</div>
          <div style="font-weight:800;color:var(--info);font-size:.95rem"><?= $summary['cnt'] ?> transaksi</div></div>
        </div>
      </div>
      
      <!-- Tombol Pemicu Pop-up Filter -->
      <div>
        <button type="button" onclick="openFilterModal()" class="btn" style="background:#ffffff; color:#0f172a; border:1px solid #cbd5e1; font-weight:700; display:inline-flex; align-items:center; gap:8px; padding:9px 16px; border-radius:10px; box-shadow:0 1px 2px rgba(0,0,0,0.04);">
          <i class="fas fa-calendar-alt" style="color:var(--primary);"></i> <?= $label_filter_aktif ?> <i class="fas fa-chevron-down" style="font-size:0.75rem; color:#94a3b8; margin-left:4px;"></i>
        </button>
      </div>
    </div>

    <!-- Tabel (Desktop) -->
    <div class="table-wrapper animate-fadeIn table-wrapper-desktop">
      <table class="table table-striped">
        <thead>
          <tr><th>No</th><th>Tanggal</th><th>Keterangan</th><th>Kategori</th><th class="text-right">Jumlah (Rp)</th><th style="width:90px">Aksi</th></tr>
        </thead>
        <tbody>
          <?php
          $rows_data = [];
          if ($rows->num_rows):
            $no = $offset + 1;
            while ($r = $rows->fetch_assoc()):
              $rows_data[] = $r;
          ?>
          <tr>
            <td class="text-muted"><?= $no++ ?></td>
            <td style="white-space:nowrap"><?= tglIndoKasMasuk($r['tanggal']) ?></td>
            <td><?= htmlspecialchars($r['keterangan']) ?></td>
            <td><span class="badge badge-success"><?= htmlspecialchars($r['nama_kategori']) ?></span></td>
            <td class="text-right fw-600 text-success">+ <?= number_format($r['jumlah'],0,',','.') ?></td>
            <td>
              <div style="display:flex;gap:6px">
                <button onclick="openEditModal(<?= $r['id'] ?>, '<?= $r['tanggal'] ?>', '<?= $r['kategori_id'] ?>', '<?= $r['jumlah'] ?>', '<?= htmlspecialchars(addslashes($r['keterangan'])) ?>')" class="btn btn-ghost btn-icon btn-sm" title="Edit"><i class="fas fa-edit"></i></button>
                <button onclick="confirmDelete(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['keterangan'])) ?>')"
                        class="btn btn-icon btn-sm" style="background:rgba(239,68,68,.1);color:var(--danger)" title="Hapus">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
          <?php endwhile;
          else: ?>
          <tr><td colspan="6"><div class="empty-state"><div class="es-icon"><i class="fas fa-inbox"></i></div><h3>Belum ada data kas masuk</h3><p>Klik tombol "Tambah Data" di atas untuk menambahkan data baru.</p></div></td></tr>
          <?php endif; ?>
        </tbody>
        <?php if (!empty($rows_data)): ?>
        <tfoot>
          <tr><td colspan="4" class="text-right">Total:</td><td class="text-right text-success fw-600">+ <?= number_format($summary['total'],0,',','.') ?></td><td></td></tr>
        </tfoot>
        <?php endif; ?>
      </table>
    </div>

    <!-- Card List (Mobile) -->
    <div class="trx-mobile-card animate-fadeIn">
      <?php if (!empty($rows_data)): foreach ($rows_data as $r): ?>
      <div class="trx-m-item">
        <div class="trx-m-icon" style="background:rgba(59,130,246,.1);color:var(--info)">
          <i class="fas fa-arrow-up"></i>
        </div>
        <div class="trx-m-body">
          <div class="trx-m-name"><?= htmlspecialchars($r['keterangan']) ?></div>
          <div class="trx-m-meta">
            <span><i class="fas fa-calendar-alt"></i> <?= tglIndoKasMasuk($r['tanggal']) ?></span>
            <span class="badge badge-success" style="font-size:.65rem;padding:2px 7px"><?= htmlspecialchars($r['nama_kategori']) ?></span>
          </div>
        </div>
        <div class="trx-m-right">
          <div class="trx-m-amount text-success">+Rp <?= number_format($r['jumlah'],0,',','.') ?></div>
          <div class="trx-m-actions" style="display:flex;gap:6px;">
            <button onclick="openEditModal(<?= $r['id'] ?>, '<?= $r['tanggal'] ?>', '<?= $r['kategori_id'] ?>', '<?= $r['jumlah'] ?>', '<?= htmlspecialchars(addslashes($r['keterangan'])) ?>')" class="btn btn-ghost btn-icon btn-sm"><i class="fas fa-edit"></i></button>
            <button onclick="confirmDelete(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['keterangan'])) ?>')"
                    class="btn btn-icon btn-sm" style="background:rgba(239,68,68,.1);color:var(--danger)">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
      </div>
      <?php endforeach; else: ?>
      <div class="empty-state"><div class="es-icon"><i class="fas fa-inbox"></i></div><h3>Belum ada data kas masuk</h3></div>
      <?php endif; ?>
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

<!-- ========================================================= -->
<!-- MODAL POP-UP TAMBAH DATA -->
<!-- ========================================================= -->
<div class="modern-modal-overlay" id="tambahModal">
  <div class="modern-modal-box">
    <div class="modern-modal-header">
      <div class="modern-modal-title">
        <i class="fas fa-plus-circle"></i> Tambah Kas Masuk Baru
      </div>
      <button class="modern-modal-close" onclick="closeTambahModal()"><i class="fas fa-times"></i></button>
    </div>
    
    <div class="modern-modal-body">
      <form method="POST">
        <div class="modal-grid-row">
          <div class="form-group mb-0">
            <label class="form-label">Tanggal <span class="required">*</span></label>
            <div class="input-group">
              <i class="fas fa-calendar input-icon"></i>
              <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
          </div>
          <div class="form-group mb-0">
            <label class="form-label">Kategori <span class="required">*</span></label>
            <div class="input-group">
              <i class="fas fa-tag input-icon"></i>
              <select name="kategori_id" class="form-control form-select" required>
                <?php 
                $kategori_list->data_seek(0);
                while ($k = $kategori_list->fetch_assoc()): 
                ?>
                <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>
          </div>
        </div>

        <div class="modal-grid-row" style="margin-bottom:0 !important;">
          <div class="form-group mb-0">
            <label class="form-label">Jumlah (Rp) <span class="required">*</span></label>
            <div class="input-group">
              <i class="fas fa-money-bill input-icon"></i>
              <input type="text" name="jumlah" id="jumlahInputModal" class="form-control" placeholder="0" required>
            </div>
          </div>
          <div class="form-group mb-0">
            <label class="form-label">Keterangan <span class="required">*</span></label>
            <div class="input-group">
              <i class="fas fa-align-left input-icon"></i>
              <input type="text" name="keterangan" class="form-control" required>
            </div>
          </div>
        </div>

        <div class="modern-modal-footer">
          <button type="button" class="btn-modal-batal" onclick="closeTambahModal()">Batal</button>
          <button type="submit" class="btn-modal-simpan">
            <i class="fas fa-save"></i> Simpan Data
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ========================================================= -->
<!-- MODAL POP-UP EDIT DATA -->
<!-- ========================================================= -->
<div class="modern-modal-overlay" id="editModal">
  <div class="modern-modal-box">
    <div class="modern-modal-header">
      <div class="modern-modal-title" style="color: var(--primary);">
        <i class="fas fa-edit"></i> Edit Data Kas Masuk
      </div>
      <button class="modern-modal-close" onclick="closeEditModal()"><i class="fas fa-times"></i></button>
    </div>
    
    <div class="modern-modal-body">
      <form method="POST">
        <input type="hidden" name="id" id="editIdField">
        <div class="modal-grid-row">
          <div class="form-group mb-0">
            <label class="form-label">Tanggal <span class="required">*</span></label>
            <div class="input-group">
              <i class="fas fa-calendar input-icon"></i>
              <input type="date" name="tanggal" id="editTanggalField" class="form-control" required>
            </div>
          </div>
          <div class="form-group mb-0">
            <label class="form-label">Kategori <span class="required">*</span></label>
            <div class="input-group">
              <i class="fas fa-tag input-icon"></i>
              <select name="kategori_id" id="editKategoriField" class="form-control form-select" required>
                <?php 
                $kategori_list->data_seek(0);
                while ($k = $kategori_list->fetch_assoc()): 
                ?>
                <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>
          </div>
        </div>

        <div class="modal-grid-row" style="margin-bottom:0 !important;">
          <div class="form-group mb-0">
            <label class="form-label">Jumlah (Rp) <span class="required">*</span></label>
            <div class="input-group">
              <i class="fas fa-money-bill input-icon"></i>
              <input type="text" name="jumlah" id="jumlahInputEditModal" class="form-control" placeholder="0" required>
            </div>
          </div>
          <div class="form-group mb-0">
            <label class="form-label">Keterangan <span class="required">*</span></label>
            <div class="input-group">
              <i class="fas fa-align-left input-icon"></i>
              <input type="text" name="keterangan" id="editKeteranganField" class="form-control" required>
            </div>
          </div>
        </div>

        <div class="modern-modal-footer">
          <button type="button" class="btn-modal-batal" onclick="closeEditModal()">Batal</button>
          <button type="submit" class="btn-modal-simpan">
            <i class="fas fa-save"></i> Simpan Perubahan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ========================================================= -->
<!-- MODAL POP-UP FILTER BULAN & TAHUN -->
<!-- ========================================================= -->
<div class="modern-modal-overlay" id="filterModal">
  <div class="modern-modal-box" style="max-width: 400px;">
    <div class="modern-modal-header">
      <div class="modern-modal-title">
        <i class="fas fa-filter"></i> Pilih Periode Bulan
      </div>
      <button class="modern-modal-close" onclick="closeFilterModal()"><i class="fas fa-times"></i></button>
    </div>
    
    <div class="modern-modal-body">
      <form method="GET" id="formFilterModal">
        <?php 
        $curr_f_y = (int)substr($filter_bulan, 0, 4);
        $curr_f_m = (int)substr($filter_bulan, 5, 2);
        if ($curr_f_y < 2026 || $curr_f_y > 2027) {
            $curr_f_y = 2026;
        }
        $list_Bulan_Modal = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        ?>
        <div class="form-group mb-3" style="margin-bottom:16px;">
          <label class="form-label">Bulan</label>
          <select name="filter_m" id="modalSelectBulan" class="form-control form-select" style="background:#f8fafc;">
            <?php foreach($list_Bulan_Modal as $m_num => $m_name): ?>
                <option value="<?= sprintf('%02d', $m_num) ?>" <?= $curr_f_m == $m_num ? 'selected' : '' ?>><?= $m_name ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group mb-3" style="margin-bottom:20px;">
          <label class="form-label">Tahun</label>
          <select name="filter_y" id="modalSelectTahun" class="form-control form-select" style="background:#f8fafc;">
            <option value="2026" <?= $curr_f_y == 2026 ? 'selected' : '' ?>>2026</option>
            <option value="2027" <?= $curr_f_y == 2027 ? 'selected' : '' ?>>2027</option>
          </select>
        </div>

        <input type="hidden" name="bulan" id="hiddenFinalBulan" value="<?= $filter_bulan ?>">

        <div class="modern-modal-footer">
          <button type="button" class="btn-modal-batal" onclick="closeFilterModal()">Batal</button>
          <button type="button" onclick="submitFilterModal()" class="btn-modal-simpan">
            <i class="fas fa-check"></i> Tampilkan
          </button>
        </div>
      </form>
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
    </div>
    <div class="modal-footer" style="display:flex; justify-content:space-between; align-items:center;">
      <button class="btn btn-ghost" onclick="closeModal()">Batal</button>
      <div style="display:flex; gap:8px;">
        <a id="deleteSoftBtn" class="btn btn-warning" style="background:var(--warning); color:#fff;"><i class="fas fa-archive"></i> Pindah ke Sampah</a>
        <a id="deleteHardBtn" class="btn btn-danger"><i class="fas fa-trash"></i> Hapus Permanen</a>
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

// Logic Pop-up Tambah Data
function openTambahModal() {
  document.getElementById('tambahModal').classList.add('active');
}
function closeTambahModal() {
  document.getElementById('tambahModal').classList.remove('active');
}
document.getElementById('tambahModal').addEventListener('click', function(e) {
  if (e.target === this) closeTambahModal();
});

// Logic Pop-up Edit Data
function openEditModal(id, tanggal, kategoriId, jumlah, keterangan) {
  document.getElementById('editIdField').value = id;
  document.getElementById('editTanggalField').value = tanggal;
  document.getElementById('editKategoriField').value = kategoriId;
  document.getElementById('jumlahInputEditModal').value = new Intl.NumberFormat('id-ID').format(jumlah);
  document.getElementById('editKeteranganField').value = keterangan;
  document.getElementById('editModal').classList.add('active');
}
function closeEditModal() {
  document.getElementById('editModal').classList.remove('active');
}
document.getElementById('editModal').addEventListener('click', function(e) {
  if (e.target === this) closeEditModal();
});

// Logic Pop-up Filter Bulan
function openFilterModal() {
  document.getElementById('filterModal').classList.add('active');
}
function closeFilterModal() {
  document.getElementById('filterModal').classList.remove('active');
}
document.getElementById('filterModal').addEventListener('click', function(e) {
  if (e.target === this) closeFilterModal();
});
function submitFilterModal() {
  let m = document.getElementById('modalSelectBulan').value;
  let y = document.getElementById('modalSelectTahun').value;
  document.getElementById('hiddenFinalBulan').value = y + '-' + m;
  document.getElementById('formFilterModal').submit();
}

// Format currency input untuk Modal dan Edit Modal
const formatRupiahInput = function(el) {
  if(!el) return;
  el.addEventListener('input', function() {
    let val = this.value.replace(/\D/g,'');
    this.value = val ? new Intl.NumberFormat('id-ID').format(val) : '';
  });
};
formatRupiahInput(document.getElementById('jumlahInputModal'));
formatRupiahInput(document.getElementById('jumlahInputEditModal'));

// Modal Delete Logic
function confirmDelete(id, name) {
  document.getElementById('deleteItemName').textContent = name;
  document.getElementById('deleteSoftBtn').href = '?delete=' + id + '&type=soft';
  document.getElementById('deleteHardBtn').href = '?delete=' + id + '&type=hard';
  document.getElementById('deleteModal').classList.add('active');
}

function closeModal() {
  document.getElementById('deleteModal').classList.remove('active');
}
document.getElementById('deleteModal').addEventListener('click', function(e){
  if(e.target===this) closeModal();
});

// Sidebar toggle
const sidebar = document.getElementById('adminSidebar');
const overlay = document.getElementById('sidebarOverlay');

document.getElementById('sidebarToggle').addEventListener('click', () => {
  if (window.innerWidth <= 768) {
    sidebar.classList.toggle('open');
    overlay.classList.toggle('active');
  } else {
    document.querySelector('.admin-wrapper').classList.toggle('toggled');
  }
});

overlay.addEventListener('click',()=>{
  sidebar.classList.remove('open');
  overlay.classList.remove('active');
});
</script>
</body>
</html>