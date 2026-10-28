<?php

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Search';

$searchTerm = trim($_GET['q'] ?? '');

$creatorResults = [];
$albumResults = [];
$photoResults = [];

$searchError = '';
$searchPerformed = false;

if ($searchTerm !== '') {
    $searchPerformed = true;

    if (strlen($searchTerm) > 100) {
        $searchError = 'Please enter a search term of 100 characters or fewer.';
    } else {
        $keyword = '%' . $searchTerm . '%';

        try {
            /*
             * Search creator names.
             */
            $creatorStatement = $pdo->prepare(
                'SELECT
                    creator.idcreator,
                    creator.name,
                    creator.website,
                    creator.imageurl,
                    COUNT(album.idalbum) AS album_count
                 FROM creator
                 LEFT JOIN album
                    ON creator.idcreator = album.idcreator
                 WHERE creator.name LIKE :creator_keyword
                 GROUP BY
                    creator.idcreator,
                    creator.name,
                    creator.website,
                    creator.imageurl
                 ORDER BY creator.name ASC
                 LIMIT 30'
            );

            $creatorStatement->execute([
                'creator_keyword' => $keyword
            ]);

            $creatorResults = $creatorStatement->fetchAll();

            /*
             * Search album titles.
             */
            $albumStatement = $pdo->prepare(
                'SELECT
                    album.idalbum,
                    album.title,
                    album.imageurl,
                    creator.idcreator,
                    creator.name AS creator_name
                 FROM album
                 INNER JOIN creator
                    ON album.idcreator = creator.idcreator
                 WHERE album.title LIKE :album_keyword
                 ORDER BY album.title ASC
                 LIMIT 30'
            );

            $albumStatement->execute([
                'album_keyword' => $keyword
            ]);

            $albumResults = $albumStatement->fetchAll();

            /*
             * Search photo titles and comments.
             */
            $photoStatement = $pdo->prepare(
                'SELECT
                    photo.idphoto,
                    photo.title,
                    photo.imageurl,
                    photo.comment,
                    creator.idcreator,
                    creator.name AS creator_name,
                    album.idalbum,
                    album.title AS album_title
                 FROM photo
                 INNER JOIN creator
                    ON photo.idcreator = creator.idcreator
                 INNER JOIN album
                    ON photo.idalbum = album.idalbum
                 WHERE
                    photo.title LIKE :photo_title_keyword
                    OR photo.comment LIKE :photo_comment_keyword
                 ORDER BY photo.title ASC
                 LIMIT 30'
            );

            $photoStatement->execute([
                'photo_title_keyword' => $keyword,
                'photo_comment_keyword' => $keyword
            ]);

            $photoResults = $photoStatement->fetchAll();
        } catch (PDOException $exception) {
            $searchError =
                'The search could not be completed at this time.';
        }
    }
}

$totalResults =
    count($creatorResults)
    + count($albumResults)
    + count($photoResults);

require_once __DIR__ . '/includes/header.php';
?>

<section class="search-page-header">
    <div class="container search-page-header-content">

        <p class="eyebrow">Search Snap Space</p>

        <h1>Search the Collection</h1>

        <p>
            Search for creators, photographic albums and individual
            photographs using a title, name or keyword.
        </p>

        <form
            class="search-form search-page-form"
            action="/snapspace/search.php"
            method="get"
        >
            <label class="visually-hidden" for="search-query">
                Search creators, albums or photos
            </label>

            <input
                class="form-control"
                type="search"
                id="search-query"
                name="q"
                maxlength="100"
                value="<?= escape($searchTerm) ?>"
                placeholder="Search creators, albums or photos..."
                required
            >

            <button class="button" type="submit">
                Search
            </button>
        </form>

    </div>
</section>

<div class="container search-results-container">

<?php if ($searchError !== ''): ?>

    <div class="public-message public-message-error">
        <?= escape($searchError) ?>
    </div>

<?php elseif (!$searchPerformed): ?>

    <div class="search-empty">
        <h2>Start Your Search</h2>

        <p>
            Enter a creator name, album title, photo title or a
            keyword from a photo comment.
        </p>
    </div>

<?php elseif ($totalResults === 0): ?>

    <div class="search-empty">
        <h2>No Results Found</h2>

        <p>
            No creators, albums or photos matched
            “<?= escape($searchTerm) ?>”.
        </p>

        <p>
            Try another name, title or keyword.
        </p>
    </div>

<?php else: ?>

    <div class="search-summary">
        <h2>
            Results for “<?= escape($searchTerm) ?>”
        </h2>

        <p>
            <?= $totalResults ?>
            result<?= $totalResults === 1 ? '' : 's' ?>
            found:
            <?= count($creatorResults) ?>
            creator<?= count($creatorResults) === 1 ? '' : 's' ?>,
            <?= count($albumResults) ?>
            album<?= count($albumResults) === 1 ? '' : 's' ?> and
            <?= count($photoResults) ?>
            photo<?= count($photoResults) === 1 ? '' : 's' ?>.
        </p>
    </div>

    <?php if ($creatorResults): ?>

        <section class="search-result-group">

            <div class="section-heading-row">
                <div>
                    <p class="eyebrow">People</p>
                    <h2>Creators</h2>
                </div>

                <span class="result-count">
                    <?= count($creatorResults) ?> found
                </span>
            </div>

            <div class="card-grid">

                <?php foreach ($creatorResults as $creator): ?>

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
                                album<?= 
                                    (int) $creator['album_count'] === 1
                                        ? ''
                                        : 's'
                                ?>
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

        </section>

    <?php endif; ?>

    <?php if ($albumResults): ?>

        <section class="search-result-group">

            <div class="section-heading-row">
                <div>
                    <p class="eyebrow">Collections</p>
                    <h2>Albums</h2>
                </div>

                <span class="result-count">
                    <?= count($albumResults) ?> found
                </span>
            </div>

            <div class="card-grid">

                <?php foreach ($albumResults as $album): ?>

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

        </section>

    <?php endif; ?>

    <?php if ($photoResults): ?>

        <section class="search-result-group">

            <div class="section-heading-row">
                <div>
                    <p class="eyebrow">Photography</p>
                    <h2>Photos</h2>
                </div>

                <span class="result-count">
                    <?= count($photoResults) ?> found
                </span>
            </div>

            <div class="card-grid">

                <?php foreach ($photoResults as $photo): ?>

                    <article class="card">

                        <img
                            class="card-image"
                            src="<?= escape(
                                imageUrl($photo['imageurl'])
                            ) ?>"
                            alt="<?= escape($photo['title']) ?>"
                        >

                        <div class="card-content">

                            <h3 class="card-title">
                                <?= escape($photo['title']) ?>
                            </h3>

                            <p class="card-text search-photo-meta">
                                By
                                <a
                                    href="/snapspace/creator-details.php?id=<?= 
                                        (int) $photo['idcreator']
                                    ?>"
                                >
                                    <?= escape(
                                        $photo['creator_name']
                                    ) ?>
                                </a>

                                ·

                                <a
                                    href="/snapspace/album-details.php?id=<?= 
                                        (int) $photo['idalbum']
                                    ?>"
                                >
                                    <?= escape(
                                        $photo['album_title']
                                    ) ?>
                                </a>
                            </p>

                            <?php if (
                                trim($photo['comment'] ?? '') !== ''
                            ): ?>
                                <p class="card-text">
                                    <?= escape($photo['comment']) ?>
                                </p>
                            <?php endif; ?>

                            <a
                                class="button"
                                href="/snapspace/photo-details.php?id=<?= 
                                    (int) $photo['idphoto']
                                ?>"
                            >
                                View Photo
                            </a>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        </section>

    <?php endif; ?>

<?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>