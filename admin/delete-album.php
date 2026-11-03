<?php
require_once __DIR__ . '/../includes/auth.php';
// Keep a fail-closed guard until the shared authentication contract is finalized.
// TODO: Review duplicate checks and align unauthenticated redirects with shared auth.
if (session_status() !== PHP_SESSION_ACTIVE) {
    http_response_code(503);
    exit('Album administration is unavailable until shared authentication starts the session.');
}
$sessionAdminId = $_SESSION['admin_id'] ?? null;
if ((!is_int($sessionAdminId) && !is_string($sessionAdminId))
    || filter_var($sessionAdminId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
    http_response_code(403);
    exit('Administrator authentication is required.');
}
require_once __DIR__ . '/../includes/db.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(503);
    exit('Album administration is unavailable until the shared PDO database connection is configured.');
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$escape = static function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
$positiveId = static function ($value) {
    return is_string($value) && preg_match('/^[1-9][0-9]*$/D', $value)
        ? filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
};
if (empty($_SESSION['album_csrf_token'])) { $_SESSION['album_csrf_token'] = bin2hex(random_bytes(32)); }
$validToken = static function () {
    return isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
        && hash_equals($_SESSION['album_csrf_token'], $_POST['csrf_token']);
};
$activeSection = 'albums';
$adminName = $_SESSION['admin_name'] ?? 'Admin';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Album deletion only accepts POST requests. Return to album management to delete an album.');
}
$id = $positiveId($_POST['id'] ?? null);
$message = '';
if (!$validToken()) { $message = 'The delete request could not be verified. Please try again from album management.'; }
elseif ($id === false) { $message = 'A valid album ID is required for deletion.'; }
else {
    try {
        $pdo->beginTransaction();
        $statement = $pdo->prepare('SELECT idalbum FROM album WHERE idalbum = :id FOR UPDATE');
        $statement->execute(['id' => $id]);
        if (!$statement->fetch(PDO::FETCH_ASSOC)) {
            $pdo->rollBack();
            $message = 'This album does not exist or has already been deleted.';
        } else {
            // The existing database foreign key cascades deletion to associated photo records.
            $statement = $pdo->prepare('DELETE FROM album WHERE idalbum = :id');
            $statement->execute(['id' => $id]);
            $pdo->commit();
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Album and its associated photo records deleted successfully. Image files were not deleted.'];
        }
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $message = 'The album could not be deleted. Please try again later.';
    }
}
if ($message !== '') { $_SESSION['flash_message'] = ['type' => 'error', 'message' => $message]; }
header('Location: /snapspace/admin/manage-albums.php', true, 303);
exit;
