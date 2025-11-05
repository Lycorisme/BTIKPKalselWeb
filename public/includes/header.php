<?php
/**
 * Public Header Template - With Dynamic Logo Combo
 */
if (!defined('BASE_URL')) {
    require_once dirname(__DIR__, 2) . '/config/config.php';
}

// Get settings
$siteName = getSetting('site_name', 'BTIKP Kalimantan Selatan');
$siteTagline = getSetting('site_tagline', 'Balai Teknologi Informasi dan Komunikasi Pendidikan');
$siteDescription = getSetting('site_description', 'Portal resmi BTIKP Provinsi Kalimantan Selatan');
$siteKeywords = getSetting('site_keywords', 'btikp, kalsel, pendidikan, teknologi');
$siteLogo = getSetting('site_logo');
$siteFavicon = getSetting('site_favicon');
$siteLogoText = getSetting('site_logo_text', 'BTIKP KALSEL');
$showLogoText = getSetting('site_logo_show_text', '1');

// Page specific meta
$metaDescription = $metaDescription ?? $siteDescription;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Portal' ?> - <?= $siteName ?></title>
    <meta name="description" content="<?= $metaDescription ?>">
    <meta name="keywords" content="<?= $siteKeywords ?>">
    
    <!-- Favicon -->
    <?php if ($siteFavicon): ?>
        <link rel="icon" type="image/png" href="<?= uploadUrl($siteFavicon) ?>">
    <?php endif; ?>
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?= $pageTitle ?? 'Portal' ?> - <?= $siteName ?>">
    <meta property="og:description" content="<?= $metaDescription ?>">
    <meta property="og:type" content="website">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    
    <style>
    /* Logo Combo Styling */
    .navbar-brand {
        font-size: 1.2rem;
        transition: all 0.3s ease;
    }
    
    .navbar-brand img {
        transition: transform 0.3s ease;
    }
    
    .navbar-brand:hover img {
        transform: scale(1.05);
    }
    
    .logo-text {
        font-weight: 600;
        color: #0d6efd;
        letter-spacing: -0.5px;
        line-height: 1.2;
    }
    
    /* Responsive Logo */
    @media (max-width: 576px) {
        .navbar-brand {
            font-size: 1rem;
        }
        
        .navbar-brand img {
            height: 32px !important;
        }
        
        .logo-text {
            font-size: 0.9rem;
        }
    }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar bg-primary text-white py-2">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <small>
                        <i class="bi bi-telephone"></i> <?= getSetting('contact_phone', '(0511) 1234567') ?> | 
                        <i class="bi bi-envelope"></i> <?= getSetting('contact_email', 'info@btikp-kalsel.id') ?>
                    </small>
                </div>
                <div class="col-md-6 text-end">
                    <small>
                        <?php if ($fbUrl = getSetting('social_facebook')): ?>
                            <a href="<?= $fbUrl ?>" class="text-white me-2" target="_blank"><i class="bi bi-facebook"></i></a>
                        <?php endif; ?>
                        <?php if ($igUrl = getSetting('social_instagram')): ?>
                            <a href="<?= $igUrl ?>" class="text-white me-2" target="_blank"><i class="bi bi-instagram"></i></a>
                        <?php endif; ?>
                        <?php if ($ytUrl = getSetting('social_youtube')): ?>
                            <a href="<?= $ytUrl ?>" class="text-white me-2" target="_blank"><i class="bi bi-youtube"></i></a>
                        <?php endif; ?>
                        <?php if ($twUrl = getSetting('social_twitter')): ?>
                            <a href="<?= $twUrl ?>" class="text-white" target="_blank"><i class="bi bi-twitter"></i></a>
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <!-- Logo Combo: Image + Text -->
            <a class="navbar-brand d-flex align-items-center fw-bold" href="<?= BASE_URL ?>">
                <?php if ($siteLogo): ?>
                    <img src="<?= uploadUrl($siteLogo) ?>" alt="<?= $siteName ?>" height="40" class="me-2">
                <?php endif; ?>
                
                <?php if ($showLogoText == '1'): ?>
                    <span class="logo-text">
                        <?= htmlspecialchars($siteLogoText) ?>
                    </span>
                <?php endif; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>">Beranda</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            Profil
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>pages/about.php">Tentang Kami</a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>pages/visi-misi.php">Visi & Misi</a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>pages/struktur.php">Struktur Organisasi</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>news/">Berita</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>services/">Layanan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>schools/">Direktori Sekolah</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>contact.php">Kontak</a>
                    </li>
                </ul>
                
                <!-- Search Button -->
                <button class="btn btn-outline-primary ms-2" data-bs-toggle="modal" data-bs-target="#searchModal">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </div>
    </nav>
    
    <!-- Search Modal -->
    <div class="modal fade" id="searchModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pencarian</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="<?= BASE_URL ?>search.php" method="GET">
                        <div class="input-group">
                            <input type="text" name="q" class="form-control form-control-lg" 
                                   placeholder="Cari berita, artikel..." required>
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-search"></i> Cari
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <main>
