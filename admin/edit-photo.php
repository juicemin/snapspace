<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Edit Photo';
$activeSection = 'photos';
$adminName = $_SESSION['admin_name'] ?? 'Admin';

/*
Retrieve the photo.
Retrieve creators and albums.
Validate and update the photo.
*/

require_once __DIR__ . '/../includes/admin-header.php';
?>

<!-- Edit Photo form -->

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>