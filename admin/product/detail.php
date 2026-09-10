<?php 
session_start();
require_once('../../database/dbhelper.php');
$page_title = 'Xem Chi Tiết Sản Phẩm';
$id = $_GET['id'] ?? '';
if (empty($id)) {
    header('Location: index.php');
    die();
}

// 1. Lấy thông tin chi tiết sản phẩm kèm tên danh mục
$sql = "SELECT Product.*, Category.name AS category_name 
        FROM Product 
        LEFT JOIN Category ON Product.category_id = Category.id 
        WHERE Product.id = '$id'";
$product = executeResult($sql, true);

if (!$product) {
    header('Location: index.php');
    die();
}

// 2. Lấy danh sách ảnh trong Galery của sản phẩm này
$galleries = executeResult("SELECT * FROM Galery WHERE product_id = '$id'");

// Tính toán giá bán và đường dẫn ảnh
$original_price = (int)$product['price'];
$discount_amount = (int)$product['discount'];
$sale_price = max(0, $original_price - $discount_amount);

$thumb_path = '../../' . $product['thumbnail'];
$thumb_display = (!empty($product['thumbnail']) && file_exists($thumb_path)) 
                 ? $thumb_path 
                 : 'https://via.placeholder.com/400x400?text=No+Image';

include '../layout/head.php'; 
include '../layout/sidebar.php'; 
?>

<!-- MAIN CONTENT -->
<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-3">
  <?php include '../layout/topbar.php'; ?>

  <div class="custom-card">
    <div class="p-3 px-4 d-flex justify-content-between align-items-center border-bottom">
      <h5 class="font-weight-bold mb-0 text-dark">
        Chi Tiết Sản Phẩm: <?= htmlspecialchars($product['title']) ?>
      </h5>
      <div>
        <a href="edit.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-warning font-weight-bold mr-1 text-white">
          <i class="fas fa-pen mr-1"></i> Sửa
        </a>
        <a href="index.php" class="btn btn-sm btn-secondary font-weight-bold">
          <i class="fas fa-arrow-left mr-1"></i> Trở về
        </a>
      </div>
    </div>
    
    <div class="p-4">
      <div class="row">
        <!-- Cột hiển thị ảnh đại diện và ảnh phụ -->
        <div class="col-md-4 text-center mb-4 mb-md-0">
          <img src="<?= $thumb_display ?>" 
               alt="<?= htmlspecialchars($product['title']) ?>" 
               class="img-fluid rounded border p-2 mb-3 shadow-sm" 
               style="max-height: 280px; width: 100%; object-fit: contain;">

          <!-- Thư viện ảnh phụ (Galery) -->
          <?php if (!empty($galleries)): ?>
            <div class="text-left border-top pt-3">
              <label class="font-weight-bold small text-muted text-uppercase mb-2 d-block">Hình ảnh bổ sung:</label>
              <div class="d-flex flex-wrap" style="gap: 8px;">
                <?php foreach ($galleries as $gal): 
                  $gal_path = '../../' . $gal['thumbnail'];
                  $gal_display = (!empty($gal['thumbnail']) && file_exists($gal_path)) ? $gal_path : 'https://via.placeholder.com/60x60?text=No+Image';
                ?>
                  <a href="<?= $gal_display ?>" target="_blank">
                    <img src="<?= $gal_display ?>" 
                         class="rounded border" 
                         width="60" 
                         height="60" 
                         style="object-fit: cover;">
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <!-- Cột thông tin chi tiết -->
        <div class="col-md-8">
          <h4 class="font-weight-bold text-dark mb-2"><?= htmlspecialchars($product['title']) ?></h4>
          <p class="text-muted mb-3">
            Danh mục: <span class="badge badge-light border px-2 py-1"><?= htmlspecialchars($product['category_name'] ?? 'Chưa phân loại') ?></span>
          </p>

          <div class="mb-3 p-3 bg-light rounded border">
            <?php if ($discount_amount > 0): ?>
              <div class="mb-1">
                <span class="text-muted mr-2">Giá gốc:</span>
                <del class="text-secondary"><?= number_format($original_price, 0, ',', '.') ?> đ</del>
                <span class="badge badge-danger ml-2">-<?= number_format($discount_amount, 0, ',', '.') ?> đ</span>
              </div>
              <div class="text-danger font-weight-bold h4 mb-0">
                Giá bán: <?= number_format($sale_price, 0, ',', '.') ?> đ
              </div>
            <?php else: ?>
              <div class="text-primary font-weight-bold h4 mb-0">
                Giá bán: <?= number_format($original_price, 0, ',', '.') ?> đ
              </div>
            <?php endif; ?>
          </div>

          <p class="text-muted small mb-3">
            <i class="far fa-calendar-alt mr-1"></i> Ngày tạo: <?= date('d/m/Y H:i', strtotime($product['created_at'])) ?> 
            <span class="mx-2">|</span> 
            <i class="fas fa-history mr-1"></i> Cập nhật lần cuối: <?= date('d/m/Y H:i', strtotime($product['updated_at'])) ?>
          </p>

          <div class="border-top pt-3">
            <h6 class="font-weight-bold text-dark mb-2">Mô tả sản phẩm:</h6>
            <div class="text-secondary small" style="line-height: 1.7; white-space: pre-line;">
              <?= !empty($product['description']) ? htmlspecialchars($product['description']) : 'Chưa có thông tin mô tả chi tiết cho sản phẩm này.' ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php include '../layout/scripts.php'; ?>