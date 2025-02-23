
<?php include 'layout/sidebar.php'; ?>
<?php include 'layout/navbar.php'; ?>
<?php


// ตรวจสอบว่ามี language_id ที่ส่งมาหรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$language_id = (int)$_GET['id'];

// ดึงข้อมูลภาษาโปรแกรม
$lang_query = $conn->prepare("SELECT * FROM programming_languages WHERE id = ?");
$lang_query->bind_param("i", $language_id);
$lang_query->execute();
$language = $lang_query->get_result()->fetch_assoc();

if (!$language) {
    header('Location: index.php');
    exit();
}



// จัดการการค้นหาและกรอง
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// สร้าง SQL query สำหรับบทความ
$articles_query = "
    SELECT a.*, u.username as author_name, 
           (SELECT COUNT(*) FROM code_examples WHERE article_id = a.id) as code_count
    FROM articles a
    JOIN users u ON a.author_id = u.id
    WHERE a.language_id = ? AND a.status = 'published'
";

// เพิ่มเงื่อนไขการค้นหา
if (!empty($search)) {
    $articles_query .= " AND (a.title LIKE ? OR a.content LIKE ?)";
}

// เพิ่มเงื่อนไขการกรอง
switch ($filter) {
    case 'has_code':
        $articles_query .= " HAVING code_count > 0";
        break;
    case 'no_code':
        $articles_query .= " HAVING code_count = 0";
        break;
}

// เพิ่มการเรียงลำดับ
switch ($sort) {
    case 'oldest':
        $articles_query .= " ORDER BY a.created_at ASC";
        break;
    case 'views':
        $articles_query .= " ORDER BY a.views DESC";
        break;
    default: // newest
        $articles_query .= " ORDER BY a.created_at DESC";
}

$stmt = $conn->prepare($articles_query);

if (!empty($search)) {
    $search_param = "%$search%";
    $stmt->bind_param("iss", $language_id, $search_param, $search_param);
} else {
    $stmt->bind_param("i", $language_id);
}

$stmt->execute();
$articles = $stmt->get_result();
?>

<!-- Begin Page Content -->
<div class="container-fluid py-5">
  

        <!-- ส่วนหัวของหมวดหมู่ -->
        <div class="category-header bg-light p-4 rounded-3 mb-4">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <?php if($language['icon_path']): ?>
                        <img src="<?php echo htmlspecialchars($language['icon_path']); ?>" 
                             alt="<?php echo htmlspecialchars($language['name']); ?>"
                             class="img-fluid category-icon mb-3 mb-md-0"
                             style="max-height: 100px;">
                    <?php endif; ?>
                </div>
                <div class="col-md-10">
                    <h1 class="display-5"><?php echo htmlspecialchars($language['name']); ?></h1>
                    <p class="lead mb-0"><?php echo htmlspecialchars($language['description']); ?></p>
                </div>
            </div>
        </div>

        <!-- ส่วนค้นหาและกรอง -->
        <div class="row mb-4">
            <div class="col-md-8">
                <form action="" method="GET" class="d-flex gap-2">
                    <input type="hidden" name="id" value="<?php echo $language_id; ?>">
                    <input type="text" name="search" class="form-control" 
                           placeholder="ค้นหาบทความ..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <select name="filter" class="form-select" style="width: auto;">
                        <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>ทั้งหมด</option>
                        <option value="has_code" <?php echo $filter == 'has_code' ? 'selected' : ''; ?>>มีตัวอย่างโค้ด</option>
                        <option value="no_code" <?php echo $filter == 'no_code' ? 'selected' : ''; ?>>ไม่มีตัวอย่างโค้ด</option>
                    </select>
                    <select name="sort" class="form-select" style="width: auto;">
                        <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>ล่าสุด</option>
                        <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>เก่าสุด</option>
                        <option value="views" <?php echo $sort == 'views' ? 'selected' : ''; ?>>ยอดเข้าชมสูงสุด</option>
                    </select>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> ค้นหา
                    </button>
                </form>
            </div>
            <!-- <div class="col-md-4 text-end">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="create_article.php?language_id=<?php echo $language_id; ?>" 
                       class="btn btn-success">
                        <i class="bi bi-plus-circle"></i> เขียนบทความใหม่
                    </a>
                <?php endif; ?>
            </div> -->
        </div>

        <!-- รายการบทความ -->
        <?php if($articles->num_rows > 0): ?>
            <div class="row row-cols-1 row-cols-md-2 g-4">
                <?php while($article = $articles->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <a href="article.php?id=<?php echo $article['id']; ?>" 
                                       class="text-decoration-none">
                                        <?php echo htmlspecialchars($article['title']); ?>
                                    </a>
                                </h5>
                                <p class="card-text text-muted">
                                    <?php 
                                    $content = strip_tags($article['content']);
                                    echo htmlspecialchars(substr($content, 0, 150)) . '...'; 
                                    ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="article-meta">
                                        <small class="text-muted">
                                            <i class="bi bi-person"></i> 
                                            <?php echo htmlspecialchars($article['author_name']); ?>
                                        </small>
                                        <small class="text-muted ms-2">
                                            <i class="bi bi-calendar"></i>
                                            <?php echo date('d/m/Y', strtotime($article['created_at'])); ?>
                                        </small>
                                        <small class="text-muted ms-2">
                                            <i class="bi bi-eye"></i>
                                            <?php echo number_format($article['views']); ?> ครั้ง
                                        </small>
                                    </div>
                                    <?php if($article['code_count'] > 0): ?>
                                        <span class="badge bg-info">
                                            <i class="bi bi-code-square"></i>
                                            มีตัวอย่างโค้ด
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">
                <i class="bi bi-info-circle"></i> ไม่พบบทความที่ตรงกับเงื่อนไขการค้นหา
            </div>
        <?php endif; ?>
   
</div>

<?php include 'layout/footer.php'; ?>