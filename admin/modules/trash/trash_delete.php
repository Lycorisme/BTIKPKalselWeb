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

// Fungsi untuk hapus file aman jika ada
function deleteFileIfExists($filePath) {
    $fullPath = '../../../public/' . $filePath;
    if ($filePath && is_file($fullPath)) {
        unlink($fullPath);
    }
}

try {
    switch ($type) {
        case 'post':
            // Jika ada file terkait pada posts, juga hapus (sesuaikan nama kolom file jika ada)
            // contoh: 'image_path' untuk file gambar dari post
            $file = $db->query("SELECT image_path FROM posts WHERE id = $id")->fetchColumn();
            deleteFileIfExists($file);

            $stmt = $db->prepare("DELETE FROM posts WHERE id=?");
            $stmt->execute([$id]);
            setAlert('success', 'Post berhasil dihapus permanen!');
            break;

        case 'service':
            // Hapus file gambar layanan jika ada
            $file = $db->query("SELECT image_path FROM services WHERE id = $id")->fetchColumn();
            deleteFileIfExists($file);

            $stmt = $db->prepare("DELETE FROM services WHERE id=?");
            $stmt->execute([$id]);
            setAlert('success', 'Layanan berhasil dihapus permanen!');
            break;

        case 'user':
            // Jika user ada file avatar/profile pic
            $file = $db->query("SELECT avatar_path FROM users WHERE id = $id")->fetchColumn();
            deleteFileIfExists($file);

            $stmt = $db->prepare("DELETE FROM users WHERE id=?");
            $stmt->execute([$id]);
            setAlert('success', 'User berhasil dihapus permanen!');
            break;

        case 'file':
            // Hapus file downloadable
            $file = $db->query("SELECT file_path FROM downloadable_files WHERE id = $id")->fetchColumn();
            deleteFileIfExists($file);

            $stmt = $db->prepare("DELETE FROM downloadable_files WHERE id=?");
            $stmt->execute([$id]);
            setAlert('success', 'File berhasil dihapus permanen!');
            break;

        case 'album':
            // Jika ada foto dalam album, hapus dulu file fotonya
            $photos = $db->query("SELECT image_path FROM gallery_photos WHERE album_id = $id")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($photos as $photoFile) {
                deleteFileIfExists($photoFile);
            }
            // Hapus album photos
            $db->prepare("DELETE FROM gallery_photos WHERE album_id = ?")->execute([$id]);

            $stmt = $db->prepare("DELETE FROM gallery_albums WHERE id=?");
            $stmt->execute([$id]);
            setAlert('success', 'Album berhasil dihapus permanen (beserta semua foto)!');
            break;

        case 'photo':
            $file = $db->query("SELECT image_path FROM gallery_photos WHERE id = $id")->fetchColumn();
            deleteFileIfExists($file);

            $stmt = $db->prepare("DELETE FROM gallery_photos WHERE id=?");
            $stmt->execute([$id]);
            setAlert('success', 'Foto berhasil dihapus permanen!');
            break;

        case 'banner':
            $file = $db->query("SELECT image_path FROM banners WHERE id = $id")->fetchColumn();
            deleteFileIfExists($file);

            $stmt = $db->prepare("DELETE FROM banners WHERE id=?");
            $stmt->execute([$id]);
            setAlert('success', 'Banner berhasil dihapus permanen!');
            break;

        case 'contact':
            $stmt = $db->prepare("DELETE FROM contact_messages WHERE id=?");
            $stmt->execute([$id]);
            setAlert('success', 'Pesan kontak berhasil dihapus permanen!');
            break;

        case 'page':
            // Jika halaman memiliki file pendukung (misal banner/image), hapus juga
            $file = $db->query("SELECT image_path FROM pages WHERE id = $id")->fetchColumn();
            deleteFileIfExists($file);
            
            $stmt = $db->prepare("DELETE FROM pages WHERE id=?");
            $stmt->execute([$id]);
            setAlert('success', 'Halaman berhasil dihapus permanen!');
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
