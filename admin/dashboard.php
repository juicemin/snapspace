<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Administration Dashboard';
$activeSection = 'dashboard';
$adminName = $_SESSION['username'] ?? 'Admin';

$counts = [
    'creators' => 0,
    'albums' => 0,
    'photos' => 0
];

try {
    $counts['creators'] = (int) $pdo
        ->query('SELECT COUNT(*) FROM creator')
        ->fetchColumn();

    $counts['albums'] = (int) $pdo
        ->query('SELECT COUNT(*) FROM album')
        ->fetchColumn();

    $counts['photos'] = (int) $pdo
        ->query('SELECT COUNT(*) FROM photo')
        ->fetchColumn();
} catch (PDOException $exception) {
    $databaseError = 'The dashboard statistics could not be loaded.';
}

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-page-heading">
    <div>
        <h1>Administration Dashboard</h1>

        <p>
            Manage the records available on Snap Space.
        </p>
    </div>
</div>

<?php if (isset($databaseError)): ?>
    <div class="admin-card">
        <p><?= htmlspecialchars($databaseError) ?></p>
    </div>
<?php endif; ?>

<section class="admin-card-grid" aria-label="Record management">

    <article class="admin-card">
        <span class="admin-record-count">
            <?= $counts['creators'] ?>
        </span>

        <h2>Creators</h2>

        <p>
            Manage creator profiles and their information.
        </p>

        <a
            class="admin-button"
            href="/snapspace/admin/manage-creators.php"
        >
            Manage Creators
        </a>
    </article>

    <article class="admin-card">
        <span class="admin-record-count">
            <?= $counts['albums'] ?>
        </span>

        <h2>Albums</h2>

        <p>
            Manage albums and their associated creators.
        </p>

        <a
            class="admin-button"
            href="/snapspace/admin/manage-albums.php"
        >
            Manage Albums
        </a>
    </article>

    <article class="admin-card">
        <span class="admin-record-count">
            <?= $counts['photos'] ?>
        </span>

        <h2>Photos</h2>

        <p>
            Manage photographs and their album relationships.
        </p>

        <a
            class="admin-button"
            href="/snapspace/admin/manage-photos.php"
        >
            Manage Photos
        </a>
    </article>

</section>

<section class="admin-section">
    <h2 class="admin-section-title">Quick Actions</h2>

    <div class="admin-quick-actions">
        <a
            class="admin-button"
            href="/snapspace/admin/add-creator.php"
        >
            + Add Creator
        </a>

        <a
            class="admin-button"
            href="/snapspace/admin/add-album.php"
        >
            + Add Album
        </a>

        <a
            class="admin-button"
            href="/snapspace/admin/add-photo.php"
        >
            + Add Photo
        </a>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>