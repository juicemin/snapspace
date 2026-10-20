<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Add Album';
$activeSection = 'albums';
$adminName = $_SESSION['admin_name'] ?? 'Admin';

/*
Retrieve creators for the dropdown.
Validate form submission.
Insert the album.
*/

require_once __DIR__ . '/../includes/admin-header.php';
?>

<!-- Add Album form -->

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>