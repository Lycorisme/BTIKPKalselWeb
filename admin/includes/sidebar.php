<?php
/**
 * Admin Sidebar Navigation
 * Fixed version - No double hover, proper active state detection
 */

// Get current page from URL
 $currentFile = basename($_SERVER['PHP_SELF']);
 $currentPath = $_SERVER['PHP_SELF'];

// Determine current module and page for accurate active state
 $currentModule = '';
 $currentPage = '';

// Extract module from path
if (preg_match('/\/modules\/([^\/]+)\//', $currentPath, $matches)) {
    $currentModule = $matches[1];
}

// Determine specific page
if (strpos($currentPath, '/posts/') !== false) {
    $currentPage = 'posts';
} elseif (strpos($currentPath, '/services/') !== false) {
    $currentPage = 'services';
} elseif (strpos($currentPath, '/users/') !== false) {
    $currentPage = 'users';
} elseif (strpos($currentPath, '/categories/') !== false) {
    $currentPage = 'categories';
} elseif (strpos($currentPath, '/tags/') !== false) {
    $currentPage = 'tags';
} elseif (strpos($currentPath, '/files/') !== false) {
    $currentPage = 'files';
} elseif (strpos($currentPath, '/banners/') !== false) {
    $currentPage = 'banners';
} elseif (strpos($currentPath, '/settings/') !== false) {
    $currentPage = 'settings';
} elseif (strpos($currentPath, '/gallery/') !== false) {
    $currentPage = 'gallery';
} elseif (strpos($currentPath, '/contact/') !== false) {
    $currentPage = 'contact';
} elseif (strpos($currentPath, '/trash/') !== false) {
    $currentPage = 'trash';
} elseif (strpos($currentPath, '/pages/') !== false) {
    $currentPage = 'pages';
} elseif (strpos($currentPath, '/reports/') !== false) {
    $currentPage = 'reports';
    // Sub-detect report type
    if (strpos($currentPath, 'report_posts') !== false) {
        $currentReportType = 'report_posts';
    } elseif (strpos($currentPath, 'report_activities') !== false) {
        $currentReportType = 'report_activities';
    } elseif (strpos($currentPath, 'report_users') !== false) {
        $currentReportType = 'report_users';
    } elseif (strpos($currentPath, 'report_services') !== false) {
        $currentReportType = 'report_services';
    } elseif (strpos($currentPath, 'report_categories') !== false) {
        $currentReportType = 'report_categories';
    } elseif (strpos($currentPath, 'report_overview') !== false) {
        $currentReportType = 'report_overview';
    }
} elseif (strpos($currentPath, '/activity-logs/') !== false) {
    $currentPage = 'activity_logs';
} elseif ($currentFile === 'index.php' && strpos($currentPath, '/admin/index.php') !== false) {
    $currentPage = 'dashboard';
}

// Get database connection for badge counts
 $db = Database::getInstance()->getConnection();

// Get unread contact messages count
 $unreadContactStmt = $db->query("SELECT COUNT(*) as unread FROM contact_messages WHERE status = 'unread'");
 $unreadContactData = $unreadContactStmt->fetch();
 $unreadContactCount = $unreadContactData['unread'] ?? 0;

// Get trash items count
 $trashCountStmt = $db->query("
    SELECT 
        (SELECT COUNT(*) FROM posts WHERE deleted_at IS NOT NULL) +
        (SELECT COUNT(*) FROM services WHERE deleted_at IS NOT NULL) +
        (SELECT COUNT(*) FROM users WHERE deleted_at IS NOT NULL) +
        (SELECT COUNT(*) FROM downloadable_files WHERE deleted_at IS NOT NULL) +
        (SELECT COUNT(*) FROM gallery_albums WHERE deleted_at IS NOT NULL) +
        (SELECT COUNT(*) FROM gallery_photos WHERE deleted_at IS NOT NULL) as total
");
 $trashCountData = $trashCountStmt->fetch();
 $trashCount = $trashCountData['total'] ?? 0;
?>

<div id="sidebar">
    <div class="sidebar-wrapper active">
        <!-- Sidebar Header -->
        <div class="sidebar-header position-relative">
            <div class="d-flex justify-content-between align-items-center">
                <div class="logo">
                    <a href="<?= ADMIN_URL ?>" class="d-flex align-items-center text-decoration-none">
                        <?php if ($adminLogo = getSetting('site_logo')): ?>
                            <img src="<?= uploadUrl($adminLogo) ?>" alt="Logo" style="max-height: 35px;" class="me-2">
                        <?php endif; ?>
                        
                        <?php if (getSetting('site_logo_show_text', '1') == '1'): ?>
                            <span style="font-size: 1.1rem; font-weight: 600; color: var(--bs-primary);">
                                <?= getSetting('site_logo_text', 'BTIKP KALSEL') ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
                <div class="sidebar-toggler x">
                    <a href="#" class="sidebar-hide d-xl-none d-block"><i class="bi bi-x bi-middle"></i></a>
                </div>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <div class="sidebar-menu">
            <ul class="menu">
                <li class="sidebar-title">Menu</li>

                <!-- Dashboard -->
                <li class="sidebar-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>" class="sidebar-link">
                        <i class="bi bi-grid-fill"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <!-- Posts/Articles -->
                <li class="sidebar-item has-sub <?= $currentPage === 'posts' || $currentPage === 'categories' || $currentPage === 'tags' ? 'active' : '' ?>">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-newspaper"></i>
                        <span>Berita & Artikel</span>
                    </a>
                    <ul class="submenu <?= $currentPage === 'posts' || $currentPage === 'categories' || $currentPage === 'tags' ? 'active' : '' ?>">
                        <li class="submenu-item <?= $currentPage === 'posts' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/posts/posts_list.php" class="submenu-link">Semua Post</a>
                        </li>
                        <li class="submenu-item <?= $currentPage === 'posts' && $currentFile === 'posts_add.php' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/posts/posts_add.php" class="submenu-link">Tambah Baru</a>
                        </li>
                        <li class="submenu-item <?= $currentPage === 'categories' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/categories/categories_list.php" class="submenu-link">Kategori</a>
                        </li>
                        <li class="submenu-item <?= $currentPage === 'tags' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/tags/tags_list.php" class="submenu-link">Tags</a>
                        </li>
                    </ul>
                </li>

                <!-- Services -->
                <li class="sidebar-item <?= $currentPage === 'services' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/services/services_list.php" class="sidebar-link">
                        <i class="bi bi-gear-fill"></i>
                        <span>Layanan</span>
                    </a>
                </li>

                <!-- Gallery -->
                <li class="sidebar-item has-sub <?= $currentPage === 'gallery' ? 'active' : '' ?>">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-images"></i>
                        <span>Gallery</span>
                    </a>
                    <ul class="submenu <?= $currentPage === 'gallery' ? 'active' : '' ?>">
                        <li class="submenu-item <?= strpos($currentFile, 'albums_') !== false ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/gallery/albums_list.php" class="submenu-link">Semua Album</a>
                        </li>
                        <li class="submenu-item <?= $currentFile === 'albums_add.php' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/gallery/albums_add.php" class="submenu-link">Tambah Album</a>
                        </li>
                    </ul>
                </li>

                <!-- Pages (NEW) -->
<!--                 <li class="sidebar-item <?= $currentPage === 'pages' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/pages/pages_list.php" class="sidebar-link">
                        <i class="bi bi-file-earmark-text"></i>
                        <span>Halaman</span>
                    </a>
                </li> -->

                <!-- Downloads -->
                <li class="sidebar-item <?= $currentPage === 'files' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/files/files_list.php" class="sidebar-link">
                        <i class="bi bi-download"></i>
                        <span>File Download</span>
                    </a>
                </li>

                <!-- Banners -->
                <li class="sidebar-item <?= $currentPage === 'banners' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/banners/banners_list.php" class="sidebar-link">
                        <i class="bi bi-card-image"></i>
                        <span>Banner</span>
                    </a>
                </li>

                <!-- Contact Messages (NEW) -->
                <li class="sidebar-item <?= $currentPage === 'contact' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/contact/messages_list.php" class="sidebar-link">
                        <i class="bi bi-envelope"></i>
                        <span>Pesan Kontak</span>
                        <?php if ($unreadContactCount > 0): ?>
                            <span class="badge bg-danger ms-auto"><?= $unreadContactCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>

                <li class="sidebar-title">Manajemen</li>

                <!-- Users -->
                <li class="sidebar-item <?= $currentPage === 'users' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/users/users_list.php" class="sidebar-link">
                        <i class="bi bi-people-fill"></i>
                        <span>Users</span>
                    </a>
                </li>

                <!-- Activity Logs -->
                <li class="sidebar-item <?= $currentPage === 'activity_logs' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/logs/activity_logs.php" class="sidebar-link">
                        <i class="bi bi-clock-history"></i>
                        <span>Activity Logs</span>
                    </a>
                </li>

                <!-- Trash (NEW) -->
                <li class="sidebar-item <?= $currentPage === 'trash' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/trash/trash_list.php" class="sidebar-link">
                        <i class="bi bi-trash"></i>
                        <span>Trash</span>
                        <?php if ($trashCount > 0): ?>
                            <span class="badge bg-secondary ms-auto"><?= $trashCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>

                <!-- Settings -->
                <li class="sidebar-item <?= $currentPage === 'settings' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/settings/settings.php" class="sidebar-link">
                        <i class="bi bi-gear"></i>
                        <span>Pengaturan</span>
                    </a>
                </li>

                <li class="sidebar-title">Laporan</li>

                <!-- Reports -->
                <li class="sidebar-item has-sub <?= $currentPage === 'reports' ? 'active' : '' ?>">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-file-bar-graph"></i>
                        <span>Laporan</span>
                    </a>
                    <ul class="submenu <?= $currentPage === 'reports' ? 'active' : '' ?>">
                        <li class="submenu-item <?= isset($currentReportType) && $currentReportType === 'report_posts' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/reports/report_posts.php" class="submenu-link">Laporan Posts</a>
                        </li>
                        <li class="submenu-item <?= isset($currentReportType) && $currentReportType === 'report_services' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/reports/report_services.php" class="submenu-link">Laporan Layanan</a>
                        </li>
                        <li class="submenu-item <?= isset($currentReportType) && $currentReportType === 'report_users' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/reports/report_users.php" class="submenu-link">Laporan Users</a>
                        </li>
                        <li class="submenu-item <?= isset($currentReportType) && $currentReportType === 'report_categories' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/reports/report_categories.php" class="submenu-link">Laporan Kategori & Tags</a>
                        </li>
                        <li class="submenu-item <?= isset($currentReportType) && $currentReportType === 'report_activities' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/reports/report_activities.php" class="submenu-link">Laporan Aktivitas</a>
                        </li>
                        <li class="submenu-item <?= isset($currentReportType) && $currentReportType === 'report_overview' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/reports/report_overview.php" class="submenu-link">System Overview</a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const btn = document.getElementById('theme-toggle-btn');
  const iconLight = document.getElementById('icon-light');
  const iconDark = document.getElementById('icon-dark');

  function updateIcons(theme) {
    if (theme === 'dark') {
      iconLight.classList.remove('d-none');
      iconDark.classList.add('d-none');
    } else {
      iconLight.classList.add('d-none');
      iconDark.classList.remove('d-none');
    }
  }

  let currentTheme = localStorage.getItem('theme') || 'light';
  updateIcons(currentTheme);

  btn.onclick = function() {
    currentTheme = currentTheme === 'dark' ? 'light' : 'dark';
    localStorage.setItem('theme', currentTheme);
    document.body.classList.toggle('dark-theme', currentTheme === 'dark');
    updateIcons(currentTheme);
  };

  if (currentTheme === 'dark') {
    document.body.classList.add('dark-theme');
  } else {
    document.body.classList.remove('dark-theme');
  }
});
</script>