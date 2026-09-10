<?php
session_start();
require_once('../../database/dbhelper.php');

$id = $_GET['id'] ?? '';

if (!empty($id)) {
    $id = intval($id);
    $sql = "DELETE FROM category WHERE id = $id";
    execute($sql);

    $_SESSION['success_msg'] = "Đã xóa vĩnh viễn danh mục!";
}

header('Location: trash.php');
die();