<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/includes/db.php';

if (!empty($_SESSION['user_id'])) {
    if (($_SESSION['role'] ?? '') === 'admin') {
        header('Location: /snapspace/admin/dashboard.php');
    } else {
        header('Location: /snapspace/index.php');
    }

    exit;
}

$errorMessage = '';
$username = '';

if (!empty($_SESSION['login_error'])) {
    $errorMessage = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' && $password === '') {
        $errorMessage = 'Please enter your username and password.';
    } elseif ($username === '') {
        $errorMessage = 'Please enter your username.';
    } elseif ($password === '') {
        $errorMessage = 'Please enter your password.';
    } else {
        try {
            $statement = $pdo->prepare(
                'SELECT iduser, username, password_hash, role
                 FROM user_account
                 WHERE username = :username
                 LIMIT 1'
            );

            $statement->execute([
                'username' => $username
            ]);

            $account = $statement->fetch();

            if (
                $account &&
                password_verify(
                    $password,
                    $account['password_hash']
                )
            ) {
                session_regenerate_id(true);

                $_SESSION['user_id'] = (int) $account['iduser'];
                $_SESSION['username'] = $account['username'];
                $_SESSION['role'] = $account['role'];

                /*
                 * Temporary compatibility for the Album module,
                 * which still checks admin_id and admin_name.
                 */
                unset(
                    $_SESSION['admin_id'],
                    $_SESSION['admin_name']
                );

                if ($account['role'] === 'admin') {
                    $_SESSION['admin_id'] =
                        (int) $account['iduser'];

                    $_SESSION['admin_name'] =
                        $account['username'];

                    header(
                        'Location: /snapspace/admin/dashboard.php'
                    );
                } else {
                    header('Location: /snapspace/index.php');
                }

                exit;
            }

            $errorMessage = 'Invalid username or password.';
        } catch (PDOException $exception) {
            $errorMessage =
                'Login is temporarily unavailable. Please try again.';
        }
    }
}

$pageTitle = 'Login';
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

        <h1>Login</h1>

        <p class="admin-login-description">
            Sign in to access Snap Space.
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

                <div class="admin-password-wrapper">
                    <input
                        class="admin-form-control"
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        required
                    >

                    <button
                        class="admin-password-toggle"
                        type="button"
                        id="passwordToggle"
                        aria-label="Show password"
                        aria-pressed="false"
                    >
                        Show
                    </button>
                </div>
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

<script>
    const passwordInput =
        document.getElementById('password');

    const passwordToggle =
        document.getElementById('passwordToggle');

    passwordToggle.addEventListener('click', function () {
        const passwordIsHidden =
            passwordInput.type === 'password';

        if (passwordIsHidden) {
            passwordInput.type = 'text';
            passwordToggle.textContent = 'Hide';
            passwordToggle.setAttribute(
                'aria-label',
                'Hide password'
            );
            passwordToggle.setAttribute(
                'aria-pressed',
                'true'
            );
        } else {
            passwordInput.type = 'password';
            passwordToggle.textContent = 'Show';
            passwordToggle.setAttribute(
                'aria-label',
                'Show password'
            );
            passwordToggle.setAttribute(
                'aria-pressed',
                'false'
            );
        }
    });
</script>

</body>
</html>
