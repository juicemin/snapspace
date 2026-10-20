<?php
$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';
?>

<section class="container" style="padding-block: 70px;">
    <h1>Discover Creators and Their Stories</h1>

    <p>
        Explore photographic collections from different creators.
    </p>

    <form class="search-form" action="/snapspace/search.php" method="get">
        <label for="home-search" hidden>Search Snap Space</label>

        <input
            class="form-control"
            type="search"
            id="home-search"
            name="q"
            placeholder="Search creators, albums or photos..."
        >

        <button class="button" type="submit">
            Search
        </button>
    </form>

    <div style="display: flex; gap: 16px; margin-top: 24px;">
        <a class="button" href="/snapspace/creators.php">
            Browse Creators
        </a>

        <a class="button button-secondary" href="/snapspace/photos.php">
            Browse Photos
        </a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>