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
$page_title = 'Chi Tiết Sản Phẩm & Biến Thể';
$id = $_GET['id'] ?? '';
if (empty($id)) {
    header('Location: index.php');
    die();
}

$id = intval($id);

// 1. Lấy thông tin cơ bản sản phẩm, danh mục và thương hiệu
$sql = "SELECT p.*, 
               c.name AS category_name, 
               b.name AS brand_name,
               b.logo AS brand_logo
        FROM Product p 
        LEFT JOIN Category c ON p.category_id = c.id 
        LEFT JOIN Brand b ON p.brand_id = b.id 
        WHERE p.id = $id AND p.deleted = 0";
$product = executeResult($sql, true);

if (!$product) {
    header('Location: index.php');
    die();
}

// 2. Lấy toàn bộ biến thể cấu hình
$variants = executeResult("SELECT * FROM Product_Variant WHERE product_id = $id AND deleted = 0 ORDER BY price ASC");

// 3. Lấy album ảnh bộ sưu tập (Galery)
$galleries = executeResult("SELECT * FROM Galery WHERE product_id = $id ORDER BY id DESC");

// Xử lý ảnh đại diện
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

  <div class="custom-card mb-4">
    <div class="p-3 px-4 d-flex justify-content-between align-items-center border-bottom flex-wrap">
      <div>
        <h5 class="font-weight-bold mb-0 text-dark">
          <?= htmlspecialchars($product['title']) ?>
        </h5>
        <small class="text-muted">Mã định danh sản phẩm: #PROD-<?= $product['id'] ?></small>
      </div>
      <div class="mt-2 mt-md-0">
        <a href="../../san-pham/<?= htmlspecialchars($product['slug'] ?? '') ?>.html" target="_blank" class="btn btn-sm btn-outline-primary font-weight-bold mr-1">
          <i class="fas fa-external-link-alt mr-1"></i> Xem ngoài Web
        </a>
        <a href="edit.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-warning font-weight-bold mr-1 text-white">
          <i class="fas fa-edit mr-1"></i> Chỉnh sửa
        </a>
        <a href="index.php" class="btn btn-sm btn-secondary font-weight-bold">
          <i class="fas fa-arrow-left mr-1"></i> Quay lại
        </a>
      </div>
    </div>
    
    <div class="p-4">
      <div class="row">
        <!-- Cột hiển thị ảnh đại diện và thư viện ảnh -->
        <div class="col-lg-4 col-md-5 text-center mb-4 mb-md-0">
          <div class="border rounded p-2 mb-3 bg-white shadow-sm">
            <img src="<?= $thumb_display ?>" 
                 alt="<?= htmlspecialchars($product['title']) ?>" 
                 class="img-fluid rounded" 
                 style="max-height: 280px; width: 100%; object-fit: contain;">
          </div>

          <!-- Bộ sưu tập ảnh (Galery) -->
          <div class="text-left border-top pt-3">
            <label class="font-weight-bold small text-muted text-uppercase mb-2 d-block">
              Album ảnh bổ sung (<?= count($galleries) ?> ảnh)
            </label>
            <?php if (!empty($galleries)): ?>
              <div class="d-flex flex-wrap" style="gap: 8px;">
                <?php foreach ($galleries as $gal): 
                  $gal_path = '../../' . $gal['thumbnail'];
                  $gal_display = (!empty($gal['thumbnail']) && file_exists($gal_path)) ? $gal_path : 'https://via.placeholder.com/70x70?text=No+Img';
                ?>
                  <a href="<?= $gal_display ?>" target="_blank" class="border rounded p-1 bg-white">
                    <img src="<?= $gal_display ?>" 
                         class="rounded" 
                         width="60" 
                         height="60" 
                         style="object-fit: cover;">
                  </a>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <p class="text-muted small mb-0">Chưa có ảnh phụ bổ sung.</p>
            <?php endif; ?>
          </div>
        </div>

        <!-- Cột thông tin chi tiết -->
        <div class="col-lg-8 col-md-7">
          <div class="d-flex align-items-center mb-3 flex-wrap" style="gap: 8px;">
            <span class="badge badge-pill badge-primary px-3 py-2 font-weight-bold">
              Danh mục: <?= htmlspecialchars($product['category_name'] ?? 'Chưa phân loại') ?>
            </span>
            <span class="badge badge-pill badge-info px-3 py-2 font-weight-bold">
              Hãng: <?= htmlspecialchars($product['brand_name'] ?? 'Chưa rõ') ?>
            </span>
            <span class="badge badge-pill badge-secondary px-3 py-2 font-weight-bold">
              Slug: <?= htmlspecialchars($product['slug'] ?? '—') ?>
            </span>
          </div>

          <p class="text-muted small mb-4">
            <i class="far fa-clock mr-1"></i> Ngày tạo: <?= date('d/m/Y H:i', strtotime($product['created_at'])) ?>
            <span class="mx-2">|</span>
            <i class="fas fa-history mr-1"></i> Cập nhật lần cuối: <?= date('d/m/Y H:i', strtotime($product['updated_at'])) ?>
          </p>

          <!-- BẢNG BIẾN THỂ CẤU HÌNH & GIÁ CHI TIẾT -->
          <h6 class="text-primary font-weight-bold text-uppercase small tracking-wider mb-2">
            <i class="fas fa-microchip mr-1"></i> Các phiên bản cấu hình & Giá
          </h6>
          
          <div class="table-responsive mb-4">
            <table class="table table-bordered table-sm text-center mb-0 align-middle">
              <thead class="thead-light">
                <tr>
                  <th>SKU</th>
                  <th>Màu sắc</th>
                  <th>CPU</th>
                  <th>RAM</th>
                  <th>Bộ nhớ</th>
                  <th>GPU</th>
                  <th>Giá gốc</th>
                  <th>Giá sau giảm</th>
                  <th>Kho</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($variants)): ?>
                  <?php foreach ($variants as $v): 
                    $v_price = (int)$v['price'];
                    $v_discount = (int)$v['discount'];
                    $v_final = max(0, $v_price - $v_discount);
                  ?>
                    <tr>
                      <td class="small font-weight-bold text-secondary"><?= htmlspecialchars($v['sku'] ?: '—') ?></td>
                      <td class="font-weight-bold text-dark"><?= htmlspecialchars($v['color'] ?: 'Mặc định') ?></td>
                      <td class="text-primary font-weight-bold"><?= htmlspecialchars($v['cpu'] ?: '—') ?></td>
                      <td><?= htmlspecialchars($v['ram'] ?: '—') ?></td>
                      <td><?= htmlspecialchars($v['storage'] ?: '—') ?></td>
                      <td><?= htmlspecialchars($v['gpu'] ?: 'Onboard') ?></td>
                      <td class="text-muted small" style="text-decoration: <?= $v_discount > 0 ? 'line-through' : 'none' ?>;">
                        <?= number_format($v_price, 0, ',', '.') ?> đ
                      </td>
                      <td class="text-danger font-weight-bold">
                        <?= number_format($v_final, 0, ',', '.') ?> đ
                        <?php if ($v_discount > 0): ?>
                          <small class="badge badge-warning text-dark ml-1">-<?= number_format($v_discount, 0, ',', '.') ?>đ</small>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if ($v['stock_quantity'] > 0): ?>
                          <span class="badge badge-success px-2"><?= $v['stock_quantity'] ?></span>
                        <?php else: ?>
                          <span class="badge badge-danger px-2">Hết hàng</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="9" class="text-muted py-3">Chưa thiết lập biến thể cấu hình nào.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- MÔ TẢ TÓM TẮT SẢN PHẨM -->
          <div class="border-top pt-3">
            <h6 class="font-weight-bold text-dark mb-2">Đặc điểm nổi bật & Mô tả:</h6>
            <div class="text-secondary small bg-light p-3 rounded" style="line-height: 1.7; white-space: pre-line;">
              <?= !empty($product['description']) ? htmlspecialchars($product['description']) : 'Chưa có thông tin mô tả chi tiết cho sản phẩm này.' ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php include '../layout/scripts.php'; ?>