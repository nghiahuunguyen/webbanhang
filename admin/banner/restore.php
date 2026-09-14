<?php
session_start();
require_once('../../database/dbhelper.php');
require_once('../check_role.php');

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role_id'], [1, 3])) {
    header('Location: ../auth/login.php');
    die();
}

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    $sql = "UPDATE `Banner` SET deleted = 0 WHERE id = $id";
    execute($sql);

    $_SESSION['success_msg'] = "Khôi phục banner thành công!";
}

header('Location: trash.php');
die();