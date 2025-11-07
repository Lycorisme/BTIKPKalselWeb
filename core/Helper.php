<?php
/**
 * Helper Functions
 */

function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: " . $url);
    exit;
}

function setAlert($type, $message) {
    $_SESSION['alert'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return $alert;
    }
    return null;
}

function formatTanggal($date, $format = 'd F Y') {
    if (empty($date) || $date == '0000-00-00' || $date == '0000-00-00 00:00:00') {
        return '-';
    }
    
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return '-';
    }
    
    $d = date('j', $timestamp);
    $m = date('n', $timestamp);
    $y = date('Y', $timestamp);
    $h = date('H', $timestamp);
    $i = date('i', $timestamp);
    
    if ($format == 'd F Y') {
        return $d . ' ' . $bulan[(int)$m] . ' ' . $y;
    } elseif ($format == 'd F Y H:i') {
        return $d . ' ' . $bulan[(int)$m] . ' ' . $y . ' ' . $h . ':' . $i;
    } elseif ($format == 'd M Y') {
        $bulanSingkat = [
            1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
            'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
        ];
        return $d . ' ' . $bulanSingkat[(int)$m] . ' ' . $y;
    }
    
    return date($format, $timestamp);
}

function formatTanggalRelatif($date) {
    if (empty($date)) return '-';
    
    $timestamp = strtotime($date);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Baru saja';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' menit yang lalu';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' jam yang lalu';
    } elseif ($diff < 172800) {
        return 'Kemarin';
    } else {
        return formatTanggal($date);
    }
}

function generateSlug($text) {
    $text = strtolower($text);
    $text = str_replace(['á', 'à', 'â', 'ã', 'ä'], 'a', $text);
    $text = str_replace(['é', 'è', 'ê', 'ë'], 'e', $text);
    $text = str_replace(['í', 'ì', 'î', 'ï'], 'i', $text);
    $text = str_replace(['ó', 'ò', 'ô', 'õ', 'ö'], 'o', $text);
    $text = str_replace(['ú', 'ù', 'û', 'ü'], 'u', $text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

function truncateText($text, $length = 100, $suffix = '...') {
    $text = strip_tags($text);
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . $suffix;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get current logged in user
 * @return array|null
 */
function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role'],
        'photo' => $_SESSION['user_photo'] ?? null // ADD THIS
    ];
}


function hasRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $userRole = $_SESSION['user_role'];
    
    // Super admin has access to everything
    if ($userRole === 'super_admin') {
        return true;
    }
    
    // Check against provided role(s)
    if (is_array($roles)) {
        return in_array($userRole, $roles);
    }
    
    return $userRole === $roles;
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * =============================
 *  UPLOAD HELPER FUNCTIONS
 * =============================
 */

/**
 * Get upload URL (Full URL)
 * @param string $path Relative path from uploads/
 * @return string
 */
function uploadUrl($path) {
    if (empty($path)) {
        return BASE_URL . 'assets/images/no-image.png';
    }

    $path = str_replace('uploads/', '', $path);
    $path = ltrim($path, '/');

    return BASE_URL . 'uploads/' . $path;
}

/**
 * Get physical upload path
 * @param string $path
 * @return string
 */
function uploadPath($path) {
    if (empty($path)) {
        return '';
    }

    $uploadsDir = dirname(__DIR__) . '/public/uploads/';
    $path = str_replace('uploads/', '', $path);
    $path = ltrim($path, '/');

    return $uploadsDir . $path;
}

/**
 * Check if uploaded file exists
 * @param string $path
 * @return bool
 */
function uploadExists($path) {
    if (empty($path)) {
        return false;
    }

    $fullPath = uploadPath($path);
    return file_exists($fullPath);
}

/**
 * Get upload file size (bytes)
 * @param string $path
 * @return int
 */
function uploadSize($path) {
    if (!uploadExists($path)) {
        return 0;
    }

    return filesize(uploadPath($path));
}

/**
 * =============================
 *  MISC FUNCTIONS
 * =============================
 */

function formatNumber($number, $decimals = 0) {
    return number_format($number, $decimals, ',', '.');
}

function getStatusBadge($status) {
    $badges = [
        'draft' => '<span class="badge bg-secondary">Draft</span>',
        'published' => '<span class="badge bg-success">Published</span>',
        'archived' => '<span class="badge bg-warning">Archived</span>',
        'active' => '<span class="badge bg-success">Aktif</span>',
        'inactive' => '<span class="badge bg-secondary">Tidak Aktif</span>',
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}

function dd(...$vars) {
    echo '<pre style="background: #1e1e1e; color: #dcdcdc; padding: 20px; border-radius: 5px;">';
    foreach ($vars as $var) {
        var_dump($var);
    }
    echo '</pre>';
    die();
}

function logActivity($actionType, $description, $modelType = null, $modelId = null) {
    if (!isLoggedIn()) {
        return false;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        $user = getCurrentUser();
        
        $stmt = $db->prepare("
            INSERT INTO activity_logs 
            (user_id, user_name, action_type, description, model_type, model_id, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $user['id'],
            $user['name'],
            $actionType,
            $description,
            $modelType,
            $modelId,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}

function getSetting($key, $default = '') {
    static $settings = [];
    
    if (empty($settings)) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT `key`, `value` FROM settings");
            $results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            $settings = $results;
        } catch (PDOException $e) {
            error_log($e->getMessage());
        }
    }
    
    return $settings[$key] ?? $default;
}

function setSetting($key, $value) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO settings (`key`, `value`) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE `value` = ?
        ");
        return $stmt->execute([$key, $value, $value]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}

function getRoleBadge($role) {
    $badges = [
        'super_admin' => '<span class="badge bg-danger">Super Admin</span>',
        'admin' => '<span class="badge bg-primary">Admin</span>',
        'editor' => '<span class="badge bg-info">Editor</span>',
        'author' => '<span class="badge bg-success">Author</span>'
    ];
    
    return $badges[$role] ?? '<span class="badge bg-secondary">Unknown</span>';
}

/**
 * Get action color for activity logs
 * @param string $action
 * @return string
 */
function getActionColor($action) {
    $colors = [
        'CREATE' => 'success',
        'UPDATE' => 'info',
        'DELETE' => 'danger',
        'LOGIN' => 'primary',
        'LOGOUT' => 'secondary',
        'VIEW' => 'light'
    ];
    
    return $colors[$action] ?? 'secondary';
}

/**
 * Get role name
 * @param string $role
 * @return string
 */
function getRoleName($role) {
    $names = [
        'super_admin' => 'Super Admin',
        'admin' => 'Admin',
        'editor' => 'Editor',
        'author' => 'Author'
    ];
    
    return $names[$role] ?? 'Unknown';
}

/**
 * Check if user can perform action on target user
 * @param int $targetUserId
 * @param string $action (edit, delete, etc)
 * @return bool
 */
function canManageUser($targetUserId, $action = 'edit') {
    $currentUser = getCurrentUser();
    
    // Super admin can do anything
    if ($currentUser['role'] === 'super_admin') {
        return true;
    }
    
    // Get target user
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$targetUserId]);
    $targetUser = $stmt->fetch();
    
    if (!$targetUser) {
        return false;
    }
    
    // Admin can't manage super_admin
    if ($targetUser['role'] === 'super_admin') {
        return false;
    }
    
    // Can't delete self
    if ($action === 'delete' && $targetUserId == $currentUser['id']) {
        return false;
    }
    
    return true;
}

/**
 * Update current user session
 * Call this after updating user profile
 * 
 * @param int $userId
 * @return void
 */
function refreshUserSession($userId) {
    // Only refresh if updating own profile
    $currentUser = getCurrentUser();
    if ($currentUser && $currentUser['id'] == $userId) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT name, email, role, photo FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_photo'] = $user['photo'];
        }
    }
}

function publicFileUrl($path) {
    if (empty($path)) {
        return BASE_URL . 'assets/images/no-file.png'; // fallback
    }
    
    // Normalisasi path: hilangkan public/, uploads\ atau uploads/
    $filePath = str_replace(['public/', 'public\\', 'uploads\\'], '', $path);
    
    // Pastikan dimulai dengan uploads/
    if (strpos($filePath, 'uploads/') !== 0) {
        $filePath = 'uploads/' . ltrim($filePath, '/');
    }
    
    return BASE_URL . $filePath;
}

function bannerImageUrl($image_path) {
    $filename = basename($image_path);
    return BASE_URL . 'uploads/banners/' . $filename;
}

function slugify($text) {
    // Ganti spasi dan karakter khusus dengan '-'
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // Transliterate (ubah karakter non ASCII ke setara ASCII)
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    // Hapus karakter yang bukan huruf, angka atau strip
    $text = preg_replace('~[^-\w]+~', '', $text);
    // Hapus strip berlebih di awal dan akhir
    $text = trim($text, '-');
    // Hapus strip yang berulang-ulang
    $text = preg_replace('~-+~', '-', $text);
    // Ubah ke huruf kecil
    $text = strtolower($text);

    if (empty($text)) {
        return 'n-a';
    }
    return $text;
}
