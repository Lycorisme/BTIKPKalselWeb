<?php
/**
 * Upload Class
 * Handle file uploads with validation
 * Integrated with Settings for max size & allowed types
 */

class Upload {
    
    private $uploadDir;
    private $maxSize;
    private $allowedTypes;
    private $error;
    
    /**
     * Constructor - Load settings dynamically
     */
    public function __construct() {
        // Base upload directory
        $this->uploadDir = dirname(__DIR__) . '/public/uploads/';
        
        // Get max size from settings (convert MB to bytes)
        $maxSizeMB = (int)getSetting('upload_max_size', 5);
        $this->maxSize = $maxSizeMB * 1024 * 1024; // Convert MB to bytes
        
        // Get allowed types from settings
        $allowedImages = getSetting('upload_allowed_images', 'jpg,jpeg,png,gif,webp');
        $allowedDocs = getSetting('upload_allowed_docs', 'pdf,doc,docx,xls,xlsx,ppt,pptx');
        
        // Combine and clean
        $imageTypes = array_map('trim', explode(',', $allowedImages));
        $docTypes = array_map('trim', explode(',', $allowedDocs));
        
        $this->allowedTypes = array_merge($imageTypes, $docTypes);
        
        // Create upload directory if not exists
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    /**
     * Upload file
     * 
     * @param array $file $_FILES array
     * @param string $subDir Subdirectory (posts, settings, users, etc.)
     * @param string $customName Custom filename (optional)
     * @return string|false Uploaded filename or false on error
     */
    public function upload($file, $subDir = 'general', $customName = null) {
        // Reset error
        $this->error = '';
        
        // Check if file exists
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            $this->error = 'No file uploaded';
            return false;
        }
        
        // Check upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->error = $this->getUploadError($file['error']);
            return false;
        }
        
        // Get file info
        $originalName = $file['name'];
        $tmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        
        // Validate file size
        if ($fileSize > $this->maxSize) {
            $maxSizeMB = $this->maxSize / (1024 * 1024);
            $this->error = "File terlalu besar. Maksimal {$maxSizeMB}MB";
            return false;
        }
        
        // Validate file type
        if (!in_array($extension, $this->allowedTypes)) {
            $this->error = "Tipe file tidak diizinkan. Hanya: " . implode(', ', $this->allowedTypes);
            return false;
        }
        
        // Validate actual file type (security check)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tmpName);
        finfo_close($finfo);
        
        if (!$this->isValidMimeType($mimeType, $extension)) {
            $this->error = 'Invalid file type (security check failed)';
            return false;
        }
        
        // Create subdirectory if not exists
        $subDirPath = $this->uploadDir . $subDir . '/';
        if (!is_dir($subDirPath)) {
            mkdir($subDirPath, 0755, true);
        }
        
        // Generate filename
        if ($customName) {
            $filename = $this->sanitizeFilename($customName) . '.' . $extension;
        } else {
            $filename = $this->generateFilename($originalName);
        }
        
        // Check if file already exists, append number if needed
        $counter = 1;
        $baseFilename = pathinfo($filename, PATHINFO_FILENAME);
        while (file_exists($subDirPath . $filename)) {
            $filename = $baseFilename . '_' . $counter . '.' . $extension;
            $counter++;
        }
        
        // Move uploaded file
        $destination = $subDirPath . $filename;
        if (move_uploaded_file($tmpName, $destination)) {
            // Set proper permissions
            chmod($destination, 0644);
            
            // Return relative path from uploads directory
            return $subDir . '/' . $filename;
        } else {
            $this->error = 'Failed to move uploaded file';
            return false;
        }
    }
    
    /**
     * Delete file
     * 
     * @param string $filepath Relative path from uploads directory
     * @return bool
     */
    public function delete($filepath) {
        if (empty($filepath)) {
            return false;
        }
        
        $fullPath = $this->uploadDir . $filepath;
        
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        
        return false;
    }
    
    /**
     * Get upload error message
     * 
     * @param int $errorCode
     * @return string
     */
    private function getUploadError($errorCode) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File melebihi upload_max_filesize di php.ini',
            UPLOAD_ERR_FORM_SIZE => 'File melebihi MAX_FILE_SIZE di HTML form',
            UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian',
            UPLOAD_ERR_NO_FILE => 'Tidak ada file yang diupload',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk',
            UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh ekstensi PHP'
        ];
        
        return $errors[$errorCode] ?? 'Unknown upload error';
    }
    
    /**
     * Validate MIME type matches extension
     * 
     * @param string $mimeType
     * @param string $extension
     * @return bool
     */
    private function isValidMimeType($mimeType, $extension) {
        $validMimes = [
            // Images
            'jpg' => ['image/jpeg', 'image/jpg'],
            'jpeg' => ['image/jpeg', 'image/jpg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
            
            // Documents
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'ppt' => ['application/vnd.ms-powerpoint'],
            'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
            
            // Archives
            'zip' => ['application/zip', 'application/x-zip-compressed'],
            'rar' => ['application/x-rar-compressed'],
        ];
        
        if (!isset($validMimes[$extension])) {
            return false;
        }
        
        return in_array($mimeType, $validMimes[$extension]);
    }
    
    /**
     * Generate unique filename
     * 
     * @param string $originalName
     * @return string
     */
    private function generateFilename($originalName) {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $basename = pathinfo($originalName, PATHINFO_FILENAME);
        
        // Sanitize basename
        $basename = $this->sanitizeFilename($basename);
        
        // Add timestamp for uniqueness
        $timestamp = time();
        $random = substr(md5(uniqid(rand(), true)), 0, 8);
        
        return $basename . '_' . $timestamp . '_' . $random . '.' . $extension;
    }
    
    /**
     * Sanitize filename
     * 
     * @param string $filename
     * @return string
     */
    private function sanitizeFilename($filename) {
        // Remove file extension if present
        $filename = pathinfo($filename, PATHINFO_FILENAME);
        
        // Convert to lowercase
        $filename = strtolower($filename);
        
        // Replace spaces with hyphens
        $filename = str_replace(' ', '-', $filename);
        
        // Remove special characters
        $filename = preg_replace('/[^a-z0-9\-_]/', '', $filename);
        
        // Remove multiple hyphens
        $filename = preg_replace('/-+/', '-', $filename);
        
        // Trim hyphens from start and end
        $filename = trim($filename, '-');
        
        // Limit length
        $filename = substr($filename, 0, 100);
        
        return $filename ?: 'file';
    }
    
    /**
     * Get error message
     * 
     * @return string
     */
    public function getError() {
        return $this->error;
    }
    
    /**
     * Get max upload size in MB
     * 
     * @return int
     */
    public function getMaxSize() {
        return $this->maxSize / (1024 * 1024);
    }
    
    /**
     * Get allowed file types
     * 
     * @return array
     */
    public function getAllowedTypes() {
        return $this->allowedTypes;
    }
    
    /**
     * Check if file type is allowed
     * 
     * @param string $filename
     * @return bool
     */
    public function isAllowedType($filename) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $this->allowedTypes);
    }
    
    /**
     * Format file size
     * 
     * @param int $bytes
     * @return string
     */
    public static function formatSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Get upload info (for display)
     * 
     * @return array
     */
    public function getUploadInfo() {
        return [
            'max_size' => $this->getMaxSize() . ' MB',
            'max_size_bytes' => $this->maxSize,
            'allowed_types' => implode(', ', $this->allowedTypes),
            'allowed_types_array' => $this->allowedTypes,
            'upload_dir' => $this->uploadDir
        ];
    }
    
    /**
     * Upload multiple files
     * 
     * @param array $files Array of $_FILES
     * @param string $subDir
     * @return array Array of uploaded filenames
     */
    public function uploadMultiple($files, $subDir = 'general') {
        $uploaded = [];
        $errors = [];
        
        // Normalize files array
        $fileCount = count($files['name']);
        
        for ($i = 0; $i < $fileCount; $i++) {
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];
            
            $result = $this->upload($file, $subDir);
            
            if ($result) {
                $uploaded[] = $result;
            } else {
                $errors[] = [
                    'filename' => $files['name'][$i],
                    'error' => $this->getError()
                ];
            }
        }
        
        return [
            'uploaded' => $uploaded,
            'errors' => $errors
        ];
    }
    
    /**
     * Create image thumbnail
     * 
     * @param string $filepath Relative path from uploads
     * @param int $maxWidth
     * @param int $maxHeight
     * @return string|false Thumbnail path or false
     */
    public function createThumbnail($filepath, $maxWidth = 300, $maxHeight = 300) {
        $fullPath = $this->uploadDir . $filepath;
        
        if (!file_exists($fullPath)) {
            return false;
        }
        
        $imageInfo = getimagesize($fullPath);
        if (!$imageInfo) {
            return false;
        }
        
        list($width, $height, $type) = $imageInfo;
        
        // Calculate new dimensions
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = (int)($width * $ratio);
        $newHeight = (int)($height * $ratio);
        
        // Create new image
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Load source image
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($fullPath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($fullPath);
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($fullPath);
                break;
            default:
                return false;
        }
        
        // Resize
        imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Generate thumbnail filename
        $pathInfo = pathinfo($filepath);
        $thumbPath = $pathInfo['dirname'] . '/thumb_' . $pathInfo['basename'];
        $thumbFullPath = $this->uploadDir . $thumbPath;
        
        // Save thumbnail
        $success = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $success = imagejpeg($newImage, $thumbFullPath, 85);
                break;
            case IMAGETYPE_PNG:
                $success = imagepng($newImage, $thumbFullPath, 8);
                break;
            case IMAGETYPE_GIF:
                $success = imagegif($newImage, $thumbFullPath);
                break;
        }
        
        // Free memory
        imagedestroy($source);
        imagedestroy($newImage);
        
        return $success ? $thumbPath : false;
    }
}
