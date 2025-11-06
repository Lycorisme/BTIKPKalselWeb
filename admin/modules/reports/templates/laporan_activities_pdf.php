<?php
/**
 * PDF Template: Laporan Activity Logs
 * Clean black & white design - Portrait
 */

// Convert logo to base64
$logoBase64 = '';
if ($siteLogo && uploadExists($siteLogo)) {
    $logoPath = uploadPath($siteLogo);
    if (file_exists($logoPath)) {
        $logoData = file_get_contents($logoPath);
        $logoExt = pathinfo($logoPath, PATHINFO_EXTENSION);
        $logoBase64 = 'data:image/' . $logoExt . ';base64,' . base64_encode($logoData);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: "Cambria", serif;
            font-size: 10pt;
            color: #000;
            margin: 0;
            padding: 0;
        }
        
        /* Header */
        .header {
            width: 100%;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .header-table {
            width: 100%;
        }
        
        .header-logo {
            width: 80px;
            vertical-align: middle;
        }
        
        .header-info {
            text-align: center;
            vertical-align: middle;
        }
        
        .header-title {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        
        .header-contact {
            font-size: 9pt;
            line-height: 1.4;
        }
        
        /* Title */
        h1 {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 20px 0 5px 0;
            letter-spacing: 1px;
        }
        
        h2 {
            font-size: 11pt;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
        }
        
        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        table.data-table {
            border: 1px solid #000;
        }
        
        table.data-table th {
            background-color: #f0f0f0;
            padding: 8px 5px;
            text-align: left;
            border: 1px solid #000;
            font-weight: bold;
            font-size: 9pt;
        }
        
        table.data-table td {
            padding: 6px 5px;
            border: 1px solid #000;
            font-size: 9pt;
        }
        
        /* Stats Table */
        .stats-table {
            width: 100%;
            margin: 15px 0;
        }
        
        .stats-table td {
            padding: 10px;
            text-align: center;
            border: 1px solid #000;
            background-color: #f9f9f9;
        }
        
        .stats-label {
            font-size: 8pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stats-value {
            font-size: 16pt;
            font-weight: bold;
        }
        
        /* Action Badge */
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border: 1px solid #000;
            background-color: #f5f5f5;
            font-size: 8pt;
            font-weight: bold;
        }
        
        /* Utilities */
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .mt-20 {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-logo">
                    <?php if ($logoBase64): ?>
                        <img src="<?= $logoBase64 ?>" style="height: 60px;">
                    <?php endif; ?>
                </td>
                <td class="header-info">
                    <div class="header-title"><?= strtoupper($siteName) ?></div>
                    <div class="header-contact">
                        <?php if ($contactAddress): ?>
                            <?= htmlspecialchars($contactAddress) ?><br>
                        <?php endif; ?>
                        <?php if ($contactPhone): ?>
                            Telp: <?= htmlspecialchars($contactPhone) ?>
                        <?php endif; ?>
                        <?php if ($contactPhone && $contactEmail): ?>
                            |
                        <?php endif; ?>
                        <?php if ($contactEmail): ?>
                            Email: <?= htmlspecialchars($contactEmail) ?>
                        <?php endif; ?>
                    </div>
                </td>
                <td style="width: 80px;"></td>
            </tr>
        </table>
    </div>
    
    <!-- Title -->
    <h1>LAPORAN AKTIVITAS SISTEM</h1>
    
    <!-- Statistics -->
    <h2>RINGKASAN STATISTIK</h2>
    <table class="stats-table">
        <tr>
            <td>
                <div class="stats-label">TOTAL AKTIVITAS</div>
                <div class="stats-value"><?= formatNumber($stats['total']) ?></div>
            </td>
            <td>
                <div class="stats-label">CREATE</div>
                <div class="stats-value"><?= formatNumber($stats['create']) ?></div>
            </td>
            <td>
                <div class="stats-label">UPDATE</div>
                <div class="stats-value"><?= formatNumber($stats['update']) ?></div>
            </td>
            <td>
                <div class="stats-label">DELETE</div>
                <div class="stats-value"><?= formatNumber($stats['delete']) ?></div>
            </td>
            <td>
                <div class="stats-label">LOGIN</div>
                <div class="stats-value"><?= formatNumber($stats['login']) ?></div>
            </td>
            <td>
                <div class="stats-label">UNIQUE USERS</div>
                <div class="stats-value"><?= formatNumber($stats['unique_users']) ?></div>
            </td>
        </tr>
    </table>
    
    <!-- Activities List -->
    <h2>DAFTAR AKTIVITAS</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 18%;">User</th>
                <th style="width: 12%;">Action</th>
                <th style="width: 35%;">Description</th>
                <th style="width: 13%;">Module</th>
                <th style="width: 18%;">Tanggal & Waktu</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($activities)): ?>
                <tr>
                    <td colspan="6" class="text-center" style="padding: 20px;">Tidak ada data aktivitas</td>
                </tr>
            <?php else: ?>
                <?php $no = 1; foreach ($activities as $activity): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td><?= htmlspecialchars($activity['display_name']) ?></td>
                        <td class="text-center">
                            <span class="badge"><?= $activity['action_type'] ?></span>
                        </td>
                        <td><?= htmlspecialchars($activity['description']) ?></td>
                        <td class="text-center">
                            <?= $activity['model_type'] ? ucfirst($activity['model_type']) : '-' ?>
                        </td>
                        <td class="text-center"><?= formatTanggal($activity['created_at'], 'd/m/Y H:i') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Stats Breakdown -->
    <div class="mt-20">
        <table style="width: 100%;">
            <tr>
                <td style="width: 33%; vertical-align: top; padding-right: 7px;">
                    <h2>TOP 10 USERS</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nama User</th>
                                <th style="width: 25%;" class="text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($userStats as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['name']) ?></td>
                                    <td class="text-center"><strong><?= formatNumber($user['total']) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </td>
                
                <td style="width: 33%; vertical-align: top; padding: 0 7px;">
                    <h2>BY ACTION TYPE</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Action Type</th>
                                <th style="width: 25%;" class="text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($actionStats as $action): ?>
                                <tr>
                                    <td>
                                        <span class="badge"><?= $action['action_type'] ?></span>
                                    </td>
                                    <td class="text-center"><strong><?= formatNumber($action['total']) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </td>
                
                <td style="width: 34%; vertical-align: top; padding-left: 7px;">
                    <h2>BY MODULE</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Module Name</th>
                                <th style="width: 25%;" class="text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($moduleStats as $module): ?>
                                <tr>
                                    <td><?= ucfirst($module['model_type']) ?></td>
                                    <td class="text-center"><strong><?= formatNumber($module['total']) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
