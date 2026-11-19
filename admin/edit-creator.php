
<?php

require_once __DIR__ . '/../includes/auth.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
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

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(503);
    exit('The database connection is unavailable.');
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (empty($_SESSION['creator_csrf_token'])) {
    $_SESSION['creator_csrf_token'] = bin2hex(random_bytes(32));
}

$pageTitle = 'Edit Creator';
$activeSection = 'creators';
$adminName = $_SESSION['admin_name'] ?? 'Admin';

$errors = [];
$name = '';
$website = '';
$imageurl = '';

$id = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? ($_POST['id'] ?? '')
    : ($_GET['id'] ?? '');

if (
    !is_string($id) ||
    filter_var(
        $id,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    ) === false
) {
    http_response_code(400);
    exit('Invalid or missing Creator ID.');
}

$creatorId = (int) $id;

/*
 * Retrieve the selected creator.
 */
try {
    $statement = $pdo->prepare(
        'SELECT idcreator, name, website, imageurl
         FROM creator
         WHERE idcreator = :id
         LIMIT 1'
    );

    $statement->execute([
        'id' => $creatorId
    ]);

    $creator = $statement->fetch();

    if (!$creator) {
        http_response_code(404);
        exit('Creator not found.');
    }

    $name = $creator['name'];
    $website = $creator['website'] ?? '';
    $imageurl = $creator['imageurl'] ?? '';

} catch (PDOException $exception) {
    error_log('Edit Creator SELECT error: ' . $exception->getMessage());
    http_response_code(503);
    exit('Unable to load creator information.');
}

/*
 * Process the update form.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = is_string($_POST['name'] ?? null)
        ? trim($_POST['name'])
        : '';

    $website = is_string($_POST['website'] ?? null)
        ? trim($_POST['website'])
        : '';

    $imageurl = is_string($_POST['imageurl'] ?? null)
        ? trim($_POST['imageurl'])
        : '';

    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION['creator_csrf_token'], $submittedToken)
    ) {
        $errors[] = 'The form expired or could not be verified. Please try again.';
    }

    /*
     * Validate creator name.
     */
    $nameLength = preg_match_all('/./us', $name);

    if (
        $name === '' ||
        $nameLength === false ||
        $nameLength > 100
    ) {
        $errors[] = 'Enter a creator name of 1 to 100 characters.';
    }

    /*
     * Validate optional website URL.
     */
    if (isset($_POST['website']) && !is_string($_POST['website'])) {
        $errors[] = 'Enter a valid website URL.';
    } elseif ($website !== '') {

        $validWebsite = filter_var($website, FILTER_VALIDATE_URL);
        $scheme = strtolower((string) parse_url($website, PHP_URL_SCHEME));

        if (
            !$validWebsite ||
            !in_array($scheme, ['http', 'https'], true) ||
            strlen($website) > 255
        ) {
            $errors[] = 'Enter a valid HTTP/HTTPS website URL (maximum 255 characters).';
        }
    }

    /*
     * Validate optional image URL or local image path.
     */
    if (isset($_POST['imageurl']) && !is_string($_POST['imageurl'])) {
        $errors[] = 'Enter a valid image URL or path.';
    } elseif ($imageurl !== '') {

        $validRemoteImage = false;
        $validLocalImage = false;

        if (
            strlen($imageurl) <= 255 &&
            filter_var($imageurl, FILTER_VALIDATE_URL)
        ) {
            $scheme = strtolower(
                (string) parse_url($imageurl, PHP_URL_SCHEME)
            );

            $validRemoteImage = in_array(
                $scheme,
                ['http', 'https'],
                true
            );
        }

        if (
            strlen($imageurl) <= 255 &&
            preg_match(
                '~^(?:uploads|assets)/[a-zA-Z0-9_./-]+\.(?:jpe?g|png|gif|webp|avif)$~iD',
                $imageurl
            ) &&
            strpos($imageurl, '..') === false &&
            is_file(__DIR__ . '/../' . $imageurl)
        ) {
            $validLocalImage = true;
        }

        if (!$validRemoteImage && !$validLocalImage) {
            $errors[] = 'Enter a valid HTTP/HTTPS image URL or an existing image path under uploads/ or assets/.';
        }
    }

    /*
     * Update creator information.
     */
    if (empty($errors)) {
        try {
            $statement = $pdo->prepare(
                'UPDATE creator
                 SET name = :name,
                     website = :website,
                     imageurl = :imageurl
                 WHERE idcreator = :id'
            );

            $statement->execute([
                'name' => $name,
                'website' => $website === '' ? null : $website,
                'imageurl' => $imageurl === '' ? null : $imageurl,
                'id' => $creatorId
            ]);

            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Creator updated successfully.'
            ];

            header(
                'Location: /snapspace/admin/manage-creators.php',
                true,
                303
            );

            exit;

        } catch (PDOException $exception) {
            error_log('Edit Creator UPDATE error: ' . $exception->getMessage());

            $errors[] = 'The creator could not be updated. Please try again later.';
            http_response_code(503);
        }
    }

    if (!empty($errors)) {
        http_response_code(422);
    }
}

require_once __DIR__ . '/../includes/admin-header.php';
?>

<link rel="stylesheet" href="../css/album.css">

<section class="album-module album-admin">

    <div class="album-page-header">
        <div>
            <h1>Edit Creator</h1>
            <p>Update the creator's information.</p>
        </div>

        <a
            href="manage-creators.php"
            class="album-button album-button-secondary"
        >
            Back to Creators
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="admin-alert admin-alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= escape($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="album-form-card">

        <form method="POST" action="edit-creator.php?id=<?= $creatorId ?>">

            <input
                type="hidden"
                name="id"
                value="<?= $creatorId ?>"
            >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= escape($_SESSION['creator_csrf_token']) ?>"
            >

            <div class="album-form-group">
                <label for="name">Creator Name *</label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?= escape($name) ?>"
                    maxlength="100"
                    required
                >
            </div>

            <div class="album-form-group">
                <label for="website">Website</label>

                <input
                    type="url"
                    id="website"
                    name="website"
                    value="<?= escape($website) ?>"
                    maxlength="255"
                    placeholder="https://example.com"
                >
            </div>

            <div class="album-form-group">
                <label for="imageurl">Image URL or Image Path</label>

                <input
                    type="text"
                    id="imageurl"
                    name="imageurl"
                    value="<?= escape($imageurl) ?>"
                    maxlength="255"
                    placeholder="https://example.com/image.jpg"
                >

                <small>
                    Enter an HTTP/HTTPS image URL or an existing path under uploads/ or assets/.
                    Leave empty if no image is required.
                </small>
            </div>

            <?php if ($imageurl !== ''): ?>
                <div class="album-form-group">
                    <label>Current Image</label>

                    <img
                        src="<?= escape(imageUrl($imageurl)) ?>"
                        alt="Creator image"
                        style="max-width: 180px; max-height: 180px; object-fit: cover;"
                    >
                </div>
            <?php endif; ?>

            <div class="album-form-actions">

                <button type="submit" class="album-button">
                    Save Changes
                </button>

                <a
                    href="manage-creators.php"
                    class="album-button album-button-secondary"
                >
                    Cancel
                </a>

            </div>

        </form>

    </div>

</section>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
