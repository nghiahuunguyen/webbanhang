<?php
session_start();

// 1. Xóa thông tin đăng nhập trong session
if (isset($_SESSION['user'])) {
    unset($_SESSION['user']);
}

// Hủy toàn bộ session
session_destroy();

// 2. Chuyển hướng về trang index.php
header('Location: index.php');
exit();
?>