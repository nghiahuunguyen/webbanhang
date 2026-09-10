<?php 
session_start();
require_once('../../database/dbhelper.php');

// Lấy và xóa thông báo session
$success_msg = $_SESSION['success_msg'] ?? '';
unset($_SESSION['success_msg']);

// 1. Lấy danh sách sản phẩm chưa xóa (deleted = 0) kết hợp bảng category để lấy tên danh mục
$sql = "SELECT Product.*, Category.name as category_name 
        FROM Product 
        LEFT JOIN Category ON Product.category_id = Category.id 
        WHERE Product.deleted = 0 
        ORDER BY Product.id DESC";
$products = executeResult($sql);

// 2. Đếm số lượng sản phẩm đang ở trong thùng rác
$countTrash = executeResult("SELECT COUNT(id) as total FROM Product WHERE deleted = 1", true);
$total_trash = $countTrash['total'] ?? 0;
$page_title = 'Quản Lý Sản Phẩm';
include '../layout/head.php'; 
include '../layout/sidebar.php'; 
?>

<!-- TOAST THÔNG BÁO GÓC TRÊN BÊN PHẢI -->
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
      <h5 class="font-weight-bold mb-0 text-dark">Quản Lý Sản Phẩm</h5>
      <div>
        <a href="trash.php" class="btn btn-sm btn-outline-danger font-weight-bold mr-2">
          <i class="fas fa-trash-alt mr-1"></i> Thùng rác 
          <span class="badge badge-danger"><?= $total_trash ?></span>
        </a>
        <a href="create.php" class="btn btn-sm btn-success font-weight-bold">
          <i class="fas fa-plus mr-1"></i> Thêm mới sản phẩm
        </a>
      </div>
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
            <th width="110px" class="text-center align-middle">HÀNH ĐỘNG</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($products)): ?>
            <?php 
              $index = 1; 
              foreach ($products as $item): 
                $original_price = (int)$item['price'];
                $discount_amount = (int)$item['discount'];
                // Giá bán = Giá gốc - Giảm giá
                $sale_price = max(0, $original_price - $discount_amount);

                // Xử lý đường dẫn ảnh đại diện
                $thumb_path = '../../' . $item['thumbnail'];
                $thumb_display = (!empty($item['thumbnail']) && file_exists($thumb_path)) 
                                 ? $thumb_path 
                                 : 'https://via.placeholder.com/60x60?text=No+Image';
            ?>
              <tr>
                <td class="text-center align-middle"><?= $index++ ?></td>
                
                <!-- Hình ảnh sản phẩm -->
                <td class="text-center align-middle">
                  <img src="<?= $thumb_display ?>" 
                       alt="<?= htmlspecialchars($item['title']) ?>" 
                       class="rounded border" 
                       width="60" 
                       height="60" 
                       style="object-fit: cover;">
                </td>

                <!-- Tên sản phẩm -->
              <td class="align-middle" style="max-width: 300px;">
                <span class="font-weight-bold text-dark d-block"><?= htmlspecialchars($item['title']) ?></span>
              </td>

                <!-- Danh mục -->
                <td class="align-middle">
                  <span class="badge badge-light border px-2 py-1"><?= htmlspecialchars($item['category_name'] ?? 'Chưa phân loại') ?></span>
                </td>

                <!-- Giá bán thực tế -->
                <td class="align-middle">
                  <?php if ($discount_amount > 0): ?>
                    <div class="text-danger font-weight-bold" style="font-size: 1rem;">
                      <?= number_format($sale_price, 0, ',', '.') ?> đ
                    </div>
                    <small class="text-muted" style="text-decoration: line-through;">
                      <?= number_format($original_price, 0, ',', '.') ?> đ
                    </small>
                    <small class="badge badge-warning text-dark ml-1">-<?= number_format($discount_amount, 0, ',', '.') ?> đ</small>
                  <?php else: ?>
                    <div class="text-primary font-weight-bold" style="font-size: 1rem;">
                      <?= number_format($original_price, 0, ',', '.') ?> đ
                    </div>
                  <?php endif; ?>
                </td>

                <!-- Hành động -->
                <td class="text-center align-middle">
                  <div class="d-inline-flex align-items-center">
                    <a href="detail.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-info mr-2" title="Xem chi tiết">
                      <i class="fas fa-eye"></i>
                    </a>
                    <a href="edit.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-warning mr-2" title="Sửa">
                      <i class="fas fa-edit"></i>
                    </a>
                    <a href="delete.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-danger" title="Xóa vào thùng rác">
                      <i class="fas fa-trash-alt"></i>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="text-center text-muted py-4 align-middle">Chưa có sản phẩm nào.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<?php include '../layout/scripts.php'; ?>

<!-- SCRIPT TỰ BIẾN MẤT THÔNG BÁO SAU 3 GIÂY -->
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