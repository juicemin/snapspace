
<?php
require_once __DIR__ . '/../includes/auth.php';

// Verify administrator authentication.
if (session_status() !== PHP_SESSION_ACTIVE) {
    http_response_code(503);
    exit('Creator administration is unavailable.');
}

$sessionAdminId = $_SESSION['admin_id'] ?? null;

if ((!is_int($sessionAdminId) && !is_string($sessionAdminId))
    || filter_var($sessionAdminId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
    http_response_code(403);
    exit('Administrator authentication is required.');
}

// Connect to the database.
require_once __DIR__ . '/../includes/db.php';

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

// Generate CSRF token.
if (empty($_SESSION['creator_csrf_token'])) {
    $_SESSION['creator_csrf_token'] = bin2hex(random_bytes(32));
}

$pageTitle = 'Add Creator';
$activeSection = 'creators';
$adminName = $_SESSION['admin_name'] ?? 'Admin';

$errors = [];
$name = '';
$website = '';
$imageurl = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Retrieve submitted values.
    $name = is_string($_POST['name'] ?? null)
        ? trim($_POST['name'])
        : '';

    $website = is_string($_POST['website'] ?? null)
        ? trim($_POST['website'])
        : '';

    $imageurl = is_string($_POST['imageurl'] ?? null)
        ? trim($_POST['imageurl'])
        : '';

    // Verify CSRF token.
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submittedToken)
        || !hash_equals($_SESSION['creator_csrf_token'], $submittedToken)
    ) {
        $errors[] = 'The form expired or could not be verified. Please submit it again.';
    }

    // Validate creator name.
    $nameLength = preg_match_all('/./us', $name);

    if (
        $name === ''
        || $nameLength === false
        || $nameLength > 100
    ) {
        $errors[] = 'Enter a creator name of 1 to 100 characters.';
    }

    // Validate website.
    if (isset($_POST['website']) && !is_string($_POST['website'])) {
        $errors[] = 'Enter a valid website URL.';
    } elseif ($website !== '') {

        $validWebsite = filter_var($website, FILTER_VALIDATE_URL);
        $scheme = strtolower((string) parse_url($website, PHP_URL_SCHEME));

        if (
            !$validWebsite
            || !in_array($scheme, ['http', 'https'], true)
            || strlen($website) > 255
        ) {
            $errors[] = 'Enter a valid website URL starting with http:// or https:// (maximum 255 characters).';
        }
    }

    // Validate creator image path or URL.
    if (isset($_POST['imageurl']) && !is_string($_POST['imageurl'])) {
        $errors[] = 'Enter a valid image path or URL.';
    } elseif ($imageurl !== '') {

        $validRemoteImage = false;
        $validLocalImage = false;

        if (
            strlen($imageurl) <= 255
            && filter_var($imageurl, FILTER_VALIDATE_URL)
        ) {
            $scheme = strtolower((string) parse_url($imageurl, PHP_URL_SCHEME));

            $validRemoteImage = in_array(
                $scheme,
                ['http', 'https'],
                true
            );
        }

        if (
            strlen($imageurl) <= 255
            && preg_match(
                '~^(?:uploads|assets)/[a-zA-Z0-9_./-]+\.(?:jpe?g|png|gif|webp|avif)$~iD',
                $imageurl
            )
            && strpos($imageurl, '..') === false
            && is_file(__DIR__ . '/../' . $imageurl)
        ) {
            $validLocalImage = true;
        }

        if (!$validRemoteImage && !$validLocalImage) {
            $errors[] = 'Enter a valid HTTP/HTTPS image URL or an existing image path under uploads/ or assets/.';
        }
    }

    // Insert creator into the database.
    if (empty($errors)) {

        try {
            $statement = $pdo->prepare(
                'INSERT INTO creator (name, website, imageurl)
                 VALUES (:name, :website, :imageurl)'
            );

            $statement->execute([
                'name' => $name,
                'website' => $website === '' ? null : $website,
                'imageurl' => $imageurl === '' ? null : $imageurl
            ]);

            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Creator added successfully.'
            ];

            header(
                'Location: /snapspace/admin/manage-creators.php',
                true,
                303
            );
            exit;

        } catch (PDOException $exception) {
            http_response_code(503);

            $errors[] = 'The creator could not be added. Please try again later.';
        }
    }

    if (!empty($errors)) {
        http_response_code(422);
    }
}

require __DIR__ . '/../includes/admin-header.php';
?>

<link rel="stylesheet" href="../css/album.css">

<section class="album-module album-admin">

    <h1>Add Creator</h1>

    <p>
        <a href="manage-creators.php">Back to Creator Management</a>
    </p>

    <?php if (!empty($errors)): ?>

        <div class="album-error" role="alert">

            <h2>Please check the following</h2>

            <ul>
                <?php foreach ($errors as $message): ?>
                    <li><?= $escape($message) ?></li>
                <?php endforeach; ?>
            </ul>

        </div>

    <?php endif; ?>

    <form
        method="post"
        action="add-creator.php"
        class="album-form"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= $escape($_SESSION['creator_csrf_token']) ?>"
        >

        <div>
            <label for="name">Creator Name (required)</label>

            <input
                type="text"
                id="name"
                name="name"
                maxlength="100"
                required
                value="<?= $escape($name) ?>"
                placeholder="Enter creator name"
            >
        </div>

        <div>
            <label for="website">Website (optional)</label>

            <input
                type="url"
                id="website"
                name="website"
                maxlength="255"
                value="<?= $escape($website) ?>"
                placeholder="https://example.com"
            >

            <p class="album-help">
                Enter a complete website URL starting with http:// or https://.
            </p>
        </div>

        <div>
            <label for="imageurl">Creator Image URL or Path (optional)</label>

            <input
                type="text"
                id="imageurl"
                name="imageurl"
                maxlength="255"
                value="<?= $escape($imageurl) ?>"
                placeholder="uploads/creators/example.jpg"
            >

            <p class="album-help">
                Enter an HTTP/HTTPS image URL or an existing image path
                under uploads/ or assets/. This form does not upload files.
                Leave blank if no image is available.
            </p>
        </div>

        <div class="album-actions">

            <button class="album-button" type="submit">
                Add Creator
            </button>

            <a href="manage-creators.php">
                Cancel
            </a>

        </div>

    </form>

</section>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
