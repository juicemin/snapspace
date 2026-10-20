<?php
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($pageTitle) ?> | Snap Space</title>

    <link rel="stylesheet" href="<?= $baseUrl ?>/css/style.css">
</head>

<body>
<header class="site-header">
    <div class="container header-content">

        <a class="site-logo" href="<?= $baseUrl ?>/index.php">
            Snap Space
        </a>

        <nav class="main-navigation" aria-label="Main navigation">
            <?php foreach ($navigation as $file => $label): ?>
                <a
                    href="<?= $baseUrl ?>/<?= $file ?>"
                    class="<?= $currentPage === $file ? 'active' : '' ?>"
                >
                    <?= $label ?>
                </a>
            <?php endforeach; ?>
        </nav>

    </div>
</header>

<main class="site-main">