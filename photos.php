<?php
// photos.php - Snap Space Photo Gallery
$pageTitle = "Photo Gallery - Snap Space";
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

$albumList = [];
if (isset($pdo)) {
    try {
        $albumStmt = $pdo->query("SELECT idalbum, title FROM album ORDER BY title ASC");
        $albumList = $albumStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $albumList = [];
    }
}

$selectedAlbum = isset($_GET['album']) ? trim($_GET['album']) : '';
$selectedSort = isset($_GET['sort']) ? trim($_GET['sort']) : 'latest';

$sql = "SELECT p.idphoto, p.title AS photo_title, p.imageurl, 
               c.name AS creator_name, c.idcreator,
               a.title AS album_title, a.idalbum
        FROM photo p
        LEFT JOIN creator c ON p.idcreator = c.idcreator
        LEFT JOIN album a ON p.idalbum = a.idalbum
        WHERE 1=1";

$params = [];

if (!empty($selectedAlbum) && is_numeric($selectedAlbum)) {
    $sql .= " AND p.idalbum = :idalbum";
    $params[':idalbum'] = $selectedAlbum;
}

if ($selectedSort === 'title_asc') {
    $sql .= " ORDER BY p.title ASC";
} elseif ($selectedSort === 'title_desc') {
    $sql .= " ORDER BY p.title DESC";
} else {
    $sql .= " ORDER BY p.idphoto DESC";
}

$photos = [];
if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $photos = [];
    }
}
?>

<link rel="stylesheet" href="css/photo.css">

<main class="gallery-container">
    <header class="gallery-header">
        <h1>Photo Gallery</h1>
        <p class="subtitle">Browse curated photo collections and explore featured shots from our creative community.</p>

        <form method="GET" action="photos.php" class="filter-sort-form">
            <div class="form-group">
                <label for="album">Filter by Album:</label>
                <select name="album" id="album" onchange="this.form.submit()">
                    <option value="">All Albums</option>
                    <?php foreach ($albumList as $album): ?>
                        <option value="<?php echo htmlspecialchars($album['idalbum']); ?>" 
                            <?php echo ($selectedAlbum == $album['idalbum']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($album['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="sort">Sort by:</label>
                <select name="sort" id="sort" onchange="this.form.submit()">
                    <option value="latest" <?php echo ($selectedSort === 'latest') ? 'selected' : ''; ?>>Latest Added</option>
                    <option value="title_asc" <?php echo ($selectedSort === 'title_asc') ? 'selected' : ''; ?>>Title (A-Z)</option>
                    <option value="title_desc" <?php echo ($selectedSort === 'title_desc') ? 'selected' : ''; ?>>Title (Z-A)</option>
                </select>
            </div>
            <noscript>
                <button type="submit" class="btn btn-secondary">Apply</button>
            </noscript>
        </form>
    </header>

    <section class="photo-grid">
        <?php if (!empty($photos)): ?>
            <?php foreach ($photos as $photo): ?>
                <?php 
                    $imgSrc = !empty($photo['imageurl']) ? htmlspecialchars($photo['imageurl']) : 'assets/images/image-placeholder.png';
                ?>
                <div class="photo-card">
                    <div class="photo-thumb-wrap">
                        <img src="<?php echo $imgSrc; ?>" 
                             alt="<?php echo htmlspecialchars($photo['photo_title']); ?>" 
                             class="photo-thumbnail"
                             onerror="this.onerror=null; this.src='assets/images/image-placeholder.png';">
                    </div>
                    <div class="photo-info">
                        <h3 class="photo-title"><?php echo htmlspecialchars($photo['photo_title']); ?></h3>
                        <p class="creator-name">
                            By <?php echo htmlspecialchars($photo['creator_name'] ?? 'Unknown Creator'); ?>
                        </p>
                        <p class="album-name">
                            Album: <?php echo htmlspecialchars($photo['album_title'] ?? 'Standalone'); ?>
                        </p>
                        <a href="photo-details.php?id=<?php echo htmlspecialchars($photo['idphoto']); ?>" class="btn-view-details">
                            View Details
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-records">
                <p>No photographs found in this collection.</p>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>