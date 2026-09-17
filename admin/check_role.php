<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../database/dbhelper.php';

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header('Location: /webbanhang/login.php');
    exit();
}

$currentUser = $_SESSION['user'];
$role_id = (int)($currentUser['role_id'] ?? 0);

// 2. Chặn khách hàng thông thường (role_id = 2) vào khu vực admin
if ($role_id === 2) {
    header('Location: /webbanhang/index.php');
    exit();
}

// 3. Quyền Admin tối cao (role_id = 1)
function requireAdmin() {
    global $role_id;
    if ($role_id !== 1) {
        $_SESSION['error_msg'] = "Bạn không có quyền truy cập chức năng này!";
        header('Location: /webbanhang/admin/index.php');
        exit();
    }
}

// 4. Kiểm tra tài khoản hiện tại có quyền truy cập module cụ thể hay không
function hasPermission($permCode) {
    global $role_id;

    // Admin tối cao (role_id = 1) luôn có toàn quyền
    if ($role_id === 1) {
        return true;
    }

    if ($role_id <= 0 || empty($permCode)) {
        return false;
    }

    $safe_code = str_replace("'", "\'", trim($permCode));

    // Truy vấn quyền được gán theo vai trò trong bảng Role_Permission
    $check = executeResult(
        "SELECT 1 
         FROM `Role_Permission` rp 
         JOIN `Permission` p ON rp.permission_id = p.id 
         WHERE rp.role_id = $role_id AND p.code = '$safe_code' 
         LIMIT 1",
        true
    );

    return !empty($check);
}

// 5. Chặn truy cập trực tiếp từ URL nếu không có quyền
function requirePermission($permCode) {
    if (!hasPermission($permCode)) {
        $_SESSION['error_msg'] = "Bạn không có quyền truy cập chức năng này!";
        header('Location: /webbanhang/admin/index.php');
        exit();
    }
}