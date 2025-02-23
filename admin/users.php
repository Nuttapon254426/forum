<?php
// ตรวจสอบสิทธิ์ admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// จัดการการกระทำต่างๆ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $user_id = (int)$_POST['user_id'];
        
        switch ($_POST['action']) {
            case 'update_role':
                $new_role = $_POST['new_role'];
                if (in_array($new_role, ['user', 'admin'])) {
                    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ? AND id != ?");
                    $stmt->bind_param("sii", $new_role, $user_id, $_SESSION['user_id']);
                    $stmt->execute();
                }
                break;
                
            case 'delete':
                // ไม่อนุญาตให้ลบตัวเอง
                if ($user_id != $_SESSION['user_id']) {
                    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                }
                break;
                
            case 'reset_password':
                // รีเซ็ตรหัสผ่านเป็น "password123"
                $default_password = password_hash("password123", PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $default_password, $user_id);
                $stmt->execute();
                break;
        }
    }
}

// การค้นหาและการกรอง
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? trim($_GET['role']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// สร้าง SQL query และ params array
$query = "SELECT * FROM users WHERE 1=1";
$params = array();
$types = "";

// เพิ่มเงื่อนไขการค้นหา
if (!empty($search)) {
    $query .= " AND (username LIKE ? OR email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

// เพิ่มเงื่อนไขการกรองตาม role
if (!empty($role_filter) && in_array($role_filter, ['user', 'admin'])) {
    $query .= " AND role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

// เพิ่มการเรียงลำดับ
switch ($sort) {
    case 'oldest':
        $query .= " ORDER BY created_at ASC";
        break;
    case 'username':
        $query .= " ORDER BY username ASC";
        break;
    default: // newest
        $query .= " ORDER BY created_at DESC";
}

$stmt = $conn->prepare($query);

// Bind parameters ถ้ามีพารามิเตอร์
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้งาน - Admin Panel</title>
    <!-- เพิ่ม DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }
        .table-actions .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .filter-section {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>จัดการผู้ใช้งาน</h2>
        <!-- <div class="d-flex gap-2">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exportModal">
                <i class="bi bi-download"></i> ส่งออกข้อมูล
            </button>
        </div> -->
    </div>

 
  

    <!-- ตารางแสดงผู้ใช้งาน -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="usersTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>รูปโปรไฟล์</th>
                            <th>ชื่อผู้ใช้</th>
                            <th>อีเมล</th>
                            <th>สถานะ</th>
                            <th>วันที่สมัคร</th>
                            <th>การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($user = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <img src="https://www.gravatar.com/avatar/<?php echo md5(strtolower(trim($user['email']))); ?>?s=40&d=mp" 
                                     class="user-avatar" alt="<?php echo htmlspecialchars($user['username']); ?>">
                            </td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <form action="" method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="update_role">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <select name="new_role" class="form-select form-select-sm" 
                                            onchange="this.form.submit()"
                                            <?php echo $user['id'] == $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                        <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </form>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                            <td class="table-actions">
                                <?php if($user['id'] != $_SESSION['user_id']): ?>
                                    <button class="btn btn-warning btn-sm" 
                                            onclick="resetPassword(<?php echo $user['id']; ?>)">
                                        <i class="bi bi-key"></i> รีเซ็ตรหัสผ่าน
                                    </button>
                                    <button class="btn btn-danger btn-sm" 
                                            onclick="deleteUser(<?php echo $user['id']; ?>)">
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

<!-- Modal ยืนยันการลบ -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ยืนยันการลบผู้ใช้</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>คุณแน่ใจหรือไม่ที่จะลบผู้ใช้นี้? การกระทำนี้ไม่สามารถย้อนกลับได้</p>
            </div>
            <div class="modal-footer">
                <form action="" method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-danger">ยืนยันการลบ</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal ยืนยันการรีเซ็ตรหัสผ่าน -->
<div class="modal fade" id="resetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ยืนยันการรีเซ็ตรหัสผ่าน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>รหัสผ่านจะถูกรีเซ็ตเป็น "password123"</p>
                <p>คุณแน่ใจหรือไม่ที่จะดำเนินการ?</p>
            </div>
            <div class="modal-footer">
                <form action="" method="POST">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="user_id" id="resetUserId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-warning">ยืนยันการรีเซ็ต</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- เพิ่ม DataTables JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<script>
function deleteUser(userId) {
    document.getElementById('deleteUserId').value = userId;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

function resetPassword(userId) {
    document.getElementById('resetUserId').value = userId;
    new bootstrap.Modal(document.getElementById('resetModal')).show();
}

// เพิ่มการตั้งค่า DataTables
$(document).ready(function() {
    $('#usersTable').DataTable({
        language: {
            "lengthMenu": "แสดง _MENU_ รายการต่อหน้า",
            "zeroRecords": "ไม่พบข้อมูล",
            "info": "แสดงหน้า _PAGE_ จาก _PAGES_",
            "infoEmpty": "ไม่มีข้อมูล",
            "infoFiltered": "(กรองจากทั้งหมด _MAX_ รายการ)",
            "search": "ค้นหา:",
            "paginate": {
                "first": "หน้าแรก",
                "last": "หน้าสุดท้าย",
                "next": "ถัดไป",
                "previous": "ก่อนหน้า"
            }
        },
        columnDefs: [
            { orderable: false, targets: [0, 5] }, // ไม่ต้องการให้เรียงลำดับคอลัมน์รูปและปุ่มจัดการ
            { searchable: false, targets: [0, 5] } // ไม่ต้องการให้ค้นหาในคอลัมน์รูปและปุ่มจัดการ
        ],
        order: [[4, 'desc']], // เรียงตามวันที่สมัครล่าสุดเป็นค่าเริ่มต้น
        pageLength: 10, // จำนวนรายการต่อหน้า
        responsive: true
    });
});
</script>

</body>
</html>