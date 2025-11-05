<?php
/**
 * Add Post Page
 * Create new post - REBUILT VERSION
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../core/Model.php';
require_once '../../../core/Validator.php';
require_once '../../../core/Upload.php';
require_once '../../../models/Post.php';
require_once '../../../models/PostCategory.php';
require_once '../../../models/Tag.php';

// Only admin and editor can add posts
if (!hasRole(['super_admin', 'admin', 'editor'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

$pageTitle = 'Tambah Post Baru';

$postModel = new Post();
$categoryModel = new PostCategory();
$tagModel = new Tag();
$validator = null;

// Get categories and tags
$categories = $categoryModel->getActive();
$allTags = $tagModel->getAll();

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validator = new Validator($_POST);
    
    // Validation rules
    $validator->required('title', 'Judul');
    $validator->required('content', 'Konten');
    $validator->required('category_id', 'Kategori');
    $validator->in('status', ['draft', 'published', 'archived'], 'Status');
    
    if ($validator->passes()) {
        try {
            $upload = new Upload();
            $featuredImage = null;
            
            // Handle image upload
            if (!empty($_FILES['featured_image']['name'])) {
                $featuredImage = $upload->upload($_FILES['featured_image'], 'posts');
                if (!$featuredImage) {
                    $validator->addError('featured_image', $upload->getError());
                }
            }
            
            if ($validator->passes()) {
                // Generate slug
                $slug = $postModel->generateSlug($_POST['title']);
                
                // Handle published_at
                $publishedAt = null;
                if ($_POST['status'] === 'published') {
                    $publishedAt = !empty($_POST['published_at']) ? $_POST['published_at'] : date('Y-m-d H:i:s');
                }
                
                // Prepare data
                $data = [
                    'title' => clean($_POST['title']),
                    'slug' => $slug,
                    'content' => $_POST['content'], // Don't clean HTML from CKEditor
                    'excerpt' => clean($_POST['excerpt'] ?? ''),
                    'featured_image' => $featuredImage,
                    'category_id' => (int)$_POST['category_id'],
                    'status' => clean($_POST['status']),
                    'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                    'published_at' => $publishedAt,
                    'meta_title' => clean($_POST['meta_title'] ?? ''),
                    'meta_description' => clean($_POST['meta_description'] ?? ''),
                    'meta_keywords' => clean($_POST['meta_keywords'] ?? ''),
                    'author_id' => getCurrentUser()['id']
                ];
                
                // Insert post
                $postId = $postModel->insert($data);
                
                if ($postId) {
                    
                    // ==========================================
                    // START: FINAL TAG HANDLING (FIXED)
                    // ==========================================
                    if (!empty($_POST['tags'])) {
                        $tagNames = array_map('trim', explode(',', $_POST['tags']));
                        $tagNames = array_filter($tagNames);
                        $tagNames = array_unique($tagNames);
                        $tagIds = [];
                    
                        foreach ($tagNames as $tagName) {
                            $normalizedName = strtolower(trim($tagName));
                            if (empty($normalizedName)) continue;
                    
                            // ** INI PERBAIKANNYA **
                            // Cek berdasarkan NAMA, bukan slug
                            $existingTag = $tagModel->findByName($normalizedName);
                    
                            if ($existingTag) {
                                // Tag SUDAH ADA - Gunakan ID yang existing
                                $tagIds[] = $existingTag['id'];
                            } else {
                                // Tag TIDAK ADA - Buat tag baru
                                $newTagId = $tagModel->insert([
                                    'name' => $normalizedName,
                                    'slug' => $tagModel->generateSlug($normalizedName) // Biarkan generateSlug handle slug
                                ]);
                                $tagIds[] = $newTagId;
                            }
                        }
                    
                        $tagIds = array_unique($tagIds);
                    
                        if (!empty($tagIds)) {
                            $postModel->syncTags($postId, $tagIds);
                        }
                    }
                    // ==========================================
                    // END: FINAL TAG HANDLING
                    // ==========================================
                    
                    logActivity('CREATE', "Menambah post: {$data['title']}", 'posts', $postId);
                    
                    setAlert('success', 'Post berhasil ditambahkan');
                    redirect(ADMIN_URL . 'modules/posts/posts_list.php');
                } else {
                    $validator->addError('general', 'Gagal menyimpan data');
                }
            }
            
        } catch (PDOException $e) {
            error_log("PDO Error: " . $e->getMessage());
            $validator->addError('general', 'Terjadi kesalahan database: ' . $e->getMessage());
        } catch (Exception $e) {
            error_log("General Error: " . $e->getMessage());
            $validator->addError('general', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="posts_list.php">Post</a></li>
                        <li class="breadcrumb-item active">Tambah Baru</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <form method="POST" enctype="multipart/form-data" id="postForm">
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Konten Post</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($validator && $validator->getError('general')): ?>
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <strong>Error:</strong> <?= $validator->getError('general') ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Judul Post <span class="text-danger">*</span></label>
                                <input type="text" name="title" 
                                       class="form-control <?= $validator && $validator->getError('title') ? 'is-invalid' : '' ?>" 
                                       value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" 
                                       placeholder="Masukkan judul post..." required>
                                <?php if ($validator && $validator->getError('title')): ?>
                                    <div class="invalid-feedback"><?= $validator->getError('title') ?></div>
                                <?php endif; ?>
                                <small class="text-muted">Slug akan dibuat otomatis dari judul</small>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Ringkasan/Excerpt</label>
                                <textarea name="excerpt" rows="3" class="form-control"
                                          placeholder="Ringkasan singkat post (opsional)"><?= htmlspecialchars($_POST['excerpt'] ?? '') ?></textarea>
                                <small class="text-muted">Akan ditampilkan di list post dan SEO</small>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Konten <span class="text-danger">*</span></label>
                                <textarea name="content" id="content" rows="15" class="form-control"><?= $_POST['content'] ?? '' ?></textarea>
                                <?php if ($validator && $validator->getError('content')): ?>
                                    <div class="text-danger small mt-1"><?= $validator->getError('content') ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">SEO Settings</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <label class="form-label">Meta Title</label>
                                <input type="text" name="meta_title" class="form-control" 
                                       value="<?= htmlspecialchars($_POST['meta_title'] ?? '') ?>"
                                       placeholder="Kosongkan untuk menggunakan judul post">
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Meta Description</label>
                                <textarea name="meta_description" rows="2" class="form-control"
                                          placeholder="Deskripsi untuk search engine (max 160 karakter)"><?= htmlspecialchars($_POST['meta_description'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="form-group mb-0">
                                <label class="form-label">Meta Keywords</label>
                                <input type="text" name="meta_keywords" class="form-control" 
                                       value="<?= htmlspecialchars($_POST['meta_keywords'] ?? '') ?>"
                                       placeholder="keyword1, keyword2, keyword3">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Publikasi</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                <select name="category_id" 
                                        class="form-select <?= $validator && $validator->getError('category_id') ? 'is-invalid' : '' ?>" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>" 
                                                <?= ($_POST['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($validator && $validator->getError('category_id')): ?>
                                    <div class="invalid-feedback"><?= $validator->getError('category_id') ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    <option value="draft" <?= ($_POST['status'] ?? 'draft') == 'draft' ? 'selected' : '' ?>>Draft</option>
                                    <option value="published" <?= ($_POST['status'] ?? '') == 'published' ? 'selected' : '' ?>>Published</option>
                                    <option value="archived" <?= ($_POST['status'] ?? '') == 'archived' ? 'selected' : '' ?>>Archived</option>
                                </select>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Tanggal Publish</label>
                                <input type="datetime-local" name="published_at" class="form-control"
                                       value="<?= $_POST['published_at'] ?? '' ?>">
                                <small class="text-muted">Kosongkan untuk publish sekarang</small>
                            </div>
                            
                            <div class="form-group mb-0">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="is_featured" value="1" 
                                           class="form-check-input" id="is_featured"
                                           <?= ($_POST['is_featured'] ?? '') == '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_featured">
                                        Post Unggulan/Featured
                                    </label>
                                </div>
                                <small class="text-muted">Tampilkan di homepage</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Gambar Utama</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-0">
                                <label class="form-label">Upload Gambar</label>
                                <input type="file" name="featured_image" 
                                       class="form-control <?= $validator && $validator->getError('featured_image') ? 'is-invalid' : '' ?>" 
                                       accept="image/*">
                                <small class="text-muted">Max <?= getSetting('upload_max_size', 5) ?>MB</small>
                                <?php if ($validator && $validator->getError('featured_image')): ?>
                                    <div class="invalid-feedback"><?= $validator->getError('featured_image') ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Tags</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <label class="form-label">Tags</label>
                                <input type="text" name="tags" id="tagsInput" class="form-control" 
                                       value="<?= htmlspecialchars($_POST['tags'] ?? '') ?>"
                                       placeholder="tag1, tag2, tag3">
                                <small class="text-muted">Pisahkan dengan koma. Tag baru akan dibuat otomatis.</small>
                            </div>
                            
                            <?php if (!empty($allTags)): ?>
                                <div>
                                    <small class="text-muted d-block mb-2">Tag populer (klik untuk tambah):</small>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php foreach (array_slice($allTags, 0, 10) as $tag): ?>
                                            <span class="badge bg-secondary" style="cursor: pointer;" 
                                                  onclick="addTag('<?= htmlspecialchars($tag['name']) ?>')">
                                                #<?= htmlspecialchars($tag['name']) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="bi bi-save"></i> Simpan Post
                                </button>
                                <a href="posts_list.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Batal
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
</div>

<script src="https://cdn.ckeditor.com/ckeditor5/40.1.0/classic/ckeditor.js"></script>
<script>
let editorInstance;

ClassicEditor
    .create(document.querySelector('#content'), {
        toolbar: [
            'heading', '|',
            'bold', 'italic', '|',
            'link', 'bulletedList', 'numberedList', '|',
            'indent', 'outdent', '|',
            'blockQuote', 'insertTable', '|',
            'undo', 'redo'
        ],
        heading: {
            options: [
                { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
                { model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' }
            ]
        }
    })
    .then(editor => {
        editorInstance = editor;
    })
    .catch(error => {
        console.error('CKEditor error:', error);
    });

// Handle form submit
document.getElementById('postForm').addEventListener('submit', function(e) {
    if (editorInstance) {
        // Sync CKEditor content to textarea
        document.querySelector('#content').value = editorInstance.getData();
    }
});

// Add tag function
function addTag(tagName) {
    const tagsInput = document.getElementById('tagsInput');
    const currentTags = tagsInput.value.split(',').map(t => t.trim()).filter(t => t);
    
    const normalizedTagName = tagName.toLowerCase();
    const normalizedCurrentTags = currentTags.map(t => t.toLowerCase());

    if (!normalizedCurrentTags.includes(normalizedTagName)) {
        currentTags.push(tagName);
        tagsInput.value = currentTags.join(', ');
    }
}
</script>

<?php include '../../includes/footer.php'; ?>