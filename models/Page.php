<?php
require_once __DIR__ . '/../core/Model.php';

/**
 * Page Model - Complete CRUD with Soft Delete
 */
class Page extends Model {
    protected $table = 'pages';
    
    /**
     * Get page by slug - EXCLUDE DELETED
     */
    public function getBySlug($slug) {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE slug = ? AND deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get page by ID - EXCLUDE DELETED
     */
    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE id = ? AND deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all active pages - EXCLUDE DELETED
     */
    public function getAllPages() {
        $stmt = $this->db->query("
            SELECT * FROM {$this->table} 
            WHERE deleted_at IS NULL 
            ORDER BY display_order ASC, title ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get paginated pages with filters
     */
    public function getPaginated($page = 1, $perPage = 15, $filters = []) {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = [];
        
        // Default: exclude deleted
        if (empty($filters['show_deleted'])) {
            $where[] = "deleted_at IS NULL";
        }
        
        // Status filter
        if (!empty($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }
        
        // Search filter
        if (!empty($filters['search'])) {
            $where[] = "(title LIKE ? OR slug LIKE ? OR content LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
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
            ORDER BY display_order ASC, created_at DESC
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
     * Check if slug exists
     */
    public function slugExists($slug, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE slug = ? AND deleted_at IS NULL";
        $params = [$slug];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Generate unique slug
     */
    public function generateSlug($title, $excludeId = null) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->slugExists($slug, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Increment page view count
     */
    public function incrementViewCount($id) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET view_count = view_count + 1, updated_at = NOW()
            WHERE id = ? AND deleted_at IS NULL
        ");
        return $stmt->execute([$id]);
    }
    
    /**
     * Soft delete page
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
     * Insert page
     */
    public function insert($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        return parent::insert($data);
    }
    
    /**
     * Update page
     */
    public function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return parent::update($id, $data);
    }
}
