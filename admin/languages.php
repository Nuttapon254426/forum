<?php
// ตรวจสอบสิทธิ์ admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// จัดการการกระทำต่างๆ (เพิ่ม/แก้ไข/ลบ)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $display_order = (int)$_POST['display_order'];
                
                // จัดการอัพโหลดไอคอน
                $icon_path = '';
                if (isset($_FILES['icon']) && $_FILES['icon']['error'] === 0) {
                    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                    $filename = $_FILES['icon']['name'];
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    
                    if (in_array($ext, $allowed)) {
                        $new_filename = uniqid() . '.' . $ext;
                        $upload_path = '../uploads/icons/' . $new_filename;
                        
                        if (move_uploaded_file($_FILES['icon']['tmp_name'], $upload_path)) {
                            $icon_path = 'uploads/icons/' . $new_filename;
                        }
                    }
                }
                
                $stmt = $conn->prepare("
                    INSERT INTO programming_languages (name, description, icon_path, display_order) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->bind_param("sssi", $name, $description, $icon_path, $display_order);
                $stmt->execute();
                break;

            case 'edit':
                $id = (int)$_POST['id'];
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $display_order = (int)$_POST['display_order'];
                
                // จัดการอัพโหลดไอคอนใหม่
                if (isset($_FILES['icon']) && $_FILES['icon']['error'] === 0) {
                    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                    $filename = $_FILES['icon']['name'];
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    
                    if (in_array($ext, $allowed)) {
                        $new_filename = uniqid() . '.' . $ext;
                        $upload_path = '../uploads/icons/' . $new_filename;
                        
                        if (move_uploaded_file($_FILES['icon']['tmp_name'], $upload_path)) {
                            // ลบไอคอนเก่า
                            $old_icon = $conn->query("SELECT icon_path FROM programming_languages WHERE id = $id")->fetch_assoc();
                            if ($old_icon && $old_icon['icon_path']) {
                                @unlink('../' . $old_icon['icon_path']);
                            }
                            
                            $icon_path = 'uploads/icons/' . $new_filename;
                            $stmt = $conn->prepare("
                                UPDATE programming_languages 
                                SET name = ?, description = ?, icon_path = ?, display_order = ? 
                                WHERE id = ?
                            ");
                            $stmt->bind_param("sssis", $name, $description, $icon_path, $display_order, $id);
                        }
                    }
                } else {
                    $stmt = $conn->prepare("
                        UPDATE programming_languages 
                        SET name = ?, description = ?, display_order = ? 
                        WHERE id = ?
                    ");
                    $stmt->bind_param("ssii", $name, $description, $display_order, $id);
                }
                $stmt->execute();
                break;

            case 'delete':
                $id = (int)$_POST['id'];
                // ตรวจสอบว่ามีบทความที่ใช้ภาษานี้อยู่หรือไม่
                $check = $conn->query("SELECT COUNT(*) as count FROM articles WHERE language_id = $id")->fetch_assoc();
                if ($check['count'] == 0) {
                    // ลบไอคอน
                    $icon = $conn->query("SELECT icon_path FROM programming_languages WHERE id = $id")->fetch_assoc();
                    if ($icon && $icon['icon_path']) {
                        @unlink('../' . $icon['icon_path']);
                    }
                    // ลบภาษา
                    $conn->query("DELETE FROM programming_languages WHERE id = $id");
                }
                break;
        }
    }
}

// ดึงรายการภาษาทั้งหมด
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'display_order';
$languages_query = "SELECT * FROM programming_languages";
switch ($sort) {
    case 'name':
        $languages_query .= " ORDER BY name ASC";
        break;
    case 'newest':
        $languages_query .= " ORDER BY created_at DESC";
        break;
    default:
        $languages_query .= " ORDER BY display_order ASC";
}
$languages = $conn->query($languages_query);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการภาษาโปรแกรม - Admin Panel</title>
    <style>
        .language-icon {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }
        .preview-icon {
            max-width: 100px;
            max-height: 100px;
            display: none;
        }
    </style>
    <!-- เพิ่ม Bootstrap CSS และ JS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>จัดการภาษาโปรแกรม</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLanguageModal">
            <i class="bi bi-plus-circle"></i> เพิ่มภาษาใหม่
        </button>
    </div>

    <!-- ส่วนการเรียงลำดับ -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="" method="GET" class="row g-3">
                <div class="col-auto">
                    <label class="col-form-label">เรียงลำดับตาม:</label>
                </div>
                <div class="col-auto">
                    <select name="sort" class="form-select" onchange="this.form.submit()">
                        <option value="display_order" <?php echo $sort === 'display_order' ? 'selected' : ''; ?>>ลำดับการแสดงผล</option>
                        <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>ชื่อภาษา (A-Z)</option>
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>เพิ่มล่าสุด</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- ตารางแสดงภาษาโปรแกรม -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ลำดับ</th>
                            <th>ไอคอน</th>
                            <th>ชื่อภาษา</th>
                            <th>คำอธิบาย</th>
                            <th>จำนวนบทความ</th>
                            <th>วันที่เพิ่ม</th>
                            <th>การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($lang = $languages->fetch_assoc()): 
                            $article_count = $conn->query("SELECT COUNT(*) as count FROM articles WHERE language_id = {$lang['id']}")->fetch_assoc()['count'];
                        ?>
                        <tr>
                            <td><?php echo $lang['display_order']; ?></td>
                            <td>
                                <?php if($lang['icon_path']): ?>
                                    <img src="../<?php echo htmlspecialchars($lang['icon_path']); ?>" 
                                         class="language-icon" 
                                         alt="<?php echo htmlspecialchars($lang['name']); ?>">
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($lang['name']); ?></td>
                            <td><?php echo htmlspecialchars($lang['description']); ?></td>
                            <td><?php echo number_format($article_count); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($lang['created_at'])); ?></td>
                            <td>
                                <a href="edit_language.php?id=<?php echo $lang['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-pencil"></i> แก้ไข
                                </a>
                                <?php if($article_count == 0): ?>
                                    <button class="btn btn-sm btn-danger" onclick="deleteLanguage(<?php echo $lang['id']; ?>)">
                                        <i class="bi bi-trash"></i> ลบ
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal เพิ่มภาษาใหม่ -->
<div class="modal fade" id="addLanguageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title">เพิ่มภาษาโปรแกรมใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ชื่อภาษา</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">คำอธิบาย</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ไอคอน</label>
                        <input type="file" name="icon" class="form-control" accept="image/*" 
                               onchange="previewIcon(this, 'addIconPreview')">
                        <img id="addIconPreview" class="mt-2 preview-icon">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ลำดับการแสดงผล</label>
                        <input type="number" name="display_order" class="form-control" value="0" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">เพิ่มภาษา</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal ยืนยันการลบ -->
<div class="modal fade" id="deleteLanguageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ยืนยันการลบภาษาโปรแกรม</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>คุณแน่ใจหรือไม่ที่จะลบภาษาโปรแกรมนี้?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-danger" onclick="confirmDelete()">ลบ</button>
            </div>
        </div>
    </div>
</div>

<script>
function deleteLanguage(id) {
    if (confirm('คุณแน่ใจหรือไม่ที่จะลบภาษาโปรแกรมนี้?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function previewIcon(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>