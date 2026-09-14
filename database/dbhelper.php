<?php
require_once('config.php');

function execute($sql) {
    $conn = mysqli_connect(HOST, USERNAME, PASSWORD, DATABASE);
    if (!$conn) {
        die("Kết nối CSDL thất bại: " . mysqli_connect_error());
    }
    mysqli_set_charset($conn, 'utf8');

    $result = mysqli_query($conn, $sql);
    if ($result === false) {
        die("Lỗi SQL (execute): " . mysqli_error($conn) . "<br>Câu lệnh: " . $sql);
    }

    mysqli_close($conn);
}

function executeResult($sql, $isSingle = false) {
    $conn = mysqli_connect(HOST, USERNAME, PASSWORD, DATABASE);
    if (!$conn) {
        die("Kết nối CSDL thất bại: " . mysqli_connect_error());
    }
    mysqli_set_charset($conn, 'utf8'); // Đã sửa từ 'uf8' thành 'utf8'

    $result = mysqli_query($conn, $sql);
    if ($result === false) {
        die("Lỗi SQL (executeResult): " . mysqli_error($conn) . "<br>Câu lệnh: " . $sql);
    }

    $data = null;
    if ($isSingle) {
        $data = mysqli_fetch_array($result, 1);
    } else {
        $data = [];
        while ($row = mysqli_fetch_array($result, 1)) {
            $data[] = $row;
        }
    }

    mysqli_close($conn);
    return $data;
}

// Bổ sung hàm lấy id tự tăng khi tạo đơn hàng mới
function executeReturnId($sql) {
    $conn = mysqli_connect(HOST, USERNAME, PASSWORD, DATABASE);
    if (!$conn) {
        die("Kết nối CSDL thất bại: " . mysqli_connect_error());
    }
    mysqli_set_charset($conn, 'utf8');

    $result = mysqli_query($conn, $sql);
    if ($result === false) {
        die("Lỗi SQL (executeReturnId): " . mysqli_error($conn) . "<br>Câu lệnh: " . $sql);
    }

    $insert_id = mysqli_insert_id($conn);
    mysqli_close($conn);
    return $insert_id;
}