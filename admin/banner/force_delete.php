<?php
session_start();
require_once('../../database/dbhelper.php');
require_once('../check_role.php');

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role_id'], [1, 3])) {
    header('Location: ../auth/login.php');
    die();
}

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    // 1. Lấy đường dẫn file ảnh để xóa vật lý trên ổ đĩa
    $banner = executeResult("SELECT image_url FROM `Banner` WHERE id = $id", true);
    if (!empty($banner['image_url'])) {
        $file_path = '../../' . $banner['image_url'];
        if (file_exists($file_path) && !is_dir($file_path)) {
            unlink($file_path);
        }
    }

    // 2. Xóa hẳn dòng khỏi cơ sở dữ liệu
    execute("DELETE FROM `Banner` WHERE id = $id");

    $_SESSION['success_msg'] = "Đã xóa vĩnh viễn banner và file ảnh!";
}

header('Location: trash.php');
die();