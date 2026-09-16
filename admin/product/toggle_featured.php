<?php
session_start();
require_once('../../database/dbhelper.php');
require_once('../check_role.php');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role_id'], [1, 3])) {
    echo json_encode(['status' => false, 'message' => 'Không có quyền truy cập']);
    exit();
}

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['status' => false, 'message' => 'Sản phẩm không hợp lệ']);
    exit();
}

$product = executeResult("SELECT id, is_featured FROM `Product` WHERE id = $id AND deleted = 0 LIMIT 1", true);
if (!$product) {
    echo json_encode(['status' => false, 'message' => 'Không tìm thấy sản phẩm']);
    exit();
}

$new_status = ($product['is_featured'] == 1) ? 0 : 1;
execute("UPDATE `Product` SET is_featured = $new_status WHERE id = $id");

echo json_encode([
    'status' => true,
    'is_featured' => $new_status,
    'message' => ($new_status == 1) ? 'Đã ghim làm sản phẩm nổi bật!' : 'Đã bỏ ghim sản phẩm nổi bật!'
]);
exit();