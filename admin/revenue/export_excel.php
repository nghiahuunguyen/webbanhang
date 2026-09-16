<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once('../../database/dbhelper.php');
require_once('../check_role.php');

// Chỉ Admin mới có quyền truy cập[cite: 2]
requireAdmin();

$filter_type = $_GET['type'] ?? 'month';
$filter_date = $_GET['date'] ?? date('Y-m-d');
$filter_month = $_GET['month'] ?? date('Y-m');
$filter_year = $_GET['year'] ?? date('Y');

$where = "status = 2"; // Chỉ tính đơn đã hoàn thành
$file_label = "";

if ($filter_type === 'day' && !empty($filter_date)) {
    $where .= " AND DATE(order_date) = '$filter_date'";
    $file_label = "Ngay_" . date('d-m-Y', strtotime($filter_date));
} elseif ($filter_type === 'year' && !empty($filter_year)) {
    $where .= " AND YEAR(order_date) = '$filter_year'";
    $file_label = "Nam_" . $filter_year;
} else {
    $where .= " AND DATE_FORMAT(order_date, '%Y-%m') = '$filter_month'";
    $file_label = "Thang_" . date('m-Y', strtotime($filter_month . '-01'));
}

$orders = executeResult("SELECT * FROM `Orders` WHERE $where ORDER BY id DESC");

$filename = "Bao_Cao_Doanh_Thu_" . $file_label . ".csv";

// Cấu hình Header để trình duyệt tải về file Excel (CSV)
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Chèn UTF-8 BOM để Excel hiển thị tiếng Việt không bị lỗi font
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Dòng tiêu đề file
fputcsv($output, ['BÁO CÁO DOANH THU BÁN HÀNG']);
fputcsv($output, ['Thời gian lọc:', $file_label]);
fputcsv($output, ['Ngày xuất file:', date('d/m/Y H:i:s')]);
fputcsv($output, []); // Dòng trống

// Header cột
fputcsv($output, ['Mã Đơn', 'Khách Hàng', 'Số Điện Thoại', 'Địa Chỉ', 'Ngày Đặt', 'Trạng Thái', 'Tổng Tiền (VNĐ)']);

$total_money_sum = 0;
if (!empty($orders)) {
    foreach ($orders as $ord) {
        $total_money_sum += (int)$ord['total_money'];
        fputcsv($output, [
            '#ORD-' . $ord['id'],
            $ord['fullname'],
            "'" . $ord['phone_number'], // Giữ số 0 đầu điện thoại
            $ord['address'],
            date('d/m/Y H:i', strtotime($ord['order_date'])),
            'Hoàn thành',
            $ord['total_money']
        ]);
    }
}

// Tổng cộng
fputcsv($output, []);
fputcsv($output, ['', '', '', '', '', 'TỔNG DOANH THU:', $total_money_sum]);

fclose($output);
exit();