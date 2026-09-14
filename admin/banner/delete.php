<?php
session_start();
require_once('../../database/dbhelper.php');
require_once('../check_role.php');

// Cho phép Admin (1) và Nhân viên (3) truy cập
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role_id'], [1, 3])) {
    header('Location: ../auth/login.php');
    die();
}

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    $sql = "UPDATE `Banner` SET deleted = 1 WHERE id = $id";
    execute($sql);

    $_SESSION['success_msg'] = "Đã chuyển banner vào thùng rác thành công!";
}

header('Location: index.php');
die();