<?php
require_once __DIR__ . '/../core/Model.php';

/**
 * Service Model
 * Complete CRUD operations for services with soft delete
 */
class Service extends Model {
    
    protected $table = 'services';
    
    /**
     * Find service by ID - EXCLUDE DELETED
     */
    public function find($id) {
        $sql = "
            SELECT *
            FROM {$this->table}
            WHERE id = ? AND deleted_at IS NULL
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
            SELECT *
            FROM {$this->table}
            WHERE deleted_at IS NULL
            ORDER BY created_at DESC
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
        $where = [];

        // Show deleted or not
        if (empty($filters['show_deleted'])) {
            $where[] = "deleted_at IS NULL";
        }

        // Status filter
        if (!empty($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        // Featured filter
        if (isset($filters['featured']) && $filters['featured'] !== '') {
            $where[] = "featured = ?";
            $params[] = $filters['featured'];
        }

        // Search filter
        if (!empty($filters['search'])) {
            $where[] = "(title LIKE ? OR description LIKE ?)";
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

        // Get data
        $sql = "
            SELECT *
            FROM {$this->table}
            WHERE $whereClause
            ORDER BY created_at DESC
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
     * Find service by slug - EXCLUDE DELETED
     */
    public function findBySlug($slug) {
        $sql = "
            SELECT *
            FROM {$this->table}
            WHERE slug = ? AND deleted_at IS NULL
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
            ORDER BY created_at DESC
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
            ORDER BY created_at DESC
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
     * Count services by status - EXCLUDE DELETED
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
     * Hard delete
     */
    public function hardDelete($id) {
        try {
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
        $data['updated_at'] = date('Y-m-d H:i:s');
        return parent::update($id, $data);
    }
}
