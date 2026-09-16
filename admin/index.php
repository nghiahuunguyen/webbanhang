<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once('../database/dbhelper.php');
require_once('check_role.php');

// Kiểm tra quyền Admin (1) hoặc Nhân viên (3)
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role_id'], [1, 3])) {
    header('Location: auth/login.php');
    die();
}

// 1. Thống kê số liệu thực tế từ database
$countOrders = executeResult("SELECT COUNT(id) AS total FROM `Orders`", true);
$total_orders = intval($countOrders['total'] ?? 0);

$countRevenue = executeResult("SELECT SUM(total_money) AS total FROM `Orders` WHERE `status` = 2", true);
$total_revenue = intval($countRevenue['total'] ?? 0);

$countStock = executeResult("SELECT SUM(stock_quantity) AS total FROM `Product_Variant` WHERE deleted = 0", true);
$total_stock = intval($countStock['total'] ?? 0);

$countProduct = executeResult("SELECT COUNT(id) AS total FROM `Product` WHERE deleted = 0", true);
$total_products = intval($countProduct['total'] ?? 0);

$countFeedback = executeResult("SELECT COUNT(id) AS total FROM `FeedBack`", true);
$total_feedbacks = intval($countFeedback['total'] ?? 0);

// 2. Lấy 6 đơn hàng mới nhất
$sqlRecentOrders = "SELECT * FROM `Orders` ORDER BY id DESC LIMIT 6";
$recent_orders = executeResult($sqlRecentOrders);

// 3. Lấy 4 sản phẩm bán chạy / tồn ít để cảnh báo nhanh
$lowStockVariants = executeResult("SELECT p.title, v.color, v.storage, v.stock_quantity 
                                  FROM `Product_Variant` v 
                                  JOIN `Product` p ON v.product_id = p.id 
                                  WHERE v.deleted = 0 AND p.deleted = 0 AND v.stock_quantity <= 5 
                                  ORDER BY v.stock_quantity ASC LIMIT 4");

$page_title = 'Bảng Điều Khiển - CommerceOS';
include 'layout/head.php'; 
include 'layout/sidebar.php'; 
?>

<!-- MAIN CONTENT -->
<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-3">

  <?php include 'layout/topbar.php'; ?>

  <!-- STATS CARDS -->
  <div class="row mb-4">
    <!-- Doanh thu thành công -->
    <div class="col-xl-3 col-sm-6 mb-3 mb-xl-0">
      <div class="stat-card d-flex align-items-center justify-content-between p-3 bg-white rounded border shadow-sm h-100">
        <div>
          <p class="text-muted font-weight-medium small mb-1">Doanh thu tích lũy</p>
          <h4 class="font-weight-bold mb-0 text-dark"><?= number_format($total_revenue, 0, ',', '.') ?> đ</h4>
          <small class="text-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i>Đơn hoàn thành</small>
        </div>
        <div class="stat-icon-wrapper rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: #ecfdf5; color: #10b981; font-size: 1.25rem;">
          <i class="fas fa-wallet"></i>
        </div>
      </div>
    </div>

    <!-- Tổng số đơn hàng -->
    <div class="col-xl-3 col-sm-6 mb-3 mb-xl-0">
      <div class="stat-card d-flex align-items-center justify-content-between p-3 bg-white rounded border shadow-sm h-100">
        <div>
          <p class="text-muted font-weight-medium small mb-1">Tổng đơn đặt hàng</p>
          <h4 class="font-weight-bold mb-0 text-dark"><?= number_format($total_orders, 0, ',', '.') ?></h4>
          <a href="order/index.php" class="text-primary small font-weight-bold">Quản lý đơn &rarr;</a>
        </div>
        <div class="stat-icon-wrapper rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: #eff6ff; color: #3b82f6; font-size: 1.25rem;">
          <i class="fas fa-shopping-bag"></i>
        </div>
      </div>
    </div>

    <!-- Tồn kho & Mẫu mã -->
    <div class="col-xl-3 col-sm-6 mb-3 mb-xl-0">
      <div class="stat-card d-flex align-items-center justify-content-between p-3 bg-white rounded border shadow-sm h-100">
        <div>
          <p class="text-muted font-weight-medium small mb-1">Tồn kho thiết bị</p>
          <h4 class="font-weight-bold mb-0 text-dark"><?= number_format($total_stock, 0, ',', '.') ?> máy</h4>
          <small class="text-muted"><?= $total_products ?> dòng sản phẩm</small>
        </div>
        <div class="stat-icon-wrapper rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: #fdf4ff; color: #a855f7; font-size: 1.25rem;">
          <i class="fas fa-boxes"></i>
        </div>
      </div>
    </div>

    <!-- Phản hồi khách hàng -->
    <div class="col-xl-3 col-sm-6 mb-3 mb-xl-0">
      <div class="stat-card d-flex align-items-center justify-content-between p-3 bg-white rounded border shadow-sm h-100">
        <div>
          <p class="text-muted font-weight-medium small mb-1">Phản hồi khách</p>
          <h4 class="font-weight-bold mb-0 text-dark"><?= number_format($total_feedbacks, 0, ',', '.') ?></h4>
          <a href="feedback/index.php" class="text-warning font-weight-bold small"><i class="fas fa-comment-dots mr-1"></i>Xem phản hồi</a>
        </div>
        <div class="stat-icon-wrapper rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: #fffbeb; color: #f59e0b; font-size: 1.25rem;">
          <i class="fas fa-comments"></i>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <!-- CỘT TRÁI: BẢNG ĐƠN HÀNG GẦN ĐÂY -->
    <div class="col-lg-8 mb-4">
      <div class="custom-card bg-white rounded border shadow-sm h-100">
        <div class="p-3 px-4 d-flex justify-content-between align-items-center border-bottom">
          <div>
            <h6 class="font-weight-bold mb-0 text-dark">Đơn Hàng Gần Đây</h6>
            <small class="text-muted">Cập nhật theo thời gian thực</small>
          </div>
          <a href="order/index.php" class="btn btn-sm btn-outline-primary font-weight-bold px-3">Xem tất cả</a>
        </div>

        <div class="table-responsive">
          <table class="table table-hover mb-0 align-middle">
            <thead class="thead-light">
              <tr>
                <th class="border-top-0">MÃ ĐƠN</th>
                <th class="border-top-0">KHÁCH HÀNG</th>
                <th class="border-top-0">ĐIỆN THOẠI</th>
                <th class="border-top-0 text-right">TỔNG TIỀN</th>
                <th class="border-top-0 text-center">TRẠNG THÁI</th>
                <th class="border-top-0 text-center">XEM</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($recent_orders)): ?>
                <?php foreach ($recent_orders as $ord): 
                  // 0: Chờ xác nhận, 1: Đang vận chuyển, 2: Hoàn thành, 3: Đã hủy[cite: 5]
                  $st = (int)$ord['status'];
                  $badge_class = 'badge-secondary';
                  $badge_label = 'Chờ xử lý';
                  if ($st === 0) {
                      $badge_class = 'badge-warning text-dark';
                      $badge_label = 'Chờ duyệt';
                  } elseif ($st === 1) {
                      $badge_class = 'badge-info';
                      $badge_label = 'Đang giao';
                  } elseif ($st === 2) {
                      $badge_class = 'badge-success';
                      $badge_label = 'Hoàn thành';
                  } elseif ($st === 3) {
                      $badge_class = 'badge-danger';
                      $badge_label = 'Đã hủy';
                  }
                ?>
                  <tr>
                    <td class="font-weight-bold text-primary">#ORD-<?= $ord['id'] ?></td>
                    <td>
                      <div class="font-weight-bold text-dark text-truncate" style="max-width: 140px;">
                        <?= htmlspecialchars($ord['fullname']) ?>
                      </div>
                      <small class="text-muted"><?= !empty($ord['order_date']) ? date('d/m/Y H:i', strtotime($ord['order_date'])) : '' ?></small>
                    </td>
                    <td class="small"><?= htmlspecialchars($ord['phone_number']) ?></td>
                    <td class="font-weight-bold text-danger text-right">
                      <?= number_format($ord['total_money'], 0, ',', '.') ?> đ
                    </td>
                    <td class="text-center">
                      <span class="badge <?= $badge_class ?> px-2 py-1"><?= $badge_label ?></span>
                    </td>
                    <td class="text-center">
                      <a href="order/detail.php?id=<?= $ord['id'] ?>" class="btn btn-sm btn-light rounded-circle border" title="Chi tiết đơn">
                        <i class="fas fa-eye text-primary"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" class="text-center py-4 text-muted">Chưa có đơn hàng nào phát sinh.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- CỘT PHẢI: CẢNH BÁO TỒN KHO & LỐI TẮT NHANH -->
    <div class="col-lg-4 mb-4">
      <!-- Cảnh báo tồn kho ít -->
      <div class="custom-card bg-white rounded border shadow-sm p-3 mb-4">
        <h6 class="font-weight-bold text-dark mb-3 border-bottom pb-2">
          <i class="fas fa-exclamation-triangle text-danger mr-1"></i> Cảnh Báo Kho (≤ 5 máy)
        </h6>
        <?php if (!empty($lowStockVariants)): ?>
          <ul class="list-unstyled mb-0">
            <?php foreach ($lowStockVariants as $ls): 
              $spec = array_filter([$ls['color'], $ls['storage']]);
            ?>
              <li class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                <div style="max-width: 70%;">
                  <span class="font-weight-bold text-dark small d-block text-truncate"><?= htmlspecialchars($ls['title']) ?></span>
                  <small class="text-muted"><?= htmlspecialchars(implode(' - ', $spec)) ?></small>
                </div>
                <span class="badge badge-danger font-weight-bold">Còn <?= $ls['stock_quantity'] ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="text-center text-muted small py-3">
            <i class="fas fa-check text-success mr-1"></i>Tất cả mặt hàng đều dồi dào.
          </div>
        <?php endif; ?>
      </div>

      <!-- Thao tác quản trị nhanh -->
      <div class="custom-card bg-white rounded border shadow-sm p-3">
        <h6 class="font-weight-bold text-dark mb-3 border-bottom pb-2">
          <i class="fas fa-bolt text-warning mr-1"></i> Tác Vụ Nhanh
        </h6>
        <div class="d-grid gap-2">
          <a href="product/create.php" class="btn btn-outline-primary btn-block text-left mb-2 font-weight-bold btn-sm">
            <i class="fas fa-plus-circle mr-2"></i>Thêm sản phẩm mới
          </a>
          <a href="banner/index.php" class="btn btn-outline-secondary btn-block text-left mb-2 font-weight-bold btn-sm">
            <i class="fas fa-images mr-2"></i>Đổi banner trang chủ
          </a>
          <a href="feedback/index.php" class="btn btn-outline-warning btn-block text-left mb-2 font-weight-bold btn-sm text-dark">
            <i class="fas fa-envelope-open-text mr-2"></i>Hộp thư góp ý
          </a>
          <a href="../index.php" target="_blank" class="btn btn-light btn-block text-left font-weight-bold btn-sm border">
            <i class="fas fa-external-link-alt mr-2 text-primary"></i>Xem giao diện Website
          </a>
        </div>
      </div>
    </div>
  </div>

</main>

<?php include 'layout/scripts.php'; ?>