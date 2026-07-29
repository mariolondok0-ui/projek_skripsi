-- =====================================================
-- MIGRATION: Tambah fitur Tempat Sampah (Soft Delete)
-- Jalankan query ini di phpMyAdmin pada database kas_masjid
-- =====================================================

USE `kas_masjid`;

-- Tambah kolom deleted_at ke tabel transaksi
ALTER TABLE `transaksi`
  ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`;

-- (Opsional) Index untuk query lebih cepat
ALTER TABLE `transaksi`
  ADD INDEX `idx_deleted_at` (`deleted_at`);
