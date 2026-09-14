<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once __DIR__ . '/database/dbhelper.php';
require_once __DIR__ . '/config/vnpay_config.php';
require_once __DIR__ . '/config/mail_helper.php';

$vnp_SecureHash = $_GET['vnp_SecureHash'] ?? '';
$inputData = array();

foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $inputData[$key] = $value;
    }
}

unset($inputData['vnp_SecureHash']);
ksort($inputData);

$i = 0;
$hashData = "";
foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $hashData .= urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
}

$secureHash    = hash_hmac('sha512', $hashData, $vnp_HashSecret);
$order_id      = intval($_GET['vnp_TxnRef'] ?? 0);
$response_code = $_GET['vnp_ResponseCode'] ?? '';

// 1. Kiểm tra chữ ký bảo mật
if ($secureHash === $vnp_SecureHash) {
    $order = executeResult("SELECT * FROM `Orders` WHERE id = $order_id LIMIT 1", true);

    // TRƯỜNG HỢP 1: THANH TOÁN THÀNH CÔNG (ResponseCode == '00')
    if ($response_code === '00') {
        if ($order && (int)$order['status'] === 0) {
            // Cập nhật đơn sang Đang vận chuyển (status = 1) và ghi nhận thanh toán
            execute("UPDATE `Orders` SET status = 1, note = CONCAT(COALESCE(note, ''), ' | Da thanh toan qua VNPay thanh cong') WHERE id = $order_id");

            // Trừ số lượng tồn kho theo chi tiết đơn hàng
            $details = executeResult("SELECT variant_id, num, price, product_id FROM `Order_Details` WHERE order_id = $order_id");
            if (!empty($details)) {
                foreach ($details as $d) {
                    if (!empty($d['variant_id'])) {
                        execute("UPDATE `Product_Variant` SET stock_quantity = GREATEST(0, stock_quantity - {$d['num']}) WHERE id = {$d['variant_id']}");
                    }
                }
            }

            // Xóa sạch giỏ hàng của user sau khi đã trả tiền
            if (!empty($order['user_id'])) {
                execute("DELETE FROM `Cart` WHERE user_id = {$order['user_id']}");
            }

            // Gửi email hóa đơn xác nhận đã thanh toán
            $cust_email = trim($order['email'] ?? '');
            $cust_name  = trim($order['fullname'] ?? 'Quý khách');

            if (!empty($cust_email) && filter_var($cust_email, FILTER_VALIDATE_EMAIL)) {
                $subject = "Xác nhận thanh toán thành công #ORD-{$order_id} - " . MAIL_FROM_NAME;
                $rows = '';
                $stt = 1;

                foreach ($details as $it) {
                    $pRow = executeResult("SELECT title FROM `Product` WHERE id = {$it['product_id']} LIMIT 1", true);
                    $vRow = executeResult("SELECT color, cpu, ram, storage FROM `Product_Variant` WHERE id = {$it['variant_id']} LIMIT 1", true);
                    
                    $pTitle = htmlspecialchars($pRow['title'] ?? 'Sản phẩm');
                    $specs  = array_filter([$vRow['color'] ?? '', $vRow['cpu'] ?? '', $vRow['ram'] ?? '', $vRow['storage'] ?? '']);
                    $spec_str = !empty($specs) ? "<br><small style='color: #64748b;'>" . implode(' - ', $specs) . "</small>" : "";
                    $uPrice = number_format($it['price'], 0, ',', '.');
                    $lTotal = number_format($it['price'] * $it['num'], 0, ',', '.');

                    $rows .= "
                    <tr>
                        <td style='padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: center;'>{$stt}</td>
                        <td style='padding: 10px; border-bottom: 1px solid #e2e8f0;'><strong>{$pTitle}</strong>{$spec_str}</td>
                        <td style='padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: center;'>{$it['num']}</td>
                        <td style='padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: right;'>{$uPrice} đ</td>
                        <td style='padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: right; font-weight: bold; color: #dc2626;'>{$lTotal} đ</td>
                    </tr>";
                    $stt++;
                }

                $totalFmt = number_format($order['total_money'], 0, ',', '.');
                $bodyMail = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px; background: #ffffff;'>
                    <div style='text-align: center; border-bottom: 2px solid #2563eb; padding-bottom: 15px; margin-bottom: 20px;'>
                        <h2 style='color: #2563eb; margin: 0;'>" . MAIL_FROM_NAME . "</h2>
                        <p style='color: #10b981; margin: 5px 0 0 0; font-size: 14px; font-weight: bold;'>ĐÃ THANH TOÁN THÀNH CÔNG QUA VNPAY</p>
                    </div>
                    <p>Xin chào <strong>{$cust_name}</strong>,</p>
                    <p>Giao dịch thanh toán trực tuyến cho đơn hàng <strong>#ORD-{$order_id}</strong> của bạn đã hoàn tất thành công.</p>

                    <table style='width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 14px;'>
                        <thead>
                            <tr style='background: #f8fafc; color: #475569;'>
                                <th style='padding: 8px; border-bottom: 2px solid #cbd5e1; text-align: center;'>STT</th>
                                <th style='padding: 8px; border-bottom: 2px solid #cbd5e1; text-align: left;'>Sản phẩm</th>
                                <th style='padding: 8px; border-bottom: 2px solid #cbd5e1; text-align: center;'>SL</th>
                                <th style='padding: 8px; border-bottom: 2px solid #cbd5e1; text-align: right;'>Đơn giá</th>
                                <th style='padding: 8px; border-bottom: 2px solid #cbd5e1; text-align: right;'>Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$rows}
                        </tbody>
                    </table>

                    <div style='text-align: right; margin-top: 15px; padding-top: 10px; border-top: 2px solid #e2e8f0;'>
                        <span style='font-size: 15px; color: #475569;'>Tổng số tiền đã thanh toán: </span>
                        <strong style='font-size: 20px; color: #dc2626;'>{$totalFmt} đ</strong>
                    </div>
                </div>";

                sendCustomMail($cust_email, $cust_name, $subject, $bodyMail);
            }
        }

        header('Location: order-success.php?id=' . $order_id . '&vnpay=success');
        die();
    } 
    
    // TRƯỜNG HỢP 2: KHÁCH HỦY GIAO DỊCH HOẶC THANH TOÁN THẤT BẠI
    else {
        // Đánh dấu đơn hàng là Đã hủy (status = 3)
        execute("UPDATE `Orders` SET status = 3, note = CONCAT(COALESCE(note, ''), ' | Huy thanh toan VNPay (Ma loi: $response_code)') WHERE id = $order_id");

        // Giữ nguyên giỏ hàng, thông báo cho khách và đưa về lại trang cart.php
        $_SESSION['cart_toast'] = [
            'type'    => 'danger',
            'title'   => 'Thanh toán thất bại',
            'message' => 'Bạn chưa hoàn tất thanh toán VNPay. Sản phẩm vẫn được lưu trong giỏ hàng!'
        ];
        header('Location: cart.php');
        die();
    }
} else {
    die("Lỗi: Chữ ký bảo mật VNPay không hợp lệ!");
}