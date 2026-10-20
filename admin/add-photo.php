<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Add Photo';
$activeSection = 'photos';
$adminName = $_SESSION['admin_name'] ?? 'Admin';

/*
Retrieve creators and albums.
Validate the selected relationship.
Insert the photo.
*/

require_once __DIR__ . '/../includes/admin-header.php';
?>

<!-- Add Photo form -->

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>