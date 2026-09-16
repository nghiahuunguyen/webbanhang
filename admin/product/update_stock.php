<?php
session_start();
require_once('../../database/dbhelper.php');
require_once('../check_role.php');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role_id'], [1, 3])) {
    echo json_encode(['status' => false, 'message' => 'Không có quyền truy cập']);
    exit();
}

$variant_id = intval($_POST['variant_id'] ?? 0);
$quantity   = intval($_POST['quantity'] ?? 0);

if ($variant_id <= 0 || $quantity < 0) {
    echo json_encode(['status' => false, 'message' => 'Dữ liệu số lượng không hợp lệ']);
    exit();
}

execute("UPDATE `Product_Variant` SET stock_quantity = $quantity WHERE id = $variant_id");

echo json_encode([
    'status' => true,
    'message' => 'Cập nhật số lượng tồn kho thành công!'
]);
exit();