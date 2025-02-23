<?php
require_once '../config.php';


// ตรวจสอบสิทธิ์ admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// ตรวจสอบ ID
if (!isset($_GET['id'])) {
    header('Location: languages.php');
    exit();
}

$id = (int)$_GET['id'];
$stmt = $conn->prepare("SELECT * FROM programming_languages WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$language = $stmt->get_result()->fetch_assoc();

if (!$language) {
    header('Location: languages.php');
    exit();
}

// จัดการการบันทึกข้อมูล
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $display_order = (int)$_POST['display_order'];
    
    if (isset($_FILES['icon']) && $_FILES['icon']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['icon']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = uniqid() . '.' . $ext;
            $upload_path = '../uploads/icons/' . $new_filename;
            
            if (move_uploaded_file($_FILES['icon']['tmp_name'], $upload_path)) {
                // ลบไอคอนเก่า
                if ($language['icon_path']) {
                    @unlink('../' . $language['icon_path']);
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
    
    if ($stmt->execute()) {
        header('Location: languages.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขภาษาโปรแกรม - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .preview-icon {
            max-width: 100px;
            max-height: 100px;
        }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">แก้ไขภาษาโปรแกรม</h5>
                    <a href="index.php?page=languages" class="btn btn-secondary">กลับ</a>
                </div>
                <div class="card-body">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">ชื่อภาษา</label>
                            <input type="text" name="name" class="form-control" required 
                                   value="<?php echo htmlspecialchars($language['name']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">คำอธิบาย</label>
                            <textarea name="description" class="form-control" rows="3"
                            ><?php echo htmlspecialchars($language['description']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ไอคอน</label>
                            <?php if($language['icon_path']): ?>
                                <div class="mb-2">
                                    <img src="../<?php echo htmlspecialchars($language['icon_path']); ?>" 
                                         class="preview-icon" id="currentIcon">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="icon" class="form-control" accept="image/*" 
                                   onchange="previewIcon(this)">
                            <img id="iconPreview" class="mt-2 preview-icon" style="display: none;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ลำดับการแสดงผล</label>
                            <input type="number" name="display_order" class="form-control" 
                                   value="<?php echo $language['display_order']; ?>" min="0">
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previewIcon(input) {
    const preview = document.getElementById('iconPreview');
    const currentIcon = document.getElementById('currentIcon');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            if (currentIcon) currentIcon.style.display = 'none';
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>
