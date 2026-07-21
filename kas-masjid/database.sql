CREATE DATABASE IF NOT EXISTS `kas_masjid` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `kas_masjid`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nama` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `kategori` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` VARCHAR(100) NOT NULL,
  `jenis` ENUM('masuk','keluar') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `transaksi` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `tanggal` DATE NOT NULL,
  `keterangan` TEXT NOT NULL,
  `jumlah` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `jenis` ENUM('masuk','keluar') NOT NULL,
  `kategori_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`kategori_id`) REFERENCES `kategori`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin default (password: password)
INSERT INTO `users` (`nama`, `email`, `password`) VALUES
('Administrator', 'admin@masjid.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Kategori
INSERT INTO `kategori` (`nama_kategori`, `jenis`) VALUES
('Infak Jumat','masuk'),('Sedekah','masuk'),('Donasi','masuk'),('Zakat','masuk'),('Infak Umum','masuk'),
('Operasional Masjid','keluar'),('Pemeliharaan Fasilitas','keluar'),('Kegiatan Keagamaan','keluar'),('Listrik & Air','keluar'),('Program Sosial','keluar');

-- Data Sample 2026
INSERT INTO `transaksi` (`tanggal`,`keterangan`,`jumlah`,`jenis`,`kategori_id`,`user_id`) VALUES
('2026-01-03','Infak Jumat Minggu 1 Januari',850000,'masuk',1,1),
('2026-01-05','Sedekah Jemaah',250000,'masuk',2,1),
('2026-01-07','Bayar Tagihan Listrik Januari',350000,'keluar',9,1),
('2026-01-10','Infak Jumat Minggu 2 Januari',920000,'masuk',1,1),
('2026-01-15','Donasi Renovasi Masjid',2000000,'masuk',3,1),
('2026-01-17','Pembelian Perlengkapan Kebersihan',180000,'keluar',7,1),
('2026-01-17','Infak Jumat Minggu 3 Januari',780000,'masuk',1,1),
('2026-01-20','Biaya Perawatan Sound System',500000,'keluar',7,1),
('2026-01-24','Infak Jumat Minggu 4 Januari',890000,'masuk',1,1),
('2026-01-28','Kegiatan Pengajian Bulanan',300000,'keluar',8,1),
('2026-02-07','Infak Jumat Minggu 1 Februari',810000,'masuk',1,1),
('2026-02-12','Bayar Tagihan Air Februari',85000,'keluar',9,1),
('2026-02-14','Infak Jumat Minggu 2 Februari',950000,'masuk',1,1),
('2026-02-18','Donasi Kegiatan Isra Miraj',1500000,'masuk',3,1),
('2026-02-20','Biaya Konsumsi Pengajian',450000,'keluar',8,1),
('2026-02-21','Infak Jumat Minggu 3 Februari',870000,'masuk',1,1),
('2026-02-25','Perbaikan Atap Selasar',800000,'keluar',7,1),
('2026-02-28','Infak Jumat Minggu 4 Februari',760000,'masuk',1,1),
('2026-03-06','Infak Jumat Minggu 1 Maret',830000,'masuk',1,1),
('2026-03-07','Bayar Listrik Maret',370000,'keluar',9,1),
('2026-03-10','Donasi Ramadhan',3000000,'masuk',3,1),
('2026-03-13','Infak Jumat Minggu 2 Maret',1100000,'masuk',1,1),
('2026-03-15','Program Buka Puasa Bersama',1200000,'keluar',10,1),
('2026-03-20','Infak Jumat Minggu 3 Maret',1350000,'masuk',1,1),
('2026-03-22','Zakat Fitrah',2500000,'masuk',4,1),
('2026-03-25','Distribusi Zakat',2500000,'keluar',10,1),
('2026-03-27','Infak Jumat Minggu 4 Maret',1200000,'masuk',1,1),
('2026-04-04','Infak Jumat Minggu 1 April',950000,'masuk',1,1),
('2026-04-07','Bayar Listrik April',360000,'keluar',9,1),
('2026-04-11','Infak Jumat Minggu 2 April',880000,'masuk',1,1),
('2026-04-18','Infak Jumat Minggu 3 April',810000,'masuk',1,1),
('2026-04-20','Pembelian Karpet Masjid',1500000,'keluar',7,1),
('2026-04-25','Infak Jumat Minggu 4 April',920000,'masuk',1,1),
('2026-05-02','Infak Jumat Minggu 1 Mei',870000,'masuk',1,1),
('2026-05-05','Bayar Listrik Mei',380000,'keluar',9,1),
('2026-05-09','Infak Jumat Minggu 2 Mei',900000,'masuk',1,1),
('2026-05-15','Donasi Infrastruktur Masjid',2500000,'masuk',3,1),
('2026-05-16','Infak Jumat Minggu 3 Mei',850000,'masuk',1,1),
('2026-05-22','Operasional Kebersihan',200000,'keluar',6,1),
('2026-05-23','Infak Jumat Minggu 4 Mei',890000,'masuk',1,1),
('2026-06-06','Infak Jumat Minggu 1 Juni',820000,'masuk',1,1),
('2026-06-07','Bayar Listrik Juni',355000,'keluar',9,1),
('2026-06-10','Infak Umum',450000,'masuk',5,1),
('2026-06-13','Infak Jumat Minggu 2 Juni',910000,'masuk',1,1),
('2026-06-18','Kegiatan Tahun Baru Islam',600000,'keluar',8,1),
('2026-06-20','Infak Jumat Minggu 3 Juni',875000,'masuk',1,1),
('2026-06-27','Infak Jumat Minggu 4 Juni',830000,'masuk',1,1),
('2026-07-04','Infak Jumat Minggu 1 Juli',860000,'masuk',1,1),
('2026-07-07','Bayar Listrik Juli',370000,'keluar',9,1),
('2026-07-11','Infak Jumat Minggu 2 Juli',920000,'masuk',1,1),
('2026-07-15','Sedekah Jemaah',280000,'masuk',2,1),
('2026-07-18','Infak Jumat Minggu 3 Juli',890000,'masuk',1,1),
('2026-07-20','Pemeliharaan AC Masjid',750000,'keluar',7,1);
