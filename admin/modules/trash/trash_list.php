<?php
/**
 * Trash/Recycle Bin - List All Deleted Items
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

 $pageTitle = 'Trash';
 $currentPage = 'trash';

 $db = Database::getInstance()->getConnection();

// Get filter parameters
 $type = $_GET['type'] ?? '';
 $search = $_GET['search'] ?? '';

// Pagination
 $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
 $perPage = 20;
 $offset = ($page - 1) * $perPage;

// Function to check if a column exists in a table
function columnExists($db, $table, $column) {
    try {
        $stmt = $db->prepare("SHOW COLUMNS FROM `$table` LIKE '$column'");
        $stmt->execute();
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// =================================================================
// STEP 1 - Dapatkan hitungan TOTAL untuk DROPDOWN
// =================================================================
$dropdownCounts = [
    'posts' => 0, 'services' => 0, 'users' => 0, 'pages' => 0, // 'pages' DITAMBAHKAN
    'categories' => 0, 'files' => 0, 'albums' => 0, 'photos' => 0, 
    'banners' => 0, 'contacts' => 0,
];
$totalDropdownItems = 0;

function getTrashCount($db, $table) {
    if (!columnExists($db, $table, 'deleted_at')) return 0;
    try {
        $stmt = $db->query("SELECT COUNT(id) FROM `$table` WHERE deleted_at IS NOT NULL");
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error counting trash for $table: " . $e->getMessage());
        return 0;
    }
}

$dropdownCounts['posts'] = getTrashCount($db, 'posts');
$dropdownCounts['services'] = getTrashCount($db, 'services');
$dropdownCounts['users'] = getTrashCount($db, 'users');
$dropdownCounts['pages'] = getTrashCount($db, 'pages'); // 'pages' DITAMBAHKAN
$dropdownCounts['categories'] = getTrashCount($db, 'categories');
$dropdownCounts['files'] = getTrashCount($db, 'downloadable_files');
$dropdownCounts['albums'] = getTrashCount($db, 'gallery_albums');
$dropdownCounts['photos'] = getTrashCount($db, 'gallery_photos');
$dropdownCounts['banners'] = getTrashCount($db, 'banners');
$dropdownCounts['contacts'] = getTrashCount($db, 'contact_messages');

foreach ($dropdownCounts as $count) {
    $totalDropdownItems += $count;
}


// =================================================================
// STEP 2 - Kumpulkan item yang DI-FILTER untuk tampilan utama
// =================================================================
$deletedItems = [];

// 1. Posts
if ($type === '' || $type === 'posts') {
    if (columnExists($db, 'posts', 'deleted_at')) {
        $sql = "SELECT id, title as name, 'post' as type, 'Berita/Artikel' as type_label, deleted_at, created_at FROM posts WHERE deleted_at IS NOT NULL";
        if ($search) $sql .= " AND (title LIKE ?)";
        $stmt = $db->prepare($sql);
        $search ? $stmt->execute(["%{$search}%"]) : $stmt->execute();
        $deletedItems = array_merge($deletedItems, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}

// 2. Services
if ($type === '' || $type === 'services') {
    if (columnExists($db, 'services', 'deleted_at')) {
        $sql = "SELECT id, title as name, 'service' as type, 'Layanan' as type_label, deleted_at, created_at FROM services WHERE deleted_at IS NOT NULL";
        if ($search) $sql .= " AND (title LIKE ?)";
        $stmt = $db->prepare($sql);
        $search ? $stmt->execute(["%{$search}%"]) : $stmt->execute();
        $deletedItems = array_merge($deletedItems, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}

// 3. Users
if ($type === '' || $type === 'users') {
    if (columnExists($db, 'users', 'deleted_at')) {
        $sql = "SELECT id, name, 'user' as type, 'User' as type_label, deleted_at, created_at FROM users WHERE deleted_at IS NOT NULL";
        if ($search) $sql .= " AND (name LIKE ? OR email LIKE ?)";
        $stmt = $db->prepare($sql);
        $search ? $stmt->execute(["%{$search}%", "%{$search}%"]) : $stmt->execute();
        $deletedItems = array_merge($deletedItems, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}

// 4. Pages (BARU DITAMBAHKAN)
if ($type === '' || $type === 'pages') {
    if (columnExists($db, 'pages', 'deleted_at')) {
        $sql = "SELECT id, title as name, 'page' as type, 'Halaman' as type_label, deleted_at, created_at FROM pages WHERE deleted_at IS NOT NULL";
        if ($search) $sql .= " AND (title LIKE ? OR slug LIKE ?)";
        $stmt = $db->prepare($sql);
        $search ? $stmt->execute(["%{$search}%", "%{$search}%"]) : $stmt->execute();
        $deletedItems = array_merge($deletedItems, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}

// 5. Categories
if ($type === '' || $type === 'categories') {
    if (columnExists($db, 'categories', 'deleted_at')) {
        $sql = "SELECT id, name, 'category' as type, 'Kategori' as type_label, deleted_at, created_at FROM categories WHERE deleted_at IS NOT NULL";
        if ($search) $sql .= " AND (name LIKE ?)";
        $stmt = $db->prepare($sql);
        $search ? $stmt->execute(["%{$search}%"]) : $stmt->execute();
        $deletedItems = array_merge($deletedItems, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}

// 6. Downloadable Files
if ($type === '' || $type === 'files') {
    if (columnExists($db, 'downloadable_files', 'deleted_at')) {
        $sql = "SELECT id, title as name, 'file' as type, 'File Download' as type_label, deleted_at, created_at FROM downloadable_files WHERE deleted_at IS NOT NULL";
        if ($search) $sql .= " AND (title LIKE ?)";
        $stmt = $db->prepare($sql);
        $search ? $stmt->execute(["%{$search}%"]) : $stmt->execute();
        $deletedItems = array_merge($deletedItems, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}

// 7. Gallery Albums
if ($type === '' || $type === 'albums') {
    if (columnExists($db, 'gallery_albums', 'deleted_at')) {
        $sql = "SELECT id, name, 'album' as type, 'Album Gallery' as type_label, deleted_at, created_at FROM gallery_albums WHERE deleted_at IS NOT NULL";
        if ($search) $sql .= " AND (name LIKE ?)";
        $stmt = $db->prepare($sql);
        $search ? $stmt->execute(["%{$search}%"]) : $stmt->execute();
        $deletedItems = array_merge($deletedItems, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}

// 8. Gallery Photos
if ($type === '' || $type === 'photos' || $type === 'albums') {
    if (columnExists($db, 'gallery_photos', 'deleted_at')) {
        $sql = "SELECT p.id, COALESCE(p.title, CONCAT('Photo #', p.id)) as name, 'photo' as type, 'Foto Gallery' as type_label, p.deleted_at, p.created_at, a.name as album_name
                FROM gallery_photos p
                LEFT JOIN gallery_albums a ON p.album_id = a.id
                WHERE p.deleted_at IS NOT NULL";
        if ($search) $sql .= " AND (p.title LIKE ? OR p.caption LIKE ?)";
        $stmt = $db->prepare($sql);
        $search ? $stmt->execute(["%{$search}%", "%{$search}%"]) : $stmt->execute();
        $deletedItems = array_merge($deletedItems, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}

// 9. Banners
if ($type === '' || $type === 'banners') {
    if (columnExists($db, 'banners', 'deleted_at')) {
        $sql = "SELECT id, title as name, 'banner' as type, 'Banner' as type_label, deleted_at, created_at FROM banners WHERE deleted_at IS NOT NULL";
        if ($search) $sql .= " AND (title LIKE ?)";
        $stmt = $db->prepare($sql);
        $search ? $stmt->execute(["%{$search}%"]) : $stmt->execute();
        $deletedItems = array_merge($deletedItems, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}

// 10. Contact Messages
if ($type === '' || $type === 'contacts') {
    if (columnExists($db, 'contact_messages', 'deleted_at')) {
        $sql = "SELECT id, subject as name, 'contact' as type, 'Pesan Kontak' as type_label, deleted_at, created_at FROM contact_messages WHERE deleted_at IS NOT NULL";
        if ($search) $sql .= " AND (subject LIKE ? OR message LIKE ?)";
        $stmt = $db->prepare($sql);
        $search ? $stmt->execute(["%{$search}%", "%{$search}%"]) : $stmt->execute();
        $deletedItems = array_merge($deletedItems, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}

// Sort by deleted_at DESC
usort($deletedItems, function($a, $b) {
    return strtotime($b['deleted_at']) - strtotime($a['deleted_at']);
});

// =================================================================
// STEP 3 - Paginate dan dapatkan hitungan untuk SUMMARY CARDS
// =================================================================

$totalItems = count($deletedItems);
$totalPages = ceil($totalItems / $perPage);
$paginatedItems = array_slice($deletedItems, $offset, $perPage);

$summaryCardCounts = [
    'posts' => 0, 'services' => 0, 'users' => 0, 'pages' => 0, // 'pages' DITAMBAHKAN
    'categories' => 0, 'files' => 0, 'albums' => 0, 'photos' => 0, 
    'banners' => 0, 'contacts' => 0,
];

foreach ($deletedItems as $item) {
    if ($item['type'] === 'post') $summaryCardCounts['posts']++;
    if ($item['type'] === 'service') $summaryCardCounts['services']++;
    if ($item['type'] === 'user') $summaryCardCounts['users']++;
    if ($item['type'] === 'page') $summaryCardCounts['pages']++; // 'page' DITAMBAHKAN
    if ($item['type'] === 'category') $summaryCardCounts['categories']++;
    if ($item['type'] === 'file') $summaryCardCounts['files']++;
    if ($item['type'] === 'album') $summaryCardCounts['albums']++;
    if ($item['type'] === 'photo') $summaryCardCounts['photos']++;
    if ($item['type'] === 'banner') $summaryCardCounts['banners']++;
    if ($item['type'] === 'contact') $summaryCardCounts['contacts']++;
}

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12 col-md-6 mb-3 mb-md-0">
                <h3><i class=""></i><?= $pageTitle ?></h3>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-md-end">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Trash</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="row mb-4">
            <div class="col-6 col-md-2 mb-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="text-primary mb-2">
                            <i class="bi bi-trash3 fs-3"></i>
                        </div>
                        <h5 class="mb-0"><?= $totalItems ?></h5> 
                        <small class="text-muted">Total Items</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-2 mb-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="text-primary mb-2">
                            <i class="bi bi-newspaper fs-3"></i>
                        </div>
                        <h5 class="mb-0"><?= $summaryCardCounts['posts'] ?></h5>
                        <small class="text-muted">Posts</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-2 mb-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="text-success mb-2">
                            <i class="bi bi-gear fs-3"></i>
                        </div>
                        <h5 class="mb-0"><?= $summaryCardCounts['services'] ?></h5>
                        <small class="text-muted">Layanan</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-2 mb-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="text-info mb-2">
                            <i class="bi bi-people fs-3"></i>
                        </div>
                        <h5 class="mb-0"><?= $summaryCardCounts['users'] ?></h5>
                        <small class="text-muted">Users</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-2 mb-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="text-warning mb-2">
                            <i class="bi bi-download fs-3"></i>
                        </div>
                        <h5 class="mb-0"><?= $summaryCardCounts['files'] ?></h5>
                        <small class="text-muted">Files</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-2 mb-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="text-secondary mb-2">
                            <i class="bi bi-images fs-3"></i>
                        </div>
                        <h5 class="mb-0"><?= $summaryCardCounts['albums'] + $summaryCardCounts['photos'] ?></h5>
                        <small class="text-muted">Gallery</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-6 col-md-4">
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle w-100" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-funnel me-2"></i>
                                <span id="filterLabel">
                                    <?php 
                                    if ($type === '') echo 'Semua Item';
                                    else if ($type === 'posts') echo 'Posts';
                                    else if ($type === 'services') echo 'Layanan';
                                    else if ($type === 'users') echo 'Users';
                                    else if ($type === 'pages') echo 'Halaman'; // 'pages' DITAMBAHKAN
                                    else if ($type === 'categories') echo 'Kategori';
                                    else if ($type === 'files') echo 'Files';
                                    else if ($type === 'albums' || $type === 'photos') echo 'Gallery';
                                    else if ($type === 'banners') echo 'Banner';
                                    else if ($type === 'contacts') echo 'Pesan Kontak';
                                    ?>
                                </span>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="filterDropdown">
                                <li>
                                    <a class="dropdown-item <?= $type === '' ? 'active' : '' ?>" href="?">
                                        <i class="bi bi-list-ul me-2"></i> Semua Item
                                        <span class="badge bg-secondary float-end"><?= $totalDropdownItems ?></span>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item <?= $type === 'posts' ? 'active' : '' ?>" href="?type=posts">
                                        <i class="bi bi-newspaper me-2"></i> Posts
                                        <span class="badge bg-primary float-end"><?= $dropdownCounts['posts'] ?></span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item <?= $type === 'services' ? 'active' : '' ?>" href="?type=services">
                                        <i class="bi bi-gear me-2"></i> Layanan
                                        <span class="badge bg-success float-end"><?= $dropdownCounts['services'] ?></span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item <?= $type === 'users' ? 'active' : '' ?>" href="?type=users">
                                        <i class="bi bi-people me-2"></i> Users
                                        <span class="badge bg-info float-end"><?= $dropdownCounts['users'] ?></span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item <?= $type === 'pages' ? 'active' : '' ?>" href="?type=pages">
                                        <i class="bi bi-file-earmark-text me-2"></i> Halaman
                                        <span class="badge bg-info float-end"><?= $dropdownCounts['pages'] ?></span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item <?= $type === 'categories' ? 'active' : '' ?>" href="?type=categories">
                                        <i class="bi bi-tags me-2"></i> Kategori
                                        <span class="badge bg-secondary float-end"><?= $dropdownCounts['categories'] ?></span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item <?= $type === 'files' ? 'active' : '' ?>" href="?type=files">
                                        <i class="bi bi-download me-2"></i> Files
                                        <span class="badge bg-warning text-dark float-end"><?= $dropdownCounts['files'] ?></span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item <?= $type === 'albums' || $type === 'photos' ? 'active' : '' ?>" href="?type=albums">
                                        <i class="bi bi-images me-2"></i> Gallery
                                        <span class="badge bg-secondary float-end"><?= $dropdownCounts['albums'] + $dropdownCounts['photos'] ?></span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item <?= $type === 'banners' ? 'active' : '' ?>" href="?type=banners">
                                        <i class="bi bi-card-image me-2"></i> Banner
                                        <span class="badge bg-primary float-end"><?= $dropdownCounts['banners'] ?? 0 ?></span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item <?= $type === 'contacts' ? 'active' : '' ?>" href="?type=contacts">
                                        <i class="bi bi-envelope me-2"></i> Pesan Kontak
                                        <span class="badge bg-secondary float-end"><?= $dropdownCounts['contacts'] ?? 0 ?></span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-6 col-md-8 mt-3 mt-md-0">
                        <div class="d-flex justify-content-end">
                            <?php if ($totalItems > 0): ?>
                                <a href="trash_empty.php<?= $type ? '?type=' . $type : '' ?>" 
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('PERINGATAN: Ini akan menghapus PERMANEN semua item di trash. Tindakan ini TIDAK BISA dibatalkan!\n\nLanjutkan?')">
                                    <i class="bi bi-trash3-fill"></i> Kosongkan Trash
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card-body">
                <div class="row g-2 mb-4">
                    <div class="col-12">
                        <form method="GET" class="d-flex gap-2">
                            <?php if ($type): ?>
                                <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
                            <?php endif; ?>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" 
                                       class="form-control" 
                                       name="search" 
                                       placeholder="Cari item..." 
                                       value="<?= htmlspecialchars($search) ?>">
                                <button class="btn btn-primary" type="submit">
                                    Cari
                                </button>
                                <?php if ($search): ?>
                                    <a href="?<?= $type ? 'type=' . $type : '' ?>" class="btn btn-outline-secondary">
                                        <i class="bi bi-x-lg"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if (empty($paginatedItems)): ?>
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="bi bi-trash3 text-muted" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="text-muted">
                            <?php if ($search): ?>
                                Tidak ada item yang cocok dengan pencarian "<strong><?= htmlspecialchars($search) ?></strong>"
                            <?php else: ?>
                                Trash kosong. Tidak ada item yang dihapus.
                            <?php endif; ?>
                        </h5>
                    </div>
                <?php else: ?>
                    <div class="table-responsive d-none d-md-block">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Name</th>
                                    <th>Dihapus</th>
                                    <th>Days in Trash</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paginatedItems as $item): ?>
                                    <?php
                                    $daysInTrash = floor((time() - strtotime($item['deleted_at'])) / 86400);
                                    $warningClass = $daysInTrash >= 25 ? 'table-danger' : ($daysInTrash >= 20 ? 'table-warning' : '');
                                    ?>
                                    <tr class="<?= $warningClass ?>">
                                        <td>
                                            <?php
                                            $typeIcons = [
                                                'post' => '<span class="badge bg-primary"><i class="bi bi-newspaper me-1"></i> Post</span>',
                                                'service' => '<span class="badge bg-success"><i class="bi bi-gear me-1"></i> Layanan</span>',
                                                'user' => '<span class="badge bg-info"><i class="bi bi-people me-1"></i> User</span>',
                                                'page' => '<span class="badge bg-info"><i class="bi bi-file-earmark-text me-1"></i> Halaman</span>', // 'page' DITAMBAHKAN
                                                'category' => '<span class="badge bg-secondary"><i class="bi bi-tags me-1"></i> Kategori</span>',
                                                'file' => '<span class="badge bg-warning text-dark"><i class="bi bi-download me-1"></i> File</span>',
                                                'album' => '<span class="badge bg-secondary"><i class="bi bi-images me-1"></i> Album</span>',
                                                'photo' => '<span class="badge bg-secondary"><i class="bi bi-image me-1"></i> Photo</span>',
                                                'banner' => '<span class="badge bg-primary"><i class="bi bi-card-image me-1"></i> Banner</span>',
                                                'contact' => '<span class="badge bg-secondary"><i class="bi bi-envelope me-1"></i> Pesan</span>'
                                            ];
                                            echo $typeIcons[$item['type']] ?? '';
                                            ?>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?= htmlspecialchars($item['name']) ?></strong>
                                                <?php if (isset($item['album_name']) && $item['album_name']): ?>
                                                    <br><small class="text-muted">Album: <?= htmlspecialchars($item['album_name']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <small><?= formatTanggal($item['deleted_at'], 'd M Y') ?></small>
                                            <br><small class="text-muted"><?= formatTanggal($item['deleted_at'], 'H:i') ?></small>
                                        </td>
                                        <td>
                                            <div>
                                                <span class="badge <?= $daysInTrash >= 25 ? 'bg-danger' : ($daysInTrash >= 20 ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                                                    <?= $daysInTrash ?> hari
                                                </span>
                                                <?php if ($daysInTrash >= 25): ?>
                                                    <br><small class="text-danger">Auto-delete dalam <?= 30 - $daysInTrash ?> hari</small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="trash_restore.php?type=<?= $item['type'] ?>&id=<?= $item['id'] ?>" 
                                                   class="btn btn-success"
                                                   title="Restore">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </a>
                                                <a href="trash_delete.php?type=<?= $item['type'] ?>&id=<?= $item['id'] ?>" 
                                                   class="btn btn-danger"
                                                   onclick="return confirm('PERINGATAN: Hapus permanen item ini? Tindakan TIDAK BISA dibatalkan!')"
                                                   title="Hapus Permanen">
                                                    <i class="bi bi-trash3-fill"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-md-none">
                        <?php foreach ($paginatedItems as $item): ?>
                            <?php
                            $daysInTrash = floor((time() - strtotime($item['deleted_at'])) / 86400);
                            $warningClass = $daysInTrash >= 25 ? 'border-danger' : ($daysInTrash >= 20 ? 'border-warning' : '');
                            ?>
                            <div class="card mb-3 shadow-sm <?= $warningClass ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <?php
                                            $typeIcons = [
                                                'post' => '<span class="badge bg-primary"><i class="bi bi-newspaper me-1"></i> Post</span>',
                                                'service' => '<span class="badge bg-success"><i class="bi bi-gear me-1"></i> Layanan</span>',
                                                'user' => '<span class="badge bg-info"><i class="bi bi-people me-1"></i> User</span>',
                                                'page' => '<span class="badge bg-info"><i class="bi bi-file-earmark-text me-1"></i> Halaman</span>', // 'page' DITAMBAHKAN
                                                'category' => '<span class="badge bg-secondary"><i class="bi bi-tags me-1"></i> Kategori</span>',
                                                'file' => '<span class="badge bg-warning text-dark"><i class="bi bi-download me-1"></i> File</span>',
                                                'album' => '<span class="badge bg-secondary"><i class="bi bi-images me-1"></i> Album</span>',
                                                'photo' => '<span class="badge bg-secondary"><i class="bi bi-image me-1"></i> Photo</span>',
                                                'banner' => '<span class="badge bg-primary"><i class="bi bi-card-image me-1"></i> Banner</span>',
                                                'contact' => '<span class="badge bg-secondary"><i class="bi bi-envelope me-1"></i> Pesan</span>'
                                            ];
                                            echo $typeIcons[$item['type']] ?? '';
                                            ?>
                                        </div>
                                        <span class="badge <?= $daysInTrash >= 25 ? 'bg-danger' : ($daysInTrash >= 20 ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                                            <?= $daysInTrash ?> hari
                                        </span>
                                    </div>
                                    <h6 class="mb-1"><?= htmlspecialchars($item['name']) ?></h6>
                                    <?php if (isset($item['album_name']) && $item['album_name']): ?>
                                        <small class="text-muted d-block mb-2">Album: <?= htmlspecialchars($item['album_name']) ?></small>
                                    <?php endif; ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i> <?= formatTanggal($item['deleted_at'], 'd M Y H:i') ?>
                                        </small>
                                        <?php if ($daysInTrash >= 25): ?>
                                            <small class="text-danger">
                                                <i class="bi bi-exclamation-triangle me-1"></i> Auto-delete dalam <?= 30 - $daysInTrash ?> hari
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="trash_restore.php?type=<?= $item['type'] ?>&id=<?= $item['id'] ?>" 
                                           class="btn btn-success btn-sm flex-fill">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i> Restore
                                        </a>
                                        <a href="trash_delete.php?type=<?= $item['type'] ?>&id=<?= $item['id'] ?>" 
                                           class="btn btn-danger btn-sm flex-fill"
                                           onclick="return confirm('Hapus permanen?')">
                                            <i class="bi bi-trash3-fill me-1"></i> Hapus
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($totalPages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination pagination-sm justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page - 1 ?><?= $type ? '&type=' . $type : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                            <i class="bi bi-chevron-left"></i>
                                            <span class="d-none d-md-inline ms-1">Previous</span>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                for ($i = $startPage; $i <= $endPage; $i++):
                                ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?><?= $type ? '&type=' . $type : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page + 1 ?><?= $type ? '&type=' . $type : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                            <span class="d-none d-md-inline me-1">Next</span>
                                            <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>