<?php
// admin/manage-photos.php - Photo Management Table with Custom Confirmation Modal
$pageTitle = "Manage Photos - Snap Space";
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/admin-header.php';

// 1. Fetch creators and albums for search filter dropdowns
$creators = [];
$albums = [];

if (isset($pdo)) {
    try {
        $creators = $pdo->query("SELECT idcreator, name FROM creator ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $albums = $pdo->query("SELECT idalbum, title FROM album ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $creators = [];
        $albums = [];
    }
}

// 2. Capture and sanitize search/filter parameters
$searchKeyword = isset($_GET['search']) ? trim($_GET['search']) : '';
$selectedCreator = isset($_GET['creator']) ? trim($_GET['creator']) : '';
$selectedAlbum = isset($_GET['album']) ? trim($_GET['album']) : '';

// 3. Build dynamic query for photos
$sql = "SELECT p.idphoto, p.title AS photo_title, p.imageurl, p.comment,
               c.idcreator, c.name AS creator_name,
               a.idalbum, a.title AS album_title
        FROM photo p
        LEFT JOIN creator c ON p.idcreator = c.idcreator
        LEFT JOIN album a ON p.idalbum = a.idalbum
        WHERE 1=1";

$params = [];

if (!empty($searchKeyword)) {
    $sql .= " AND (p.title LIKE :keyword OR p.comment LIKE :keyword)";
    $params[':keyword'] = '%' . $searchKeyword . '%';
}

if (!empty($selectedCreator) && is_numeric($selectedCreator)) {
    $sql .= " AND p.idcreator = :idcreator";
    $params[':idcreator'] = $selectedCreator;
}

if (!empty($selectedAlbum) && is_numeric($selectedAlbum)) {
    $sql .= " AND p.idalbum = :idalbum";
    $params[':idalbum'] = $selectedAlbum;
}

$sql .= " ORDER BY p.idphoto DESC";

$photos = [];
if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $photos = [];
    }
}
?>

<!-- Scoped styling for clean table rendering and custom modal -->
<style>
.admin-photo-container {
    padding: 1rem 0 3rem 0;
    width: 100%;
}

.admin-header-flex {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.25rem;
}

.admin-header-flex h1 {
    font-size: 1.8rem;
    color: #3b281c;
    margin: 0 0 0.3rem 0;
}

.admin-header-flex .subtitle {
    color: #6c5a4f;
    font-size: 0.95rem;
    margin: 0;
}

.btn-add-primary {
    display: inline-block;
    padding: 0.6rem 1.2rem;
    background-color: #3b281c;
    color: #ffffff !important;
    text-decoration: none;
    border-radius: 4px;
    font-weight: 600;
    font-size: 0.9rem;
    transition: background-color 0.2s;
}

.btn-add-primary:hover {
    background-color: #553c2b;
}

/* Filter Toolbar */
.admin-filter-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: flex-end;
    background-color: #f7f4f0;
    padding: 1rem 1.25rem;
    border-radius: 6px;
    border: 1px solid #e2dad2;
    margin-bottom: 1.5rem;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.filter-group label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #4a382c;
}

.filter-input-wrap {
    display: flex;
    gap: 0.4rem;
}

.filter-input-wrap input {
    padding: 0.45rem 0.75rem;
    border: 1px solid #c7b9b0;
    border-radius: 4px;
    font-size: 0.9rem;
    min-width: 200px;
    outline: none;
}

.filter-group select {
    padding: 0.45rem 0.85rem;
    border: 1px solid #c7b9b0;
    border-radius: 4px;
    background-color: #fff;
    font-size: 0.9rem;
    cursor: pointer;
}

.btn-filter-search {
    padding: 0.45rem 1rem;
    background-color: #3b281c;
    color: #ffffff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
}

.btn-filter-reset {
    padding: 0.45rem 0.85rem;
    background-color: #e2dad2;
    color: #4a382c !important;
    text-decoration: none;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 600;
}

/* Table Architecture */
.admin-table-wrapper {
    background: #ffffff;
    border: 1px solid #e0d8d0;
    border-radius: 6px;
    overflow-x: auto;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.admin-data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.95rem;
    text-align: left;
}

.admin-data-table th {
    background-color: #f7f4f0;
    color: #4a382c;
    font-weight: 700;
    padding: 0.85rem 1rem;
    border-bottom: 2px solid #e0d8d0;
    white-space: nowrap;
}

.admin-data-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #eee8e2;
    vertical-align: middle;
    color: #333333;
}

.admin-data-table tbody tr:hover {
    background-color: #faf8f5;
}

.photo-thumb-container {
    width: 70px;
    height: 52px;
    border-radius: 4px;
    overflow: hidden;
    background-color: #ebe5df;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #ded6ce;
}

.photo-thumb-img {
    width: 100% !important;
    height: 100% !important;
    max-width: 70px !important;
    max-height: 52px !important;
    object-fit: cover !important;
    display: block;
}

.action-btn-group {
    display: flex;
    gap: 0.4rem;
    align-items: center;
}

.btn-ctrl {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    font-size: 0.8rem;
    font-weight: 600;
    text-decoration: none !important;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    line-height: 1.2;
}

.btn-ctrl-edit {
    background-color: #4a382c;
    color: #ffffff !important;
}

.btn-ctrl-edit:hover {
    background-color: #31231a;
}

.btn-ctrl-delete {
    background-color: #8c3b3b;
    color: #ffffff !important;
}

.btn-ctrl-delete:hover {
    background-color: #6b2626;
}

.no-data-msg {
    text-align: center;
    padding: 3rem 1rem;
    color: #7b6c62;
    font-style: italic;
}

/* --- High-end Custom Modal Styles --- */
.custom-modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(30, 20, 15, 0.55);
    backdrop-filter: blur(3px);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}

.custom-modal-box {
    background: #ffffff;
    border-radius: 8px;
    width: 90%;
    max-width: 440px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
    text-align: center;
    animation: modalPopIn 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes modalPopIn {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.modal-icon-circle {
    width: 56px;
    height: 56px;
    background-color: #fbeae8;
    color: #c93b2b;
    font-size: 1.7rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.2rem auto;
    font-weight: 700;
}

.custom-modal-box h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.3rem;
    color: #2b1f17;
}

.custom-modal-box p {
    color: #6c5a4f;
    font-size: 0.95rem;
    line-height: 1.5;
    margin: 0 0 1.5rem 0;
}

.custom-modal-box .target-name {
    font-weight: 700;
    color: #c93b2b;
}

.modal-actions-flex {
    display: flex;
    gap: 0.75rem;
    justify-content: center;
}

.btn-modal-cancel {
    padding: 0.65rem 1.4rem;
    background-color: #ede7e1;
    color: #4a382c;
    border: none;
    border-radius: 4px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
}

.btn-modal-confirm {
    padding: 0.65rem 1.4rem;
    background-color: #8c3b3b;
    color: #ffffff;
    border: none;
    border-radius: 4px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
}

.btn-modal-confirm:hover { background-color: #6b2626; }
.btn-modal-cancel:hover { background-color: #ded6ce; }
</style>

<div class="admin-photo-container">
    <div class="admin-header-flex">
        <div>
            <h1>Manage Photos</h1>
            <p class="subtitle">Maintain photo records, validate relationships, and manage uploads.</p>
        </div>
        <div>
            <a href="add-photo.php" class="btn-add-primary">+ Add Photo</a>
        </div>
    </div>

    <!-- Filter and Search Controls (Wireframe 7.10) -->
    <form method="GET" action="manage-photos.php" class="admin-filter-bar">
        <div class="filter-group">
            <label for="search">Filter & Search Records:</label>
            <div class="filter-input-wrap">
                <input type="text" name="search" id="search" placeholder="Search by Title..." value="<?php echo htmlspecialchars($searchKeyword); ?>">
                <button type="submit" class="btn-filter-search">Search</button>
            </div>
        </div>

        <div class="filter-group">
            <label for="creator">Creator:</label>
            <select name="creator" id="creator" onchange="this.form.submit()">
                <option value="">Select a Creator</option>
                <?php foreach ($creators as $c): ?>
                    <option value="<?php echo htmlspecialchars($c['idcreator']); ?>" <?php echo ($selectedCreator == $c['idcreator']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label for="album">Album:</label>
            <select name="album" id="album" onchange="this.form.submit()">
                <option value="">Select an Album</option>
                <?php foreach ($albums as $a): ?>
                    <option value="<?php echo htmlspecialchars($a['idalbum']); ?>" <?php echo ($selectedAlbum == $a['idalbum']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($a['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if (!empty($searchKeyword) || !empty($selectedCreator) || !empty($selectedAlbum)): ?>
            <div class="filter-group" style="align-self: flex-end;">
                <a href="manage-photos.php" class="btn-filter-reset">Clear Filters</a>
            </div>
        <?php endif; ?>
    </form>

    <!-- Formatted Data Table (Wireframe 7.10) -->
    <div class="admin-table-wrapper">
        <table class="admin-data-table">
            <thead>
                <tr>
                    <th style="width: 85px;">Image</th>
                    <th style="width: 60px;">ID</th>
                    <th>Photo Title</th>
                    <th>Creator</th>
                    <th>Album</th>
                    <th style="width: 140px; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($photos)): ?>
                    <?php foreach ($photos as $photo): ?>
                        <?php 
                            $rawUrl = $photo['imageurl'] ?? '';
                            if (!empty($rawUrl)) {
                                $thumbPath = (strpos($rawUrl, 'http') === 0) ? $rawUrl : '../' . ltrim($rawUrl, '/');
                            } else {
                                $thumbPath = '../assets/images/image-placeholder.png';
                            }
                        ?>
                        <tr>
                            <td>
                                <div class="photo-thumb-container">
                                    <img src="<?php echo htmlspecialchars($thumbPath); ?>" 
                                         alt="Photo Thumbnail" 
                                         class="photo-thumb-img"
                                         onerror="this.onerror=null; this.src='../assets/images/image-placeholder.png';">
                                </div>
                            </td>
                            <td>#<?php echo htmlspecialchars($photo['idphoto']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($photo['photo_title']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($photo['creator_name'] ?? 'Not Assigned'); ?></td>
                            <td><?php echo htmlspecialchars($photo['album_title'] ?? 'Standalone Photo'); ?></td>
                            <td style="text-align: center;">
                                <div class="action-btn-group" style="justify-content: center;">
                                    <a href="edit-photo.php?id=<?php echo htmlspecialchars($photo['idphoto']); ?>" class="btn-ctrl btn-ctrl-edit">Edit</a>
                                    <!-- Trigger custom styled modal -->
                                    <button type="button" class="btn-ctrl btn-ctrl-delete" 
                                            onclick="openDeleteModal('<?php echo htmlspecialchars($photo['idphoto']); ?>', '<?php echo htmlspecialchars(addslashes($photo['photo_title'])); ?>')">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="no-data-msg">No photo records found matching your selection.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Custom High-End Delete Modal Component -->
<div id="deleteModal" class="custom-modal-overlay">
    <div class="custom-modal-box">
        <div class="modal-icon-circle">!</div>
        <h3>Delete Photograph</h3>
        <p>Are you sure you want to permanently delete <span id="modalPhotoName" class="target-name"></span>? This action cannot be reversed.</p>
        
        <form id="deletePhotoForm" method="POST" action="delete-photo.php">
            <input type="hidden" name="id" id="modalPhotoId" value="">
            <div class="modal-actions-flex">
                <button type="button" class="btn-modal-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button type="submit" class="btn-modal-confirm">Confirm Delete</button>
            </div>
        </form>
    </div>
</div>

<script>
// JavaScript handlers for custom modal interactions
function openDeleteModal(photoId, photoTitle) {
    document.getElementById('modalPhotoId').value = photoId;
    document.getElementById('modalPhotoName').innerText = '"' + photoTitle + '"';
    document.getElementById('deleteModal').style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Close modal when user clicks outside the modal box
window.onclick = function(event) {
    var modal = document.getElementById('deleteModal');
    if (event.target === modal) {
        closeDeleteModal();
    }
}
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>