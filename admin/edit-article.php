<?php
require_once '../config.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// ตรวจสอบ ID บทความ
if (!isset($_GET['id'])) {
    header('Location: articles.php');
    exit();
}

$article_id = (int)$_GET['id'];

// ดึงข้อมูลบทความ
$article_query = $conn->prepare("
    SELECT a.*, u.username as author_name
    FROM articles a
    JOIN users u ON a.author_id = u.id
    WHERE a.id = ?
");
$article_query->bind_param("i", $article_id);
$article_query->execute();
$article = $article_query->get_result()->fetch_assoc();

if (!$article) {
    header('Location: articles.php');
    exit();
}

// ดึงโค้ดตัวอย่างของบทความ
$code_query = $conn->prepare("SELECT * FROM code_examples WHERE article_id = ?");
$code_query->bind_param("i", $article_id);
$code_query->execute();
$code_examples = $code_query->get_result()->fetch_all(MYSQLI_ASSOC);

// ดึงรายการภาษาโปรแกรม
$languages = $conn->query("SELECT * FROM programming_languages ORDER BY name");

// จัดการการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = $_POST['content'];
    $language_id = (int)$_POST['language_id'];
    $status = $_POST['status'];
    $current_date = date('Y-m-d H:i:s');

    try {
        $conn->begin_transaction();

        // อัพเดทบทความ
        $update_stmt = $conn->prepare("
            UPDATE articles 
            SET title = ?, content = ?, language_id = ?, status = ?, updated_at = ?
            WHERE id = ?
        ");
        $update_stmt->bind_param("ssissi", 
            $title, $content, $language_id, $status, $current_date, $article_id
        );
        $update_stmt->execute();

        // ลบโค้ดตัวอย่างเก่า
        $conn->query("DELETE FROM code_examples WHERE article_id = $article_id");

        // เพิ่มโค้ดตัวอย่างใหม่
        if (!empty($_POST['code_examples'])) {
            $code_stmt = $conn->prepare("
                INSERT INTO code_examples (article_id, title, code_content, language)
                VALUES (?, ?, ?, ?)
            ");

            foreach ($_POST['code_examples'] as $code) {
                if (!empty($code['content'])) {
                    $code_stmt->bind_param("isss", 
                        $article_id, 
                        $code['title'], 
                        $code['content'], 
                        $code['language']
                    );
                    $code_stmt->execute();
                }
            }
        }

        $conn->commit();
        header('Location: articles.php');
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $error = "เกิดข้อผิดพลาดในการอัพเดทบทความ: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขบทความ - Programming Learning Forum</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/themes/prism-okaidia.min.css" rel="stylesheet">
    <style>
        .code-example {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .preview-content {
            border: 1px solid #dee2e6;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .meta-info {
            color: #6c757d;
            font-size: 0.9rem;
        }
        #previewTab {
            display: none;
        }
    </style>
</head>
<body>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-9">
            <!-- ฟอร์มแก้ไขบทความ -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">แก้ไขบทความ</h5>
                    <div>
                        <button type="button" class="btn btn-outline-secondary" onclick="showPreview()">
                            <i class="bi bi-eye"></i> ดูตัวอย่าง
                        </button>
                        <a href="ndex.php?page=articles" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> ยกเลิก
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- ข้อมูล Meta -->
                    <div class="meta-info mb-4">
                        <p class="mb-1">
                            <i class="bi bi-person"></i> ผู้เขียน: <?php echo htmlspecialchars($article['author_name']); ?>
                        </p>
                        <p class="mb-1">
                            <i class="bi bi-calendar"></i> สร้างเมื่อ: <?php echo date('d/m/Y H:i', strtotime($article['created_at'])); ?>
                        </p>
                        <p class="mb-1">
                            <i class="bi bi-pencil"></i> แก้ไขล่าสุด: <?php echo date('d/m/Y H:i', strtotime($article['updated_at'])); ?>
                        </p>
                    </div>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form id="articleForm" method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="title" class="form-label">หัวข้อบทความ</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo htmlspecialchars($article['title']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="language_id" class="form-label">ภาษาโปรแกรม</label>
                            <select class="form-select" id="language_id" name="language_id" required>
                                <?php while($lang = $languages->fetch_assoc()): ?>
                                    <option value="<?php echo $lang['id']; ?>" 
                                            <?php echo $lang['id'] == $article['language_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($lang['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label">เนื้อหาบทความ</label>
                            <textarea id="content" name="content" class="form-control" rows="15" required>
                                <?php echo htmlspecialchars($article['content']); ?>
                            </textarea>
                        </div>

                        <!-- ส่วนโค้ดตัวอย่าง -->
                        <div class="mb-3">
                            <label class="form-label d-flex justify-content-between">
                                <span>โค้ดตัวอย่าง</span>
                                <button type="button" class="btn btn-sm btn-primary" onclick="addCodeExample()">
                                    <i class="bi bi-plus-circle"></i> เพิ่มโค้ดตัวอย่าง
                                </button>
                            </label>
                            <div id="codeExamples">
                                <?php foreach ($code_examples as $index => $code): ?>
                                    <div class="code-example">
                                        <div class="mb-2">
                                            <input type="text" class="form-control" 
                                                   name="code_examples[<?php echo $index; ?>][title]" 
                                                   value="<?php echo htmlspecialchars($code['title']); ?>" 
                                                   placeholder="ชื่อตัวอย่างโค้ด" required>
                                        </div>
                                        <div class="mb-2">
                                            <select class="form-select" 
                                                    name="code_examples[<?php echo $index; ?>][language]" required>
                                                <option value="javascript" <?php echo $code['language'] === 'javascript' ? 'selected' : ''; ?>>JavaScript</option>
                                                <option value="php" <?php echo $code['language'] === 'php' ? 'selected' : ''; ?>>PHP</option>
                                                <option value="python" <?php echo $code['language'] === 'python' ? 'selected' : ''; ?>>Python</option>
                                            </select>
                                        </div>
                                        <div class="mb-2">
                                            <textarea class="form-control code-editor" 
                                                      name="code_examples[<?php echo $index; ?>][content]" 
                                                      rows="5" required><?php echo htmlspecialchars($code['code_content']); ?></textarea>
                                        </div>
                                        <button type="button" class="btn btn-danger btn-sm" 
                                                onclick="this.parentElement.remove()">
                                            <i class="bi bi-trash"></i> ลบโค้ดตัวอย่าง
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">สถานะบทความ</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="published" <?php echo $article['status'] === 'published' ? 'selected' : ''; ?>>เผยแพร่</option>
                                <option value="draft" <?php echo $article['status'] === 'draft' ? 'selected' : ''; ?>>แบบร่าง</option>
                                <option value="archived" <?php echo $article['status'] === 'archived' ? 'selected' : ''; ?>>เก็บถาวร</option>
                            </select>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> บันทึกการแก้ไข
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ส่วนแสดงตัวอย่าง -->
            <div id="previewTab" class="mt-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">ตัวอย่างบทความ</h5>
                    </div>
                    <div class="card-body">
                        <h2 id="previewTitle"></h2>
                        <div id="previewContent" class="preview-content"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <!-- คำแนะนำการแก้ไขบทความ -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">คำแนะนำการแก้ไขบทความ</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> 
                            ตรวจสอบความถูกต้องของเนื้อหา
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> 
                            ทดสอบโค้ดตัวอย่างให้ทำงานได้จริง
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> 
                            เพิ่มคำอธิบายที่ช่วยให้เข้าใจง่าย
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> 
                            ตรวจสอบการจัดรูปแบบให้อ่านง่าย
                        </li>
                    </ul>
                </div>
            </div>

            <!-- ประวัติการแก้ไข -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">สถิติบทความ</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <i class="bi bi-eye"></i> 
                        ยอดเข้าชม: <?php echo number_format($article['views']); ?> ครั้ง
                    </p>
                    <p class="mb-0">
                        <i class="bi bi-clock"></i> 
                        จำนวนการแก้ไข: <?php 
                            $edits = (strtotime($article['updated_at']) - strtotime($article['created_at'])) > 0 ?
                                'แก้ไขแล้ว' : 'ยังไม่เคยแก้ไข'; 
                            echo $edits;
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/prism.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-php.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-python.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize Summernote
    $('#content').summernote({
        height: 300,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'underline', 'clear']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['insert', ['link', 'picture']],
            ['view', ['fullscreen', 'codeview']]
        ]
    });
});

function addCodeExample() {
    const index = document.querySelectorAll('.code-example').length;
    const template = `
        <div class="code-example">
            <div class="mb-2">
                <input type="text" class="form-control" 
                       name="code_examples[${index}][title]" 
                       placeholder="ชื่อตัวอย่างโค้ด" required>
            </div>
            <div class="mb-2">
                <select class="form-select" name="code_examples[${index}][language]" required>
                    <option value="javascript">JavaScript</option>
                    <option value="php">PHP</option>
                    <option value="python">Python</option>
                </select>
            </div>
            <div class="mb-2">
                <textarea class="form-control code-editor" 
                          name="code_examples[${index}][content]" 
                          rows="5" required></textarea>
            </div>
            <button type="button" class="btn btn-danger btn-sm" 
                    onclick="this.parentElement.remove()">
                <i class="bi bi-trash"></i> ลบโค้ดตัวอย่าง
            </button>
        </div>
    `;
    document.getElementById('codeExamples').insertAdjacentHTML('beforeend', template);
}

function showPreview() {
    const previewTab = document.getElementById('previewTab');
    const display = previewTab.style.display;
    
    if (display === 'none' || display === '') {
        document.getElementById('previewTitle').textContent = document.getElementById('title').value;
        document.getElementById('previewContent').innerHTML = $('#content').summernote('code');
        previewTab.style.display = 'block';
        Prism.highlightAll();
    } else {
        previewTab.style.display = 'none';
    }
}

// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
})()
</script>

</body>
</html>