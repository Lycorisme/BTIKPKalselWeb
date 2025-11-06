<?php
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

$db = Database::getInstance()->getConnection();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("UPDATE banners SET deleted_at=NOW() WHERE id=?");
$stmt->execute([$id]);

setAlert('success', 'Banner dihapus (masuk trash).');
header("Location: banners_list.php");
exit;
