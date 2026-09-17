<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once('../../database/dbhelper.php');
require_once('../check_role.php');

// Chỉ Admin tối cao mới quản lý vai trò
requireAdmin();

// Lấy thông báo từ session
$success_msg = $_SESSION['success_msg'] ?? '';
$error_msg   = $_SESSION['error_msg'] ?? '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

// Lấy danh sách vai trò và số lượng quyền được cấp
$roles = executeResult("SELECT r.*, COUNT(rp.permission_id) AS total_perms,
                       (SELECT COUNT(u.id) FROM `User` u WHERE u.role_id = r.id AND u.deleted = 0) AS total_users
                        FROM `Role` r
                        LEFT JOIN `Role_Permission` rp ON r.id = rp.role_id
                        GROUP BY r.id
                        ORDER BY r.id ASC");

$page_title = 'Quản Lý Vai Trò & Phân Quyền';
include '../layout/head.php'; 
include '../layout/sidebar.php'; 
?>

<!-- TOAST THÔNG BÁO GÓC PHẢI TRÊN (CHUẨN PRODUCT) -->
<div id="dynamic-toast-wrapper" style="position: fixed; top: 20px; right: 20px; z-index: 99999; min-width: 320px; display: none; transition: all 0.4s ease;">
  <div class="toast show shadow-lg border-0" role="alert">
    <div class="toast-header bg-success text-white" id="toastHeader">
      <i class="fas fa-check-circle mr-2" id="toastIcon"></i>
      <strong class="mr-auto" id="toastTitle">Thông báo</strong>
      <small class="text-white">Vừa xong</small>
      <button type="button" class="ml-2 mb-1 close text-white" onclick="document.getElementById('dynamic-toast-wrapper').style.display='none'" style="outline: none;">
        <span>&times;</span>
      </button>
    </div>
    <div class="toast-body bg-white text-dark font-weight-bold" id="toastBody"></div>
  </div>
</div>

<?php if (!empty($success_msg)): ?>
  <script>
    window.addEventListener('DOMContentLoaded', function() {
      showToast('<?= addslashes($success_msg) ?>', true);
    });
  </script>
<?php endif; ?>

<?php if (!empty($error_msg)): ?>
  <script>
    window.addEventListener('DOMContentLoaded', function() {
      showToast('<?= addslashes($error_msg) ?>', false);
    });
  </script>
<?php endif; ?>

<!-- MAIN CONTENT -->
<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-3">
  <?php include '../layout/topbar.php'; ?>

  <div class="custom-card bg-white rounded shadow-sm border mb-4">
    <div class="p-3 px-4 d-flex justify-content-between align-items-center border-bottom flex-wrap">
      <div>
        <h5 class="font-weight-bold mb-0 text-dark">
          <i class="fas fa-user-shield text-primary mr-2"></i>Danh Sách Vai Trò (Roles)
        </h5>
        <small class="text-muted">Tùy biến quyền hạn và phân quyền cho từng chức vụ</small>
      </div>
      <a href="create.php" class="btn btn-sm btn-success font-weight-bold shadow-sm">
        <i class="fas fa-plus mr-1"></i> Thêm Vai Trò Mới
      </a>
    </div>

    <div class="p-4 table-responsive">
      <table class="table table-bordered table-hover mb-0 align-middle">
        <thead class="thead-light">
          <tr>
            <th width="60px" class="text-center align-middle">ID</th>
            <th class="align-middle">TÊN VAI TRÒ</th>
            <th class="text-center align-middle">SỐ QUYỀN ĐƯỢC CẤP</th>
            <th class="text-center align-middle">NHÂN SỰ ĐANG GIỮ</th>
            <th width="140px" class="text-center align-middle">TÁC VỤ</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($roles)): ?>
            <?php foreach ($roles as $r): 
              $is_super = ((int)$r['id'] === 1);
            ?>
              <tr>
                <td class="text-center font-weight-bold text-muted"><?= $r['id'] ?></td>
                <td>
                  <strong class="text-dark font-weight-bold"><?= htmlspecialchars($r['name']) ?></strong>
                  <?php if ($is_super): ?>
                    <span class="badge badge-danger ml-2 font-weight-bold">Tối cao</span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <?php if ($is_super): ?>
                    <span class="badge badge-success font-weight-bold px-2 py-1">Toàn quyền hệ thống</span>
                  <?php else: ?>
                    <span class="badge badge-primary font-weight-bold px-2 py-1"><?= $r['total_perms'] ?> chức năng</span>
                  <?php endif; ?>
                </td>
                <td class="text-center font-weight-bold text-secondary">
                  <?= $r['total_users'] ?> người dùng
                </td>
                <td class="text-center">
                  <div class="d-inline-flex align-items-center">
                    <a href="edit.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-warning mr-2" title="Chỉnh sửa phân quyền">
                      <i class="fas fa-key"></i>
                    </a>
                    <?php if (!in_array((int)$r['id'], [1, 2])): ?>
                      <a href="delete.php?id=<?= $r['id'] ?>" 
                         class="btn btn-sm btn-outline-danger" 
                         title="Xóa vai trò"
                         onclick="return confirm('Bạn có chắc muốn xóa vai trò này? Tất cả người dùng mang vai trò này cần được gán lại!');">
                        <i class="fas fa-trash-alt"></i>
                      </a>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" class="text-center text-muted py-4 align-middle">Chưa có vai trò nào được tạo.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<?php include '../layout/scripts.php'; ?>

<!-- SCRIPT TOAST GÓC PHẢI TRÊN GIỐNG TRANG PRODUCT -->
<script>
  function showToast(msg, isSuccess = true) {
    var wrapper = document.getElementById('dynamic-toast-wrapper');
    var header = document.getElementById('toastHeader');
    var title = document.getElementById('toastTitle');
    var body = document.getElementById('toastBody');
    var icon = document.getElementById('toastIcon');

    if (isSuccess) {
      header.className = 'toast-header bg-success text-white';
      icon.className = 'fas fa-check-circle mr-2';
      title.textContent = 'Thành công';
    } else {
      header.className = 'toast-header bg-danger text-white';
      icon.className = 'fas fa-exclamation-circle mr-2';
      title.textContent = 'Lỗi';
    }

    body.textContent = msg;
    wrapper.style.display = 'block';

    setTimeout(function() {
      wrapper.style.display = 'none';
    }, 3000);
  }
</script>