<?php
session_start();

require_once '../config/config.php';
require_once '../core/Database.php';
require_once '../core/Helper.php';
require_once '../core/Validator.php';
require_once '../core/RateLimiter.php';
require_once '../vendor/autoload.php';
require_once '../core/Email.php';

$siteName     = getSetting('site_name', 'BTIKP Kalimantan Selatan');
$siteLogo     = getSetting('site_logo');
$siteLogoText = getSetting('site_logo_text', 'BTIKP KALSEL');
$showLogoText = getSetting('site_logo_show_text', '1');
$siteFavicon  = getSetting('site_favicon');
$bgType       = getSetting('login_background_type', 'gradient');
$bgImage      = getSetting('login_background_image');
$bgGradient   = getSetting('login_background_gradient', 'purple-pink');
$bgColor      = getSetting('login_background_color', '#667eea');
$bgOverlayText = trim(getSetting('login_background_overlay_text', ''));

// ==================================================================
// == Logika untuk memuat file Notifikasi/Alert dinamis
// ==================================================================

$currentTheme = getSetting('notification_alert_theme', 'alecto-final-blow');

switch ($currentTheme) {
    case 'an-eye-for-an-eye':
        $notificationCssFile = 'notifications_an_eye_for_an_eye.css';
        $notificationJsFile = 'notifications_an_eye_for_an_eye.js';
        break;
    case 'throne-of-ruin':
        $notificationCssFile = 'notifications_throne.css';
        $notificationJsFile = 'notifications_throne.js';
        break;
    case 'hoki-crossbow-of-tang':
        $notificationCssFile = 'notifications_crossbow.css';
        $notificationJsFile = 'notifications_crossbow.js';
        break;
    case 'death-sonata':
        $notificationCssFile = 'notifications_death_sonata.css';
        $notificationJsFile = 'notifications_death_sonata.js';
        break;
    case 'alecto-final-blow':
    default:
        $notificationCssFile = 'notifications.css';
        $notificationJsFile = 'notifications.js';
        break;
}

$validator = null;
$alertJS = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean($_POST['email'] ?? '');

    // ===== RATE LIMITING - START =====
    $rateLimiter = new RateLimiter();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // Check rate limit: max 3 attempts per 60 minutes
    // Gunakan EMAIL sebagai identifier jika ada, jika tidak gunakan IP
    $identifier = !empty($email) ? $email : $ipAddress;
    $rateCheck = $rateLimiter->check($identifier, 'password_reset', 3, 60);

    if (!$rateCheck['allowed']) {
        // User is blocked
        $alertJS .= "notify.error('" . addslashes($rateCheck['message']) . "', 5000);";
    } else {
        // Rate limit OK, proceed with validation
        
        $validator = new Validator($_POST);
        $validator->required('email', 'Email');
        $validator->email('email', 'Email');

        if ($validator->passes()) {
            try {
                $db = Database::getInstance()->getConnection();

                // Cek user berdasarkan email
                $stmt = $db->prepare("SELECT id, name FROM users WHERE email = ? AND deleted_at IS NULL AND is_active = 1 LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user) {
                    // Initialize email handler
                    $emailHandler = new Email();
                    
                    // Generate verification code (6 digit)
                    $code = $emailHandler->generateVerificationCode();
                    $expiryMinutes = $emailHandler->getVerificationExpiry();
                    $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryMinutes} minutes"));

                    // Delete old verification codes for this email
                    $del = $db->prepare("DELETE FROM verification_codes WHERE email = ? AND type = 'password_reset'");
                    $del->execute([$email]);

                    // Insert new verification code
                    $stmt = $db->prepare("INSERT INTO verification_codes (email, code, type, user_id, expires_at, created_at) VALUES (?, ?, 'password_reset', ?, ?, NOW())");
                    $stmt->execute([$email, $code, $user['id'], $expiresAt]);

                    // Send verification code via email
                    if ($emailHandler->sendPasswordResetCode($email, $user['name'], $code)) {
                        // Store email in session for reset page
                        $_SESSION['password_reset_email'] = $email;
                        
                        // Notifikasi sukses
                        $alertJS .= "
                        notify.success('Kode verifikasi telah dikirim ke email Anda!', 5000);
                        setTimeout(function() {
                            notify.alert({ 
                                type: 'info', 
                                title: 'Kode Reset Password Terkirim', 
                                message: 'Kami telah mengirim kode verifikasi ke <b>" . addslashes($email) . "</b>.<br>Silakan cek inbox atau folder spam Anda.',
                                confirmText: 'Masukkan Kode',
                                onConfirm: function() { window.location.href = '" . ADMIN_URL . "reset-password.php?email=" . urlencode($email) . "'; }
                            });
                        }, 600);
                        ";
                    } else {
                        $alertJS .= "notify.error('Gagal mengirim email. Silakan coba lagi.', 5000);";
                    }

                } else {
                    // User not found - Record failed attempt
                    $rateLimiter->record($identifier, 'password_reset', 60);
                    
                    $alertJS .= "notify.error('Email tidak ditemukan atau akun belum aktif.');";
                }
            } catch (PDOException $e) {
                error_log($e->getMessage());
                // Record failed attempt
                $rateLimiter->record($identifier, 'password_reset', 60);
                $alertJS .= "notify.error('Terjadi kesalahan sistem.');";
            }
        } else {
            // Invalid form input
            $rateLimiter->record($identifier, 'password_reset', 60);

            if ($validator->getError('email'))
                $alertJS .= "notify.warning('".addslashes($validator->getError('email'))."');";
            if ($validator->getError('general'))
                $alertJS .= "notify.error('".addslashes($validator->getError('general'))."');";
        }
    } // End rate limiting check
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - <?= htmlspecialchars($siteName) ?></title>

    <?php if ($siteFavicon): ?>
        <link rel="icon" type="image/png" href="<?= uploadUrl($siteFavicon) ?>">
    <?php endif; ?>

    <link rel="stylesheet" href="<?= ADMIN_URL ?>assets/compiled/css/app.css">
    <link rel="stylesheet" href="<?= ADMIN_URL ?>assets/compiled/css/app-dark.css">
    <link rel="stylesheet" href="<?= ADMIN_URL ?>assets/compiled/css/auth.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <link rel="stylesheet" href="<?= ADMIN_URL ?>assets/css/<?= $notificationCssFile ?>?v=<?= time() ?>">
</head>
<body>
    <script src="<?= ADMIN_URL ?>assets/static/js/initTheme.js"></script>

    <div id="auth">
        <div class="row h-100">
            <div class="col-lg-5 col-12">
                <div id="auth-left">
                    <div class="auth-logo mb-4">
                        <a href="<?= BASE_URL ?>" class="d-flex align-items-center">
                            <?php if ($siteLogo): ?>
                                <img src="<?= uploadUrl($siteLogo) ?>" alt="Logo" style="height: 50px;" class="me-2">
                            <?php endif; ?>
                            <?php if ($showLogoText == '1'): ?>
                                <span style="font-size: 1.5rem; font-weight: 600; color: var(--bs-primary);">
                                    <?= htmlspecialchars($siteLogoText) ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </div>

                    <h1 class="auth-title">Lupa Password.</h1>
                    <p class="auth-subtitle mb-5">Masukkan email Anda untuk reset password.</p>

                    <form method="POST" novalidate autocomplete="off">
                        <div class="form-group position-relative has-icon-left mb-4">
                            <input type="email" name="email"
                                class="form-control form-control-xl <?= $validator && $validator->getError('email') ? 'is-invalid' : '' ?>"
                                placeholder="Email"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                required autocomplete="email" autofocus>
                            <div class="form-control-icon">
                                <i class="bi bi-envelope"></i>
                            </div>
                            <?php if ($validator && $validator->getError('email')): ?>
                                <div class="invalid-feedback"><?= $validator->getError('email') ?></div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block btn-lg shadow-lg mt-3">
                            <i class="bi bi-envelope"></i> Kirim Link Reset
                        </button>
                    </form>

                    <div class="text-center mt-5 text-lg fs-4">
                        <p class="text-gray-600">
                            Ingat password? 
                            <a href="<?= ADMIN_URL ?>login.php" class="font-bold">Login di sini</a>
                        </p>
                        <p>
                            <a href="<?= BASE_URL ?>" class="font-bold">
                                <i class="bi bi-arrow-left"></i> Kembali ke Website
                            </a>
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-lg-7 d-none d-lg-block">
                <div id="auth-right" style="<?= generateBackgroundStyle($bgType, $bgImage, $bgGradient, $bgColor) ?>">
                    <?php if ($bgType === 'image' && !empty($bgOverlayText)): ?>
                        <div class="overlay-text"><?= nl2br(htmlspecialchars($bgOverlayText)) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= ADMIN_URL ?>assets/static/js/components/dark.js"></script>
    
    <script src="<?= ADMIN_URL ?>assets/js/<?= $notificationJsFile ?>?v=<?= time() ?>"></script>
    
    <?php if (!empty($alertJS)): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?= $alertJS ?>
    });
    </script>
    <?php endif; ?>
</body>
</html>