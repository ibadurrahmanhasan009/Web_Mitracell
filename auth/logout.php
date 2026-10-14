<?php
// ============================================
// File: /auth/logout.php
// Tujuan: Logout user dengan menghapus session
// ============================================

require_once __DIR__ . '/../config/session.php';

// Hapus semua data session
$_SESSION = [];

// Jika session memakai cookies, hapus cookie session juga
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
}

session_destroy();

header('Location: login.php');
exit;

