<?php
/**
 * Admin Login Page
 * With Dynamic Logo & Favicon
 */

session_start();

require_once '../config/config.php';
require_once '../core/Database.php';
require_once '../core/Helper.php';
require_once '../core/Validator.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(ADMIN_URL);
}

// Get settings
$siteName = getSetting('site_name', 'BTIKP Kalimantan Selatan');
$siteLogo = getSetting('site_logo');
$siteLogoText = getSetting('site_logo_text', 'BTIKP KALSEL');
$showLogoText = getSetting('site_logo_show_text', '1');
$siteFavicon = getSetting('site_favicon');

$validator = null;

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $validator = new Validator($_POST);
    $validator->required('email', 'Email');
    $validator->required('password', 'Password');
    $validator->email('email', 'Email');
    
    if ($validator->passes()) {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Check user
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_photo'] = $user['photo']; // ✅ Tambahkan ini

                // Update last login
                $stmt = $db->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);

                // Log activity
                logActivity('LOGIN', 'User login ke sistem', 'users', $user['id']);

                setAlert('success', 'Selamat datang, ' . $user['name'] . '!');
                redirect(ADMIN_URL);
            } else {
                $validator->addError('general', 'Email atau password salah');
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $validator->addError('general', 'Terjadi kesalahan sistem');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= $siteName ?></title>
    
    <!-- Favicon (Dynamic) -->
    <?php if ($siteFavicon): ?>
        <link rel="icon" type="image/png" href="<?= uploadUrl($siteFavicon) ?>">
    <?php endif; ?>
    
    <!-- Mazer CSS -->
    <link rel="stylesheet" href="<?= ADMIN_URL ?>assets/compiled/css/app.css">
    <link rel="stylesheet" href="<?= ADMIN_URL ?>assets/compiled/css/app-dark.css">
    <link rel="stylesheet" href="<?= ADMIN_URL ?>assets/compiled/css/auth.css">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
    <script src="<?= ADMIN_URL ?>assets/static/js/initTheme.js"></script>
    
    <div id="auth">
        <div class="row h-100">
            <div class="col-lg-5 col-12">
                <div id="auth-left">
                    <!-- Logo (Dynamic) -->
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
                    
                    <h1 class="auth-title">Log in.</h1>
                    <p class="auth-subtitle mb-5">Masukkan email dan password Anda untuk login ke sistem.</p>

                    <?php if ($validator && $validator->getError('general')): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="bi bi-x-circle"></i> <?= $validator->getError('general') ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group position-relative has-icon-left mb-4">
                            <input type="email" name="email" 
                                   class="form-control form-control-xl <?= $validator && $validator->getError('email') ? 'is-invalid' : '' ?>" 
                                   placeholder="Email" 
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                                   required autofocus>
                            <div class="form-control-icon">
                                <i class="bi bi-person"></i>
                            </div>
                            <?php if ($validator && $validator->getError('email')): ?>
                                <div class="invalid-feedback"><?= $validator->getError('email') ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group position-relative has-icon-left mb-4">
                            <input type="password" name="password" 
                                   class="form-control form-control-xl <?= $validator && $validator->getError('password') ? 'is-invalid' : '' ?>" 
                                   placeholder="Password" 
                                   required>
                            <div class="form-control-icon">
                                <i class="bi bi-shield-lock"></i>
                            </div>
                            <?php if ($validator && $validator->getError('password')): ?>
                                <div class="invalid-feedback"><?= $validator->getError('password') ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-check form-check-lg d-flex align-items-end mb-4">
                            <input class="form-check-input me-2" type="checkbox" name="remember" id="flexCheckDefault">
                            <label class="form-check-label text-gray-600" for="flexCheckDefault">
                                Ingat saya
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block btn-lg shadow-lg mt-3">
                            <i class="bi bi-box-arrow-in-right"></i> Log in
                        </button>
                    </form>
                    
                    <div class="text-center mt-5 text-lg fs-4">
                        <p class="text-gray-600">
                            Lupa password? 
                            <a href="<?= ADMIN_URL ?>forgot-password.php" class="font-bold">Reset Password</a>
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
                <div id="auth-right" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="d-flex align-items-center justify-content-center h-100">
                        <div class="text-center text-white p-5">
                            <h1 class="display-4 fw-bold mb-3"><?= $siteName ?></h1>
                            <p class="lead"><?= getSetting('site_tagline', 'Portal Administrasi') ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="<?= ADMIN_URL ?>assets/static/js/components/dark.js"></script>
</body>
</html>
