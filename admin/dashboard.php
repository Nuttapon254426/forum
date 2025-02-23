<?php
// ดึงข้อมูลสถิติต่างๆ
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_articles = $conn->query("SELECT COUNT(*) as count FROM articles")->fetch_assoc()['count'];
$total_languages = $conn->query("SELECT COUNT(*) as count FROM programming_languages")->fetch_assoc()['count'];
$total_views = $conn->query("SELECT SUM(views) as total FROM articles")->fetch_assoc()['total'];

// ดึงบทความล่าสุด
$recent_articles = $conn->query("
    SELECT a.*, u.username, pl.name as language_name 
    FROM articles a 
    JOIN users u ON a.author_id = u.id 
    JOIN programming_languages pl ON a.language_id = pl.id 
    ORDER BY a.created_at DESC 
    LIMIT 5
");

// ดึงผู้ใช้งานล่าสุด
$recent_users = $conn->query("
    SELECT * FROM users 
    ORDER BY created_at DESC 
    LIMIT 5
");
?>

<div class="container-fluid">
    <h2 class="mb-4">แดชบอร์ด</h2>

    <!-- สถิติโดยรวม -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white stats-card">
                <div class="card-body">
                    <h5 class="card-title">ผู้ใช้งานทั้งหมด</h5>
                    <h2><?php echo number_format($total_users); ?></h2>
                    <i class="bi bi-people position-absolute end-0 bottom-0 mb-3 me-3" style="font-size: 2rem; opacity: 0.5;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white stats-card">
                <div class="card-body">
                    <h5 class="card-title">บทความทั้งหมด</h5>
                    <h2><?php echo number_format($total_articles); ?></h2>
                    <i class="bi bi-file-text position-absolute end-0 bottom-0 mb-3 me-3" style="font-size: 2rem; opacity: 0.5;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white stats-card">
                <div class="card-body">
                    <h5 class="card-title">ภาษาโปรแกรม</h5>
                    <h2><?php echo number_format($total_languages); ?></h2>
                    <i class="bi bi-code-square position-absolute end-0 bottom-0 mb-3 me-3" style="font-size: 2rem; opacity: 0.5;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white stats-card">
                <div class="card-body">
                    <h5 class="card-title">จำนวนการเข้าชม</h5>
                    <h2><?php echo number_format($total_views); ?></h2>
                    <i class="bi bi-eye position-absolute end-0 bottom-0 mb-3 me-3" style="font-size: 2rem; opacity: 0.5;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- บทความล่าสุด -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">บทความล่าสุด</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>หัวข้อ</th>
                                    <th>ผู้เขียน</th>
                                    <th>ภาษา</th>
                                    <th>วันที่</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($article = $recent_articles->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <a href="../article.php?id=<?php echo $article['id']; ?>" target="_blank">
                                            <?php echo htmlspecialchars($article['title']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($article['username']); ?></td>
                                    <td><?php echo htmlspecialchars($article['language_name']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($article['created_at'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ผู้ใช้งานล่าสุด -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">ผู้ใช้งานล่าสุด</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ชื่อผู้ใช้</th>
                                    <th>อีเมล</th>
                                    <th>สถานะ</th>
                                    <th>วันที่สมัคร</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($user = $recent_users->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'success'; ?>">
                                            <?php echo $user['role']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>