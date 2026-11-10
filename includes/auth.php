<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/*
 * A guest must log in first.
 */
if (empty($_SESSION['user_id'])) {
    $_SESSION['login_error'] =
        'Please log in to access the administration area.';

    header('Location: /snapspace/login.php');
    exit;
}

/*
 * A logged-in ordinary user cannot access administration.
 */
if (($_SESSION['role'] ?? '') !== 'admin') {
    $_SESSION['access_error'] =
        'Administrator access is required.';

    header('Location: /snapspace/index.php');
    exit;
}