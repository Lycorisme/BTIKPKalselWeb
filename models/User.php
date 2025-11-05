<?php
/**
 * User Model
 * Complete with role management, pagination, soft delete, and activity tracking
 * ALL BUGS FIXED
 */

class User extends Model {
    
    protected $table = 'users';

    /**
     * Get all users - EXCLUDE DELETED
     */
    public function getAllWithCreator() {
        $sql = "
            SELECT u.*
            FROM {$this->table} u
            WHERE u.deleted_at IS NULL
            ORDER BY u.created_at DESC
        ";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Get paginated users with filters
     * @param int $page
     * @param int $perPage
     * @param array $filters
     * @return array
     */
    public function getPaginated($page = 1, $perPage = 10, $filters = []) {
        $offset = ($page - 1) * $perPage;
        
        // ✅ FIX: Initialize $params FIRST
        $params = [];
        
        // Build WHERE clause
        $where = [];
        
        // CONDITIONAL: Only exclude deleted if not showing deleted
        if (empty($filters['show_deleted'])) {
            $where[] = "u.deleted_at IS NULL";
        }
        
        // Role filter
        if (!empty($filters['role'])) {
            $where[] = "u.role = ?";
            $params[] = $filters['role'];
        }
        
        // Active status filter
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $where[] = "u.is_active = ?";
            $params[] = $filters['is_active'];
        }
        
        // Search filter
        if (!empty($filters['search'])) {
            $where[] = "(u.name LIKE ? OR u.email LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Default WHERE if empty
        if (empty($where)) {
            $where[] = "1=1";
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM {$this->table} u WHERE $whereClause";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Get data
        $sql = "
            SELECT u.*
            FROM {$this->table} u
            WHERE $whereClause
            ORDER BY u.created_at DESC
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
     * Get user by email
     * @param string $email
     * @return array|false
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = ? AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    /**
     * Check if email exists (EXCLUDE DELETED USERS)
     * @param string $email
     * @param int|null $excludeId
     * @return bool
     */
    public function emailExists($email, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE email = ? AND deleted_at IS NULL";
        $params = [$email];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Get users by role
     * @param string $role
     * @return array
     */
    public function getByRole($role) {
        $sql = "
            SELECT * FROM {$this->table} 
            WHERE role = ? AND is_active = 1 AND deleted_at IS NULL
            ORDER BY name ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$role]);
        return $stmt->fetchAll();
    }

    /**
     * Get active users count by role - EXCLUDE DELETED
     * @return array
     */
    public function countByRole() {
        $sql = "
            SELECT 
                role,
                COUNT(*) as count
            FROM {$this->table}
            WHERE deleted_at IS NULL
            GROUP BY role
        ";
        
        $stmt = $this->db->query($sql);
        $results = $stmt->fetchAll();
        
        $counts = [
            'super_admin' => 0,
            'admin' => 0,
            'editor' => 0,
            'author' => 0
        ];
        
        foreach ($results as $row) {
            $counts[$row['role']] = (int)$row['count'];
        }
        
        return $counts;
    }

    /**
     * Update last login timestamp
     * @param int $userId
     * @return bool
     */
    public function updateLastLogin($userId) {
        $sql = "UPDATE {$this->table} SET last_login_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$userId]);
    }

    /**
     * Change password
     * @param int $userId
     * @param string $newPassword
     * @return bool
     */
    public function changePassword($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE {$this->table} SET password = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$hashedPassword, $userId]);
    }

    /**
     * Toggle user active/inactive status
     * @param int $userId
     * @return bool
     */
    public function toggleStatus($userId) {
        $sql = "UPDATE {$this->table} SET is_active = NOT is_active, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$userId]);
    }

    /**
     * Soft delete user WITH EMAIL MODIFICATION and DEACTIVATION
     * Changes email to prevent duplicate issues
     * Sets is_active to 0 to deactivate user
     * Format: originalname_deletedXXXXXXXXXX@domain.com
     * 
     * @param int $id User ID to delete
     * @return bool Success status
     */
    public function softDelete($id) {
        try {
            // Get user data first
            $user = $this->find($id);
            
            if (!$user) {
                error_log("User ID {$id} not found for deletion");
                return false;
            }
            
            // Generate new email with timestamp
            $email = $user['email'];
            $timestamp = time();
            
            // Split email into name and domain
            if (strpos($email, '@') !== false) {
                list($emailName, $emailDomain) = explode('@', $email, 2);
                $newEmail = $emailName . '_deleted' . $timestamp . '@' . $emailDomain;
            } else {
                // Fallback if no @ found (shouldn't happen with email validation)
                $newEmail = $email . '_deleted' . $timestamp;
            }
            
            // ✅ UPDATE: Set is_active = 0, email, and deleted_at
            $sql = "UPDATE {$this->table} 
                    SET email = ?, 
                        is_active = 0,
                        deleted_at = NOW(),
                        updated_at = NOW()
                    WHERE id = ? AND deleted_at IS NULL";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$newEmail, $id]);
            
            $rowCount = $stmt->rowCount();
            
            // Debug log
            error_log("Soft delete user ID {$id}: Changed email from '{$email}' to '{$newEmail}', set is_active=0, {$rowCount} rows affected");
            
            return $rowCount > 0;
            
        } catch (PDOException $e) {
            error_log("Error in softDelete: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Hard delete user (permanent delete)
     * @param int $id
     * @return bool
     */
    public function hardDelete($id) {
        try {
            $sql = "DELETE FROM {$this->table} WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$id]);
            
            $rowCount = $stmt->rowCount();
            error_log("Hard delete user ID {$id}: {$rowCount} rows affected");
            
            return $rowCount > 0;
        } catch (PDOException $e) {
            error_log("Error in hardDelete: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Insert user (auto-hash password + created_at)
     * @param array $data
     * @return int|false Insert ID or false
     */
    public function insert($data) {
        // Hash password if present
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        $data['created_at'] = date('Y-m-d H:i:s');
        
        return parent::insert($data);
    }

    /**
     * Update user (auto-hash password if changed)
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        // Hash password if present and not empty
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            unset($data['password']);
        }
        
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return parent::update($id, $data);
    }
}
