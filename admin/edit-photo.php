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

<!-- Scoped Admin Form Styling -->
<style>
.form-page-container {
    width: 100%;
    max-width: 100%;
    padding: 1rem 0 3rem 0;
    box-sizing: border-box;
}

.form-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.form-page-header h1 {
    font-size: 1.8rem;
    color: #3b281c;
    margin: 0 0 0.3rem 0;
}

.form-page-header .subtitle {
    color: #6c5a4f;
    font-size: 0.95rem;
    margin: 0;
}

.btn-back-link {
    display: inline-block;
    padding: 0.5rem 1rem;
    background-color: #ede7e1;
    color: #4a382c !important;
    text-decoration: none;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 600;
    transition: background-color 0.2s;
}

.btn-back-link:hover {
    background-color: #ded6ce;
}

.card-form-box {
    background: #ffffff;
    border: 1px solid #e0d8d0;
    border-radius: 8px;
    padding: 2rem 2.5rem;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
}

.admin-alert-danger {
    background-color: #fbeae8;
    border-left: 4px solid #c93b2b;
    padding: 1rem 1.25rem;
    border-radius: 4px;
    margin-bottom: 1.75rem;
    color: #8c281d;
}

.admin-alert-danger ul {
    margin: 0;
    padding-left: 1.25rem;
}

.admin-alert-danger li {
    font-size: 0.9rem;
    margin-bottom: 0.3rem;
}

.form-row {
    margin-bottom: 1.4rem;
}

.form-row label {
    display: block;
    font-weight: 700;
    font-size: 0.9rem;
    color: #3b281c;
    margin-bottom: 0.45rem;
}

.form-row .req {
    color: #c93b2b;
}

.input-text, .input-select, .input-textarea {
    width: 100%;
    padding: 0.65rem 0.9rem;
    border: 1px solid #c7b9b0;
    border-radius: 4px;
    font-size: 0.95rem;
    color: #2b1f17;
    background-color: #faf8f5;
    box-sizing: border-box;
    transition: border-color 0.2s, background-color 0.2s;
}

.input-text:focus, .input-select:focus, .input-textarea:focus {
    border-color: #5c4333;
    background-color: #ffffff;
    outline: none;
}

.edit-preview-card {
    display: flex;
    gap: 1.25rem;
    align-items: center;
    background-color: #faf8f5;
    padding: 0.85rem;
    border: 1px solid #e2dad2;
    border-radius: 6px;
    margin-bottom: 0.75rem;
}

.edit-preview-img {
    width: 90px;
    height: 70px;
    object-fit: cover;
    border-radius: 4px;
    border: 1px solid #ded6ce;
    display: block;
}

.preview-desc {
    font-size: 0.85rem;
    color: #6c5a4f;
}

.input-file-control {
    width: 100%;
    padding: 0.6rem;
    background: #faf8f5;
    border: 1px dashed #c7b9b0;
    border-radius: 4px;
    box-sizing: border-box;
}

.field-hint {
    display: block;
    font-size: 0.8rem;
    color: #7b6c62;
    margin-top: 0.35rem;
}

.form-footer-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #ede7e1;
}

.btn-submit-action {
    padding: 0.7rem 1.6rem;
    background-color: #3b281c;
    color: #ffffff !important;
    border: none;
    border-radius: 4px;
    font-size: 0.95rem;
    font-weight: 700;
    cursor: pointer;
    transition: background-color 0.2s;
}

.btn-submit-action:hover {
    background-color: #553c2b;
}

.btn-cancel-action {
    display: inline-block;
    padding: 0.7rem 1.4rem;
    background-color: transparent;
    color: #6c5a4f !important;
    text-decoration: none;
    font-size: 0.95rem;
    font-weight: 600;
}

.btn-cancel-action:hover {
    color: #2b1f17 !important;
    text-decoration: underline;
}
</style>

<div class="form-page-container">
    <div class="form-page-header">
        <div>
            <h1>Edit Photo Record</h1>
            <p class="subtitle">Modify metadata and relationships for Photo #<?php echo htmlspecialchars($idphoto); ?></p>
        </div>
        <a href="manage-photos.php" class="btn-back-link">&larr; Back to Photos</a>
    </div>

    <div class="card-form-box">
        <?php if (!empty($errors)): ?>
            <div class="admin-alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="edit-photo.php?id=<?php echo htmlspecialchars($idphoto); ?>" enctype="multipart/form-data">
            <div class="form-row">
                <label for="title">Photo Title <span class="req">*</span></label>
                <input type="text" name="title" id="title" class="input-text" maxlength="45" required value="<?php echo htmlspecialchars($title); ?>">
                <span class="field-hint">Maximum 45 characters.</span>
            </div>

            <div class="form-row">
                <label>Current Image & Replacement</label>
                <div class="edit-preview-card">
                    <?php 
                        $rawUrl = $imageurl ?? '';
                        $currentPreview = !empty($rawUrl) ? ((strpos($rawUrl, 'http') === 0) ? $rawUrl : '../' . ltrim($rawUrl, '/')) : '../assets/images/image-placeholder.png'; 
                    ?>
                    <img src="<?php echo htmlspecialchars($currentPreview); ?>" alt="Current Photo" class="edit-preview-img" onerror="this.onerror=null; this.src='../assets/images/image-placeholder.png';">
                    <div class="preview-desc">
                        <strong>Current File Reference:</strong><br>
                        <code><?php echo htmlspecialchars($imageurl ?: 'Default placeholder'); ?></code>
                    </div>
                </div>
                <input type="file" name="photo_file" id="photo_file" class="input-file-control" accept="image/jpeg,image/png,image/webp">
                <span class="field-hint">Leave blank if you wish to keep the existing image.</span>
            </div>

            <div class="form-row">
                <label for="idcreator">Associated Creator</label>
                <select name="idcreator" id="idcreator" class="input-select">
                    <option value="">-- No Creator Selected --</option>
                    <?php foreach ($creators as $creator): ?>
                        <option value="<?php echo htmlspecialchars($creator['idcreator']); ?>" <?php echo ($idcreator == $creator['idcreator']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($creator['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="field-hint">Update creator association for this photograph.</span>
            </div>

            <div class="form-row">
                <label for="idalbum">Associated Album</label>
                <select name="idalbum" id="idalbum" class="input-select">
                    <option value="">-- No Album Selected (Standalone) --</option>
                    <?php foreach ($albums as $album): ?>
                        <option value="<?php echo htmlspecialchars($album['idalbum']); ?>" <?php echo ($idalbum == $album['idalbum']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($album['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="field-hint"><strong>Important:</strong> If selected, this album must strictly belong to the chosen creator.</span>
            </div>

            <div class="form-row">
                <label for="comment">Photo Story / Comment</label>
                <textarea name="comment" id="comment" class="input-textarea" rows="4" maxlength="140"><?php echo htmlspecialchars($comment); ?></textarea>
                <span class="field-hint">Maximum 140 characters.</span>
            </div>

            <div class="form-footer-actions">
                <button type="submit" class="btn-submit-action">Update Photo Record</button>
                <a href="manage-photos.php" class="btn-cancel-action">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>