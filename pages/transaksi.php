<?php
require_once __DIR__ . '/../config/session.php';
require_login();

$conn = db();
$idUser = (int)($_SESSION['id_user'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('danger', 'Permintaan tidak valid.');
        header('Location: transaksi.php');
        exit;
    }

    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah') {
        $idPelanggan = (int)($_POST['id_pelanggan'] ?? 0);
        $status = $_POST['status'] ?? 'pending';
        $metode = $_POST['metode_pembayaran'] ?? 'cash';
        $catatan = trim($_POST['catatan'] ?? '');
        $layananIds = $_POST['id_layanan'] ?? [];
        $jumlahList = $_POST['jumlah'] ?? [];

        if (!in_array($status, transaksi_status_enum(), true)) {
            $status = 'pending';
        }
        if (!in_array($metode, transaksi_metode_enum(), true)) {
            $metode = 'cash';
        }

        $items = [];
        if (is_array($layananIds) && is_array($jumlahList)) {
            foreach ($layananIds as $i => $lid) {
                $lid = (int)$lid;
                $qty = (int)($jumlahList[$i] ?? 0);
                if ($lid > 0 && $qty > 0) {
                    $items[] = ['id' => $lid, 'qty' => $qty];
                }
            }
        }

        if ($idPelanggan <= 0 || !$items) {
            set_flash('danger', 'Pelanggan dan minimal satu layanan wajib diisi.');
        } else {
            $conn->begin_transaction();
            try {
                $total = 0.0;
                $rows = [];

                foreach ($items as $item) {
                    $stmtL = $conn->prepare('SELECT id_layanan, harga FROM layanan WHERE id_layanan = ? LIMIT 1');
                    $stmtL->bind_param('i', $item['id']);
                    $stmtL->execute();
                    $layanan = $stmtL->get_result()->fetch_assoc();
                    $stmtL->close();

                    if (!$layanan) {
                        throw new RuntimeException('Layanan tidak ditemukan.');
                    }

                    $harga = (float)$layanan['harga'];
                    $subtotal = $harga * $item['qty'];
                    $total += $subtotal;
                    $rows[] = [
                        'id' => (int)$layanan['id_layanan'],
                        'qty' => $item['qty'],
                        'harga' => $harga,
                        'subtotal' => $subtotal,
                    ];
                }

                $stmtT = $conn->prepare(
                    'INSERT INTO transaksi (id_pelanggan, id_user, tanggal, total, status, metode_pembayaran, catatan)
                     VALUES (?, ?, NOW(), ?, ?, ?, ?)'
                );
                $stmtT->bind_param('iidsss', $idPelanggan, $idUser, $total, $status, $metode, $catatan);
                $stmtT->execute();
                $idTransaksi = (int)$conn->insert_id;
                $stmtT->close();

                $stmtD = $conn->prepare(
                    'INSERT INTO detail_transaksi (id_transaksi, id_layanan, jumlah, harga, subtotal)
                     VALUES (?, ?, ?, ?, ?)'
                );
                foreach ($rows as $row) {
                    $stmtD->bind_param('iiidd', $idTransaksi, $row['id'], $row['qty'], $row['harga'], $row['subtotal']);
                    $stmtD->execute();
                }
                $stmtD->close();

                $conn->commit();
                set_flash('success', 'Transaksi berhasil disimpan.');
            } catch (Throwable $e) {
                $conn->rollback();
                set_flash('danger', 'Gagal menyimpan transaksi.');
            }
        }
    } elseif ($aksi === 'update_status') {
        $id = (int)($_POST['id_transaksi'] ?? 0);
        $status = $_POST['status'] ?? '';
        if ($id > 0 && in_array($status, transaksi_status_enum(), true)) {
            $stmt = $conn->prepare('UPDATE transaksi SET status = ? WHERE id_transaksi = ?');
            $stmt->bind_param('si', $status, $id);
            $stmt->execute();
            $stmt->close();
            set_flash('success', 'Status transaksi diperbarui.');
        }
    } elseif ($aksi === 'hapus') {
        $id = (int)($_POST['id_transaksi'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare('DELETE FROM transaksi WHERE id_transaksi = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            set_flash('success', 'Transaksi dihapus.');
        }
    }

    $conn->close();
    header('Location: transaksi.php');
    exit;
}

$pelangganList = [];
$resP = $conn->query('SELECT id_pelanggan, nama_pelanggan FROM pelanggan ORDER BY nama_pelanggan ASC');
if ($resP) {
    while ($row = $resP->fetch_assoc()) {
        $pelangganList[] = $row;
    }
}

$layananList = [];
$hargaById = [];
$resL = $conn->query('SELECT id_layanan, nama_layanan, harga FROM layanan ORDER BY nama_layanan ASC');
if ($resL) {
    while ($row = $resL->fetch_assoc()) {
        $layananList[] = $row;
        $hargaById[(string)$row['id_layanan']] = (float)$row['harga'];
    }
}

$transaksiList = [];
$resT = $conn->query("
    SELECT t.id_transaksi, t.tanggal, t.total, t.status, t.metode_pembayaran,
           p.nama_pelanggan, u.nama_user,
           GROUP_CONCAT(CONCAT(l.nama_layanan, ' x', d.jumlah) SEPARATOR ', ') AS item_ringkas
    FROM transaksi t
    JOIN pelanggan p ON p.id_pelanggan = t.id_pelanggan
    JOIN `user` u ON u.id_user = t.id_user
    LEFT JOIN detail_transaksi d ON d.id_transaksi = t.id_transaksi
    LEFT JOIN layanan l ON l.id_layanan = d.id_layanan
    GROUP BY t.id_transaksi, t.tanggal, t.total, t.status, t.metode_pembayaran, p.nama_pelanggan, u.nama_user
    ORDER BY t.id_transaksi DESC
");
if ($resT) {
    while ($row = $resT->fetch_assoc()) {
        $transaksiList[] = $row;
    }
}
$conn->close();

$optionsHtml = '<option value="">Pilih layanan</option>';
foreach ($layananList as $l) {
    $optionsHtml .= '<option value="' . (int)$l['id_layanan'] . '">'
        . e($l['nama_layanan']) . ' (' . rupiah((float)$l['harga']) . ')</option>';
}

$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

$pageTitle = 'Transaksi';
$pageSubtitle = 'Catat penjualan pulsa, data, dan layanan konter.';
ob_start();
?>
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
    <i class="bi bi-plus-lg"></i> Transaksi baru
</button>
<?php
$pageActions = ob_get_clean();
require __DIR__ . '/_layout_start.php';
?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tanggal</th>
                        <th>Pelanggan</th>
                        <th>Item</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Metode</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$transaksiList): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Belum ada transaksi.</td></tr>
                <?php else: ?>
                    <?php foreach ($transaksiList as $t): ?>
                        <tr>
                            <td><?= (int)$t['id_transaksi'] ?></td>
                            <td class="text-nowrap"><?= e(date('d/m/Y H:i', strtotime($t['tanggal']))) ?></td>
                            <td class="fw-semibold"><?= e($t['nama_pelanggan']) ?></td>
                            <td><?= e((string)($t['item_ringkas'] ?: '-')) ?></td>
                            <td class="text-nowrap"><?= rupiah((float)$t['total']) ?></td>
                            <td><span class="<?= e(transaksi_status_badge_class($t['status'])) ?>"><?= e($t['status']) ?></span></td>
                            <td><?= e($t['metode_pembayaran']) ?></td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-sm btn-outline-secondary" href="cetak_transaksi.php?id=<?= (int)$t['id_transaksi'] ?>" target="_blank">Cetak</a>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalStatus<?= (int)$t['id_transaksi'] ?>">Status</button>
                                    <form method="post" onsubmit="return confirm('Hapus transaksi ini?');">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="aksi" value="hapus">
                                        <input type="hidden" name="id_transaksi" value="<?= (int)$t['id_transaksi'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <?= csrf_input() ?>
                <input type="hidden" name="aksi" value="tambah">
                <input type="hidden" name="total" id="totalHidden" value="0">
                <div class="modal-header">
                    <h5 class="modal-title">Transaksi baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Pelanggan</label>
                            <select class="form-select" name="id_pelanggan" required>
                                <option value="">Pilih pelanggan</option>
                                <?php foreach ($pelangganList as $p): ?>
                                    <option value="<?= (int)$p['id_pelanggan'] ?>"><?= e($p['nama_pelanggan']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <?php foreach (transaksi_status_enum() as $st): ?>
                                    <option value="<?= e($st) ?>"><?= e($st) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Metode</label>
                            <select class="form-select" name="metode_pembayaran">
                                <?php foreach (transaksi_metode_enum() as $m): ?>
                                    <option value="<?= e($m) ?>"><?= e($m) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive mt-3">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Layanan</th>
                                    <th style="width:90px">Qty</th>
                                    <th style="width:130px">Harga</th>
                                    <th style="width:140px">Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="detailTbody"></tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnAddRow">Tambah item</button>

                    <div class="mt-3">
                        <label class="form-label">Catatan</label>
                        <textarea class="form-control" name="catatan" rows="2"></textarea>
                    </div>
                    <div class="mt-3 fw-bold">Total: <span id="totalLabel">Rp 0</span></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary" type="submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php foreach ($transaksiList as $t): ?>
<div class="modal fade" id="modalStatus<?= (int)$t['id_transaksi'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <?= csrf_input() ?>
                <input type="hidden" name="aksi" value="update_status">
                <input type="hidden" name="id_transaksi" value="<?= (int)$t['id_transaksi'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah status #<?= (int)$t['id_transaksi'] ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <select class="form-select" name="status">
                        <?php foreach (transaksi_status_enum() as $st): ?>
                            <option value="<?= e($st) ?>" <?= $t['status'] === $st ? 'selected' : '' ?>><?= e($st) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary" type="submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php
$pageScripts = '<script>MitraHaidarTransaksi.init({ hargaById: '
    . json_encode($hargaById, $jsonFlags)
    . ', layananOptionsHtml: '
    . json_encode($optionsHtml, $jsonFlags)
    . ' });</script>';
require __DIR__ . '/_layout_end.php';
