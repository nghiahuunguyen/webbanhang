<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Nạp thủ công các file mã nguồn PHPMailer
require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'storeelectronics457@gmail.com');
define('MAIL_PASSWORD', 'bity jzxp zpbq tvep');
define('MAIL_FROM_NAME', 'CommerceOS Store');

function sendCustomMail($toEmail, $toName, $subject, $bodyContent) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_USERNAME, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyContent;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Lỗi gửi mail: " . $mail->ErrorInfo);
        return false;
    }
}

function sendOrderInvoiceMail($toEmail, $toName, $orderId, $cartItems, $totalMoney) {
    if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL) || $toEmail === 'pos@cuahang.local') {
        return false;
    }

    $subject = "Xác nhận đơn hàng #ORD-{$orderId} - " . MAIL_FROM_NAME;

    $rows = '';
    $stt = 1;
    foreach ($cartItems as $item) {
        $pTitle = htmlspecialchars($item['title'] ?? $item['product_title'] ?? 'Sản phẩm');
        $pSpec  = htmlspecialchars($item['spec'] ?? '');
        $specText = !empty($pSpec) ? "<br><small style='color: #6c757d;'>{$pSpec}</small>" : '';
        $price = number_format((int)($item['final_price'] ?? $item['price'] ?? 0), 0, ',', '.');
        $lineTotal = number_format((int)($item['final_price'] ?? $item['price'] ?? 0) * (int)$item['num'], 0, ',', '.');

        $rows .= "
        <tr>
            <td style='padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: center;'>{$stt}</td>
            <td style='padding: 10px; border-bottom: 1px solid #e2e8f0;'><strong>{$pTitle}</strong>{$specText}</td>
            <td style='padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: center;'>{$item['num']}</td>
            <td style='padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: right;'>{$price} đ</td>
            <td style='padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: right; font-weight: bold; color: #e53e3e;'>{$lineTotal} đ</td>
        </tr>";
        $stt++;
    }

    $totalFmt = number_format($totalMoney, 0, ',', '.');

    $html = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #edf2f7; border-radius: 12px; background: #ffffff;'>
        <div style='text-align: center; border-bottom: 2px solid #2b6cb0; padding-bottom: 15px; margin-bottom: 20px;'>
            <h2 style='color: #2b6cb0; margin: 0;'>" . MAIL_FROM_NAME . "</h2>
            <p style='color: #718096; margin: 5px 0 0 0; font-size: 14px;'>Hóa đơn mua hàng tại quầy / trực tuyến</p>
        </div>

        <p>Xin chào <strong>{$toName}</strong>,</p>
        <p>Cảm ơn bạn đã mua sắm! Đơn hàng <strong>#ORD-{$orderId}</strong> của bạn đã được khởi tạo thành công:</p>

        <table style='width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 14px;'>
            <thead>
                <tr style='background: #f7fafc; color: #4a5568;'>
                    <th style='padding: 8px; border-bottom: 2px solid #cbd5e0; text-align: center;'>STT</th>
                    <th style='padding: 8px; border-bottom: 2px solid #cbd5e0; text-align: left;'>Mặt hàng</th>
                    <th style='padding: 8px; border-bottom: 2px solid #cbd5e0; text-align: center;'>SL</th>
                    <th style='padding: 8px; border-bottom: 2px solid #cbd5e0; text-align: right;'>Đơn giá</th>
                    <th style='padding: 8px; border-bottom: 2px solid #cbd5e0; text-align: right;'>Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                {$rows}
            </tbody>
        </table>

        <div style='text-align: right; margin-top: 15px; padding-top: 10px; border-top: 2px solid #e2e8f0;'>
            <span style='font-size: 15px; color: #4a5568;'>Tổng tiền thanh toán: </span>
            <strong style='font-size: 20px; color: #e53e3e;'>{$totalFmt} đ</strong>
        </div>

        <div style='margin-top: 25px; padding: 12px; background: #f7fafc; border-radius: 8px; font-size: 12px; color: #718096; text-align: center;'>
            Nếu có bất kỳ thắc mắc nào về sản phẩm hoặc bảo hành, vui lòng giữ lại email này để đối chiếu.
        </div>
    </div>";

    return sendCustomMail($toEmail, $toName, $subject, $html);
}