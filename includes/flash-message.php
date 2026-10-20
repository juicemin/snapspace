<?php

if (empty($_SESSION['flash_message'])) {
    return;
}

$flashMessage = $_SESSION['flash_message'];
unset($_SESSION['flash_message']);

$messageType = $flashMessage['type'] ?? 'success';
$messageText = $flashMessage['message'] ?? '';

$allowedTypes = [
    'success',
    'error',
    'warning'
];

if (!in_array($messageType, $allowedTypes, true)) {
    $messageType = 'success';
}
?>

<?php if ($messageText !== ''): ?>
    <div
        class="admin-alert admin-alert-<?= htmlspecialchars($messageType) ?>"
        role="alert"
    >
        <?= htmlspecialchars($messageText) ?>
    </div>
<?php endif; ?>