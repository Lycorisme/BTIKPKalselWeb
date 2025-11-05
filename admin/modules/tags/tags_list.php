<?php
/**
 * Tags List
 * With DataTables for better filtering & sorting
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../core/Model.php';
require_once '../../../models/Tag.php';

$pageTitle = 'Kelola Tags';

$tagModel = new Tag();
$tags = $tagModel->getWithPostCount();

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted">Kelola tags untuk berita & artikel</p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Tags</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Daftar Tags</h5>
                    <a href="tags_add.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Tambah Tag
                    </a>
                </div>
            </div>
            
            <div class="card-body">
                <!-- Alert Messages -->
                <?php if ($alert = getAlert()): ?>
                    <div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show">
                        <?= $alert['message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Info -->
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    Total <strong><?= count($tags) ?></strong> tags
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover" id="tagsTable">
                        <thead>
                            <tr>
                                <th width="50">ID</th>
                                <th>Nama Tag</th>
                                <th>Slug</th>
                                <th width="120" class="text-center">Jumlah Post</th>
                                <th width="150" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tags)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        Belum ada tag. <a href="tags_add.php">Tambah tag pertama</a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($tags as $tag): ?>
                                    <tr>
                                        <td><?= $tag['id'] ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                #<?= htmlspecialchars($tag['name']) ?>
                                            </span>
                                        </td>
                                        <td><code><?= $tag['slug'] ?></code></td>
                                        <td class="text-center">
                                            <span class="badge bg-primary"><?= formatNumber($tag['post_count']) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= BASE_URL ?>news/tag.php?slug=<?= $tag['slug'] ?>" 
                                                   class="btn btn-info" title="Lihat" target="_blank">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="tags_edit.php?id=<?= $tag['id'] ?>" 
                                                   class="btn btn-warning" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="tags_delete.php?id=<?= $tag['id'] ?>" 
                                                   class="btn btn-danger" 
                                                   onclick="return confirm('Yakin hapus tag ini?')"
                                                   title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    <small class="text-muted">
                        Tags digunakan untuk menandai topik spesifik dalam berita. Satu berita bisa memiliki banyak tags.
                    </small>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- DataTables CSS & JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    // Get items per page from settings
    var itemsPerPage = <?= (int)getSetting('items_per_page', 10) ?>;
    
    $('#tagsTable').DataTable({
        "pageLength": itemsPerPage,
        "order": [[3, "desc"]], // Sort by post count
        "language": {
            "search": "Cari:",
            "lengthMenu": "Tampilkan _MENU_ data per halaman",
            "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ tags",
            "infoEmpty": "Tidak ada data",
            "infoFiltered": "(difilter dari _MAX_ total tags)",
            "zeroRecords": "Tidak ada tags yang cocok",
            "paginate": {
                "first": "Pertama",
                "last": "Terakhir",
                "next": "Selanjutnya",
                "previous": "Sebelumnya"
            }
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
