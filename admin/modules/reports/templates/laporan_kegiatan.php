<?php
// Pastikan variabel: $siteName, $contactAddress, $contactPhone, $contactEmail,
// $siteLogo, $totalKegiatan, $totalFoto, $kegiatan
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
        body { font-family: "Cambria", serif; font-size: 10pt; color: #000; }
        .header { width:100%; border-bottom:2px solid #000; padding-bottom:10px; margin-bottom:20px;}
        .header-table { width:100%; }
        .header-logo { width:80px; vertical-align:middle; }
        .header-info { text-align:center; vertical-align:middle; }
        .header-title { font-size:14pt; font-weight:bold; text-transform:uppercase; margin-bottom:3px;}
        .header-contact { font-size:9pt; line-height:1.4;}
        h1 {text-align:center; font-size:14pt; font-weight:bold; text-transform:uppercase; margin:20px 0 3px 0;}
        h2 {font-size:11pt; font-weight:bold; margin-top:20px; margin-bottom:10px; border-bottom:1px solid #000; padding-bottom:3px;}
        table { width:100%; border-collapse:collapse; margin-top:10px;}
        table.data-table { border:1px solid #000;}
        table.data-table th { background:#f0f0f0; padding:8px 5px; text-align:left; border:1px solid #000; font-weight:bold; font-size:9pt;}
        table.data-table td { padding:6px 5px; border:1px solid #000; font-size:9pt;}
        .stats-table {width:100%;margin:15px 0;}
        .stats-table td {padding:10px; text-align:center; border:1px solid #000; background:#f9f9f9;}
        .stats-label {font-size:8pt;font-weight:bold;}
        .stats-value {font-size:16pt;font-weight:bold;}
        .text-center{text-align:center;}
    </style>
</head>
<body>
<div class="header">
    <table class="header-table">
        <tr>
            <td class="header-logo">
                <?php if($logoBase64): ?>
                    <img src="<?= $logoBase64 ?>" style="height:60px;">
                <?php endif; ?>
            </td>
            <td class="header-info">
                <div class="header-title"><?= strtoupper($siteName) ?></div>
                <div class="header-contact">
                    <?php if ($contactAddress): ?><?= htmlspecialchars($contactAddress) ?><br><?php endif; ?>
                    <?php if ($contactPhone): ?>
                        Telp: <?= htmlspecialchars($contactPhone) ?>
                    <?php endif; ?>
                    <?php if ($contactPhone && $contactEmail): ?>|<?php endif; ?>
                    <?php if ($contactEmail): ?>
                        Email: <?= htmlspecialchars($contactEmail) ?>
                    <?php endif; ?>
                </div>
            </td>
            <td style="width:80px;"></td>
        </tr>
    </table>
</div>

<h1>LAPORAN KEGIATAN</h1>
<h2>RINGKASAN STATISTIK</h2>
<table class="stats-table">
    <tr>
        <td>
            <div class="stats-label">TOTAL KEGIATAN</div>
            <div class="stats-value"><?= formatNumber($totalKegiatan) ?></div>
        </td>
        <td>
            <div class="stats-label">TOTAL DOKUMENTASI</div>
            <div class="stats-value"><?= formatNumber($totalFoto) ?></div>
        </td>
    </tr>
</table>

<h2>DAFTAR KEGIATAN</h2>
<table class="data-table">
    <thead>
        <tr>
            <th style="width:5%;">No</th>
            <th style="width:40%;">Nama Kegiatan</th>
            <th style="width:15%;">Slug</th>
            <th style="width:25%;">Tanggal Kegiatan</th>
            <th style="width:15%;">Total Dokumentasi</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($kegiatan)): ?>
            <tr>
                <td colspan="5" class="text-center" style="padding:20px;">Tidak ada data kegiatan</td>
            </tr>
        <?php else: ?>
            <?php $no=1; foreach($kegiatan as $item): ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= htmlspecialchars($item['title']) ?></td>
                    <td><?= htmlspecialchars($item['slug']) ?></td>
                    <td class="text-center"><?= formatTanggal($item['date_event'], 'd/m/Y') ?></td>
                    <td class="text-center"><?= (int)$item['total_photos'] ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
</body>
</html>
