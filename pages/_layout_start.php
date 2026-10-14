<?php
$pageTitle = $pageTitle ?? 'Mitra Haidar Cell';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> - Mitra Haidar Cell</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
    <?php if (!empty($extraHead)) echo $extraHead; ?>
</head>
<body class="app-page">
<?php require __DIR__ . '/_navbar.php'; ?>
<div class="app-shell">
    <?php require __DIR__ . '/_sidebar.php'; ?>
    <main class="app-main">
        <div class="page-head">
            <div>
                <h1><?= e($pageTitle) ?></h1>
                <?php if (!empty($pageSubtitle)): ?>
                    <p><?= e($pageSubtitle) ?></p>
                <?php endif; ?>
            </div>
            <?php if (!empty($pageActions)) echo $pageActions; ?>
        </div>
        <?php render_flash(); ?>
