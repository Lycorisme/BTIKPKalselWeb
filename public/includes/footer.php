    </main>
    
    <!-- Footer -->
    <footer class="bg-dark text-white pt-5 pb-3">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 class="fw-bold mb-3"><?= getSetting('site_name', 'BTIKP Kalimantan Selatan') ?></h5>
                    <p class="small">
                        <?= getSetting('site_tagline', 'Balai Teknologi Informasi dan Komunikasi Pendidikan Provinsi Kalimantan Selatan') ?>
                    </p>
                    <div class="social-links">
                        <?php if ($fbUrl = getSetting('social_facebook')): ?>
                            <a href="<?= $fbUrl ?>" class="text-white me-3" target="_blank">
                                <i class="bi bi-facebook fs-4"></i>
                            </a>
                        <?php endif; ?>
                        <?php if ($igUrl = getSetting('social_instagram')): ?>
                            <a href="<?= $igUrl ?>" class="text-white me-3" target="_blank">
                                <i class="bi bi-instagram fs-4"></i>
                            </a>
                        <?php endif; ?>
                        <?php if ($ytUrl = getSetting('social_youtube')): ?>
                            <a href="<?= $ytUrl ?>" class="text-white me-3" target="_blank">
                                <i class="bi bi-youtube fs-4"></i>
                            </a>
                        <?php endif; ?>
                        <?php if ($twUrl = getSetting('social_twitter')): ?>
                            <a href="<?= $twUrl ?>" class="text-white" target="_blank">
                                <i class="bi bi-twitter fs-4"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <h5 class="fw-bold mb-3">Menu Cepat</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="<?= BASE_URL ?>" class="text-white text-decoration-none">Beranda</a></li>
                        <li class="mb-2"><a href="<?= BASE_URL ?>pages/about.php" class="text-white text-decoration-none">Tentang Kami</a></li>
                        <li class="mb-2"><a href="<?= BASE_URL ?>news/" class="text-white text-decoration-none">Berita</a></li>
                        <li class="mb-2"><a href="<?= BASE_URL ?>services/" class="text-white text-decoration-none">Layanan</a></li>
                        <li class="mb-2"><a href="<?= BASE_URL ?>contact.php" class="text-white text-decoration-none">Kontak</a></li>
                    </ul>
                </div>
                
                <div class="col-md-4 mb-4">
                    <h5 class="fw-bold mb-3">Kontak Kami</h5>
                    <ul class="list-unstyled small">
                        <li class="mb-2">
                            <i class="bi bi-geo-alt"></i> 
                            <?= nl2br(getSetting('contact_address', 'Jl. Pendidikan No. 123, Banjarmasin')) ?>
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-telephone"></i> 
                            <?= getSetting('contact_phone', '(0511) 1234567') ?>
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-envelope"></i> 
                            <?= getSetting('contact_email', 'info@btikp-kalsel.id') ?>
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-clock"></i> 
                            Senin - Jumat: 08:00 - 16:00 WITA
                        </li>
                    </ul>
                </div>
            </div>
            
            <hr class="border-secondary">
            
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <small>
                        <?php 
                        $copyright = getSetting('site_copyright', '© {year} ' . getSetting('site_name', 'BTIKP Kalimantan Selatan') . '. All Rights Reserved.');
                        // Replace {year} placeholder with current year
                        $copyright = str_replace('{year}', date('Y'), $copyright);
                        echo $copyright;
                        ?>
                    </small>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <small>
                        <a href="<?= BASE_URL ?>privacy.php" class="text-white text-decoration-none">Privacy Policy</a> | 
                        <a href="<?= BASE_URL ?>terms.php" class="text-white text-decoration-none">Terms of Service</a>
                    </small>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>
</html>
