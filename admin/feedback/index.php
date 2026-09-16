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

// Lấy thông báo và xóa session ngay để không bị hiện lại khi F5
$success_msg = $_SESSION['success_msg'] ?? '';
unset($_SESSION['success_msg']);

// 1. Cấu hình phân trang: 5 phản hồi / trang
$limit = 5;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;

// 2. Đếm tổng số phản hồi trong bảng FeedBack
$countFeedback = executeResult("SELECT COUNT(id) AS total FROM `FeedBack`", true);
$total_records = intval($countFeedback['total'] ?? 0);
$total_pages = ceil($total_records / $limit);
if ($total_pages > 0 && $page > $total_pages) $page = $total_pages;

$start = ($page - 1) * $limit;
if ($start < 0) $start = 0;

// 3. Lấy danh sách phản hồi theo từng trang
$feedbacks = executeResult("SELECT * FROM `FeedBack` ORDER BY id DESC LIMIT $start, $limit");

$page_title = 'Quản Lý Phản Hồi Khách Hàng';
include '../layout/head.php'; 
include '../layout/sidebar.php'; 
?>

<!-- POPUP THÔNG BÁO GÓC TRÊN BÊN PHẢI -->
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
        <h5 class="font-weight-bold mb-0 text-dark">Danh Sách Ý Kiến & Phản Hồi</h5>
        <small class="text-muted">Tổng cộng <?= number_format($total_records, 0, ',', '.') ?> phản hồi từ khách hàng</small>
      </div>
    </div>

    <div class="p-4 table-responsive">
      <table class="table table-bordered table-hover mb-0 align-middle">
        <thead class="thead-light">
          <tr>
            <th width="50px" class="text-center align-middle">STT</th>
            <th class="align-middle">HỌ VÀ TÊN</th>
            <th class="align-middle">EMAIL / SĐT</th>
            <th class="align-middle">CHỦ ĐỀ</th>
            <th class="align-middle">NỘI DUNG</th>
            <th width="140px" class="text-center align-middle">THỜI GIAN</th>
            <th width="110px" class="text-center align-middle">HÀNH ĐỘNG</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($feedbacks)): ?>
            <?php 
              $index = $start + 1; 
              foreach ($feedbacks as $item): 
                $fullname = trim(($item['firstname'] ?? '') . ' ' . ($item['lastname'] ?? ''));
            ?>
              <tr>
                <td class="text-center align-middle font-weight-bold"><?= $index++ ?></td>
                
                <td class="align-middle font-weight-bold text-dark">
                  <?= htmlspecialchars($fullname ?: 'Khách ẩn danh') ?>
                </td>

                <td class="align-middle small">
                  <div><i class="fas fa-envelope mr-1 text-muted"></i><?= htmlspecialchars($item['email'] ?? '—') ?></div>
                  <?php if (!empty($item['phone_number'])): ?>
                    <div><i class="fas fa-phone-alt mr-1 text-muted"></i><?= htmlspecialchars($item['phone_number']) ?></div>
                  <?php endif; ?>
                </td>

                <td class="align-middle font-weight-bold text-primary">
                  <?= htmlspecialchars($item['subject_name'] ?? '—') ?>
                </td>

                <td class="align-middle small text-muted text-truncate" style="max-width: 260px;">
                  <?= htmlspecialchars($item['note'] ?? '') ?>
                </td>

                <td class="text-center align-middle small text-muted">
                  <?= !empty($item['created_at']) ? date('d/m/Y H:i', strtotime($item['created_at'])) : '—' ?>
                </td>

                <td class="text-center align-middle">
                  <div class="d-inline-flex align-items-center">
                    <!-- Nút Xem chi tiết chuyển sang detail.php -->
                    <a href="detail.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-info mr-2" title="Xem chi tiết">
                      <i class="fas fa-eye"></i>
                    </a>

                    <!-- Nút Xóa phản hồi -->
                    <a href="reply.php?id=<?= $item['id'] ?>" 
                      class="btn btn-sm btn-outline-primary" 
                      title="Gửi mail phản hồi">
                      <i class="fas fa-paper-plane"></i>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="text-center text-muted py-4 align-middle">Chưa có phản hồi nào từ khách hàng.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- THANH PHÂN TRANG (PAGINATION) -->
    <?php if ($total_pages > 1): ?>
      <div class="card-footer bg-white border-top py-3">
        <nav class="d-flex justify-content-center">
          <ul class="pagination pagination-custom mb-0">
            <!-- Nút Trang Trước -->
            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
              <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Trước">
                <i class="fas fa-chevron-left fa-xs"></i>
              </a>
            </li>

            <!-- Các nút số trang -->
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
              <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                  <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
              <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                <li class="page-item disabled"><span class="page-link border-0">...</span></li>
              <?php endif; ?>
            <?php endfor; ?>

            <!-- Nút Trang Kế Tiếp -->
            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
              <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Sau">
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