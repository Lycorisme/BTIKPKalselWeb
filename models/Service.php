<?php
/**
 * Service Model
 * Complete CRUD operations for services with author name fix
 */

class Service extends Model {
    
    protected $table = 'services';
    
    /**
     * Find service by ID with author name
     * Override parent find() to include LEFT JOIN
     * 
     * @param int $id
     * @return array|false
     */
    public function find($id) {
        $sql = "
            SELECT s.*, u.name as author_name
            FROM {$this->table} s
            LEFT JOIN users u ON s.author_id = u.id
            WHERE s.id = ? AND s.deleted_at IS NULL
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Get all services - EXCLUDE DELETED
     */
    public function getAll() {
        $sql = "
            SELECT s.*, u.name as author_name
            FROM {$this->table} s
            LEFT JOIN users u ON s.author_id = u.id
            WHERE s.deleted_at IS NULL
            ORDER BY s.`order` ASC, s.created_at DESC
        ";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Get paginated services with filters
     */
    public function getPaginated($page = 1, $perPage = 10, $filters = []) {
        $offset = ($page - 1) * $perPage;
        $params = [];
        
        // Build WHERE clause
        $where = [];
        
        // Show deleted or not
        if (empty($filters['show_deleted'])) {
            $where[] = "s.deleted_at IS NULL";
        }
        
        // Status filter
        if (!empty($filters['status'])) {
            $where[] = "s.status = ?";
            $params[] = $filters['status'];
        }
        
        // Featured filter
        if (isset($filters['featured']) && $filters['featured'] !== '') {
            $where[] = "s.featured = ?";
            $params[] = $filters['featured'];
        }
        
        // Search filter
        if (!empty($filters['search'])) {
            $where[] = "(s.title LIKE ? OR s.description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Default WHERE
        if (empty($where)) {
            $where[] = "1=1";
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM {$this->table} s WHERE $whereClause";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Get data
        $sql = "
            SELECT s.*, u.name as author_name
            FROM {$this->table} s
            LEFT JOIN users u ON s.author_id = u.id
            WHERE $whereClause
            ORDER BY s.`order` ASC, s.created_at DESC
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
     * Find service by slug
     */
    public function findBySlug($slug) {
        $sql = "
            SELECT s.*, u.name as author_name
            FROM {$this->table} s
            LEFT JOIN users u ON s.author_id = u.id
            WHERE s.slug = ? AND s.deleted_at IS NULL
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$slug]);
        return $stmt->fetch();
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
     * Get featured services
     */
    public function getFeatured($limit = 3) {
        $sql = "
            SELECT * FROM {$this->table}
            WHERE featured = 1 AND status = 'published' AND deleted_at IS NULL
            ORDER BY `order` ASC, created_at DESC
            LIMIT ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get published services
     */
    public function getPublished($limit = null) {
        $sql = "
            SELECT * FROM {$this->table}
            WHERE status = 'published' AND deleted_at IS NULL
            ORDER BY `order` ASC, created_at DESC
        ";
        
        if ($limit) {
            $sql .= " LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit]);
        } else {
            $stmt = $this->db->query($sql);
        }
        
        return $stmt->fetchAll();
    }
    
    /**
     * Count services by status
     */
    public function countByStatus() {
        $sql = "
            SELECT 
                status,
                COUNT(*) as count
            FROM {$this->table}
            WHERE deleted_at IS NULL
            GROUP BY status
        ";
        
        $stmt = $this->db->query($sql);
        $results = $stmt->fetchAll();
        
        $counts = [
            'draft' => 0,
            'published' => 0,
            'archived' => 0
        ];
        
        foreach ($results as $row) {
            $counts[$row['status']] = (int)$row['count'];
        }
        
        return $counts;
    }
    
    /**
     * Soft delete with slug modification
     */
    public function softDelete($id) {
        try {
            $service = $this->find($id);
            
            if (!$service) {
                return false;
            }
            
            // Modify slug to prevent conflicts
            $timestamp = time();
            $newSlug = $service['slug'] . '_deleted' . $timestamp;
            
            $sql = "UPDATE {$this->table} 
                    SET slug = ?, 
                        status = 'archived',
                        deleted_at = NOW(),
                        updated_at = NOW()
                    WHERE id = ? AND deleted_at IS NULL";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$newSlug, $id]);
            
            return $stmt->rowCount() > 0;
            
        } catch (PDOException $e) {
            error_log("Error in softDelete: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Insert service
     */
    public function insert($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        return parent::insert($data);
    }
    
    /**
     * Update service - FORCE updated_at refresh
     */
    public function update($id, $data) {
        // Always set updated_at to NOW even if no changes
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // Force update by using raw SQL
        $fields = [];
        $values = [];
        
        foreach ($data as $field => $value) {
            $fields[] = "`{$field}` = ?";
            $values[] = $value;
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . 
               " WHERE id = ?";
        
        $values[] = $id;
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($values);
        
        // Debug log
        error_log("Updated service ID {$id} at " . $data['updated_at']);
        
        return $result;
    }
    
} 