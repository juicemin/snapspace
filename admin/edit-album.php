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
$editing = true;
$pageTitle = $editing ? 'Edit Album' : 'Add Album';
$errors = [];
$creators = [];
$title = '';
$imageurl = '';
$creatorId = false;
$available = true;
$id = $editing ? $positiveId($_GET['id'] ?? null) : null;
if ($editing && $id === false) { http_response_code(400); $errors[] = 'A valid album ID is required.'; $available = false; }
try {
    if ($available && $editing) {
        $statement = $pdo->prepare('SELECT * FROM album WHERE idalbum = :id');
        $statement->execute(['id' => $id]);
        $album = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$album) { http_response_code(404); $errors[] = 'This album does not exist or has been deleted.'; $available = false; }
        else { $title = (string) $album['title']; $imageurl = (string) $album['imageurl']; $creatorId = (int) $album['idcreator']; }
    }
    if ($available) {
        $statement = $pdo->prepare('SELECT idcreator, name FROM creator ORDER BY name, idcreator');
        $statement->execute();
        $creators = $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    if ($available && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = is_string($_POST['title'] ?? null) ? trim($_POST['title']) : '';
        $imageurl = is_string($_POST['imageurl'] ?? null) ? trim($_POST['imageurl']) : '';
        $creatorId = $positiveId($_POST['idcreator'] ?? null);
        if (!$validToken()) { $errors[] = 'The form expired or could not be verified. Please submit it again.'; }
        $titleLength = preg_match_all('/./us', $title, $characters);
        if ($title === '' || $titleLength === false || $titleLength > 45) { $errors[] = 'Enter an album title of 1 to 45 characters.'; }
        if (isset($_POST['imageurl']) && !is_string($_POST['imageurl'])) { $errors[] = 'Enter a valid relative image path.'; }
        if ($imageurl !== '') {
            // Only existing local raster images are accepted; URLs and traversal paths are rejected.
            $safeImage = strlen($imageurl) <= 100
                && preg_match('~^(?:uploads|assets)/[a-zA-Z0-9_./-]+\.(?:jpe?g|png|gif|webp|avif)$~iD', $imageurl)
                && strpos($imageurl, '..') === false;
            if (!$safeImage || !is_file(__DIR__ . '/../' . $imageurl)) {
                $errors[] = 'Use an existing image path relative to the project, under uploads/ or assets/ (maximum 100 characters), or leave it blank.';
            }
        }
        if ($creatorId === false || !in_array((int) $creatorId, array_map('intval', array_column($creators, 'idcreator')), true)) { $errors[] = 'Select an existing creator.'; }
        if (!$errors) {
            $pdo->beginTransaction();
            if ($editing) {
                $statement = $pdo->prepare('SELECT idcreator FROM album WHERE idalbum = :id FOR UPDATE');
                $statement->execute(['id' => $id]);
                $current = $statement->fetch(PDO::FETCH_ASSOC);
                if (!$current) { http_response_code(404); $errors[] = 'This album was deleted before your changes could be saved.'; $available = false; }
                elseif ((int) $current['idcreator'] !== (int) $creatorId) {
                    $statement = $pdo->prepare('SELECT idphoto, idcreator FROM photo WHERE idalbum = :id FOR UPDATE');
                    $statement->execute(['id' => $id]);
                    foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $photo) {
                        if ($photo['idcreator'] === null || (int) $photo['idcreator'] !== (int) $creatorId) {
                            $errors[] = 'The creator cannot be changed because existing photos would belong to a different creator. Resolve the photo relationships in the Photo module first. No photo records have been changed.';
                            break;
                        }
                    }
                }
            }
            $statement = $pdo->prepare('SELECT idcreator FROM creator WHERE idcreator = :creator FOR UPDATE');
            $statement->execute(['creator' => $creatorId]);
            if (!$statement->fetch(PDO::FETCH_ASSOC)) { $errors[] = 'The selected creator no longer exists. Choose another creator.'; }
            if ($errors) { $pdo->rollBack(); }
            else {
                $parameters = ['title' => $title, 'image' => $imageurl === '' ? null : $imageurl, 'creator' => $creatorId];
                if ($editing) {
                    $parameters['id'] = $id;
                    $statement = $pdo->prepare('UPDATE album SET title = :title, imageurl = :image, idcreator = :creator WHERE idalbum = :id');
                } else { $statement = $pdo->prepare('INSERT INTO album (title, imageurl, idcreator) VALUES (:title, :image, :creator)'); }
                $statement->execute($parameters);
                $pdo->commit();
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => $editing ? 'Album updated successfully.' : 'Album added successfully.'];
                header('Location: /snapspace/admin/manage-albums.php', true, 303);
                exit;
            }
        }
        if ($errors && $available) { http_response_code(422); }
    }
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(503);
    $errors[] = 'The album could not be loaded or saved. Please try again. No partial changes were saved.';
    $available = false;
}
require __DIR__ . '/../includes/admin-header.php';
?>
<link rel="stylesheet" href="../css/album.css">
<section class="album-module album-admin">
    <h1><?= $escape($pageTitle) ?></h1><p><a href="manage-albums.php">Back to album management</a></p>
    <?php if ($errors): ?><div class="album-error" role="alert"><h2>Please check the following</h2><ul><?php foreach ($errors as $message): ?><li><?= $escape($message) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <?php if ($available && !$creators): ?><p class="album-empty">No creators are available. Add a creator in the Creator module before saving an album.</p><?php endif; ?>
    <?php if ($available): ?>
    <form method="post" action="<?= $editing ? 'edit-album.php?id=' . (int) $id : 'add-album.php' ?>" class="album-form">
        <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['album_csrf_token']) ?>">
        <div><label for="title">Album title (required)</label><input id="title" name="title" maxlength="45" required value="<?= $escape($title) ?>"></div>
        <div><label for="idcreator">Creator (required)</label><select id="idcreator" name="idcreator" required><option value="">Choose a creator</option>
            <?php foreach ($creators as $creator): ?><option value="<?= (int) $creator['idcreator'] ?>" <?= (int) $creatorId === (int) $creator['idcreator'] ? 'selected' : '' ?>><?= $escape($creator['name']) ?></option><?php endforeach; ?>
        </select><?php if ($editing): ?><p class="album-help">Changing the creator is blocked if any existing photo would have a different creator.</p><?php endif; ?></div>
        <div><label for="imageurl">Cover image path (optional)</label><input id="imageurl" name="imageurl" maxlength="100" value="<?= $escape($imageurl) ?>" aria-describedby="image-help"><p id="image-help" class="album-help">Use an existing file such as uploads/albums/cover.jpg. Leave blank for no cover. This form does not upload files.</p></div>
        <div class="album-actions"><button class="album-button" type="submit" <?= !$creators ? 'disabled' : '' ?>><?= $editing ? 'Save changes' : 'Add album' ?></button><a href="manage-albums.php">Cancel</a></div>
    </form>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
