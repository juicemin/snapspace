
<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    http_response_code(503);
    exit('Creator administration is unavailable.');
}

$adminId = $_SESSION['admin_id'] ?? null;

if (
    (!is_int($adminId) && !is_string($adminId)) ||
    filter_var(
        $adminId,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    ) === false
) {
    http_response_code(403);
    exit('Administrator authentication is required.');
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(503);
    exit('The database connection is unavailable.');
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$escape = static function ($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
};

if (empty($_SESSION['creator_csrf_token'])) {
    $_SESSION['creator_csrf_token'] = bin2hex(random_bytes(32));
}

$pageTitle = 'Manage Creators';
$activeSection = 'creators';
$adminName = $_SESSION['admin_name'] ?? 'Admin';

$creators = [];
$error = '';

/*
 * Retrieve all creator records.
 */
try {
    $statement = $pdo->prepare(
        'SELECT idcreator, name, website, imageurl
         FROM creator
         ORDER BY name ASC, idcreator ASC'
    );

    $statement->execute();
    $creators = $statement->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $exception) {
    error_log(
        'Manage Creators database error: ' . $exception->getMessage()
    );

    http_response_code(503);
    $error = 'Creators could not be loaded. Please try again later.';
}

require_once __DIR__ . '/../includes/admin-header.php';
?>

<link rel="stylesheet" href="../css/album.css">

<section class="album-module album-admin">

    <div class="album-heading">
        <h1>Manage Creators</h1>

        <a class="album-button" href="add-creator.php">
            Add Creator
        </a>
    </div>

    <?php if (!empty($_SESSION['flash_message'])): ?>

        <?php
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        ?>

        <p
            class="<?= ($flash['type'] ?? '') === 'success'
                ? 'album-success'
                : 'album-error' ?>"
            role="status"
        >
            <?= $escape($flash['message'] ?? '') ?>
        </p>

    <?php endif; ?>

    <?php if ($error !== ''): ?>

        <p class="album-error" role="alert">
            <?= $escape($error) ?>
        </p>

    <?php elseif (empty($creators)): ?>

        <p class="album-empty">
            No creators found. Add a creator to get started.
        </p>

    <?php else: ?>

        <div
            class="admin-table-wrapper"
            tabindex="0"
            role="region"
            aria-label="Creator management table"
        >

            <table class="admin-table">

                <thead>
                    <tr>
                        <th scope="col">Image</th>
                        <th scope="col">Creator Name</th>
                        <th scope="col">Website</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($creators as $creator): ?>

                        <tr>

                            <td>
                                <?php if (!empty($creator['imageurl'])): ?>

                                    <img
                                        src="<?= $escape(imageUrl($creator['imageurl'])) ?>"
                                        alt="<?= $escape($creator['name']) ?>"
                                        width="60"
                                        height="60"
                                        style="object-fit: cover; border-radius: 6px;"
                                    >

                                <?php else: ?>

                                    No image

                                <?php endif; ?>
                            </td>

                            <td>
                                <a href="../creator-details.php?id=<?= (int) $creator['idcreator'] ?>">
                                    <?= $escape($creator['name']) ?>
                                </a>
                            </td>

                            <td>
                                <?php if (!empty($creator['website'])): ?>

                                    <a
                                        href="<?= $escape($creator['website']) ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <?= $escape($creator['website']) ?>
                                    </a>

                                <?php else: ?>

                                    -

                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="album-actions">

                                    <a href="edit-creator.php?id=<?= (int) $creator['idcreator'] ?>">
                                        Edit
                                    </a>

                                    <form
                                        method="post"
                                        action="delete-creator.php"
                                        onsubmit="return confirm('Are you sure you want to delete this creator?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int) $creator['idcreator'] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= $escape($_SESSION['creator_csrf_token']) ?>"
                                        >

                                        <button
                                            class="album-button album-danger"
                                            type="submit"
                                        >
                                            Delete
                                        </button>

                                    </form>

                                </div>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</section>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
