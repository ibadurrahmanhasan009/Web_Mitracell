<header class="app-topbar">
    <button class="btn btn-link text-dark d-md-none p-0 me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-label="Buka menu">
        <i class="bi bi-list fs-3"></i>
    </button>

    <a class="app-brand" href="dashboard.php">
        <span>
            Mitra Haidar Cell
            <small>Sistem Konter</small>
        </span>
    </a>

    <div class="app-user">
        <div class="app-user-meta d-none d-sm-block">
            <strong><?= e((string)($_SESSION['nama_user'] ?? 'Admin')) ?></strong>
            <span><?= e((string)($_SESSION['role'] ?? 'petugas')) ?></span>
        </div>
        <a class="btn btn-outline-danger btn-sm" href="../auth/logout.php">Keluar</a>
    </div>
</header>
