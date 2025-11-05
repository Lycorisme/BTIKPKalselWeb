<?php
/**
 * Admin Footer Template
 * With Dynamic Copyright from Settings
 */

// Get copyright from settings
$copyright = getSetting('site_copyright', '© {year} BTIKP Kalimantan Selatan. All Rights Reserved.');
$copyright = str_replace('{year}', date('Y'), $copyright);

// Get site name
$siteName = getSetting('site_name', 'BTIKP Kalimantan Selatan');
?>
            </div> <!-- End #main-content -->
            
            <!-- Footer -->
            <footer>
                <div class="container-fluid">
                    <div class="footer clearfix mb-0 text-muted">
                        <div class="float-start">
                            <p><?= $copyright ?></p>
                        </div>
                        <div class="float-end">
                            <p>Powered by <span class="text-danger"><i class="bi bi-heart-fill icon-mid"></i></span>
                                <a href="<?= BASE_URL ?>" target="_blank"><?= $siteName ?></a>
                            </p>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    
    <!-- Mazer JS -->
    <script src="<?= ADMIN_URL ?>assets/static/js/components/dark.js"></script>
    <script src="<?= ADMIN_URL ?>assets/extensions/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="<?= ADMIN_URL ?>assets/compiled/js/app.js"></script>
    
    <!-- Additional Scripts -->
    <?php if (isset($additionalScripts)): ?>
        <?= $additionalScripts ?>
    <?php endif; ?>
</body>
</html>
