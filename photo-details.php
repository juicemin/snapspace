<?php
// photo-details.php - Snap Space Photo Details Page
$pageTitle = "Photo Details - Snap Space";
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

// 1. Validate incoming photo ID parameter
$photoId = isset($_GET['id']) ? trim($_GET['id']) : '';

$photo = null;
$relatedPhotos = [];

if (!empty($photoId) && is_numeric($photoId) && isset($pdo)) {
    try {
        // 2. Fetch specific photo record with joined creator and album data
        $query = "SELECT p.idphoto, p.title AS photo_title, p.imageurl, p.comment,
                         c.idcreator, c.name AS creator_name,
                         a.idalbum, a.title AS album_title
                  FROM photo p
                  LEFT JOIN creator c ON p.idcreator = c.idcreator
                  LEFT JOIN album a ON p.idalbum = a.idalbum
                  WHERE p.idphoto = :idphoto
                  LIMIT 1";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([':idphoto' => $photoId]);
        $photo = $stmt->fetch(PDO::FETCH_ASSOC);

        // 3. Fetch related photos from the same album or creator (excluding the current photo)
        if ($photo) {
            $relatedQuery = "SELECT p.idphoto, p.title AS photo_title, p.imageurl,
                                    c.name AS creator_name,
                                    a.title AS album_title
                             FROM photo p
                             LEFT JOIN creator c ON p.idcreator = c.idcreator
                             LEFT JOIN album a ON p.idalbum = a.idalbum
                             WHERE p.idphoto != :current_id";
            
            $relatedParams = [':current_id' => $photoId];

            if (!empty($photo['idalbum'])) {
                $relatedQuery .= " AND p.idalbum = :idalbum";
                $relatedParams[':idalbum'] = $photo['idalbum'];
            } elseif (!empty($photo['idcreator'])) {
                $relatedQuery .= " AND p.idcreator = :idcreator";
                $relatedParams[':idcreator'] = $photo['idcreator'];
            }

            $relatedQuery .= " ORDER BY p.idphoto DESC LIMIT 3";

            $relStmt = $pdo->prepare($relatedQuery);
            $relStmt->execute($relatedParams);
            $relatedPhotos = $relStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $photo = null;
        $relatedPhotos = [];
    }
}
?>

<!-- Include module stylesheet -->
<link rel="stylesheet" href="css/photo.css">

<main class="gallery-container">
    <?php if ($photo): ?>
        <!-- Breadcrumb / Back Link -->
        <nav class="details-breadcrumb">
            <a href="photos.php" class="back-link">&larr; Back to Gallery</a>
        </nav>

        <!-- Main Photo Details Section (Matches Wireframe 7.8) -->
        <article class="photo-details-layout">
            <div class="photo-main-display">
                <?php 
                    $mainImgSrc = !empty($photo['imageurl']) ? htmlspecialchars($photo['imageurl']) : 'assets/images/image-placeholder.png';
                ?>
                <img src="<?php echo $mainImgSrc; ?>" 
                     alt="<?php echo htmlspecialchars($photo['photo_title']); ?>" 
                     class="detail-large-image"
                     onerror="this.onerror=null; this.src='assets/images/image-placeholder.png';">
            </div>

            <div class="photo-metadata-panel">
                <h1 class="detail-photo-title"><?php echo htmlspecialchars($photo['photo_title']); ?></h1>

                <!-- Creator Details Reference -->
                <div class="meta-item-group">
                    <span class="meta-label">Creator Details:</span>
                    <strong class="meta-value"><?php echo htmlspecialchars($photo['creator_name'] ?? 'Unknown Creator'); ?></strong>
                    <?php if (!empty($photo['idcreator'])): ?>
                        <a href="creator-details.php?id=<?php echo htmlspecialchars($photo['idcreator']); ?>" class="btn-outline">
                            View Creator Profile
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Album Details Reference -->
                <div class="meta-item-group">
                    <span class="meta-label">Album Information:</span>
                    <strong class="meta-value"><?php echo htmlspecialchars($photo['album_title'] ?? 'Standalone Photo'); ?></strong>
                    <?php if (!empty($photo['idalbum'])): ?>
                        <a href="album-details.php?id=<?php echo htmlspecialchars($photo['idalbum']); ?>" class="btn-outline">
                            View Related Album
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Comment / Story Field -->
                <div class="meta-item-group comment-group">
                    <span class="meta-label">Photo Comment / Story:</span>
                    <p class="photo-story-text">
                        <?php 
                            if (!empty($photo['comment'])) {
                                echo nl2br(htmlspecialchars($photo['comment']));
                            } else {
                                echo '<em>No description or comment provided for this photograph.</em>';
                            }
                        ?>
                    </p>
                </div>
            </div>
        </article>

        <!-- Related Photos Section (Matches Wireframe 7.8) -->
        <?php if (!empty($relatedPhotos)): ?>
            <section class="related-photos-section">
                <h2>Related Photographs</h2>
                <div class="photo-grid">
                    <?php foreach ($relatedPhotos as $rel): ?>
                        <?php 
                            $relImgSrc = !empty($rel['imageurl']) ? htmlspecialchars($rel['imageurl']) : 'assets/images/image-placeholder.png';
                        ?>
                        <div class="photo-card">
                            <div class="photo-thumb-wrap">
                                <img src="<?php echo $relImgSrc; ?>" 
                                     alt="<?php echo htmlspecialchars($rel['photo_title']); ?>" 
                                     class="photo-thumbnail"
                                     onerror="this.onerror=null; this.src='assets/images/image-placeholder.png';">
                            </div>
                            <div class="photo-info">
                                <h3 class="photo-title"><?php echo htmlspecialchars($rel['photo_title']); ?></h3>
                                <p class="creator-name">By <?php echo htmlspecialchars($rel['creator_name'] ?? 'Unknown'); ?></p>
                                <p class="album-name">Album: <?php echo htmlspecialchars($rel['album_title'] ?? 'Standalone'); ?></p>
                                <a href="photo-details.php?id=<?php echo htmlspecialchars($rel['idphoto']); ?>" class="btn-view-details">
                                    View Details
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

    <?php else: ?>
        <!-- Record Not Found State -->
        <div class="empty-state-card">
            <h2>Photograph Not Found</h2>
            <p>The requested photograph record does not exist or may have been removed.</p>
            <a href="photos.php" class="btn btn-primary">Return to Gallery</a>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>