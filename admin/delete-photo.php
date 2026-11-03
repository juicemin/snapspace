<?php
// admin/delete-photo.php - Secure Photo Record Deletion Script
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Strict POST method enforcement for deletion safety
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idphoto = isset($_POST['id']) ? trim($_POST['id']) : '';

    if (!empty($idphoto) && is_numeric($idphoto) && isset($pdo)) {
        try {
            // 1. Optionally delete local image file if not placeholder
            $imgStmt = $pdo->prepare("SELECT imageurl FROM photo WHERE idphoto = :idphoto LIMIT 1");
            $imgStmt->execute([':idphoto' => $idphoto]);
            $photo = $imgStmt->fetch(PDO::FETCH_ASSOC);

            if ($photo && !empty($photo['imageurl'])) {
                $filePath = __DIR__ . '/../' . ltrim($photo['imageurl'], '/');
                if (file_exists($filePath) && strpos($photo['imageurl'], 'assets/images/') === false) {
                    @unlink($filePath);
                }
            }

            // 2. Delete database record
            $deleteStmt = $pdo->prepare("DELETE FROM photo WHERE idphoto = :idphoto");
            $deleteStmt->execute([':idphoto' => $idphoto]);

            header("Location: manage-photos.php?msg=deleted");
            exit;
        } catch (PDOException $e) {
            header("Location: manage-photos.php?error=delfailed");
            exit;
        }
    }
}

// Redirect back if accessed directly via GET or without valid parameters
header("Location: manage-photos.php");
exit;