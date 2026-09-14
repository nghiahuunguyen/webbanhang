<?php 
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

// 1. Chỉ lấy những danh mục chưa bị xóa (deleted = 0)
$categories = executeResult("SELECT * FROM category WHERE deleted = 0 ORDER BY id DESC");

// 2. Đếm số lượng danh mục đang nằm trong thùng rác (deleted = 1)
$countTrash = executeResult("SELECT COUNT(id) as total FROM category WHERE deleted = 1", true);
$total_trash = $countTrash['total'] ?? 0;
$page_title = 'Quản Lý Danh Mục';
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

  <div class="custom-card">
    <div class="p-3 px-4 d-flex justify-content-between align-items-center border-bottom">
      <h5 class="font-weight-bold mb-0 text-dark">Quản Lý Danh Mục</h5>
      <div>
        <a href="trash.php" class="btn btn-sm btn-outline-danger font-weight-bold mr-2">
          <i class="fas fa-trash-alt mr-1"></i> Thùng rác 
          <span class="badge badge-danger"><?= $total_trash ?></span>
        </a>
        <a href="create.php" class="btn btn-sm btn-success font-weight-bold">
          <i class="fas fa-plus mr-1"></i> Thêm mới loại
        </a>
      </div>
    </div>

    <div class="p-4 table-responsive">
      <table class="table table-bordered table-hover mb-0">
        <thead class="thead-light">
          <tr>
            <th width="70px" class="text-center align-middle">STT</th>
            <th class="align-middle">TÊN DANH MỤC</th>
            <th width="120px" class="text-center align-middle">HÀNH ĐỘNG</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($categories)): ?>
            <?php $index = 1; foreach ($categories as $item): ?>
              <tr>
                <td class="text-center align-middle"><?= $index++ ?></td>
                <td class="align-middle"><?= htmlspecialchars($item['name']) ?></td>
                <td class="text-center align-middle">
                  <div class="d-inline-flex align-items-center">
                    <a href="edit.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-warning mr-2" title="Sửa">
                      <i class="fas fa-edit"></i>
                    </a>
                   <a href="delete.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-danger" title="Xóa">
                    <i class="fas fa-trash-alt"></i>
                  </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="3" class="text-center text-muted py-4 align-middle">Chưa có danh mục nào.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<?php include '../layout/scripts.php'; ?>

<!-- SCRIPT ĐẾM NGƯỢC 3 GIÂY ĐỂ ẨN VÀ XÓA THÔNG BÁO -->
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