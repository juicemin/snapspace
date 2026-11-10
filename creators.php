
<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Creators';

$query = trim($_GET['q'] ?? '');
$errorMessage = '';
$creators = [];

if (strlen($query) > 100) {
    $errorMessage =
        'Enter a creator name of 100 characters or fewer.';
} else {
    try {
        if ($query === '') {
            $statement = $pdo->query(
                'SELECT idcreator, name, website, imageurl
                 FROM creator
                 ORDER BY name ASC'
            );
        } else {
            $escapedQuery = strtr(
                $query,
                [
                    '!' => '!!',
                    '%' => '!%',
                    '_' => '!_'
                ]
            );

            $statement = $pdo->prepare(
                "SELECT idcreator, name, website, imageurl
                 FROM creator
                 WHERE name LIKE :query ESCAPE '!'
                 ORDER BY name ASC"
            );

            $statement->execute([
                'query' => '%' . $escapedQuery . '%'
            ]);
        }

        $creators = $statement->fetchAll();
    } catch (PDOException $exception) {
        $errorMessage =
            'Creators could not be loaded at this time.';
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<link rel="stylesheet" href="/snapspace/css/creator.css">

<section class="creator-page container">
    <div class="creator-page-heading">
        <h1>Meet Our Creators</h1>
        <p>Explore the creators and discover their albums and photos.</p>
    </div>

        <form
        class="search-form creator-search"
        action="/snapspace/creators.php"
        method="get"
    >
        <label
            class="visually-hidden"
            for="creator-search"
        >
            Search creators
        </label>

        <div class="search-input-wrapper">

            <input
                class="form-control"
                type="search"
                id="creator-search"
                name="q"
                maxlength="100"
                value="<?= escape($query) ?>"
                placeholder="Search creator names..."
                autocomplete="off"
                aria-autocomplete="list"
                aria-controls="creator-search-suggestions"
                data-search-suggestions
                data-suggestion-type="creator"
            >

            <div
                class="search-suggestions"
                id="creator-search-suggestions"
                role="listbox"
                aria-live="polite"
                hidden
            ></div>

        </div>

        <button class="button" type="submit">
            Search
        </button>
    </form>

    <?php if ($errorMessage !== ''): ?>
        <div class="public-message public-message-error">
            <?= escape($errorMessage) ?>
        </div>
    <?php endif; ?>

    <?php if (
        $errorMessage === '' &&
        empty($creators)
    ): ?>
        <div class="creator-empty">
            <p>No creators found.</p>
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
