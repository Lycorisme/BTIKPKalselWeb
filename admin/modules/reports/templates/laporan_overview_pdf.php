<?php
/**
 * PDF Template: Laporan Overview
 * Sesuai standar laporan executive (A4 Portrait cukup untuk overview)
 */

// 1. Pastikan semua variabel data tersedia (Fallback ke getSetting jika belum didefinisikan di controller)
$siteName       = $siteName ?? getSetting('site_name', 'BTIKP Kalimantan Selatan');
$contactAddress = $contactAddress ?? getSetting('contact_address', '');
$contactPhone   = $contactPhone ?? getSetting('contact_phone', '');
$contactEmail   = $contactEmail ?? getSetting('contact_email', '');
$siteLogo       = $siteLogo ?? getSetting('site_logo', '');

// 2. Convert logo to base64 agar tampil di PDF
$logoBase64 = '';
if ($siteLogo && function_exists('uploadExists') && uploadExists($siteLogo)) {
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
        
        /* Header dengan Logo di Atas */
        .header {
            width: 100%;
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .header-logo {
            margin-bottom: 10px;
        }
        
        .header-logo img {
            height: 45px;
            max-width: 110px;
        }
        
        .header-title {
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }
        
        .header-contact {
            font-size: 9pt;
            line-height: 1.5;
            color: #333;
        }
        
        /* Title */
        h1 {
            text-align: center;
            font-size: 18pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 20px 0 5px 0;
            letter-spacing: 2px;
        }
        
        .subtitle {
            text-align: center;
            font-size: 10pt;
            color: #666;
            margin-bottom: 25px;
        }
        
        /* Main Table */
        h2 {
            font-size: 12pt;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
            text-transform: uppercase;
        }
        
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-top: 10px;
        }
        
        table.data-table th {
            background-color: #e0e0e0;
            padding: 10px 5px;
            text-align: center;
            border: 1px solid #000;
            font-weight: bold;
            font-size: 9pt;
            text-transform: uppercase;
        }
        
        table.data-table td {
            padding: 8px 5px;
            border: 1px solid #000;
            font-size: 9pt;
            vertical-align: middle;
        }
        
        /* Zebra Striping */
        .bg-gray {
            background-color: #f9f9f9;
        }
        
        /* Utilities */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .fw-bold { font-weight: bold; }
        
        .badge {
            padding: 2px 6px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 8pt;
            background-color: #eee;
        }

    </style>
</head>
<body>
    <div class="header">
        <?php if ($logoBase64): ?>
            <div class="header-logo">
                <img 
                    src="<?= $logoBase64 ?>" 
                    alt="Logo"
                    style="height:60px; max-width:110px;"
                >
            </div>
        <?php endif; ?>
        
        <div class="header-title"><?= strtoupper($siteName) ?></div>
        <div class="header-contact">
            <?php if ($contactAddress): ?>
                <?= htmlspecialchars($contactAddress) ?><br>
            <?php endif; ?>
            
            <?php 
            // Logic tampilan telepon dan email agar rapi
            $contactInfo = [];
            if ($contactPhone) {
                $contactInfo[] = 'Telp: ' . htmlspecialchars($contactPhone);
            }
            if ($contactEmail) {
                $contactInfo[] = 'Email: ' . htmlspecialchars($contactEmail);
            }
            
            if (!empty($contactInfo)) {
                echo implode(' | ', $contactInfo);
            }
            ?>
        </div>
    </div>
    
    <h1>Laporan Overview Sistem</h1>
    <div class="subtitle">
        Tanggal Cetak: <?= date('d F Y, H:i') ?> WIB
    </div>
    
    <h2>Ringkasan Status Modul</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25%;">Modul Sistem</th>
                <th style="width: 15%;">Total Data</th>
                <th style="width: 15%;">Status Aktif</th>
                <th style="width: 20%;">Status Pending</th>
                <th style="width: 25%;">Metrik Lain</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($overviewData as $index => $row): ?>
            <tr class="<?= $index % 2 != 0 ? 'bg-gray' : '' ?>">
                <td class="text-left">
                    <strong><?= htmlspecialchars($row['module']) ?></strong>
                </td>
                <td class="text-center">
                    <strong><?= $row['total'] ?></strong>
                </td>
                <td class="text-center">
                    <?php if ($row['active'] !== '-'): ?>
                        <?= $row['active'] ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <?php if ($row['pending'] !== '-' && $row['pending'] != 0): ?>
                        <?= $row['pending'] ?>
                    <?php elseif ($row['pending'] === 0): ?>
                        0
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td class="text-right">
                    <?php if ($row['metric_value'] !== '-'): ?>
                        <span style="color: #555; font-size: 8pt;"><?= $row['metric_label'] ?>:</span> 
                        <strong><?= $row['metric_value'] ?></strong>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>