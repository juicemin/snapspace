<?php
$baseUrl = $baseUrl ?? '/snapspace';
$activeSection = $activeSection ?? '';
?>

<aside class="admin-sidebar">
    <div class="admin-brand">
        <a href="<?= $baseUrl ?>/admin/dashboard.php">
            Snap Space
        </a>

        <span>Administration</span>
    </div>

    <nav class="admin-navigation" aria-label="Administration navigation">

        <a
            href="<?= $baseUrl ?>/admin/dashboard.php"
            class="<?= $activeSection === 'dashboard' ? 'active' : '' ?>"
        >
            Dashboard
        </a>

        <a
            href="<?= $baseUrl ?>/admin/manage-creators.php"
            class="<?= $activeSection === 'creators' ? 'active' : '' ?>"
        >
            Manage Creators
        </a>

        <a
            href="<?= $baseUrl ?>/admin/manage-albums.php"
            class="<?= $activeSection === 'albums' ? 'active' : '' ?>"
        >
            Manage Albums
        </a>

        <a
            href="<?= $baseUrl ?>/admin/manage-photos.php"
            class="<?= $activeSection === 'photos' ? 'active' : '' ?>"
        >
            Manage Photos
        </a>

    </nav>

    <div class="admin-sidebar-footer">
        <a href="<?= $baseUrl ?>/index.php">
            View Website
        </a>

        <a href="<?= $baseUrl ?>/admin/logout.php">
            Log Out
        </a>
    </div>
</aside>