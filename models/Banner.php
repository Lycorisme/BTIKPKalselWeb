<?php
require_once __DIR__ . '/../core/Model.php';

/**
 * Banner Model - Complete CRUD with Soft Delete & Drag/Drop Reordering
 */
class Banner extends Model {
    protected $table = 'banners';
    
    /**
     * Get all active banners - EXCLUDE DELETED
     */
    public function getActiveBanners($limit = null) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE is_active = 1 AND deleted_at IS NULL 
                ORDER BY ordering ASC, created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get banner by ID - EXCLUDE DELETED
     */
    public function getBannerById($id) {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE id = ? AND deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get banner by slug - EXCLUDE DELETED
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
     * Get paginated banners with filters
     */
    public function getPaginated($page = 1, $perPage = 20, $filters = []) {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = [];
        
        // Default: exclude deleted
        if (empty($filters['show_deleted'])) {
            $where[] = "deleted_at IS NULL";
        }
        
        // Status filter
        if (isset($filters['status']) && $filters['status'] !== '') {
            $where[] = "is_active = ?";
            $params[] = (int)$filters['status'];
        }
        
        // Search filter
        if (!empty($filters['search'])) {
            $where[] = "(title LIKE ? OR caption LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
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
            ORDER BY ordering ASC, created_at DESC
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
     * Soft delete banner
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
     * Update banner ordering
     */
    public function updateOrdering($bannerId, $ordering) {
        try {
            $sql = "UPDATE {$this->table} 
                    SET ordering = ?, updated_at = NOW()
                    WHERE id = ? AND deleted_at IS NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$ordering, $bannerId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error in updateOrdering: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Insert banner
     */
    public function insert($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        return parent::insert($data);
    }
    
    /**
     * Update banner
     */
    public function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return parent::update($id, $data);
    }
}
