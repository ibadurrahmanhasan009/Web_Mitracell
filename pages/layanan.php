<?php
require_once __DIR__ . '/../config/session.php';
require_login();

$conn = db();
$isAdmin = (($_SESSION['role'] ?? '') === 'admin');
$kategoriOpsi = ['Pulsa', 'Internet', 'Keuangan', 'Token', 'Lainnya'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isAdmin) {
        set_flash('danger', 'Hanya admin yang dapat mengubah layanan.');
        header('Location: layanan.php');
        exit;
    }
    if (!verify_csrf()) {
        set_flash('danger', 'Permintaan tidak valid.');
        header('Location: layanan.php');
        exit;
    }

    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'hapus') {
        $id = (int)($_POST['id_layanan'] ?? 0);
        $cek = $conn->prepare('SELECT COUNT(*) AS cnt FROM detail_transaksi WHERE id_layanan = ?');
        $cek->bind_param('i', $id);
        $cek->execute();
        $dipakai = (int)($cek->get_result()->fetch_assoc()['cnt'] ?? 0);
        $cek->close();

        if ($dipakai > 0) {
            set_flash('danger', 'Layanan tidak bisa dihapus karena sudah dipakai transaksi.');
        } else {
            $stmt = $conn->prepare('DELETE FROM layanan WHERE id_layanan = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            set_flash('success', 'Layanan dihapus.');
        }
    } else {
        $nama = trim($_POST['nama_layanan'] ?? '');
        $kategori = trim($_POST['kategori'] ?? '');
        $harga = (float)str_replace(',', '.', trim($_POST['harga'] ?? '0'));

        if ($nama === '' || $kategori === '' || $harga <= 0) {
            set_flash('danger', 'Nama, kategori, dan harga wajib diisi.');
        } elseif ($aksi === 'tambah') {
            $stmt = $conn->prepare('INSERT INTO layanan (nama_layanan, kategori, harga) VALUES (?, ?, ?)');
            $stmt->bind_param('ssd', $nama, $kategori, $harga);
            $stmt->execute();
            $stmt->close();
            set_flash('success', 'Layanan ditambahkan.');
        } elseif ($aksi === 'edit') {
            $id = (int)($_POST['id_layanan'] ?? 0);
            $stmt = $conn->prepare('UPDATE layanan SET nama_layanan = ?, id_kategori = ?, harga = ? WHERE id_layanan = ?');
            $stmt->bind_param('ssdi', $nama, $kategori, $harga, $id);
            $stmt->execute();
            $stmt->close();
            set_flash('success', 'Layanan diperbarui.');
        }
    }

    $conn->close();
    header('Location: layanan.php');
    exit;
}

$layanan = [];
$res = $conn->query('SELECT id_layanan, nama_layanan, id_kategori, harga FROM layanan ORDER BY id_layanan DESC');
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $layanan[] = $row;
    }
}
$conn->close();

$pageTitle = 'Layanan';
$pageSubtitle = 'Daftar produk dan jasa konter.';
ob_start();
if ($isAdmin):
?>
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
    <i class="bi bi-plus-lg"></i> Tambah
</button>
<?php
endif;
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
                        <th>Nama</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <?php if ($isAdmin): ?><th class="text-end">Aksi</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$layanan): ?>
                    <tr><td colspan="<?= $isAdmin ? 5 : 4 ?>" class="text-center text-muted py-4">Belum ada layanan.</td></tr>
                <?php else: ?>
                    <?php foreach ($layanan as $l): ?>
                        <tr>
                            <td><?= (int)$l['id_layanan'] ?></td>
                            <td class="fw-semibold"><?= e($l['nama_layanan']) ?></td>
                            <td><?= e($l['id_kategori']) ?></td>
                            <td><?= rupiah((float)$l['harga']) ?></td>
                            <?php if ($isAdmin): ?>
                            <td>
                                <div class="actions">
                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEdit<?= (int)$l['id_layanan'] ?>">Edit</button>
                                    <form method="post" onsubmit="return confirm('Hapus layanan ini?');">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="aksi" value="hapus">
                                        <input type="hidden" name="id_layanan" value="<?= (int)$l['id_layanan'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($isAdmin): ?>
<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <?= csrf_input() ?>
                <input type="hidden" name="aksi" value="tambah">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Layanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama layanan</label>
                        <input class="form-control" name="nama_layanan" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="kategori" required>
                            <?php foreach ($kategoriOpsi as $kat): ?>
                                <option value="<?= e($kat) ?>"><?= e($kat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Harga</label>
                        <input class="form-control" type="number" min="1" step="1" name="harga" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary" type="submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php foreach ($layanan as $l): ?>
<div class="modal fade" id="modalEdit<?= (int)$l['id_layanan'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <?= csrf_input() ?>
                <input type="hidden" name="aksi" value="edit">
                <input type="hidden" name="id_layanan" value="<?= (int)$l['id_layanan'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Layanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama layanan</label>
                        <input class="form-control" name="nama_layanan" value="<?= e($l['nama_layanan']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="kategori" required>
                            <?php foreach ($kategoriOpsi as $kat): ?>
                                <option value="<?= e($kat) ?>" <?= $l['id_kategori'] === $kat ? 'selected' : '' ?>><?= e($kat) ?></option>
                            <?php endforeach; ?>
                            <?php if (!in_array($l['id_kategori'], $kategoriOpsi, true)): ?>
                                <option value="<?= e($l['id_kategori']) ?>" selected><?= e($l['id_kategori']) ?></option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Harga</label>
                        <input class="form-control" type="number" min="1" step="1" name="harga" value="<?= (int)$l['harga'] ?>" required>
                    </div>
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
<?php endif; ?>

<?php require __DIR__ . '/_layout_end.php'; ?>
