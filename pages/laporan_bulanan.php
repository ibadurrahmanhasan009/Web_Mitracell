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

$data = [];
$totalSemua = 0.0;
$stmt = $conn->prepare("
    SELECT t.id_transaksi, t.tanggal, t.total, t.status, t.metode_pembayaran, p.nama_pelanggan, u.nama_user
    FROM transaksi t
    JOIN pelanggan p ON p.id_pelanggan = t.id_pelanggan
    JOIN `user` u ON u.id_user = t.id_user
    WHERE MONTH(t.tanggal) = ? AND YEAR(t.tanggal) = ?
    ORDER BY t.id_transaksi DESC
");
$stmt->bind_param('ii', $bulan, $tahun);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $data[] = $row;
    $totalSemua += (float)$row['total'];
}
$stmt->close();
$conn->close();

$namaBulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
];

$pageTitle = 'Laporan bulanan';
$pageSubtitle = $namaBulan[$bulan] . ' ' . $tahun;
ob_start();
?>
<a class="btn btn-success" href="cetak_laporan_bulanan.php?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>" target="_blank">Cetak</a>
<?php
$pageActions = ob_get_clean();
require __DIR__ . '/_layout_start.php';
?>

<div class="card mb-3">
    <div class="card-body">
        <form class="row g-2 align-items-end" method="get">
            <div class="col-sm-4 col-md-3">
                <label class="form-label">Bulan</label>
                <select class="form-select" name="bulan">
                    <?php foreach ($namaBulan as $num => $label): ?>
                        <option value="<?= $num ?>" <?= $bulan === $num ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-3 col-md-2">
                <label class="form-label">Tahun</label>
                <input class="form-control" type="number" name="tahun" value="<?= $tahun ?>" min="2000" max="2100">
            </div>
            <div class="col-sm-3">
                <button class="btn btn-primary" type="submit">Tampilkan</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (!$data): ?>
            <div class="alert alert-warning mb-0">Belum ada transaksi pada periode ini.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tanggal</th>
                            <th>Pelanggan</th>
                            <th>Petugas</th>
                            <th>Status</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($data as $d): ?>
                        <tr>
                            <td><?= (int)$d['id_transaksi'] ?></td>
                            <td><?= e(date('d/m/Y H:i', strtotime($d['tanggal']))) ?></td>
                            <td><?= e($d['nama_pelanggan']) ?></td>
                            <td><?= e($d['nama_user']) ?></td>
                            <td><span class="<?= e(transaksi_status_badge_class($d['status'])) ?>"><?= e($d['status']) ?></span></td>
                            <td><?= rupiah((float)$d['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="fw-bold mt-3 mb-0">Total periode: <?= rupiah($totalSemua) ?></p>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/_layout_end.php'; ?>
