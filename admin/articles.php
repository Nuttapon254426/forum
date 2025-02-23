<?php
// ตรวจสอบสิทธิ์ admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// จัดการการกระทำต่างๆ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete':
                $article_id = (int)$_POST['article_id'];
                // ลบโค้ดตัวอย่างก่อน
                $conn->query("DELETE FROM code_examples WHERE article_id = $article_id");
                // จากนั้นลบบทความ
                $conn->query("DELETE FROM articles WHERE id = $article_id");
                break;
                
            case 'update_status':
                $article_id = (int)$_POST['article_id'];
                $new_status = $_POST['new_status'];
                if (in_array($new_status, ['published', 'draft', 'archived'])) {
                    $conn->query("UPDATE articles SET status = '$new_status' WHERE id = $article_id");
                }
                break;
        }
    }
}

// การค้นหาและกรอง
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$language_filter = isset($_GET['language']) ? (int)$_GET['language'] : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// ปรับ query ให้ดึงข้อมูลทั้งหมดโดยไม่ต้องมีการกรองเบื้องต้น
$query = "
    SELECT a.*, u.username as author_name, pl.name as language_name 
    FROM articles a
    JOIN users u ON a.author_id = u.id
    JOIN programming_languages pl ON a.language_id = pl.id
    ORDER BY a.created_at DESC
";

$articles = $conn->query($query);
$languages = $conn->query("SELECT id, name FROM programming_languages ORDER BY name");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการบทความ - Admin Panel</title>
    
    <!-- jQuery first, then Bootstrap, then DataTables -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap5.min.css">
    
    <!-- DataTables JS -->
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap5.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>

    <style>
        .article-title {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .status-badge {
            width: 100px;
        }
        .dataTables_filter {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>จัดการบทความ</h2>
        <a href="create-article.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> เขียนบทความใหม่
        </a>
    </div>


    <!-- ตารางแสดงบทความ -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="articlesTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>ชื่อบทความ</th>
                            <th>ผู้เขียน</th>
                            <th>ภาษา</th>
                            <th>สถานะ</th>
                            <th>ยอดเข้าชม</th>
                            <th>วันที่สร้าง</th>
                            <th>แก้ไขล่าสุด</th>
                            <th>การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($article = $articles->fetch_assoc()): ?>
                        <tr>
                            <td class="article-title">
                                <a href="../article.php?id=<?php echo $article['id']; ?>" target="_blank">
                                    <?php echo htmlspecialchars($article['title']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($article['author_name']); ?></td>
                            <td><?php echo htmlspecialchars($article['language_name']); ?></td>
                            <td>
                                <select class="form-select form-select-sm status-badge" 
                                        onchange="updateStatus(<?php echo $article['id']; ?>, this.value)">
                                    <option value="published" <?php echo $article['status'] === 'published' ? 'selected' : ''; ?>>เผยแพร่</option>
                                    <option value="draft" <?php echo $article['status'] === 'draft' ? 'selected' : ''; ?>>แบบร่าง</option>
                                    <option value="archived" <?php echo $article['status'] === 'archived' ? 'selected' : ''; ?>>เก็บถาวร</option>
                                </select>
                            </td>
                            <td><?php echo number_format($article['views']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($article['created_at'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($article['updated_at'])); ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="edit-article.php?id=<?php echo $article['id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="bi bi-pencil"></i> แก้ไข
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger" 
                                            onclick="deleteArticle(<?php echo $article['id']; ?>, '<?php echo addslashes($article['title']); ?>')">
                                        <i class="bi bi-trash"></i> ลบ
                                    </button>
                                </div>
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
<div class="modal fade" id="deleteArticleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ยืนยันการลบบทความ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>คุณแน่ใจหรือไม่ที่จะลบบทความ "<span id="deleteArticleTitle"></span>"?</p>
                <p class="text-danger">การกระทำนี้ไม่สามารถย้อนกลับได้</p>
            </div>
            <div class="modal-footer">
                <form action="" method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="article_id" id="deleteArticleId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-danger">ยืนยันการลบ</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#articlesTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/th.json'
        },
        order: [[5, 'desc']], // เรียงตามวันที่สร้างล่าสุด
        pageLength: 25,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: 'ส่งออก Excel',
                className: 'btn btn-success btn-sm',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
                }
            }
        ],
        columnDefs: [
            {
                targets: 7,
                orderable: false
            }
        ]
    });
});

function deleteArticle(id, title) {
    document.getElementById('deleteArticleTitle').textContent = title;
    document.getElementById('deleteArticleId').value = id;
    new bootstrap.Modal(document.getElementById('deleteArticleModal')).show();
}

function updateStatus(id, status) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="article_id" value="${id}">
        <input type="hidden" name="new_status" value="${status}">
    `;
    document.body.append(form);
    form.submit();
}
</script>

</body>
</html>