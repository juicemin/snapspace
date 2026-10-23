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
$pageTitle = 'Albums';
$albums = [];
$creators = [];
$query = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$creatorId = isset($_GET['creator']) && $_GET['creator'] !== '' ? $positiveId($_GET['creator']) : null;
if ((isset($_GET['q']) && !is_string($_GET['q'])) || strlen($query) > 500 || $creatorId === false) {
    http_response_code(400);
    $error = 'Enter a valid title search and select a valid creator.';
}
if ($error === '') {
    try {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $statement = $pdo->prepare('SELECT idcreator, name FROM creator ORDER BY name, idcreator');
        $statement->execute();
        $creators = $statement->fetchAll(PDO::FETCH_ASSOC);
        if ($creatorId !== null && !in_array((int) $creatorId, array_map('intval', array_column($creators, 'idcreator')), true)) {
            http_response_code(400);
            $error = 'The selected creator does not exist. Choose another creator.';
        } else {
            $sql = 'SELECT a.*, c.name AS creator_name, (SELECT COUNT(*) FROM photo p WHERE p.idalbum = a.idalbum) AS photo_count FROM album a JOIN creator c ON c.idcreator = a.idcreator WHERE 1=1';
            $parameters = [];
            if ($query !== '') {
                $sql .= " AND a.title LIKE :title ESCAPE '!'";
                $parameters['title'] = '%' . strtr($query, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
            }
            if ($creatorId !== null) { $sql .= ' AND a.idcreator = :creator'; $parameters['creator'] = $creatorId; }
            $statement = $pdo->prepare($sql . ' ORDER BY a.title, a.idalbum');
            $statement->execute($parameters);
            $albums = $statement->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $exception) {
        http_response_code(503);
        $error = 'Albums could not be loaded. Please try again later.';
    }
}
require __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="css/album.css">
<section class="album-module">
    <h1>Albums</h1>
    <p>Explore photo collections and the creators behind them.</p>
    <form method="get" action="albums.php" class="album-filters">
        <div><label for="q">Search album titles</label><input id="q" name="q" type="search" maxlength="125" value="<?= $escape($query) ?>"></div>
        <div><label for="creator">Creator</label><select id="creator" name="creator"><option value="">All creators</option>
            <?php foreach ($creators as $creator): ?><option value="<?= (int) $creator['idcreator'] ?>" <?= (int) $creatorId === (int) $creator['idcreator'] ? 'selected' : '' ?>><?= $escape($creator['name']) ?></option><?php endforeach; ?>
        </select></div>
        <button class="album-button" type="submit">Search albums</button><a href="albums.php">Clear filters</a>
    </form>
    <?php if ($error !== ''): ?><p class="album-error" role="alert"><?= $escape($error) ?></p>
    <?php elseif (!$albums): ?><p class="album-empty">No albums found. Try another title or choose a different creator.</p>
    <?php else: ?><p><?= count($albums) ?> album(s) found</p><div class="album-grid">
        <?php foreach ($albums as $album): $image = $imagePath($album['imageurl']); ?>
        <article class="album-card">
            <?php if ($image !== null): ?><img src="<?= $escape($image) ?>" alt="<?= $escape($album['title']) ?>" width="640" height="480" loading="lazy"><?php else: ?><div class="album-no-image">No cover image</div><?php endif; ?>
            <div class="album-card-content"><h2><a href="album-details.php?id=<?= (int) $album['idalbum'] ?>"><?= $escape($album['title'] ?: 'Untitled album') ?></a></h2>
            <p>By <a href="creator-details.php?id=<?= (int) $album['idcreator'] ?>"><?= $escape($album['creator_name']) ?></a></p><p><?= (int) $album['photo_count'] ?> photo(s)</p></div>
        </article>
        <?php endforeach; ?>
    </div><?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
