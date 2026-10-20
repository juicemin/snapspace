<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: /snapspace/admin/dashboard.php');
    exit;
}

$errorMessage = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $errorMessage = 'Please enter your username and password.';
    } else {
        $statement = $pdo->prepare(
            'SELECT idadministrator, username, password_hash
             FROM administrator
             WHERE username = :username
             LIMIT 1'
        );

        $statement->execute([
            'username' => $username
        ]);

        $administrator = $statement->fetch();

        $validPassword =
            $administrator &&
            password_verify(
                $password,
                $administrator['password_hash']
            );

        if ($validPassword) {
            session_regenerate_id(true);

            $_SESSION['admin_id'] =
                (int) $administrator['idadministrator'];

            $_SESSION['admin_name'] =
                $administrator['username'];

            header('Location: /snapspace/admin/dashboard.php');
            exit;
        }

        $errorMessage = 'Invalid username or password.';
    }
}

$pageTitle = 'Administrator Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?= htmlspecialchars($pageTitle) ?> | Snap Space</title>

    <link
        rel="stylesheet"
        href="/snapspace/css/admin.css"
    >
</head>

<body class="admin-login-body">

<main class="admin-login-container">

    <section class="admin-login-card">

        <a
            class="admin-login-brand"
            href="/snapspace/index.php"
        >
            Snap Space
        </a>

        <h1>Administrator Login</h1>

        <p class="admin-login-description">
            Sign in to manage creators, albums and photos.
        </p>

        <?php if ($errorMessage !== ''): ?>
            <div class="admin-alert admin-alert-error">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="">

            <div class="admin-form-group">
                <label for="username">Username</label>

                <input
                    class="admin-form-control"
                    type="text"
                    id="username"
                    name="username"
                    value="<?= htmlspecialchars($username) ?>"
                    autocomplete="username"
                    required
                >
            </div>

            <div class="admin-form-group">
                <label for="password">Password</label>

                <input
                    class="admin-form-control"
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button
                class="admin-button admin-login-button"
                type="submit"
            >
                Log In
            </button>

        </form>

        <a
            class="admin-return-link"
            href="/snapspace/index.php"
        >
            Return to Snap Space
        </a>

    </section>

</main>

</body>
</html>