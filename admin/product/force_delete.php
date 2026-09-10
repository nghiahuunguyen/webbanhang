<?php
session_start();
require_once('../../database/dbhelper.php');

$id = $_GET['id'] ?? '';

if (!empty($id)) {
    $id = intval($id);

    // 1. Lấy thông tin ảnh đại diện để xóa file vật lý
    $product = executeResult("SELECT thumbnail FROM Product WHERE id = $id", true);
    if (!empty($product['thumbnail'])) {
        $thumb_file = '../../' . $product['thumbnail'];
        if (file_exists($thumb_file)) {
            unlink($thumb_file);
        }
    }

    // 2. Lấy thông tin các ảnh phụ trong Galery để xóa file vật lý
    $galleries = executeResult("SELECT thumbnail FROM Galery WHERE product_id = $id");
    if (!empty($galleries)) {
        foreach ($galleries as $gal) {
            $gal_file = '../../' . $gal['thumbnail'];
            if (file_exists($gal_file)) {
                unlink($gal_file);
            }
        }
    }

    // 3. Xóa dữ liệu bảng Galery và bảng Product
    execute("DELETE FROM Galery WHERE product_id = $id");
    execute("DELETE FROM Product WHERE id = $id");

    $_SESSION['success_msg'] = "Đã xóa vĩnh viễn sản phẩm và toàn bộ hình ảnh!";
}

header('Location: trash.php');
die();