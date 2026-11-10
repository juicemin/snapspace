<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pageTitle = $pageTitle ?? 'Snap Space';
$currentPage = basename($_SERVER['PHP_SELF']);
$baseUrl = '/snapspace';

$navigation = [
    'index.php' => 'Home',
    'creators.php' => 'Creators',
    'albums.php' => 'Albums',
    'photos.php' => 'Photos',
    'search.php' => 'Search'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= htmlspecialchars(
            $pageTitle,
            ENT_QUOTES,
            'UTF-8'
        ) ?> | Snap Space
    </title>

    <link
        rel="stylesheet"
        href="<?= $baseUrl ?>/css/style.css"
    >
</head>

<body>

<header class="site-header">
    <div class="container header-content">

        <a
            class="site-logo"
            href="<?= $baseUrl ?>/index.php"
        >
            Snap Space
        </a>

        <nav
            class="main-navigation"
            aria-label="Main navigation"
        >
            <?php foreach ($navigation as $file => $label): ?>
                <a
                    href="<?= $baseUrl ?>/<?= $file ?>"
                    class="<?= $currentPage === $file
                        ? 'active'
                        : '' ?>"
                >
                    <?= htmlspecialchars($label) ?>
                </a>
            <?php endforeach; ?>

            <?php if (empty($_SESSION['user_id'])): ?>

                <a
                    href="<?= $baseUrl ?>/login.php"
                    class="<?= $currentPage === 'login.php'
                        ? 'active'
                        : '' ?>"
                >
                    Login
                </a>

            <?php else: ?>

                <?php if (
                    ($_SESSION['role'] ?? '') === 'admin'
                ): ?>
                    <a
                        href="<?= $baseUrl ?>/admin/dashboard.php"
                    >
                        Dashboard
                    </a>
                <?php endif; ?>

                <span class="navigation-user">
                    <?= htmlspecialchars(
                        $_SESSION['username'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>

                <a href="<?= $baseUrl ?>/logout.php">
                    Logout
                </a>

            <?php endif; ?>
        </nav>

    </div>
</header>

<main class="site-main">