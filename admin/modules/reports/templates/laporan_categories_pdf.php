<?php
/**
 * PDF Template: Laporan Categories & Tags
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
    <h1>LAPORAN KATEGORI & TAGS</h1>
    
    <!-- Statistics -->
    <h2>RINGKASAN STATISTIK</h2>
    <table class="stats-table">
        <tr>
            <td>
                <div class="stats-label">TOTAL KATEGORI</div>
                <div class="stats-value"><?= formatNumber($stats['total_categories']) ?></div>
            </td>
            <td>
                <div class="stats-label">KATEGORI TERPAKAI</div>
                <div class="stats-value"><?= formatNumber($stats['categories_with_posts']) ?></div>
            </td>
            <td>
                <div class="stats-label">TOTAL TAGS</div>
                <div class="stats-value"><?= formatNumber($stats['total_tags']) ?></div>
            </td>
            <td>
                <div class="stats-label">TAGS TERPAKAI</div>
                <div class="stats-value"><?= formatNumber($stats['tags_with_posts']) ?></div>
            </td>
        </tr>
    </table>
    
    <!-- Categories and Tags List Side by Side -->
    <div class="mt-20">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                    <h2>DAFTAR KATEGORI</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 10%;">No</th>
                                <th style="width: 60%;">Nama Kategori</th>
                                <th style="width: 30%;" class="text-center">Posts</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="3" class="text-center">Tidak ada kategori</td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($categories as $category): ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($category['name']) ?></td>
                                        <td class="text-center"><strong><?= formatNumber($category['post_count']) ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </td>
                
                <td style="width: 50%; vertical-align: top; padding-left: 10px;">
                    <h2>DAFTAR TAGS</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 10%;">No</th>
                                <th style="width: 60%;">Nama Tag</th>
                                <th style="width: 30%;" class="text-center">Posts</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tags)): ?>
                                <tr>
                                    <td colspan="3" class="text-center">Tidak ada tags</td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($tags as $tag): ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($tag['name']) ?></td>
                                        <td class="text-center"><strong><?= formatNumber($tag['post_count']) ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Top 10 -->
    <div class="mt-20">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                    <h2>TOP 10 KATEGORI</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nama Kategori</th>
                                <th style="width: 30%;" class="text-center">Posts</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topCategories as $cat): ?>
                                <tr>
                                    <td><?= htmlspecialchars($cat['name']) ?></td>
                                    <td class="text-center"><strong><?= formatNumber($cat['post_count']) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </td>
                
                <td style="width: 50%; vertical-align: top; padding-left: 10px;">
                    <h2>TOP 10 TAGS</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nama Tag</th>
                                <th style="width: 30%;" class="text-center">Posts</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topTags as $tag): ?>
                                <tr>
                                    <td><?= htmlspecialchars($tag['name']) ?></td>
                                    <td class="text-center"><strong><?= formatNumber($tag['post_count']) ?></strong></td>
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
