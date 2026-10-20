<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Manage Photos';
$activeSection = 'photos';
$adminName = $_SESSION['admin_name'] ?? 'Admin';

/*
Photo SELECT query with creator and album joins will be added here.
*/

require_once __DIR__ . '/../includes/admin-header.php';
?>

<!-- Photo management table -->

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>