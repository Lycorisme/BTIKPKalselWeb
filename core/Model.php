<?php
/**
 * Base Model Class
 * Parent class untuk semua model with RESERVED WORD fix
 */

class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get all records
     */
    public function all($conditions = [], $orderBy = null, $limit = null) {
        $sql = "SELECT * FROM {$this->table}";
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . $this->buildWhereClause($conditions);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }
        
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($conditions));
        return $stmt->fetchAll();
    }
    
    /**
     * Find record by ID - EXCLUDE DELETED
     */
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Find by conditions
     */
    public function findBy($conditions) {
        $sql = "SELECT * FROM {$this->table} WHERE " . $this->buildWhereClause($conditions);
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($conditions));
        return $stmt->fetch();
    }
    
    /**
     * Insert record with BACKTICK protection for reserved words
     */
    public function insert($data) {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        // ✅ Wrap field names in backticks to handle reserved words
        $escapedFields = array_map(function($field) {
            return "`{$field}`";
        }, $fields);
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $escapedFields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Update record with BACKTICK protection for reserved words
     */
    public function update($id, $data) {
        $fields = [];
        foreach (array_keys($data) as $field) {
            // ✅ Wrap field names in backticks
            $fields[] = "`{$field}` = ?";
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . 
               " WHERE {$this->primaryKey} = ?";
        
        $values = array_values($data);
        $values[] = $id;
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }
    
    /**
     * Soft delete (generic)
     */
    public function softDelete($id) {
        $sql = "UPDATE {$this->table} SET deleted_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Hard delete (generic)
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Count records
     */
    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . $this->buildWhereClause($conditions);
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($conditions));
        return $stmt->fetchColumn();
    }
    
    /**
     * Build WHERE clause
     */
    protected function buildWhereClause($conditions) {
        $where = [];
        foreach (array_keys($conditions) as $field) {
            $where[] = "$field = ?";
        }
        return implode(' AND ', $where);
    }
    
    /**
     * Execute raw SQL
     */
    public function query($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->db->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->db->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->db->rollBack();
    }
}
