<?php
/**
 * Settings Management - Complete with Logo Text & Copyright
 * WITH CUSTOM NOTIFICATIONS SYSTEM
 * UPDATED: Working Hours, Auto-Response, Email & Notification Settings
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
            $checkboxFields = [
                'site_logo_show_text', 
                'site_maintenance_mode',
                'office_show_status',
                'contact_auto_reply',
                'contact_working_hours_only',
                'email_smtp_enable',
                'notification_new_contact'
            ];
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
            
            // Handle login background image upload
            if (!empty($_FILES['login_background_image']['name'])) {
                $loginBg = $upload->upload($_FILES['login_background_image'], 'backgrounds');
                if ($loginBg) {
                    $oldBg = getSetting('login_background_image');
                    if ($oldBg) $upload->delete($oldBg);
                    
                    setSetting('login_background_image', $loginBg);
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
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                notify.error('<?= addslashes($validator->getError('general')) ?>');
            });
            </script>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" id="settingsForm">
            <?= csrfField() ?>
            
            <div class="row">
                <!-- ========================================
                     KOLOM KIRI
                ======================================== -->
                <div class="col-lg-6">
                    <!-- General Settings -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-gear"></i> Pengaturan Umum
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <label class="form-label">Nama Website</label>
                                <input type="text" name="site_name" class="form-control" 
                                       value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Tagline/Slogan</label>
                                <input type="text" name="site_tagline" class="form-control" 
                                       value="<?= htmlspecialchars($settings['site_tagline'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Deskripsi Website</label>
                                <textarea name="site_description" class="form-control" rows="3"><?= htmlspecialchars($settings['site_description'] ?? '') ?></textarea>
                                <small class="text-muted">Untuk SEO meta description</small>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Keywords (SEO)</label>
                                <input type="text" name="site_keywords" class="form-control" 
                                       value="<?= htmlspecialchars($settings['site_keywords'] ?? '') ?>">
                                <small class="text-muted">Pisahkan dengan koma</small>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Logo Website</label>
                                <?php if (!empty($settings['site_logo'])): ?>
                                    <div class="mb-2">
                                        <img src="<?= uploadUrl($settings['site_logo']) ?>" 
                                             alt="Logo" style="max-height: 60px;">
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="site_logo" class="form-control" accept="image/*">
                                <small class="text-muted">
                                    PNG/JPG, max <strong><?= htmlspecialchars($settings['upload_max_size'] ?? '5') ?>MB</strong>. Rekomendasi: 200x60px
                                </small>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Logo Text</label>
                                <input type="text" name="site_logo_text" class="form-control" 
                                       value="<?= htmlspecialchars($settings['site_logo_text'] ?? 'BTIKP KALSEL') ?>"
                                       placeholder="Text yang muncul di sebelah logo">
                                <small class="text-muted">Akan muncul di sebelah logo di header</small>
                            </div>
                            
                            <div class="form-group mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="site_logo_show_text" id="site_logo_show_text" 
                                           class="form-check-input" 
                                           value="1" <?= ($settings['site_logo_show_text'] ?? '1') == '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="site_logo_show_text">
                                        Tampilkan text di bawah logo
                                    </label>
                                </div>
                            </div>
                            
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
                            
                            <div class="form-group mb-0">
                                <label class="form-label">Copyright Text</label>
                                <input type="text" name="site_copyright" class="form-control" 
                                       value="<?= htmlspecialchars($settings['site_copyright'] ?? '© {year} BTIKP Kalimantan Selatan. All Rights Reserved.') ?>">
                                <small class="text-muted">Text copyright di footer. Gunakan <code>{year}</code> untuk tahun otomatis.</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Upload Settings -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-cloud-upload"></i> Pengaturan Upload
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <label class="form-label">Max File Size (MB)</label>
                                <input type="number" name="upload_max_size" class="form-control" 
                                       value="<?= htmlspecialchars($settings['upload_max_size'] ?? '5') ?>" min="1" max="50">
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Format Gambar Diizinkan</label>
                                <input type="text" name="upload_allowed_images" class="form-control" 
                                       value="<?= htmlspecialchars($settings['upload_allowed_images'] ?? '') ?>">
                                <small class="text-muted">Pisahkan dengan koma. Contoh: jpg,png,gif</small>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Format Dokumen Diizinkan</label>
                                <input type="text" name="upload_allowed_docs" class="form-control" 
                                       value="<?= htmlspecialchars($settings['upload_allowed_docs'] ?? '') ?>">
                                <small class="text-muted">Contoh: pdf,doc,docx,xls,xlsx</small>
                            </div>
                            
                            <div class="form-group mb-0">
                                <label class="form-label">Items Per Page (Admin)</label>
                                <input type="number" name="items_per_page" class="form-control" 
                                       value="<?= htmlspecialchars($settings['items_per_page'] ?? '10') ?>" min="5" max="100">
                            </div>
                        </div>
                    </div>

                    <!-- Notification Theme -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-bell"></i> Gaya Notifikasi & Alert
                            </h5>
                        </div>
                        <div class="card-body">                               
                            <?php
                            $themeStyles = [
                                'alecto-final-blow' => 'Alecto: Final Blow',
                                'an-eye-for-an-eye' => 'An Eye for An Eye',
                                'throne-of-ruin' => 'Throne of Ruin',
                                'hoki-crossbow-of-tang' => 'Hoki Crossbow of Tang',
                                'death-sonata' => 'Death Sonata'
                            ];
                            
                            $currentTheme = $settings['notification_alert_theme'] ?? 'alecto-final-blow';
                            ?>
                            
                            <div class="form-group mb-3">
                                <label class="form-label fw-bold" for="notification_alert_theme">
                                    Pilih Tema
                                </label>
                                <select name="notification_alert_theme" id="notification_alert_theme" class="form-select">
                                    <?php foreach ($themeStyles as $key => $label): ?>
                                        <option value="<?= $key ?>" <?= $currentTheme === $key ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mt-4 pt-3 border-top">
                                <button type="button" class="btn btn-primary btn-lg" 
                                        onclick="previewTheme()" 
                                        id="btnPreview">
                                    <i class="bi bi-eye"></i> Preview
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Maintenance Mode -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-wrench"></i> Mode Maintenance
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="site_maintenance_mode" id="site_maintenance_mode"
                                            class="form-check-input"
                                            value="1" <?= ($settings['site_maintenance_mode'] ?? '0') == '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold" for="site_maintenance_mode">
                                        Aktifkan Mode Maintenance
                                    </label>
                                </div>
                                <small class="text-muted">Jika diaktifkan, halaman public akan menampilkan halaman maintenance. Admin tetap bisa mengakses dashboard.</small>
                            </div>
                                    
                            <div class="form-group mb-0">
                                <label class="form-label">Pesan Maintenance</label>
                                <textarea name="site_maintenance_message" class="form-control" rows="3"><?= htmlspecialchars($settings['site_maintenance_message'] ?? 'Website sedang dalam pemeliharaan. Silakan kembali beberapa saat lagi.') ?></textarea>
                                <small class="text-muted">Pesan yang akan ditampilkan pada halaman maintenance</small>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================
                         SECTION BARU: WORKING HOURS & HOLIDAY
                    ======================================== -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-clock-history"></i> Jam Kerja & Hari Libur
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <label class="form-label">Hari Kerja</label>
                                <input type="text" name="office_working_days" class="form-control" 
                                       value="<?= htmlspecialchars($settings['office_working_days'] ?? 'monday,tuesday,wednesday,thursday,friday') ?>">
                                <small class="text-muted">Pisahkan dengan koma. Contoh: monday,tuesday,wednesday,thursday,friday</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label">Jam Mulai Kerja</label>
                                        <input type="time" name="office_start_time" class="form-control" 
                                               value="<?= htmlspecialchars($settings['office_start_time'] ?? '08:00') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label">Jam Selesai Kerja</label>
                                        <input type="time" name="office_end_time" class="form-control" 
                                               value="<?= htmlspecialchars($settings['office_end_time'] ?? '16:00') ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label">Jam Mulai Istirahat</label>
                                        <input type="time" name="office_break_start" class="form-control" 
                                               value="<?= htmlspecialchars($settings['office_break_start'] ?? '12:00') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label">Jam Selesai Istirahat</label>
                                        <input type="time" name="office_break_end" class="form-control" 
                                               value="<?= htmlspecialchars($settings['office_break_end'] ?? '13:00') ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label">Tanggal Hari Libur</label>
                                <textarea name="office_holiday_dates" class="form-control" rows="3" placeholder="2025-12-25, 2025-12-31, 2026-01-01"><?= htmlspecialchars($settings['office_holiday_dates'] ?? '') ?></textarea>
                                <small class="text-muted">Format: YYYY-MM-DD, pisahkan dengan koma</small>
                            </div>

                            <div class="form-group mb-0">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="office_show_status" id="office_show_status" 
                                           class="form-check-input" 
                                           value="1" <?= ($settings['office_show_status'] ?? '1') == '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="office_show_status">
                                        Tampilkan Status Kantor di Website
                                    </label>
                                </div>
                                <small class="text-muted">Menampilkan "Buka" atau "Tutup" berdasarkan jam kerja</small>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================
                         SECTION BARU: AUTO-RESPONSE MESSAGES
                    ======================================== -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-chat-left-text"></i> Auto-Response Messages
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="contact_auto_reply" id="contact_auto_reply" 
                                           class="form-check-input" 
                                           value="1" <?= ($settings['contact_auto_reply'] ?? '1') == '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold" for="contact_auto_reply">
                                        Aktifkan Balasan Otomatis
                                    </label>
                                </div>
                                <small class="text-muted">Auto-reply akan dikirim ketika ada pesan masuk dari form kontak</small>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label">Pesan Balasan Otomatis</label>
                                <textarea name="contact_auto_reply_message" class="form-control" rows="3"><?= htmlspecialchars($settings['contact_auto_reply_message'] ?? 'Terima kasih atas pesan Anda. Kami akan merespon dalam 1x24 jam.') ?></textarea>
                                <small class="text-muted">Pesan yang akan dikirim otomatis kepada pengirim</small>
                            </div>

                            <div class="form-group mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="contact_working_hours_only" id="contact_working_hours_only" 
                                           class="form-check-input" 
                                           value="1" <?= ($settings['contact_working_hours_only'] ?? '1') == '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="contact_working_hours_only">
                                        Gunakan Pesan Berbeda di Luar Jam Kerja
                                    </label>
                                </div>
                            </div>

                            <div class="form-group mb-0">
                                <label class="form-label">Pesan di Luar Jam Kerja</label>
                                <textarea name="contact_non_working_message" class="form-control" rows="3"><?= htmlspecialchars($settings['contact_non_working_message'] ?? 'Kantor sedang tutup. Pesan Anda akan dibalas pada hari kerja berikutnya.') ?></textarea>
                                <small class="text-muted">Pesan yang ditampilkan jika di luar jam kerja</small>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- ========================================
                     KOLOM KANAN
                ======================================== -->
                <div class="col-lg-6">
                    <!-- Contact Info -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-telephone"></i> Informasi Kontak
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <label class="form-label">Telepon</label>
                                <input type="text" name="contact_phone" class="form-control" 
                                       value="<?= htmlspecialchars($settings['contact_phone'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="contact_email" class="form-control" 
                                       value="<?= htmlspecialchars($settings['contact_email'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Alamat Lengkap</label>
                                <textarea name="contact_address" class="form-control" rows="3"><?= htmlspecialchars($settings['contact_address'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="form-group mb-0">
                                <label class="form-label">Google Maps Embed Code</label>
                                <textarea name="contact_maps_embed" class="form-control" rows="3" placeholder="<iframe src=...></iframe>"><?= htmlspecialchars($settings['contact_maps_embed'] ?? '') ?></textarea>
                                <small class="text-muted">Paste iframe embed code dari Google Maps</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Social Media -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-share"></i> Social Media
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <label class="form-label">
                                    <i class="bi bi-facebook text-primary"></i> Facebook URL
                                </label>
                                <input type="url" name="social_facebook" class="form-control" 
                                       value="<?= htmlspecialchars($settings['social_facebook'] ?? '') ?>"
                                       placeholder="https://facebook.com/yourpage">
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">
                                    <i class="bi bi-instagram text-danger"></i> Instagram URL
                                </label>
                                <input type="url" name="social_instagram" class="form-control" 
                                       value="<?= htmlspecialchars($settings['social_instagram'] ?? '') ?>"
                                       placeholder="https://instagram.com/yourprofile">
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">
                                    <i class="bi bi-youtube text-danger"></i> YouTube URL
                                </label>
                                <input type="url" name="social_youtube" class="form-control" 
                                       value="<?= htmlspecialchars($settings['social_youtube'] ?? '') ?>"
                                       placeholder="https://youtube.com/@yourchannel">
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">
                                    <i class="bi bi-twitter text-info"></i> Twitter/X URL
                                </label>
                                <input type="url" name="social_twitter" class="form-control" 
                                       value="<?= htmlspecialchars($settings['social_twitter'] ?? '') ?>"
                                       placeholder="https://twitter.com/yourprofile">
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">
                                    <i class="bi bi-tiktok text-dark"></i> TikTok URL
                                </label>
                                <input type="url" name="social_tiktok" class="form-control" 
                                       value="<?= htmlspecialchars($settings['social_tiktok'] ?? '') ?>"
                                       placeholder="https://tiktok.com/@yourprofile">
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">
                                    <i class="bi bi-linkedin text-primary"></i> LinkedIn URL
                                </label>
                                <input type="url" name="social_linkedin" class="form-control" 
                                       value="<?= htmlspecialchars($settings['social_linkedin'] ?? '') ?>"
                                       placeholder="https://linkedin.com/in/yourprofile">
                            </div>
                            
                            <div class="form-group mb-0">
                                <label class="form-label">
                                    <i class="bi bi-whatsapp text-success"></i> WhatsApp Link/Number
                                </label>
                                <input type="text" name="social_whatsapp" class="form-control" 
                                       value="<?= htmlspecialchars($settings['social_whatsapp'] ?? '') ?>"
                                       placeholder="https://wa.me/628xxxxxxx atau +62 812-xxxx-xxxx">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Appearance (Background) -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-palette"></i> Appearance (Background)
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <label class="form-label fw-bold">Login Page Background</label>
                                
                                <div class="form-check">
                                    <input type="radio" name="login_background_type" id="login_bg_gradient" 
                                           class="form-check-input" value="gradient" 
                                           <?= ($settings['login_background_type'] ?? 'gradient') === 'gradient' ? 'checked' : '' ?>
                                           onchange="toggleBackgroundFields('login')">
                                    <label class="form-check-label" for="login_bg_gradient">
                                        Gradient
                                    </label>
                                </div>
                                
                                <div class="form-check">
                                    <input type="radio" name="login_background_type" id="login_bg_image" 
                                           class="form-check-input" value="image" 
                                           <?= ($settings['login_background_type'] ?? 'gradient') === 'image' ? 'checked' : '' ?>
                                           onchange="toggleBackgroundFields('login')">
                                    <label class="form-check-label" for="login_bg_image">
                                        Custom Image
                                    </label>
                                </div>
                                
                                <div class="form-check">
                                    <input type="radio" name="login_background_type" id="login_bg_solid" 
                                           class="form-check-input" value="solid" 
                                           <?= ($settings['login_background_type'] ?? 'gradient') === 'solid' ? 'checked' : '' ?>
                                           onchange="toggleBackgroundFields('login')">
                                    <label class="form-check-label" for="login_bg_solid">
                                        Solid Color
                                    </label>
                                </div>
                            </div>
                            
                            <div id="login_gradient_field" class="form-group mb-3" 
                                 style="display: <?= ($settings['login_background_type'] ?? 'gradient') === 'gradient' ? 'block' : 'none' ?>;">
                                <label class="form-label">Gradient Style</label>
                                <select name="login_background_gradient" class="form-select">
                                    <?php 
                                    $currentGradient = $settings['login_background_gradient'] ?? 'purple-pink';
                                    foreach (getGradientOptions() as $key => $label): 
                                    ?>
                                        <option value="<?= $key ?>" <?= $currentGradient === $key ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div id="login_image_field" class="form-group mb-3" 
                                 style="display: <?= ($settings['login_background_type'] ?? 'gradient') === 'image' ? 'block' : 'none' ?>;">
                                <label class="form-label">Background Image</label>
                                <?php if (!empty($settings['login_background_image'])): ?>
                                    <div class="mb-2">
                                        <img src="<?= uploadUrl($settings['login_background_image']) ?>" 
                                             alt="Login BG" style="max-height: 100px; border-radius: 8px;">
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="login_background_image" class="form-control" accept="image/*">
                                <small class="text-muted">
                                    JPG/PNG, max <strong><?= htmlspecialchars($settings['upload_max_size'] ?? '5') ?>MB</strong>. Rekomendasi: 1920x1080px
                                </small>
                            </div>
                            
                            <div id="login_color_field" class="form-group mb-3" 
                                 style="display: <?= ($settings['login_background_type'] ?? 'gradient') === 'solid' ? 'block' : 'none' ?>;">
                                <label class="form-label">Background Color</label>
                                <input type="color" name="login_background_color" class="form-control" 
                                       value="<?= htmlspecialchars($settings['login_background_color'] ?? '#667eea') ?>"
                                       style="height: 50px;">
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label fw-bold">Teks Overlay di Background</label>
                                <textarea class="form-control" name="login_background_overlay_text" rows="3" placeholder="Masukkan teks yang akan muncul di atas background"><?= htmlspecialchars($settings['login_background_overlay_text'] ?? '') ?></textarea>
                                <small class="text-muted">Kosongkan jika tidak ingin menampilkan teks overlay</small>
                            </div>
                        </div>
                    </div>

                    <!-- Theme Warna Halaman Public -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-palette-fill"></i> Theme Warna Halaman Public
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3">
                                <i class="bi bi-info-circle"></i> Warna-warna ini akan diterapkan menggunakan CSS variables pada halaman public.
                            </p>
                                    
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label">Primary Color</label>
                                        <input type="color" name="public_theme_primary_color" class="form-control"
                                                value="<?= htmlspecialchars($settings['public_theme_primary_color'] ?? '#667eea') ?>"
                                               style="height: 50px;">
                                        <small class="text-muted">Warna utama (button, link, header)</small>
                                    </div>
                                </div>
                                            
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label">Secondary Color</label>
                                        <input type="color" name="public_theme_secondary_color" class="form-control"
                                                value="<?= htmlspecialchars($settings['public_theme_secondary_color'] ?? '#764ba2') ?>"
                                               style="height: 50px;">
                                        <small class="text-muted">Warna sekunder (hover, accent)</small>
                                    </div>
                                </div>
                                            
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label">Accent Color</label>
                                        <input type="color" name="public_theme_accent_color" class="form-control"
                                                value="<?= htmlspecialchars($settings['public_theme_accent_color'] ?? '#f093fb') ?>"
                                               style="height: 50px;">
                                        <small class="text-muted">Warna highlight/badge</small>
                                    </div>
                                </div>
                                            
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label">Text Color</label>
                                        <input type="color" name="public_theme_text_color" class="form-control"
                                                value="<?= htmlspecialchars($settings['public_theme_text_color'] ?? '#333333') ?>"
                                               style="height: 50px;">
                                        <small class="text-muted">Warna teks utama</small>
                                    </div>
                                </div>
                                            
                                <div class="col-md-12">
                                    <div class="form-group mb-0">
                                        <label class="form-label">Background Color</label>
                                        <input type="color" name="public_theme_background_color" class="form-control"
                                                value="<?= htmlspecialchars($settings['public_theme_background_color'] ?? '#ffffff') ?>"
                                               style="height: 50px;">
                                        <small class="text-muted">Warna background utama</small>
                                    </div>
                                </div>
                            </div>
                                    
                            <div class="mt-3 pt-3 border-top">
                                <button type="button" class="btn btn-info" onclick="resetThemeColors()">
                                    <i class="bi bi-arrow-clockwise"></i> Reset ke Default
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================
                         SECTION BARU: EMAIL & NOTIFICATION
                    ======================================== -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-envelope"></i> Email & Notification Settings
                            </h5>
                        </div>
                        <div class="card-body">
                            <h6 class="fw-bold mb-3">Email Configuration</h6>

                            <div class="form-group mb-3">
                                <label class="form-label">From Name</label>
                                <input type="text" name="email_from_name" class="form-control" 
                                       value="<?= htmlspecialchars($settings['email_from_name'] ?? 'BTIKP Kalimantan Selatan') ?>">
                                <small class="text-muted">Nama pengirim email</small>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label">From Email Address</label>
                                <input type="email" name="email_from_address" class="form-control" 
                                       value="<?= htmlspecialchars($settings['email_from_address'] ?? 'noreply@btikpkalsel.id') ?>">
                                <small class="text-muted">Alamat email pengirim</small>
                            </div>

                            <div class="form-group mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="email_smtp_enable" id="email_smtp_enable" 
                                           class="form-check-input" 
                                           value="1" <?= ($settings['email_smtp_enable'] ?? '0') == '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold" for="email_smtp_enable">
                                        Enable SMTP
                                    </label>
                                </div>
                                <small class="text-muted">Gunakan SMTP server untuk mengirim email (direkomendasikan)</small>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label">SMTP Host</label>
                                <input type="text" name="email_smtp_host" class="form-control" 
                                       value="<?= htmlspecialchars($settings['email_smtp_host'] ?? '') ?>"
                                       placeholder="smtp.gmail.com">
                                <small class="text-muted">Contoh: smtp.gmail.com, smtp.office365.com</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label">SMTP Port</label>
                                        <input type="number" name="email_smtp_port" class="form-control" 
                                               value="<?= htmlspecialchars($settings['email_smtp_port'] ?? '587') ?>">
                                        <small class="text-muted">587 (TLS) atau 465 (SSL)</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label">Encryption</label>
                                        <select name="email_smtp_encryption" class="form-select">
                                            <option value="tls" <?= ($settings['email_smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                                            <option value="ssl" <?= ($settings['email_smtp_encryption'] ?? 'tls') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label">SMTP Username</label>
                                <input type="text" name="email_smtp_username" class="form-control" 
                                       value="<?= htmlspecialchars($settings['email_smtp_username'] ?? '') ?>"
                                       placeholder="your-email@gmail.com">
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label">SMTP Password</label>
                                <input type="password" name="email_smtp_password" class="form-control" 
                                       value="<?= htmlspecialchars($settings['email_smtp_password'] ?? '') ?>"
                                       placeholder="••••••••">
                                <small class="text-muted">Gunakan App Password untuk Gmail</small>
                            </div>

                            <hr class="my-4">

                            <h6 class="fw-bold mb-3">Notification Settings</h6>

                            <div class="form-group mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="notification_new_contact" id="notification_new_contact" 
                                           class="form-check-input" 
                                           value="1" <?= ($settings['notification_new_contact'] ?? '1') == '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold" for="notification_new_contact">
                                        Notifikasi Pesan Kontak Baru
                                    </label>
                                </div>
                                <small class="text-muted">Kirim notifikasi email saat ada pesan kontak baru</small>
                            </div>

                            <div class="form-group mb-0">
                                <label class="form-label">Email Admin (untuk notifikasi)</label>
                                <input type="email" name="notification_email_admin" class="form-control" 
                                       value="<?= htmlspecialchars($settings['notification_email_admin'] ?? 'admin@btikpkalsel.id') ?>">
                                <small class="text-muted">Email yang akan menerima notifikasi</small>
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
                            <div class="d-flex flex-column flex-sm-row gap-2">
                                <button type="button" class="btn btn-primary btn-lg flex-grow-1" onclick="confirmSaveSettings()">
                                    <i class="bi bi-save"></i> 
                                    <span class="d-none d-sm-inline">Simpan Semua Pengaturan</span>
                                    <span class="d-inline d-sm-none">Simpan</span>
                                </button>
                                <button type="button" class="btn btn-secondary btn-lg" onclick="confirmBack()">
                                    <i class="bi bi-arrow-left"></i> Kembali
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
</div>

<script>
    // Mendefinisikan variabel URL dan Tema sebelum settings.js dimuat
    var ADMIN_URL = '<?= ADMIN_URL ?>';
    var CURRENT_THEME = '<?= $settings['notification_alert_theme'] ?? 'alecto-final-blow' ?>';
</script>

<script src="<?= ADMIN_URL ?>assets/js/settings.js?v=1.2"></script> 
<script>
function toggleBackgroundFields(type) {
    if (type === 'login') {
        const bgType = document.querySelector('input[name="login_background_type"]:checked').value;
        
        document.getElementById('login_gradient_field').style.display = bgType === 'gradient' ? 'block' : 'none';
        document.getElementById('login_image_field').style.display = bgType === 'image' ? 'block' : 'none';
        document.getElementById('login_color_field').style.display = bgType === 'solid' ? 'block' : 'none';
    }
}

// Initialize toggle states on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleBackgroundFields('login');
});

/**
 * CUSTOM NOTIFICATIONS IMPLEMENTATION
 */

// Confirm before save
function confirmSaveSettings() {
    notify.confirm({
        type: 'warning',
        title: 'Simpan Pengaturan?',
        message: 'Anda akan menyimpan semua perubahan pengaturan website. Lanjutkan?',
        confirmText: 'Ya, Simpan',
        cancelText: 'Batal',
        onConfirm: function() {
            // Show loading
            notify.loading('Menyimpan pengaturan...');
            
            // Submit form
            document.getElementById('settingsForm').submit();
        }
    });
}

// Confirm before back
function confirmBack() {
    notify.confirm({
        type: 'info',
        title: 'Kembali ke Dashboard?',
        message: 'Perubahan yang belum disimpan akan hilang. Yakin ingin kembali?',
        confirmText: 'Ya, Kembali',
        cancelText: 'Batal',
        onConfirm: function() {
            window.location.href = '<?= ADMIN_URL ?>';
        }
    });
}

// Preview theme function
function previewTheme() {
    const selectElement = document.getElementById('notification_alert_theme');
    
    if (!selectElement) {
         notify.error('Elemen select tema tidak ditemukan!');
         return;
    }

    const themeName = selectElement.value;
    const themeLabel = selectElement.options[selectElement.selectedIndex].text;
    
    if (typeof BTIKPSettings !== 'undefined') {
        BTIKPSettings.previewTheme(themeName, themeLabel);
    } else {
        console.error('BTIKPSettings not loaded!');
        notify.error('Settings script belum dimuat. Refresh halaman dan coba lagi.');
    }
}

// Checkbox maintenance mode handler
function updateCheckboxValue(checkbox) {
    checkbox.value = checkbox.checked ? '1' : '0';
}

// Reset theme colors to default
function resetThemeColors() {
    notify.confirm({
        type: 'warning',
        title: 'Reset Theme Warna?',
        message: 'Semua warna akan dikembalikan ke pengaturan default. Lanjutkan?',
        confirmText: 'Ya, Reset',
        cancelText: 'Batal',
        onConfirm: function() {
            document.querySelector('input[name="public_theme_primary_color"]').value = '#667eea';
            document.querySelector('input[name="public_theme_secondary_color"]').value = '#764ba2';
            document.querySelector('input[name="public_theme_accent_color"]').value = '#f093fb';
            document.querySelector('input[name="public_theme_text_color"]').value = '#333333';
            document.querySelector('input[name="public_theme_background_color"]').value = '#ffffff';
                        
            notify.success('Theme warna berhasil direset ke default!');
        }
    });
}
</script>

<?php include '../../includes/footer.php'; ?>
