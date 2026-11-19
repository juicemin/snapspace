<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$accessError = $_SESSION['access_error'] ?? '';
unset($_SESSION['access_error']);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Home';

$featuredCreators = [];
$featuredAlbums = [];
$homeError = '';

try {
    $creatorStatement = $pdo->query(
        'SELECT
            creator.idcreator,
            creator.name,
            creator.website,
            creator.imageurl,
            COUNT(album.idalbum) AS album_count
         FROM creator
         LEFT JOIN album
            ON creator.idcreator = album.idcreator
         GROUP BY
            creator.idcreator,
            creator.name,
            creator.website,
            creator.imageurl
         ORDER BY creator.idcreator DESC
         LIMIT 3'
    );

    $featuredCreators = $creatorStatement->fetchAll();

    $albumStatement = $pdo->query(
        'SELECT
            album.idalbum,
            album.title,
            album.imageurl,
            creator.idcreator,
            creator.name AS creator_name
         FROM album
         INNER JOIN creator
            ON album.idcreator = creator.idcreator
         ORDER BY album.idalbum DESC
         LIMIT 3'
    );

    $featuredAlbums = $albumStatement->fetchAll();
} catch (PDOException $exception) {
    $homeError =
        'The featured collection could not be loaded at this time.';
}

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($accessError !== ''): ?>
    <section class="container section">
        <div
            class="public-message public-message-error"
            role="alert"
        >
            <?= escape($accessError) ?>
        </div>
    </section>
<?php endif; ?>

<section class="home-hero">
    <div class="container home-hero-grid">

        <div class="home-hero-content">
            <p class="eyebrow">Creator Portfolio and Photo Gallery</p>

            <h1>Discover Creators and Their Stories</h1>

            <p class="home-hero-description">
                Explore photographic collections from different
                creators, browse their albums and discover the
                stories behind individual photographs.
            </p>

            <form
                class="search-form home-search"
                action="/snapspace/search.php"
                method="get"
            >
                <label class="visually-hidden" for="home-search">
                    Search Snap Space
                </label>

               <div class="search-input-wrapper">

                <input
                    class="form-control"
                    type="search"
                    id="home-search"
                    name="q"
                    maxlength="100"
                    placeholder="Search creators, albums or photos..."
                    autocomplete="off"
                    aria-autocomplete="list"
                    aria-controls="home-search-suggestions"
                    data-search-suggestions
                    required
                >

                <div
                    class="search-suggestions"
                    id="home-search-suggestions"
                    role="listbox"
                    aria-live="polite"
                    hidden
                ></div>

            </div>

                <button class="button" type="submit">
                    Search
                </button>
            </form>

            <div class="home-actions">
                <a
                    class="button"
                    href="/snapspace/creators.php"
                >
                    Browse Creators
                </a>

                <a
                    class="button button-secondary"
                    href="/snapspace/photos.php"
                >
                    Browse Photos
                </a>
            </div>
        </div>

        <div class="home-hero-image">
            <img
                src="/snapspace/assets/images/photos/city-crossing.jpg"
                alt="Featured city photography from Snap Space"
            >
        </div>

    </div>
</section>

<?php if ($homeError !== ''): ?>
    <section class="container section">
        <div class="public-message public-message-error">
            <?= escape($homeError) ?>
        </div>
    </section>
<?php endif; ?>

<section class="container section">

    <div class="section-heading-row">
        <div>
            <h2>Featured Creators</h2>
        </div>

        <a class="text-link" href="/snapspace/creators.php">
            View All Creators
        </a>
    </div>

    <?php if ($featuredCreators): ?>

        <div class="card-grid home-card-grid">

            <?php foreach ($featuredCreators as $creator): ?>

                <article class="card">

                    <img
                        class="card-image creator-card-image"
                        src="<?= escape(
                            imageUrl($creator['imageurl'])
                        ) ?>"
                        alt="<?= escape($creator['name']) ?>"
                    >

                    <div class="card-content">
                        <h3 class="card-title">
                            <?= escape($creator['name']) ?>
                        </h3>

                        <p class="card-text">
                            <?= (int) $creator['album_count'] ?>
                            album<?= (int) $creator['album_count'] === 1
                                ? ''
                                : 's' ?>
                        </p>

                        <a
                            class="button"
                            href="/snapspace/creator-details.php?id=<?= 
                                (int) $creator['idcreator']
                            ?>"
                        >
                            View Profile
                        </a>
                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <p class="empty-state">
            No creators are available yet.
        </p>

    <?php endif; ?>

</section>

<section class="section section-muted">

    <div class="container">

        <div class="section-heading-row">
            <div>
                <h2>Featured Albums</h2>
            </div>

            <a class="text-link" href="/snapspace/albums.php">
                View All Albums
            </a>
        </div>

        <?php if ($featuredAlbums): ?>

            <div class="card-grid home-card-grid">

                <?php foreach ($featuredAlbums as $album): ?>

                    <article class="card">

                        <img
                            class="card-image"
                            src="<?= escape(
                                imageUrl($album['imageurl'])
                            ) ?>"
                            alt="<?= escape($album['title']) ?>"
                        >

                        <div class="card-content">
                            <h3 class="card-title">
                                <?= escape($album['title']) ?>
                            </h3>

                            <p class="card-text">
                                By
                                <a
                                    href="/snapspace/creator-details.php?id=<?= 
                                        (int) $album['idcreator']
                                    ?>"
                                >
                                    <?= escape(
                                        $album['creator_name']
                                    ) ?>
                                </a>
                            </p>

                            <a
                                class="button"
                                href="/snapspace/album-details.php?id=<?= 
                                    (int) $album['idalbum']
                                ?>"
                            >
                                View Album
                            </a>
                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <p class="empty-state">
                No albums are available yet.
            </p>

        <?php endif; ?>

    </div>

</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>