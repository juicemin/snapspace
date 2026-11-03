<?php
require_once __DIR__ . '/../includes/auth.php';
// Keep a fail-closed guard until the shared authentication contract is finalized.
// TODO: Review duplicate checks and align unauthenticated redirects with shared auth.
if (session_status() !== PHP_SESSION_ACTIVE) {
    http_response_code(503);
    exit('Album administration is unavailable until shared authentication starts the session.');
}
$sessionAdminId = $_SESSION['admin_id'] ?? null;
if ((!is_int($sessionAdminId) && !is_string($sessionAdminId))
    || filter_var($sessionAdminId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
    http_response_code(403);
    exit('Administrator authentication is required.');
}
require_once __DIR__ . '/../includes/db.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(503);
    exit('Album administration is unavailable until the shared PDO database connection is configured.');
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$escape = static function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
$positiveId = static function ($value) {
    return is_string($value) && preg_match('/^[1-9][0-9]*$/D', $value)
        ? filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
};
if (empty($_SESSION['album_csrf_token'])) { $_SESSION['album_csrf_token'] = bin2hex(random_bytes(32)); }
$validToken = static function () {
    return isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
        && hash_equals($_SESSION['album_csrf_token'], $_POST['csrf_token']);
};
$activeSection = 'albums';
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$pageTitle = 'Manage Albums';
$albums = [];
$error = '';
try {
    $statement = $pdo->prepare('SELECT a.idalbum, a.title, c.name AS creator_name, (SELECT COUNT(*) FROM photo p WHERE p.idalbum = a.idalbum) AS photo_count FROM album a JOIN creator c ON c.idcreator = a.idcreator ORDER BY a.title, a.idalbum');
    $statement->execute();
    $albums = $statement->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $exception) { http_response_code(503); $error = 'Albums could not be loaded. Please try again later.'; }
require __DIR__ . '/../includes/admin-header.php';
?>
<link rel="stylesheet" href="../css/album.css">
<section class="album-module album-admin">
    <div class="album-heading"><h1>Manage albums</h1><a class="album-button" href="add-album.php">Add album</a></div>
    <p>Deleting an album also permanently deletes all associated photo records. Image files are not deleted.</p>
    <?php if ($error !== ''): ?><p class="album-error" role="alert"><?= $escape($error) ?></p>
    <?php elseif (!$albums): ?><p class="album-empty">No albums yet. Add an album to get started.</p>
    <?php else: ?><div class="admin-table-wrapper" tabindex="0" role="region" aria-label="Album management table"><table class="admin-table">
        <thead><tr><th scope="col">Title</th><th scope="col">Creator</th><th scope="col">Photos</th><th scope="col">Actions</th></tr></thead>
        <tbody><?php foreach ($albums as $album): ?><tr>
            <td><a href="../album-details.php?id=<?= (int) $album['idalbum'] ?>"><?= $escape($album['title'] ?: 'Untitled album') ?></a></td>
            <td><?= $escape($album['creator_name']) ?></td><td><?= (int) $album['photo_count'] ?></td>
            <td><div class="album-actions"><a href="edit-album.php?id=<?= (int) $album['idalbum'] ?>">Edit<span class="album-sr-only"> <?= $escape($album['title']) ?></span></a>
                <form method="post" action="delete-album.php" data-confirm-delete data-record-name="<?= $escape(($album['title'] ?: 'this album') . ' and all its associated photos') ?>">
                    <input type="hidden" name="id" value="<?= (int) $album['idalbum'] ?>"><input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['album_csrf_token']) ?>">
                    <button class="album-button album-danger" type="submit">Delete<span class="album-sr-only"> <?= $escape($album['title']) ?> and its photos</span></button>
                </form>
            </div></td>
        </tr><?php endforeach; ?></tbody>
    </table></div><?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
