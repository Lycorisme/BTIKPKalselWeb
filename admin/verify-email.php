<?php
/**
 * Email Verification Page
 * Verifikasi email untuk pendaftaran akun baru
 */
session_start();

require_once '../config/config.php';
require_once '../core/Database.php';
require_once '../core/Helper.php';
require_once '../core/Validator.php';
require_once '../core/RateLimiter.php';
require_once '../vendor/autoload.php';
require_once '../core/Email.php';

if (isLoggedIn()) {
    redirect(ADMIN_URL);
}

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

// Load notification theme
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

$alertJS = "";
$email = $_GET['email'] ?? $_SESSION['pending_verification_email'] ?? '';
$showResend = false;
$verified = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::getInstance()->getConnection();
    $email = clean($_POST['email'] ?? '');
    $code = clean($_POST['verification_code'] ?? '');
    $action = $_POST['action'] ?? 'verify';
    
    // Rate limiting
    $rateLimiter = new RateLimiter();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    if ($action === 'resend') {
        // Resend verification code
        $rateCheck = $rateLimiter->check($email, 'resend_code', 3, 5); // Max 3 resends per 5 minutes
        
        if (!$rateCheck['allowed']) {
            $alertJS .= "notify.error('" . addslashes($rateCheck['message']) . "', 5000);";
        } else {
            // Check if pending registration exists
            $stmt = $db->prepare("SELECT * FROM pending_registrations WHERE email = ? AND expires_at > NOW()");
            $stmt->execute([$email]);
            $pending = $stmt->fetch();
            
            if ($pending) {
                // Generate new code
                $emailHandler = new Email();
                $newCode = $emailHandler->generateVerificationCode();
                $expiryMinutes = $emailHandler->getVerificationExpiry();
                $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryMinutes} minutes"));
                
                // Update code in database
                $stmt = $db->prepare("UPDATE pending_registrations SET verification_code = ?, expires_at = ? WHERE email = ?");
                $stmt->execute([$newCode, $expiresAt, $email]);
                
                // Send new verification email
                if ($emailHandler->sendRegistrationVerification($email, $pending['name'], $newCode)) {
                    $rateLimiter->record($email, 'resend_code', 5);
                    $alertJS .= "notify.success('Kode verifikasi baru telah dikirim ke email Anda.', 5000);";
                    $_SESSION['pending_verification_email'] = $email;
                } else {
                    $alertJS .= "notify.error('Gagal mengirim email. Silakan coba lagi.', 5000);";
                }
            } else {
                $alertJS .= "notify.error('Pendaftaran tidak ditemukan atau sudah kadaluarsa. Silakan daftar ulang.', 5000);";
            }
        }
        $showResend = true;
    } else {
        // Verify code
        $rateCheck = $rateLimiter->check($email, 'verify_code', 5, 15); // Max 5 attempts per 15 minutes
        
        if (!$rateCheck['allowed']) {
            $alertJS .= "notify.error('" . addslashes($rateCheck['message']) . "', 5000);";
        } else {
            // Validate input
            $validator = new Validator($_POST);
            $validator->required('email', 'Email');
            $validator->required('verification_code', 'Kode Verifikasi');
            
            if ($validator->passes()) {
                try {
                    // Check pending registration
                    $stmt = $db->prepare("SELECT * FROM pending_registrations WHERE email = ? AND verification_code = ? AND expires_at > NOW()");
                    $stmt->execute([$email, $code]);
                    $pending = $stmt->fetch();
                    
                    if ($pending) {
                        // Move to users table
                        $stmt = $db->prepare("INSERT INTO users (name, email, password, role, is_active, created_at) VALUES (?, ?, ?, 'editor', 3, NOW())");
                        $stmt->execute([$pending['name'], $pending['email'], $pending['password']]);
                        
                        // Delete pending registration
                        $stmt = $db->prepare("DELETE FROM pending_registrations WHERE email = ?");
                        $stmt->execute([$email]);
                        
                        // Send welcome email
                        $emailHandler = new Email();
                        $emailHandler->sendWelcomeEmail($email, $pending['name']);
                        
                        // Clear session
                        unset($_SESSION['pending_verification_email']);
                        
                        $verified = true;
                        $alertJS .= "
                        notify.success('Email berhasil diverifikasi!', 3000);
                        setTimeout(function() {
                            notify.alert({
                                type: 'success',
                                title: 'Verifikasi Berhasil! 🎉',
                                message: 'Akun Anda telah diverifikasi dan <b>menunggu persetujuan admin</b>.<br>Anda akan dapat login setelah admin menyetujui akun Anda.',
                                confirmText: 'Ke Halaman Login',
                                onConfirm: function() { window.location.href = '" . ADMIN_URL . "login.php'; }
                            });
                        }, 500);
                        ";
                    } else {
                        $rateLimiter->record($email, 'verify_code', 15);
                        $alertJS .= "notify.error('Kode verifikasi tidak valid atau sudah kadaluarsa.', 5000);";
                        $showResend = true;
                    }
                } catch (PDOException $e) {
                    error_log($e->getMessage());
                    $alertJS .= "notify.error('Terjadi kesalahan sistem.', 5000);";
                }
            } else {
                $rateLimiter->record($email, 'verify_code', 15);
                foreach (['email', 'verification_code'] as $field) {
                    if ($validator->getError($field)) {
                        $alertJS .= "notify.warning('" . addslashes($validator->getError($field)) . "', 3000);";
                    }
                }
            }
        }
    }
}

// Check if there's pending verification
if (!empty($email) && !$verified) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM pending_registrations WHERE email = ? AND expires_at > NOW()");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $showResend = true;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Verifikasi Email - <?= htmlspecialchars($siteName) ?></title>
    <?php if ($siteFavicon): ?>
    <link rel="icon" type="image/png" href="<?= uploadUrl($siteFavicon) ?>" />
    <?php endif; ?>

    <link rel="stylesheet" href="<?= ADMIN_URL ?>assets/compiled/css/app.css" />
    <link rel="stylesheet" href="<?= ADMIN_URL ?>assets/compiled/css/app-dark.css" />
    <link rel="stylesheet" href="<?= ADMIN_URL ?>assets/compiled/css/auth.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="<?= ADMIN_URL ?>assets/css/<?= $notificationCssFile ?>?v=<?= time() ?>" />

    <style>
        .verification-code-input {
            font-size: 2rem;
            letter-spacing: 0.5rem;
            text-align: center;
            font-weight: 700;
            font-family: 'Consolas', 'Monaco', monospace;
        }
        .email-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.9rem;
            display: inline-block;
            margin-bottom: 1.5rem;
        }
        .expiry-notice {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: #856404;
        }
        .resend-section {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(0,0,0,0.1);
        }
    </style>
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
                            <img src="<?= uploadUrl($siteLogo) ?>" alt="Logo" style="height:50px" class="me-2" />
                            <?php endif; ?>
                            <?php if ($showLogoText == '1'): ?>
                            <span style="font-size: 1.5rem; font-weight: 600; color: var(--bs-primary)">
                                <?= htmlspecialchars($siteLogoText) ?>
                            </span>
                            <?php endif; ?>
                        </a>
                    </div>

                    <h1 class="auth-title">Verifikasi Email</h1>
                    <p class="auth-subtitle mb-4">Masukkan kode verifikasi yang telah dikirim ke email Anda.</p>

                    <?php if (!empty($email)): ?>
                    <div class="email-badge">
                        <i class="bi bi-envelope-check me-2"></i><?= htmlspecialchars($email) ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!$verified): ?>
                    <form method="POST" novalidate autocomplete="off">
                        <input type="hidden" name="action" value="verify">
                        
                        <div class="form-group position-relative has-icon-left mb-4">
                            <input type="email" name="email"
                                class="form-control form-control-xl"
                                placeholder="Email"
                                value="<?= htmlspecialchars($email) ?>"
                                <?= !empty($email) ? 'readonly' : '' ?>
                                required autocomplete="email" />
                            <div class="form-control-icon">
                                <i class="bi bi-envelope"></i>
                            </div>
                        </div>

                        <div class="form-group position-relative has-icon-left mb-4">
                            <input type="text" name="verification_code"
                                class="form-control form-control-xl verification-code-input"
                                placeholder="000000"
                                maxlength="10"
                                required autofocus
                                autocomplete="one-time-code"
                                inputmode="numeric"
                                pattern="[0-9]*" />
                            <div class="form-control-icon">
                                <i class="bi bi-shield-lock"></i>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block btn-lg shadow-lg mt-3">
                            <i class="bi bi-check2-circle"></i> Verifikasi
                        </button>
                    </form>

                    <div class="expiry-notice">
                        <i class="bi bi-clock me-2"></i>
                        <strong>Perhatian:</strong> Kode verifikasi berlaku selama <?= getSetting('email_verification_expiry_minutes', '15') ?> menit.
                    </div>

                    <?php if ($showResend && !empty($email)): ?>
                    <div class="resend-section">
                        <p class="text-gray-600 mb-3">Tidak menerima kode?</p>
                        <form method="POST">
                            <input type="hidden" name="action" value="resend">
                            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                            <button type="submit" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-repeat"></i> Kirim Ulang Kode
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>

                    <div class="text-center mt-5 text-lg fs-4">
                        <p class="text-gray-600">
                            Sudah punya akun?
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
                <div id="auth-right"
                    style="<?= generateBackgroundStyle($bgType, $bgImage, $bgGradient, $bgColor) ?>">
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
