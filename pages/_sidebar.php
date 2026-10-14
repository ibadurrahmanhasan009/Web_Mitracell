<?php
$active = basename($_SERVER['PHP_SELF'] ?? '');

$menu = [
    ['file' => 'dashboard.php', 'label' => 'Dashboard', 'icon' => 'bi-speedometer2'],
    ['file' => 'pelanggan.php', 'label' => 'Pelanggan', 'icon' => 'bi-people'],
    ['file' => 'layanan.php', 'label' => 'Layanan', 'icon' => 'bi-grid'],
    ['file' => 'transaksi.php', 'label' => 'Transaksi', 'icon' => 'bi-receipt'],
    ['file' => 'laporan_bulanan.php', 'label' => 'Laporan', 'icon' => 'bi-bar-chart'],
];
?>

<nav class="app-sidebar d-none d-md-flex" aria-label="Menu utama">
    <div class="app-sidebar-label">Menu</div>
    <?php foreach ($menu as $item): ?>
        <a class="app-nav-link <?= $active === $item['file'] ? 'active' : '' ?>" href="<?= e($item['file']) ?>">
            <i class="bi <?= e($item['icon']) ?>"></i>
            <span><?= e($item['label']) ?></span>
        </a>
    <?php endforeach; ?>
    <div class="app-sidebar-foot">
        <?= e((string)($_SESSION['nama_user'] ?? 'Pengguna')) ?>
    </div>
</nav>

<div class="offcanvas offcanvas-start offcanvas-nav d-md-none" tabindex="-1" id="sidebarOffcanvas">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Menu</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
    </div>
    <div class="offcanvas-body">
        <?php foreach ($menu as $item): ?>
            <a class="app-nav-link <?= $active === $item['file'] ? 'active' : '' ?>" href="<?= e($item['file']) ?>">
                <i class="bi <?= e($item['icon']) ?>"></i>
                <span><?= e($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>
