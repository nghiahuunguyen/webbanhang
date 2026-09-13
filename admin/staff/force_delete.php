<?php
session_start();
require_once('../../database/dbhelper.php');

$id = $_GET['id'] ?? '';

if (!empty($id)) {
    $id = intval($id);
    $sql = "DELETE FROM user WHERE id = $id";
    execute($sql);

    $_SESSION['success_msg'] = "Đã xóa vĩnh viễn nhân viên!";
}

header('Location: trash.php');
die();