<?php
session_start();
require_once('../../database/dbhelper.php');

$id = $_GET['id'] ?? '';

if (!empty($id)) {
    $id = intval($id);
    $sql = "UPDATE category SET deleted = 1 WHERE id = $id";
    execute($sql);

    $_SESSION['success_msg'] = "Đã chuyển danh mục vào thùng rác thành công!";
}

header('Location: index.php');
die();