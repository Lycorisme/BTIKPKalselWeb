<?php
/**
 * BTIKP AJAX Handler
 * Endpoint terpusat untuk semua modul (Datatable, dll)
 *
 * File: C:\laragon\www\btikp-kalsel\admin\ajax\ajax_custom.php
 * VERSI PERBAIKAN
 */

session_start();

// PATHING:
// ajax_custom.php ada di /admin/ajax/
// auth_check.php ada di /admin/includes/
// Path yang benar adalah C:\laragon\www\btikp-kalsel\admin\includes\auth_check.php
require_once dirname(__DIR__) . '/includes/auth_check.php';

// Cek jika auth_check.php sudah memuat file-file ini.
// Jika sudah, 2 baris ini tidak wajib, tapi aman jika ada.
require_once dirname(__DIR__, 2) . '/core/Database.php';
require_once dirname(__DIR__, 2) . '/core/Helper.php';

// Security check
// FUNGSI INI SEKARANG DIKENALI KARENA auth_check.php
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$module = $input['module'] ?? '';

// Response helper
function jsonResponse($success, $data = [], $message = '') {
    // ... (sisa kode sama persis seperti respons saya sebelumnya) ...
    // ... (fungsi loadData, deleteData, restoreData, getModuleConfig, formatItem) ...
}

// Main router
switch ($action) {
    case 'load_data':
        loadData($module, $input);
        break;
        
    case 'delete':
        deleteData($module, $input);
        break;
        
    case 'restore':
        restoreData($module, $input);
        break;
        
    default:
        jsonResponse(false, [], 'Invalid action');
}

// --- FUNGSI-FUNGSI HELPER (loadData, deleteData, restoreData, dll.) ---
// --- Salin-tempel SEMUA fungsi dari respons saya sebelumnya ke sini ---

/**
 * Load data dengan pagination, search, dan filter soft delete
 */
function loadData($module, $input) {
    $db = Database::getInstance()->getConnection();
    
    $page = max(1, (int)($input['page'] ?? 1));
    $perPage = max(1, min(100, (int)($input['perPage'] ?? 10)));
    $search = trim($input['search'] ?? '');
    $showDeleted = (bool)($input['showDeleted'] ?? false);
    
    $offset = ($page - 1) * $perPage;
    
    $config = getModuleConfig($module);
    if (!$config) {
        jsonResponse(false, [], 'Modul tidak valid');
    }
    
    $table = $config['table'];
    $searchFields = $config['searchFields'];
    $orderBy = $config['orderBy'] ?? 'created_at DESC';
    
    $where = [];
    $params = [];
    
    if ($showDeleted) {
        $where[] = 'deleted_at IS NOT NULL';
    } else {
        $where[] = 'deleted_at IS NULL';
    }
    
    if ($search) {
        $searchConditions = [];
        foreach ($searchFields as $field) {
            $searchConditions[] = "$field LIKE ?";
            $params[] = "%{$search}%";
        }
        $where[] = '(' . implode(' OR ', $searchConditions) . ')';
    }
    
    $whereClause = 'WHERE ' . implode(' AND ', $where);
    
    $countSql = "SELECT COUNT(*) FROM {$table} {$whereClause}";
    $stmtCount = $db->prepare($countSql);
    $stmtCount->execute($params);
    $totalItems = (int)$stmtCount->fetchColumn();
    
    $sql = "SELECT * FROM {$table} {$whereClause} ORDER BY {$orderBy} LIMIT {$perPage} OFFSET {$offset}";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalPages = $totalItems > 0 ? ceil($totalItems / $perPage) : 1;
    $from = $totalItems > 0 ? $offset + 1 : 0;
    $to = min($offset + $perPage, $totalItems);
    
    $formattedItems = array_map(function($item) use ($module, $offset, $items) {
        $index = array_search($item, $items);
        return formatItem($module, $item, $offset + $index + 1);
    }, $items);
    
    jsonResponse(true, [
        'items' => $formattedItems,
        'pagination' => [
            'current' => $page,
            'total' => $totalPages,
            'perPage' => $perPage,
            'totalItems' => $totalItems,
            'from' => $from,
            'to' => $to
        ]
    ]);
}

/**
 * Hapus data (soft atau permanent)
 */
function deleteData($module, $input) {
    $db = Database::getInstance()->getConnection();
    
    $id = (int)($input['id'] ?? 0);
    $permanent = (bool)($input['permanent'] ?? false);
    
    if (!$id) {
        jsonResponse(false, [], 'ID tidak valid');
    }
    
    if (!hasRole(['super_admin', 'admin'])) {
        jsonResponse(false, [], 'Anda tidak memiliki akses untuk menghapus data');
    }
    
    $config = getModuleConfig($module);
    if (!$config) {
        jsonResponse(false, [], 'Modul tidak valid');
    }
    
    $table = $config['table'];
    $titleField = $config['titleField'] ?? 'title';
    
    try {
        $stmt = $db->prepare("SELECT * FROM {$table} WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            jsonResponse(false, [], 'Data tidak ditemukan');
        }
        $itemTitle = $item[$titleField] ?? $item['name'] ?? $id;

        if ($permanent) {
            $stmt = $db->prepare("DELETE FROM {$table} WHERE id = ?");
            $stmt->execute([$id]);
            // logActivity('DELETE_PERMANENT', ...);
            jsonResponse(true, [], 'Data berhasil dihapus permanen');
        } else {
            $stmt = $db->prepare("UPDATE {$table} SET deleted_at = NOW(), updated_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);
            // logActivity('DELETE', ...);
            jsonResponse(true, [], 'Data berhasil dipindahkan ke Trash');
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        jsonResponse(false, [], 'Gagal menghapus data. Periksa relasi data.');
    }
}

/**
 * Restore data yang di-soft-delete
 */
function restoreData($module, $input) {
    $db = Database::getInstance()->getConnection();
    
    $id = (int)($input['id'] ?? 0);
    
    if (!$id) {
        jsonResponse(false, [], 'ID tidak valid');
    }
    
    if (!hasRole(['super_admin', 'admin'])) {
        jsonResponse(false, [], 'Anda tidak memiliki akses untuk restore data');
    }
    
    $config = getModuleConfig($module);
    if (!$config) {
        jsonResponse(false, [], 'Modul tidak valid');
    }
    
    $table = $config['table'];
    $titleField = $config['titleField'] ?? 'title';
    
    try {
        $stmt = $db->prepare("SELECT * FROM {$table} WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            jsonResponse(false, [], 'Data tidak ditemukan');
        }
        $itemTitle = $item[$titleField] ?? $item['name'] ?? $id;

        $stmt = $db->prepare("UPDATE {$table} SET deleted_at = NULL, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        
        // logActivity('RESTORE', ...);
        jsonResponse(true, [], 'Data berhasil direstore');
    } catch (PDOException $e) {
        error_log($e->getMessage());
        jsonResponse(false, [], 'Gagal merestore data');
    }
}

/**
 * Mengambil konfigurasi modul
 */
function getModuleConfig($module) {
    $configs = [
        'services' => [
            'table' => 'services',
            'titleField' => 'title',
            'searchFields' => ['title', 'slug', 'description'],
            'orderBy' => 'created_at DESC'
        ],
        'posts' => [
            'table' => 'posts',
            'titleField' => 'title',
            'searchFields' => ['title', 'slug', 'excerpt', 'content'],
            'orderBy' => 'created_at DESC'
        ],
        'pages' => [
            'table' => 'pages',
            'titleField' => 'title',
            'searchFields' => ['title', 'slug', 'content'],
            'orderBy' => 'created_at DESC'
        ],
        'categories' => [
            'table' => 'categories',
            'titleField' => 'name',
            'searchFields' => ['name', 'slug', 'description'],
            'orderBy' => 'name ASC'
        ],
    ];
    
    return $configs[$module] ?? null;
}

/**
 * Memformat item untuk ditampilkan di tabel
 */
function formatItem($module, $item, $no) {
    $formatted = [
        'no' => $no,
        'id' => $item['id'],
        'deleted' => !empty($item['deleted_at'])
    ];
    
    switch ($module) {
        case 'services':
            $formatted['title'] = htmlspecialchars($item['title']);
            $formatted['slug'] = htmlspecialchars($item['slug']);
            $formatted['description'] = truncateText($item['description'], 100);
            $formatted['website_url'] = $item['website_url'];
            $formatted['status'] = $item['status'];
            $formatted['created_at'] = formatTanggal($item['created_at'], 'd M Y');
            break;
            
        // ... (modul lain)
    }
    
    return $formatted;
}