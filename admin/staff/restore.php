<?php
session_start();
require_once('../../database/dbhelper.php');

$id = $_GET['id'] ?? '';

if (!empty($id)) {
    $id = intval($id);
    $sql = "UPDATE user SET deleted = 0 WHERE id = $id";
    execute($sql);

    $_SESSION['success_msg'] = "Khôi phục nhân viên thành công!";
}

header('Location: trash.php');
die();