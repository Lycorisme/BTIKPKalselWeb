<?php
/**
 * Settings Management - Complete with Logo Text & Copyright
 * Single page untuk manage semua settings
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../core/Validator.php';
require_once '../../../core/Upload.php';

$pageTitle = 'Pengaturan Website';

// Only admin can access
if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

$db = Database::getInstance()->getConnection();
$validator = null;

// Get all settings
$stmt = $db->query("SELECT * FROM settings ORDER BY `group`, `key`");
$allSettings = $stmt->fetchAll();

// Convert to key-value array
$settings = [];
foreach ($allSettings as $setting) {
    $settings[$setting['key']] = $setting['value'];
}

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validator = new Validator($_POST);
    
    // Verify CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $validator->addError('general', 'Invalid CSRF token');
    }
    
    if ($validator->passes()) {
        try {
            $upload = new Upload();
            $updated = 0;
            
            // Process checkbox values
            $checkboxFields = ['site_logo_show_text'];
            foreach ($checkboxFields as $field) {
                if (!isset($_POST[$field])) {
                    $_POST[$field] = '0';
                }
            }
            
            // Process each posted setting
            foreach ($_POST as $key => $value) {
                if ($key === 'csrf_token') continue;
                
                // Clean value
                $cleanValue = is_array($value) ? implode(',', $value) : clean($value);
                
                // Update or insert setting
                $stmt = $db->prepare("
                    INSERT INTO settings (`key`, `value`, updated_at) 
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                    `value` = VALUES(`value`), 
                    updated_at = NOW()
                ");
                if ($stmt->execute([$key, $cleanValue])) {
                    $updated++;
                }
            }
            
            // Handle file uploads (logo & favicon)
            if (!empty($_FILES['site_logo']['name'])) {
                $logo = $upload->upload($_FILES['site_logo'], 'settings');
                if ($logo) {
                    // Delete old logo
                    $oldLogo = getSetting('site_logo');
                    if ($oldLogo) $upload->delete($oldLogo);
                    
                    setSetting('site_logo', $logo);
                    $updated++;
                }
            }
            
            if (!empty($_FILES['site_favicon']['name'])) {
                $favicon = $upload->upload($_FILES['site_favicon'], 'settings');
                if ($favicon) {
                    // Delete old favicon
                    $oldFavicon = getSetting('site_favicon');
                    if ($oldFavicon) $upload->delete($oldFavicon);
                    
                    setSetting('site_favicon', $favicon);
                    $updated++;
                }
            }
            
            // Log activity
            logActivity('UPDATE', "Mengupdate settings website ($updated items)", 'settings');
            
            setAlert('success', "Settings berhasil diupdate ($updated items)");
            redirect(ADMIN_URL . 'modules/settings/settings.php');
            
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $validator->addError('general', 'Terjadi kesalahan sistem: ' . $e->getMessage());
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
                        <li class="breadcrumb-item active">Settings</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <?php if ($validator && $validator->getError('general')): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $validator->getError('general') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($alert = getAlert()): ?>
            <div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show">
                <?= $alert['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>
            
            <div class="row">
                <!-- General Settings -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-gear"></i> Pengaturan Umum
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Site Name -->
                            <div class="form-group mb-3">
                                <label class="form-label">Nama Website</label>
                                <input type="text" name="site_name" class="form-control" 
                                       value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>">
                            </div>
                            
                            <!-- Site Tagline -->
                            <div class="form-group mb-3">
                                <label class="form-label">Tagline/Slogan</label>
                                <input type="text" name="site_tagline" class="form-control" 
                                       value="<?= htmlspecialchars($settings['site_tagline'] ?? '') ?>">
                            </div>
                            
                            <!-- Site Description -->
                            <div class="form-group mb-3">
                                <label class="form-label">Deskripsi Website</label>
                                <textarea name="site_description" class="form-control" rows="3"><?= htmlspecialchars($settings['site_description'] ?? '') ?></textarea>
                                <small class="text-muted">Untuk SEO meta description</small>
                            </div>
                            
                            <!-- Keywords -->
                            <div class="form-group mb-3">
                                <label class="form-label">Keywords (SEO)</label>
                                <input type="text" name="site_keywords" class="form-control" 
                                       value="<?= htmlspecialchars($settings['site_keywords'] ?? '') ?>">
                                <small class="text-muted">Pisahkan dengan koma</small>
                            </div>
                            
                            <!-- Logo -->
                            <div class="form-group mb-3">
                                <label class="form-label">Logo Website</label>
                                <?php if (!empty($settings['site_logo'])): ?>
                                    <div class="mb-2">
                                        <img src="<?= uploadUrl($settings['site_logo']) ?>" 
                                             alt="Logo" style="max-height: 60px;">
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="site_logo" class="form-control" accept="image/*">
                                <small class="text-muted">PNG/JPG, max 2MB. Rekomendasi: 200x60px</small>
                            </div>
                            
                            <!-- Logo Text -->
                            <div class="form-group mb-3">
                                <label class="form-label">Logo Text</label>
                                <input type="text" name="site_logo_text" class="form-control" 
                                       value="<?= htmlspecialchars($settings['site_logo_text'] ?? 'BTIKP KALSEL') ?>"
                                       placeholder="Text yang muncul di sebelah logo">
                                <small class="text-muted">Akan muncul di sebelah logo di header</small>
                            </div>
                            
                            <!-- Show Logo Text -->
                            <div class="form-group mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="site_logo_show_text" id="site_logo_show_text" 
                                           class="form-check-input" 
                                           value="1" <?= ($settings['site_logo_show_text'] ?? '1') == '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="site_logo_show_text">
                                        Tampilkan text di sebelah logo
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Favicon -->
                            <div class="form-group mb-3">
                                <label class="form-label">Favicon</label>
                                <?php if (!empty($settings['site_favicon'])): ?>
                                    <div class="mb-2">
                                        <img src="<?= uploadUrl($settings['site_favicon']) ?>" 
                                             alt="Favicon" style="max-height: 32px;">
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="site_favicon" class="form-control" accept="image/*">
                                <small class="text-muted">ICO/PNG, 32x32px atau 64x64px</small>
                            </div>
                            
                            <!-- Copyright Text -->
                            <div class="form-group mb-0">
                                <label class="form-label">Copyright Text</label>
                                <input type="text" name="site_copyright" class="form-control" 
                                       value="<?= htmlspecialchars($settings['site_copyright'] ?? '© {year} BTIKP Kalimantan Selatan. All Rights Reserved.') ?>">
                                <small class="text-muted">Text copyright di footer. Gunakan <code>{year}</code> untuk tahun otomatis.</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Upload Settings -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-cloud-upload"></i> Pengaturan Upload
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Max Upload Size -->
                            <div class="form-group mb-3">
                                <label class="form-label">Max File Size (MB)</label>
                                <input type="number" name="upload_max_size" class="form-control" 
                                       value="<?= htmlspecialchars($settings['upload_max_size'] ?? '5') ?>" min="1" max="50">
                            </div>
                            
                            <!-- Allowed Images -->
                            <div class="form-group mb-3">
                                <label class="form-label">Format Gambar Diizinkan</label>
                                <input type="text" name="upload_allowed_images" class="form-control" 
                                       value="<?= htmlspecialchars($settings['upload_allowed_images'] ?? '') ?>">
                                <small class="text-muted">Pisahkan dengan koma. Contoh: jpg,png,gif</small>
                            </div>
                            
                            <!-- Allowed Docs -->
                            <div class="form-group mb-3">
                                <label class="form-label">Format Dokumen Diizinkan</label>
                                <input type="text" name="upload_allowed_docs" class="form-control" 
                                       value="<?= htmlspecialchars($settings['upload_allowed_docs'] ?? '') ?>">
                                <small class="text-muted">Contoh: pdf,doc,docx,xls,xlsx</small>
                            </div>
                            
                            <!-- Pagination -->
                            <div class="form-group mb-0">
                                <label class="form-label">Items Per Page (Admin)</label>
                                <input type="number" name="items_per_page" class="form-control" 
                                       value="<?= htmlspecialchars($settings['items_per_page'] ?? '10') ?>" min="5" max="100">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Contact & Social -->
                <div class="col-lg-6">
                    <!-- Contact Info -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-telephone"></i> Informasi Kontak
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Phone -->
                            <div class="form-group mb-3">
                                <label class="form-label">Telepon</label>
                                <input type="text" name="contact_phone" class="form-control" 
                                       value="<?= htmlspecialchars($settings['contact_phone'] ?? '') ?>">
                            </div>
                            
                            <!-- Email -->
                            <div class="form-group mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="contact_email" class="form-control" 
                                       value="<?= htmlspecialchars($settings['contact_email'] ?? '') ?>">
                            </div>
                            
                            <!-- Address -->
                            <div class="form-group mb-3">
                                <label class="form-label">Alamat Lengkap</label>
                                <textarea name="contact_address" class="form-control" rows="3"><?= htmlspecialchars($settings['contact_address'] ?? '') ?></textarea>
                            </div>
                            
                            <!-- Maps Embed -->
                            <div class="form-group mb-0">
                                <label class="form-label">Google Maps Embed Code</label>
                                <textarea name="contact_maps_embed" class="form-control" rows="3" placeholder="<iframe src=...></iframe>"><?= htmlspecialchars($settings['contact_maps_embed'] ?? '') ?></textarea>
                                <small class="text-muted">Paste iframe embed code dari Google Maps</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Social Media -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-share"></i> Social Media
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Facebook -->
                            <div class="form-group mb-3">
                                <label class="form-label">
                                    <i class="bi bi-facebook text-primary"></i> Facebook URL
                                </label>
                                <input type="url" name="social_facebook" class="form-control" 
                                       value="<?= htmlspecialchars($settings['social_facebook'] ?? '') ?>"
                                       placeholder="https://facebook.com/yourpage">
                            </div>
                            
                            <!-- Instagram -->
                            <div class="form-group mb-3">
                                <label class="form-label">
                                    <i class="bi bi-instagram text-danger"></i> Instagram URL
                                </label>
                                <input type="url" name="social_instagram" class="form-control" 
                                       value="<?= htmlspecialchars($settings['social_instagram'] ?? '') ?>"
                                       placeholder="https://instagram.com/yourprofile">
                            </div>
                            
                            <!-- YouTube -->
                            <div class="form-group mb-3">
                                <label class="form-label">
                                    <i class="bi bi-youtube text-danger"></i> YouTube URL
                                </label>
                                <input type="url" name="social_youtube" class="form-control" 
                                       value="<?= htmlspecialchars($settings['social_youtube'] ?? '') ?>"
                                       placeholder="https://youtube.com/@yourchannel">
                            </div>
                            
                            <!-- Twitter -->
                            <div class="form-group mb-0">
                                <label class="form-label">
                                    <i class="bi bi-twitter text-info"></i> Twitter/X URL
                                </label>
                                <input type="url" name="social_twitter" class="form-control" 
                                       value="<?= htmlspecialchars($settings['social_twitter'] ?? '') ?>"
                                       placeholder="https://twitter.com/yourprofile">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Save Button -->
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save"></i> Simpan Semua Pengaturan
                            </button>
                            <a href="<?= ADMIN_URL ?>" class="btn btn-secondary btn-lg">
                                <i class="bi bi-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
