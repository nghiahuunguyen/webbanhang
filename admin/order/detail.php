<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once('../../database/dbhelper.php');
require_once('../check_role.php');

// Cho phép Admin (1) và Nhân viên (3) truy cập
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role_id'], [1, 3])) {
    header('Location: ../auth/login.php');
    die();
}

$id = $_GET['id'] ?? '';
if (empty($id)) {
    header('Location: index.php');
    die();
}

$id = intval($id);

// Cập nhật trạng thái đơn hàng ngay trên trang chi tiết
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $new_status = intval($_POST['status'] ?? 0);
    if (in_array($new_status, [0, 1, 2, 3])) {
        execute("UPDATE `Orders` SET status = $new_status WHERE id = $id");
        $_SESSION['success_msg'] = "Đã cập nhật trạng thái đơn hàng #$id!";
    }
    header('Location: detail.php?id=' . $id);
    die();
}

$success_msg = $_SESSION['success_msg'] ?? '';
unset($_SESSION['success_msg']);

// 1. Lấy thông tin đơn hàng và nhân viên tạo đơn (nếu có)
$sql_order = "SELECT o.*, u.fullname AS staff_name, u.account AS staff_account 
              FROM `Orders` o 
              LEFT JOIN `User` u ON o.user_id = u.id 
              WHERE o.id = $id 
              LIMIT 1";
$order = executeResult($sql_order, true);

if (!$order) {
    header('Location: index.php');
    die();
}

// 2. Lấy danh sách sản phẩm chi tiết trong đơn hàng (kèm biến thể cấu hình)
$sql_details = "SELECT od.*, 
                       p.title AS product_title, 
                       p.thumbnail AS product_thumbnail,
                       v.sku, 
                       v.color, 
                       v.cpu, 
                       v.ram, 
                       v.storage, 
                       v.gpu
                FROM `Order_Details` od
                LEFT JOIN `Product` p ON od.product_id = p.id
                LEFT JOIN `Product_Variant` v ON od.variant_id = v.id
                WHERE od.order_id = $id
                ORDER BY od.id ASC";
$order_details = executeResult($sql_details);

$page_title = 'Chi Tiết Đơn Hàng #' . $order['id'];
include '../layout/head.php'; 
include '../layout/sidebar.php'; 
?>

<style>
  .badge-status-lg {
    padding: 6px 14px;
    border-radius: 50rem;
    font-size: 0.85rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  .badge-status-0 { background-color: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
  .badge-status-1 { background-color: #eff6ff; color: #1d4ed8; border: 1px solid #dbeafe; }
  .badge-status-2 { background-color: #f0fdf4; color: #15803d; border: 1px solid #dcfce7; }
  .badge-status-3 { background-color: #fef2f2; color: #b91c1c; border: 1px solid #fee2e2; }

  @media print {
    body * { visibility: hidden; }
    #printableArea, #printableArea * { visibility: visible; }
    #printableArea { position: absolute; left: 0; top: 0; width: 100%; border: none !important; box-shadow: none !important; }
    .no-print { display: none !important; }
  }
</style>

<!-- TOAST THÔNG BÁO -->
<?php if (!empty($success_msg)): ?>
  <div id="toast-wrapper" style="position: fixed; top: 20px; right: 20px; z-index: 99999; min-width: 300px; transition: all 0.5s ease;">
    <div class="toast show shadow-lg border-0" role="alert">
      <div class="toast-header bg-success text-white">
        <i class="fas fa-check-circle mr-2"></i>
        <strong class="mr-auto">Thông báo</strong>
        <small class="text-white">Vừa xong</small>
        <button type="button" class="ml-2 mb-1 close text-white" onclick="document.getElementById('toast-wrapper').remove()" style="outline: none;">
          <span>&times;</span>
        </button>
      </div>
      <div class="toast-body bg-white text-dark font-weight-bold">
        <?= htmlspecialchars($success_msg) ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- MAIN CONTENT -->
<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-3">
  <?php include '../layout/topbar.php'; ?>

  <div class="custom-card bg-white rounded-lg shadow-sm border mb-4" id="printableArea">
    <!-- TIÊU ĐỀ & NÚT THAO TÁC -->
    <div class="p-3 px-4 d-flex justify-content-between align-items-center border-bottom flex-wrap">
      <div>
        <h5 class="font-weight-bold mb-1 text-dark d-flex align-items-center">
          <i class="fas fa-receipt text-primary mr-2"></i> Hóa Đơn #ORD-<?= $order['id'] ?>
        </h5>
        <small class="text-muted">
          Thời gian tạo đơn: <?= date('d/m/Y H:i:s', strtotime($order['order_date'])) ?>
        </small>
      </div>
      <div class="mt-2 mt-md-0 no-print">
        <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-primary font-weight-bold mr-1">
          <i class="fas fa-print mr-1"></i> In hóa đơn
        </button>
        <a href="index.php" class="btn btn-sm btn-secondary font-weight-bold">
          <i class="fas fa-arrow-left mr-1"></i> Quay lại
        </a>
      </div>
    </div>

    <div class="p-4">
      <div class="row mb-4">
        <!-- KHỐI THÔNG TIN KHÁCH HÀNG -->
        <div class="col-md-6 mb-3 mb-md-0">
          <div class="border rounded p-3 h-100 bg-light">
            <h6 class="font-weight-bold text-uppercase text-secondary small mb-3">
              <i class="fas fa-user mr-1"></i> Thông Tin Khách Hàng
            </h6>
            <div class="mb-2">
              <span class="text-muted small">Họ và tên:</span>
              <strong class="d-block text-dark font-weight-bold"><?= htmlspecialchars($order['fullname']) ?></strong>
            </div>
            <div class="mb-2">
              <span class="text-muted small">Số điện thoại:</span>
              <strong class="d-block text-dark"><?= htmlspecialchars($order['phone_number']) ?></strong>
            </div>
            <div class="mb-2">
              <span class="text-muted small">Email:</span>
              <span class="d-block text-dark"><?= htmlspecialchars($order['email'] ?: 'Không có') ?></span>
            </div>
            <div>
              <span class="text-muted small">Địa chỉ nhận hàng:</span>
              <span class="d-block text-dark"><?= htmlspecialchars($order['address'] ?: 'Nhận tại quầy') ?></span>
            </div>
          </div>
        </div>

        <!-- KHỐI TRẠNG THÁI & GHI CHÚ ĐƠN -->
        <div class="col-md-6">
          <div class="border rounded p-3 h-100 bg-light">
            <h6 class="font-weight-bold text-uppercase text-secondary small mb-3">
              <i class="fas fa-info-circle mr-1"></i> Trạng Thái & Xử Lý Đơn
            </h6>
            
            <div class="mb-3 d-flex align-items-center justify-content-between">
              <div>
                <span class="text-muted small d-block">Trạng thái hiện tại:</span>
                <?php $st = (int)$order['status']; ?>
                <?php if ($st === 0): ?>
                  <span class="badge-status-lg badge-status-0 mt-1"><i class="fas fa-clock"></i> Chờ xác nhận</span>
                <?php elseif ($st === 1): ?>
                  <span class="badge-status-lg badge-status-1 mt-1"><i class="fas fa-truck"></i> Đang vận chuyển</span>
                <?php elseif ($st === 2): ?>
                  <span class="badge-status-lg badge-status-2 mt-1"><i class="fas fa-check-circle"></i> Hoàn thành</span>
                <?php elseif ($st === 3): ?>
                  <span class="badge-status-lg badge-status-3 mt-1"><i class="fas fa-times-circle"></i> Đã hủy</span>
                <?php endif; ?>
              </div>

              <!-- Form thay đổi trạng thái nhanh -->
              <div class="no-print">
                <form method="POST" class="d-inline-flex align-items-center">
                  <input type="hidden" name="action" value="update_status">
                  <select name="status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                    <option value="0" <?= ($st === 0) ? 'selected' : '' ?>>Chờ xác nhận</option>
                    <option value="1" <?= ($st === 1) ? 'selected' : '' ?>>Đang vận chuyển</option>
                    <option value="2" <?= ($st === 2) ? 'selected' : '' ?>>Hoàn thành</option>
                    <option value="3" <?= ($st === 3) ? 'selected' : '' ?>>Đã hủy</option>
                  </select>
                </form>
              </div>
            </div>

            <div class="mb-2">
              <span class="text-muted small">Nhân viên tạo/xử lý:</span>
              <strong class="d-block text-dark"><?= htmlspecialchars($order['staff_name'] ?? 'Khách đặt qua Website') ?></strong>
            </div>
            <div>
              <span class="text-muted small">Ghi chú đơn hàng:</span>
              <div class="small bg-white p-2 rounded border mt-1 text-secondary">
                <?= !empty($order['note']) ? htmlspecialchars($order['note']) : 'Không có ghi chú thêm.' ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- BẢNG CHI TIẾT SẢN PHẨM TRONG ĐƠN -->
      <h6 class="text-primary font-weight-bold text-uppercase small tracking-wider mb-2">
        <i class="fas fa-box-open mr-1"></i> Danh Sách Mặt Hàng Chi Tiết
      </h6>

      <div class="table-responsive mb-4">
        <table class="table table-bordered table-sm align-middle mb-0">
          <thead class="thead-light text-center">
            <tr>
              <th width="45px">STT</th>
              <th width="65px">ẢNH</th>
              <th class="text-left">TÊN SẢN PHẨM & CẤU HÌNH BIẾN THỂ</th>
              <th width="140px">ĐƠN GIÁ</th>
              <th width="80px">SL</th>
              <th width="150px" class="text-right">THÀNH TIỀN</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($order_details)): 
                $count = 1;
                foreach ($order_details as $item): 
                  $img_path = '../../' . $item['product_thumbnail'];
                  $img_src = (!empty($item['product_thumbnail']) && file_exists($img_path)) 
                             ? $img_path 
                             : 'https://via.placeholder.com/60x60?text=No+Img';

                  $specs = array_filter([$item['color'], $item['cpu'], $item['ram'], $item['storage'], $item['gpu']]);
                  $spec_str = implode(' | ', $specs);
            ?>
              <tr>
                <td class="text-center font-weight-bold text-muted"><?= $count++ ?></td>
                
                <!-- Ảnh -->
                <td class="text-center p-1">
                  <img src="<?= $img_src ?>" class="rounded border" width="50" height="50" style="object-fit: contain;">
                </td>

                <!-- Tên thiết bị & Thông số biến thể -->
                <td>
                  <strong class="text-dark d-block"><?= htmlspecialchars($item['product_title'] ?? 'Sản phẩm không còn tồn tại') ?></strong>
                  <?php if (!empty($spec_str)): ?>
                    <small class="text-muted d-block mt-1">
                      <i class="fas fa-microchip mr-1"></i><?= htmlspecialchars($spec_str) ?>
                    </small>
                  <?php endif; ?>
                  <?php if (!empty($item['sku'])): ?>
                    <small class="badge badge-light border text-secondary mt-1">SKU: <?= htmlspecialchars($item['sku']) ?></small>
                  <?php endif; ?>
                </td>

                <!-- Đơn giá -->
                <td class="text-center font-weight-bold">
                  <?= number_format($item['price'], 0, ',', '.') ?> đ
                </td>

                <!-- Số lượng -->
                <td class="text-center font-weight-bold">
                  <?= $item['num'] ?>
                </td>

                <!-- Thành tiền -->
                <td class="text-right font-weight-bold text-danger">
                  <?= number_format($item['total_money'], 0, ',', '.') ?> đ
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="text-center text-muted py-4">Đơn hàng không có sản phẩm nào.</td>
            </tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- TỔNG KẾT HÓA ĐƠN -->
      <div class="row justify-content-end">
        <div class="col-md-5 col-lg-4">
          <div class="border rounded p-3 bg-light">
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted small font-weight-bold">Tổng tiền hàng:</span>
              <strong class="text-dark"><?= number_format($order['total_money'], 0, ',', '.') ?> đ</strong>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted small font-weight-bold">Phí vận chuyển:</span>
              <span class="text-success font-weight-bold small">Miễn phí</span>
            </div>
            <hr class="my-2">
            <div class="d-flex justify-content-between align-items-center">
              <span class="font-weight-bold text-dark">TỔNG THANH TOÁN:</span>
              <span class="h5 mb-0 text-danger font-weight-bold"><?= number_format($order['total_money'], 0, ',', '.') ?> đ</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php include '../layout/scripts.php'; ?>

<!-- SCRIPT TỰ BIẾN MẤT TOAST SAU 3 GIÂY -->
<script>
  window.addEventListener('load', function() {
    var toastBox = document.getElementById('toast-wrapper');
    if (toastBox) {
      setTimeout(function() {
        toastBox.style.opacity = '0';
        toastBox.style.transform = 'translateX(50px)';
        setTimeout(function() {
          toastBox.remove();
        }, 500);
      }, 3000);
    }
  });
</script>