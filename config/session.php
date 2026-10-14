<?php
// ============================================
// File: /config/session.php
// Tujuan: Session helper untuk autentikasi
// ============================================

require_once __DIR__ . '/koneksi.php';

// Mulai session di setiap halaman yang butuh proteksi
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function redirectToLogin(): void
{
    header('Location: ../auth/login.php');
    exit;
}

// Cek apakah user sudah login
function require_login(): void
{
    if (!isset($_SESSION['id_user'])) {
        redirectToLogin();
    }
}

// Cek apakah role user termasuk yang diizinkan
function require_role(array $roles): void
{
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $roles, true)) {
        // Untuk pemula: jika tidak punya akses, redirect ke dashboard.
        header('Location: ../pages/dashboard.php');
        exit;
    }
}

// Helper untuk menghindari XSS
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Ambil koneksi database (dibuat per request)
function db(): mysqli
{
    return getMysqliConnection();
}

// ============================================================
// CSRF Protection sederhana
// ============================================================

/**
 * Menghasilkan dan menyimpan token CSRF di session.
 */
function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Helper untuk ditaruh di dalam <form> (hidden input).
 */
function csrf_input(): string
{
    $token = csrf_token();
    return '<input type="hidden" name="_token" value="' . e($token) . '">';
}

/**
 * Validasi token CSRF pada request POST.
 */
function verify_csrf(): bool
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true;
    }

    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $token = $_POST['_token'] ?? '';

    if (!is_string($sessionToken) || !is_string($token) || $sessionToken === '' || $token === '') {
        return false;
    }

    return hash_equals($sessionToken, $token);
}

// ============================================================
// Transaksi (status & badge — selaras ENUM di database)
// ============================================================

/** @return string[] */
function transaksi_status_enum(): array
{
    return ['pending', 'proses', 'selesai', 'diambil'];
}

/** @return string[] */
function transaksi_metode_enum(): array
{
    return ['cash', 'transfer', 'e-wallet'];
}

function transaksi_status_badge_class(string $status): string
{
    switch ($status) {
        case 'selesai':
            return 'badge-status badge-status-selesai';
        case 'diambil':
            return 'badge-status badge-status-diambil';
        case 'proses':
            return 'badge-status badge-status-proses';
        case 'pending':
            return 'badge-status badge-status-pending';
        default:
            return 'badge-status badge-status-default';
    }
}

function rupiah(float $amount): string
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function set_flash(string $type, string $message): void
{
    $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['_flash']) || !is_array($_SESSION['_flash'])) {
        return null;
    }

    $flash = $_SESSION['_flash'];
    unset($_SESSION['_flash']);

    return $flash;
}

function render_flash(): void
{
    $flash = get_flash();
    if ($flash === null) {
        return;
    }

    $type = $flash['type'] ?? 'info';
    $message = (string)($flash['message'] ?? '');
    if ($message === '') {
        return;
    }

    $class = $type === 'success' ? 'alert-success' : ($type === 'danger' ? 'alert-danger' : 'alert-info');
    $icon = $type === 'success' ? 'bi-check-circle-fill' : ($type === 'danger' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill');

    echo '<div class="alert ' . e($class) . ' alert-dismissible fade show app-alert" role="alert">';
    echo '<i class="bi ' . e($icon) . '"></i>';
    echo '<span>' . e($message) . '</span>';
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>';
    echo '</div>';
}

