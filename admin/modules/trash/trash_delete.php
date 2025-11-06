<?php
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

$db = Database::getInstance()->getConnection();

$type = $_GET['type'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    setAlert('danger', 'ID tidak valid.');
    header("Location: trash_list.php" . ($type ? "?type=$type" : ""));
    exit;
}

try {
    switch ($type) {
        case 'post':
            $stmt = $db->prepare("DELETE FROM posts WHERE id=?");
            $stmt->execute([$id]);
            setAlert('success', 'Post berhasil dihapus permanen!');
            break;
        case 'service':
            $stmt = $db->prepare("DELETE FROM services WHERE id=?");
            $stmt->execute([$id]);
            setAlert('success', 'Layanan berhasil dihapus permanen!');
            break;
        case 'user':
            $stmt = $db->prepare("DELETE FROM users WHERE id=?");
            $stmt->execute([$id]);
            setAlert('success', 'User berhasil dihapus permanen!');
            break;
        case 'file':
            $stmt = $db->prepare("DELETE FROM downloadable_files WHERE id=?");
            $stmt->execute([$id]);
            setAlert('success', 'File berhasil dihapus permanen!');
            break;
        case 'album':
            $stmt = $db->prepare("DELETE FROM gallery_albums WHERE id=?");
            $stmt->execute([$id]);
            setAlert('success', 'Album berhasil dihapus permanen!');
            break;
        case 'photo':
            $stmt = $db->prepare("DELETE FROM gallery_photos WHERE id=?");
            $stmt->execute([$id]);
            setAlert('success', 'Foto berhasil dihapus permanen!');
            break;
        case 'banner':
            // Hapus file gambar sebelum delete record
            $file = $db->query("SELECT image_path FROM banners WHERE id = $id")->fetchColumn();
            if ($file && file_exists('../../../public/' . $file)) {
                unlink('../../../public/' . $file);
            }
            $stmt = $db->prepare("DELETE FROM banners WHERE id=?");
            $stmt->execute([$id]);
            setAlert('success', 'Banner berhasil dihapus permanen!');
            break;
        case 'contact':
            $stmt = $db->prepare("DELETE FROM contact_messages WHERE id=?");
            $stmt->execute([$id]);
            setAlert('success', 'Pesan kontak berhasil dihapus permanen!');
            break;
        default:
            setAlert('danger', 'Tipe data tidak dikenal.');
            break;
    }
} catch (PDOException $e) {
    setAlert('danger', 'Terjadi kesalahan saat menghapus item: ' . $e->getMessage());
}

header("Location: trash_list.php" . ($type ? "?type=$type" : ""));
exit;
