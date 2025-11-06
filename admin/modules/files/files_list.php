<?php
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

 $pageTitle = 'File Download';
 $currentPage = 'files';

 $db = Database::getInstance()->getConnection();

 $q = trim($_GET['q'] ?? '');
 $status = ($_GET['status'] ?? '');
 $page = max(1, (int)($_GET['page'] ?? 1));
 $perPage = 20;

 $where = ["deleted_at IS NULL"];
 $params = [];
if ($q) {
    $where[] = "(title LIKE ? OR description LIKE ?)";
    $params[] = "%$q%"; $params[] = "%$q%";
}
if ($status === '1' || $status === '0') {
    $where[] = "is_active = ?"; $params[] = $status;
}
 $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
 $countSql = "SELECT COUNT(*) AS total FROM downloadable_files $whereSql";
 $stmt = $db->prepare($countSql); $stmt->execute($params);
 $totalFiles = $stmt->fetch()['total'];
 $totalPages = ceil($totalFiles / $perPage);
 $sql = "SELECT * FROM downloadable_files $whereSql ORDER BY created_at DESC LIMIT $perPage OFFSET " . (($page-1)*$perPage);
 $stmt = $db->prepare($sql); $stmt->execute($params);
 $files = $stmt->fetchAll(PDO::FETCH_ASSOC); 

include '../../includes/header.php';
?>
<div class="page-heading">
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12 col-md-6 mb-2 mb-md-0">
                <h3><i class=""></i><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted mb-0">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-md-end">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">File Download</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    
    <section class="section">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="get">
                    <div class="row g-3">
                        <div class="col-12 col-md-5">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" class="form-control" name="q" placeholder="Cari judul/deskripsi/file..." value="<?= htmlspecialchars($q) ?>">
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <select class="form-select" name="status">
                                <option value="">Semua Status</option>
                                <option value="1"<?= $status==='1' ? ' selected' : '' ?>>Aktif</option>
                                <option value="0"<?= $status==='0' ? ' selected' : '' ?>>Nonaktif</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <button class="btn btn-primary w-100" type="submit">
                                <i class="bi bi-search me-1"></i> Cari
                            </button>
                        </div>
                        <div class="col-6 col-md-2">
                            <a href="files_add.php" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Upload File
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-header border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Daftar File</h5>
                    <span class="badge bg-primary"><?= $totalFiles ?> file</span>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($files)): ?>
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="bi bi-file-earmark text-muted" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="text-muted">Tidak ada file ditemukan</h5>
                        <p class="text-muted">Coba ubah filter atau upload file baru</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive d-none d-md-block">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="25%">Nama File</th>
                                    <th width="15%">Ukuran/Tipe</th>
                                    <th width="25%">Deskripsi</th>
                                    <th width="10%">Status</th>
                                    <th width="10%">Diunggah</th>
                                    <th width="10%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($files as $idx => $file): ?>
                                    <tr>
                                        <td><?= (($page-1)*$perPage)+$idx+1 ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    <?php
                                                    $iconClass = 'bi-file-earmark';
                                                    $iconColor = 'text-secondary';
                                                    
                                                    if (in_array(strtolower($file['file_type']), ['pdf'])) {
                                                        $iconClass = 'bi-file-earmark-pdf';
                                                        $iconColor = 'text-danger';
                                                    } elseif (in_array(strtolower($file['file_type']), ['doc', 'docx'])) {
                                                        $iconClass = 'bi-file-earmark-word';
                                                        $iconColor = 'text-primary';
                                                    } elseif (in_array(strtolower($file['file_type']), ['xls', 'xlsx'])) {
                                                        $iconClass = 'bi-file-earmark-excel';
                                                        $iconColor = 'text-success';
                                                    } elseif (in_array(strtolower($file['file_type']), ['ppt', 'pptx'])) {
                                                        $iconClass = 'bi-file-earmark-slides';
                                                        $iconColor = 'text-warning';
                                                    } elseif (in_array(strtolower($file['file_type']), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
                                                        $iconClass = 'bi-file-earmark-image';
                                                        $iconColor = 'text-info';
                                                    } elseif (in_array(strtolower($file['file_type']), ['zip', 'rar', '7z'])) {
                                                        $iconClass = 'bi-file-earmark-zip';
                                                        // [FIXED] 'text-dark' changed to 'text-secondary' for dark mode visibility
                                                        $iconColor = 'text-secondary';
                                                    }
                                                    ?>
                                                    <i class="bi <?= $iconClass ?> <?= $iconColor ?> fs-4"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold"><?= htmlspecialchars($file['title']) ?></div>
                                                    <a href="#" class="small text-primary file-preview-link"
                                                       data-file-url="<?= publicFileUrl($file['file_path']) ?>"
                                                       data-file-type="<?= htmlspecialchars(strtolower($file['file_type'])) ?>"
                                                       data-bs-toggle="modal"
                                                       data-bs-target="#filePreviewModal"><?= basename($file['file_path']) ?></a>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small"><?= formatFileSize($file['file_size']) ?></div>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($file['file_type']) ?></span>
                                        </td>
                                        <td>
                                            <span class="small"><?= htmlspecialchars(truncateText($file['description'], 60)) ?></span>
                                        </td>
                                        <td>
                                            <?php if ($file['is_active']): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="small"><?= formatTanggal($file['created_at'], 'd M Y') ?></div>
                                            <div class="text-muted small"><?= formatTanggal($file['created_at'], 'H:i') ?></div>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="files_edit.php?id=<?= $file['id'] ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="files_delete.php?id=<?= $file['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus file ini?')" title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="d-md-none p-3">
                        <?php foreach ($files as $idx => $file): ?>
                            <div class="card mb-3 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="d-flex align-items-center">
                                            <?php
                                            $iconClass = 'bi-file-earmark';
                                            $iconColor = 'text-secondary';
                                            
                                            if (in_array(strtolower($file['file_type']), ['pdf'])) {
                                                $iconClass = 'bi-file-earmark-pdf';
                                                $iconColor = 'text-danger';
                                            } elseif (in_array(strtolower($file['file_type']), ['doc', 'docx'])) {
                                                $iconClass = 'bi-file-earmark-word';
                                                $iconColor = 'text-primary';
                                            } elseif (in_array(strtolower($file['file_type']), ['xls', 'xlsx'])) {
                                                $iconClass = 'bi-file-earmark-excel';
                                                $iconColor = 'text-success';
                                            } elseif (in_array(strtolower($file['file_type']), ['ppt', 'pptx'])) {
                                                $iconClass = 'bi-file-earmark-slides';
                                                $iconColor = 'text-warning';
                                            } elseif (in_array(strtolower($file['file_type']), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
                                                $iconClass = 'bi-file-earmark-image';
                                                $iconColor = 'text-info';
                                            } elseif (in_array(strtolower($file['file_type']), ['zip', 'rar', '7z'])) {
                                                $iconClass = 'bi-file-earmark-zip';
                                                // [FIXED] 'text-dark' changed to 'text-secondary' for dark mode visibility
                                                $iconColor = 'text-secondary';
                                            }
                                            ?>
                                            <i class="bi <?= $iconClass ?> <?= $iconColor ?> fs-4 me-2"></i>
                                            <div>
                                                <div class="fw-semibold"><?= htmlspecialchars($file['title']) ?></div>
                                                <div class="small text-muted"><?= formatFileSize($file['file_size']) ?></div>
                                            </div>
                                        </div>
                                        <?php if ($file['is_active']): ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Nonaktif</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <a href="#" class="small text-primary file-preview-link"
                                           data-file-url="<?= publicFileUrl($file['file_path']) ?>"
                                           data-file-type="<?= htmlspecialchars(strtolower($file['file_type'])) ?>"
                                           data-bs-toggle="modal"
                                           data-bs-target="#filePreviewModal"><?= basename($file['file_path']) ?></a>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <span class="small text-muted"><?= htmlspecialchars(truncateText($file['description'], 60)) ?></span>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="small text-muted">
                                            <i class="bi bi-calendar me-1"></i> <?= formatTanggal($file['created_at'], 'd M Y') ?>
                                        </div>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="files_edit.php?id=<?= $file['id'] ?>" class="btn btn-outline-warning" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="files_delete.php?id=<?= $file['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Hapus file ini?')" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($totalPages > 1): ?>
                    <div class="card-footer border-top">
                        <nav>
                            <ul class="pagination pagination-sm justify-content-center mb-0">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?q=<?= urlencode($q) ?>&status=<?= urlencode($status) ?>&page=<?= $page-1 ?>">
                                            <i class="bi bi-chevron-left"></i>
                                            <span class="d-none d-md-inline ms-1">Previous</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                for ($p = $startPage; $p <= $endPage; $p++):
                                ?>
                                    <li class="page-item<?= $p === $page ? ' active' : '' ?>">
                                        <a class="page-link" href="?q=<?= urlencode($q) ?>&status=<?= urlencode($status) ?>&page=<?= $p ?>"><?= $p ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?q=<?= urlencode($q) ?>&status=<?= urlencode($status) ?>&page=<?= $page+1 ?>">
                                            <span class="d-none d-md-inline me-1">Next</span>
                                            <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="filePreviewModal" tabindex="-1" aria-labelledby="filePreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filePreviewModalLabel">Preview File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                <a href="#" id="downloadLink" class="btn btn-primary" target="_blank">
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
                    preview = `<iframe src="https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(url)}" width="100%" height="600" frameborder="0"></iframe>`;
                } else {
                    preview = `
                        <div class="p-4">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i> Preview online tidak tersedia untuk tipe file ini.
                            </div>
                            <a href="${url}" target="_blank" class="btn btn-primary">
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