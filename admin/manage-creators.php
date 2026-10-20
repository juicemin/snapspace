<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Manage Creators';
$activeSection = 'creators';
$adminName = $_SESSION['admin_name'] ?? 'Admin';

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-page-heading">
    <div>
        <h1>Manage Creators</h1>

        <p>View and maintain creator records.</p>
    </div>

    <a
        class="admin-button"
        href="/snapspace/admin/add-creator.php"
    >
        + Add Creator
    </a>
</div>

<div class="admin-table-wrapper">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Image</th>
                <th>ID</th>
                <th>Creator Name</th>
                <th>Website</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>
            <tr>
                <td>Thumbnail</td>
                <td>01</td>
                <td>Sample Creator</td>
                <td>example.com</td>
                <td>View · Edit · Delete</td>
            </tr>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>