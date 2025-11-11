<?php
/**
 * Files List - Full Mazer Design with Soft Delete, Pagination, Search, File Preview
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../models/File.php';

$pageTitle = 'Kelola File Download';
$currentPage = 'files';

$db = Database::getInstance()->getConnection();
$fileModel = new File();

// Pagination & Filter
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($_GET['per_page'] ?? 20);
$search = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$showDeleted = $_GET['show_deleted'] ?? '0';

// Build filters
$filters = [];
if ($search) $filters['search'] = $search;
if ($status !== '') $filters['status'] = $status;
if ($showDeleted === '1') $filters['show_deleted'] = true;

// Get paginated data
$result = $fileModel->getPaginated($page, $perPage, $filters);
$files = $result['data'];
$totalFiles = $result['total'];
$totalPages = $result['last_page'];
$offset = ($page - 1) * $perPage;

// Options for dropdown
$statusOptions = [
    '' => 'Semua Status',
    '1' => 'Aktif',
    '0' => 'Nonaktif'
];
$perPageOptions = [10, 20, 50, 100];
$showDeletedOptions = [
    '0' => 'Tampilkan Data Aktif',
    '1' => 'Tampilkan Data Terhapus'
];

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted">Kelola semua file yang dapat diunduh</p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Files</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <!-- Filter Card -->
        <div class="card shadow mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-center">
                    <div class="col-12 col-sm-4">
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari file..." class="form-control">
                    </div>
                    <div class="col-6 col-sm-2">
                        <select name="status" class="form-select custom-dropdown">
                            <?php foreach ($statusOptions as $val => $label): ?>
                                <option value="<?= $val ?>"<?= $status === $val ? ' selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-sm-2">
                        <select name="show_deleted" class="form-select custom-dropdown">
                            <?php foreach ($showDeletedOptions as $val => $label): ?>
                                <option value="<?= $val ?>"<?= $showDeleted === $val ? ' selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-sm-2">
                        <select name="per_page" class="form-select custom-dropdown">
                            <?php foreach ($perPageOptions as $n): ?>
                                <option value="<?= $n ?>"<?= $perPage == $n ? ' selected' : '' ?>><?= $n ?>/hlm</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-sm-2">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="bi bi-search"></i> <span class="d-none d-md-inline">Cari</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($search || $status !== '' || $showDeleted === '1'): ?>
            <div class="mb-3">
                <a href="files_list.php" class="btn btn-sm btn-secondary">
                    <i class="bi bi-x-circle"></i> Reset
                </a>
            </div>
        <?php endif; ?>

        <!-- Files Card -->
        <div class="card shadow">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-0">Daftar File</h5>
                    <p class="text-muted small mb-0">Total: <strong><?= $totalFiles ?></strong> file</p>
                </div>
                <a href="files_add.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> <span class="d-none d-md-inline">Upload File</span>
                </a>
            </div>

            <div class="card-body p-0">
                <?php if (empty($files)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-file-earmark" style="font-size: 3rem; color: #ccc;"></i>
                        <h5 class="text-muted mt-3">Tidak ada file ditemukan</h5>
                        <p class="text-muted">Coba ubah filter atau <a href="files_add.php">upload file baru</a></p>
                    </div>
                <?php else: ?>
                    <!-- Desktop Table View -->
                    <div class="table-responsive d-none d-lg-block">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40px;">No</th>
                                    <th>Nama File</th>
                                    <th>Ukuran/Tipe</th>
                                    <th>Deskripsi</th>
                                    <th class="text-center">Download</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Diunggah</th>
                                    <th class="text-center" style="width:120px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($files as $idx => $file):
                                    $isTrashed = !is_null($file['deleted_at'] ?? null);
                                ?>
                                    <tr<?= $isTrashed ? ' class="table-danger text-muted"' : '' ?>>
                                        <td><?= $offset + $idx + 1 ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-file-earmark me-2 fs-5" style="color: #6c757d;"></i>
                                                <div>
                                                    <div class="fw-semibold"><?= htmlspecialchars($file['title']) ?></div>
                                                    <?php if (!$isTrashed): ?>
                                                        <a href="#" class="small text-primary file-preview-link"
                                                           data-file-url="<?= publicFileUrl($file['file_path']) ?>"
                                                           data-file-type="<?= htmlspecialchars(strtolower($file['file_type'])) ?>"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#filePreviewModal"><?= basename($file['file_path']) ?></a>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary ms-2">Terhapus</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div><?= formatFileSize($file['file_size'] ?? 0) ?></div>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($file['file_type']) ?></span>
                                        </td>
                                        <td>
                                            <small><?= htmlspecialchars(truncateText($file['description'] ?? '', 50)) ?></small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info"><?= formatNumber($file['download_count'] ?? 0) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($file['is_active']): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <small><?= formatTanggal($file['created_at'], 'd M Y H:i') ?></small>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($isTrashed): ?>
                                                <span class="text-danger fw-semibold text-nowrap">
                                                    Deleted at <?= formatTanggal($file['deleted_at'], 'd M Y') ?>
                                                </span>
                                            <?php else: ?>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="files_edit.php?id=<?= $file['id'] ?>" class="btn btn-warning" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="files_delete.php?id=<?= $file['id'] ?>" class="btn btn-danger"
                                                       data-confirm-delete
                                                       data-title="<?= htmlspecialchars($file['title']) ?>"
                                                       data-message="File &quot;<?= htmlspecialchars($file['title']) ?>&quot; akan dipindahkan ke Trash. Lanjutkan?"
                                                       data-loading-text="Menghapus file..."
                                                       title="Hapus">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Card View -->
                    <div class="d-lg-none p-3">
                        <?php foreach ($files as $idx => $file):
                            $isTrashed = !is_null($file['deleted_at'] ?? null);
                        ?>
                            <div class="card mb-3 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="d-flex align-items-center flex-grow-1">
                                            <i class="bi bi-file-earmark me-2 fs-5" style="color: #6c757d;"></i>
                                            <div class="flex-grow-1">
                                                <div class="fw-semibold"><?= htmlspecialchars($file['title']) ?></div>
                                                <small class="text-muted"><?= formatFileSize($file['file_size'] ?? 0) ?> · <?= htmlspecialchars($file['file_type']) ?></small>
                                            </div>
                                        </div>
                                        <?php if ($file['is_active']): ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Nonaktif</span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($file['description']): ?>
                                        <div class="mb-2">
                                            <small class="text-muted"><?= htmlspecialchars(truncateText($file['description'], 60)) ?></small>
                                        </div>
                                    <?php endif; ?>

                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small class="text-muted">
                                            <i class="bi bi-download"></i> <?= formatNumber($file['download_count'] ?? 0) ?> downloads
                                        </small>
                                        <small class="text-muted"><?= formatTanggal($file['created_at'], 'd M Y') ?></small>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <?php if (!$isTrashed): ?>
                                            <a href="#" class="btn btn-sm btn-outline-info file-preview-link flex-grow-1"
                                               data-file-url="<?= publicFileUrl($file['file_path']) ?>"
                                               data-file-type="<?= htmlspecialchars(strtolower($file['file_type'])) ?>"
                                               data-bs-toggle="modal"
                                               data-bs-target="#filePreviewModal">
                                                <i class="bi bi-eye"></i> Preview
                                            </a>
                                            <a href="files_edit.php?id=<?= $file['id'] ?>" class="btn btn-sm btn-outline-warning">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="files_delete.php?id=<?= $file['id'] ?>" class="btn btn-sm btn-outline-danger"
                                               data-confirm-delete
                                               data-title="<?= htmlspecialchars($file['title']) ?>"
                                               data-message="File akan dipindahkan ke Trash. Lanjutkan?"
                                               data-loading-text="Menghapus...">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-danger w-100 text-center">Terhapus</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="card-footer border-top">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <small class="text-muted">
                                Halaman <?= $page ?> dari <?= $totalPages ?> · Menampilkan <?= count($files) ?> dari <?= $totalFiles ?> file
                            </small>
                            <nav>
                                <ul class="pagination mb-0">
                                    <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
                                    </li>
                                    <?php
                                    $from = max(1, $page - 2);
                                    $to = min($totalPages, $page + 2);
                                    for ($i = $from; $i <= $to; $i++): ?>
                                        <li class="page-item<?= $i == $page ? ' active' : '' ?>">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item<?= $page >= $totalPages ? ' disabled' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                            <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<!-- File Preview Modal -->
<div class="modal fade" id="filePreviewModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filePreviewModalLabel">Preview File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-0" id="file-preview-area" style="min-height:60vh;overflow:auto;">
                <div class="d-flex justify-content-center align-items-center h-100">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="downloadLink" class="btn btn-primary" target="_blank" download>
                    <i class="bi bi-download me-1"></i> Download
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filePreviewLinks = document.querySelectorAll(".file-preview-link");
    const filePreviewArea = document.getElementById("file-preview-area");
    const downloadLink = document.getElementById("downloadLink");
    
    filePreviewLinks.forEach(function(link) {
        link.addEventListener("click", function(e) {
            e.preventDefault();
            const url = this.dataset.fileUrl;
            const type = this.dataset.fileType;
            
            // Set download link
            downloadLink.href = url;
            
            // Show loading
            filePreviewArea.innerHTML = `
                <div class="d-flex justify-content-center align-items-center h-100">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;
            
            // Generate preview based on file type
            setTimeout(() => {
                let preview = '';
                
                if(["pdf"].includes(type)) {
                    preview = `<embed src="${url}#toolbar=0&navpanes=0" type="application/pdf" style="width:100%;min-height:70vh" />`;
                } else if(["jpg","jpeg","png","gif","webp","svg"].includes(type)) {
                    preview = `<img src="${url}" style="max-width:98%;max-height:80vh;border-radius:6px;" />`;
                } else if(["doc","docx","ppt","pptx","xls","xlsx"].includes(type)) {
                    preview = `<iframe src="https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(url)}" width="100%" height="600px" frameborder="0"></iframe>`;
                } else {
                    preview = `
                        <div class="p-4">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i> Preview online tidak tersedia untuk tipe file ini.
                            </div>
                            <a href="${url}" target="_blank" class="btn btn-primary" download>
                                <i class="bi bi-download me-1"></i> Download File
                            </a>
                        </div>
                    `;
                }
                
                filePreviewArea.innerHTML = preview;
            }, 500);
        });
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
