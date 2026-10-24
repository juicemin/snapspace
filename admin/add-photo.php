<?php
// admin/add-photo.php - Create New Photo Record with Relational Integrity Validation
$pageTitle = "Add Photo - Snap Space";
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$errors = [];
$title = '';
$comment = '';
$idcreator = '';
$idalbum = '';
$imageurl = '';

// 1. Fetch available creators and albums for form selection
$creators = [];
$albums = [];

if (isset($pdo)) {
    try {
        $creators = $pdo->query("SELECT idcreator, name FROM creator ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $albums = $pdo->query("SELECT idalbum, title, idcreator FROM album ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errors[] = "Failed to load database relational data.";
    }
}

// 2. Handle POST submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $comment = trim($_POST['comment'] ?? '');
    $idcreator = trim($_POST['idcreator'] ?? '');
    $idalbum = trim($_POST['idalbum'] ?? '');

    // Server-side validation
    if (empty($title)) {
        $errors[] = "Photo title is required.";
    } elseif (strlen($title) > 45) {
        $errors[] = "Photo title cannot exceed 45 characters.";
    }

    if (strlen($comment) > 140) {
        $errors[] = "Photo comment cannot exceed 140 characters.";
    }

    // 3. Core Relational Integrity Check: Selected album must belong to the selected creator
    if (!empty($idalbum) && !empty($idcreator)) {
        try {
            $checkStmt = $pdo->prepare("SELECT idalbum FROM album WHERE idalbum = :idalbum AND idcreator = :idcreator");
            $checkStmt->execute([':idalbum' => $idalbum, ':idcreator' => $idcreator]);
            if (!$checkStmt->fetch()) {
                $errors[] = "Relational Error: The chosen album does not belong to the selected creator.";
            }
        } catch (PDOException $e) {
            $errors[] = "Validation query failed: " . $e->getMessage();
        }
    }

    // 4. File Upload Handling
    if (isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['photo_file']['tmp_name'];
        $fileName = $_FILES['photo_file']['name'];
        $fileSize = $_FILES['photo_file']['size'];
        $fileType = mime_content_type($fileTmpPath);

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($fileType, $allowedMimeTypes) && in_array($ext, $allowedExtensions)) {
            // Generate clean unique filename
            $newFileName = 'photo_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
            $uploadTargetDir = __DIR__ . '/../uploads/photos/';
            
            if (!is_dir($uploadTargetDir)) {
                mkdir($uploadTargetDir, 0755, true);
            }

            $destination = $uploadTargetDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $destination)) {
                $imageurl = 'uploads/photos/' . $newFileName;
            } else {
                $errors[] = "Failed to upload and save image to server directory.";
            }
        } else {
            $errors[] = "Invalid image file format. Only JPG, PNG, and WebP are accepted.";
        }
    } else {
        // Fallback default placeholder if no file uploaded
        $imageurl = 'assets/images/image-placeholder.png';
    }

    // 5. Insert Record if validation passes
    if (empty($errors) && isset($pdo)) {
        try {
            $insertSql = "INSERT INTO photo (title, imageurl, comment, idcreator, idalbum) 
                          VALUES (:title, :imageurl, :comment, :idcreator, :idalbum)";
            $insertStmt = $pdo->prepare($insertSql);
            $insertStmt->execute([
                ':title' => $title,
                ':imageurl' => $imageurl,
                ':comment' => !empty($comment) ? $comment : null,
                ':idcreator' => !empty($idcreator) ? $idcreator : null,
                ':idalbum' => !empty($idalbum) ? $idalbum : null,
            ]);

            header("Location: manage-photos.php?msg=added");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Database insertion error: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-content-header">
    <div class="header-left">
        <h1>Add New Photo</h1>
        <p class="subtitle">Upload photographic work and assign corresponding database relationships.</p>
    </div>
    <div class="header-right">
        <a href="manage-photos.php" class="btn btn-secondary">&larr; Back to Photos</a>
    </div>
</div>

<div class="admin-form-container">
    <?php if (!empty($errors)): ?>
        <div class="admin-error-box">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="add-photo.php" enctype="multipart/form-data" class="admin-form">
        <div class="form-group">
            <label for="title">Photo Title: <span class="required">*</span></label>
            <input type="text" name="title" id="title" maxlength="45" required value="<?php echo htmlspecialchars($title); ?>" placeholder="Enter photo title...">
            <small class="form-hint">Maximum 45 characters.</small>
        </div>

        <div class="form-group">
            <label for="photo_file">Photo Image File:</label>
            <input type="file" name="photo_file" id="photo_file" accept="image/jpeg,image/png,image/webp">
            <small class="form-hint">Relative path will be stored automatically. JPG, PNG, or WebP.</small>
        </div>

        <div class="form-group">
            <label for="idcreator">Associated Creator:</label>
            <select name="idcreator" id="idcreator">
                <option value="">-- No Creator Selected --</option>
                <?php foreach ($creators as $creator): ?>
                    <option value="<?php echo htmlspecialchars($creator['idcreator']); ?>" <?php echo ($idcreator == $creator['idcreator']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($creator['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="idalbum">Associated Album:</label>
            <select name="idalbum" id="idalbum">
                <option value="">-- No Album Selected (Standalone) --</option>
                <?php foreach ($albums as $album): ?>
                    <option value="<?php echo htmlspecialchars($album['idalbum']); ?>" <?php echo ($idalbum == $album['idalbum']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($album['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small class="form-hint">Note: If selected, this album must belong to the chosen creator.</small>
        </div>

        <div class="form-group">
            <label for="comment">Photo Comment / Story:</label>
            <textarea name="comment" id="comment" rows="4" maxlength="140" placeholder="Optional comments, story or context..."><?php echo htmlspecialchars($comment); ?></textarea>
            <small class="form-hint">Maximum 140 characters.</small>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Photo Record</button>
            <a href="manage-photos.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>