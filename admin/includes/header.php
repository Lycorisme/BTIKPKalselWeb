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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= $pageTitle ?? 'Admin' ?> - <?= $siteName ?></title>
    
    <?php if ($siteFavicon): ?>
        <link rel="icon" type="image/png" href="<?= uploadUrl($siteFavicon) ?>" />
    <?php else: ?>
        <link rel="icon" type="image/png" href="<?= ADMIN_URL ?>assets/static/images/logo/favicon.png" />
    <?php endif; ?>
    
    <link rel="stylesheet" href="<?= ADMIN_URL ?>assets/compiled/css/app.css" />
    <link rel="stylesheet" href="<?= ADMIN_URL ?>assets/compiled/css/app-dark.css" />
    <link rel="stylesheet" href="<?= ADMIN_URL ?>assets/compiled/css/iconly.css" />
    
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css"
    />
    
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
        
        /* Search box styles */
        .search-form-desktop {
            max-width: 300px;
        }
        
        .search-toggle-mobile {
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 1.3rem;
            color: #5a5a5a;
            padding: 0.5rem;
        }
        
        .search-toggle-mobile:hover {
            color: #0d6efd;
        }
        
        .search-form-mobile {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            padding: 1rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 1000;
            animation: slideDown 0.3s ease;
        }
        
        [data-bs-theme="dark"] .search-form-mobile {
            background: #1a1d20;
        }
        
        .search-form-mobile.show {
            display: block;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Theme toggle in dropdown */
        .theme-toggle-item {
            cursor: pointer;
        }
        
        .theme-toggle-item:hover {
            background-color: #f8f9fa;
        }
        
        [data-bs-theme="dark"] .theme-toggle-item:hover {
            background-color: #2d3135;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .search-form-desktop {
                display: none !important;
            }
            
            .search-toggle-mobile {
                display: block;
            }
            
            .user-name {
                display: none;
            }
            
            .navbar-nav {
                gap: 0.5rem;
            }
        }
        
        @media (min-width: 769px) {
            .search-toggle-mobile {
                display: none;
            }
            
            .search-form-mobile {
                display: none !important;
            }
        }
        
        /* Notification badge positioning */
        .badge-notification {
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 0.65rem;
            padding: 0.25em 0.5em;
        }
        
        .nav-link {
            position: relative;
        }
        
        /* Navbar alignment */
        .navbar-right-items {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>
    
    <?php if (isset($additionalHead)): ?>
        <?= $additionalHead ?>
    <?php endif; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const htmlEl = document.documentElement;

        // Theme toggle functionality
        function updateTheme(theme) {
            htmlEl.setAttribute('data-bs-theme', theme);
            localStorage.setItem('theme', theme);
            
            // Update icon in dropdown
            const lightIcon = document.getElementById('theme-icon-light');
            const darkIcon = document.getElementById('theme-icon-dark');
            const themeText = document.getElementById('theme-text');
            
            if (lightIcon && darkIcon && themeText) {
                if (theme === 'dark') {
                    lightIcon.classList.remove('d-none');
                    darkIcon.classList.add('d-none');
                    themeText.textContent = 'Light Mode';
                } else {
                    lightIcon.classList.add('d-none');
                    darkIcon.classList.remove('d-none');
                    themeText.textContent = 'Dark Mode';
                }
            }
        }

        // Initialize theme icons
        const currentTheme = htmlEl.getAttribute('data-bs-theme') || 'light';
        updateTheme(currentTheme);

        // Theme toggle click handler
        const themeToggleItem = document.getElementById('theme-toggle-item');
        if (themeToggleItem) {
            themeToggleItem.addEventListener('click', function(e) {
                e.preventDefault();
                let theme = htmlEl.getAttribute('data-bs-theme') || 'light';
                theme = theme === 'dark' ? 'light' : 'dark';
                updateTheme(theme);
            });
        }

        // Mobile search toggle
        const searchToggle = document.getElementById('search-toggle-mobile');
        const searchFormMobile = document.getElementById('search-form-mobile');
        
        if (searchToggle && searchFormMobile) {
            searchToggle.addEventListener('click', function() {
                searchFormMobile.classList.toggle('show');
                
                // Focus on input when opened
                if (searchFormMobile.classList.contains('show')) {
                    const input = searchFormMobile.querySelector('input[type="search"]');
                    if (input) {
                        setTimeout(() => input.focus(), 100);
                    }
                }
            });
            
            // Close search when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchToggle.contains(e.target) && !searchFormMobile.contains(e.target)) {
                    searchFormMobile.classList.remove('show');
                }
            });
        }
    });
    </script>
    </head>
<body>
    <script src="<?= ADMIN_URL ?>assets/static/js/initTheme.js"></script>
    
    <div id="app">
        <?php include 'sidebar.php'; ?>
        
        <div id="main" class='layout-navbar navbar-fixed'>
            <header>
                <nav class="navbar navbar-expand navbar-light navbar-top">
                    <div class="container-fluid">
                        <a href="#" class="burger-btn d-block">
                            <i class="bi bi-justify fs-3"></i>
                        </a>

                        <!-- Right side items -->
                        <div class="navbar-right-items ms-auto">
                            <!-- Desktop Search Form -->
                            <form class="search-form-desktop d-flex my-2 my-lg-0" role="search" action="<?= ADMIN_URL ?>modules/search/search.php" method="GET">
                                <div class="input-group">
                                    <input
                                        class="form-control"
                                        type="search"
                                        placeholder="Search..."
                                        aria-label="Search"
                                        name="q"
                                        autocomplete="off"
                                    />
                                    <button class="btn btn-outline-primary" type="submit">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </form>

                            <!-- Mobile Search Toggle -->
                            <button id="search-toggle-mobile" class="search-toggle-mobile" title="Search">
                                <i class="bi bi-search"></i>
                            </button>
                            
                            <!-- Notifications -->
                            <div class="dropdown">
                                <a class="nav-link active dropdown-toggle text-gray-600" href="#" 
                                   data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class='bi bi-bell bi-sub fs-4'></i>
                                    <span class="badge badge-notification bg-danger">5</span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                                    <li><h6 class="dropdown-header">Notifikasi</h6></li>
                                    <li><a class="dropdown-item" href="#">Belum ada notifikasi baru</a></li>
                                </ul>
                            </div>
                            
                            <!-- User Profile Dropdown -->
                            <div class="dropdown">
                                <a href="#" data-bs-toggle="dropdown" aria-expanded="false">
                                    <div class="user-menu d-flex align-items-center">
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
                                        <a class="dropdown-item" href="<?= ADMIN_URL ?>profile.php">
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
                                        <a class="dropdown-item theme-toggle-item" href="#" id="theme-toggle-item">
                                            <i id="theme-icon-light" class="icon-mid bi bi-sun-fill me-2 d-none"></i>
                                            <i id="theme-icon-dark" class="icon-mid bi bi-moon-fill me-2"></i>
                                            <span id="theme-text">Dark Mode</span>
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
                    
                    <!-- Mobile Search Form (appears below navbar) -->
                    <div id="search-form-mobile" class="search-form-mobile">
                        <form role="search" action="<?= ADMIN_URL ?>modules/search/search.php" method="GET">
                            <div class="input-group">
                                <input
                                    class="form-control"
                                    type="search"
                                    placeholder="Search..."
                                    aria-label="Search"
                                    name="q"
                                    autocomplete="off"
                                />
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </nav>
            </header>
            
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
            
            <div id="main-content">