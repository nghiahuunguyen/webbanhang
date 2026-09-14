<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once('../../database/dbhelper.php');
require_once('../check_role.php');
require_once('../../config/mail_helper.php');

// Cho phép Admin (1) và Nhân viên (3) truy cập
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role_id'], [1, 3])) {
    header('Location: ../auth/login.php');
    die();
}

// XỬ LÝ CHUYỂN TIẾP TRẠNG THÁI TỪNG BƯỚC (0 -> 1 -> 2 HOẶC HỦY 3)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'step_status') {
    $order_id   = intval($_POST['order_id'] ?? 0);
    $new_status = intval($_POST['new_status'] ?? -1);

    if ($order_id > 0 && in_array($new_status, [1, 2, 3])) {
        // Lấy thông tin đơn hàng hiện tại
        $curr_order = executeResult("SELECT id, fullname, email, status, total_money FROM `Orders` WHERE id = $order_id LIMIT 1", true);

        if ($curr_order) {
            $curr_st = (int)$curr_order['status'];
            $is_valid_transition = false;

            // Kiểm tra tính hợp lệ của luồng trạng thái
            if ($curr_st === 0 && ($new_status === 1 || $new_status === 3)) {
                $is_valid_transition = true; // 0 lên 1 (Giao hàng) hoặc 0 sang 3 (Hủy)
            } elseif ($curr_st === 1 && $new_status === 2) {
                $is_valid_transition = true; // 1 lên 2 (Hoàn thành)
            }

            if ($is_valid_transition) {
                // Cập nhật CSDL
                execute("UPDATE `Orders` SET status = $new_status WHERE id = $order_id");

                // Nếu hủy đơn hàng (3) thì hoàn lại số lượng tồn kho
                if ($new_status === 3) {
                    $details = executeResult("SELECT variant_id, num FROM `Order_Details` WHERE order_id = $order_id");
                    if (!empty($details)) {
                        foreach ($details as $d) {
                            if (!empty($d['variant_id'])) {
                                execute("UPDATE `Product_Variant` SET stock_quantity = stock_quantity + {$d['num']} WHERE id = {$d['variant_id']}");
                            }
                        }
                    }
                }

                // Gửi email thông báo cho khách hàng nếu có email hợp lệ
                $cust_email = trim($curr_order['email'] ?? '');
                $cust_name  = trim($curr_order['fullname'] ?? 'Quý khách');

                if (!empty($cust_email) && filter_var($cust_email, FILTER_VALIDATE_EMAIL) && $cust_email !== 'pos@cuahang.local') {
                    $status_titles = [
                        1 => 'Đơn hàng đang trên đường giao đến bạn',
                        2 => 'Đơn hàng đã được giao thành công',
                        3 => 'Đơn hàng đã bị hủy'
                    ];

                    $status_messages = [
                        1 => 'Đơn hàng của bạn đã được kiểm duyệt và hiện đang được chuyển phát đến địa chỉ bạn cung cấp.',
                        2 => 'Đơn hàng của bạn đã hoàn thành giao dịch thành công. Cảm ơn bạn đã tin tưởng mua sắm tại cửa hàng!',
                        3 => 'Đơn hàng của bạn đã được chuyển sang trạng thái đã hủy. Nếu có nhầm lẫn, vui lòng phản hồi lại email này.'
                    ];

                    $subject = "Cập nhật đơn hàng #ORD-{$order_id}: " . ($status_titles[$new_status] ?? '');
                    $totalFmt = number_format($curr_order['total_money'], 0, ',', '.');

                    $bodyMail = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px; background: #ffffff;'>
                        <div style='text-align: center; border-bottom: 2px solid #2563eb; padding-bottom: 15px; margin-bottom: 20px;'>
                            <h2 style='color: #2563eb; margin: 0;'>" . MAIL_FROM_NAME . "</h2>
                            <p style='color: #64748b; margin: 5px 0 0 0; font-size: 14px;'>Thông báo trạng thái đơn hàng</p>
                        </div>
                        <p>Xin chào <strong>{$cust_name}</strong>,</p>
                        <p>Hệ thống xin thông báo cập nhật về đơn hàng <strong>#ORD-{$order_id}</strong>:</p>
                        <div style='padding: 15px; background: #f8fafc; border-left: 4px solid #2563eb; border-radius: 4px; margin: 15px 0;'>
                            <strong style='font-size: 16px; color: #1e293b;'>" . ($status_titles[$new_status] ?? '') . "</strong>
                            <p style='margin: 8px 0 0 0; color: #475569; font-size: 14px;'>" . ($status_messages[$new_status] ?? '') . "</p>
                        </div>
                        <p style='margin: 15px 0 5px 0; font-size: 14px;'>Tổng tiền thanh toán: <strong style='color: #dc2626;'>{$totalFmt} đ</strong></p>
                        <div style='margin-top: 25px; padding: 12px; background: #f8fafc; border-radius: 8px; font-size: 12px; color: #64748b; text-align: center;'>
                            Mọi thắc mắc cần hỗ trợ xin vui lòng phản hồi qua email này hoặc liên hệ hotline cửa hàng.
                        </div>
                    </div>";

                    sendCustomMail($cust_email, $cust_name, $subject, $bodyMail);
                }

                $_SESSION['success_msg'] = "Đã cập nhật đơn hàng #$order_id sang trạng thái tiếp theo và gửi email thông báo!";
            }
        }
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    die();
}

$success_msg = $_SESSION['success_msg'] ?? '';
unset($_SESSION['success_msg']);

// 1. Bộ lọc & Tìm kiếm
$status_filter = isset($_GET['status']) && $_GET['status'] !== '' ? intval($_GET['status']) : '';
$search_query  = trim($_GET['s'] ?? '');

$where_clauses = ["1=1"];

if ($status_filter !== '') {
    $where_clauses[] = "o.status = $status_filter";
}

if (!empty($search_query)) {
    $safe_s = str_replace("'", "\'", $search_query);
    $where_clauses[] = "(o.fullname LIKE '%$safe_s%' OR o.phone_number LIKE '%$safe_s%' OR o.id = '$safe_s')";
}

$where_sql = implode(' AND ', $where_clauses);

// 2. Cấu hình phân trang: 5 đơn / 1 trang
$limit = 5;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;

$countOrder = executeResult("SELECT COUNT(o.id) AS total FROM `Orders` o WHERE $where_sql", true);
$total_records = intval($countOrder['total'] ?? 0);
$total_pages = ceil($total_records / $limit);
if ($total_pages > 0 && $page > $total_pages) $page = $total_pages;

$start = ($page - 1) * $limit;
if ($start < 0) $start = 0;

// 3. Lấy danh sách đơn hàng kèm số lượng mặt hàng
$sql_orders = "SELECT o.*, 
                      u.fullname AS staff_name, 
                      COUNT(od.id) AS total_items
               FROM `Orders` o
               LEFT JOIN `User` u ON o.user_id = u.id
               LEFT JOIN `Order_Details` od ON o.id = od.order_id
               WHERE $where_sql
               GROUP BY o.id
               ORDER BY o.order_date DESC, o.id DESC
               LIMIT $start, $limit";
$orders = executeResult($sql_orders);

$page_title = 'Quản Lý Đơn Hàng';
include '../layout/head.php'; 
include '../layout/sidebar.php'; 
?>

<style>
  .badge-status {
    padding: 6px 12px;
    border-radius: 50rem;
    font-size: 0.78rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 5px;
  }
  .badge-status-0 { background-color: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
  .badge-status-1 { background-color: #eff6ff; color: #1d4ed8; border: 1px solid #dbeafe; }
  .badge-status-2 { background-color: #f0fdf4; color: #15803d; border: 1px solid #dcfce7; }
  .badge-status-3 { background-color: #fef2f2; color: #b91c1c; border: 1px solid #fee2e2; }
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

  <div class="custom-card bg-white rounded-lg shadow-sm border">
    <div class="p-3 px-4 d-flex justify-content-between align-items-center border-bottom flex-wrap">
      <div>
        <h5 class="font-weight-bold mb-0 text-dark">Quản Lý Đơn Hàng</h5>
        <small class="text-muted">Kiểm duyệt và chuyển trạng thái đơn theo từng bước (Chờ xác nhận &rarr; Giao hàng &rarr; Hoàn thành)</small>
      </div>
      <div class="mt-2 mt-md-0">
        <a href="create.php" class="btn btn-sm btn-primary font-weight-bold shadow-sm px-3 py-2" style="border-radius: 8px;">
          <i class="fas fa-cash-register mr-1"></i> Bán hàng tại quầy (POS)
        </a>
      </div>
    </div>

    <!-- KHỐI TÌM KIẾM & BỘ LỌC -->
    <div class="p-3 px-4 bg-light border-bottom">
      <form method="GET" class="form-row align-items-center">
        <div class="col-md-5 mb-2 mb-md-0">
          <div class="input-group input-group-sm">
            <input type="text" name="s" class="form-control" placeholder="Tìm theo mã đơn (#ID), tên khách, số điện thoại..." value="<?= htmlspecialchars($search_query) ?>">
            <div class="input-group-append">
              <button class="btn btn-secondary font-weight-bold" type="submit"><i class="fas fa-search"></i></button>
            </div>
          </div>
        </div>

        <div class="col-md-4 mb-2 mb-md-0">
          <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
            <option value="">-- Tất cả trạng thái --</option>
            <option value="0" <?= ($status_filter === 0) ? 'selected' : '' ?>>0: Chờ xác nhận</option>
            <option value="1" <?= ($status_filter === 1) ? 'selected' : '' ?>>1: Đang vận chuyển</option>
            <option value="2" <?= ($status_filter === 2) ? 'selected' : '' ?>>2: Hoàn thành</option>
            <option value="3" <?= ($status_filter === 3) ? 'selected' : '' ?>>3: Đã hủy</option>
          </select>
        </div>

        <div class="col-md-3">
          <?php if (!empty($search_query) || $status_filter !== ''): ?>
            <a href="index.php" class="btn btn-sm btn-outline-secondary font-weight-bold">
              <i class="fas fa-sync-alt mr-1"></i> Xóa bộ lọc
            </a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- BẢNG DANH SÁCH ĐƠN HÀNG -->
    <div class="p-4 table-responsive">
      <table class="table table-bordered table-hover mb-0 align-middle">
        <thead class="thead-light">
          <tr>
            <th width="75px" class="text-center align-middle">MÃ ĐƠN</th>
            <th class="align-middle">THÔNG TIN KHÁCH HÀNG</th>
            <th width="105px" class="text-center align-middle">MẶT HÀNG</th>
            <th width="140px" class="align-middle text-right">TỔNG TIỀN</th>
            <th width="150px" class="text-center align-middle">TRẠNG THÁI</th>
            <th width="150px" class="align-middle">THỜI GIAN ĐẶT</th>
            <th width="180px" class="text-center align-middle">TIẾN TRÌNH & THAO TÁC</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $item): 
                $st = (int)$item['status'];
            ?>
              <tr>
                <!-- Mã đơn -->
                <td class="text-center align-middle font-weight-bold text-primary">
                  #<?= $item['id'] ?>
                </td>

                <!-- Khách hàng -->
                <td class="align-middle">
                  <div class="font-weight-bold text-dark"><?= htmlspecialchars($item['fullname']) ?></div>
                  <div class="small text-muted"><i class="fas fa-phone mr-1"></i><?= htmlspecialchars($item['phone_number']) ?></div>
                  <?php if (!empty($item['staff_name'])): ?>
                    <small class="badge badge-light border text-secondary mt-1">NV: <?= htmlspecialchars($item['staff_name']) ?></small>
                  <?php endif; ?>
                </td>

                <!-- Số lượng món -->
                <td class="text-center align-middle">
                  <span class="badge badge-pill badge-light border px-2 py-1 font-weight-bold">
                    <?= $item['total_items'] ?> món
                  </span>
                </td>

                <!-- Tổng tiền -->
                <td class="text-right align-middle font-weight-bold text-danger" style="font-size: 0.95rem;">
                  <?= number_format($item['total_money'], 0, ',', '.') ?> đ
                </td>

                <!-- Trạng thái đơn -->
                <td class="text-center align-middle">
                  <?php if ($st === 0): ?>
                    <span class="badge-status badge-status-0">
                      <i class="fas fa-clock"></i> Chờ xác nhận
                    </span>
                  <?php elseif ($st === 1): ?>
                    <span class="badge-status badge-status-1">
                      <i class="fas fa-shipping-fast"></i> Đang giao
                    </span>
                  <?php elseif ($st === 2): ?>
                    <span class="badge-status badge-status-2">
                      <i class="fas fa-check-circle"></i> Hoàn thành
                    </span>
                  <?php elseif ($st === 3): ?>
                    <span class="badge-status badge-status-3">
                      <i class="fas fa-times-circle"></i> Đã hủy
                    </span>
                  <?php endif; ?>
                </td>

                <!-- Ngày đặt -->
                <td class="align-middle small text-muted">
                  <div><i class="far fa-calendar-alt mr-1"></i><?= date('d/m/Y', strtotime($item['order_date'])) ?></div>
                  <div><i class="far fa-clock mr-1"></i><?= date('H:i', strtotime($item['order_date'])) ?></div>
                </td>

                <!-- TIẾN TRÌNH CHUYỂN BƯỚC TUẦN TỰ (0 -> 1 -> 2) -->
                <td class="text-center align-middle">
                  <div class="d-inline-flex align-items-center flex-wrap justify-content-center" style="gap: 4px;">
                    <!-- Xem chi tiết -->
                    <a href="detail.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-info" title="Xem chi tiết">
                      <i class="fas fa-eye"></i>
                    </a>

                    <!-- Nếu đang ở 0 (Chờ duyệt): Cho phép Duyệt giao (lên 1) hoặc Hủy (sang 3) -->
                    <?php if ($st === 0): ?>
                      <form method="POST" class="d-inline mb-0" onsubmit="return confirm('Duyệt đơn #<?= $item['id'] ?> và chuyển sang đang vận chuyển?');">
                        <input type="hidden" name="action" value="step_status">
                        <input type="hidden" name="order_id" value="<?= $item['id'] ?>">
                        <input type="hidden" name="new_status" value="1">
                        <button type="submit" class="btn btn-sm btn-outline-primary font-weight-bold" title="Duyệt giao hàng">
                          <i class="fas fa-truck mr-1"></i> Giao hàng
                        </button>
                      </form>

                      <form method="POST" class="d-inline mb-0" onsubmit="return confirm('Bạn có chắc chắn muốn HỦY đơn #<?= $item['id'] ?>?');">
                        <input type="hidden" name="action" value="step_status">
                        <input type="hidden" name="order_id" value="<?= $item['id'] ?>">
                        <input type="hidden" name="new_status" value="3">
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hủy đơn">
                          <i class="fas fa-times"></i>
                        </button>
                      </form>

                    <!-- Nếu đang ở 1 (Đang giao): Chỉ cho phép Hoàn tất (lên 2) -->
                    <?php elseif ($st === 1): ?>
                      <form method="POST" class="d-inline mb-0" onsubmit="return confirm('Khách đã nhận hàng và hoàn tất đơn #<?= $item['id'] ?>?');">
                        <input type="hidden" name="action" value="step_status">
                        <input type="hidden" name="order_id" value="<?= $item['id'] ?>">
                        <input type="hidden" name="new_status" value="2">
                        <button type="submit" class="btn btn-sm btn-success font-weight-bold" title="Hoàn thành đơn">
                          <i class="fas fa-check mr-1"></i> Đã giao
                        </button>
                      </form>

                    <!-- Đã ở trạng thái 2 (Hoàn tất) hoặc 3 (Đã hủy): Khóa chuyển đổi -->
                    <?php else: ?>
                      <span class="badge badge-light border text-muted px-2 py-1 small">
                        <i class="fas fa-lock mr-1"></i> Đã đóng
                      </span>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="text-center text-muted py-5 align-middle">
                <i class="fas fa-receipt fa-2x mb-2 text-black-50 d-block"></i>
                Không tìm thấy đơn hàng nào phù hợp với điều kiện lọc.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- PHÂN TRANG: 5 ĐƠN / TRANG -->
    <?php if ($total_pages > 1): ?>
      <div class="card-footer bg-white border-top py-3">
        <nav class="d-flex justify-content-center">
          <ul class="pagination pagination-custom mb-0">
            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
              <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= $status_filter ?>&s=<?= urlencode($search_query) ?>" aria-label="Trước">
                <i class="fas fa-chevron-left fa-xs"></i>
              </a>
            </li>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
              <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                  <a class="page-link" href="?page=<?= $i ?>&status=<?= $status_filter ?>&s=<?= urlencode($search_query) ?>"><?= $i ?></a>
                </li>
              <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                <li class="page-item disabled"><span class="page-link border-0">...</span></li>
              <?php endif; ?>
            <?php endfor; ?>

            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
              <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= $status_filter ?>&s=<?= urlencode($search_query) ?>" aria-label="Sau">
                <i class="fas fa-chevron-right fa-xs"></i>
              </a>
            </li>
          </ul>
        </nav>
      </div>

      <style>
        .pagination-custom {
          display: flex;
          align-items: center;
          gap: 6px;
        }
        .pagination-custom .page-item .page-link {
          min-width: 36px;
          height: 36px;
          padding: 0 10px;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          font-weight: 600;
          font-size: 0.875rem;
          color: #4b5563;
          background-color: #f3f4f6;
          border: 1px solid transparent;
          border-radius: 50rem !important;
          transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .pagination-custom .page-item .page-link:hover {
          background-color: #e5e7eb;
          color: #111827;
          transform: translateY(-1px);
        }
        .pagination-custom .page-item.active .page-link {
          background-color: #2563eb;
          border-color: #2563eb;
          color: #ffffff;
          box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);
        }
        .pagination-custom .page-item.disabled .page-link {
          background-color: transparent;
          color: #9ca3af;
          cursor: not-allowed;
          box-shadow: none;
          transform: none;
        }
      </style>
    <?php endif; ?>
  </div>
</main>

<?php include '../layout/scripts.php'; ?>

<script>
// Tự ẩn toast sau 3 giây
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