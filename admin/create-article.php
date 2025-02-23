<?php
require_once '../config.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// ดึงข้อมูลภาษาโปรแกรมทั้งหมด
$languages = $conn->query("SELECT * FROM programming_languages ORDER BY name");

// จัดการการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = $_POST['content'];
    $language_id = (int)$_POST['language_id'];
    $status = $_POST['status'];
    $author_id = $_SESSION['user_id'];
    $current_date = date('Y-m-d H:i:s');

    try {
        $conn->begin_transaction();

        // เพิ่มบทความ
        $article_stmt = $conn->prepare("
            INSERT INTO articles (title, content, language_id, author_id, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $article_stmt->bind_param("ssiisss", $title, $content, $language_id, $author_id, $status, $current_date, $current_date);
        $article_stmt->execute();
        $article_id = $conn->insert_id;

        // เพิ่มโค้ดตัวอย่าง (ถ้ามี)
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
        $error = "เกิดข้อผิดพลาดในการบันทึกบทความ: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เขียนบทความใหม่ - Programming Learning Forum</title>
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
        #previewTab {
            display: none;
        }
    </style>
</head>
<body>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-9">
            <!-- ฟอร์มเขียนบทความ -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">เขียนบทความใหม่</h5>
                    <div>
                        <button type="button" class="btn btn-outline-secondary" onclick="showPreview()">
                            <i class="bi bi-eye"></i> ดูตัวอย่าง
                        </button>
                        <a href="index.php?page=articles" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> ยกเลิก
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form id="articleForm" method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="title" class="form-label">หัวข้อบทความ</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>

                        <div class="mb-3">
                            <label for="language_id" class="form-label">ภาษาโปรแกรม</label>
                            <select class="form-select" id="language_id" name="language_id" required>
                                <option value="">เลือกภาษาโปรแกรม</option>
                                <?php while($lang = $languages->fetch_assoc()): ?>
                                    <option value="<?php echo $lang['id']; ?>">
                                        <?php echo htmlspecialchars($lang['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label">เนื้อหาบทความ</label>
                            <textarea id="content" name="content" class="form-control" rows="15" required></textarea>
                        </div>

                        <!-- ส่วนเพิ่มโค้ดตัวอย่าง -->
                        <div class="mb-3">
                            <label class="form-label d-flex justify-content-between">
                                <span>โค้ดตัวอย่าง</span>
                                <button type="button" class="btn btn-sm btn-primary" onclick="addCodeExample()">
                                    <i class="bi bi-plus-circle"></i> เพิ่มโค้ดตัวอย่าง
                                </button>
                            </label>
                            <div id="codeExamples"></div>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">สถานะบทความ</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="published">เผยแพร่</option>
                                <option value="draft">แบบร่าง</option>
                            </select>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> บันทึกบทความ
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
            <!-- คำแนะนำการเขียนบทความ -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">คำแนะนำการเขียนบทความ</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> 
                            เขียนหัวข้อที่สื่อความหมายชัดเจน
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> 
                            แบ่งเนื้อหาเป็นหัวข้อย่อยให้อ่านง่าย
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> 
                            ใส่ตัวอย่างโค้ดที่เกี่ยวข้องและทำงานได้จริง
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> 
                            อธิบายแนวคิดและหลักการให้เข้าใจง่าย
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/prism.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-php.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-python.min.js"></script>

<script>
$(document).ready(function() {
    // กำหนดค่า Summernote
    $('#content').summernote({
        height: 300,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'underline', 'clear']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link']],
            ['view', ['fullscreen', 'codeview', 'help']]
        ],
        callbacks: {
            onChange: function(contents) {
                $('#previewContent').html(contents);
            }
        }
    });

    // Form validation
    $('#articleForm').on('submit', function(e) {
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        $(this).addClass('was-validated');
    });
});

// เพิ่มโค้ดตัวอย่าง
let codeExampleCount = 0;
function addCodeExample() {
    const container = document.getElementById('codeExamples');
    const id = codeExampleCount++;
    
    const codeExample = document.createElement('div');
    codeExample.className = 'code-example';
    codeExample.innerHTML = `
        <div class="mb-2">
            <input type="text" class="form-control" 
                   name="code_examples[${id}][title]" 
                   placeholder="ชื่อตัวอย่างโค้ด" required>
        </div>
        <div class="mb-2">
            <select class="form-select" name="code_examples[${id}][language]" required>
                <option value="javascript">JavaScript</option>
                <option value="php">PHP</option>
                <option value="python">Python</option>
            </select>
        </div>
        <div class="mb-2">
            <textarea class="form-control code-editor" 
                      name="code_examples[${id}][content]" 
                      rows="5" placeholder="// โค้ดตัวอย่าง" required></textarea>
        </div>
        <button type="button" class="btn btn-danger btn-sm" 
                onclick="this.parentElement.remove()">
            <i class="bi bi-trash"></i> ลบโค้ดตัวอย่าง
        </button>
    `;
    container.appendChild(codeExample);
}

// แสดงตัวอย่างบทความ
function showPreview() {
    const previewTab = document.getElementById('previewTab');
    const title = document.getElementById('title').value;
    
    document.getElementById('previewTitle').textContent = title;
    previewTab.style.display = previewTab.style.display === 'none' ? 'block' : 'none';
    
    // Highlight code blocks
    Prism.highlightAll();
}
</script>

</body>
</html>