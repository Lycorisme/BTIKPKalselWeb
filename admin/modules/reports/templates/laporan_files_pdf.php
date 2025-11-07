<?php
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
<meta charset="UTF-8" />
<style>
    body {
        font-family: "Cambria", serif;
        font-size: 10pt;
        color: #000;
        margin: 0; padding: 0;
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
    .header-info { text-align: center; vertical-align: middle; }
    .header-title { font-size: 14pt; font-weight: bold; text-transform: uppercase; margin-bottom: 3px; }
    .header-contact { font-size: 9pt; line-height: 1.4; }
    h1 {
        text-align: center;
        font-size: 14pt;
        font-weight: bold;
        text-transform: uppercase;
        margin: 20px 0 5px 0;
        letter-spacing: 1px;
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
    .text-center { text-align: center; }
</style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-logo">
                    <?php if ($logoBase64): ?>
                        <img src="<?= $logoBase64 ?>" style="height: 60px;" />
                    <?php endif; ?>
                </td>
                <td class="header-info">
                    <div class="header-title"><?= strtoupper($siteName) ?></div>
                    <div class="header-contact">
                        <?php if ($contactAddress): ?>
                            <?= htmlspecialchars($contactAddress) ?><br />
                        <?php endif; ?>
                        <?php if ($contactPhone): ?>
                            Telp: <?= htmlspecialchars($contactPhone) ?>
                        <?php endif; ?>
                        <?php if ($contactPhone && $contactEmail): ?>|<?php endif; ?>
                        <?php if ($contactEmail): ?>
                            Email: <?= htmlspecialchars($contactEmail) ?>
                        <?php endif; ?>
                    </div>
                </td>
                <td style="width: 80px;"></td>
            </tr>
        </table>
    </div>

    <h1>LAPORAN FILE DOWNLOAD</h1>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 40%;">Judul File</th>
                <th style="width: 40%;">Deskripsi</th>
                <th style="width: 15%;">Tanggal Upload</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($files)): ?>
                <tr>
                    <td colspan="4" class="text-center" style="padding: 20px;">Tidak ada data</td>
                </tr>
            <?php else: ?>
                <?php $no=1; foreach($files as $file): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td><?= htmlspecialchars($file['title'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($file['description'] ?? '') ?></td>
                        <td class="text-center"><?= formatTanggal($file['created_at'] ?? '', 'd/m/Y') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
