<?php
require_once __DIR__ . '/../core/Model.php';

/**
 * Gallery Model
 */
class Gallery extends Model {
    protected $table = 'gallery_albums';
    
    /**
     * Get all albums with photo count - EXCLUDE DELETED
     */
    public function getAllAlbums($limit = null) {
        $sql = "SELECT a.*, 
                (SELECT COUNT(*) FROM gallery_photos WHERE album_id = a.id AND deleted_at IS NULL) as photo_count,
                (SELECT filename FROM gallery_photos WHERE album_id = a.id AND deleted_at IS NULL ORDER BY display_order ASC LIMIT 1) as cover_image
                FROM {$this->table} a
                WHERE a.deleted_at IS NULL
                ORDER BY a.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get album by slug - EXCLUDE DELETED
     */
    public function getAlbumBySlug($slug) {
        $stmt = $this->db->prepare("
            SELECT a.*, 
                   (SELECT COUNT(*) FROM gallery_photos WHERE album_id = a.id AND deleted_at IS NULL) as photo_count
            FROM {$this->table} a
            WHERE a.slug = ? AND a.deleted_at IS NULL
        ");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get album by ID - EXCLUDE DELETED
     */
    public function getAlbumById($id) {
        $stmt = $this->db->prepare("
            SELECT a.*, 
                   (SELECT COUNT(*) FROM gallery_photos WHERE album_id = a.id AND deleted_at IS NULL) as photo_count
            FROM {$this->table} a
            WHERE a.id = ? AND a.deleted_at IS NULL
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get photos by album ID - EXCLUDE DELETED
     */
    public function getPhotosByAlbumId($album_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM gallery_photos 
            WHERE album_id = ? AND deleted_at IS NULL 
            ORDER BY display_order ASC, created_at DESC
        ");
        $stmt->execute([$album_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get photo by ID - EXCLUDE DELETED
     */
    public function getPhotoById($id) {
        $stmt = $this->db->prepare("
            SELECT * FROM gallery_photos 
            WHERE id = ? AND deleted_at IS NULL
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Soft delete album
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
}
