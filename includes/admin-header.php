<?php
$pageTitle = $pageTitle ?? 'Administration';
$adminName = $adminName ?? 'Admin';
$baseUrl = '/snapspace';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?= htmlspecialchars($pageTitle) ?> | Snap Space</title>

    <link
        rel="stylesheet"
        href="<?= $baseUrl ?>/css/admin.css"
    >
</head>

<body class="admin-body">

<div class="admin-layout">

    <?php require __DIR__ . '/admin-sidebar.php'; ?>

    <div class="admin-page">

        <header class="admin-topbar">
            <div>
                <p class="admin-topbar-label">
                    Snap Space Administration
                </p>

                <p class="admin-welcome">
                    Welcome Back, <?= htmlspecialchars($adminName) ?>
                </p>
            </div>
        </header>

        <main class="admin-content">