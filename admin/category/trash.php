<?php 
session_start();
require_once('../../database/dbhelper.php');

$success_msg = $_SESSION['success_msg'] ?? '';
unset($_SESSION['success_msg']);

// Lấy danh sách các danh mục nằm trong thùng rác (deleted = 1)
$trash_list = executeResult("SELECT * FROM category WHERE deleted = 1 ORDER BY id DESC");
$page_title = 'Thùng Rác Danh Mục';
include '../layout/head.php'; 
include '../layout/sidebar.php'; 
?>

<!-- TOAST THÔNG BÁO GÓC PHẢI TRÊN (3 GIÂY TỰ BIẾN MẤT) -->
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
      <div>
        <h5 class="font-weight-bold mb-1 text-danger"><i class="fas fa-trash-alt mr-2"></i>Thùng Rác Danh Mục</h5>
        <small class="text-muted">Danh sách các loại sản phẩm đã tạm xóa</small>
      </div>
      <a href="index.php" class="btn btn-sm btn-secondary font-weight-bold">
        <i class="fas fa-arrow-left mr-1"></i> Quay lại danh sách
      </a>
    </div>

    <div class="p-4 table-responsive">
      <table class="table table-bordered table-hover mb-0">
        <thead class="thead-light">
          <tr>
            <th width="70px" class="text-center align-middle">STT</th>
            <th class="align-middle">TÊN DANH MỤC</th>
            <th width="140px" class="text-center align-middle">TÁC VỤ</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($trash_list)): ?>
            <?php $index = 1; foreach ($trash_list as $item): ?>
              <tr>
                <td class="text-center align-middle"><?= $index++ ?></td>
                <td class="align-middle font-weight-bold text-dark"><?= htmlspecialchars($item['name']) ?></td>
                <td class="text-center align-middle">
                  <div class="d-inline-flex align-items-center">
                    <!-- Nút khôi phục -->
                    <a href="restore.php?id=<?= $item['id'] ?>" class="btn btn-outline-success btn-sm mr-2" title="Khôi phục">
                      <i class="fas fa-undo"></i>
                    </a>
                    <!-- Nút mở khung xác nhận xóa vĩnh viễn -->
                    <button type="button" 
                            class="btn btn-outline-danger btn-sm" 
                            title="Xóa vĩnh viễn" 
                            onclick="confirmDelete(<?= $item['id'] ?>)">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="3" class="text-center text-muted py-4 align-middle">Thùng rác trống.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<!-- MODAL XÁC NHẬN XÓA GIAO DIỆN HIỆN ĐẠI -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title font-weight-bold">
          <i class="fas fa-exclamation-triangle mr-2"></i>Xác Nhận Xóa Vĩnh Viễn
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body text-center py-4">
        <i class="fas fa-trash-alt text-danger fa-3x mb-3"></i>
        <h6 class="font-weight-bold">Hành động này không thể hoàn tác!</h6>
        <p class="text-muted mb-0">Bạn có chắc chắn muốn xóa vĩnh viễn danh mục này khỏi cơ sở dữ liệu?</p>
      </div>
      <div class="modal-footer bg-light justify-content-center">
        <button type="button" class="btn btn-secondary font-weight-bold px-4" data-dismiss="modal">Hủy</button>
        <a href="#" id="btnConfirmDelete" class="btn btn-danger font-weight-bold px-4">Đồng ý xóa</a>
      </div>
    </div>
  </div>
</div>

<?php include '../layout/scripts.php'; ?>

<!-- SCRIPT ĐIỀU KHIỂN MODAL VÀ TOAST -->
<script>
  // Mở popup xác nhận Bootstrap
  function confirmDelete(id) {
    document.getElementById('btnConfirmDelete').href = 'force_delete.php?id=' + id;
    $('#deleteModal').modal('show');
  }

  // Tự động tắt thông báo sau 3 giây
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