<?php
// admin/edit-photo.php - Edit Existing Photo Record
$pageTitle = "Edit Photo - Snap Space";
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$errors = [];
$idphoto = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($idphoto) || !is_numeric($idphoto)) {
    header("Location: manage-photos.php");
    exit;
}

// 1. Fetch current photo record
$photo = null;
if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM photo WHERE idphoto = :idphoto LIMIT 1");
        $stmt->execute([':idphoto' => $idphoto]);
        $photo = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errors[] = "Database retrieval failed.";
    }
}

if (!$photo) {
    header("Location: manage-photos.php?error=notfound");
    exit;
}

// 2. Fetch creators and albums for form options
$creators = [];
$albums = [];
if (isset($pdo)) {
    try {
        $creators = $pdo->query("SELECT idcreator, name FROM creator ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $albums = $pdo->query("SELECT idalbum, title, idcreator FROM album ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errors[] = "Failed to load relationship options.";
    }
}

// Initialise form values from database
$title = $photo['title'];
$comment = $photo['comment'] ?? '';
$idcreator = $photo['idcreator'] ?? '';
$idalbum = $photo['idalbum'] ?? '';
$imageurl = $photo['imageurl'];

// 3. Process Edit POST Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $comment = trim($_POST['comment'] ?? '');
    $idcreator = trim($_POST['idcreator'] ?? '');
    $idalbum = trim($_POST['idalbum'] ?? '');

    if (empty($title)) {
        $errors[] = "Photo title is required.";
    } elseif (strlen($title) > 45) {
        $errors[] = "Photo title cannot exceed 45 characters.";
    }

    if (strlen($comment) > 140) {
        $errors[] = "Photo comment cannot exceed 140 characters.";
    }

    // Relational Integrity Validation: Album must belong to creator
    if (!empty($idalbum) && !empty($idcreator)) {
        try {
            $checkStmt = $pdo->prepare("SELECT idalbum FROM album WHERE idalbum = :idalbum AND idcreator = :idcreator");
            $checkStmt->execute([':idalbum' => $idalbum, ':idcreator' => $idcreator]);
            if (!$checkStmt->fetch()) {
                $errors[] = "Relational Error: The chosen album does not belong to the selected creator.";
            }
        } catch (PDOException $e) {
            $errors[] = "Validation query error: " . $e->getMessage();
        }
    }

    // Optional Image Replacement
    if (isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['photo_file']['tmp_name'];
        $fileName = $_FILES['photo_file']['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ext, $allowedExtensions)) {
            $newFileName = 'photo_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
            $uploadTargetDir = __DIR__ . '/../uploads/photos/';
            if (!is_dir($uploadTargetDir)) {
                mkdir($uploadTargetDir, 0755, true);
            }

            if (move_uploaded_file($fileTmpPath, $uploadTargetDir . $newFileName)) {
                $imageurl = 'uploads/photos/' . $newFileName;
            }
        } else {
            $errors[] = "Invalid image file type. Please upload JPG, PNG, or WebP.";
        }
    }

    // Execute Update
    if (empty($errors) && isset($pdo)) {
        try {
            $updateSql = "UPDATE photo 
                          SET title = :title, 
                              imageurl = :imageurl, 
                              comment = :comment, 
                              idcreator = :idcreator, 
                              idalbum = :idalbum 
                          WHERE idphoto = :idphoto";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([
                ':title' => $title,
                ':imageurl' => $imageurl,
                ':comment' => !empty($comment) ? $comment : null,
                ':idcreator' => !empty($idcreator) ? $idcreator : null,
                ':idalbum' => !empty($idalbum) ? $idalbum : null,
                ':idphoto' => $idphoto,
            ]);

            header("Location: manage-photos.php?msg=updated");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Failed to update record: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-content-header">
    <div class="header-left">
        <h1>Edit Photo Record</h1>
        <p class="subtitle">Modify metadata and relationships for Photo #<?php echo htmlspecialchars($idphoto); ?></p>
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

    <form method="POST" action="edit-photo.php?id=<?php echo htmlspecialchars($idphoto); ?>" enctype="multipart/form-data" class="admin-form">
        <div class="form-group">
            <label for="title">Photo Title: <span class="required">*</span></label>
            <input type="text" name="title" id="title" maxlength="45" required value="<?php echo htmlspecialchars($title); ?>">
        </div>

        <div class="form-group">
            <label>Current Image Preview:</label>
            <div class="preview-box">
                <?php $currentPreview = !empty($imageurl) ? '../' . ltrim(htmlspecialchars($imageurl), '/') : '../assets/images/image-placeholder.png'; ?>
                <img src="<?php echo $currentPreview; ?>" alt="Current Photo" style="max-height: 120px; border-radius: 4px; border: 1px solid #ddd;">
            </div>
            <label for="photo_file" style="margin-top: 0.5rem;">Replace Image (Optional):</label>
            <input type="file" name="photo_file" id="photo_file" accept="image/jpeg,image/png,image/webp">
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
            <small class="form-hint">Selected album must belong to the selected creator.</small>
        </div>

        <div class="form-group">
            <label for="comment">Photo Comment / Story:</label>
            <textarea name="comment" id="comment" rows="4" maxlength="140"><?php echo htmlspecialchars($comment); ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Photo</button>
            <a href="manage-photos.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>