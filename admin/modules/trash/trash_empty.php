<?php
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

$db = Database::getInstance()->getConnection();

$type = $_GET['type'] ?? '';

try {
    switch ($type) {
        case 'posts':
            $db->exec("DELETE FROM posts WHERE deleted_at IS NOT NULL");
            setAlert('success', 'Semua posts di trash sudah dihapus permanen!');
            break;
        case 'services':
            $db->exec("DELETE FROM services WHERE deleted_at IS NOT NULL");
            setAlert('success', 'Semua layanan di trash sudah dihapus permanen!');
            break;
        case 'users':
            $db->exec("DELETE FROM users WHERE deleted_at IS NOT NULL");
            setAlert('success', 'Semua user di trash sudah dihapus permanen!');
            break;
        case 'files':
            $db->exec("DELETE FROM downloadable_files WHERE deleted_at IS NOT NULL");
            setAlert('success', 'Semua file di trash sudah dihapus permanen!');
            break;
        case 'albums':
            $db->exec("DELETE FROM gallery_albums WHERE deleted_at IS NOT NULL");
            setAlert('success', 'Semua album gallery di trash sudah dihapus permanen!');
            break;
        case 'photos':
            $db->exec("DELETE FROM gallery_photos WHERE deleted_at IS NOT NULL");
            setAlert('success', 'Semua foto gallery di trash sudah dihapus permanen!');
            break;
        case 'banners':
            // Hapus file gambar dulu supaya tidak tersisa orphan
            $stmt = $db->query("SELECT image_path FROM banners WHERE deleted_at IS NOT NULL");
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $file) {
                if ($file && file_exists('../../../public/' . $file)) {
                    unlink('../../../public/' . $file);
                }
            }
            $db->exec("DELETE FROM banners WHERE deleted_at IS NOT NULL");
            setAlert('success', 'Semua banner di trash sudah dihapus permanen!');
            break;
        case 'contacts':
            $db->exec("DELETE FROM contact_messages WHERE deleted_at IS NOT NULL");
            setAlert('success', 'Semua pesan kontak di trash sudah dihapus permanen!');
            break;
        case '':
            // Kosongkan semua trash (semua tabel)
            // Hapus file banner dulu
            $stmt = $db->query("SELECT image_path FROM banners WHERE deleted_at IS NOT NULL");
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $file) {
                if ($file && file_exists('../../../public/' . $file)) {
                    unlink('../../../public/' . $file);
                }
            }

            $tables = [
                'posts',
                'services',
                'users',
                'downloadable_files',
                'gallery_albums',
                'gallery_photos',
                'banners',
                'contact_messages'
            ];
            foreach ($tables as $table) {
                $db->exec("DELETE FROM $table WHERE deleted_at IS NOT NULL");
            }
            setAlert('success', 'Semua trash di semua modul sudah dihapus permanen!');
            break;
        default:
            setAlert('danger', 'Tipe trash tidak dikenal.');
            break;
    }
} catch (PDOException $e) {
    setAlert('danger', 'Terjadi kesalahan saat mengosongkan trash: ' . $e->getMessage());
}

header("Location: trash_list.php" . ($type ? "?type=$type" : ""));
exit;
