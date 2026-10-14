<?php
require_once __DIR__ . '/../config/session.php';
require_login();

$conn = db();
$bulan = (int)($_GET['bulan'] ?? date('n'));
$tahun = (int)($_GET['tahun'] ?? date('Y'));
if ($bulan < 1 || $bulan > 12) {
    $bulan = (int)date('n');
}
if ($tahun < 2000 || $tahun > 2100) {
    $tahun = (int)date('Y');
}

$namaBulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
];

$stmt = $conn->prepare("
    SELECT t.id_transaksi, t.tanggal, t.total, t.status, p.nama_pelanggan
    FROM transaksi t
    JOIN pelanggan p ON p.id_pelanggan = t.id_pelanggan
    WHERE MONTH(t.tanggal) = ? AND YEAR(t.tanggal) = ?
    ORDER BY t.id_transaksi ASC
");
$stmt->bind_param('ii', $bulan, $tahun);
$stmt->execute();
$res = $stmt->get_result();
$total = 0.0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan <?= e($namaBulan[$bulan] . ' ' . $tahun) ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 24px; color: #1c2b3a; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #d7e0ea; padding: 8px; text-align: left; }
        th { background: #f4f7fa; }
    </style>
</head>
<body onload="window.print()">
    <h2>Mitra Haidar Cell</h2>
    <p>Laporan bulanan: <?= e($namaBulan[$bulan] . ' ' . $tahun) ?></p>
    <table>
        <tr>
            <th>ID</th>
            <th>Tanggal</th>
            <th>Pelanggan</th>
            <th>Status</th>
            <th>Total</th>
        </tr>
        <?php while ($r = $res->fetch_assoc()):
            $total += (float)$r['total'];
        ?>
        <tr>
            <td><?= (int)$r['id_transaksi'] ?></td>
            <td><?= e(date('d/m/Y H:i', strtotime($r['tanggal']))) ?></td>
            <td><?= e($r['nama_pelanggan']) ?></td>
            <td><?= e($r['status']) ?></td>
            <td><?= rupiah((float)$r['total']) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <h3>Total: <?= rupiah($total) ?></h3>
</body>
</html>
<?php
$stmt->close();
$conn->close();
