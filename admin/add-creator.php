<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Add Creator';
$activeSection = 'creators';
$adminName = $_SESSION['admin_name'] ?? 'Admin';

/*
POST validation and INSERT query will be added here.
*/

require_once __DIR__ . '/../includes/admin-header.php';
?>

<!-- Add Creator form -->

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>