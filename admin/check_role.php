<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header('Location: /webbanhang/login.php');
    exit();
}

$currentUser = $_SESSION['user'];
$role_id = (int)($currentUser['role_id'] ?? 0);

// 2. Chặn khách hàng (role_id = 2) vào khu vực admin
if ($role_id === 2) {
    header('Location: /webbanhang/index.php');
    exit();
}

// 3. Chặn Nhân viên (role_id = 3), chỉ cho phép Admin (role_id = 1)
function requireAdmin() {
    global $role_id;
    if ($role_id !== 1) {
        $_SESSION['error_msg'] = "Bạn không có quyền truy cập chức năng này!";
        header('Location: /webbanhang/admin/index.php');
        exit();
    }
}