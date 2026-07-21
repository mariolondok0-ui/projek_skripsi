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
