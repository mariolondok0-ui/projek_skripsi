<?php
session_start();
// Sesuaikan path config dengan struktur foldermu
require_once '../includes/config.php';

// Cek apakah ada data yang dikirim dari checkbox
if (!isset($_POST['id_pilihan']) || empty($_POST['id_pilihan'])) {
    echo "<script>alert('Tidak ada data yang dipilih!'); window.close();</script>";
    exit;
}

// Amankan array ID yang dicentang
$id_array = $_POST['id_pilihan'];
$id_aman = array_map('intval', $id_array); // Pastikan semuanya angka
$id_list = implode(',', $id_aman); // Ubah jadi format: 1,2,3

// Query hanya mengambil ID yang ada di dalam list pilihan
$query = "SELECT * FROM transaksi WHERE id IN ($id_list) ORDER BY tanggal DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan Pilihan</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h2 { margin: 0 0 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table th, table td { border: 1px solid #000; padding: 8px; text-align: left; }
        table th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        /* Sembunyikan elemen ini saat di-print/PDF */
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()"> <!-- Script onload ini akan otomatis memunculkan pop-up print browser -->

    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px; background: #007bff; color: white; border: none; cursor: pointer;">Cetak Sekarang / Save as PDF</button>
    </div>

    <div class="header">
        <h2>Laporan Kas Masjid Baiturrohman</h2>
        <p>Data Pilihan</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" width="5%">No</th>
                <th width="15%">Tanggal</th>
                <th>Keterangan</th>
                <th width="15%">Jenis</th>
                <th class="text-right" width="20%">Nominal</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            $total_masuk = 0;
            $total_keluar = 0;

            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()): 
                    // Hitung total
                    if ($row['jenis'] == 'masuk') {
                        $total_masuk += $row['nominal'];
                    } else {
                        $total_keluar += $row['nominal'];
                    }
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                <td><?= $row['keterangan'] ?></td>
                <td><?= ucfirst($row['jenis']) ?></td>
                <td class="text-right">Rp <?= number_format($row['nominal'], 0, ',', '.') ?></td>
            </tr>
            <?php 
                endwhile; 
            } else {
                echo "<tr><td colspan='5' class='text-center'>Data tidak ditemukan</td></tr>";
            }
            ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="text-right">Total Pemasukan Dipilih</th>
                <th class="text-right">Rp <?= number_format($total_masuk, 0, ',', '.') ?></th>
            </tr>
            <tr>
                <th colspan="4" class="text-right">Total Pengeluaran Dipilih</th>
                <th class="text-right">Rp <?= number_format($total_keluar, 0, ',', '.') ?></th>
            </tr>
        </tfoot>
    </table>

</body>
</html>