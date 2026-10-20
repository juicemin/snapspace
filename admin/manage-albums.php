<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Manage Albums';
$activeSection = 'albums';
$adminName = $_SESSION['admin_name'] ?? 'Admin';

/*
Album SELECT query will be added here.
*/

require_once __DIR__ . '/../includes/admin-header.php';
?>

<!-- Album management table -->

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>