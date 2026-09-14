<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once __DIR__ . '/database/dbhelper.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header('Location: login.php?redirect=' . urlencode('orders.php'));
    exit();
}

$currentUser = $_SESSION['user'];
$user_id     = (int)$currentUser['id'];

// XỬ LÝ HỦY ĐƠN HÀNG KHI ĐANG Ở TRẠNG THÁI 0 (CHỜ XÁC NHẬN)
if (isset($_GET['action']) && $_GET['action'] === 'cancel_order') {
    $cancel_id = intval($_GET['order_id'] ?? 0);
    if ($cancel_id > 0) {
        $check_order = executeResult("SELECT id, status FROM `Orders` WHERE id = $cancel_id AND user_id = $user_id LIMIT 1", true);
        if ($check_order && (int)$check_order['status'] === 0) {
            // Hoàn lại số lượng tồn kho
            $details = executeResult("SELECT variant_id, num FROM `Order_Details` WHERE order_id = $cancel_id");
            if (!empty($details)) {
                foreach ($details as $d) {
                    if (!empty($d['variant_id'])) {
                        execute("UPDATE `Product_Variant` SET stock_quantity = stock_quantity + {$d['num']} WHERE id = {$d['variant_id']}");
                    }
                }
            }

            execute("UPDATE `Orders` SET status = 3, note = CONCAT(COALESCE(note, ''), ' | Khách hàng tự hủy đơn') WHERE id = $cancel_id");
            $_SESSION['order_toast'] = [
                'type'    => 'danger',
                'title'   => 'Đã hủy đơn hàng',
                'message' => "Đơn hàng #ORD-$cancel_id đã được hủy thành công!"
            ];
        }
    }
    header('Location: orders.php');
    exit();
}

// 1. Nhận tab trạng thái đang xem
$status_tab = isset($_GET['status']) && $_GET['status'] !== '' ? intval($_GET['status']) : '';

// 2. Thống kê số lượng đơn theo từng trạng thái
$count_all = executeResult("SELECT COUNT(id) AS total FROM `Orders` WHERE user_id = $user_id", true)['total'] ?? 0;
$count_0   = executeResult("SELECT COUNT(id) AS total FROM `Orders` WHERE user_id = $user_id AND status = 0", true)['total'] ?? 0;
$count_1   = executeResult("SELECT COUNT(id) AS total FROM `Orders` WHERE user_id = $user_id AND status = 1", true)['total'] ?? 0;
$count_2   = executeResult("SELECT COUNT(id) AS total FROM `Orders` WHERE user_id = $user_id AND status = 2", true)['total'] ?? 0;
$count_3   = executeResult("SELECT COUNT(id) AS total FROM `Orders` WHERE user_id = $user_id AND status = 3", true)['total'] ?? 0;

// 3. Truy vấn danh sách đơn hàng
$where_status = ($status_tab !== '') ? "AND status = $status_tab" : "";
$sql_orders = "SELECT * FROM `Orders` 
               WHERE user_id = $user_id $where_status 
               ORDER BY order_date DESC, id DESC";
$orders = executeResult($sql_orders);

$toast_data = $_SESSION['order_toast'] ?? null;
unset($_SESSION['order_toast']);

$page_title = 'Đơn Hàng Của Tôi';
include 'includes/header.php';
?>

<!-- TOAST THÔNG BÁO GÓC PHẢI -->
<div id="order-toast-wrapper" style="position: fixed; top: 25px; right: 25px; z-index: 99999; min-width: 320px; max-width: 380px; display: none;">
  <div class="card border-0 shadow-lg" style="border-radius: 12px; overflow: hidden; border-left: 5px solid #ef4444 !important;">
    <div class="card-body p-3 bg-white text-dark d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center">
        <span id="toastIcon" class="text-danger mr-2" style="font-size: 1.2rem;"><i class="fas fa-trash-alt"></i></span>
        <div>
          <strong id="toastTitle" class="d-block text-dark font-weight-bold" style="font-size: 0.9rem;">Thông báo</strong>
          <span id="toastMessage" class="small text-muted" style="line-height: 1.3;">Thao tác thành công!</span>
        </div>
      </div>
      <button type="button" class="close text-muted ml-2" onclick="closeOrderToast()" style="outline: none; font-size: 1.25rem;">&times;</button>
    </div>
  </div>
</div>

<main class="container my-5">
  <div class="row">
    <!-- Cột trái: Sidebar tài khoản -->
    <div class="col-lg-3 col-md-4 mb-4">
      <div class="card border-0 rounded-2xl shadow-soft p-3 bg-white">
        <div class="text-center py-3 border-bottom">
          <div class="btn-icon bg-light text-primary rounded-circle mx-auto mb-2 border d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; font-size: 1.4rem;">
            <i class="far fa-user"></i>
          </div>
          <h6 class="font-weight-bold text-dark mb-1">
            <?= htmlspecialchars($currentUser['fullname'] ?? 'Khách hàng') ?>
          </h6>
          <span class="badge badge-pill badge-light border text-primary font-weight-bold px-3 py-1">Thành viên</span>
        </div>

        <div class="list-group list-group-flush pt-2">
          <a href="profile.php" class="list-group-item list-group-item-action border-0 rounded-pill-custom py-2 px-3 text-secondary">
            <i class="fas fa-user-circle mr-2"></i> Hồ sơ cá nhân
          </a>
          <a href="orders.php" class="list-group-item list-group-item-action border-0 rounded-pill-custom py-2 px-3 font-weight-bold text-primary active bg-light">
            <i class="fas fa-box-open mr-2"></i> Lịch sử đơn hàng
          </a>
          <a href="logout.php" class="list-group-item list-group-item-action border-0 rounded-pill-custom py-2 px-3 text-danger mt-3 font-weight-semibold">
            <i class="fas fa-sign-out-alt mr-2"></i> Đăng xuất
          </a>
        </div>
      </div>
    </div>

    <!-- Cột phải: Danh sách đơn hàng -->
    <div class="col-lg-9 col-md-8">
      <div class="card border-0 rounded-2xl shadow-soft p-4 bg-white mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h5 class="font-weight-bold text-dark mb-1">Đơn Hàng Của Tôi</h5>
            <p class="text-muted small mb-0">Theo dõi tiến độ và kiểm tra lịch sử đặt hàng</p>
          </div>
        </div>

        <!-- Tabs trạng thái -->
        <ul class="nav nav-pills mb-4 border-bottom pb-3 flex-nowrap overflow-auto">
          <li class="nav-item mr-2">
            <a class="nav-link <?= $status_tab === '' ? 'active' : 'text-secondary' ?> rounded-pill-custom px-3 py-2 small font-weight-bold" href="orders.php">
              Tất cả (<?= $count_all ?>)
            </a>
          </li>
          <li class="nav-item mr-2">
            <a class="nav-link <?= $status_tab === 0 ? 'active' : 'text-secondary' ?> rounded-pill-custom px-3 py-2 small font-weight-bold" href="orders.php?status=0">
              Chờ xác nhận (<?= $count_0 ?>)
            </a>
          </li>
          <li class="nav-item mr-2">
            <a class="nav-link <?= $status_tab === 1 ? 'active' : 'text-secondary' ?> rounded-pill-custom px-3 py-2 small font-weight-bold" href="orders.php?status=1">
              Đang giao (<?= $count_1 ?>)
            </a>
          </li>
          <li class="nav-item mr-2">
            <a class="nav-link <?= $status_tab === 2 ? 'active' : 'text-secondary' ?> rounded-pill-custom px-3 py-2 small font-weight-bold" href="orders.php?status=2">
              Hoàn thành (<?= $count_2 ?>)
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $status_tab === 3 ? 'active' : 'text-secondary' ?> rounded-pill-custom px-3 py-2 small font-weight-bold" href="orders.php?status=3">
              Đã hủy (<?= $count_3 ?>)
            </a>
          </li>
        </ul>

        <?php if (!empty($orders)): ?>
          <?php foreach ($orders as $ord): 
              $ord_id = (int)$ord['id'];
              $st     = (int)$ord['status'];

              $sql_items = "SELECT od.*, p.title, p.thumbnail, v.color, v.cpu, v.ram, v.storage 
                            FROM `Order_Details` od
                            LEFT JOIN `Product` p ON od.product_id = p.id
                            LEFT JOIN `Product_Variant` v ON od.variant_id = v.id
                            WHERE od.order_id = $ord_id";
              $items = executeResult($sql_items);
          ?>
            <div class="border rounded-2xl p-4 mb-4">
              <!-- Tiêu đề mã đơn & trạng thái -->
              <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3 flex-wrap" style="gap: 8px;">
                <div>
                  <span class="font-weight-bold text-dark">MÃ ĐƠN: #ORD-<?= $ord_id ?></span>
                  <span class="text-muted small ml-2">
                    <i class="far fa-clock mr-1"></i> <?= date('d/m/Y H:i', strtotime($ord['order_date'])) ?>
                  </span>
                </div>
                <div>
                  <?php if ($st === 0): ?>
                    <span class="badge badge-pill badge-warning text-dark px-3 py-2 font-weight-bold">
                      <i class="fas fa-clock mr-1"></i> Chờ xác nhận
                    </span>
                  <?php elseif ($st === 1): ?>
                    <span class="badge badge-pill badge-info text-white px-3 py-2 font-weight-bold">
                      <i class="fas fa-shipping-fast mr-1"></i> Đang vận chuyển
                    </span>
                  <?php elseif ($st === 2): ?>
                    <span class="badge badge-pill badge-success text-white px-3 py-2 font-weight-bold">
                      <i class="fas fa-check-circle mr-1"></i> Giao thành công
                    </span>
                  <?php elseif ($st === 3): ?>
                    <span class="badge badge-pill badge-danger text-white px-3 py-2 font-weight-bold">
                      <i class="fas fa-times-circle mr-1"></i> Đã hủy
                    </span>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Danh sách mặt hàng -->
              <?php foreach ($items as $it): 
                  $specs = array_filter([$it['color'], $it['cpu'], $it['ram'], $it['storage']]);
                  $spec_str = !empty($specs) ? implode(' · ', $specs) : 'Bản tiêu chuẩn';
                  $img = (!empty($it['thumbnail']) && file_exists($it['thumbnail'])) 
                         ? htmlspecialchars($it['thumbnail']) 
                         : 'https://via.placeholder.com/80x80?text=SP';
              ?>
                <div class="d-flex align-items-center justify-content-between flex-wrap py-2 border-bottom" style="gap: 12px;">
                  <div class="d-flex align-items-center">
                    <img src="<?= $img ?>" class="rounded-lg mr-3 border p-1" style="width: 65px; height: 65px; object-fit: contain;" alt="<?= htmlspecialchars($it['title'] ?? 'SP') ?>">
                    <div>
                      <h6 class="font-weight-bold text-dark mb-1"><?= htmlspecialchars($it['title'] ?? 'Sản phẩm') ?></h6>
                      <span class="text-muted small">Cấu hình: <?= htmlspecialchars($spec_str) ?> | Số lượng: x<?= $it['num'] ?></span>
                    </div>
                  </div>
                  <div class="font-weight-bold text-dark"><?= number_format($it['total_money'], 0, ',', '.') ?> ₫</div>
                </div>
              <?php endforeach; ?>

              <!-- Footer đơn hàng: Phân luồng nút theo yêu cầu -->
              <div class="d-flex justify-content-between align-items-center pt-3 flex-wrap" style="gap: 8px;">
                <span class="small text-muted">
                  <?= !empty($ord['note']) ? htmlspecialchars($ord['note']) : 'Giao hàng tận nơi' ?>
                </span>
                <div class="text-right d-flex align-items-center">
                  <span class="small text-muted mr-2">Tổng thanh toán:</span>
                  <span class="h5 font-weight-bold text-danger mb-0 mr-3"><?= number_format($ord['total_money'], 0, ',', '.') ?> ₫</span>

                  <!-- 1. Trạng thái 0 (Chờ xác nhận): Hiện nút HỦY ĐƠN -->
                  <?php if ($st === 0): ?>
                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill-custom px-3 font-weight-bold" onclick="confirmCancelToast(<?= $ord_id ?>)">
                      <i class="fas fa-times mr-1"></i> Hủy đơn
                    </button>

                  <!-- 2. Trạng thái 1 (Đang vận chuyển): Không hiện nút nào -->
                  <?php elseif ($st === 1): ?>
                    <!-- Không hiển thị gì -->

                  <!-- 3. Trạng thái 2 (Thành công) hoặc 3 (Đã hủy): Hiện nút MUA LẠI -->
                  <?php elseif ($st === 2 || $st === 3): ?>
                    <a href="product-detail.php?id=<?= $items[0]['product_id'] ?? 1 ?>" class="btn btn-primary btn-sm rounded-pill-custom px-3 font-weight-bold">
                      Mua lại
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="text-center py-5 text-muted">
            <i class="fas fa-box-open fa-3x mb-3 text-black-50"></i>
            <p class="mb-0">Không có đơn hàng nào trong mục này.</p>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</main>

<script>
// Toast thông báo xác nhận hủy đơn ở góc trên bên phải
function confirmCancelToast(orderId) {
  var oldToast = document.getElementById('order-cancel-toast');
  if (oldToast) oldToast.remove();

  var toastDiv = document.createElement('div');
  toastDiv.id = 'order-cancel-toast';
  toastDiv.style.cssText = 'position: fixed; top: 25px; right: 25px; z-index: 99999; min-width: 320px; max-width: 380px; transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1); transform: translateY(-15px); opacity: 0;';

  toastDiv.innerHTML = `
    <div class="card border-0 shadow-lg" style="border-radius: 12px; overflow: hidden; border-left: 5px solid #ef4444 !important;">
      <div class="card-body p-3 bg-white text-dark">
        <div class="d-flex align-items-start justify-content-between mb-2">
          <div class="d-flex align-items-center">
            <span class="text-danger mr-2" style="font-size: 1.15rem;"><i class="fas fa-exclamation-triangle"></i></span>
            <strong class="text-dark font-weight-bold" style="font-size: 0.95rem;">Xác nhận hủy đơn</strong>
          </div>
          <button type="button" class="close text-muted" onclick="this.closest('#order-cancel-toast').remove()" style="outline: none; font-size: 1.25rem; line-height: 1;">&times;</button>
        </div>
        <p class="small text-muted mb-3">Bạn có chắc chắn muốn hủy đơn hàng <strong>#ORD-${orderId}</strong> không?</p>
        <div class="d-flex justify-content-end" style="gap: 8px;">
          <button type="button" class="btn btn-sm btn-light border px-3 py-1 font-weight-bold" onclick="this.closest('#order-cancel-toast').remove()">Đóng</button>
          <a href="orders.php?action=cancel_order&order_id=${orderId}" class="btn btn-sm btn-danger px-3 py-1 font-weight-bold text-white shadow-sm" style="border-radius: 6px;">Đồng ý hủy</a>
        </div>
      </div>
    </div>
  `;

  document.body.appendChild(toastDiv);

  setTimeout(function() {
    toastDiv.style.opacity = '1';
    toastDiv.style.transform = 'translateY(0)';
  }, 10);
}

// Toast thông báo sau khi hủy đơn
function showOrderToast(title, message, type = 'danger') {
  var wrapper = document.getElementById('order-toast-wrapper');
  var titleEl = document.getElementById('toastTitle');
  var msgEl   = document.getElementById('toastMessage');

  if (!wrapper) return;

  titleEl.innerText = title;
  msgEl.innerText = message;

  wrapper.style.display = 'block';
  wrapper.style.opacity = '0';
  wrapper.style.transform = 'translateY(-15px)';
  wrapper.style.transition = 'all 0.35s cubic-bezier(0.16, 1, 0.3, 1)';

  setTimeout(function() {
    wrapper.style.opacity = '1';
    wrapper.style.transform = 'translateY(0)';
  }, 10);

  setTimeout(closeOrderToast, 3500);
}

function closeOrderToast() {
  var wrapper = document.getElementById('order-toast-wrapper');
  if (wrapper) {
    wrapper.style.opacity = '0';
    wrapper.style.transform = 'translateX(40px)';
    setTimeout(function() { wrapper.style.display = 'none'; }, 350);
  }
}

window.addEventListener('load', function() {
  <?php if (!empty($toast_data)): ?>
    showOrderToast(
      '<?= addslashes($toast_data['title']) ?>', 
      '<?= addslashes($toast_data['message']) ?>', 
      '<?= addslashes($toast_data['type']) ?>'
    );
  <?php endif; ?>
});
</script>

<?php 
include 'includes/footer.php'; 
?>