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

<?php
// Sidebar setup sama seperti sebelumnya...
$currentFile = basename($_SERVER['PHP_SELF']);
$currentPath = $_SERVER['PHP_SELF'];
$currentModule = '';
$currentPage = '';
$currentReportType = '';

if (preg_match('/\/modules\/([^\/]+)\//', $currentPath, $matches)) {
    $currentModule = $matches[1];
}

if (strpos($currentPath, '/posts/') !== false) $currentPage = 'posts';
elseif (strpos($currentPath, '/services/') !== false) $currentPage = 'services';
elseif (strpos($currentPath, '/users/') !== false) $currentPage = 'users';
elseif (strpos($currentPath, '/categories/') !== false) $currentPage = 'categories';
elseif (strpos($currentPath, '/tags/') !== false) $currentPage = 'tags';
elseif (strpos($currentPath, '/files/') !== false) $currentPage = 'files';
elseif (strpos($currentPath, '/banners/') !== false) $currentPage = 'banners';
elseif (strpos($currentPath, '/settings/') !== false) $currentPage = 'settings';
elseif (strpos($currentPath, '/gallery/') !== false) $currentPage = 'gallery';
elseif (strpos($currentPath, '/contact/') !== false) $currentPage = 'contact';
elseif (strpos($currentPath, '/trash/') !== false) $currentPage = 'trash';
elseif (strpos($currentPath, '/pages/') !== false) $currentPage = 'pages';
elseif (strpos($currentPath, '/reports/') !== false) {
    $currentPage = 'reports';
    if (strpos($currentPath, 'report_posts') !== false) $currentReportType = 'report_posts';
    elseif (strpos($currentPath, 'report_services') !== false) $currentReportType = 'report_services';
    elseif (strpos($currentPath, 'report_users') !== false) $currentReportType = 'report_users';
    elseif (strpos($currentPath, 'report_categories') !== false) $currentReportType = 'report_categories';
    elseif (strpos($currentPath, 'report_overview') !== false) $currentReportType = 'report_overview';
    elseif (strpos($currentPath, 'report_activities') !== false) $currentReportType = 'report_activities';
    elseif (strpos($currentPath, 'report_file_download') !== false) $currentReportType = 'report_file_download';
    elseif (strpos($currentPath, 'report_kegiatan') !== false) $currentReportType = 'report_kegiatan';
    elseif (strpos($currentPath, 'report_contact') !== false) $currentReportType = 'report_contact';
}
elseif (strpos($currentPath, '/activity-logs/') !== false) $currentPage = 'activity_logs';
elseif ($currentFile === 'index.php' && strpos($currentPath, '/admin/index.php') !== false) $currentPage = 'dashboard';

// Badge counts
$db = Database::getInstance()->getConnection();

$unreadContactStmt = $db->query("SELECT COUNT(*) as unread FROM contact_messages WHERE status = 'unread'");
$unreadContactData = $unreadContactStmt->fetch();
$unreadContactCount = $unreadContactData['unread'] ?? 0;

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

    <!-- Sidebar Header Logo & Text -->
    <div class="sidebar-header position-relative pb-2 pt-4 mb-4">
      <div class="d-flex flex-column align-items-center justify-content-center" style="gap:8px;">
        <!-- LOGO --> 
        <?php if ($adminLogo = getSetting('site_logo')): ?>
          <img src="<?= uploadUrl($adminLogo) ?>" alt="Logo BTIKP" 
               style="height:70px; width:auto; display:block;">
        <?php else: ?>
          <img src="<?= BASE_URL ?>path/to/default/logo.png" alt="Logo" 
               style="height:70px; width:auto; display:block;">
        <?php endif; ?>
        <!-- Logo Text --> 
        <?php if (getSetting('site_logo_show_text', '1') == '1'): ?>
          <span style="
            font-size: 1.50rem; 
            font-weight: 800; 
            color: var(--bs-primary); 
            text-align:center;
            line-height:1.1;
            letter-spacing:1px;
            display:block;
            ">
            <?= getSetting('site_logo_text', 'BTIKP KALSEL') ?>
          </span>
        <?php endif; ?>
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
              <a href="<?= ADMIN_URL ?>modules/posts/posts_list.php">Semua Post</a>
            </li>
            <li class="submenu-item <?= $currentPage === 'posts' && $currentFile === 'posts_add.php' ? 'active' : '' ?>">
              <a href="<?= ADMIN_URL ?>modules/posts/posts_add.php">Tambah Baru</a>
            </li>
            <li class="submenu-item <?= $currentPage === 'categories' ? 'active' : '' ?>">
              <a href="<?= ADMIN_URL ?>modules/categories/categories_list.php">Kategori</a>
            </li>
            <li class="submenu-item <?= $currentPage === 'tags' ? 'active' : '' ?>">
              <a href="<?= ADMIN_URL ?>modules/tags/tags_list.php">Tags</a>
            </li>
          </ul>
        </li>
        <!-- Layanan -->
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
              <a href="<?= ADMIN_URL ?>modules/gallery/albums_list.php">Semua Album</a>
            </li>
            <li class="submenu-item <?= $currentFile === 'albums_add.php' ? 'active' : '' ?>">
              <a href="<?= ADMIN_URL ?>modules/gallery/albums_add.php">Tambah Album</a>
            </li>
          </ul>
        </li>
        <!-- Pages -->
        <li class="sidebar-item <?= $currentPage === 'pages' ? 'active' : '' ?>">
          <a href="<?= ADMIN_URL ?>modules/pages/pages_list.php" class="sidebar-link">
            <i class="bi bi-file-earmark-text"></i>
            <span>Halaman</span>
          </a>
        </li>
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
        <!-- Kontak -->
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
        <!-- Trash -->
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
        <li class="sidebar-item <?= (isset($currentReportType) && $currentReportType === 'report_overview') ? 'active' : '' ?>">
          <a href="<?= ADMIN_URL ?>modules/reports/report_overview.php" class="sidebar-link">
            <i class="bi bi-bar-chart-steps"></i>
            <span>Laporan Overview</span>
          </a>
        </li>
        <li class="sidebar-item <?= (isset($currentReportType) && $currentReportType === 'report_activities') ? 'active' : '' ?>">
          <a href="<?= ADMIN_URL ?>modules/reports/report_activities.php" class="sidebar-link">
            <i class="bi bi-diagram-3"></i>
            <span>Laporan Sistem</span>
          </a>
        </li>
        <li class="sidebar-item <?= (isset($currentReportType) && $currentReportType === 'report_kegiatan') ? 'active' : '' ?>">
          <a href="<?= ADMIN_URL ?>modules/reports/report_kegiatan.php" class="sidebar-link">
            <i class="bi bi-calendar-event"></i>
            <span>Laporan Kegiatan</span>
          </a>
        </li>
        <li class="sidebar-item <?= (isset($currentReportType) && $currentReportType === 'report_posts') ? 'active' : '' ?>">
          <a href="<?= ADMIN_URL ?>modules/reports/report_posts.php" class="sidebar-link">
            <i class="bi bi-journal-text"></i>
            <span>Laporan Post</span>
          </a>
        </li>
        <li class="sidebar-item <?= (isset($currentReportType) && $currentReportType === 'report_services') ? 'active' : '' ?>">
          <a href="<?= ADMIN_URL ?>modules/reports/report_services.php" class="sidebar-link">
            <i class="bi bi-briefcase-fill"></i>
            <span>Laporan Layanan</span>
          </a>
        </li>
        <li class="sidebar-item <?= (isset($currentReportType) && $currentReportType === 'report_categories') ? 'active' : '' ?>">
          <a href="<?= ADMIN_URL ?>modules/reports/report_categories.php" class="sidebar-link">
            <i class="bi bi-tags"></i>
            <span>Laporan Kategori & Tag</span>
          </a>
        </li>
        <li class="sidebar-item <?= (isset($currentReportType) && $currentReportType === 'report_users') ? 'active' : '' ?>">
          <a href="<?= ADMIN_URL ?>modules/reports/report_users.php" class="sidebar-link">
            <i class="bi bi-person-lines-fill"></i>
            <span>Laporan User</span>
          </a>
        </li>
        <li class="sidebar-item <?= (isset($currentReportType) && $currentReportType === 'report_file_download') ? 'active' : '' ?>">
          <a href="<?= ADMIN_URL ?>modules/reports/report_files.php" class="sidebar-link">
            <i class="bi bi-download"></i>
            <span>Laporan File Download</span>
          </a>
        </li>
        <li class="sidebar-item <?= (isset($currentReportType) && $currentReportType === 'report_contact') ? 'active' : '' ?>">
          <a href="<?= ADMIN_URL ?>modules/reports/report_contact.php" class="sidebar-link">
            <i class="bi bi-envelope"></i>
            <span>Laporan Pesan Kontak</span>
          </a>
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