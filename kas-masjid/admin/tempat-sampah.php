<?php
require_once '../includes/config.php';
requireLogin();

// Fungsi Helper Format Tanggal Indonesia
function formatTanggalIndo($tanggal, $dengan_waktu = false) {
    if (empty($tanggal) || $tanggal == '0000-00-00' || $tanggal == '0000-00-00 00:00:00') {
        return '-';
    }
    
    $bulan = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
    ];
    
    $timestamp = strtotime($tanggal);
    $tgl   = date('d', $timestamp);
    $bln   = $bulan[(int)date('m', $timestamp)];
    $thn   = date('Y', $timestamp);
    $waktu = date('H:i', $timestamp);
    
    if ($dengan_waktu) {
        return "$tgl $bln $thn $waktu";
    }
    return "$tgl $bln $thn";
}

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

$where = "t.deleted_at IS NOT NULL";
if ($filter_jenis !== 'semua') {
    $where .= " AND t.jenis='" . sanitize($filter_jenis) . "'";
}

$total_rows  = $conn->query("SELECT COUNT(*) as c FROM transaksi t WHERE $where")->fetch_assoc()['c'];
$total_pages = max(1, ceil($total_rows / $per_page));
$offset      = ($page - 1) * $per_page;

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
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css?v=<?= time() ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<!-- FONT MODERN: Plus Jakarta Sans -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* =======================================================
   STYLING MODAL POP-UP RESTORE & DELETE MODERN
   ======================================================= */
.modern-modal-overlay {
  position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(4px); z-index: 9999;
  display: flex; align-items: center; justify-content: center;
  padding: 20px; opacity: 0; visibility: hidden; transition: all 0.3s ease;
}
.modern-modal-overlay.active { opacity: 1; visibility: visible; }
.modern-modal-box {
  background: #ffffff; border-radius: 16px; width: 100%; max-width: 500px;
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
  font-size: 1.1rem; font-weight: 700; 
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

/* TOMBOL BATAL DENGAN WARNA ABU-ABU ELEGAN DAN SELARAS */
.modern-modal-footer {
    display: flex; justify-content: space-between; align-items: center; gap: 12px;
    margin-top: 20px; padding-top: 16px; border-top: 1px solid #f1f5f9;
}

.btn-modal-batal {
    background: #e2e8f0; color: #334155; font-weight: 600; font-size: 0.875rem;
    border: none; padding: 11px 24px; border-radius: 8px; cursor: pointer; transition: 0.2s;
    font-family: 'Plus Jakarta Sans', sans-serif !important;
}
.btn-modal-batal:hover { background: #cbd5e1; color: #0f172a; }

.btn-modal-pulihkan {
    background: var(--primary); color: #ffffff; font-weight: 600; font-size: 0.875rem;
    border: none; padding: 11px 24px; border-radius: 8px; cursor: pointer; transition: 0.2s;
    display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 6px -1px rgba(30,110,181,0.3);
    text-decoration: none; font-family: 'Plus Jakarta Sans', sans-serif !important;
}
.btn-modal-pulihkan:hover { background: var(--primary-dark); transform: translateY(-1px); color: #ffffff; }

.btn-modal-hapus {
    background: var(--danger); color: #ffffff; font-weight: 600; font-size: 0.875rem;
    border: none; padding: 11px 24px; border-radius: 8px; cursor: pointer; transition: 0.2s;
    display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.3);
    text-decoration: none; font-family: 'Plus Jakarta Sans', sans-serif !important;
}
.btn-modal-hapus:hover { background: #dc2626; transform: translateY(-1px); color: #ffffff; }

.table-wrapper {
    display: block !important;
    width: 100% !important;
    overflow-x: auto !important;
    -webkit-overflow-scrolling: touch !important;
    border-radius: 12px;
}

select.form-select {
    font-family: 'Plus Jakarta Sans', sans-serif !important;
    font-size: 0.95rem !important;
    font-weight: 500 !important;
    color: #1f2937 !important;
    padding: 12px 16px !important;
    border: 1px solid #d1d5db !important;
    border-radius: 8px !important;
    background-color: #f9fafb !important;
    appearance: none !important;
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E") !important;
    background-repeat: no-repeat !important;
    background-position: right 14px center !important;
    background-size: 18px !important;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02) !important;
    transition: all 0.2s ease;
}

/* STAT CARDS */
.trash-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 20px;
}

.stat-card {
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    justify-content: center !important;
    text-align: left !important;
    padding: 20px 20px !important;
    gap: 14px !important;
}

.stat-card .stat-icon {
    position: static !important;
    transform: none !important;
    margin: 0 !important;
    flex-shrink: 0 !important;
}

.stat-card-content {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.stat-card.red-trash .stat-icon {
    background: rgba(239, 68, 68, 0.1) !important;
    color: var(--danger) !important;
}

.info-banner-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    width: 100%;
}

.info-banner-actions button {
    flex: 1;
    justify-content: center;
    padding: 10px 14px;
}

.filter-bar-form {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    gap: 12px;
}

.filter-bar-form select {
    width: 100% !important;
    max-width: 100% !important;
}

@media (max-width: 768px) {
    html, body { overflow-x: hidden !important; max-width: 100vw !important; }
    .admin-wrapper { display: block !important; width: 100% !important; overflow-x: hidden !important; }
    .admin-main { width: 100% !important; margin-left: 0 !important; padding: 0 !important; box-sizing: border-box !important; }
    .admin-content { width: 100% !important; padding: 12px !important; box-sizing: border-box !important; margin: 0 !important; }
    .topbar { width: 100% !important; box-sizing: border-box !important; padding: 12px 15px !important; }
    .t-name { display: none !important; }

    .trash-stats-grid {
        display: flex !important;
        flex-direction: column !important;
        gap: 12px !important;
        width: 100% !important;
    }
    .trash-stats-grid > div {
        width: 100% !important;
    }
    .stat-card { 
        padding: 18px 20px !important; 
        border-radius: 12px !important; 
        width: 100% !important; 
        margin: 0 !important; 
        box-sizing: border-box !important; 
        justify-content: center !important; 
    }
    
    .info-banner {
        flex-direction: column !important;
        align-items: stretch !important;
    }
    .info-banner-actions {
        flex-direction: column !important;
    }
    .info-banner-actions button {
        width: 100% !important;
    }

    .filter-bar-form {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 8px !important;
    }
    .table-wrapper table { min-width: 800px !important; }
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
        <span class="bc-item active">Tempat Sampah</span>
      </div>
    </div>
    <div class="topbar-right">
      <div class="topbar-date"><i class="fas fa-calendar-alt me-1" style="margin-right: 4px;"></i> <?= formatTanggalIndo(date('Y-m-d')) ?>[cite: 2]</div>
      
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
      <?= $alert['message'] ?>
    </div>
    <?php endif; ?>

    <!-- STAT CARDS -->
    <div class="trash-stats-grid">
      <div class="stat-card red-trash animate-fadeIn">
        <div class="stat-card-inner" style="display:flex; align-items:center; gap:14px;">
          <div class="stat-icon"><i class="fas fa-trash"></i></div>
          <div class="stat-card-content">
            <div class="stat-label">TOTAL DATA SAMPAH</div>
            <div class="stat-value"><?= $jml_total ?> data</div>
            <div class="stat-sub"><i class="fas fa-info-circle"></i> Menunggu tindakan</div>
          </div>
        </div>
      </div>
      <div class="stat-card green animate-fadeIn delay-1">
        <div class="stat-card-inner" style="display:flex; align-items:center; gap:14px;">
          <div class="stat-icon"><i class="fas fa-arrow-up"></i></div>
          <div class="stat-card-content">
            <div class="stat-label">KAS MASUK TERHAPUS</div>
            <div class="stat-value"><?= $jml_masuk ?> data</div>
            <div class="stat-sub"><i class="fas fa-undo"></i> Bisa dipulihkan</div>
          </div>
        </div>
      </div>
      <div class="stat-card red animate-fadeIn delay-2">
        <div class="stat-card-inner" style="display:flex; align-items:center; gap:14px;">
          <div class="stat-icon"><i class="fas fa-arrow-down"></i></div>
          <div class="stat-card-content">
            <div class="stat-label">KAS KELUAR TERHAPUS</div>
            <div class="stat-value"><?= $jml_keluar ?> data</div>
            <div class="stat-sub"><i class="fas fa-undo"></i> Bisa dipulihkan</div>
          </div>
        </div>
      </div>
    </div>

    <!-- INFO BANNER -->
    <?php if ($jml_total > 0): ?>
    <div class="info-banner" style="background:linear-gradient(135deg,rgba(239,68,68,.08),rgba(245,158,11,.08));border:1px solid rgba(239,68,68,.2);border-radius:var(--radius-lg);padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
      <div style="display:flex;align-items:center;gap:10px">
        <i class="fas fa-exclamation-triangle" style="color:var(--warning);font-size:1.2rem;flex-shrink:0;"></i>
        <div>
          <div style="font-weight:700;font-size:.9rem;color:var(--text-primary)">
            Ada <?= $jml_total ?> data di tempat sampah
          </div>
          <div style="font-size:.7rem;color:var(--text-muted)">
            Data akan tetap tersimpan sampai Anda menghapus secara permanen
          </div>
        </div>
      </div>
      <div class="info-banner-actions">
        <button onclick="confirmPulihkanSemua()" class="btn btn-success btn-sm">
          <i class="fas fa-undo"></i> Pulihkan Semua
        </button>
        <button onclick="confirmKosongkan()" class="btn btn-danger btn-sm">
          <i class="fas fa-fire"></i> Kosongkan Sampah
        </button>
      </div>
    </div>
    <?php endif; ?>

    <!-- FILTER -->
    <div class="filter-bar" style="padding:14px 18px;">
      <form method="GET" class="filter-bar-form">
        <div style="display:flex; align-items:center; gap:8px; width:100%;">
          <span class="filter-label" style="white-space:nowrap;"><i class="fas fa-filter"></i> Filter:</span>
          <select name="jenis" class="form-control form-select" onchange="this.form.submit()">
            <option value="semua"  <?= $filter_jenis=='semua'?'selected':'' ?>>Semua Jenis</option>
            <option value="masuk"  <?= $filter_jenis=='masuk'?'selected':'' ?>>Kas Masuk</option>
            <option value="keluar" <?= $filter_jenis=='keluar'?'selected':'' ?>>Kas Keluar</option>
          </select>
        </div>
        <div style="text-align:right; width:100%;">
          <span style="font-size:.8rem;color:var(--text-muted); white-space:nowrap;">
            <i class="fas fa-database"></i> <?= $total_rows ?> data
          </span>
        </div>
      </form>
    </div>

    <!-- TABEL -->
    <div class="table-wrapper animate-fadeIn">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>No</th>
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
            <!-- Menggunakan Format Tanggal Indonesia -->
            <td style="white-space:nowrap;"><?= formatTanggalIndo($r['tanggal']) ?>[cite: 2]</td>
            <td>
              <span style="text-decoration:line-through;color:var(--text-muted)">
                <?= htmlspecialchars($r['keterangan']) ?>
              </span>
            </td>
            <td><span class="badge badge-primary" style="white-space:nowrap;"><?= htmlspecialchars($r['nama_kategori']) ?></span></td>
            <td style="white-space:nowrap;">
              <?= $r['jenis']=='masuk'
                ? '<span class="badge badge-success"><i class="fas fa-arrow-up"></i> Masuk</span>'
                : '<span class="badge badge-danger"><i class="fas fa-arrow-down"></i> Keluar</span>' ?>
            </td>
            <td class="text-right fw-600 <?= $r['jenis']=='masuk'?'text-success':'text-danger' ?>"
                style="text-decoration:line-through;opacity:.7;white-space:nowrap;">
              <?= ($r['jenis']=='masuk'?'+':'-') . formatRupiah($r['jumlah']) ?>
            </td>
            <!-- Menggunakan Format Tanggal Indonesia dengan Waktu -->
            <td style="font-size:.78rem;color:var(--text-muted);white-space:nowrap;">
              <i class="fas fa-clock"></i>
              <?= formatTanggalIndo($r['deleted_at'], true) ?>[cite: 2]
            </td>
            <td>
              <div style="display:flex;gap:6px;justify-content:center">
                <!-- Tombol Pulihkan Membuka Pop-up -->
                <button onclick="confirmPulihkan(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['keterangan'])) ?>')"
                        class="btn btn-primary btn-sm"
                        style="padding:6px 12px; border-radius:8px;"
                        data-tooltip="Pulihkan">
                  <i class="fas fa-undo"></i>
                </button>
                <!-- Tombol Hapus Permanen Membuka Pop-up -->
                <button onclick="confirmHapusPermanent(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['keterangan'])) ?>')"
                        class="btn btn-danger btn-sm"
                        style="padding:6px 12px; border-radius:8px;"
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

  </div>
</div>
</div>

<!-- ========================================================= -->
<!-- MODAL POP-UP KONFIRMASI PULIHKAN DATA -->
<!-- ========================================================= -->
<div class="modern-modal-overlay" id="modalPulihkan">
  <div class="modern-modal-box">
    <div class="modern-modal-header">
      <div class="modern-modal-title" style="color:var(--primary);">
        <i class="fas fa-undo"></i> Pulihkan Data
      </div>
      <button class="modern-modal-close" onclick="closeModal('modalPulihkan')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modern-modal-body">
      <p style="font-size:0.9rem; color:#475569; margin-bottom:12px;">Apakah Anda yakin ingin memulihkan data transaksi berikut ke daftar transaksi aktif?</p>
      <div style="background:#f8fafc; border:1px solid #cbd5e1; padding:14px; border-radius:10px; font-weight:600; color:#0f172a; margin-bottom:12px;" id="namaDataPulihkan"></div>
      
      <div class="modern-modal-footer">
        <button type="button" class="btn-modal-batal" onclick="closeModal('modalPulihkan')">Batal</button>
        <a id="btnConfirmPulihkan" href="#" class="btn-modal-pulihkan">
          <i class="fas fa-undo"></i> Ya, Pulihkan
        </a>
      </div>
    </div>
  </div>
</div>

<!-- ========================================================= -->
<!-- MODAL POP-UP KONFIRMASI HAPUS PERMANEN DATA -->
<!-- ========================================================= -->
<div class="modern-modal-overlay" id="modalHapus">
  <div class="modern-modal-box">
    <div class="modern-modal-header">
      <div class="modern-modal-title" style="color:var(--danger);">
        <i class="fas fa-exclamation-triangle"></i> Hapus Permanen
      </div>
      <button class="modern-modal-close" onclick="closeModal('modalHapus')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modern-modal-body">
      <p style="font-size:0.9rem; color:#475569; margin-bottom:12px;">Apakah Anda yakin ingin menghapus data transaksi berikut secara permanen?</p>
      <div style="background:#fef2f2; border:1px solid #fecaca; padding:14px; border-radius:10px; font-weight:600; color:#991b1b; margin-bottom:12px;" id="namaDataHapus"></div>
      <p style="font-size:0.8rem; color:#dc2626; font-weight:600;"><i class="fas fa-warning me-1"></i> Peringatan: Tindakan ini tidak dapat dibatalkan!</p>

      <div class="modern-modal-footer">
        <button type="button" class="btn-modal-batal" onclick="closeModal('modalHapus')">Batal</button>
        <a id="btnHapusPermanent" href="#" class="btn-modal-hapus">
          <i class="fas fa-trash"></i> Hapus Permanen
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

// Logic Pop-up Modal Pulihkan Single
function confirmPulihkan(id, nama) {
  document.getElementById('namaDataPulihkan').textContent = nama;
  document.getElementById('btnConfirmPulihkan').href = '?pulihkan=' + id;
  document.getElementById('modalPulihkan').classList.add('active');
}

// Logic Pop-up Modal Hapus Permanen Single
function confirmHapusPermanent(id, nama) {
  document.getElementById('namaDataHapus').textContent = nama;
  document.getElementById('btnHapusPermanent').href = '?hapus=' + id + '&jenis=<?= $filter_jenis ?>&page=<?= $page ?>';
  document.getElementById('modalHapus').classList.add('active');
}

// Logic Pulihkan Semua
function confirmPulihkanSemua() {
  document.getElementById('namaDataPulihkan').textContent = "SEMUA data transaksi di tempat sampah";
  document.getElementById('btnConfirmPulihkan').href = '?pulihkan_semua=1';
  document.getElementById('modalPulihkan').classList.add('active');
}

// Logic Kosongkan Sampah
function confirmKosongkan() {
  document.getElementById('namaDataHapus').textContent = "SEMUA data transaksi di tempat sampah";
  document.getElementById('btnHapusPermanent').href = '?kosongkan=1';
  document.getElementById('modalHapus').classList.add('active');
}

function closeModal(modalId) {
  document.getElementById(modalId).classList.remove('active');
}

// Close modal when clicking outside
['modalPulihkan', 'modalHapus'].forEach(id => {
  const el = document.getElementById(id);
  if(el) {
    el.addEventListener('click', function(e) {
      if (e.target === this) closeModal(id);
    });
  }
});

document.addEventListener('keydown', e => { 
  if (e.key === 'Escape') {
    closeModal('modalPulihkan');
    closeModal('modalHapus');
  } 
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
overlay.addEventListener('click', () => {
  sidebar.classList.remove('open');
  overlay.classList.remove('active');
});
</script>
</body>
</html>