<?php 
session_start();
require_once('../../database/dbhelper.php');
$page_title = 'Thùng Rác Sản Phẩm';
$success_msg = $_SESSION['success_msg'] ?? '';
unset($_SESSION['success_msg']);

// Lấy danh sách sản phẩm nằm trong thùng rác
$sql = "SELECT Product.*, Category.name AS category_name 
        FROM Product 
        LEFT JOIN Category ON Product.category_id = Category.id 
        WHERE Product.deleted = 1 
        ORDER BY Product.id DESC";
$trash_list = executeResult($sql);

include '../layout/head.php'; 
include '../layout/sidebar.php'; 
?>

<!-- TOAST THÔNG BÁO GÓC TRÊN BÊN PHẢI (TỰ BIẾN MẤT SAU 3S) -->
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
        <h5 class="font-weight-bold mb-1 text-danger"><i class="fas fa-trash-alt mr-2"></i>Thùng Rác Sản Phẩm</h5>
        <small class="text-muted">Danh sách các sản phẩm đã tạm xóa</small>
      </div>
      <a href="index.php" class="btn btn-sm btn-secondary font-weight-bold">
        <i class="fas fa-arrow-left mr-1"></i> Quay lại danh sách
      </a>
    </div>

    <div class="p-4 table-responsive">
      <table class="table table-bordered table-hover mb-0 align-middle">
        <thead class="thead-light">
          <tr>
            <th width="60px" class="text-center align-middle">STT</th>
            <th width="80px" class="text-center align-middle">HÌNH ẢNH</th>
            <th width="300px" class="align-middle">TÊN SẢN PHẨM</th>
            <th width="140px" class="align-middle">DANH MỤC</th>
            <th width="170px" class="align-middle">GIÁ BÁN</th>
            <th width="140px" class="text-center align-middle">TÁC VỤ</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($trash_list)): ?>
            <?php 
              $index = 1; 
              foreach ($trash_list as $item): 
                $original_price = (int)$item['price'];
                $discount_amount = (int)$item['discount'];
                $sale_price = max(0, $original_price - $discount_amount);

                $thumb_path = '../../' . $item['thumbnail'];
                $thumb_display = (!empty($item['thumbnail']) && file_exists($thumb_path)) 
                                 ? $thumb_path 
                                 : 'https://via.placeholder.com/60x60?text=No+Image';
            ?>
              <tr>
                <td class="text-center align-middle"><?= $index++ ?></td>
                
                <td class="text-center align-middle">
                  <img src="<?= $thumb_display ?>" 
                       class="rounded border" 
                       width="60" 
                       height="60" 
                       style="object-fit: cover;">
                </td>

                <td class="align-middle" style="max-width: 300px;">
                  <span class="font-weight-bold text-dark d-block"><?= htmlspecialchars($item['title']) ?></span>
                </td>

                <td class="align-middle">
                  <span class="badge badge-light border px-2 py-1"><?= htmlspecialchars($item['category_name'] ?? 'Chưa phân loại') ?></span>
                </td>

                <td class="align-middle">
                  <?php if ($discount_amount > 0): ?>
                    <div class="text-danger font-weight-bold"><?= number_format($sale_price, 0, ',', '.') ?> đ</div>
                    <small class="text-muted" style="text-decoration: line-through;"><?= number_format($original_price, 0, ',', '.') ?> đ</small>
                  <?php else: ?>
                    <div class="text-primary font-weight-bold"><?= number_format($original_price, 0, ',', '.') ?> đ</div>
                  <?php endif; ?>
                </td>

                <td class="text-center align-middle">
                  <div class="d-inline-flex align-items-center">
                    <!-- Nút khôi phục -->
                    <a href="restore.php?id=<?= $item['id'] ?>" class="btn btn-outline-success btn-sm mr-2" title="Khôi phục">
                      <i class="fas fa-undo"></i>
                    </a>
                    <!-- Nút kích hoạt Modal xóa vĩnh viễn -->
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
              <td colspan="6" class="text-center text-muted py-4 align-middle">Thùng rác trống.</td>
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
        <p class="text-muted mb-0">Sản phẩm và toàn bộ hình ảnh liên quan sẽ bị xóa hoàn toàn khỏi hệ thống.</p>
      </div>
      <div class="modal-footer bg-light justify-content-center">
        <button type="button" class="btn btn-secondary font-weight-bold px-4" data-dismiss="modal">Hủy</button>
        <a href="#" id="btnConfirmDelete" class="btn btn-danger font-weight-bold px-4">Đồng ý xóa</a>
      </div>
    </div>
  </div>
</div>

<?php include '../layout/scripts.php'; ?>

<!-- SCRIPT ĐIỀU KHIỂN MODAL VÀ TỰ ẨN THÔNG BÁO -->
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
        setTimeout(function() {
          toastBox.remove();
        }, 500);
      }, 3000);
    }
  });
</script>