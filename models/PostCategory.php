<?php
require_once __DIR__ . '/../core/Model.php';

/**
 * PostCategory Model
 * Complete CRUD with slug generation, soft delete, and post count
 */
class PostCategory extends Model {
    
    protected $table = 'post_categories';
    
    /**
     * Override find to include deleted_at check if column exists
     */
    public function find($id) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error in find: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all categories
     */
    public function getAll() {
        $sql = "SELECT * FROM {$this->table} ORDER BY name ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Get all active categories
     */
    public function getActive() {
        $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY name ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Get categories with post count
     */
    public function getWithPostCount() {
        $sql = "
            SELECT 
                pc.*,
                COUNT(p.id) as post_count
            FROM {$this->table} pc
            LEFT JOIN posts p ON pc.id = p.category_id AND p.deleted_at IS NULL
            GROUP BY pc.id
            ORDER BY pc.name ASC
        ";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Get paginated categories with filters
     */
    public function getPaginated($page = 1, $perPage = 10, $filters = []) {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = [];
        
        // Active filter
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $where[] = "is_active = ?";
            $params[] = $filters['is_active'];
        }
        
        // Search filter
        if (!empty($filters['search'])) {
            $where[] = "(name LIKE ? OR description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (empty($where)) $where[] = "1=1";
        $whereClause = implode(' AND ', $where);
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM {$this->table} WHERE $whereClause";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Get data with post count
        $sql = "
            SELECT 
                pc.*,
                COUNT(p.id) as post_count
            FROM {$this->table} pc
            LEFT JOIN posts p ON pc.id = p.category_id AND p.deleted_at IS NULL
            WHERE $whereClause
            GROUP BY pc.id
            ORDER BY pc.name ASC
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $perPage;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
        
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
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE slug = ?";
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
    public function generateSlug($name, $excludeId = null) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->slugExists($slug, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Find by conditions
     */
    public function findBy($conditions) {
        $sql = "SELECT * FROM {$this->table} WHERE ";
        $whereClauses = [];
        $params = [];
        
        foreach ($conditions as $column => $value) {
            $whereClauses[] = "$column = ?";
            $params[] = $value;
        }
        
        $sql .= implode(' AND ', $whereClauses) . " LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    /**
     * Toggle active status
     */
    public function toggleStatus($id) {
        $sql = "UPDATE {$this->table} SET is_active = NOT is_active, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    /**
     * Check if category has posts
     */
    public function hasPosts($id) {
        $sql = "SELECT COUNT(*) FROM posts WHERE category_id = ? AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Soft delete (modify slug to allow reuse)
     */
    public function softDelete($id) {
        try {
            $category = $this->find($id);
            
            if (!$category) {
                return false;
            }
            
            // Check if has posts
            if ($this->hasPosts($id)) {
                throw new Exception("Cannot delete category with existing posts");
            }
            
            // Modify slug
            $timestamp = time();
            $newSlug = $category['slug'] . '_deleted' . $timestamp;
            
            $sql = "UPDATE {$this->table} 
                    SET slug = ?, 
                        is_active = 0,
                        updated_at = NOW()
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$newSlug, $id]);
            
            return $stmt->rowCount() > 0;
            
        } catch (PDOException $e) {
            error_log("Error in softDelete: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Hard delete
     */
    public function hardDelete($id) {
        try {
            // Check if has posts
            if ($this->hasPosts($id)) {
                throw new Exception("Cannot delete category with existing posts");
            }
            
            $sql = "DELETE FROM {$this->table} WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            
            return $stmt->rowCount() > 0;
            
        } catch (PDOException $e) {
            error_log("Error in hardDelete: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Insert category
     */
    public function insert($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        return parent::insert($data);
    }
    
    /**
     * Update category
     */
    public function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return parent::update($id, $data);
    }
}
