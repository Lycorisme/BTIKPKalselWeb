<?php
/**
 * Pages - Edit Page
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

$pageTitle = 'Edit Halaman';
$currentPage = 'pages';

$db = Database::getInstance()->getConnection();

$id = $_GET['id'] ?? null;
if (!$id) {
    setAlert('danger', 'Halaman tidak ditemukan.');
    redirect(ADMIN_URL . 'modules/pages/pages_list.php');
}

// Get page
$stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
$stmt->execute([$id]);
$page = $stmt->fetch();

if (!$page) {
    setAlert('danger', 'Halaman tidak ditemukan.');
    redirect(ADMIN_URL . 'modules/pages/pages_list.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = clean($_POST['title'] ?? '');
    $slug = clean($_POST['slug'] ?? '');
    $content = $_POST['content'] ?? '';
    $status = $_POST['status'] ?? 'draft';
    $displayOrder = (int)($_POST['display_order'] ?? 0);

    if (!$title) {
        $errors[] = 'Judul halaman wajib diisi.';
    }
    if (!$slug) {
        $slug = slugify($title);
    } else {
        $slug = slugify($slug);
    }

    // Check slug uniqueness excluding current page
    $stmtCheck = $db->prepare("SELECT COUNT(*) FROM pages WHERE slug = ? AND id != ?");
    $stmtCheck->execute([$slug, $id]);
    $count = $stmtCheck->fetchColumn();
    if ($count > 0) {
        $errors[] = 'Slug sudah digunakan, silakan gunakan slug lain.';
    }

    if (empty($errors)) {
        try {
            $stmtUpdate = $db->prepare("UPDATE pages SET title = ?, slug = ?, content = ?, status = ?, display_order = ?, updated_at = NOW() WHERE id = ?");
            $stmtUpdate->execute([$title, $slug, $content, $status, $displayOrder, $id]);

            logActivity('UPDATE', "Mengubah halaman: {$title}", 'pages', $id);

            setAlert('success', 'Halaman berhasil diupdate!');
            redirect(ADMIN_URL . 'modules/pages/pages_list.php');
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errors[] = 'Gagal mengupdate halaman. Silakan coba lagi.';
        }
    }
}

include '../../includes/header.php';
?>
<div class="page-heading">
    <div class="page-title">
        <h3><?= $pageTitle ?></h3>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <?= csrfField() ?>
                    <div class="mb-3">
                        <label for="title" class="form-label">Judul Halaman <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($page['title']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug URL</label>
                        <input type="text" class="form-control" id="slug" name="slug" value="<?= htmlspecialchars($page['slug']) ?>">
                        <small class="text-muted">URL unik untuk halaman ini. Diisi otomatis dari judul jika kosong.</small>
                    </div>
                    <div class="mb-3">
                        <label for="content" class="form-label">Isi Konten</label>
                        <textarea class="form-control" id="content" name="content" rows="10"><?= htmlspecialchars($page['content']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status Publikasi</label>
                        <select id="status" name="status" class="form-select">
                            <option value="published" <?= ($page['status'] === 'published') ? 'selected' : '' ?>>Published</option>
                            <option value="draft" <?= ($page['status'] === 'draft') ? 'selected' : '' ?>>Draft</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="display_order" class="form-label">Urutan Tampilan</label>
                        <input type="number" class="form-control" id="display_order" name="display_order" value="<?= (int)$page['display_order'] ?>">
                        <small class="text-muted">Urutan halaman yang akan ditampilkan (semakin kecil semakin atas)</small>
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="pages_list.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>
<?php include '../../includes/footer.php'; ?>
<script src="https://cdn.ckeditor.com/4.19.0/standard/ckeditor.js"></script>
<script>
    CKEDITOR.replace('content');
</script>
