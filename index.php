<?php include 'layout/sidebar.php'; ?>
<?php include 'layout/navbar.php'; ?>
<?php

    // ดึงข้อมูลภาษาโปรแกรมทั้งหมด
    $languages_query = "SELECT * FROM programming_languages ORDER BY display_order ASC";
    $languages       = $conn->query($languages_query);

    // ดึงบทความยอดนิยม 5 อันดับแรก
    $popular_articles_query = "
    SELECT a.*, pl.name as language_name, u.username as author_name
    FROM articles a
    JOIN programming_languages pl ON a.language_id = pl.id
    JOIN users u ON a.author_id = u.id
    WHERE a.status = 'published'
    ORDER BY a.views DESC
    LIMIT 5";
    $popular_articles = $conn->query($popular_articles_query);

    // ดึงบทความล่าสุด 5 รายการ
    $latest_articles_query = "
    SELECT a.*, pl.name as language_name, u.username as author_name
    FROM articles a
    JOIN programming_languages pl ON a.language_id = pl.id
    JOIN users u ON a.author_id = u.id
    WHERE a.status = 'published'
    ORDER BY a.created_at DESC
    LIMIT 5";
    $latest_articles = $conn->query($latest_articles_query);
?>

<!-- Hero Section -->
<div class="bg-primary bg-gradient text-white py-5">
    <div class="container py-5">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <h1 class="display-3 fw-bold mb-3">เรียนรู้การเขียนโปรแกรม</h1>
                <p class="lead fs-4 mb-4">แหล่งรวมความรู้และแลกเปลี่ยนประสบการณ์การเขียนโปรแกรมภาษาต่างๆ</p>
                <a href="#languages" class="btn btn-light btn-lg px-4 shadow-sm">
                    <i class="bi bi-code-slash me-2"></i>เริ่มเรียนรู้เลย
                </a>
            </div>
            <div class="col-lg-6">
                <img src="img/hero-image.jpg" alt="Programming" class="img-fluid rounded-3 shadow-lg">
            </div>
        </div>
    </div>
</div>

<!-- Begin Page Content -->
<div class="container-fluid py-5">
    <!-- Programming Languages Section -->
    <section id="languages" class="mb-5">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">ภาษาโปรแกรมที่เปิดสอน</h2>
            <div class="col-lg-6 mx-auto">
                <p class="lead text-muted">เลือกภาษาโปรแกรมที่คุณสนใจและเริ่มต้นเรียนรู้ได้ทันที</p>
            </div>
        </div>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php
                $languages->data_seek(0);
                while ($lang = $languages->fetch_assoc()):
            ?>
            <div class="col mt-2">
                <div class="card h-100 border-0 shadow-sm hover-shadow transition-300">
                    <?php if ($lang['icon_path']): ?>
                    <div class="text-center pt-4">
                        <img src="<?php echo htmlspecialchars($lang['icon_path']); ?>" 
                             class="card-img-top" style="max-width: 100px;"
                             alt="<?php echo htmlspecialchars($lang['name']); ?>">
                    </div>
                    <?php endif; ?>
                    <div class="card-body text-center">
                        <h5 class="card-title fw-bold"><?php echo htmlspecialchars($lang['name']); ?></h5>
                        <p class="card-text text-muted"><?php echo htmlspecialchars($lang['description']); ?></p>
                        <a href="category.php?id=<?php echo $lang['id']; ?>"
                           class="btn btn-outline-primary">เรียนรู้เพิ่มเติม</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </section>

    <div class="row g-4">
        <!-- Popular Articles -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-4">
                    <h3 class="fs-4 fw-bold text-primary mb-0">
                        <i class="bi bi-star-fill me-2"></i>บทความยอดนิยม
                    </h3>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <?php while ($article = $popular_articles->fetch_assoc()): ?>
                        <a href="article.php?id=<?php echo $article['id']; ?>"
                           class="list-group-item list-group-item-action border-0 py-3">
                            <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($article['title']); ?></h6>
                            <small class="text-muted d-flex align-items-center gap-3">
                                <span><i class="bi bi-code-square me-1"></i><?php echo htmlspecialchars($article['language_name']); ?></span>
                                <span><i class="bi bi-eye me-1"></i><?php echo number_format($article['views']); ?> ครั้ง</span>
                            </small>
                        </a>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Latest Articles -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-4">
                    <h3 class="fs-4 fw-bold text-success mb-0">
                        <i class="bi bi-clock-history me-2"></i>บทความล่าสุด
                    </h3>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <?php while ($article = $latest_articles->fetch_assoc()): ?>
                        <a href="article.php?id=<?php echo $article['id']; ?>"
                           class="list-group-item list-group-item-action border-0 py-3">
                            <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($article['title']); ?></h6>
                            <small class="text-muted d-flex align-items-center gap-3">
                                <span><i class="bi bi-person me-1"></i><?php echo htmlspecialchars($article['author_name']); ?></span>
                                <span><i class="bi bi-calendar3 me-1"></i><?php echo date('d/m/Y', strtotime($article['created_at'])); ?></span>
                            </small>
                        </a>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>