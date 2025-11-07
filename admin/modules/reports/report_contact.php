<?php
/**
 * Report: Contact Messages
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../vendor/autoload.php';

$pageTitle = 'Laporan Pesan Kontak';
$db = Database::getInstance()->getConnection();

// Filters
$status = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$exportPdf = $_GET['export_pdf'] ?? '';

// Build query
$sql = "SELECT * FROM contact_messages WHERE deleted_at IS NULL";
$params = [];

if ($status) {
    $sql .= " AND status = ?";
    $params[] = $status;
}
if ($dateFrom) {
    $sql .= " AND DATE(created_at) >= ?";
    $params[] = $dateFrom;
}
if ($dateTo) {
    $sql .= " AND DATE(created_at) <= ?";
    $params[] = $dateTo;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$contacts = $stmt->fetchAll();

$totalContacts = count($contacts);

// Export PDF
if ($exportPdf === '1') {
    $siteName = getSetting('site_name', 'BTIKP Kalimantan Selatan');
    $contactPhone = getSetting('contact_phone', '');
    $contactEmail = getSetting('contact_email', '');
    $contactAddress = getSetting('contact_address', '');
    $siteLogo = getSetting('site_logo', '');

    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 10,
        'margin_bottom' => 20,
        'margin_header' => 0,
        'margin_footer' => 10,
    ]);
    $mpdf->SetDefaultFont('cambria');

    $footer = '
        <table width="100%" style="border-top: 1px solid #000; padding-top: 5px; font-size: 9pt;">
            <tr>
                <td width="70%" style="text-align: left;">' . htmlspecialchars($siteName) . '</td>
                <td width="30%" style="text-align: right;">Halaman {PAGENO} dari {nbpg}</td>
            </tr>
        </table>';
    $mpdf->SetHTMLFooter($footer);

    ob_start();
    include __DIR__ . '/templates/laporan_contact_pdf.php';
    $html = ob_get_clean();

    $mpdf->WriteHTML($html);
    $mpdf->Output('Laporan_PesanKontak_' . date('Ymd_His') . '.pdf', 'I');
    exit;
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
            <li class="breadcrumb-item">Laporan</li>
            <li class="breadcrumb-item active">Pesan Kontak</li>
          </ol>
        </nav>
      </div>
    </div>
  </div>

  <section class="section">
    <div class="card mb-3">
      <div class="card-header">
        <h5 class="card-title mb-0"><i class="bi bi-funnel"></i> Filter Laporan</h5>
      </div>
      <div class="card-body">
        <form method="GET">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="" <?= $status === '' ? 'selected' : '' ?>>Semua Status</option>
                <option value="unread" <?= $status === 'unread' ? 'selected' : '' ?>>Unread</option>
                <option value="read" <?= $status === 'read' ? 'selected' : '' ?>>Read</option>
                <option value="replied" <?= $status === 'replied' ? 'selected' : '' ?>>Replied</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Dari Tanggal</label>
              <input type="date" name="date_from" class="form-control" value="<?= $dateFrom ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Sampai Tanggal</label>
              <input type="date" name="date_to" class="form-control" value="<?= $dateTo ?>">
            </div>
          </div>
          <div class="row mt-3">
            <div class="col-12">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-search"></i> Tampilkan
              </button>
              <a href="report_contact.php" class="btn btn-secondary">
                <i class="bi bi-arrow-counterclockwise"></i> Reset
              </a>
              <a href="?export_pdf=1<?= $status ? '&status='.$status : '' ?><?= $dateFrom ? '&date_from='.$dateFrom : '' ?><?= $dateTo ? '&date_to='.$dateTo : '' ?>" class="btn btn-danger" target="_blank">
                <i class="bi bi-file-pdf"></i> Export PDF
              </a>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">Daftar Pesan Kontak</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Subjek</th>
                <th>Status</th>
                <th>Tanggal</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($contacts)): ?>
                <tr>
                  <td colspan="7" class="text-center text-muted">Tidak ada data</td>
                </tr>
              <?php else: ?>
                <?php $no=1; foreach ($contacts as $contact): ?>
                  <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($contact['name']) ?></td>
                    <td><?= htmlspecialchars($contact['email']) ?></td>
                    <td><?= htmlspecialchars($contact['phone']) ?></td>
                    <td><?= htmlspecialchars(truncateText($contact['subject'], 30)) ?></td>
                    <td><?= ucfirst($contact['status']) ?></td>
                    <td><?= formatTanggal($contact['created_at'], 'd/m/Y') ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include '../../includes/footer.php'; ?>
