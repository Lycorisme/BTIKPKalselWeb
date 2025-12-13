<?php
// Ambil status dan badge count
$currentFile = basename($_SERVER['PHP_SELF']);
$currentPath = $_SERVER['PHP_SELF'];
$currentModule = '';
$currentPage = '';
$currentReportType = '';

// Ekstrak module dari path
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
elseif (strpos($currentPath, '/logs/') !== false) $currentPage = 'activity_logs';
elseif (strpos($currentPath, '/reports/') !== false) {
    $currentPage = 'reports';
    if (strpos($currentPath, 'report_activities') !== false) $currentReportType = 'report_activities';
    elseif (strpos($currentPath, 'report_kegiatan') !== false) $currentReportType = 'report_kegiatan';
    elseif (strpos($currentPath, 'report_posts') !== false) $currentReportType = 'report_posts';
    elseif (strpos($currentPath, 'report_tags') !== false) $currentReportType = 'report_tags';
    elseif (strpos($currentPath, 'report_categories') !== false) $currentReportType = 'report_categories';
    elseif (strpos($currentPath, 'report_users') !== false) $currentReportType = 'report_users';
    elseif (strpos($currentPath, 'report_engagement') !== false) $currentReportType = 'report_engagement';
    elseif (strpos($currentPath, 'report_downloads') !== false) $currentReportType = 'report_downloads';
    elseif (strpos($currentPath, 'report_contacts') !== false) $currentReportType = 'report_contacts';
    // UPDATE: Deteksi Report Overview
    elseif (strpos($currentPath, 'report_overview') !== false) $currentReportType = 'report_overview';
    elseif (strpos($currentPath, 'report_executive') !== false) $currentReportType = 'report_executive';
    elseif (strpos($currentPath, 'report_security') !== false) $currentReportType = 'report_security';
}
elseif ($currentFile === 'index.php' && strpos($currentPath, '/admin/index.php') !== false) $currentPage = 'dashboard';

// Badge counts
$db = Database::getInstance()->getConnection();

// Unread contact messages
$unreadContactStmt = $db->query("SELECT COUNT(*) as unread FROM contact_messages WHERE status = 'unread'");
$unreadContactData = $unreadContactStmt->fetch();
$unreadContactCount = $unreadContactData['unread'] ?? 0;

// Pending users (is_active = 3)
$pendingUsersStmt = $db->query("SELECT COUNT(*) as pending FROM users WHERE is_active = 3 AND deleted_at IS NULL");
$pendingUsersData = $pendingUsersStmt->fetch();
$pendingUsersCount = $pendingUsersData['pending'] ?? 0;

// Trash count
$trashCountStmt = $db->query("
    SELECT 
        (SELECT COUNT(*) FROM posts WHERE deleted_at IS NOT NULL) +
        (SELECT COUNT(*) FROM services WHERE deleted_at IS NOT NULL) +
        (SELECT COUNT(*) FROM users WHERE deleted_at IS NOT NULL) +
        (SELECT COUNT(*) FROM downloadable_files WHERE deleted_at IS NOT NULL) +
        (SELECT COUNT(*) FROM gallery_albums WHERE deleted_at IS NOT NULL) +
        (SELECT COUNT(*) FROM gallery_photos WHERE deleted_at IS NOT NULL) +
        (SELECT COUNT(*) FROM post_categories WHERE deleted_at IS NOT NULL) +
        (SELECT COUNT(*) FROM tags WHERE deleted_at IS NOT NULL) +
        (SELECT COUNT(*) FROM pages WHERE deleted_at IS NOT NULL) as total
");
$trashCountData = $trashCountStmt->fetch();
$trashCount = $trashCountData['total'] ?? 0;

// Pending comments
$pendingCommentsStmt = $db->query("SELECT COUNT(*) as pending FROM comments WHERE status = 'pending'");
$pendingCommentsData = $pendingCommentsStmt->fetch();
$pendingCommentsCount = $pendingCommentsData['pending'] ?? 0;
?>

<style>
/* * Menonaktifkan PerfectScrollbar Mazer dan 
 * menggantinya dengan scrollbar bawaan browser.
 */
#sidebar .sidebar-wrapper {
    /* * Paksa scrollbar browser
     * 'auto' berarti scrollbar hanya muncul saat dibutuhkan.
     */
    overflow-y: auto; 
    overflow-x: hidden;
    
    /* * Ini PENTING: Mencegah 'app.js' Mazer menginisialisasi
     * PerfectScrollbar pada elemen ini.
     */
    "data-perfect-scrollbar": "none";
}

/* * Menonaktifkan 'padding-right' aneh yang 
 * mungkin ditambahkan Mazer untuk scrollbar.
 */
body.sidebar-desktop #sidebar .sidebar-wrapper {
    padding-right: 0;
}

/* * Mengatur CSS untuk transisi Akordeon (Buka/Tutup)
 */
#sidebar .submenu {
    /* Sembunyikan submenu secara default */
    max-height: 0;
    overflow: hidden;
    
    /* Atur animasi transisi */
    transition: max-height 0.3s ease-out;
}

/* * Saat .has-sub memiliki class .active,
 * buka submenu-nya ke tinggi maksimum kontennya.
 */
#sidebar .has-sub.active > .submenu {
    max-height: 1000px; /* Atur ke angka yang besar */
    /* Transisi saat membuka */
    transition: max-height 0.5s ease-in-out;
}
</style>
<div id="sidebar">
    <div class="sidebar-wrapper active">

        <div class="sidebar-header position-relative">
            <div class="d-flex align-items-center justify-content-center">
                <a href="<?= ADMIN_URL ?>" class="d-flex flex-column align-items-center text-decoration-none w-100 py-3"> 
                    <?php if ($adminLogo = getSetting('site_logo')): ?>
                      <img src="<?= uploadUrl($adminLogo) ?>" alt="Logo BTIKP" style="height:60px; width:auto; display:block;">
                    <?php else: ?>
                      <img src="<?= BASE_URL ?>path/to/default/logo.png" alt="Logo" style="height:70px; width:auto; display:block;">
                    <?php endif; ?>

                    <?php if (getSetting('site_logo_show_text', '1') == '1'): ?>
                        <div class="text-center">
                          <span style="font-size:1.5rem; font-weight:800; color:var(--bs-primary); text-align:center;"> 
                            <?= getSetting('site_logo_text', 'BTIKP KALSEL') ?>
                          </span>
                        </div>
                    <?php endif; ?>
                </a>
            </div>
            <hr class="sidebar-divider my-0">
        </div>

        <div class="sidebar-menu">
            <ul class="menu">
                
                <li class="sidebar-title">Menu Utama</li>
                
                <li class="sidebar-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>" class="sidebar-link">
                        <i class="bi bi-grid-fill"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <li class="sidebar-title">Konten</li>

                <li class="sidebar-item has-sub <?= $currentPage === 'posts' || $currentPage === 'categories' || $currentPage === 'tags' ? 'active' : '' ?>">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-newspaper"></i>
                        <span>Berita & Artikel</span>
                        <?php if ($pendingCommentsCount > 0): ?>
                            <span class="badge bg-warning badge-sm"><?= $pendingCommentsCount ?></span>
                        <?php endif; ?>
                    </a>
                    <ul class="submenu <?= $currentPage === 'posts' || $currentPage === 'categories' || $currentPage === 'tags' ? 'active' : '' ?>">
                        <li class="submenu-item <?= $currentPage === 'posts' && !in_array($currentFile, ['posts_add.php', 'posts_edit.php']) ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/posts/posts_list.php" class="submenu-link">
                                <i class="bi bi-circle"></i>
                                <span>Semua Post</span>
                            </a>
                        </li>
                        <li class="submenu-item <?= $currentFile === 'posts_add.php' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/posts/posts_add.php" class="submenu-link">
                                <i class="bi bi-plus-circle"></i>
                                <span>Tambah Baru</span>
                            </a>
                        </li>
                        <li class="submenu-item <?= $currentPage === 'categories' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/categories/categories_list.php" class="submenu-link">
                                <i class="bi bi-folder"></i>
                                <span>Kategori</span>
                            </a>
                        </li>
                        <li class="submenu-item <?= $currentPage === 'tags' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/tags/tags_list.php" class="submenu-link">
                                <i class="bi bi-tags"></i>
                                <span>Tags</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="sidebar-item <?= $currentPage === 'pages' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/pages/pages_list.php" class="sidebar-link">
                        <i class="bi bi-file-earmark-text"></i>
                        <span>Halaman</span>
                    </a>
                </li>

                <li class="sidebar-item <?= $currentPage === 'services' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/services/services_list.php" class="sidebar-link">
                        <i class="bi bi-gear-fill"></i>
                        <span>Layanan</span>
                    </a>
                </li>

                <li class="sidebar-item has-sub <?= $currentPage === 'gallery' ? 'active' : '' ?>">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-images"></i>
                        <span>Gallery</span>
                    </a>
                    <ul class="submenu <?= $currentPage === 'gallery' ? 'active' : '' ?>">
                        <li class="submenu-item <?= strpos($currentFile, 'albums_list') !== false ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/gallery/albums_list.php" class="submenu-link">
                                <i class="bi bi-collection"></i>
                                <span>Semua Album</span>
                            </a>
                        </li>
                        <li class="submenu-item <?= $currentFile === 'albums_add.php' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/gallery/albums_add.php" class="submenu-link">
                                <i class="bi bi-plus-circle"></i>
                                <span>Tambah Album</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="sidebar-item <?= $currentPage === 'files' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/files/files_list.php" class="sidebar-link">
                        <i class="bi bi-download"></i>
                        <span>File Download</span>
                    </a>
                </li>

                <li class="sidebar-item <?= $currentPage === 'banners' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/banners/banners_list.php" class="sidebar-link">
                        <i class="bi bi-card-image"></i>
                        <span>Banner</span>
                    </a>
                </li>

                <li class="sidebar-item <?= $currentPage === 'contact' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/contact/messages_list.php" class="sidebar-link">
                        <i class="bi bi-envelope-fill"></i>
                        <span>Pesan Kontak</span>
                        <?php if ($unreadContactCount > 0): ?>
                            <span class="badge bg-danger badge-pill"><?= $unreadContactCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>

                <li class="sidebar-title">Manajemen</li>

                <li class="sidebar-item <?= $currentPage === 'users' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/users/users_list.php" class="sidebar-link">
                        <i class="bi bi-people-fill"></i>
                        <span>Pengguna</span>
                        <?php if ($pendingUsersCount > 0): ?>
                            <span class="badge bg-warning badge-pill"><?= $pendingUsersCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>

                <li class="sidebar-item <?= $currentPage === 'activity_logs' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/logs/activity_logs.php" class="sidebar-link">
                        <i class="bi bi-clock-history"></i>
                        <span>Activity Logs</span>
                    </a>
                </li>

                <li class="sidebar-item <?= $currentPage === 'trash' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/trash/trash_list.php" class="sidebar-link">
                        <i class="bi bi-trash-fill"></i>
                        <span>Trash</span>
                        <?php if ($trashCount > 0): ?>
                            <span class="badge bg-secondary badge-pill"><?= $trashCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>

                <li class="sidebar-item <?= $currentPage === 'settings' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/settings/settings.php" class="sidebar-link">
                        <i class="bi bi-gear-fill"></i>
                        <span>Pengaturan</span>
                    </a>
                </li>

                <li class="sidebar-title">Laporan & Analisis</li>

                <li class="sidebar-item has-sub <?= in_array($currentReportType, ['report_overview', 'report_posts', 'report_engagement']) ? 'active' : '' ?>">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-bar-chart-fill"></i>
                        <span>Laporan Utama</span>
                    </a>
                    <ul class="submenu <?= in_array($currentReportType, ['report_overview', 'report_posts', 'report_engagement']) ? 'active' : '' ?>">
                        <li class="submenu-item <?= $currentReportType === 'report_overview' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/reports/report_overview.php" class="submenu-link">
                                <i class="bi bi-grid-1x2-fill"></i>
                                <span>Overview</span>
                            </a>
                        </li>
                        <li class="submenu-item <?= $currentReportType === 'report_posts' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/reports/report_posts.php" class="submenu-link">
                                <i class="bi bi-journal-text"></i>
                                <span>Postingan</span>
                            </a>
                        </li>
                        <li class="submenu-item <?= $currentReportType === 'report_engagement' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/reports/report_engagement.php" class="submenu-link">
                                <i class="bi bi-heart-fill"></i>
                                <span>Engagement</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="sidebar-item has-sub <?= in_array($currentReportType, ['report_categories', 'report_tags', 'report_services', 'report_downloads']) ? 'active' : '' ?>">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-folder-fill"></i>
                        <span>Laporan Konten</span>
                    </a>
                    <ul class="submenu <?= in_array($currentReportType, ['report_categories', 'report_tags', 'report_services', 'report_downloads']) ? 'active' : '' ?>">
                        <li class="submenu-item <?= $currentReportType === 'report_categories' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/reports/report_categories.php" class="submenu-link">
                                <i class="bi bi-folder2"></i>
                                <span>Kategori</span>
                            </a>
                        </li>
                        <li class="submenu-item <?= $currentReportType === 'report_tags' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/reports/report_tags.php" class="submenu-link">
                                <i class="bi bi-tag-fill"></i>
                                <span>Tags</span>
                            </a>
                        </li>
                        <li class="submenu-item <?= $currentReportType === 'report_services' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/reports/report_services.php" class="submenu-link">
                                <i class="bi bi-briefcase-fill"></i>
                                <span>Layanan</span>
                            </a>
                        </li>
                        <li class="submenu-item <?= $currentReportType === 'report_downloads' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/reports/report_files.php" class="submenu-link">
                                <i class="bi bi-cloud-download"></i>
                                <span>File Download</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="sidebar-item has-sub <?= in_array($currentReportType, ['report_users', 'report_activities', 'report_contacts', 'report_security']) ? 'active' : '' ?>">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-shield-check"></i>
                        <span>Laporan Manajemen</span>
                    </a>
                    <ul class="submenu <?= in_array($currentReportType, ['report_users', 'report_activities', 'report_contacts', 'report_security']) ? 'active' : '' ?>">
                        <li class="submenu-item <?= $currentReportType === 'report_users' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/reports/report_users.php" class="submenu-link">
                                <i class="bi bi-person-badge"></i>
                                <span>Pengguna</span>
                            </a>
                        </li>
                        <li class="submenu-item <?= $currentReportType === 'report_activities' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/reports/report_activities.php" class="submenu-link">
                                <i class="bi bi-activity"></i>
                                <span>Aktivitas Sistem</span>
                            </a>
                        </li>
                        <li class="submenu-item <?= $currentReportType === 'report_contacts' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/reports/report_contact.php" class="submenu-link">
                                <i class="bi bi-envelope-paper"></i>
                                <span>Pesan Kontak</span>
                            </a>
                        </li>
                        <li class="submenu-item <?= $currentReportType === 'report_security' ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/reports/report_security.php" class="submenu-link">
                                <i class="bi bi-shield-lock"></i>
                                <span>Keamanan</span>
                            </a>
                        </li>
                    </ul>
                </li>

            </ul>
        </div> </div> </div> <script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Ambil elemen wrapper scrollbar
    const sidebarWrapper = document.querySelector('#sidebar .sidebar-wrapper');
    if (!sidebarWrapper) return; // Keluar jika tidak ada
    
    // Ambil semua item menu yang memiliki submenu
    const hasSubItems = document.querySelectorAll('.sidebar-item.has-sub');

    // Fungsi untuk meng-scroll wrapper ke atas
    function resetSidebarScroll() {
        if (sidebarWrapper.scrollTop > 0) {
            sidebarWrapper.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
    }

    // Pasang listener ke setiap item menu
    hasSubItems.forEach(item => {
        const link = item.querySelector('.sidebar-link');
        
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Cek apakah item ini *sudah* aktif
            const wasActive = item.classList.contains('active');
            
            // 1. SELALU scroll ke atas setiap kali ada klik
            // Ini akan memperbaiki bug "regroup"
            resetSidebarScroll();

            // 2. TUTUP SEMUA submenu lain
            // Ini memenuhi permintaan "hanya satu yang boleh buka"
            hasSubItems.forEach(otherItem => {
                if (otherItem !== item) {
                    otherItem.classList.remove('active');
                }
            });

            // 3. BUKA submenu yang diklik (jika tadinya tertutup)
            // Kita beri jeda sedikit (50ms) agar scroll ke atas
            // bisa dimulai sebelum animasi 'max-height' dimulai.
            if (!wasActive) {
                setTimeout(() => {
                    item.classList.add('active');
                }, 50); 
            } else {
                // Jika tadinya sudah aktif, tutup saja
                item.classList.remove('active');
            }
        });
    });
});
</script>