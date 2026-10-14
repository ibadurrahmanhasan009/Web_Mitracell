-- Mitra Haidar Cell — skema production (sesuai upgrade)
-- Charset utf8mb4. Tabel `user` selalu pakai backtick (reserved di MySQL).

CREATE DATABASE IF NOT EXISTS mitra_haidar_cell
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE mitra_haidar_cell;

-- ======================
-- TABEL USER
-- ======================
CREATE TABLE IF NOT EXISTS `user` (
  id_user INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  nama_user VARCHAR(100) NOT NULL,
  role ENUM('admin','petugas') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================
-- TABEL PELANGGAN
-- ======================
CREATE TABLE IF NOT EXISTS pelanggan (
  id_pelanggan INT AUTO_INCREMENT PRIMARY KEY,
  nama_pelanggan VARCHAR(100) NOT NULL,
  no_hp VARCHAR(25) NOT NULL,
  alamat TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================
-- TABEL LAYANAN
-- ======================
CREATE TABLE IF NOT EXISTS layanan (
  id_layanan INT AUTO_INCREMENT PRIMARY KEY,
  nama_layanan VARCHAR(100) NOT NULL,
  kategori VARCHAR(100) NOT NULL,
  harga DECIMAL(12,2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================
-- TABEL TRANSAKSI
-- ======================
CREATE TABLE IF NOT EXISTS transaksi (
  id_transaksi INT AUTO_INCREMENT PRIMARY KEY,
  id_pelanggan INT NOT NULL,
  id_user INT NOT NULL,
  tanggal DATETIME DEFAULT CURRENT_TIMESTAMP,
  total DECIMAL(12,2) NOT NULL,
  status ENUM('pending','proses','selesai','diambil') DEFAULT 'pending',
  metode_pembayaran ENUM('cash','transfer','e-wallet') DEFAULT 'cash',
  catatan TEXT,
  CONSTRAINT fk_transaksi_pelanggan
    FOREIGN KEY (id_pelanggan) REFERENCES pelanggan(id_pelanggan)
      ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_transaksi_user
    FOREIGN KEY (id_user) REFERENCES `user`(id_user)
      ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================
-- TABEL DETAIL TRANSAKSI
-- ======================
CREATE TABLE IF NOT EXISTS detail_transaksi (
  id_detail INT AUTO_INCREMENT PRIMARY KEY,
  id_transaksi INT NOT NULL,
  id_layanan INT NOT NULL,
  jumlah INT NOT NULL,
  harga DECIMAL(12,2) NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  CONSTRAINT fk_detail_transaksi
    FOREIGN KEY (id_transaksi) REFERENCES transaksi(id_transaksi)
      ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_detail_layanan
    FOREIGN KEY (id_layanan) REFERENCES layanan(id_layanan)
      ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================
-- INDEX
-- ======================
CREATE INDEX idx_transaksi_tanggal ON transaksi(tanggal);
CREATE INDEX idx_pelanggan_nama ON pelanggan(nama_pelanggan);

-- ======================
-- DATA AWAL
-- Password plain 'admin' — saat login pertama, aplikasi meng-hash otomatis (lihat auth/login.php).
-- ======================
-- Password plain 'admin' — login akan hash otomatis, atau pakai seed PHP (ensureDefaultAdmin di koneksi.php)
INSERT INTO `user` (username, password, nama_user, role)
VALUES ('admin', 'admin', 'Admin Utama', 'admin')
ON DUPLICATE KEY UPDATE username = VALUES(username);

INSERT INTO layanan (nama_layanan, kategori, harga)
SELECT 'Pulsa', 'Biasa', 10000
WHERE NOT EXISTS (SELECT 1 FROM layanan WHERE nama_layanan = 'Pulsa' LIMIT 1);

INSERT INTO layanan (nama_layanan, kategori, harga)
SELECT 'Pulsa Data', 'Internet', 20000
WHERE NOT EXISTS (SELECT 1 FROM layanan WHERE nama_layanan = 'Pulsa Data' LIMIT 1);

INSERT INTO layanan (nama_layanan, kategori, harga)
SELECT 'Tarik Tunai', 'Keuangan', 5000
WHERE NOT EXISTS (SELECT 1 FROM layanan WHERE nama_layanan = 'Tarik Tunai' LIMIT 1);
