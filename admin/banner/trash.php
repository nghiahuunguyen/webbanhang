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
$page_title = 'Thùng Rác Banner';

$success_msg = $_SESSION['success_msg'] ?? '';
unset($_SESSION['success_msg']);

// Lấy danh sách banner trong thùng rác
$trash_list = executeResult("SELECT * FROM `Banner` WHERE deleted = 1 ORDER BY id DESC");
$total_records = count($trash_list);

include '../layout/head.php'; 
include '../layout/sidebar.php'; 
?>

<!-- TOAST THÔNG BÁO GÓC TRÊN BÊN PHẢI -->
<?php if (!empty($success_msg)): ?>
  <div id="toast-wrapper" style="position: fixed; top: 20px; right: 20px; z-index: 99999; min-width: 320px; transition: all 0.5s ease;">
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

<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-3">
  <?php include '../layout/topbar.php'; ?>

  <div class="custom-card">
    <div class="p-3 px-4 d-flex justify-content-between align-items-center border-bottom flex-wrap">
      <div>
        <h5 class="font-weight-bold mb-1 text-danger"><i class="fas fa-trash-alt mr-2"></i>Thùng Rác Banner</h5>
        <small class="text-muted">Tổng cộng <?= number_format($total_records, 0, ',', '.') ?> banner đã tạm xóa</small>
      </div>
      <a href="index.php" class="btn btn-sm btn-secondary font-weight-bold mt-2 mt-md-0">
        <i class="fas fa-arrow-left mr-1"></i> Quay lại danh sách
      </a>
    </div>

    <div class="p-4 table-responsive">
      <table class="table table-bordered table-hover mb-0 align-middle">
        <thead class="thead-light">
          <tr>
            <th width="60px" class="text-center align-middle">STT</th>
            <th width="240px" class="text-center align-middle">HÌNH ẢNH BANNER</th>
            <th class="align-middle">ĐƯỜNG DẪN ẢNH</th>
            <th width="120px" class="text-center align-middle">THỨ TỰ CŨ</th>
            <th width="140px" class="text-center align-middle">TÁC VỤ</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($trash_list)): ?>
            <?php 
              $stt = 1; 
              foreach ($trash_list as $item): 
                $img_path = '../../' . $item['image_url'];
                $img_src = (!empty($item['image_url']) && file_exists($img_path)) 
                           ? $img_path 
                           : ((strpos($item['image_url'], 'http') === 0) ? $item['image_url'] : 'https://via.placeholder.com/210x90?text=No+Img');
            ?>
              <tr>
                <td class="text-center align-middle font-weight-bold"><?= $stt++ ?></td>
                
                <td class="text-center align-middle">
                  <div style="width: 210px; height: 90px; border-radius: 8px; overflow: hidden; background: #f8fafc;" class="border mx-auto p-1 bg-white">
                    <img src="<?= htmlspecialchars($img_src) ?>" alt="Banner" style="width: 100%; height: 100%; object-fit: cover;" class="rounded">
                  </div>
                </td>

                <td class="align-middle">
                  <code class="text-break small"><?= htmlspecialchars($item['image_url']) ?></code>
                </td>

                <td class="text-center align-middle font-weight-bold text-muted">
                  <?= $item['sort_order'] ?>
                </td>

                <!-- Khôi phục / Xóa vĩnh viễn -->
                <td class="text-center align-middle">
                  <div class="d-inline-flex align-items-center">
                    <a href="restore.php?id=<?= $item['id'] ?>" class="btn btn-outline-success btn-sm mr-2" title="Khôi phục banner">
                      <i class="fas fa-undo"></i>
                    </a>
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
              <td colspan="5" class="text-center text-muted py-4 align-middle">Thùng rác trống.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<!-- MODAL XÁC NHẬN XÓA VĨNH VIỄN -->
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
        <p class="text-muted mb-0">File ảnh vật lý lưu trong thư mục <code>img/banner/</code> sẽ bị xóa hoàn toàn khỏi máy chủ.</p>
      </div>
      <div class="modal-footer bg-light justify-content-center">
        <button type="button" class="btn btn-secondary font-weight-bold px-4" data-dismiss="modal">Hủy</button>
        <a href="#" id="btnConfirmDelete" class="btn btn-danger font-weight-bold px-4">Đồng ý xóa vĩnh viễn</a>
      </div>
    </div>
  </div>
</div>

<?php include '../layout/scripts.php'; ?>

<script>
  function confirmDelete(id) {
    document.getElementById('btnConfirmDelete').href = 'force_delete.php?id=' + id;
    $('#deleteModal').modal('show');
  }

  window.addEventListener('load', function() {
    var toastBox = document.getElementById('toast-wrapper');
    if (toastBox) {
      setTimeout(function() {
        toastBox.style.opacity = '0';
        toastBox.style.transform = 'translateX(50px)';
        setTimeout(function() { toastBox.remove(); }, 500);
      }, 3000);
    }
  });
</script>