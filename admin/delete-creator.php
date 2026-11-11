
<?php

require_once __DIR__ . '/../includes/auth.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/*
 * Only allow POST requests.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method Not Allowed. Please use the Delete button.');
}

/*
 * Verify administrator authentication.
 */
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

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(503);
    exit('The database connection is unavailable.');
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/*
 * Verify CSRF token.
 */
$submittedToken = $_POST['csrf_token'] ?? '';

if (
    !is_string($submittedToken) ||
    empty($_SESSION['creator_csrf_token']) ||
    !hash_equals($_SESSION['creator_csrf_token'], $submittedToken)
) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Invalid or expired request. Please try again.'
    ];

    header('Location: /snapspace/admin/manage-creators.php', true, 303);
    exit;
}

/*
 * Validate Creator ID.
 */
$id = $_POST['id'] ?? '';

if (
    !is_string($id) ||
    filter_var(
        $id,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    ) === false
) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Invalid Creator ID.'
    ];

    header('Location: /snapspace/admin/manage-creators.php', true, 303);
    exit;
}

$creatorId = (int) $id;

try {

    $pdo->beginTransaction();

    /*
     * Lock and verify the creator record.
     */
    $statement = $pdo->prepare(
        'SELECT idcreator
         FROM creator
         WHERE idcreator = :id
         FOR UPDATE'
    );

    $statement->execute([
        'id' => $creatorId
    ]);

    $creator = $statement->fetch();

    if (!$creator) {
        $pdo->rollBack();

        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => 'Creator not found.'
        ];

        header('Location: /snapspace/admin/manage-creators.php', true, 303);
        exit;
    }

    /*
     * Check related albums.
     */
    $statement = $pdo->prepare(
        'SELECT COUNT(*)
         FROM album
         WHERE idcreator = :id'
    );

    $statement->execute([
        'id' => $creatorId
    ]);

    $albumCount = (int) $statement->fetchColumn();

    /*
     * Check related photos.
     */
    $statement = $pdo->prepare(
        'SELECT COUNT(*)
         FROM photo
         WHERE idcreator = :id'
    );

    $statement->execute([
        'id' => $creatorId
    ]);

    $photoCount = (int) $statement->fetchColumn();

    if ($albumCount > 0 || $photoCount > 0) {

        $pdo->rollBack();

        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => 'This creator cannot be deleted because related albums or photos still exist.'
        ];

        header('Location: /snapspace/admin/manage-creators.php', true, 303);
        exit;
    }

    /*
     * Delete the creator.
     */
    $statement = $pdo->prepare(
        'DELETE FROM creator
         WHERE idcreator = :id'
    );

    $statement->execute([
        'id' => $creatorId
    ]);

    if ($statement->rowCount() !== 1) {
        throw new RuntimeException('Creator deletion failed.');
    }

    $pdo->commit();

    $_SESSION['flash_message'] = [
        'type' => 'success',
        'message' => 'Creator deleted successfully.'
    ];

} catch (Throwable $exception) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'Delete Creator error: ' . $exception->getMessage()
    );

    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'The creator could not be deleted. Please try again.'
    ];
}

header('Location: /snapspace/admin/manage-creators.php', true, 303);
exit;
