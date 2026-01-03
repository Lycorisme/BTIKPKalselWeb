<?php
/**
 * Reset Password Page
 * Verifikasi kode dan reset password
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
$email = $_GET['email'] ?? $_SESSION['password_reset_email'] ?? '';
$showResend = false;
$codeVerified = false;
$resetComplete = false;

// Check if we're in verification step or password step
$step = $_GET['step'] ?? 'verify'; // 'verify' or 'reset'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::getInstance()->getConnection();
    $email = clean($_POST['email'] ?? '');
    $action = $_POST['action'] ?? 'verify';
    
    // Rate limiting
    $rateLimiter = new RateLimiter();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    if ($action === 'resend') {
        // Resend verification code
        $rateCheck = $rateLimiter->check($email, 'resend_reset_code', 3, 5); // Max 3 resends per 5 minutes
        
        if (!$rateCheck['allowed']) {
            $alertJS .= "notify.error('" . addslashes($rateCheck['message']) . "', 5000);";
        } else {
            // Check if user exists
            $stmt = $db->prepare("SELECT id, name FROM users WHERE email = ? AND deleted_at IS NULL AND is_active = 1 LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Generate new code
                $emailHandler = new Email();
                $newCode = $emailHandler->generateVerificationCode();
                $expiryMinutes = $emailHandler->getVerificationExpiry();
                $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryMinutes} minutes"));
                
                // Delete old codes
                $del = $db->prepare("DELETE FROM verification_codes WHERE email = ? AND type = 'password_reset'");
                $del->execute([$email]);
                
                // Insert new code
                $stmt = $db->prepare("INSERT INTO verification_codes (email, code, type, user_id, expires_at, created_at) VALUES (?, ?, 'password_reset', ?, ?, NOW())");
                $stmt->execute([$email, $newCode, $user['id'], $expiresAt]);
                
                // Send new verification email
                if ($emailHandler->sendPasswordResetCode($email, $user['name'], $newCode)) {
                    $rateLimiter->record($email, 'resend_reset_code', 5);
                    $alertJS .= "notify.success('Kode verifikasi baru telah dikirim ke email Anda.', 5000);";
                    $_SESSION['password_reset_email'] = $email;
                } else {
                    $alertJS .= "notify.error('Gagal mengirim email. Silakan coba lagi.', 5000);";
                }
            } else {
                $alertJS .= "notify.error('Email tidak ditemukan.', 5000);";
            }
        }
        $showResend = true;
        
    } elseif ($action === 'verify') {
        // Verify code only
        $code = clean($_POST['verification_code'] ?? '');
        
        $rateCheck = $rateLimiter->check($email, 'verify_reset_code', 5, 15);
        
        if (!$rateCheck['allowed']) {
            $alertJS .= "notify.error('" . addslashes($rateCheck['message']) . "', 5000);";
        } else {
            $validator = new Validator($_POST);
            $validator->required('email', 'Email');
            $validator->required('verification_code', 'Kode Verifikasi');
            
            if ($validator->passes()) {
                try {
                    // Check verification code
                    $stmt = $db->prepare("SELECT vc.*, u.name FROM verification_codes vc 
                                          JOIN users u ON vc.user_id = u.id 
                                          WHERE vc.email = ? AND vc.code = ? AND vc.type = 'password_reset' 
                                          AND vc.expires_at > NOW() AND vc.used_at IS NULL");
                    $stmt->execute([$email, $code]);
                    $verified = $stmt->fetch();
                    
                    if ($verified) {
                        // Store verification in session
                        $_SESSION['password_reset_verified'] = true;
                        $_SESSION['password_reset_email'] = $email;
                        $_SESSION['password_reset_code'] = $code;
                        
                        $codeVerified = true;
                        $step = 'reset';
                        $alertJS .= "notify.success('Kode verifikasi valid! Silakan buat password baru.', 3000);";
                    } else {
                        $rateLimiter->record($email, 'verify_reset_code', 15);
                        $alertJS .= "notify.error('Kode verifikasi tidak valid atau sudah kadaluarsa.', 5000);";
                        $showResend = true;
                    }
                } catch (PDOException $e) {
                    error_log($e->getMessage());
                    $alertJS .= "notify.error('Terjadi kesalahan sistem.', 5000);";
                }
            } else {
                $rateLimiter->record($email, 'verify_reset_code', 15);
                foreach (['email', 'verification_code'] as $field) {
                    if ($validator->getError($field)) {
                        $alertJS .= "notify.warning('" . addslashes($validator->getError($field)) . "', 3000);";
                    }
                }
            }
        }
        
    } elseif ($action === 'reset') {
        // Reset password
        $newPassword = $_POST['password'] ?? '';
        $confirmPassword = $_POST['password_confirm'] ?? '';
        
        // Check if verified
        if (empty($_SESSION['password_reset_verified']) || $_SESSION['password_reset_email'] !== $email) {
            $alertJS .= "notify.error('Sesi tidak valid. Silakan mulai ulang proses reset password.', 5000);";
        } else {
            $validator = new Validator($_POST);
            $validator->required('password', 'Password');
            $validator->minLength('password', 6, 'Password minimal 6 karakter');
            $validator->required('password_confirm', 'Konfirmasi Password');
            $validator->match('password_confirm', 'password', 'Konfirmasi Password tidak cocok');
            
            if ($validator->passes()) {
                try {
                    $sessionCode = $_SESSION['password_reset_code'] ?? '';
                    
                    // Double check verification code is still valid
                    $stmt = $db->prepare("SELECT * FROM verification_codes 
                                          WHERE email = ? AND code = ? AND type = 'password_reset' 
                                          AND expires_at > NOW() AND used_at IS NULL");
                    $stmt->execute([$email, $sessionCode]);
                    
                    if ($stmt->fetch()) {
                        // Update password
                        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE email = ? AND deleted_at IS NULL");
                        $stmt->execute([$passwordHash, $email]);
                        
                        // Mark code as used
                        $stmt = $db->prepare("UPDATE verification_codes SET used_at = NOW() WHERE email = ? AND code = ? AND type = 'password_reset'");
                        $stmt->execute([$email, $sessionCode]);
                        
                        // Clear session
                        unset($_SESSION['password_reset_verified']);
                        unset($_SESSION['password_reset_email']);
                        unset($_SESSION['password_reset_code']);
                        
                        $resetComplete = true;
                        $alertJS .= "
                        notify.success('Password berhasil diubah!', 3000);
                        setTimeout(function() {
                            notify.alert({
                                type: 'success',
                                title: 'Reset Password Berhasil! 🎉',
                                message: 'Password Anda telah berhasil diubah.<br>Silakan login dengan password baru Anda.',
                                confirmText: 'Login Sekarang',
                                onConfirm: function() { window.location.href = '" . ADMIN_URL . "login.php'; }
                            });
                        }, 500);
                        ";
                    } else {
                        $alertJS .= "notify.error('Kode verifikasi tidak valid atau sudah digunakan. Silakan mulai ulang.', 5000);";
                        // Clear session
                        unset($_SESSION['password_reset_verified']);
                        unset($_SESSION['password_reset_email']);
                        unset($_SESSION['password_reset_code']);
                    }
                } catch (PDOException $e) {
                    error_log($e->getMessage());
                    $alertJS .= "notify.error('Terjadi kesalahan sistem.', 5000);";
                }
            } else {
                foreach (['password', 'password_confirm'] as $field) {
                    if ($validator->getError($field)) {
                        $alertJS .= "notify.warning('" . addslashes($validator->getError($field)) . "', 3000);";
                    }
                }
                $step = 'reset';
                $codeVerified = true;
            }
        }
    }
}

// Check if already verified in session
if (!empty($_SESSION['password_reset_verified']) && $_SESSION['password_reset_email'] === $email) {
    $codeVerified = true;
    $step = 'reset';
}

// Check if there's pending reset code
if (!empty($email) && !$codeVerified && !$resetComplete) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM verification_codes WHERE email = ? AND type = 'password_reset' AND expires_at > NOW() AND used_at IS NULL");
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
    <title>Reset Password - <?= htmlspecialchars($siteName) ?></title>
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
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .step {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6c757d;
        }
        .step.active {
            color: var(--bs-primary);
            font-weight: 600;
        }
        .step.completed {
            color: #28a745;
        }
        .step-number {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .step.active .step-number {
            background: var(--bs-primary);
            color: white;
        }
        .step.completed .step-number {
            background: #28a745;
            color: white;
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

                    <h1 class="auth-title">Reset Password</h1>
                    
                    <?php if (!$resetComplete): ?>
                    <!-- Step Indicator -->
                    <div class="step-indicator">
                        <div class="step <?= $step === 'verify' ? 'active' : ($codeVerified ? 'completed' : '') ?>">
                            <span class="step-number"><?= $codeVerified ? '✓' : '1' ?></span>
                            <span>Verifikasi</span>
                        </div>
                        <i class="bi bi-arrow-right"></i>
                        <div class="step <?= $step === 'reset' ? 'active' : '' ?>">
                            <span class="step-number">2</span>
                            <span>Password Baru</span>
                        </div>
                    </div>

                    <?php if (!empty($email)): ?>
                    <div class="email-badge">
                        <i class="bi bi-envelope-check me-2"></i><?= htmlspecialchars($email) ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($step === 'verify' && !$codeVerified): ?>
                    <!-- STEP 1: Verify Code -->
                    <p class="auth-subtitle mb-4">Masukkan kode verifikasi yang telah dikirim ke email Anda.</p>

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
                            <i class="bi bi-check2-circle"></i> Verifikasi Kode
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

                    <?php elseif ($step === 'reset' || $codeVerified): ?>
                    <!-- STEP 2: New Password -->
                    <p class="auth-subtitle mb-4">Buat password baru untuk akun Anda.</p>

                    <form method="POST" novalidate autocomplete="off">
                        <input type="hidden" name="action" value="reset">
                        <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">

                        <div class="form-group position-relative has-icon-left mb-4">
                            <input type="password" name="password"
                                class="form-control form-control-xl"
                                placeholder="Password Baru"
                                required autofocus
                                autocomplete="new-password" />
                            <div class="form-control-icon">
                                <i class="bi bi-lock"></i>
                            </div>
                        </div>

                        <div class="form-group position-relative has-icon-left mb-4">
                            <input type="password" name="password_confirm"
                                class="form-control form-control-xl"
                                placeholder="Konfirmasi Password"
                                required
                                autocomplete="new-password" />
                            <div class="form-control-icon">
                                <i class="bi bi-lock-fill"></i>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block btn-lg shadow-lg mt-3">
                            <i class="bi bi-check2-all"></i> Ubah Password
                        </button>
                    </form>
                    <?php endif; ?>
                    <?php endif; ?>

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
