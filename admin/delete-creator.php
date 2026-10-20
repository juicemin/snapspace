<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /snapspace/admin/manage-creators.php');
    exit;
}

/*
Validate idcreator and perform the DELETE query here.
*/

header('Location: /snapspace/admin/manage-creators.php');
exit;