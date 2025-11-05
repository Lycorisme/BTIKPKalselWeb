<?php

require_once dirname(__DIR__) . '/core/Model.php';

class Post extends Model {

    protected $table = 'posts';

    /**
     * Get paginated posts with filters
     */
    public function getPaginated($page = 1, $perPage = 10, $filters = []) {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = [];

        // Show deleted or not
        if (empty($filters['show_deleted'])) {
            $where[] = "p.deleted_at IS NULL";
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $where[] = "p.status = ?";
            $params[] = $filters['status'];
        }

        // Filter by category
        if (!empty($filters['category_id'])) {
            $where[] = "p.category_id = ?";
            $params[] = $filters['category_id'];
        }

        // Filter by featured
        if (isset($filters['is_featured']) && $filters['is_featured'] !== '') {
            $where[] = "p.is_featured = ?";
            $params[] = $filters['is_featured'];
        }

        // Search filter
        if (!empty($filters['search'])) {
            $where[] = "(p.title LIKE ? OR p.content LIKE ? OR p.excerpt LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (empty($where)) $where[] = "1=1";
        $whereClause = implode(' AND ', $where);

        // Count total
        $countSql = "SELECT COUNT(*) FROM {$this->table} p WHERE $whereClause";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();

        // Fetch data
        $sql = "
            SELECT p.*, 
                   c.name AS category_name,
                   c.slug AS category_slug,
                   u.name AS author_name
            FROM {$this->table} p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN users u ON p.author_id = u.id
            WHERE $whereClause
            ORDER BY p.published_at DESC, p.created_at DESC
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
     * Get by ID
     */
    public function getById($id) {
        $sql = "
            SELECT p.*, 
                   c.name AS category_name,
                   c.slug AS category_slug,
                   u.name AS author_name,
                   u.email AS author_email
            FROM {$this->table} p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN users u ON p.author_id = u.id
            WHERE p.id = ? AND p.deleted_at IS NULL
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Get by slug
     */
    public function getBySlug($slug) {
        $sql = "
            SELECT p.*, 
                   c.name AS category_name,
                   c.slug AS category_slug,
                   u.name AS author_name
            FROM {$this->table} p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN users u ON p.author_id = u.id
            WHERE p.slug = ? AND p.deleted_at IS NULL
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }

    /**
     * Recent posts
     */
    public function getRecent($limit = 5) {
        $sql = "
            SELECT p.*, c.name AS category_name, u.name AS author_name
            FROM {$this->table} p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN users u ON p.author_id = u.id
            WHERE p.status = 'published' 
              AND p.deleted_at IS NULL
              AND p.published_at <= NOW()
            ORDER BY p.published_at DESC
            LIMIT ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Featured posts
     */
    public function getFeatured($limit = 5) {
        $sql = "
            SELECT p.*, c.name AS category_name, u.name AS author_name
            FROM {$this->table} p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN users u ON p.author_id = u.id
            WHERE p.is_featured = 1 
              AND p.status = 'published'
              AND p.deleted_at IS NULL
              AND p.published_at <= NOW()
            ORDER BY p.published_at DESC
            LIMIT ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Tags management
     */
    public function getTags($postId) {
        $sql = "
            SELECT t.* 
            FROM tags t
            INNER JOIN post_tags pt ON t.id = pt.tag_id
            WHERE pt.post_id = ? AND t.deleted_at IS NULL
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$postId]);
        return $stmt->fetchAll();
    }

    public function syncTags($postId, $tagIds) {
        $sql = "DELETE FROM post_tags WHERE post_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$postId]);

        if (!empty($tagIds)) {
            $sql = "INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)";
            $stmt = $this->db->prepare($sql);
            foreach ($tagIds as $tagId) {
                $stmt->execute([$postId, $tagId]);
            }
        }
        return true;
    }

    /**
     * Slug helpers
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

    public function generateSlug($title, $excludeId = null) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $originalSlug = $slug;
        $counter = 1;
        while ($this->slugExists($slug, $excludeId)) {
            $slug = $originalSlug . '-' . $counter++;
        }
        return $slug;
    }

    /**
     * Increment view count
     */
    public function incrementView($id) {
        $sql = "UPDATE {$this->table} SET view_count = view_count + 1 WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Statistics
     */
    public function getStats() {
        $sql = "
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) AS published,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) AS draft,
                SUM(view_count) AS total_views
            FROM {$this->table}
            WHERE deleted_at IS NULL
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function countByStatus() {
        $sql = "
            SELECT status, COUNT(*) AS count
            FROM {$this->table}
            WHERE deleted_at IS NULL
            GROUP BY status
        ";
        $stmt = $this->db->query($sql);
        $results = $stmt->fetchAll();

        $counts = ['draft' => 0, 'published' => 0, 'archived' => 0];
        foreach ($results as $row) {
            $counts[$row['status']] = (int)$row['count'];
        }
        return $counts;
    }

    /**
     * Soft delete (rename slug)
     */
    public function softDelete($id) {
        try {
            $post = $this->getById($id);
            if (!$post) return false;

            $newSlug = $post['slug'] . '_deleted' . time();
            $sql = "UPDATE {$this->table} 
                    SET slug = ?, status = 'archived',
                        deleted_at = NOW(), updated_at = NOW()
                    WHERE id = ? AND deleted_at IS NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$newSlug, $id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('Error in softDelete: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update (force updated_at)
     */
    public function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return parent::update($id, $data);
    }
}
