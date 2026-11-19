
<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$creatorId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$creatorId || $creatorId < 1) {
    http_response_code(400);
    exit('Invalid creator ID.');
}

$stmt = $pdo->prepare(
    'SELECT idcreator, name, website, imageurl
     FROM creator
     WHERE idcreator = :idcreator'
);
$stmt->execute(['idcreator' => $creatorId]);
$creator = $stmt->fetch();

if (!$creator) {
    http_response_code(404);
    exit('Creator not found.');
}

$albumStmt = $pdo->prepare(
    'SELECT idalbum, title, imageurl
     FROM album
     WHERE idcreator = :idcreator
     ORDER BY title ASC'
);
$albumStmt->execute(['idcreator' => $creatorId]);
$albums = $albumStmt->fetchAll();

$photoStmt = $pdo->prepare(
    'SELECT idphoto, title, imageurl, comment
     FROM photo
     WHERE idcreator = :idcreator
     ORDER BY title ASC'
);
$photoStmt->execute(['idcreator' => $creatorId]);
$photos = $photoStmt->fetchAll();

$pageTitle = $creator['name'];

require_once __DIR__ . '/includes/header.php';
?>

<link rel="stylesheet" href="/snapspace/css/creator.css">

<section class="creator-detail-page container">
    <a class="creator-back-link" href="/snapspace/creators.php">
        &larr; Back to Creators
    </a>

    <section class="creator-profile">
        <div class="creator-profile-image-wrapper">
            <img
                class="creator-profile-image"
                src="<?= escape(imageUrl($creator['imageurl'])) ?>"
                alt="<?= escape($creator['name']) ?>"
            >
        </div>

        <div class="creator-profile-content">
            <p class="creator-eyebrow">CREATOR PROFILE</p>
            <h1><?= escape($creator['name']) ?></h1>

            <?php if (!empty($creator['website'])): ?>
                <a
                    class="creator-website"
                    href="<?= escape($creator['website']) ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    Visit Website
                </a>
            <?php endif; ?>
        </div>
    </section>

    <section class="creator-detail-section">
        <div class="creator-section-heading">
            <h2>Albums</h2>
            <span><?= count($albums) ?> album(s)</span>
        </div>

        <?php if (empty($albums)): ?>
            <p class="creator-section-empty">
                No albums available for this creator.
            </p>
        <?php else: ?>
            <div class="creator-grid">
                <?php foreach ($albums as $album): ?>
                    <article class="creator-card">
                        <a
                            class="creator-image-link"
                            href="/snapspace/album-details.php?id=<?= (int) $album['idalbum'] ?>"
                        >
                            <img
                                class="creator-image"
                                src="<?= escape(imageUrl($album['imageurl'])) ?>"
                                alt="<?= escape($album['title']) ?>"
                                loading="lazy"
                            >
                        </a>

                        <div class="creator-card-content">
                            <h3><?= escape($album['title']) ?></h3>
                            <a
                                class="creator-view-link"
                                href="/snapspace/album-details.php?id=<?= (int) $album['idalbum'] ?>"
                            >
                                View Album
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="creator-detail-section">
        <div class="creator-section-heading">
            <h2>Photos</h2>
            <span><?= count($photos) ?> photo(s)</span>
        </div>

        <?php if (empty($photos)): ?>
            <p class="creator-section-empty">
                No photos available for this creator.
            </p>
        <?php else: ?>
            <div class="creator-grid">
                <?php foreach ($photos as $photo): ?>
                    <article class="creator-card">
                        <a
                            class="creator-image-link"
                            href="/snapspace/photo-details.php?id=<?= (int) $photo['idphoto'] ?>"
                        >
                            <img
                                class="creator-image"
                                src="<?= escape(imageUrl($photo['imageurl'])) ?>"
                                alt="<?= escape($photo['title']) ?>"
                                loading="lazy"
                            >
                        </a>

                        <div class="creator-card-content">
                            <h3><?= escape($photo['title']) ?></h3>

                            <?php if (!empty($photo['comment'])): ?>
                                <p class="creator-photo-comment">
                                    <?= escape($photo['comment']) ?>
                                </p>
                            <?php endif; ?>

                            <a
                                class="creator-view-link"
                                href="/snapspace/photo-details.php?id=<?= (int) $photo['idphoto'] ?>"
                            >
                                View Photo
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
