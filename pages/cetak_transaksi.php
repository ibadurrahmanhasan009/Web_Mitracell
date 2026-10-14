<?php
require_once __DIR__ . '/../config/session.php';
require_login();

$conn = db();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die('ID tidak valid');
}

$stmt = $conn->prepare("
    SELECT t.*, p.nama_pelanggan, u.nama_user
    FROM transaksi t
    JOIN pelanggan p ON p.id_pelanggan = t.id_pelanggan
    JOIN `user` u ON u.id_user = t.id_user
    WHERE t.id_transaksi = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$transaksi = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$transaksi) {
    die('Data tidak ditemukan');
}

$stmt = $conn->prepare("
    SELECT d.jumlah, d.harga, d.subtotal, l.nama_layanan
    FROM detail_transaksi d
    JOIN layanan l ON l.id_layanan = d.id_layanan
    WHERE d.id_transaksi = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$detail = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Struk #<?= (int)$transaksi['id_transaksi'] ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 24px; color: #1c2b3a; }
        h2 { margin: 0 0 4px; }
        .muted { color: #6b7c8f; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #d7e0ea; padding: 8px; text-align: left; }
        th { background: #f4f7fa; }
        .total { margin-top: 16px; font-size: 1.1rem; }
    </style>
</head>
<body onload="window.print()">
    <h2>Mitra Haidar Cell</h2>
    <div class="muted">Struk transaksi</div>
    <p><b>ID:</b> <?= (int)$transaksi['id_transaksi'] ?><br>
       <b>Pelanggan:</b> <?= e($transaksi['nama_pelanggan']) ?><br>
       <b>Petugas:</b> <?= e($transaksi['nama_user']) ?><br>
       <b>Tanggal:</b> <?= e(date('d/m/Y H:i', strtotime($transaksi['tanggal']))) ?><br>
       <b>Status:</b> <?= e($transaksi['status']) ?><br>
       <b>Metode:</b> <?= e($transaksi['metode_pembayaran']) ?></p>
    <table>
        <tr>
            <th>Layanan</th>
            <th>Jumlah</th>
            <th>Harga</th>
            <th>Subtotal</th>
        </tr>
        <?php while ($d = $detail->fetch_assoc()): ?>
        <tr>
            <td><?= e($d['nama_layanan']) ?></td>
            <td><?= (int)$d['jumlah'] ?></td>
            <td><?= rupiah((float)$d['harga']) ?></td>
            <td><?= rupiah((float)$d['subtotal']) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <p class="total"><b>Total: <?= rupiah((float)$transaksi['total']) ?></b></p>
</body>
</html>
<?php
$stmt->close();
$conn->close();
