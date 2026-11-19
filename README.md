# Snap Space

Snap Space is a PHP and MySQL creator portfolio and online photography gallery developed for IBM4202 Web Programming.

## Requirements

- XAMPP
- PHP
- MySQL
- Web browser
- Git

## Local Installation

1. Clone the repository into `C:\xampp\htdocs\snapspace`.
2. Start Apache and MySQL in XAMPP.
3. Open phpMyAdmin.
4. Import `database/IBM4202.sql`.
5. Open `http://localhost/snapspace/`.

## Database

The project uses the `IBM4202` database with these tables:

- `creator`
- `album`
- `photo`
- `administrator`

The development administrator username is `admin`.

The temporary development password is communicated privately to group members and must not be committed to GitHub.

## Branch Workflow

- `main` contains stable completed work.
- `develop` contains integrated development work.
- Each member works on their assigned branch.
- Pull or merge the latest `develop` before starting.
- Commit only files related to the assigned task.
- Push completed work to the member branch.
- Create a pull request from the member branch into `develop`.
- Merge `develop` into `main` only after integration testing.

## Shared Components

Public pages use:

- `includes/header.php`
- `includes/footer.php`
- `css/style.css`

Protected administration pages use:

- `includes/auth.php`
- `includes/admin-header.php`
- `includes/admin-sidebar.php`
- `includes/admin-footer.php`
- `css/admin.css`

Do not change shared components without informing the group.

## Administration Page Structure

Protected administration pages must include authentication before producing HTML:

```php
<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Page Name';
$activeSection = 'dashboard';
$adminName = $_SESSION['admin_name'] ?? 'Admin';

require_once __DIR__ . '/../includes/admin-header.php';
?>

<!-- Unique page content -->

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>