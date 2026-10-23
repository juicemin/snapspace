<?php
require_once __DIR__ . '/includes/db.php';
$escape = static function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
$positiveId = static function ($value) {
    return is_string($value) && preg_match('/^[1-9][0-9]*$/D', $value)
        ? filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
};
$imagePath = static function ($value) {
    if (!is_string($value) || !preg_match('~^(?:uploads|assets)/[a-zA-Z0-9_./-]+\.(?:jpe?g|png|gif|webp|avif)$~iD', $value)) { return null; }
    if (strpos($value, '..') !== false || !is_file(__DIR__ . '/' . $value)) { return null; }
    return $value;
};
$error = '';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(503);
    $error = 'Albums are temporarily unavailable. The shared database connection has not been configured.';
}
$pageTitle = 'Album details';
$album = null;
$photos = [];
$id = $positiveId($_GET['id'] ?? null);
if ($id === false) { http_response_code(400); $error = 'A valid album ID is required. Choose an album from the album list.'; }
if ($error === '') {
    try {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $statement = $pdo->prepare('SELECT a.*, c.name AS creator_name FROM album a JOIN creator c ON c.idcreator = a.idcreator WHERE a.idalbum = :id');
        $statement->execute(['id' => $id]);
        $album = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$album) { http_response_code(404); $error = 'This album does not exist or has been deleted.'; }
        else {
            $pageTitle = $album['title'] ?: 'Untitled album';
            $statement = $pdo->prepare('SELECT idphoto, title, imageurl, comment FROM photo WHERE idalbum = :id ORDER BY idphoto');
            $statement->execute(['id' => $id]);
            $photos = $statement->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $exception) { http_response_code(503); $error = 'The album could not be loaded. Please try again later.'; }
}
require __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="css/album.css">
<section class="album-module">
    <a href="albums.php">Back to albums</a>
    <?php if ($error !== ''): ?><h1>Album unavailable</h1><p class="album-error" role="alert"><?= $escape($error) ?></p>
    <?php else: $cover = $imagePath($album['imageurl']); ?>
        <h1><?= $escape($pageTitle) ?></h1>
        <p>By <a href="creator-details.php?id=<?= (int) $album['idcreator'] ?>"><?= $escape($album['creator_name']) ?></a></p>
        <?php if ($cover !== null): ?><img class="album-cover" src="<?= $escape($cover) ?>" alt="<?= $escape($pageTitle) ?>" width="960" height="640"><?php endif; ?>
        <h2>Photos in this album (<?= count($photos) ?>)</h2>
        <?php if (!$photos): ?><p class="album-empty">This album has no photos yet.</p><?php else: ?><div class="album-grid">
            <?php foreach ($photos as $photo): $image = $imagePath($photo['imageurl']); ?>
                <article class="album-card">
                    <?php if ($image !== null): ?><img src="<?= $escape($image) ?>" alt="<?= $escape($photo['title']) ?>" width="640" height="480" loading="lazy"><?php else: ?><div class="album-no-image">No photo image</div><?php endif; ?>
                    <div class="album-card-content"><h3><a href="photo-details.php?id=<?= (int) $photo['idphoto'] ?>"><?= $escape($photo['title'] ?: 'Untitled photo') ?></a></h3>
                    <?php if ($photo['comment'] !== null && $photo['comment'] !== ''): ?><p><?= $escape($photo['comment']) ?></p><?php endif; ?></div>
                </article>
            <?php endforeach; ?>
        </div><?php endif; ?>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
