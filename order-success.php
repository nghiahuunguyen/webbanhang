<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once __DIR__ . '/database/dbhelper.php';
require_once __DIR__ . '/config/mail_helper.php';
require_once __DIR__ . '/config/vnpay_config.php';

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header('Location: login.php?redirect=' . urlencode('checkout.php'));
    die();
}

$user = $_SESSION['user'];
$user_id = (int)$user['id'];

// 2. Lấy danh sách sản phẩm trong giỏ hàng
$sql_cart = "SELECT c.id AS cart_id, c.num, 
                    p.id AS product_id, p.title, p.thumbnail, 
                    v.id AS variant_id, v.sku, v.color, v.cpu, v.ram, v.storage, v.gpu, 
                    v.price, v.discount, (v.price - v.discount) AS final_price, 
                    v.stock_quantity
             FROM `Cart` c
             JOIN `Product` p ON c.product_id = p.id
             JOIN `Product_Variant` v ON c.variant_id = v.id
             WHERE c.user_id = $user_id AND v.deleted = 0 AND p.deleted = 0
             ORDER BY c.id DESC";
$cart_items = executeResult($sql_cart);

if (empty($cart_items)) {
    header('Location: cart.php');
    die();
}

$error_msg = '';
$total_money = 0;
foreach ($cart_items as $item) {
    $total_money += (int)$item['final_price'] * (int)$item['num'];
}

// 3. Xử lý đặt hàng khi submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    $fullname       = trim($_POST['fullname'] ?? '');
    $phone_number   = trim($_POST['phone_number'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $address        = trim($_POST['address'] ?? '');
    $note           = trim($_POST['note'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'cod';

    if (empty($fullname) || empty($phone_number) || empty($address)) {
        $error_msg = 'Vui lòng điền đầy đủ Họ tên, Số điện thoại và Địa chỉ nhận hàng!';
    } else {
        // Kiểm tra tồn kho
        $stock_error = false;
        foreach ($cart_items as $item) {
            $check_v = executeResult("SELECT stock_quantity FROM `Product_Variant` WHERE id = {$item['variant_id']} LIMIT 1", true);
            $current_stock = (int)($check_v['stock_quantity'] ?? 0);
            if ($item['num'] > $current_stock) {
                $stock_error = true;
                $error_msg = "Sản phẩm '{$item['title']}' không đủ hàng tồn kho (chỉ còn {$current_stock} chiếc)!";
                break;
            }
        }

        if (!$stock_error) {
            $safe_fullname = str_replace("'", "\'", $fullname);
            $safe_phone    = str_replace("'", "\'", $phone_number);
            $safe_email    = str_replace("'", "\'", $email);
            $safe_address  = str_replace("'", "\'", $address);
            $payment_label = ($payment_method === 'vnpay') ? 'VNPay (Chờ thanh toán)' : 'COD (Tiền mặt)';
            $safe_note     = str_replace("'", "\'", ($note ? $note . " | " : "") . "PTTT: " . $payment_label);

            // Tạo đơn hàng ở trạng thái 0 (Chờ duyệt / Chờ thanh toán)
            $sql_order = "INSERT INTO `Orders` (user_id, fullname, email, phone_number, address, note, order_date, status, total_money) 
                          VALUES ($user_id, '$safe_fullname', '$safe_email', '$safe_phone', '$safe_address', '$safe_note', NOW(), 0, $total_money)";
            $order_id = executeReturnId($sql_order);

            if ($order_id > 0) {
                // Luôn lưu chi tiết đơn hàng trước
                foreach ($cart_items as $item) {
                    $p_id       = (int)$item['product_id'];
                    $v_id       = (int)$item['variant_id'];
                    $p_price    = (int)$item['final_price'];
                    $p_num      = (int)$item['num'];
                    $line_total = $p_price * $p_num;

                    execute("INSERT INTO `Order_Details` (order_id, product_id, variant_id, price, num, total_money) 
                             VALUES ($order_id, $p_id, $v_id, $p_price, $p_num, $line_total)");
                }

                // NHÁNH 1: THANH TOÁN VNPAY -> CHƯA TRỪ KHO, CHƯA GỬI MAIL, CHUYỂN SANG CỔNG VNPAY
                if ($payment_method === 'vnpay') {
                    $vnp_TxnRef = (string)$order_id;
                    $vnp_OrderInfo = "Thanh toan don hang ORD-" . $order_id;
                    $vnp_OrderType = "billpayment";
                    $vnp_Amount = $total_money * 100;
                    $vnp_Locale = "vn";
                    $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];

                    $inputData = array(
                        "vnp_Version"    => "2.1.0",
                        "vnp_TmnCode"    => $vnp_TmnCode,
                        "vnp_Amount"     => $vnp_Amount,
                        "vnp_Command"    => "pay",
                        "vnp_CreateDate" => date('YmdHis'),
                        "vnp_CurrCode"   => "VND",
                        "vnp_IpAddr"     => $vnp_IpAddr,
                        "vnp_Locale"     => $vnp_Locale,
                        "vnp_OrderInfo"  => $vnp_OrderInfo,
                        "vnp_OrderType"  => $vnp_OrderType,
                        "vnp_ReturnUrl"  => $vnp_Returnurl,
                        "vnp_TxnRef"     => $vnp_TxnRef,
                        "vnp_ExpireDate" => date('YmdHis', strtotime('+15 minutes'))
                    );

                    ksort($inputData);
                    $query = "";
                    $i = 0;
                    $hashdata = "";

                    foreach ($inputData as $key => $value) {
                        if ($i == 1) {
                            $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
                        } else {
                            $hashdata .= urlencode($key) . "=" . urlencode($value);
                            $i = 1;
                        }
                        $query .= urlencode($key) . "=" . urlencode($value) . '&';
                    }

                    $vnp_Url_Payment = $vnp_Url . "?" . $query;
                    if (isset($vnp_HashSecret)) {
                        $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
                        $vnp_Url_Payment .= 'vnp_SecureHash=' . $vnpSecureHash;
                    }

                    header('Location: ' . $vnp_Url_Payment);
                    die();
                } 
                
                // NHÁNH 2: THANH TOÁN KHI NHẬN HÀNG (COD) -> TRỪ KHO, XÓA GIỎ, GỬI MAIL NGAY
                else {
                    foreach ($cart_items as $item) {
                        $v_id  = (int)$item['variant_id'];
                        $p_num = (int)$item['num'];
                        execute("UPDATE `Product_Variant` SET stock_quantity = GREATEST(0, stock_quantity - $p_num) WHERE id = $v_id");
                    }

                    execute("DELETE FROM `Cart` WHERE user_id = $user_id");

                    if (!empty($safe_email) && filter_var($safe_email, FILTER_VALIDATE_EMAIL)) {
                        $subject = "Xác nhận đơn hàng #ORD-{$order_id} - " . MAIL_FROM_NAME;
                        $rows = '';
                        $stt = 1;
                        foreach ($cart_items as $it) {
                            $pTitle = htmlspecialchars($it['title']);
                            $specs  = array_filter([$it['color'], $it['cpu'], $it['ram'], $it['storage']]);
                            $spec_str = !empty($specs) ? "<br><small style='color: #64748b;'>" . implode(' - ', $specs) . "</small>" : "";
                            $uPrice = number_format($it['final_price'], 0, ',', '.');
                            $lTotal = number_format($it['final_price'] * $it['num'], 0, ',', '.');

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

                        $totalFmt = number_format($total_money, 0, ',', '.');
                        $bodyMail = "
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px; background: #ffffff;'>
                            <div style='text-align: center; border-bottom: 2px solid #2563eb; padding-bottom: 15px; margin-bottom: 20px;'>
                                <h2 style='color: #2563eb; margin: 0;'>" . MAIL_FROM_NAME . "</h2>
                                <p style='color: #64748b; margin: 5px 0 0 0; font-size: 14px;'>Đơn hàng trực tuyến (COD)</p>
                            </div>
                            <p>Xin chào <strong>{$safe_fullname}</strong>,</p>
                            <p>Đơn hàng <strong>#ORD-{$order_id}</strong> của bạn đã được khởi tạo thành công và đang chờ xác nhận giao hàng.</p>
                            
                            <div style='background: #f8fafc; padding: 12px; border-radius: 6px; margin: 15px 0; font-size: 13px;'>
                                <div><strong>Người nhận:</strong> {$safe_fullname} - {$safe_phone}</div>
                                <div><strong>Địa chỉ:</strong> {$safe_address}</div>
                            </div>

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
                                <span style='font-size: 15px; color: #475569;'>Tổng thanh toán khi nhận hàng: </span>
                                <strong style='font-size: 20px; color: #dc2626;'>{$totalFmt} đ</strong>
                            </div>
                        </div>";

                        sendCustomMail($safe_email, $safe_fullname, $subject, $bodyMail);
                    }

                    header('Location: order-success.php?id=' . $order_id);
                    die();
                }
            } else {
                $error_msg = 'Lỗi hệ thống: Không thể xử lý đơn hàng lúc này!';
            }
        }
    }
}

$page_title = 'Thanh Toán Đơn Hàng';
include 'includes/header.php'; 
?>

<main class="container my-5">
  <!-- Toast lỗi góc phải -->
  <?php if (!empty($error_msg)): ?>
    <div id="toast-wrapper" style="position: fixed; top: 25px; right: 25px; z-index: 99999; min-width: 320px; transition: all 0.5s ease;">
      <div class="toast show shadow-lg border-0" role="alert">
        <div class="toast-header border-0 bg-white py-2 px-3">
          <strong class="mr-auto text-danger font-weight-bold"><i class="fas fa-exclamation-circle mr-1"></i> Lỗi Đặt Hàng</strong>
          <button type="button" class="ml-2 mb-1 close" onclick="document.getElementById('toast-wrapper').remove()">&times;</button>
        </div>
        <div class="toast-body bg-white rounded-bottom px-3 pb-3 pt-0 text-dark font-weight-bold">
          <?= htmlspecialchars($error_msg) ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="row">
    <!-- Cột trái: Form thông tin & Phương thức thanh toán -->
    <div class="col-lg-7 mb-4">
      <div class="card border-0 rounded-2xl shadow-soft p-4 p-md-5 bg-white">
        <h4 class="font-weight-bold text-dark mb-4">
          <i class="fas fa-shipping-fast text-primary mr-2"></i> Thông Tin Giao Hàng
        </h4>

        <form action="" method="POST" id="checkoutForm">
          <input type="hidden" name="action" value="place_order">

          <div class="form-group mb-3">
            <label class="font-weight-semibold small text-muted">Họ và tên người nhận <span class="text-danger">*</span></label>
            <input type="text" name="fullname" class="form-control bg-light border-0 py-2" 
                   value="<?= htmlspecialchars($_POST['fullname'] ?? $user['fullname']) ?>" required>
          </div>

          <div class="form-row">
            <div class="form-group col-md-6 mb-3">
              <label class="font-weight-semibold small text-muted">Số điện thoại nhận hàng <span class="text-danger">*</span></label>
              <input type="tel" name="phone_number" class="form-control bg-light border-0 py-2" 
                     value="<?= htmlspecialchars($_POST['phone_number'] ?? $user['phone_number']) ?>" required>
            </div>
            <div class="form-group col-md-6 mb-3">
              <label class="font-weight-semibold small text-muted">Địa chỉ Email (nhận hóa đơn)</label>
              <input type="email" name="email" class="form-control bg-light border-0 py-2" 
                     value="<?= htmlspecialchars($_POST['email'] ?? $user['email']) ?>">
            </div>
          </div>

          <div class="form-group mb-3">
            <label class="font-weight-semibold small text-muted">Địa chỉ nhận hàng chi tiết <span class="text-danger">*</span></label>
            <textarea name="address" rows="2" class="form-control bg-light border-0" 
                      placeholder="Số nhà, ngõ/đường, phường/xã, quận/huyện..." required><?= htmlspecialchars($_POST['address'] ?? $user['address']) ?></textarea>
          </div>

          <div class="form-group mb-4">
            <label class="font-weight-semibold small text-muted">Ghi chú đơn hàng (nếu có)</label>
            <textarea name="note" rows="2" class="form-control bg-light border-0" 
                      placeholder="Chỉ dẫn giao hàng, thời gian nhận máy..."><?= htmlspecialchars($_POST['note'] ?? '') ?></textarea>
          </div>

          <h5 class="font-weight-bold text-dark mb-3">Phương Thức Thanh Toán</h5>
          <div class="border rounded-lg p-3 mb-3 bg-light">
            <div class="custom-control custom-radio mb-3">
              <input type="radio" id="paymentCod" name="payment_method" value="cod" class="custom-control-input" checked>
              <label class="custom-control-label font-weight-bold text-dark" for="paymentCod">
                <i class="fas fa-hand-holding-usd text-success mr-1"></i> Thanh toán khi nhận hàng (COD)
              </label>
              <small class="d-block text-muted ml-4">Quý khách nhận máy kiểm tra rồi mới thanh toán tiền mặt cho shipper.</small>
            </div>
            <hr class="my-2">
            <div class="custom-control custom-radio">
              <input type="radio" id="paymentVnpay" name="payment_method" value="vnpay" class="custom-control-input">
              <label class="custom-control-label font-weight-bold text-dark" for="paymentVnpay">
                <i class="fas fa-credit-card text-primary mr-1"></i> Cổng thanh toán VNPay Sandbox (ATM / QR Code)
              </label>
              <small class="d-block text-muted ml-4">Chuyển hướng sang cổng VNPay Sandbox để thanh toán thử nghiệm trực tuyến.</small>
            </div>
          </div>

          <button type="submit" class="btn btn-primary btn-block rounded-pill-custom py-3 font-weight-bold shadow-sm" style="font-size: 1.05rem;">
            XÁC NHẬN ĐẶT HÀNG &rarr;
          </button>
        </form>
      </div>
    </div>

    <!-- Cột phải: Tóm tắt đơn hàng -->
    <div class="col-lg-5">
      <div class="card border-0 rounded-2xl shadow-soft p-4 bg-white mb-4 position-sticky" style="top: 20px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="font-weight-bold text-dark mb-0">Đơn Hàng Của Bạn</h5>
          <a href="cart.php" class="small text-primary font-weight-bold text-decoration-none">Sửa giỏ hàng</a>
        </div>

        <div class="order-items-scroll pr-1 mb-3" style="max-height: 320px; overflow-y: auto;">
          <?php foreach ($cart_items as $it): 
              $specs = array_filter([$it['color'], $it['cpu'], $it['ram'], $it['storage']]);
              $spec_str = !empty($specs) ? implode(' · ', $specs) : 'Bản tiêu chuẩn';
              $img = (!empty($it['thumbnail']) && file_exists($it['thumbnail'])) 
                     ? htmlspecialchars($it['thumbnail']) 
                     : 'https://via.placeholder.com/60x60?text=SP';
          ?>
            <div class="d-flex align-items-center py-2 border-bottom">
              <img src="<?= $img ?>" class="rounded border mr-3 p-1 flex-shrink-0" width="55" height="55" style="object-fit: contain;">
              <div class="flex-grow-1 mr-2" style="min-width: 0;">
                <div class="font-weight-bold text-dark text-truncate small" title="<?= htmlspecialchars($it['title']) ?>">
                  <?= htmlspecialchars($it['title']) ?>
                </div>
                <div class="text-muted small text-truncate"><?= htmlspecialchars($spec_str) ?></div>
                <div class="small font-weight-bold text-primary">SL: <?= $it['num'] ?></div>
              </div>
              <div class="text-right font-weight-bold text-danger small flex-shrink-0">
                <?= number_format($it['final_price'] * $it['num'], 0, ',', '.') ?> ₫
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="d-flex justify-content-between py-2 border-bottom text-muted small">
          <span>Tạm tính tiền hàng:</span>
          <strong class="text-dark"><?= number_format($total_money, 0, ',', '.') ?> ₫</strong>
        </div>
        <div class="d-flex justify-content-between py-2 border-bottom text-muted small">
          <span>Phí vận chuyển:</span>
          <span class="text-success font-weight-bold">Miễn phí giao hàng</span>
        </div>

        <div class="d-flex justify-content-between py-3 border-top align-items-center">
          <span class="font-weight-bold text-dark h6 mb-0">TỔNG THANH TOÁN:</span>
          <span class="h4 font-weight-bold text-danger mb-0"><?= number_format($total_money, 0, ',', '.') ?> ₫</span>
        </div>

        <div class="alert alert-light border rounded-lg small text-muted mb-0 mt-2">
          <i class="fas fa-shield-alt text-success mr-1"></i> Giao dịch bảo mật qua mã hóa SSL 256-bit và cổng VNPay.
        </div>
      </div>
    </div>
  </div>
</main>

<script>
window.addEventListener('load', function() {
  var toastBox = document.getElementById('toast-wrapper');
  if (toastBox) {
    setTimeout(function() {
      toastBox.style.opacity = '0';
      toastBox.style.transform = 'translateX(50px)';
      setTimeout(function() { toastBox.remove(); }, 500);
    }, 4000);
  }
});
</script>

<?php 
include 'includes/footer.php'; 
?>