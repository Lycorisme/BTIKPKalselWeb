<?php
/**
 * Contact Page - Updated with Dynamic Settings
 */

require_once '../config/config.php';
require_once '../core/Database.php';
require_once '../core/Helper.php';

$pageTitle = 'Kontak Kami';

// Get contact settings
$phone = getSetting('contact_phone', '(0511) 1234567');
$email = getSetting('contact_email', 'info@btikp-kalsel.id');
$address = getSetting('contact_address', 'Jl. Pendidikan No. 123, Banjarmasin, Kalimantan Selatan');
$mapsEmbed = getSetting('contact_maps_embed');

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean($_POST['name'] ?? '');
    $emailFrom = clean($_POST['email'] ?? '');
    $subject = clean($_POST['subject'] ?? '');
    $message = clean($_POST['message'] ?? '');
    
    if ($name && $emailFrom && $subject && $message) {
        // Save to database or send email
        // For now, just show success
        setAlert('success', 'Pesan Anda telah terkirim. Kami akan segera menghubungi Anda.');
        redirect(BASE_URL . 'contact.php');
    } else {
        $error = 'Semua field harus diisi';
    }
}

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="bg-primary text-white py-5">
    <div class="container">
        <h1 class="fw-bold">Kontak Kami</h1>
        <p class="lead mb-0">Hubungi kami untuk informasi lebih lanjut</p>
    </div>
</div>

<!-- Contact Section -->
<section class="py-5">
    <div class="container">
        <div class="row g-5">
            <!-- Contact Info -->
            <div class="col-lg-4">
                <h3 class="fw-bold mb-4">Informasi Kontak</h3>
                
                <div class="mb-4">
                    <h5><i class="bi bi-geo-alt text-primary"></i> Alamat</h5>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($address)) ?></p>
                </div>
                
                <div class="mb-4">
                    <h5><i class="bi bi-telephone text-primary"></i> Telepon</h5>
                    <p class="text-muted"><?= htmlspecialchars($phone) ?></p>
                </div>
                
                <div class="mb-4">
                    <h5><i class="bi bi-envelope text-primary"></i> Email</h5>
                    <p class="text-muted"><?= htmlspecialchars($email) ?></p>
                </div>
                
                <div class="mb-4">
                    <h5><i class="bi bi-clock text-primary"></i> Jam Operasional</h5>
                    <p class="text-muted">
                        Senin - Jumat: 08:00 - 16:00 WITA<br>
                        Sabtu - Minggu: Tutup
                    </p>
                </div>
            </div>
            
            <!-- Contact Form -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h3 class="fw-bold mb-4">Kirim Pesan</h3>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <?php if ($alert = getAlert()): ?>
                            <div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show">
                                <?= $alert['message'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Subjek <span class="text-danger">*</span></label>
                                    <input type="text" name="subject" class="form-control" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Pesan <span class="text-danger">*</span></label>
                                    <textarea name="message" class="form-control" rows="6" required></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-send"></i> Kirim Pesan
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Map Section -->
<?php if ($mapsEmbed): ?>
    <section class="py-5 bg-light">
        <div class="container">
            <h3 class="fw-bold mb-4 text-center">Lokasi Kami</h3>
            <div class="ratio ratio-21x9">
                <?= $mapsEmbed ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
