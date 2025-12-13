<?php
/**
 * Admin Dashboard - Mazer Style
 * Full Mazer Assets Implementation
 */

require_once 'includes/auth_check.php';
require_once '../core/Database.php';
require_once '../core/Helper.php';
require_once '../models/Post.php';

$pageTitle = 'Dashboard';

// Initialize
$postModel = new Post();
$db = Database::getInstance()->getConnection();

// Get dynamic settings
$siteName = getSetting('site_name', 'BTIKP Kalimantan Selatan');

// Get statistics
$stats = [
    'total_laporan' => 0,
    'masuk_hari_ini' => 0,
    'sedang_diproses' => 0,
    'total_views' => 0
];

// Total posts (as laporan)
$stmt = $db->query("SELECT COUNT(*) FROM posts WHERE deleted_at IS NULL");
$stats['total_laporan'] = $stmt->fetchColumn();

// Posts today (masuk hari ini)
$stmt = $db->query("SELECT COUNT(*) FROM posts WHERE DATE(created_at) = CURDATE() AND deleted_at IS NULL");
$stats['masuk_hari_ini'] = $stmt->fetchColumn();

// Posts in process (draft + published) - Asumsi logika bisnis Anda
$stmt = $db->query("SELECT COUNT(*) FROM posts WHERE status IN ('draft', 'published') AND deleted_at IS NULL");
$stats['sedang_diproses'] = $stmt->fetchColumn();

// Total views
$stmt = $db->query("SELECT SUM(view_count) FROM posts WHERE deleted_at IS NULL");
$stats['total_views'] = $stmt->fetchColumn() ?: 0;

// Laporan masuk terakhir (bar chart data - 6 bulan terakhir)
$stmt = $db->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM posts
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
      AND deleted_at IS NULL
    GROUP BY month
    ORDER BY month ASC
");
$laporanPerBulan = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Distribusi status (donut chart data)
$stmt = $db->query("
    SELECT 
        status,
        COUNT(*) as count
    FROM posts
    WHERE deleted_at IS NULL
    GROUP BY status
");
$statusData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent activities
$stmt = $db->query("
    SELECT *
    FROM activity_logs
    ORDER BY created_at DESC
    LIMIT 5
");
$recentActivities = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="page-heading">
    <h3>Dashboard Statistics</h3>
    <p class="text-subtitle text-muted">Ringkasan data portal <?= htmlspecialchars($siteName) ?></p>
</div>

<div class="page-content">
    <section class="row">
        <div class="col-12 col-lg-12">
            <div class="row">
                <div class="col-6 col-lg-3 col-md-6">
                    <div class="card">
                        <div class="card-body px-4 py-4-5">
                            <div class="row">
                                <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                                    <div class="stats-icon purple mb-2">
                                        <i class="iconly-boldDocument"></i>
                                    </div>
                                </div>
                                <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                    <h6 class="text-muted font-semibold">Total Laporan</h6>
                                    <h6 class="font-extrabold mb-0"><?= formatNumber($stats['total_laporan']) ?></h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3 col-md-6">
                    <div class="card">
                        <div class="card-body px-4 py-4-5">
                            <div class="row">
                                <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                                    <div class="stats-icon blue mb-2">
                                        <i class="iconly-boldTime-Circle"></i>
                                    </div>
                                </div>
                                <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                    <h6 class="text-muted font-semibold">Masuk Hari Ini</h6>
                                    <h6 class="font-extrabold mb-0"><?= formatNumber($stats['masuk_hari_ini']) ?></h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3 col-md-6">
                    <div class="card">
                        <div class="card-body px-4 py-4-5">
                            <div class="row">
                                <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                                    <div class="stats-icon green mb-2">
                                        <i class="iconly-boldWork"></i>
                                    </div>
                                </div>
                                <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                    <h6 class="text-muted font-semibold">Diproses</h6>
                                    <h6 class="font-extrabold mb-0"><?= formatNumber($stats['sedang_diproses']) ?></h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3 col-md-6">
                    <div class="card">
                        <div class="card-body px-4 py-4-5">
                            <div class="row">
                                <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                                    <div class="stats-icon red mb-2">
                                        <i class="iconly-boldGraph"></i>
                                    </div>
                                </div>
                                <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                    <h6 class="text-muted font-semibold">Total Views</h6>
                                    <h6 class="font-extrabold mb-0"><?= formatNumber($stats['total_views']) ?></h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-xl-8">
                    <div class="card">
                        <div class="card-header">
                            <h4>Tren Laporan Masuk</h4>
                        </div>
                        <div class="card-body">
                            <div style="height: 300px; position: relative;">
                                <canvas id="laporanChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="card">
                        <div class="card-header">
                            <h4>Distribusi Status</h4>
                        </div>
                        <div class="card-body">
                            <div style="height: 300px; position: relative;">
                                <canvas id="statusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Aktivitas Terbaru</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-lg">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Aksi</th>
                                            <th>Deskripsi</th>
                                            <th>Waktu</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($recentActivities)): ?>
                                            <?php foreach ($recentActivities as $activity): ?>
                                                <tr>
                                                    <td class="col-3">
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar avatar-md">
                                                                <div class="avatar-content bg-primary text-white font-bold">
                                                                    <?= strtoupper(substr($activity['user_name'], 0, 1)) ?>
                                                                </div>
                                                            </div>
                                                            <p class="font-bold ms-3 mb-0"><?= htmlspecialchars($activity['user_name']) ?></p>
                                                        </div>
                                                    </td>
                                                    <td class="col-auto">
                                                        <?php
                                                        $badges = [
                                                            'CREATE' => 'success',
                                                            'UPDATE' => 'primary',
                                                            'DELETE' => 'danger',
                                                            'LOGIN' => 'info',
                                                            'LOGOUT' => 'secondary'
                                                        ];
                                                        $badgeClass = $badges[$activity['action_type']] ?? 'secondary';
                                                        ?>
                                                        <span class="badge bg-<?= $badgeClass ?>">
                                                            <?= $activity['action_type'] ?>
                                                        </span>
                                                    </td>
                                                    <td class="col-auto">
                                                        <p class="mb-0 text-truncate" style="max-width: 300px;">
                                                            <?= htmlspecialchars($activity['description']) ?>
                                                        </p>
                                                    </td>
                                                    <td class="col-auto">
                                                        <small class="text-muted">
                                                            <?= formatTanggalRelatif($activity['created_at']) ?>
                                                        </small>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">Belum ada aktivitas tercatat.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ===== BAR CHART - LAPORAN MASUK =====
const laporanChartCtx = document.getElementById('laporanChart').getContext('2d');
const laporanData = <?= json_encode($laporanPerBulan) ?>;

// Prepare 6 months data
const months = [];
const counts = [];
const now = new Date();

for (let i = 5; i >= 0; i--) {
    const date = new Date(now.getFullYear(), now.getMonth() - i, 1);
    const monthKey = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0');
    // Format label bulan (Short format: Jan, Feb, etc.)
    const monthName = date.toLocaleDateString('id-ID', { month: 'short', year: 'numeric' });
    
    months.push(monthName);
    counts.push(laporanData[monthKey] || 0);
}

new Chart(laporanChartCtx, {
    type: 'bar',
    data: {
        labels: months,
        datasets: [{
            label: 'Jumlah Laporan',
            data: counts,
            backgroundColor: '#435ebe', // Mazer Primary Blue
            borderRadius: 4,
            barThickness: 30
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1e1e1e',
                padding: 10,
                titleFont: { family: "Nunito" },
                bodyFont: { family: "Nunito" }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: '#e9ecef',
                    borderDash: [4, 4]
                },
                ticks: {
                    stepSize: 1,
                    font: { family: "Nunito" }
                }
            },
            x: {
                grid: { display: false },
                ticks: { font: { family: "Nunito" } }
            }
        }
    }
});

// ===== DONUT CHART - DISTRIBUSI STATUS =====
const statusChartCtx = document.getElementById('statusChart').getContext('2d');
const statusRawData = <?= json_encode($statusData) ?>;

const statusLabels = {
    'draft': 'Draft',
    'published': 'Published',
    'archived': 'Archived'
};

const statusColors = {
    'draft': '#ffc107',    // Warning
    'published': '#57caeb', // Info/Cyan Mazer
    'archived': '#6c757d'  // Secondary
};

const statusChartLabels = statusRawData.map(item => statusLabels[item.status] || item.status);
const statusCounts = statusRawData.map(item => parseInt(item.count));
const statusBgColors = statusRawData.map(item => statusColors[item.status] || '#435ebe');

new Chart(statusChartCtx, {
    type: 'doughnut',
    data: {
        labels: statusChartLabels,
        datasets: [{
            data: statusCounts,
            backgroundColor: statusBgColors,
            borderWidth: 0,
            hoverOffset: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    font: { family: "Nunito", size: 12 },
                    usePointStyle: true,
                    padding: 20
                }
            }
        },
        cutout: '70%'
    }
});
</script>

<?php include 'includes/footer.php'; ?>