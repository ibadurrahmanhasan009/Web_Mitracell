<?php
// ============================================
// File: /config/koneksi.php
// Tujuan: Membuat koneksi ke database MySQL
// ============================================

// Sesuaikan kredensial database kamu di sini.
// Bisa diatur via environment variable sederhana (jika ada),
// jika tidak ada akan memakai nilai default di bawah.
$DB_HOST = getenv('DB_HOST') !== false ? getenv('DB_HOST') : 'localhost';
$DB_USER = getenv('DB_USER') !== false ? getenv('DB_USER') : 'root';
$DB_PASS = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$DB_NAME = getenv('DB_NAME') !== false ? getenv('DB_NAME') : 'mitra_haidar_cell';
$DB_PORT = getenv('DB_PORT') !== false ? (int)getenv('DB_PORT') : 3306;

// ============================================
// Helper: pastikan tabel sudah ada
// ============================================
function ensureSchema(mysqli $conn): void
{
    global $DB_NAME;

    // Cek apakah tabel `user` ada dulu.
    // Jika belum ada (mis. karena database.sql belum dijalankan), kita auto-create supaya website langsung bisa jalan.
    $check = $conn->prepare('
        SELECT 1
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
        LIMIT 1
    ');
    $tableName = 'user';
    $check->bind_param('ss', $DB_NAME, $tableName);
    $check->execute();
    $res = $check->get_result();

    $exists = ($res && $res->num_rows > 0);
    $check->close();

    if ($exists) {
        return;
    }

    // Ambil isi database.sql
    $sqlFile = __DIR__ . '/../database.sql';
    if (!file_exists($sqlFile)) {
        die('database.sql tidak ditemukan. Pastikan file ada di root proyek.');
    }

    $fullSql = file_get_contents($sqlFile);
    if ($fullSql === false || trim($fullSql) === '') {
        die('Gagal membaca database.sql.');
    }

    // Ambil bagian setelah perintah USE agar tidak perlu CREATE DATABASE saat runtime.
    $useStmt = 'USE ' . $DB_NAME . ';';
    $pos = strpos($fullSql, $useStmt);
    if ($pos !== false) {
        $schemaSql = substr($fullSql, $pos + strlen($useStmt));
    } else {
        // Fallback: hapus baris CREATE DATABASE dan USE, lalu jalankan sisanya.
        $schemaSql = preg_replace('/^CREATE DATABASE.*$/m', '', $fullSql);
        $schemaSql = preg_replace('/^USE\\s+.*;$/m', '', $schemaSql);
    }

    // Jalankan multi query
    if (!$conn->multi_query($schemaSql)) {
        die('Auto-create schema gagal: ' . $conn->error);
    }

    // Buang semua result set dari multi_query
    while ($conn->more_results()) {
        $conn->next_result();
    }

    // Verifikasi ulang tabel `user` ada
    $check2 = $conn->prepare('
        SELECT 1
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
        LIMIT 1
    ');
    $check2->bind_param('ss', $DB_NAME, $tableName);
    $check2->execute();
    $res2 = $check2->get_result();
    $exists2 = ($res2 && $res2->num_rows > 0);
    $check2->close();

    if (!$exists2) {
        die('Auto-create schema selesai, tapi tabel `user` masih tidak ditemukan. Cek database & privileges MySQL kamu.');
    }
}

/**
 * Pastikan ada user default admin (password: admin) jika tabel kosong / seed SQL gagal.
 * Password disimpan dengan password_hash agar login selalu konsisten.
 */
function ensureDefaultAdmin(mysqli $conn): void
{
    $username = 'admin';
    $stmt = $conn->prepare('SELECT id_user, password FROM `user` WHERE username = ? LIMIT 1');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    $defaultPass = 'admin';
    $hash = password_hash($defaultPass, PASSWORD_DEFAULT);
    if ($hash === false) {
        return;
    }

    if ($row === null) {
        $ins = $conn->prepare('INSERT INTO `user` (username, password, nama_user, role) VALUES (?, ?, ?, ?)');
        $nama = 'Admin Utama';
        $role = 'admin';
        $ins->bind_param('ssss', $username, $hash, $nama, $role);
        $ins->execute();
        $ins->close();
    }
}

// Membuat koneksi menggunakan MySQLi (object oriented)
function getMysqliConnection(): mysqli
{
    global $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT;

    // Buat koneksi
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);

    // Cek error koneksi
    if ($conn->connect_error) {
        // Untuk pemula: tampilkan error agar mudah debugging.
        die("Koneksi database gagal: " . $conn->connect_error);
    }

    // Set charset agar penyimpanan teks aman
    $conn->set_charset('utf8mb4');

    // Pastikan skema sudah ada (biar pemula tidak harus import manual jika lupa)
    ensureSchema($conn);
    ensureDefaultAdmin($conn);

    return $conn;
}

