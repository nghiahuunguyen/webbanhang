<?php
session_start();
require_once('../../database/dbhelper.php');
require_once('../check_role.php');

requireAdmin();

$id = intval($_GET['id'] ?? 0);

// Không được xóa Admin tối cao (1) hoặc Khách hàng mặc định (2)
if ($id > 2) {
    // Đưa các tài khoản đang giữ vai trò này về vai trò Nhân viên mặc định (3) hoặc gán null
    execute("UPDATE `User` SET role_id = 3 WHERE role_id = $id");
    // Xóa vai trò (Role_Permission tự xóa theo ràng buộc CASCADE)
    execute("DELETE FROM `Role` WHERE id = $id");

    $_SESSION['success_msg'] = "Đã xóa vai trò thành công!";
}

header('Location: index.php');
exit();