<?php
/**
 * Admin Footer Template
 * With Dynamic Copyright from Settings & Dynamic Notification Theme Loading
 */

// Get copyright from settings
 $copyright = getSetting('site_copyright', '© {year} BTIKP Kalimantan Selatan. All Rights Reserved.');
 $copyright = str_replace('{year}', date('Y'), $copyright);

// Get site name
 $siteName = getSetting('site_name', 'BTIKP Kalimantan Selatan');

// ===================================
// DYNAMIC NOTIFICATION THEME LOADING
// ===================================
 $notification_theme = getSetting('notification_alert_theme', 'alecto-final-blow');

// Map theme names to JS file names
 $themeFiles = [
    'alecto-final-blow' => 'notifications.js',
    'an-eye-for-an-eye' => 'notifications_an_eye_for_an_eye.js',
    'throne-of-ruin' => 'notifications_throne.js',
    'hoki-crossbow-of-tang' => 'notifications_crossbow.js',
    'death-sonata' => 'notifications_death_sonata.js'
];

// Get current theme JS file or fallback to default
 $themeJsFile = $themeFiles[$notification_theme] ?? $themeFiles['alecto-final-blow'];
?>
            </div> <!-- End #main-content -->
            
            <!-- Footer -->
            <footer class="animate-on-scroll fade-in" data-delay="600">
                <div class="container-fluid">
                    <div class="footer clearfix mb-0 text-muted">
                        <div class="float-start">
                            <p><?= $copyright ?></p>
                        </div>
                        <div class="float-end">
                            <p>Powered by <span class="text-danger"><i class=""></i></span>
                                <a href="#" target="_blank">M. Haldi</a>
                            </p>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    
    <!-- Mazer Core JS -->
    <script src="<?= ADMIN_URL ?>assets/static/js/components/dark.js"></script>
    <script src="<?= ADMIN_URL ?>assets/extensions/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="<?= ADMIN_URL ?>assets/compiled/js/app.js"></script>
    
    <!-- DYNAMIC NOTIFICATION THEME JAVASCRIPT -->
    <script src="<?= ADMIN_URL ?>assets/js/<?= $themeJsFile ?>?v=<?= time() ?>" 
            data-notification-theme="<?= $notification_theme ?>"></script>
    
    <!-- PAGE ANIMATIONS JAVASCRIPT -->
    <script src="<?= ADMIN_URL ?>assets/js/page-animations.js?v=<?= time() ?>"></script>
    
    <!-- CORE NOTIFICATION MODAL LOGIC -->
    <script>
    function openNotificationModal() {
        const modal = document.getElementById('notification-modal');
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        }
    }
    
    function closeNotificationModal() {
        const modal = document.getElementById('notification-modal');
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = ''; // Restore scrolling
        }
    }
    </script>
    
    <!-- Auto show alert from PHP session (using custom toast) -->
    <?php if ($alert = getAlert()): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Wait for notify object to be loaded
        if (typeof notify !== 'undefined') {
            notify.<?= $alert['type'] === 'danger' ? 'error' : $alert['type'] ?>('<?= addslashes($alert['message']) ?>');
        } else {
            // Fallback if notify not loaded yet
            console.error('Notify object not loaded. Alert message: <?= addslashes($alert['message']) ?>');
        }
    });
    </script>
    <?php endif; ?>
    
    <!-- Additional Scripts from Pages -->
    <?php if (isset($additionalScripts)): ?>
        <?= $additionalScripts ?>
    <?php endif; ?>


    <!-- Notification Center Modal (Minimal Elegance Design) -->
    <div id="notification-modal" class="btikp-notification-modal" onclick="if(event.target === this) closeNotificationModal()" role="dialog" aria-modal="true" aria-labelledby="notification-title">
        <div class="btikp-notification-box">
            <!-- Header -->
            <div class="btikp-notification-header">
                <h5 id="notification-title">
                    <i class="bi bi-bell"></i>
                    <span>Notifikasi</span>
                </h5>
                <button type="button" class="btikp-notification-close" onclick="closeNotificationModal()" aria-label="Tutup">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            
            <!-- Notification List -->
            <div class="btikp-notification-list">
                <?php if (empty($notifications)): ?>
                    <!-- Empty State -->
                    <div class="btikp-notification-empty">
                        <div class="btikp-empty-icon">
                            <i class="bi bi-bell-slash"></i>
                        </div>
                        <h6>Tidak ada notifikasi</h6>
                        <p>Semua beres! Anda telah melihat semua notifikasi yang masuk.</p>
                    </div>
                <?php else: ?>
                    <!-- Notification Items -->
                    <?php foreach ($notifications as $notif): ?>
                        <a href="<?= ADMIN_URL ?>modules/contact/messages_view.php?id=<?= $notif['id'] ?>" 
                           class="btikp-notification-item unread">
                            <div class="btikp-notification-item-icon">
                                <i class="bi bi-envelope"></i>
                            </div>
                            <div class="btikp-notification-item-content">
                                <span class="btikp-notification-item-title">
                                    Pesan dari <?= htmlspecialchars($notif['name'] ?? '') ?>
                                </span>
                                <span class="btikp-notification-item-text">
                                    <?= htmlspecialchars(truncateText($notif['subject'], 50)) ?>
                                </span>
                                <div class="btikp-notification-item-time">
                                    <i class="bi bi-clock"></i>
                                    <span><?= formatTanggalRelatif($notif['created_at']) ?></span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Footer (only show if there are notifications) -->
            <?php if (!empty($notifications)): ?>
                <div class="btikp-notification-footer">
                    <a href="<?= ADMIN_URL ?>modules/contact/messages_list.php?status=unread" class="btikp-btn-view-all">
                        <span>Lihat Semua</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>