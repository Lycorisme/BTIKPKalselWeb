<?php
/**
 * PDF Template: System Overview Report
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
        
        .section-box {
            border: 1px solid #000;
            padding: 10px;
            margin: 10px 0;
            background-color: #fafafa;
        }
        
        .section-title {
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 8px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 3px;
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
    <h1>LAPORAN SYSTEM OVERVIEW</h1>
    <div style="text-align: center; font-size: 9pt; color: #666; margin-bottom: 20px;">
        Tanggal: <?= formatTanggal(date('Y-m-d'), 'd F Y') ?>
    </div>
    
    <!-- POSTS STATISTICS -->
    <h2>STATISTIK POSTS</h2>
    <table class="stats-table">
        <tr>
            <td>
                <div class="stats-label">TOTAL POSTS</div>
                <div class="stats-value"><?= formatNumber($postsStats['total']) ?></div>
            </td>
            <td>
                <div class="stats-label">PUBLISHED</div>
                <div class="stats-value"><?= formatNumber($postsStats['published']) ?></div>
            </td>
            <td>
                <div class="stats-label">DRAFT</div>
                <div class="stats-value"><?= formatNumber($postsStats['draft']) ?></div>
            </td>
            <td>
                <div class="stats-label">ARCHIVED</div>
                <div class="stats-value"><?= formatNumber($postsStats['archived']) ?></div>
            </td>
            <td>
                <div class="stats-label">FEATURED</div>
                <div class="stats-value"><?= formatNumber($postsStats['featured']) ?></div>
            </td>
            <td>
                <div class="stats-label">TOTAL VIEWS</div>
                <div class="stats-value"><?= formatNumber($postsStats['total_views']) ?></div>
            </td>
        </tr>
    </table>
    
    <!-- SERVICES & USERS SIDE BY SIDE -->
    <table style="width: 100%; margin-top: 20px;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                <div class="section-box">
                    <div class="section-title">STATISTIK LAYANAN</div>
                    <table style="border: none; margin: 0;">
                        <tr>
                            <td style="border: none; width: 25%; text-align: center; padding: 5px;">
                                <div style="font-size: 8pt; color: #666;">Total</div>
                                <div style="font-size: 14pt; font-weight: bold;"><?= formatNumber($servicesStats['total']) ?></div>
                            </td>
                            <td style="border: none; width: 25%; text-align: center; padding: 5px;">
                                <div style="font-size: 8pt; color: #666;">Published</div>
                                <div style="font-size: 14pt; font-weight: bold;"><?= formatNumber($servicesStats['published']) ?></div>
                            </td>
                            <td style="border: none; width: 25%; text-align: center; padding: 5px;">
                                <div style="font-size: 8pt; color: #666;">Draft</div>
                                <div style="font-size: 14pt; font-weight: bold;"><?= formatNumber($servicesStats['draft']) ?></div>
                            </td>
                            <td style="border: none; width: 25%; text-align: center; padding: 5px;">
                                <div style="font-size: 8pt; color: #666;">Archived</div>
                                <div style="font-size: 14pt; font-weight: bold;"><?= formatNumber($servicesStats['archived']) ?></div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
            
            <td style="width: 50%; vertical-align: top; padding-left: 10px;">
                <div class="section-box">
                    <div class="section-title">STATISTIK USERS</div>
                    <table style="border: none; margin: 0;">
                        <tr>
                            <td style="border: none; width: 20%; text-align: center; padding: 5px;">
                                <div style="font-size: 8pt; color: #666;">Total</div>
                                <div style="font-size: 14pt; font-weight: bold;"><?= formatNumber($usersStats['total']) ?></div>
                            </td>
                            <td style="border: none; width: 20%; text-align: center; padding: 5px;">
                                <div style="font-size: 8pt; color: #666;">S.Admin</div>
                                <div style="font-size: 14pt; font-weight: bold;"><?= formatNumber($usersStats['super_admin']) ?></div>
                            </td>
                            <td style="border: none; width: 20%; text-align: center; padding: 5px;">
                                <div style="font-size: 8pt; color: #666;">Admin</div>
                                <div style="font-size: 14pt; font-weight: bold;"><?= formatNumber($usersStats['admin']) ?></div>
                            </td>
                            <td style="border: none; width: 20%; text-align: center; padding: 5px;">
                                <div style="font-size: 8pt; color: #666;">Editor</div>
                                <div style="font-size: 14pt; font-weight: bold;"><?= formatNumber($usersStats['editor']) ?></div>
                            </td>
                            <td style="border: none; width: 20%; text-align: center; padding: 5px;">
                                <div style="font-size: 8pt; color: #666;">Author</div>
                                <div style="font-size: 14pt; font-weight: bold;"><?= formatNumber($usersStats['author']) ?></div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>
    
    <!-- CATEGORIES & TAGS -->
    <table style="width: 100%; margin-top: 15px;">
        <tr>
            <td style="width: 35%; vertical-align: top; padding-right: 10px;">
                <div class="section-box">
                    <div class="section-title">KATEGORI & TAGS</div>
                    <table style="border: none; margin: 0;">
                        <tr>
                            <td style="border: none; width: 50%; text-align: center; padding: 8px;">
                                <div style="font-size: 8pt; color: #666;">Total Kategori</div>
                                <div style="font-size: 16pt; font-weight: bold;"><?= formatNumber($totalCategories) ?></div>
                            </td>
                            <td style="border: none; width: 50%; text-align: center; padding: 8px;">
                                <div style="font-size: 8pt; color: #666;">Total Tags</div>
                                <div style="font-size: 16pt; font-weight: bold;"><?= formatNumber($totalTags) ?></div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
            
            <td style="width: 65%; vertical-align: top; padding-left: 10px;">
                <div class="section-box">
                    <div class="section-title">STATISTIK AKTIVITAS</div>
                    <table style="border: none; margin: 0;">
                        <tr>
                            <td style="border: none; width: 20%; text-align: center; padding: 5px;">
                                <div style="font-size: 8pt; color: #666;">Total</div>
                                <div style="font-size: 14pt; font-weight: bold;"><?= formatNumber($activitiesStats['total']) ?></div>
                            </td>
                            <td style="border: none; width: 20%; text-align: center; padding: 5px;">
                                <div style="font-size: 8pt; color: #666;">Create</div>
                                <div style="font-size: 14pt; font-weight: bold;"><?= formatNumber($activitiesStats['creates']) ?></div>
                            </td>
                            <td style="border: none; width: 20%; text-align: center; padding: 5px;">
                                <div style="font-size: 8pt; color: #666;">Update</div>
                                <div style="font-size: 14pt; font-weight: bold;"><?= formatNumber($activitiesStats['updates']) ?></div>
                            </td>
                            <td style="border: none; width: 20%; text-align: center; padding: 5px;">
                                <div style="font-size: 8pt; color: #666;">Delete</div>
                                <div style="font-size: 14pt; font-weight: bold;"><?= formatNumber($activitiesStats['deletes']) ?></div>
                            </td>
                            <td style="border: none; width: 20%; text-align: center; padding: 5px;">
                                <div style="font-size: 8pt; color: #666;">Login</div>
                                <div style="font-size: 14pt; font-weight: bold;"><?= formatNumber($activitiesStats['logins']) ?></div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>
    
    <!-- RECENT POSTS & TOP CATEGORIES -->
    <div class="mt-20">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                    <h2>5 POSTS TERBARU</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Judul Post</th>
                                <th style="width: 30%;">Penulis</th>
                                <th style="width: 20%;">Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentPosts)): ?>
                                <tr>
                                    <td colspan="3" class="text-center">Tidak ada posts</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentPosts as $post): ?>
                                    <tr>
                                        <td><?= htmlspecialchars(truncateText($post['title'], 35)) ?></td>
                                        <td><?= htmlspecialchars($post['author_name']) ?></td>
                                        <td class="text-center"><?= formatTanggal($post['created_at'], 'd/m/Y') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </td>
                
                <td style="width: 50%; vertical-align: top; padding-left: 10px;">
                    <h2>TOP 5 KATEGORI</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nama Kategori</th>
                                <th style="width: 30%;" class="text-center">Jumlah Posts</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topCategories)): ?>
                                <tr>
                                    <td colspan="2" class="text-center">Tidak ada kategori</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($topCategories as $cat): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($cat['name']) ?></td>
                                        <td class="text-center"><strong><?= formatNumber($cat['post_count']) ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
