<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once('../../database/dbhelper.php');
require_once('../check_role.php');

// Chỉ Admin (role_id = 1) mới có quyền quản lý danh sách tài khoản nội bộ
requireAdmin();

// Lấy thông báo và xóa session ngay để không bị hiện lại khi F5
$success_msg = $_SESSION['success_msg'] ?? '';
unset($_SESSION['success_msg']);

// 1. Cấu hình phân trang: 5 người / trang
$limit = 5;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;

// 2. Đếm tổng số tài khoản Admin (role_id = 1) và Nhân viên (role_id = 3) chưa bị xóa (deleted = 0)
$countStaff = executeResult("SELECT COUNT(id) AS total FROM `User` WHERE role_id IN (1, 3) AND deleted = 0", true);
$total_records = intval($countStaff['total'] ?? 0);
$total_pages = ceil($total_records / $limit);
if ($total_pages > 0 && $page > $total_pages) $page = $total_pages;

$start = ($page - 1) * $limit;
if ($start < 0) $start = 0;

// 3. Lấy danh sách tài khoản nội bộ (Admin + Nhân viên) theo từng trang
$staffs = executeResult("SELECT * FROM `User` WHERE role_id IN (1, 3) AND deleted = 0 ORDER BY role_id ASC, id DESC LIMIT $start, $limit");

// 4. Đếm số lượng tài khoản đang nằm trong thùng rác (deleted = 1)
$countTrash = executeResult("SELECT COUNT(id) AS total FROM `User` WHERE role_id IN (1, 3) AND deleted = 1", true);
$total_trash = $countTrash['total'] ?? 0;

$page_title = 'Quản Lý Nhân Viên & Quản Trị Viên';
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
      <h5 class="font-weight-bold mb-0 text-dark">Quản Lý Nhân Sự & Quản Trị</h5>
      <div class="mt-2 mt-md-0">
        <a href="trash.php" class="btn btn-sm btn-outline-danger font-weight-bold mr-2">
          <i class="fas fa-trash-alt mr-1"></i> Thùng rác 
          <span class="badge badge-danger"><?= $total_trash ?></span>
        </a>
        <a href="create.php" class="btn btn-sm btn-success font-weight-bold">
          <i class="fas fa-user-plus mr-1"></i> Thêm tài khoản
        </a>
      </div>
    </div>

    <div class="p-4 table-responsive">
      <table class="table table-bordered table-hover mb-0">
        <thead class="thead-light">
          <tr>
            <th width="50px" class="text-center align-middle">STT</th>
            <th class="align-middle">HỌ VÀ TÊN</th>
            <th class="align-middle">TÀI KHOẢN</th>
            <th class="align-middle text-center">VAI TRÒ</th>
            <th class="align-middle">EMAIL</th>
            <th class="align-middle">SỐ ĐIỆN THOẠI</th>
            <th class="align-middle">ĐỊA CHỈ</th>
            <th width="110px" class="text-center align-middle">HÀNH ĐỘNG</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($staffs)): ?>
            <?php 
              $index = $start + 1; 
              foreach ($staffs as $item): 
                $is_admin = ((int)$item['role_id'] === 1);
            ?>
              <tr>
                <td class="text-center align-middle font-weight-bold"><?= $index++ ?></td>
                <td class="align-middle font-weight-bold text-dark">
                  <?= htmlspecialchars($item['fullname']) ?>
                </td>
                <td class="align-middle">
                  <span class="badge badge-light border text-primary font-weight-bold px-2 py-1">
                    <?= htmlspecialchars($item['account'] ?? 'Chưa đặt') ?>
                  </span>
                </td>
                <!-- Cột Vai trò -->
                <td class="align-middle text-center">
                  <?php if ($is_admin): ?>
                    <span class="badge badge-danger px-2 py-1 font-weight-bold">
                      <i class="fas fa-user-shield mr-1"></i> Quản trị viên
                    </span>
                  <?php else: ?>
                    <span class="badge badge-info px-2 py-1 font-weight-bold">
                      <i class="fas fa-user-tie mr-1"></i> Nhân viên
                    </span>
                  <?php endif; ?>
                </td>
                <td class="align-middle"><?= htmlspecialchars($item['email']) ?></td>
                <td class="align-middle"><?= htmlspecialchars($item['phone_number']) ?></td>
                <td class="align-middle small text-muted"><?= htmlspecialchars($item['address'] ?? '—') ?></td>
              <td class="text-center align-middle">
                <div class="d-inline-flex align-items-center">
                  <!-- Nút Xem chi tiết: Dành cho cả Admin và Nhân viên -->
                  <a href="detail.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-info mr-2" title="Xem chi tiết">
                    <i class="fas fa-eye"></i>
                  </a>

                  <!-- Nút Chỉnh sửa: Dành cho cả Admin và Nhân viên (nếu là nhân viên thì thêm class mr-2 để cách nút xóa) -->
                  <a href="edit.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-warning <?= !$is_admin ? 'mr-2' : '' ?>" title="Chỉnh sửa">
                    <i class="fas fa-edit"></i>
                  </a>

                  <!-- Nút Xóa: CHỈ HIỂN THỊ VỚI NHÂN VIÊN (role_id != 1), ẨN HOÀN TOÀN VỚI ADMIN -->
                  <?php if (!$is_admin): ?>
                    <a href="delete.php?id=<?= $item['id'] ?>" 
                      class="btn btn-sm btn-outline-danger" 
                      title="Xóa vào thùng rác" 
                      onclick="return confirm('Bạn có chắc muốn chuyển tài khoản nhân viên này vào thùng rác?');">
                      <i class="fas fa-trash-alt"></i>
                    </a>
                  <?php endif; ?>
                </div>
              </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="text-center text-muted py-4 align-middle">Chưa có tài khoản nào trong danh sách.</td>
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