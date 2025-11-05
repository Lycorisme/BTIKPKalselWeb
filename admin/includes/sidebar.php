<?php
/**
 * Admin Sidebar Menu
 * Enhanced with proper active state detection
 */
if (!defined('ADMIN_URL')) {
    die('Direct access not allowed');
}

$currentPage = basename($_SERVER['PHP_SELF']);
$currentModule = basename(dirname($_SERVER['PHP_SELF']));

// Helper function to check active state
function isActive($pages) {
    global $currentPage, $currentModule;
    if (is_array($pages)) {
        foreach ($pages as $page) {
            if (strpos($currentPage, $page) !== false || $currentModule === $page) {
                return true;
            }
        }
    } else {
        return strpos($currentPage, $pages) !== false || $currentModule === $pages;
    }
    return false;
}
?>
<div id="sidebar" class="active">
    <div class="sidebar-wrapper active">
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
                <div class="theme-toggle d-flex gap-2 align-items-center mt-2">
                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                        aria-hidden="true" role="img" class="iconify iconify--system-uicons" width="20" height="20"
                        preserveAspectRatio="xMidYMid meet" viewBox="0 0 21 21">
                        <g fill="none" fill-rule="evenodd" stroke="currentColor" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path
                                d="M10.5 14.5c2.219 0 4-1.763 4-3.982a4.003 4.003 0 0 0-4-4.018c-2.219 0-4 1.781-4 4c0 2.219 1.781 4 4 4zM4.136 4.136L5.55 5.55m9.9 9.9l1.414 1.414M1.5 10.5h2m14 0h2M4.135 16.863L5.55 15.45m9.899-9.9l1.414-1.415M10.5 19.5v-2m0-14v-2"
                                opacity=".3"></path>
                            <g transform="translate(-210 -1)">
                                <path d="M220.5 2.5v2m6.5.5l-1.5 1.5"></path>
                                <circle cx="220.5" cy="11.5" r="4"></circle>
                                <path d="m214 5l1.5 1.5m5 14v-2m6.5-.5l-1.5-1.5M214 18l1.5-1.5m-4-5h2m14 0h2">
                                </path>
                            </g>
                        </g>
                    </svg>
                    <div class="form-check form-switch fs-6">
                        <input class="form-check-input me-0" type="checkbox" id="toggle-dark" style="cursor: pointer">
                        <label class="form-check-label"></label>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                        aria-hidden="true" role="img" class="iconify iconify--mdi" width="20" height="20"
                        preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24">
                        <path fill="currentColor"
                            d="m17.75 4.09l-2.53 1.94l.91 3.06l-2.63-1.81l-2.63 1.81l.91-3.06l-2.53-1.94L12.44 4l1.06-3l1.06 3l3.19.09m3.5 6.91l-1.64 1.25l.59 1.98l-1.7-1.17l-1.7 1.17l.59-1.98L15.75 11l2.06-.05L18.5 9l.69 1.95l2.06.05m-2.28 4.95c.83-.08 1.72 1.1 1.19 1.85c-.32.45-.66.87-1.08 1.27C15.17 23 8.84 23 4.94 19.07c-3.91-3.9-3.91-10.24 0-14.14c.4-.4.82-.76 1.27-1.08c.75-.53 1.93.36 1.85 1.19c-.27 2.86.69 5.83 2.89 8.02a9.96 9.96 0 0 0 8.02 2.89m-1.64 2.02a12.08 12.08 0 0 1-7.8-3.47c-2.17-2.19-3.33-5-3.49-7.82c-2.81 3.14-2.7 7.96.31 10.98c3.02 3.01 7.84 3.12 10.98.31Z">
                        </path>
                    </svg>
                </div>
                <div class="sidebar-toggler x">
                    <a href="#" class="sidebar-hide d-xl-none d-block"><i class="bi bi-x bi-middle"></i></a>
                </div>
            </div>
        </div>

        <div class="sidebar-menu">
            <ul class="menu">
                <li class="sidebar-title">Menu</li>

                <!-- Dashboard -->
                <li class="sidebar-item <?= $currentPage == 'index.php' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>" class="sidebar-link">
                        <i class="bi bi-grid-fill"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <!-- Berita & Artikel -->
                <li class="sidebar-item has-sub <?= isActive(['posts', 'categories', 'tags']) ? 'active' : '' ?>">
                    <a href="#" class="sidebar-link">
                        <i class="bi bi-newspaper"></i>
                        <span>Berita & Artikel</span>
                    </a>
                    <ul class="submenu <?= isActive(['posts', 'categories', 'tags']) ? 'active' : '' ?>">
                        <li class="submenu-item <?= isActive('posts') ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/posts/posts_list.php" class="submenu-link">Semua Post</a>
                        </li>
                        <li class="submenu-item">
                            <a href="<?= ADMIN_URL ?>modules/posts/posts_add.php" class="submenu-link">Tambah Baru</a>
                        </li>
                        <li class="submenu-item <?= isActive('categories') ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/categories/categories_list.php" class="submenu-link">Kategori</a>
                        </li>
                        <li class="submenu-item <?= isActive('tags') ? 'active' : '' ?>">
                            <a href="<?= ADMIN_URL ?>modules/tags/tags_list.php" class="submenu-link">Tags</a>
                        </li>
                    </ul>
                </li>

                <!-- Sekolah -->
                <li class="sidebar-item <?= isActive('schools') ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/schools/schools_list.php" class="sidebar-link">
                        <i class="bi bi-building"></i>
                        <span>Direktori Sekolah</span>
                    </a>
                </li>

                <!-- Services -->
                <li class="sidebar-item <?= isActive('services') ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/services/services_list.php" class="sidebar-link">
                        <i class="bi bi-gear-fill"></i>
                        <span>Layanan</span>
                    </a>
                </li>

                <!-- Pages -->
                <li class="sidebar-item <?= isActive('pages') ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/pages/pages_list.php" class="sidebar-link">
                        <i class="bi bi-file-earmark-text"></i>
                        <span>Halaman Statis</span>
                    </a>
                </li>

                <!-- Gallery (optional - uncomment if needed) -->
                <!-- <li class="sidebar-item <?= isActive('gallery') ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/gallery/gallery_list.php" class="sidebar-link">
                        <i class="bi bi-images"></i>
                        <span>Galeri</span>
                    </a>
                </li> -->

                <li class="sidebar-title">Pengaturan</li>

                <!-- Users -->
                <li class="sidebar-item <?= isActive('users') ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/users/users_list.php" class="sidebar-link">
                        <i class="bi bi-people-fill"></i>
                        <span>Pengguna</span>
                    </a>
                </li>

                <!-- Settings -->
                <li class="sidebar-item <?= isActive('settings') ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/settings/settings.php" class="sidebar-link">
                        <i class="bi bi-sliders"></i>
                        <span>Settings</span>
                    </a>
                </li>

                <!-- Activity Logs -->
                <li class="sidebar-item <?= isActive('logs') ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>modules/logs/activity_logs.php" class="sidebar-link">
                        <i class="bi bi-clock-history"></i>
                        <span>Activity Logs</span>
                    </a>
                </li>

                <li class="sidebar-title">Akun</li>

                <!-- Profile -->
                <li class="sidebar-item <?= $currentPage == 'profile.php' ? 'active' : '' ?>">
                    <a href="<?= ADMIN_URL ?>profile.php" class="sidebar-link">
                        <i class="bi bi-person-circle"></i>
                        <span>Profile</span>
                    </a>
                </li>

                <!-- Logout -->
                <li class="sidebar-item">
                    <a href="<?= ADMIN_URL ?>logout.php" class="sidebar-link" 
                       onclick="return confirm('Yakin ingin logout?')">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
