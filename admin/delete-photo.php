<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /snapspace/admin/manage-photos.php');
    exit;
}

/*
Validate idphoto and perform the DELETE query.
*/

header('Location: /snapspace/admin/manage-photos.php');
exit;