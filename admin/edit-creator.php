<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Edit Creator';
$activeSection = 'creators';
$adminName = $_SESSION['admin_name'] ?? 'Admin';

/*
SELECT and UPDATE queries will be added here.
*/

require_once __DIR__ . '/../includes/admin-header.php';
?>

<!-- Edit Creator form -->

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>