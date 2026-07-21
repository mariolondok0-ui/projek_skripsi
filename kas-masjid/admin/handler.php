<?php
/**
 * AJAX Handler – Sistem Informasi Kas Masjid
 * Menangani request AJAX dari halaman admin
 */
require_once '../includes/config.php';

header('Content-Type: application/json');

// Hanya izinkan request dari admin yang login
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ---- Get summary realtime ----
    case 'get_summary':
        $masuk  = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='masuk'")->fetch_assoc()['t'];
        $keluar = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='keluar'")->fetch_assoc()['t'];
        $bln    = date('Y-m');
        $masuk_bln  = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='masuk'  AND DATE_FORMAT(tanggal,'%Y-%m')='$bln'")->fetch_assoc()['t'];
        $keluar_bln = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='keluar' AND DATE_FORMAT(tanggal,'%Y-%m')='$bln'")->fetch_assoc()['t'];
        echo json_encode([
            'success'     => true,
            'saldo'       => $masuk - $keluar,
            'total_masuk' => $masuk,
            'total_keluar'=> $keluar,
            'masuk_bulan' => $masuk_bln,
            'keluar_bulan'=> $keluar_bln,
            'saldo_format'=> formatRupiah($masuk - $keluar),
        ]);
        break;

    // ---- Get chart data ----
    case 'get_chart':
        $tahun = (int)($_GET['tahun'] ?? date('Y'));
        $bulan_labels = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
        $masuk_data = $keluar_data = $saldo_data = [];
        $kum = 0;
        for ($m = 1; $m <= 12; $m++) {
            $bln = sprintf('%04d-%02d', $tahun, $m);
            $mk = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='masuk'  AND DATE_FORMAT(tanggal,'%Y-%m')='$bln'")->fetch_assoc()['t'];
            $kl = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='keluar' AND DATE_FORMAT(tanggal,'%Y-%m')='$bln'")->fetch_assoc()['t'];
            $masuk_data[]  = $mk;
            $keluar_data[] = $kl;
            $kum += ($mk - $kl);
            $saldo_data[] = $kum;
        }
        echo json_encode([
            'success'      => true,
            'labels'       => $bulan_labels,
            'masuk'        => $masuk_data,
            'keluar'       => $keluar_data,
            'saldo_kumulatif' => $saldo_data,
        ]);
        break;

    // ---- Search transaksi ----
    case 'search_transaksi':
        $q     = sanitize($_GET['q'] ?? '');
        $jenis = sanitize($_GET['jenis'] ?? '');
        $where = ["(t.keterangan LIKE '%$q%' OR k.nama_kategori LIKE '%$q%')"];
        if ($jenis) $where[] = "t.jenis='$jenis'";
        $sql  = "SELECT t.*, k.nama_kategori FROM transaksi t JOIN kategori k ON t.kategori_id=k.id WHERE " . implode(' AND ', $where) . " ORDER BY t.tanggal DESC LIMIT 20";
        $rows = $conn->query($sql);
        $data = [];
        while ($r = $rows->fetch_assoc()) {
            $data[] = [
                'id'           => $r['id'],
                'tanggal'      => date('d M Y', strtotime($r['tanggal'])),
                'keterangan'   => $r['keterangan'],
                'nama_kategori'=> $r['nama_kategori'],
                'jenis'        => $r['jenis'],
                'jumlah'       => (float)$r['jumlah'],
                'jumlah_format'=> formatRupiah($r['jumlah']),
            ];
        }
        echo json_encode(['success' => true, 'data' => $data, 'total' => count($data)]);
        break;

    // ---- Get saldo publik (untuk publik juga, tanpa login) ----
    case 'get_saldo_publik':
        $masuk  = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='masuk'")->fetch_assoc()['t'];
        $keluar = (float)$conn->query("SELECT COALESCE(SUM(jumlah),0) as t FROM transaksi WHERE jenis='keluar'")->fetch_assoc()['t'];
        echo json_encode([
            'success'      => true,
            'saldo'        => $masuk - $keluar,
            'saldo_format' => formatRupiah($masuk - $keluar),
            'total_masuk'  => $masuk,
            'total_keluar' => $keluar,
            'updated_at'   => date('d M Y, H:i'),
        ]);
        break;

    // ---- Get transaksi terbaru ----
    case 'get_transaksi_terbaru':
        $limit = min(20, (int)($_GET['limit'] ?? 5));
        $rows  = $conn->query("SELECT t.*, k.nama_kategori FROM transaksi t JOIN kategori k ON t.kategori_id=k.id ORDER BY t.tanggal DESC, t.id DESC LIMIT $limit");
        $data  = [];
        while ($r = $rows->fetch_assoc()) {
            $data[] = [
                'id'           => $r['id'],
                'tanggal'      => date('d M Y', strtotime($r['tanggal'])),
                'keterangan'   => $r['keterangan'],
                'nama_kategori'=> $r['nama_kategori'],
                'jenis'        => $r['jenis'],
                'jumlah_format'=> formatRupiah($r['jumlah']),
            ];
        }
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    // ---- Delete transaksi via AJAX ----
    case 'delete_transaksi':
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $conn->query("DELETE FROM transaksi WHERE id=$id");
            echo json_encode(['success' => true, 'message' => 'Transaksi berhasil dihapus.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID tidak valid.']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action tidak dikenali: ' . $action]);
        break;
}
exit();
