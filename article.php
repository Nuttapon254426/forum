<?php include 'layout/sidebar.php'; ?>
<?php include 'layout/navbar.php'; ?>

<?php

// ตรวจสอบว่ามี ID บทความที่ส่งมาหรือไม่
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$article_id = (int)$_GET['id'];

try {
    // ดึงข้อมูลบทความพร้อมข้อมูลผู้เขียนและภาษาโปรแกรม
    $article_query = "
        SELECT a.*, pl.name as language_name, pl.id as language_id,
               u.username as author_name, u.email as author_email
        FROM articles a
        JOIN programming_languages pl ON a.language_id = pl.id
        JOIN users u ON a.author_id = u.id
        WHERE a.id = ? AND a.status = 'published'
    ";
    
    $stmt = $conn->prepare($article_query);
    if (!$stmt) {
        throw new Exception("การเตรียมคำสั่ง SQL ผิดพลาด");
    }
    
    $stmt->bind_param("i", $article_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $article = $result->fetch_assoc();

    if (!$article) {
        header('Location: index.php');
        exit();
    }

    // อัพเดทจำนวนการเข้าชม
    $conn->query("UPDATE articles SET views = views + 1 WHERE id = $article_id");

    // ดึงตัวอย่างโค้ด
    $code_query = "SELECT * FROM code_examples WHERE article_id = ?";
    $code_stmt = $conn->prepare($code_query);
    $code_stmt->bind_param("i", $article_id);
    $code_stmt->execute();
    $code_examples = $code_stmt->get_result();

} catch (Exception $e) {
    die("เกิดข้อผิดพลาด: " . $e->getMessage());
}
?>

<style>
    .article-container {
        max-width: 900px;
        margin: 0 auto;
    }
    .article-meta {
        background: #f8f9fa;
        border-radius: 1rem;
        padding: 1.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    .code-block {
        margin: 2rem 0;
        position: relative;
        background: #f8f9fa;
        border-radius: 1rem;
        padding: 1.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    .copy-btn {
        position: absolute;
        right: 1rem;
        top: 1rem;
        z-index: 100;
        transition: all 0.3s ease;
    }
    .copy-btn:hover {
        transform: translateY(-2px);
    }
    .copy-btn.success {
        background-color: #198754;
        color: white;
    }
    .copy-btn.error {
        background-color: #dc3545;
        color: white;
    }
    pre[class*="language-"] {
        border-radius: 0.5rem;
        margin: 1.5rem 0;
        padding: 1.5rem !important;
    }
    .author-info {
        background: linear-gradient(to right, #f8f9fa, #e9ecef);
        border-radius: 1rem;
        padding: 2rem;
        margin: 2rem 0;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    .article-content {
        font-size: 1.1rem;
        line-height: 1.8;
    }
    .breadcrumb {
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 0.5rem;
    }
</style>

<div class="container-fluid py-5">
    <div class="article-container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4 shadow-sm">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">หน้าแรก</a></li>
                <li class="breadcrumb-item">
                    <a href="category.php?id=<?php echo $article['language_id']; ?>">
                        <?php echo htmlspecialchars($article['language_name']); ?>
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?php echo htmlspecialchars($article['title']); ?>
                </li>
            </ol>
        </nav>

        <!-- หัวข้อบทความ -->
        <h1 class="display-4 mb-4"><?php echo htmlspecialchars($article['title']); ?></h1>

        <!-- ข้อมูลบทความ -->
        <div class="article-meta mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center mb-2">
                        <img src="https://www.gravatar.com/avatar/<?php echo md5(strtolower(trim($article['author_email']))); ?>?s=40&d=mp" 
                             class="rounded-circle me-2" alt="<?php echo htmlspecialchars($article['author_name']); ?>">
                        <div>
                            <strong><?php echo htmlspecialchars($article['author_name']); ?></strong>
                            <div class="text-muted small">
                                <i class="bi bi-calendar-event"></i> 
                                <?php echo date('d/m/Y H:i', strtotime($article['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="badge bg-primary mb-2">
                        <i class="bi bi-code-square"></i> 
                        <?php echo htmlspecialchars($article['language_name']); ?>
                    </div>
                    <div class="text-muted small">
                        <i class="bi bi-eye-fill"></i> 
                        <?php echo number_format($article['views']); ?> ครั้ง
                    </div>
                </div>
            </div>
        </div>

        <!-- เนื้อหาบทความ -->
        <div class="article-content mb-5">
            <?php echo $article['content']; ?>
        </div>

        <!-- ตัวอย่างโค้ด -->
        <?php if($code_examples->num_rows > 0): ?>
            <h3 class="h2 mb-4">ตัวอย่างโค้ด</h3>
            <?php while($code = $code_examples->fetch_assoc()): ?>
                <div class="code-block">
                    <h5 class="border-bottom pb-2 mb-3">
                        <i class="bi bi-code-slash"></i>
                        <?php echo htmlspecialchars($code['title']); ?>
                    </h5>
                    <div class="position-relative">
                        <button class="btn btn-sm btn-primary copy-btn" 
                                data-clipboard-target="#code-<?php echo $code['id']; ?>">
                            <i class="bi bi-clipboard"></i> คัดลอกโค้ด
                        </button>
                        <pre><code id="code-<?php echo $code['id']; ?>" class="language-<?php echo htmlspecialchars($code['language']); ?>"><?php echo htmlspecialchars($code['code_content']); ?></code></pre>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>

        <!-- ข้อมูลผู้เขียน -->
        <div class="author-info">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <img src="https://www.gravatar.com/avatar/<?php echo md5(strtolower(trim($article['author_email']))); ?>?s=120&d=mp" 
                         class="rounded-circle img-fluid mb-3" alt="<?php echo htmlspecialchars($article['author_name']); ?>">
                </div>
                <div class="col-md-10">
                    <h4 class="mb-3">เกี่ยวกับผู้เขียน</h4>
                    <h5 class="mb-2"><?php echo htmlspecialchars($article['author_name']); ?></h5>
                    <p class="mb-0 text-muted">
                        <i class="bi bi-person-badge"></i> สมาชิกตั้งแต่: <?php echo date('d/m/Y', strtotime($article['created_at'])); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- เพิ่ม Clipboard.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var clipboard = new ClipboardJS('.copy-btn');
    
    clipboard.on('success', function(e) {
        const button = e.trigger;
        button.classList.add('success');
        button.innerHTML = '<i class="bi bi-check"></i> คัดลอกแล้ว';
        
        setTimeout(function() {
            button.classList.remove('success');
            button.innerHTML = '<i class="bi bi-clipboard"></i> คัดลอกโค้ด';
        }, 2000);
        
        e.clearSelection();
    });

    clipboard.on('error', function(e) {
        const button = e.trigger;
        button.classList.add('error');
        button.innerHTML = '<i class="bi bi-x"></i> คัดลอกไม่สำเร็จ';
        
        setTimeout(function() {
            button.classList.remove('error');
            button.innerHTML = '<i class="bi bi-clipboard"></i> คัดลอกโค้ด';
        }, 2000);
    });
});
</script>

<?php include 'layout/footer.php'; ?>