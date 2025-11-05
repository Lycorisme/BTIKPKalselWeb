<?php
/**
 * Tag Model
 * Manage post tags with CRUD operations
 */

class Tag extends Model {
    
    protected $table = 'tags';
    
    /**
     * Override find
     */
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Get all tags
     */
    public function getAll() {
        $sql = "SELECT * FROM {$this->table} ORDER BY name ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Get tags with post count
     */
    public function getWithPostCount() {
        $sql = "
            SELECT 
                t.*,
                COUNT(pt.post_id) as post_count
            FROM {$this->table} t
            LEFT JOIN post_tags pt ON t.id = pt.tag_id
            GROUP BY t.id
            ORDER BY t.name ASC
        ";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Get paginated tags with filters
     */
    public function getPaginated($page = 1, $perPage = 10, $filters = []) {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = [];
        
        // Search filter
        if (!empty($filters['search'])) {
            $where[] = "t.name LIKE ?";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
        }
        
        if (empty($where)) $where[] = "1=1";
        $whereClause = implode(' AND ', $where);
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM {$this->table} t WHERE $whereClause";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Get data with post count
        $sql = "
            SELECT 
                t.*,
                COUNT(pt.post_id) as post_count
            FROM {$this->table} t
            LEFT JOIN post_tags pt ON t.id = pt.tag_id
            WHERE $whereClause
            GROUP BY t.id
            ORDER BY t.name ASC
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
     * Find by slug
     */
    public function findBySlug($slug) {
        $sql = "SELECT * FROM {$this->table} WHERE slug = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }

    /**
     * ==========================================
     * TAMBAHAN: Find by name (FIX UNTUK ERROR)
     * ==========================================
     */
    public function findByName($name) {
        $sql = "SELECT * FROM {$this->table} WHERE name = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$name]);
        return $stmt->fetch();
    }
    
    /**
     * Check if tag has posts
     */
    public function hasPosts($id) {
        $sql = "SELECT COUNT(*) FROM post_tags WHERE tag_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Get posts by tag
     */
    public function getPosts($tagId, $limit = null) {
        $sql = "
            SELECT p.* FROM posts p
            INNER JOIN post_tags pt ON p.id = pt.post_id
            WHERE pt.tag_id = ? 
              AND p.status = 'published' 
              AND p.deleted_at IS NULL
            ORDER BY p.published_at DESC
        ";
        
        if ($limit) {
            $sql .= " LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tagId, $limit]);
        } else {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tagId]);
        }
        
        return $stmt->fetchAll();
    }
    
    /**
     * Soft delete (modify slug to allow reuse)
     */
    public function softDelete($id) {
        try {
            $tag = $this->find($id);
            
            if (!$tag) {
                return false;
            }
            
            // Check if has posts
            if ($this->hasPosts($id)) {
                throw new Exception("Cannot delete tag with existing posts");
            }
            
            // Modify slug
            $timestamp = time();
            $newSlug = $tag['slug'] . '_deleted' . $timestamp;
            
            $sql = "UPDATE {$this->table} 
                    SET slug = ?
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
                throw new Exception("Cannot delete tag with existing posts");
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
     * Insert tag
     */
    public function insert($data) {
        // Cek dulu apakah NAMA sudah ada
        if (isset($data['name'])) {
            $existing = $this->findByName($data['name']);
            if ($existing) {
                return $existing['id']; // Kembalikan ID yang ada, JANGAN insert baru
            }
        }

        // Jika belum ada, baru insert
        $data['created_at'] = date('Y-m-d H:i:s');
        return parent::insert($data);
    }
    
    /**
     * Update tag
     */
    public function update($id, $data) {
        // Tags table might not have updated_at column
        // Only add if you have it in your schema
        return parent::update($id, $data);
    }
}