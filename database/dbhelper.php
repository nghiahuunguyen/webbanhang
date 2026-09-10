<?php
require_once('config.php');

function execute($sql) {
    // 1. Mở kết nối (đúng thứ tự: host, username, password, database)
    $conn = mysqli_connect(HOST, USERNAME, PASSWORD, DATABASE);
    // 2. Set charset có truyền $conn và dấu chấm phẩy
    mysqli_set_charset($conn, 'utf8');
    // 3. Thực thi query
    $result = mysqli_query($conn, $sql);
    // 4. Đóng kết nối
    mysqli_close($conn);
}
//SQL: select-> lay du lieu dau ra(select danh sach ban ghi,lay 1 ban ghi)
function executeResult($sql,$isSingle=false) {
    $data=null;
    $conn = mysqli_connect(HOST, USERNAME, PASSWORD, DATABASE);
    mysqli_set_charset($conn, 'uf8');
    //query
    $result = mysqli_query($conn, $sql);
    if ($isSingle) {
        $data = mysqli_fetch_array($result,1);
    } else {
        $data=[];
        while (($row = mysqli_fetch_array($result,1))!=null) {
            $data[]=$row;
        }
    }
    
    mysqli_close($conn);
    return $data;
    
}
