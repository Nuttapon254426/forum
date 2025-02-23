<?php
require_once '../config.php';

// ตรวจสอบสิทธิ์ admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการ - Programming Learning Forum</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .admin-sidebar {
            min-height: 100vh;
            background: #343a40;
        }
        .admin-sidebar .nav-link {
            color: rgba(255,255,255,.8);
            padding: 1rem;
        }
        .admin-sidebar .nav-link:hover {
            color: #fff;
            background: rgba(255,255,255,.1);
        }
        .admin-sidebar .nav-link.active {
            color: #fff;
            background: #0d6efd;
        }
        .admin-content {
            padding: 20px;
        }
        .stats-card {
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 px-0 admin-sidebar">
                <div class="d-flex flex-column">
                    <div class="p-3 text-white">
                        <h5>Admin Panel</h5>
                        <small><?php echo htmlspecialchars($_SESSION['username']); ?></small>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page === 'dashboard' ? 'active' : ''; ?>" 
                               href="index.php">
                                <i class="bi bi-speedometer2"></i> แดชบอร์ด
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page === 'languages' ? 'active' : ''; ?>" 
                               href="?page=languages">
                                <i class="bi bi-code-square"></i> จัดการภาษาโปรแกรม
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page === 'articles' ? 'active' : ''; ?>" 
                               href="?page=articles">
                                <i class="bi bi-file-text"></i> จัดการบทความ
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page === 'users' ? 'active' : ''; ?>" 
                               href="?page=users">
                                <i class="bi bi-people"></i> จัดการผู้ใช้งาน
                            </a>
                        </li>
                        <li class="nav-item mt-auto">
                            <a class="nav-link" href="../logout.php">
                                <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 admin-content">
                <?php
                switch($page) {
                    case 'dashboard':
                        include 'dashboard.php';
                        break;
                    case 'languages':
                        include 'languages.php';
                        break;
                    case 'articles':
                        include 'articles.php';
                        break;
                    case 'users':
                        include 'users.php';
                        break;
                    default:
                        include 'dashboard.php';
                }
                ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</body>
</html>