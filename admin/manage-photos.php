<?php
// admin/manage-photos.php - Photo Management Table for Administrators
$pageTitle = "Manage Photos - Snap Space";
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/admin-header.php';

// 1. Fetch creators and albums for search filter dropdowns
$creators = [];
$albums = [];

if (isset($pdo)) {
    try {
        $creators = $pdo->query("SELECT idcreator, name FROM creator ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $albums = $pdo->query("SELECT idalbum, title FROM album ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $creators = [];
        $albums = [];
    }
}

// 2. Capture and sanitize search/filter parameters
$searchKeyword = isset($_GET['search']) ? trim($_GET['search']) : '';
$selectedCreator = isset($_GET['creator']) ? trim($_GET['creator']) : '';
$selectedAlbum = isset($_GET['album']) ? trim($_GET['album']) : '';

// 3. Build dynamic query for photos
$sql = "SELECT p.idphoto, p.title AS photo_title, p.imageurl, p.comment,
               c.idcreator, c.name AS creator_name,
               a.idalbum, a.title AS album_title
        FROM photo p
        LEFT JOIN creator c ON p.idcreator = c.idcreator
        LEFT JOIN album a ON p.idalbum = a.idalbum
        WHERE 1=1";

$params = [];

if (!empty($searchKeyword)) {
    $sql .= " AND (p.title LIKE :keyword OR p.comment LIKE :keyword)";
    $params[':keyword'] = '%' . $searchKeyword . '%';
}

if (!empty($selectedCreator) && is_numeric($selectedCreator)) {
    $sql .= " AND p.idcreator = :idcreator";
    $params[':idcreator'] = $selectedCreator;
}

if (!empty($selectedAlbum) && is_numeric($selectedAlbum)) {
    $sql .= " AND p.idalbum = :idalbum";
    $params[':idalbum'] = $selectedAlbum;
}

$sql .= " ORDER BY p.idphoto DESC";

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

<div class="admin-content-header">
    <div class="header-left">
        <h1>Manage Photos</h1>
        <p class="subtitle">Maintain photo records, validate relationships, and manage uploads.</p>
    </div>
    <div class="header-right">
        <a href="add-photo.php" class="btn btn-primary">+ Add Photo</a>
    </div>
</div>

<!-- Filter and Search Controls (Matches Wireframe 7.10) -->
<form method="GET" action="manage-photos.php" class="admin-filter-bar">
    <div class="filter-item">
        <label for="search">Filter & Search Records:</label>
        <div class="search-input-group">
            <input type="text" name="search" id="search" placeholder="Search by Title..." value="<?php echo htmlspecialchars($searchKeyword); ?>">
            <button type="submit" class="btn btn-dark">Search</button>
        </div>
    </div>

    <div class="filter-item">
        <label for="creator">Creator:</label>
        <select name="creator" id="creator" onchange="this.form.submit()">
            <option value="">Select a Creator</option>
            <?php foreach ($creators as $c): ?>
                <option value="<?php echo htmlspecialchars($c['idcreator']); ?>" <?php echo ($selectedCreator == $c['idcreator']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($c['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="filter-item">
        <label for="album">Album:</label>
        <select name="album" id="album" onchange="this.form.submit()">
            <option value="">Select a Album</option>
            <?php foreach ($albums as $a): ?>
                <option value="<?php echo htmlspecialchars($a['idalbum']); ?>" <?php echo ($selectedAlbum == $a['idalbum']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($a['title']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <?php if (!empty($searchKeyword) || !empty($selectedCreator) || !empty($selectedAlbum)): ?>
        <div class="filter-item filter-reset">
            <a href="manage-photos.php" class="btn btn-secondary">Clear Filters</a>
        </div>
    <?php endif; ?>
</form>

<!-- Tabular Photo List (Matches Wireframe 7.10) -->
<div class="admin-table-container">
    <table class="admin-data-table">
        <thead>
            <tr>
                <th style="width: 90px;">Image</th>
                <th style="width: 70px;">ID</th>
                <th>Photo Title</th>
                <th>Creator</th>
                <th>Album</th>
                <th style="width: 150px; text-align: center;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($photos)): ?>
                <?php foreach ($photos as $photo): ?>
                    <?php 
                        $thumbPath = !empty($photo['imageurl']) ? '../' . ltrim(htmlspecialchars($photo['imageurl']), '/') : '../assets/images/image-placeholder.png';
                    ?>
                    <tr>
                        <td>
                            <div class="table-img-wrap">
                                <img src="<?php echo $thumbPath; ?>" 
                                     alt="Photo Thumbnail" 
                                     class="table-thumb"
                                     onerror="this.onerror=null; this.src='../assets/images/image-placeholder.png';">
                            </div>
                        </td>
                        <td><?php echo str_pad(htmlspecialchars($photo['idphoto']), 2, '0', STR_PAD_LEFT); ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($photo['photo_title']); ?></strong>
                        </td>
                        <td><?php echo htmlspecialchars($photo['creator_name'] ?? 'Not Assigned'); ?></td>
                        <td><?php echo htmlspecialchars($photo['album_title'] ?? 'Standalone Photo'); ?></td>
                        <td style="text-align: center;">
                            <div class="action-buttons-inline">
                                <a href="edit-photo.php?id=<?php echo htmlspecialchars($photo['idphoto']); ?>" class="btn-action edit-btn">Edit</a>
                                <form method="POST" action="delete-photo.php" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this photo record? This action cannot be undone.');">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($photo['idphoto']); ?>">
                                    <button type="submit" class="btn-action delete-btn">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="no-records-cell">No photo records found matching the specified criteria.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>