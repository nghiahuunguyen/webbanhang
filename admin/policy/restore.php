<?php
session_start();
require_once('../../database/dbhelper.php');
require_once('../check_role.php');

if (!isset($_SESSION['user']) || (int)$_SESSION['user']['role_id'] !== 1) {
    header('Location: ../dashboard/index.php');
    die();
}

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    execute("UPDATE `Policy` SET deleted = 0 WHERE id = $id");
    $_SESSION['policy_msg'] = "Khôi phục bài viết chính sách thành công!";
}

header('Location: trash.php');
die();