<?php
require_once __DIR__ . '/../core/Model.php';

/**
 * File Model (Downloadable Files) - Complete CRUD with Soft Delete
 */
class File extends Model {
    protected $table = 'downloadable_files';
    
    /**
     * Get all active files - EXCLUDE DELETED
     */
    public function getAllFiles($category_id = null, $limit = null) {
        $sql = "SELECT f.*, c.name as category_name 
                FROM {$this->table} f
                LEFT JOIN file_categories c ON f.category_id = c.id
                WHERE f.is_active = 1 AND f.deleted_at IS NULL";
        
        if ($category_id) {
            $sql .= " AND f.category_id = " . (int)$category_id;
        }
        
        $sql .= " ORDER BY f.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get file by ID - EXCLUDE DELETED
     */
    public function getFileById($id) {
        $stmt = $this->db->prepare("
            SELECT f.*, c.name as category_name 
            FROM {$this->table} f
            LEFT JOIN file_categories c ON f.category_id = c.id
            WHERE f.id = ? AND f.deleted_at IS NULL
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get file by slug - EXCLUDE DELETED
     */
    public function getBySlug($slug) {
        $stmt = $this->db->prepare("
            SELECT f.*, c.name as category_name 
            FROM {$this->table} f
            LEFT JOIN file_categories c ON f.category_id = c.id
            WHERE f.slug = ? AND f.deleted_at IS NULL
        ");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get paginated files with filters
     */
    public function getPaginated($page = 1, $perPage = 20, $filters = []) {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = [];
        
        // Default: exclude deleted
        if (empty($filters['show_deleted'])) {
            $where[] = "deleted_at IS NULL";
        }
        
        // Search filter
        if (!empty($filters['search'])) {
            $where[] = "(title LIKE ? OR description LIKE ? OR file_type LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Status filter
        if (isset($filters['status']) && $filters['status'] !== '') {
            $where[] = "is_active = ?";
            $params[] = (int)$filters['status'];
        }
        
        // Category filter
        if (!empty($filters['category_id'])) {
            $where[] = "category_id = ?";
            $params[] = (int)$filters['category_id'];
        }
        
        if (empty($where)) $where[] = "1=1";
        $whereClause = implode(' AND ', $where);
        
        // Count total
        $countSql = "SELECT COUNT(*) FROM {$this->table} WHERE $whereClause";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Get data
        $sql = "
            SELECT * FROM {$this->table}
            WHERE $whereClause
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $perPage;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }
    
    /**
     * Increment download count
     */
    public function incrementDownloadCount($id) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET download_count = download_count + 1, updated_at = NOW()
            WHERE id = ? AND deleted_at IS NULL
        ");
        return $stmt->execute([$id]);
    }
    
    /**
     * Get file categories - EXCLUDE DELETED
     */
    public function getCategories() {
        $stmt = $this->db->query("
            SELECT c.*, COUNT(f.id) as file_count
            FROM file_categories c
            LEFT JOIN {$this->table} f ON f.category_id = c.id AND f.deleted_at IS NULL
            WHERE c.deleted_at IS NULL
            GROUP BY c.id
            ORDER BY c.name ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get files by category ID - EXCLUDE DELETED
     */
    public function getFilesByCategoryId($category_id) {
        $stmt = $this->db->prepare("
            SELECT f.*, c.name as category_name 
            FROM {$this->table} f
            LEFT JOIN file_categories c ON f.category_id = c.id
            WHERE f.category_id = ? AND f.deleted_at IS NULL
            ORDER BY f.created_at DESC
        ");
        $stmt->execute([$category_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Soft delete file
     */
    public function softDelete($id) {
        try {
            $sql = "UPDATE {$this->table} 
                    SET deleted_at = NOW(), updated_at = NOW()
                    WHERE id = ? AND deleted_at IS NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error in softDelete: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Insert file
     */
    public function insert($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        return parent::insert($data);
    }
    
    /**
     * Update file
     */
    public function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return parent::update($id, $data);
    }
}
