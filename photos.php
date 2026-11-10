<?php
// photos.php - Snap Space Photo Gallery
$pageTitle = "Photo Gallery";
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
$query = isset($_GET['q']) && is_string($_GET['q'])
    ? trim($_GET['q'])
    : '';

$sql = "SELECT p.idphoto, p.title AS photo_title, p.imageurl, 
               c.name AS creator_name, c.idcreator,
               a.title AS album_title, a.idalbum
        FROM photo p
        LEFT JOIN creator c ON p.idcreator = c.idcreator
        LEFT JOIN album a ON p.idalbum = a.idalbum
        WHERE 1=1";

$params = [];

if ($query !== '' && strlen($query) <= 100) {
    $escapedQuery = strtr(
        $query,
        [
            '!' => '!!',
            '%' => '!%',
            '_' => '!_'
        ]
    );

    $sql .= " AND p.title LIKE :photo_title ESCAPE '!'";

    $params[':photo_title'] =
        '%' . $escapedQuery . '%';
}

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

<section class="gallery-container">
    <header class="gallery-header">
        <h1>Photo Gallery</h1>
        <p class="subtitle">Browse curated photo collections and explore featured shots from our creative community.</p>

        <form
            method="get"
            action="photos.php"
            class="filter-sort-form"
        >
            <div class="form-group search-input-wrapper">

                <label for="photo-search">
                    Search photo titles:
                </label>

                <input
                    class="form-control"
                    type="search"
                    id="photo-search"
                    name="q"
                    maxlength="100"
                    value="<?= htmlspecialchars(
                        $query,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    placeholder="Search photo titles..."
                    autocomplete="off"
                    aria-autocomplete="list"
                    aria-controls="photo-search-suggestions"
                    data-search-suggestions
                    data-suggestion-type="photo"
                >

                <div
                    class="search-suggestions"
                    id="photo-search-suggestions"
                    role="listbox"
                    aria-live="polite"
                    hidden
                ></div>

            </div>

            <div class="form-group">
                <label for="album">
                    Filter by Album:
                </label>

                <select name="album" id="album">
                    <option value="">All Albums</option>

                    <?php foreach ($albumList as $album): ?>
                        <option
                            value="<?= (int) $album['idalbum'] ?>"
                            <?= (string) $selectedAlbum ===
                                (string) $album['idalbum']
                                    ? 'selected'
                                    : '' ?>
                        >
                            <?= htmlspecialchars(
                                $album['title'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="sort">
                    Sort by:
                </label>

                <select name="sort" id="sort">
                    <option
                        value="latest"
                        <?= $selectedSort === 'latest'
                            ? 'selected'
                            : '' ?>
                    >
                        Latest Added
                    </option>

                    <option
                        value="title_asc"
                        <?= $selectedSort === 'title_asc'
                            ? 'selected'
                            : '' ?>
                    >
                        Title (A–Z)
                    </option>

                    <option
                        value="title_desc"
                        <?= $selectedSort === 'title_desc'
                            ? 'selected'
                            : '' ?>
                    >
                        Title (Z–A)
                    </option>
                </select>
            </div>

            <button
                type="submit"
                class="btn btn-secondary"
            >
                Apply Filters
            </button>

            <a href="photos.php" class="btn btn-secondary">
                Clear Filters
            </a>
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
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>