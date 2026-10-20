<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Edit Album';
$activeSection = 'albums';
$adminName = $_SESSION['admin_name'] ?? 'Admin';

/*
Retrieve the album.
Retrieve creators for the dropdown.
Update the album after submission.
*/

require_once __DIR__ . '/../includes/admin-header.php';
?>

<!-- Edit Album form -->

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>