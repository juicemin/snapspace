
<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Creators';

$stmt = $pdo->query(
    'SELECT idcreator, name, website, imageurl
     FROM creator
     ORDER BY name ASC'
);

$creators = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<link rel="stylesheet" href="/snapspace/css/creator.css">

<section class="creator-page container">
    <div class="creator-page-heading">
        <h1>Meet Our Creators</h1>
        <p>Explore the creators and discover their albums and photos.</p>
    </div>

    <?php if (empty($creators)): ?>
        <div class="creator-empty">
            <p>No creators are available yet.</p>
        </div>
    <?php else: ?>
        <div class="creator-grid">
            <?php foreach ($creators as $creator): ?>
                <article class="creator-card">
                    <a
                        class="creator-image-link"
                        href="/snapspace/creator-details.php?id=<?= (int) $creator['idcreator'] ?>"
                    >
                        <img
                            class="creator-image"
                            src="<?= escape(imageUrl($creator['imageurl'])) ?>"
                            alt="<?= escape($creator['name']) ?>"
                            loading="lazy"
                        >
                    </a>

                    <div class="creator-card-content">
                        <h2>
                            <a href="/snapspace/creator-details.php?id=<?= (int) $creator['idcreator'] ?>">
                                <?= escape($creator['name']) ?>
                            </a>
                        </h2>

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

                        <a
                            class="creator-view-link"
                            href="/snapspace/creator-details.php?id=<?= (int) $creator['idcreator'] ?>"
                        >
                            View Creator
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
