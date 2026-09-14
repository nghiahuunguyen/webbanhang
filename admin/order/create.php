<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once __DIR__ . '/../../database/dbhelper.php';
require_once __DIR__ . '/../check_role.php';
require_once __DIR__ . '/../../config/mail_helper.php';
require_once __DIR__ . '/../../config/vnpay_config.php';

// Kiểm tra quyền: Admin (1) hoặc Nhân viên (3)
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role_id'], [1, 3])) {
    header('Location: ../auth/login.php');
    die();
}

// ENDPOINT AJAX: Tra cứu thông tin khách hàng qua SĐT
if (isset($_GET['action']) && $_GET['action'] === 'lookup_customer') {
    header('Content-Type: application/json; charset=utf-8');
    $phone = trim($_GET['phone'] ?? '');

    if (strlen($phone) >= 9) {
        $safe_phone = str_replace("'", "\'", $phone);

        // 1. Tìm trong bảng User trước
        $sql_user = "SELECT fullname, email, address 
                     FROM `User` 
                     WHERE phone_number = '$safe_phone' AND deleted = 0 
                     LIMIT 1";
        $customer = executeResult($sql_user, true);

        // 2. Nếu không thấy trong User, tìm đơn gần nhất trong Orders
        if (!$customer) {
            $sql_order_hist = "SELECT fullname, email, address 
                               FROM `Orders` 
                               WHERE phone_number = '$safe_phone' 
                               ORDER BY id DESC 
                               LIMIT 1";
            $customer = executeResult($sql_order_hist, true);
        }

        if ($customer) {
            echo json_encode([
                'success'  => true,
                'fullname' => $customer['fullname'] ?? '',
                'email'    => $customer['email'] ?? '',
                'address'  => $customer['address'] ?? ''
            ]);
            exit;
        }
    }

    echo json_encode(['success' => false]);
    exit;
}

$error = '';
$success_msg = $_SESSION['success_msg'] ?? '';
unset($_SESSION['success_msg']);

// Xử lý tạo đơn thanh toán tại quầy
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    $fullname       = trim($_POST['fullname'] ?? '') ?: 'Khách mua lẻ tại quầy';
    $phone_number   = trim($_POST['phone_number'] ?? '') ?: '0000000000';
    $email          = trim($_POST['email'] ?? '') ?: 'pos@cuahang.local';
    $address        = trim($_POST['address'] ?? '') ?: 'Mua tại quầy';
    $note           = trim($_POST['note'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'cash';
    
    $cart_json    = $_POST['cart_data'] ?? '[]';
    $cart_items   = json_decode($cart_json, true);

    if (empty($cart_items)) {
        $error = 'Giỏ hàng đang trống! Vui lòng chọn sản phẩm.';
    } else {
        $user_id = (int)$_SESSION['user']['id'];
        $total_money = 0;

        foreach ($cart_items as $item) {
            $total_money += (int)$item['final_price'] * (int)$item['num'];
        }

        $safe_fullname = str_replace("'", "\'", $fullname);
        $safe_phone    = str_replace("'", "\'", $phone_number);
        $safe_email    = str_replace("'", "\'", $email);
        $safe_address  = str_replace("'", "\'", $address);
        $payment_label = ($payment_method === 'vnpay') ? 'POS - VNPay (Chờ thanh toán)' : 'POS - Tiền mặt tại quầy';
        $safe_note     = str_replace("'", "\'", ($note ? $note . " | " : "") . $payment_label);

        // Nếu là tiền mặt: trạng thái = 2 (Hoàn thành ngay). Nếu VNPay: trạng thái = 0 (Chờ duyệt/chờ thanh toán)
        $initial_status = ($payment_method === 'vnpay') ? 0 : 2;

        $sql_order = "INSERT INTO `Orders` (user_id, fullname, email, phone_number, address, note, order_date, status, total_money) 
                      VALUES ($user_id, '$safe_fullname', '$safe_email', '$safe_phone', '$safe_address', '$safe_note', NOW(), $initial_status, $total_money)";
        $order_id = executeReturnId($sql_order);

        if ($order_id > 0) {
            foreach ($cart_items as $item) {
                $p_id       = (int)$item['product_id'];
                $v_id       = (int)$item['variant_id'];
                $p_price    = (int)$item['final_price'];
                $p_num      = (int)$item['num'];
                $line_total = $p_price * $p_num;

                // Lưu chi tiết đơn hàng
                $sql_detail = "INSERT INTO `Order_Details` (order_id, product_id, variant_id, price, num, total_money) 
                               VALUES ($order_id, $p_id, $v_id, $p_price, $p_num, $line_total)";
                execute($sql_detail);

                // Nếu là tiền mặt: Giảm số lượng kho ngay
                if ($payment_method !== 'vnpay') {
                    $sql_stock = "UPDATE `Product_Variant` 
                                  SET stock_quantity = GREATEST(0, stock_quantity - $p_num) 
                                  WHERE id = $v_id";
                    execute($sql_stock);
                }
            }

            // NHÁNH 1: THANH TOÁN VNPAY -> CHUYỂN CỔNG VNPAY NGAY
            if ($payment_method === 'vnpay') {
                $vnp_TxnRef = (string)$order_id;
                $vnp_OrderInfo = "Thanh toan don POS ORD-" . $order_id;
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
            
            // NHÁNH 2: TIỀN MẶT -> GỬI EMAIL VÀ CHUYỂN ĐẾN XEM CHI TIẾT
            else {
                if (!empty($safe_email) && filter_var($safe_email, FILTER_VALIDATE_EMAIL) && $safe_email !== 'pos@cuahang.local') {
                    $subject = "Xác nhận đơn hàng #ORD-" . $order_id . " - " . MAIL_FROM_NAME;
                    $rows = '';
                    $stt = 1;
                    foreach ($cart_items as $it) {
                        $pTitle = htmlspecialchars($it['title'] ?? 'Sản phẩm');
                        $pSpec  = htmlspecialchars($it['spec'] ?? '');
                        $specText = !empty($pSpec) ? "<br><small style='color: #64748b;'>" . $pSpec . "</small>" : '';
                        $unitPrice = number_format((int)$it['final_price'], 0, ',', '.');
                        $itemTotal = number_format((int)$it['final_price'] * (int)$it['num'], 0, ',', '.');

                        $rows .= "
                        <tr>
                            <td style='padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: center;'>" . $stt . "</td>
                            <td style='padding: 10px; border-bottom: 1px solid #e2e8f0;'><strong>" . $pTitle . "</strong>" . $specText . "</td>
                            <td style='padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: center;'>" . $it['num'] . "</td>
                            <td style='padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: right;'>" . $unitPrice . " đ</td>
                            <td style='padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: right; font-weight: bold; color: #e11d48;'>" . $itemTotal . " đ</td>
                        </tr>";
                        $stt++;
                    }

                    $totalFmt = number_format($total_money, 0, ',', '.');

                    $bodyMail = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px; background: #ffffff;'>
                        <div style='text-align: center; border-bottom: 2px solid #2563eb; padding-bottom: 15px; margin-bottom: 20px;'>
                            <h2 style='color: #2563eb; margin: 0;'>" . MAIL_FROM_NAME . "</h2>
                            <p style='color: #64748b; margin: 5px 0 0 0; font-size: 14px;'>Hóa đơn mua hàng tại quầy</p>
                        </div>

                        <p>Xin chào <strong>" . $safe_fullname . "</strong>,</p>
                        <p>Cảm ơn bạn đã mua sắm! Đơn hàng <strong>#ORD-" . $order_id . "</strong> của bạn đã được thanh toán thành công tại quầy:</p>

                        <table style='width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 14px;'>
                            <thead>
                                <tr style='background: #f8fafc; color: #475569;'>
                                    <th style='padding: 8px; border-bottom: 2px solid #cbd5e1; text-align: center;'>STT</th>
                                    <th style='padding: 8px; border-bottom: 2px solid #cbd5e1; text-align: left;'>Mặt hàng</th>
                                    <th style='padding: 8px; border-bottom: 2px solid #cbd5e1; text-align: center;'>SL</th>
                                    <th style='padding: 8px; border-bottom: 2px solid #cbd5e1; text-align: right;'>Đơn giá</th>
                                    <th style='padding: 8px; border-bottom: 2px solid #cbd5e1; text-align: right;'>Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                " . $rows . "
                            </tbody>
                        </table>

                        <div style='text-align: right; margin-top: 15px; padding-top: 10px; border-top: 2px solid #e2e8f0;'>
                            <span style='font-size: 15px; color: #475569;'>Tổng thanh toán: </span>
                            <strong style='font-size: 20px; color: #dc2626;'>" . $totalFmt . " đ</strong>
                        </div>

                        <div style='margin-top: 25px; padding: 12px; background: #f8fafc; border-radius: 8px; font-size: 12px; color: #64748b; text-align: center;'>
                            Vui lòng giữ lại email này làm phiếu bảo hành và đối chiếu khi cần thiết.
                        </div>
                    </div>";

                    sendCustomMail($safe_email, $safe_fullname, $subject, $bodyMail);
                }

                $_SESSION['success_msg'] = "Thanh toán thành công đơn hàng #" . $order_id . "!";
                header('Location: detail.php?id=' . $order_id);
                die();
            }
        } else {
            $error = 'Lỗi hệ thống: Không thể khởi tạo hóa đơn.';
        }
    }
}

// Lấy danh sách sản phẩm và biến thể cấu hình còn hàng
$sql_products = "SELECT 
                    p.id AS product_id, 
                    p.title, 
                    p.thumbnail,
                    v.id AS variant_id,
                    v.sku,
                    v.color,
                    v.cpu,
                    v.ram,
                    v.storage,
                    v.gpu,
                    v.price,
                    v.discount,
                    (v.price - v.discount) AS final_price,
                    v.stock_quantity
                 FROM `Product` p
                 JOIN `Product_Variant` v ON p.id = v.product_id AND v.deleted = 0
                 WHERE p.deleted = 0 AND v.stock_quantity > 0
                 ORDER BY p.id DESC, v.price ASC";
$items = executeResult($sql_products);

$page_title = 'POS - Bán Hàng Tại Quầy';
include '../layout/head.php'; 
include '../layout/sidebar.php'; 
?>

<style>
  .pos-container {
    background: #f4f6f9;
    min-height: calc(100vh - 75px);
  }
  .pos-card-product {
    background: #ffffff;
    border: 1px solid #edf2f7;
    border-radius: 12px;
    transition: all 0.22s ease-in-out;
    cursor: pointer;
    overflow: hidden;
    position: relative;
  }
  .pos-card-product:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.06);
    border-color: #3b82f6;
  }
  .pos-card-product .img-box {
    height: 110px;
    background: #fafafa;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 8px;
  }
  .pos-card-product .img-box img {
    max-height: 100%;
    max-width: 100%;
    object-fit: contain;
  }
  .badge-stock-pill {
    position: absolute;
    top: 8px;
    right: 8px;
    font-size: 0.72rem;
    padding: 3px 8px;
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid #e2e8f0;
    color: #10b981;
    font-weight: 700;
  }
  .spec-pill {
    font-size: 0.72rem;
    color: #64748b;
    background: #f1f5f9;
    padding: 2px 6px;
    border-radius: 4px;
    display: inline-block;
    max-width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .pos-invoice {
    background: #ffffff;
    border-radius: 14px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.03);
    border: 1px solid #e2e8f0;
  }
  .cart-table thead th {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    background: #f8fafc;
    border: none;
  }
  .cart-item-row {
    border-bottom: 1px dashed #e2e8f0;
  }
  .cart-scroll-area {
    height: 230px;
    overflow-y: auto;
  }
  .cart-scroll-area::-webkit-scrollbar, .product-scroll-area::-webkit-scrollbar {
    width: 5px;
  }
  .cart-scroll-area::-webkit-scrollbar-thumb, .product-scroll-area::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
  }
  .btn-counter {
    width: 24px;
    height: 24px;
    padding: 0;
    border-radius: 6px;
    font-size: 0.75rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }
</style>

<!-- TOAST THÔNG BÁO -->
<?php if (!empty($error) || !empty($success_msg)): ?>
  <div id="toast-wrapper" style="position: fixed; top: 20px; right: 20px; z-index: 99999; min-width: 300px;">
    <div class="toast show shadow border-0" role="alert">
      <div class="toast-header <?= !empty($error) ? 'bg-danger' : 'bg-success' ?> text-white py-2">
        <i class="fas <?= !empty($error) ? 'fa-exclamation-circle' : 'fa-check-circle' ?> mr-2"></i>
        <strong class="mr-auto"><?= !empty($error) ? 'Lỗi' : 'Thành công' ?></strong>
        <button type="button" class="ml-2 close text-white" onclick="document.getElementById('toast-wrapper').remove()">&times;</button>
      </div>
      <div class="toast-body bg-white font-weight-bold text-dark">
        <?= htmlspecialchars($error ?: $success_msg) ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-3 pos-container">
  <?php include '../layout/topbar.php'; ?>

  <div class="row">
    <!-- CỘT TRÁI: SẢN PHẨM KHẢ DỤNG -->
    <div class="col-xl-7 col-lg-6 mb-4">
      <div class="bg-white p-3 rounded-lg border mb-3 shadow-sm d-flex flex-wrap align-items-center justify-content-between">
        <div class="font-weight-bold text-dark d-flex align-items-center">
          <i class="fas fa-th-large text-primary mr-2"></i>
          <span>Danh Sách Sản Phẩm</span>
        </div>
        <div style="width: 280px;">
          <div class="input-group input-group-sm">
            <div class="input-group-prepend">
              <span class="input-group-text bg-light border-right-0"><i class="fas fa-search text-muted"></i></span>
            </div>
            <input type="text" id="posSearch" class="form-control border-left-0 bg-light" placeholder="Tìm theo tên máy, cấu hình...">
          </div>
        </div>
      </div>

      <div class="product-scroll-area pr-1" style="max-height: 740px; overflow-y: auto;">
        <div class="row" id="posGrid">
          <?php if (!empty($items)): foreach ($items as $p): 
              $specs = array_filter([$p['color'], $p['cpu'], $p['ram'], $p['storage'], $p['gpu']]);
              $spec_str = implode(' · ', $specs);
              $search_meta = strtolower($p['title'] . ' ' . $spec_str . ' ' . $p['sku']);
              $img = (!empty($p['thumbnail']) && file_exists('../../' . $p['thumbnail'])) ? '../../' . $p['thumbnail'] : 'https://via.placeholder.com/150';
          ?>
            <div class="col-sm-6 col-md-6 col-xl-4 mb-3 item-box" data-keyword="<?= htmlspecialchars($search_meta) ?>">
              <div class="pos-card-product h-100 p-2 d-flex flex-column justify-content-between"
                   onclick='addToCart(<?= json_encode([
                       "product_id"  => (int)$p["product_id"],
                       "variant_id"  => (int)$p["variant_id"],
                       "title"       => $p["title"],
                       "spec"        => $spec_str,
                       "final_price" => (int)$p["final_price"],
                       "stock"       => (int)$p["stock_quantity"]
                   ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                
                <span class="badge-stock-pill"><i class="fas fa-cubes mr-1"></i><?= $p['stock_quantity'] ?></span>

                <div class="img-box rounded mb-2">
                  <img src="<?= $img ?>" alt="<?= htmlspecialchars($p['title']) ?>">
                </div>

                <div class="px-1">
                  <div class="font-weight-bold text-dark text-truncate" title="<?= htmlspecialchars($p['title']) ?>" style="font-size: 0.9rem;">
                    <?= htmlspecialchars($p['title']) ?>
                  </div>
                  <div class="spec-pill my-1" title="<?= htmlspecialchars($spec_str) ?>">
                    <?= htmlspecialchars($spec_str) ?>
                  </div>
                </div>

                <div class="px-1 pt-2 d-flex justify-content-between align-items-baseline">
                  <span class="text-danger font-weight-bold" style="font-size: 0.95rem;">
                    <?= number_format($p['final_price'], 0, ',', '.') ?> đ
                  </span>
                  <?php if ($p['discount'] > 0): ?>
                    <small class="text-muted"><del><?= number_format($p['price'], 0, ',', '.') ?> đ</del></small>
                  <?php endif; ?>
                </div>

              </div>
            </div>
          <?php endforeach; else: ?>
            <div class="col-12 text-center text-muted py-5">
              <i class="fas fa-boxes fa-3x mb-2 text-muted"></i>
              <p>Hiện không có sản phẩm nào có sẵn tồn kho.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- CỘT PHẢI: GIỎ HÀNG & THU TIỀN -->
    <div class="col-xl-5 col-lg-6 mb-4">
      <div class="pos-invoice p-3">
        <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
          <span class="font-weight-bold text-dark"><i class="fas fa-shopping-basket text-primary mr-1"></i> Giỏ Hàng POS</span>
          <button type="button" class="btn btn-sm btn-light text-danger font-weight-bold border" onclick="clearCart()">
            <i class="fas fa-trash-alt mr-1"></i> Xóa hết
          </button>
        </div>

        <div class="cart-scroll-area pr-1">
          <table class="table table-sm cart-table align-middle mb-0">
            <thead>
              <tr>
                <th>Thiết bị / Cấu hình</th>
                <th width="85" class="text-center">Số lượng</th>
                <th width="95" class="text-right">Tiền</th>
                <th width="25"></th>
              </tr>
            </thead>
            <tbody id="cartBody">
              <tr id="emptyRow">
                <td colspan="4" class="text-center text-muted py-5">
                  <i class="fas fa-cart-arrow-down fa-2x mb-2 text-black-50 d-block"></i>
                  Chưa có sản phẩm nào
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="p-3 bg-light rounded my-3 border">
          <div class="d-flex justify-content-between align-items-center">
            <span class="font-weight-bold text-secondary">Tổng tiền thanh toán:</span>
            <span class="h4 mb-0 text-danger font-weight-bold" id="totalDisplay">0 đ</span>
          </div>
        </div>

        <form method="POST" id="posForm" onsubmit="return handleCheckout()">
          <input type="hidden" name="action" value="checkout">
          <input type="hidden" name="cart_data" id="cartDataInput" value="[]">

          <!-- SĐT tự động tra cứu -->
          <div class="form-group mb-2 position-relative">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <label class="small font-weight-bold text-muted mb-0">Số điện thoại</label>
              <span id="lookupStatus" class="small text-muted" style="font-size: 0.75rem;"></span>
            </div>
            <div class="input-group input-group-sm">
              <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-phone-alt"></i></span>
              </div>
              <input type="tel" name="phone_number" id="inputPhone" class="form-control" placeholder="Nhập SĐT để tự tìm thông tin...">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-6 mb-2">
              <label class="small font-weight-bold text-muted mb-1">Họ và tên</label>
              <input type="text" name="fullname" id="inputFullname" class="form-control form-control-sm" placeholder="Khách vãng lai">
            </div>
            <div class="form-group col-md-6 mb-2">
              <label class="small font-weight-bold text-muted mb-1">Email</label>
              <input type="email" name="email" id="inputEmail" class="form-control form-control-sm" placeholder="khachhang@example.com">
            </div>
          </div>

          <div class="form-group mb-2">
            <label class="small font-weight-bold text-muted mb-1">Địa chỉ</label>
            <input type="text" name="address" id="inputAddress" class="form-control form-control-sm" placeholder="Địa chỉ giao/nhận hàng (nếu có)">
          </div>

          <!-- PHƯƠNG THỨC THANH TOÁN (TIỀN MẶT / VNPAY) -->
          <div class="form-group mb-2">
            <label class="small font-weight-bold text-muted mb-1">Hình thức thanh toán</label>
            <div class="border rounded p-2 bg-light d-flex justify-content-around">
              <div class="custom-control custom-radio">
                <input type="radio" id="payCash" name="payment_method" value="cash" class="custom-control-input" checked>
                <label class="custom-control-label font-weight-bold text-dark small" for="payCash">
                  <i class="fas fa-money-bill-wave text-success mr-1"></i> Tiền mặt
                </label>
              </div>
              <div class="custom-control custom-radio">
                <input type="radio" id="payVnpay" name="payment_method" value="vnpay" class="custom-control-input">
                <label class="custom-control-label font-weight-bold text-primary small" for="payVnpay">
                  <i class="fas fa-qrcode text-primary mr-1"></i> Quét mã VNPay
                </label>
              </div>
            </div>
          </div>

          <div class="form-group mb-3">
            <label class="small font-weight-bold text-muted mb-1">Ghi chú hóa đơn</label>
            <textarea name="note" rows="2" class="form-control form-control-sm" placeholder="Ghi chú thêm nếu cần..."></textarea>
          </div>

          <button type="submit" id="btnSubmitPos" class="btn btn-primary btn-block font-weight-bold py-2 shadow-sm" style="border-radius: 8px;">
            <i class="fas fa-print mr-1"></i> LƯU ĐƠN & THU TIỀN NGAY
          </button>
        </form>
      </div>
    </div>
  </div>
</main>

<?php include '../layout/scripts.php'; ?>

<script>
let cart = [];

function addToCart(item) {
  let found = cart.find(i => i.variant_id === item.variant_id);
  if (found) {
    if (found.num >= item.stock) {
      alert('Tồn kho hiện tại chỉ còn ' + item.stock + ' máy!');
      return;
    }
    found.num += 1;
  } else {
    cart.push({
      product_id: item.product_id,
      variant_id: item.variant_id,
      title: item.title,
      spec: item.spec,
      final_price: item.final_price,
      stock: item.stock,
      num: 1
    });
  }
  renderCart();
}

function updateQty(variant_id, delta) {
  let item = cart.find(i => i.variant_id === variant_id);
  if (!item) return;

  if (delta > 0 && item.num >= item.stock) {
    alert('Đã chạm giới hạn tồn kho!');
    return;
  }

  item.num += delta;
  if (item.num <= 0) {
    cart = cart.filter(i => i.variant_id !== variant_id);
  }
  renderCart();
}

function removeItem(variant_id) {
  cart = cart.filter(i => i.variant_id !== variant_id);
  renderCart();
}

function clearCart() {
  cart = [];
  renderCart();
}

function renderCart() {
  const tbody = document.getElementById('cartBody');
  const totalDisplay = document.getElementById('totalDisplay');
  const cartInput = document.getElementById('cartDataInput');

  if (cart.length === 0) {
    tbody.innerHTML = `
      <tr id="emptyRow">
        <td colspan="4" class="text-center text-muted py-5">
          <i class="fas fa-cart-arrow-down fa-2x mb-2 text-black-50 d-block"></i>
          Chưa có sản phẩm nào
        </td>
      </tr>`;
    totalDisplay.innerText = '0 đ';
    cartInput.value = '[]';
    return;
  }

  let totalMoney = 0;
  let html = '';

  cart.forEach(item => {
    let lineTotal = item.final_price * item.num;
    totalMoney += lineTotal;

    html += `
      <tr class="cart-item-row">
        <td class="align-middle py-2">
          <span class="font-weight-bold d-block text-truncate text-dark" style="max-width: 150px; font-size: 0.85rem;">${item.title}</span>
          <small class="text-muted d-block text-truncate" style="max-width: 150px;">${item.spec}</small>
          <span class="text-primary font-weight-bold small">${item.final_price.toLocaleString('vi-VN')} đ</span>
        </td>
        <td class="align-middle text-center py-2">
          <div class="d-inline-flex align-items-center">
            <button class="btn btn-sm btn-light border btn-counter" type="button" onclick="updateQty(${item.variant_id}, -1)">-</button>
            <span class="px-2 font-weight-bold small">${item.num}</span>
            <button class="btn btn-sm btn-light border btn-counter" type="button" onclick="updateQty(${item.variant_id}, 1)">+</button>
          </div>
        </td>
        <td class="align-middle text-right font-weight-bold text-dark small py-2">${lineTotal.toLocaleString('vi-VN')} đ</td>
        <td class="align-middle text-center py-2">
          <a href="javascript:;" class="text-danger" onclick="removeItem(${item.variant_id})">
            <i class="fas fa-times-circle"></i>
          </a>
        </td>
      </tr>
    `;
  });

  tbody.innerHTML = html;
  totalDisplay.innerText = totalMoney.toLocaleString('vi-VN') + ' đ';
  cartInput.value = JSON.stringify(cart);
}

// Thay đổi tiêu đề nút bấm theo hình thức thanh toán
document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
  radio.addEventListener('change', function() {
    const btn = document.getElementById('btnSubmitPos');
    if (this.value === 'vnpay') {
      btn.innerHTML = '<i class="fas fa-qrcode mr-1"></i> TẠO MÃ THANH TOÁN VNPAY &rarr;';
      btn.className = 'btn btn-info btn-block font-weight-bold py-2 shadow-sm text-white';
    } else {
      btn.innerHTML = '<i class="fas fa-print mr-1"></i> LƯU ĐƠN & THU TIỀN NGAY';
      btn.className = 'btn btn-primary btn-block font-weight-bold py-2 shadow-sm';
    }
  });
});

function handleCheckout() {
  if (cart.length === 0) {
    alert('Vui lòng chọn ít nhất 1 sản phẩm trước khi thanh toán!');
    return false;
  }
  const isVnpay = document.getElementById('payVnpay').checked;
  const msg = isVnpay 
    ? 'Xác nhận khởi tạo thanh toán VNPay và chuyển sang cổng quét mã?' 
    : 'Xác nhận lưu hóa đơn và thu tiền mặt trực tiếp?';
  return confirm(msg);
}

// Lọc tìm kiếm sản phẩm
document.getElementById('posSearch').addEventListener('input', function(e) {
  const q = e.target.value.toLowerCase().trim();
  document.querySelectorAll('.item-box').forEach(card => {
    const meta = card.getAttribute('data-keyword');
    card.style.display = meta.includes(q) ? '' : 'none';
  });
});

// Tra cứu SĐT tự động qua AJAX
let lookupTimer = null;
const phoneInput    = document.getElementById('inputPhone');
const nameInput     = document.getElementById('inputFullname');
const emailInput    = document.getElementById('inputEmail');
const addressInput  = document.getElementById('inputAddress');
const statusDisplay = document.getElementById('lookupStatus');

phoneInput.addEventListener('input', function () {
  const phone = this.value.trim();
  clearTimeout(lookupTimer);

  if (phone.length < 9) {
    statusDisplay.innerHTML = '';
    return;
  }

  statusDisplay.innerHTML = '<span class="text-muted"><i class="fas fa-spinner fa-spin"></i> Đang tìm...</span>';

  lookupTimer = setTimeout(() => {
    fetch(`${window.location.pathname}?action=lookup_customer&phone=${encodeURIComponent(phone)}`)
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          if (data.fullname) nameInput.value = data.fullname;
          if (data.email) emailInput.value = data.email;
          if (data.address) addressInput.value = data.address;
          statusDisplay.innerHTML = '<span class="text-success font-weight-bold"><i class="fas fa-check"></i> Đã nhận diện khách cũ</span>';
        } else {
          statusDisplay.innerHTML = '<span class="text-secondary">Khách mới</span>';
        }
      })
      .catch(() => {
        statusDisplay.innerHTML = '';
      });
  }, 400);
});

// Tự ẩn toast
window.addEventListener('load', function() {
  var t = document.getElementById('toast-wrapper');
  if (t) {
    setTimeout(function() {
      t.style.opacity = '0';
      t.style.transition = 'all 0.5s ease';
      setTimeout(function() { t.remove(); }, 500);
    }, 3000);
  }
});
</script>