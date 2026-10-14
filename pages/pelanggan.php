<?php
require_once __DIR__ . '/../config/session.php';
require_login();

$conn = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('danger', 'Permintaan tidak valid.');
        header('Location: pelanggan.php');
        exit;
    }

    $aksi = $_POST['aksi'] ?? '';
    $nama = trim($_POST['nama_pelanggan'] ?? '');
    $noHp = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');

    if ($aksi === 'tambah') {
        if ($nama === '' || $noHp === '') {
            set_flash('danger', 'Nama dan nomor HP wajib diisi.');
        } else {
            $stmt = $conn->prepare('INSERT INTO pelanggan (nama_pelanggan, no_hp, alamat) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $nama, $noHp, $alamat);
            $stmt->execute();
            $stmt->close();
            set_flash('success', 'Pelanggan berhasil ditambahkan.');
        }
    } elseif ($aksi === 'edit') {
        $id = (int)($_POST['id_pelanggan'] ?? 0);
        if ($id <= 0 || $nama === '' || $noHp === '') {
            set_flash('danger', 'Data pelanggan tidak valid.');
        } else {
            $stmt = $conn->prepare('UPDATE pelanggan SET nama_pelanggan = ?, no_hp = ?, alamat = ? WHERE id_pelanggan = ?');
            $stmt->bind_param('sssi', $nama, $noHp, $alamat, $id);
            $stmt->execute();
            $stmt->close();
            set_flash('success', 'Data pelanggan diperbarui.');
        }
    } elseif ($aksi === 'hapus') {
        $id = (int)($_POST['id_pelanggan'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare('DELETE FROM pelanggan WHERE id_pelanggan = ?');
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                set_flash('success', 'Pelanggan dihapus.');
            } else {
                set_flash('danger', 'Pelanggan tidak bisa dihapus karena masih punya transaksi.');
            }
            $stmt->close();
        }
    }

    $conn->close();
    header('Location: pelanggan.php');
    exit;
}

$pelangganList = [];
$res = $conn->query('SELECT id_pelanggan, nama_pelanggan, no_hp, alamat FROM pelanggan ORDER BY id_pelanggan DESC');
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $pelangganList[] = $row;
    }
}
$conn->close();

$pageTitle = 'Pelanggan';
$pageSubtitle = 'Data pelanggan konter Mitra Haidar Cell.';
ob_start();
?>
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
    <i class="bi bi-plus-lg"></i> Tambah
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
                        <th>Nama</th>
                        <th>No. HP</th>
                        <th>Alamat</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$pelangganList): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada pelanggan.</td></tr>
                <?php else: ?>
                    <?php foreach ($pelangganList as $p): ?>
                        <?php
                        $wa = preg_replace('/[^0-9]/', '', (string)$p['no_hp']);
                        if (str_starts_with($wa, '0')) {
                            $wa = '62' . substr($wa, 1);
                        }
                        ?>
                        <tr>
                            <td><?= (int)$p['id_pelanggan'] ?></td>
                            <td class="fw-semibold"><?= e($p['nama_pelanggan']) ?></td>
                            <td>
                                <?php if ($wa !== ''): ?>
                                    <a href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener"><?= e($p['no_hp']) ?></a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?= e((string)($p['alamat'] ?? '-')) ?></td>
                            <td>
                                <div class="actions">
                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEdit<?= (int)$p['id_pelanggan'] ?>">Edit</button>
                                    <form method="post" onsubmit="return confirm('Hapus pelanggan ini?');">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="aksi" value="hapus">
                                        <input type="hidden" name="id_pelanggan" value="<?= (int)$p['id_pelanggan'] ?>">
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
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <?= csrf_input() ?>
                <input type="hidden" name="aksi" value="tambah">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Pelanggan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input class="form-control" name="nama_pelanggan" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No. HP</label>
                        <input class="form-control" name="no_hp" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Alamat</label>
                        <textarea class="form-control" name="alamat" rows="3"></textarea>
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

<?php foreach ($pelangganList as $p): ?>
<div class="modal fade" id="modalEdit<?= (int)$p['id_pelanggan'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <?= csrf_input() ?>
                <input type="hidden" name="aksi" value="edit">
                <input type="hidden" name="id_pelanggan" value="<?= (int)$p['id_pelanggan'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Pelanggan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input class="form-control" name="nama_pelanggan" value="<?= e($p['nama_pelanggan']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No. HP</label>
                        <input class="form-control" name="no_hp" value="<?= e($p['no_hp']) ?>" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Alamat</label>
                        <textarea class="form-control" name="alamat" rows="3"><?= e((string)($p['alamat'] ?? '')) ?></textarea>
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

<?php require __DIR__ . '/_layout_end.php'; ?>
