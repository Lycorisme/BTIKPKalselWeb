<?php
/**
 * Admin Header Template
 * With Dynamic Favicon, Site Name & User Photo
 */
if (!defined('ADMIN_URL')) {
    die('Direct access not allowed');
}

// Load required files
require_once dirname(__DIR__, 2) . '/core/Database.php';
require_once dirname(__DIR__, 2) . '/core/Helper.php';

// Get settings
$siteName = getSetting('site_name', 'BTIKP Kalimantan Selatan');
$siteFavicon = getSetting('site_favicon');

// Get current user with photo
$currentUser = getCurrentUser();

// Get user photo from database if not in session
if (!isset($currentUser['photo'])) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT photo FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$currentUser['id']]);
    $userPhoto = $stmt->fetchColumn();
    $currentUser['photo'] = $userPhoto;
}

$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Admin' ?> - <?= $siteName ?></title>
    
    <!-- Favicon (Dynamic) -->
    <?php if ($siteFavicon): ?>
        <link rel="icon" type="image/png" href="<?= uploadUrl($siteFavicon) ?>">
    <?php else: ?>
        <link rel="icon" type="image/png" href="<?= ADMIN_URL ?>assets/static/images/logo/favicon.png">
    <?php endif; ?>
    
    <!-- Mazer CSS -->
    <link rel="stylesheet" href="<?= ADMIN_URL ?>assets/compiled/css/app.css">
    <link rel="stylesheet" href="<?= ADMIN_URL ?>assets/compiled/css/app-dark.css">
    <link rel="stylesheet" href="<?= ADMIN_URL ?>assets/compiled/css/iconly.css">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <style>
        .alert-floating {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideInRight 0.3s ease;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .stats-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .stats-icon.purple { background-color: #9694ff; color: white; }
        .stats-icon.blue { background-color: #57caeb; color: white; }
        .stats-icon.green { background-color: #5ddab4; color: white; }
        .stats-icon.red { background-color: #ff7976; color: white; }
        
        /* User avatar styles */
        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .user-menu .avatar {
            border: 2px solid #e9ecef;
        }
    </style>
    
    <!-- Additional Head Content -->
    <?php if (isset($additionalHead)): ?>
        <?= $additionalHead ?>
    <?php endif; ?>
</head>
<body>
    <script src="<?= ADMIN_URL ?>assets/static/js/initTheme.js"></script>
    
    <div id="app">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>
        
        <!-- Main Content -->
        <div id="main" class='layout-navbar navbar-fixed'>
            <!-- Header Navbar -->
            <header>
                <nav class="navbar navbar-expand navbar-light navbar-top">
                    <div class="container-fluid">
                        <a href="#" class="burger-btn d-block">
                            <i class="bi bi-justify fs-3"></i>
                        </a>

                        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                                aria-expanded="false" aria-label="Toggle navigation">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        
                        <div class="collapse navbar-collapse" id="navbarSupportedContent">
                            <ul class="navbar-nav ms-auto mb-lg-0">
                                <!-- Notifications -->
                                <li class="nav-item dropdown me-1">
                                    <a class="nav-link active dropdown-toggle text-gray-600" href="#" 
                                       data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class='bi bi-bell bi-sub fs-4'></i>
                                        <span class="badge badge-notification bg-danger">5</span>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                                        <li><h6 class="dropdown-header">Notifikasi</h6></li>
                                        <li><a class="dropdown-item" href="#">Belum ada notifikasi baru</a></li>
                                    </ul>
                                </li>
                            </ul>
                            
                            <!-- User Profile Dropdown -->
                            <div class="dropdown">
                                <a href="#" data-bs-toggle="dropdown" aria-expanded="false">
                                    <div class="user-menu d-flex">
                                        <div class="user-name text-end me-3">
                                            <h6 class="mb-0 text-gray-600"><?= htmlspecialchars($currentUser['name']) ?></h6>
                                            <p class="mb-0 text-sm text-gray-600"><?= getRoleName($currentUser['role']) ?></p>
                                        </div>
                                        <div class="user-img d-flex align-items-center">
                                            <div class="avatar avatar-md">
                                                <?php if (!empty($currentUser['photo'])): ?>
                                                    <img src="<?= uploadUrl($currentUser['photo']) ?>" 
                                                         alt="<?= htmlspecialchars($currentUser['name']) ?>">
                                                <?php else: ?>
                                                    <img src="<?= ADMIN_URL ?>assets/static/images/faces/1.jpg" 
                                                         alt="Avatar">
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton" 
                                    style="min-width: 11rem;">
                                    <li>
                                        <h6 class="dropdown-header">Hello, <?= htmlspecialchars($currentUser['name']) ?>!</h6>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?= ADMIN_URL ?>modules/users/users_edit.php?id=<?= $currentUser['id'] ?>">
                                            <i class="icon-mid bi bi-person me-2"></i> Profil Saya
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?= ADMIN_URL ?>modules/settings/settings.php">
                                            <i class="icon-mid bi bi-gear me-2"></i> Pengaturan
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="<?= ADMIN_URL ?>logout.php" 
                                           onclick="return confirm('Yakin ingin logout?')">
                                            <i class="icon-mid bi bi-box-arrow-left me-2"></i> Logout
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>
            </header>
            
            <!-- Alert Notification -->
            <?php if ($alert): ?>
            <div class="alert alert-<?= $alert['type'] ?> alert-floating alert-dismissible fade show" role="alert">
                <strong>
                    <?php if ($alert['type'] === 'success'): ?>
                        <i class="bi bi-check-circle"></i> Berhasil!
                    <?php elseif ($alert['type'] === 'danger'): ?>
                        <i class="bi bi-x-circle"></i> Error!
                    <?php elseif ($alert['type'] === 'warning'): ?>
                        <i class="bi bi-exclamation-triangle"></i> Peringatan!
                    <?php else: ?>
                        <i class="bi bi-info-circle"></i> Info!
                    <?php endif; ?>
                </strong>
                <?= $alert['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <script>
                // Auto hide alert after 5 seconds
                setTimeout(function() {
                    let alert = document.querySelector('.alert-floating');
                    if (alert) {
                        let bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }
                }, 5000);
            </script>
            <?php endif; ?>
            
            <!-- Main Content Area -->
            <div id="main-content">
