<?php
require_once __DIR__ . '/../config/session.php';

if (isset($_SESSION['id_user'])) {
    header('Location: ../pages/dashboard.php');
    exit;
}

$error = '';
$logoPath = __DIR__ . '/../img/MY LOGO.png';
$hasLogo = is_file($logoPath);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Permintaan tidak valid. Silakan coba lagi.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $error = 'Username dan password wajib diisi.';
        } else {
            $conn = db();
            $stmt = $conn->prepare('SELECT id_user, username, password, nama_user, role FROM `user` WHERE username = ? LIMIT 1');
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$user) {
                $error = 'Username atau password salah.';
            } else {
                $storedPassword = trim((string)$user['password']);
                $isValid = false;

                if ($storedPassword !== '' && password_verify($password, $storedPassword)) {
                    $isValid = true;
                } elseif (hash_equals($password, $storedPassword)) {
                    $isValid = true;
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    if ($newHash !== false) {
                        $update = $conn->prepare('UPDATE `user` SET password = ? WHERE id_user = ?');
                        $idUser = (int)$user['id_user'];
                        $update->bind_param('si', $newHash, $idUser);
                        $update->execute();
                        $update->close();
                    }
                }

                if (!$isValid) {
                    $error = 'Username atau password salah.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['id_user'] = (int)$user['id_user'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['nama_user'] = $user['nama_user'];
                    $_SESSION['role'] = $user['role'];
                    header('Location: ../pages/dashboard.php');
                    $conn->close();
                    exit;
                }
            }

            $conn->close();
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - Mitra Haidar Cell</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="login-page">
    <div class="login-card">
        <div class="login-brand">
            <?php if ($hasLogo): ?>
                <img src="../img/MY%20LOGO.png" alt="Mitra Haidar Cell" class="login-logo">
            <?php else: ?>
                <div class="login-mark"><i class="bi bi-phone"></i></div>
            <?php endif; ?>
            <h1>Mitra Haidar Cell</h1>
            <p>Sistem informasi konter pulsa</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger app-alert mb-3">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="post">
            <?= csrf_input() ?>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input class="form-control" name="username" required autocomplete="username" placeholder="admin">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input class="form-control" name="password" type="password" required autocomplete="current-password" placeholder="••••••••">
                </div>
            </div>
            <button class="btn btn-login" type="submit">Masuk</button>
        </form>
    </div>
</body>
</html>
