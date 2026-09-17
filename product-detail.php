<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once __DIR__ . '/database/dbhelper.php';

// 1. Nhận ID hoặc Slug từ URL
$id   = isset($_GET['id']) ? intval($_GET['id']) : 0;
$slug = trim($_GET['slug'] ?? '');

$where_cond = "";
if (!empty($slug)) {
    $safe_slug  = str_replace("'", "\'", $slug);
    $where_cond = "p.slug = '$safe_slug'";
} elseif ($id > 0) {
    $where_cond = "p.id = $id";
} else {
    header('Location: index.php');
    exit();
}

// 2. Lấy thông tin chi tiết sản phẩm, danh mục và hãng
$sql_prod = "SELECT p.*, 
                    c.id AS cat_id, 
                    c.name AS category_name, 
                    c.slug AS category_slug,
                    b.name AS brand_name,
                    b.slug AS brand_slug
             FROM Product p 
             LEFT JOIN Category c ON p.category_id = c.id 
             LEFT JOIN Brand b ON p.brand_id = b.id 
             WHERE $where_cond AND p.deleted = 0 LIMIT 1";
$product = executeResult($sql_prod, true);

if (!$product) {
    header('Location: index.php');
    exit();
}

$id = intval($product['id']);

// XỬ LÝ GỬI ĐÁNH GIÁ & BÌNH LUẬN
$review_msg = '';
$review_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    $rating = intval($_POST['rating'] ?? 5);
    if ($rating < 1 || $rating > 5) $rating = 5;

    $comment = trim($_POST['comment'] ?? '');
    $user_id = isset($_SESSION['user']['id']) ? intval($_SESSION['user']['id']) : "NULL";
    $fullname = trim($_POST['fullname'] ?? ($_SESSION['user']['fullname'] ?? ''));

    if (empty($fullname) || empty($comment)) {
        $review_msg = 'Vui lòng nhập đầy đủ họ tên và nội dung đánh giá!';
    } else {
        $safe_name = str_replace("'", "\'", htmlspecialchars($fullname));
        $safe_comment = str_replace("'", "\'", htmlspecialchars($comment));

        $sql_review_insert = "INSERT INTO `Product_Review` (`product_id`, `user_id`, `fullname`, `rating`, `comment`) 
                              VALUES ($id, $user_id, '$safe_name', $rating, '$safe_comment')";
        execute($sql_review_insert);
        $review_msg = 'Cảm ơn bạn đã gửi đánh giá và nhận xét!';
        $review_success = true;
    }
}

// LẤY DỮ LIỆU ĐÁNH GIÁ CỦA SẢN PHẨM
$reviews = executeResult("SELECT * FROM `Product_Review` WHERE `product_id` = $id AND `deleted` = 0 ORDER BY `id` DESC");
$total_reviews = count($reviews);

$avg_rating = 5.0;
$star_counts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];

if ($total_reviews > 0) {
    $sum_rating = 0;
    foreach ($reviews as $rev) {
        $r_score = (int)$rev['rating'];
        $sum_rating += $r_score;
        if (isset($star_counts[$r_score])) {
            $star_counts[$r_score]++;
        }
    }
    $avg_rating = round($sum_rating / $total_reviews, 1);
}

// 3. Lấy danh sách các biến thể cấu hình chưa xóa
$variants = executeResult("SELECT * FROM Product_Variant WHERE product_id = $id AND deleted = 0 ORDER BY price ASC");

// 4. Lấy bộ sưu tập ảnh Galery
$galleries = executeResult("SELECT * FROM Galery WHERE product_id = $id ORDER BY id ASC");

// Biến thể mặc định là bản đầu tiên
$first_variant = !empty($variants) ? $variants[0] : null;
$def_price = $first_variant ? (int)$first_variant['price'] : 0;
$def_discount = $first_variant ? (int)$first_variant['discount'] : 0;
$def_sale = max(0, $def_price - $def_discount);
$def_stock = $first_variant ? (int)$first_variant['stock_quantity'] : 0;
$def_percent = ($def_price > 0 && $def_discount > 0) ? round(($def_discount / $def_price) * 100) : 0;

$is_logged_in = isset($_SESSION['user']);
$page_title = $product['title'] . ' - NovaShop';
include 'includes/header.php';
?>

<base href="/webbanhang/">

<main class="container my-4">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb bg-transparent p-0 small">
      <li class="breadcrumb-item"><a href="index.php" class="text-muted text-decoration-none">Trang chủ</a></li>
      <?php if (!empty($product['category_name'])): ?>
        <li class="breadcrumb-item">
          <a href="danh-muc/<?= htmlspecialchars($product['category_slug'] ?? $product['cat_id']) ?>" class="text-muted text-decoration-none">
            <?= htmlspecialchars($product['category_name']) ?>
          </a>
        </li>
      <?php endif; ?>
      <li class="breadcrumb-item active text-dark font-weight-bold" aria-current="page">
        <?= htmlspecialchars($product['title']) ?>
      </li>
    </ol>
  </nav>

  <div class="card border-0 rounded-2xl shadow-soft p-4 p-md-5 bg-white mb-4">
    <div class="row">
      <div class="col-lg-6 mb-4 mb-lg-0">
        <?php 
          $raw_main = $product['thumbnail'];
          $main_img = (!empty($raw_main) && file_exists(__DIR__ . '/' . ltrim($raw_main, '/')))
                      ? '/webbanhang/' . ltrim($raw_main, '/')
                      : ((!empty($raw_main) && strpos($raw_main, 'http') === 0) ? $raw_main : 'https://via.placeholder.com/600x600?text=No+Image');
        ?>
        <div class="rounded-2xl overflow-hidden mb-3 border text-center bg-white p-3 d-flex align-items-center justify-content-center" style="min-height: 400px;">
          <img id="mainProductImg" src="<?= htmlspecialchars($main_img) ?>" 
               class="img-fluid" style="max-height: 380px; object-fit: contain;" 
               alt="<?= htmlspecialchars($product['title']) ?>">
        </div>

        <div class="d-flex flex-wrap" style="gap: 8px;">
          <img src="<?= htmlspecialchars($main_img) ?>" 
               class="img-thumbnail rounded-lg thumb-gallery-item active-thumb" 
               style="width: 70px; height: 70px; object-fit: contain; cursor: pointer;" 
               onclick="changeMainImg(this.src, this)">

          <?php foreach ($galleries as $gal):
            $raw_g = $gal['thumbnail'];
            $gal_src = (!empty($raw_g) && file_exists(__DIR__ . '/' . ltrim($raw_g, '/')))
                       ? '/webbanhang/' . ltrim($raw_g, '/')
                       : ((!empty($raw_g) && strpos($raw_g, 'http') === 0) ? $raw_g : '');
            if (!$gal_src) continue;
          ?>
            <img src="<?= htmlspecialchars($gal_src) ?>" 
                 class="img-thumbnail rounded-lg thumb-gallery-item" 
                 style="width: 70px; height: 70px; object-fit: contain; cursor: pointer;" 
                 onclick="changeMainImg(this.src, this)">
          <?php endforeach; ?>
        </div>
      </div>

      <div class="col-lg-6 pl-lg-4">
        <form action="cart.php" method="POST" id="formAddToCart">
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
          <input type="hidden" name="variant_id" id="selectedVariantId" value="<?= $first_variant['id'] ?? 0 ?>">

          <div class="mb-2">
            <a href="thuong-hieu/<?= htmlspecialchars($product['brand_slug'] ?? '') ?>" class="badge badge-pill badge-light border text-primary px-3 py-1 font-weight-bold text-decoration-none">
              <?= htmlspecialchars($product['brand_name'] ?? 'Chính hãng') ?>
            </a>
          </div>
          <h3 class="font-weight-bold text-dark mb-2"><?= htmlspecialchars($product['title']) ?></h3>

          <div class="d-flex align-items-center mb-3">
            <span class="text-warning small mr-2">
              <?php for ($s = 1; $s <= 5; $s++): ?>
                <i class="fas fa-star <?= $s <= round($avg_rating) ? 'text-warning' : 'text-muted opacity-25' ?>"></i>
              <?php endfor; ?>
            </span>
            <span class="text-dark font-weight-bold small mr-2"><?= number_format($avg_rating, 1) ?></span>
            <span class="text-muted small border-left pl-2"><?= $total_reviews ?> lượt đánh giá</span>
          </div>

          <div class="bg-light p-3 rounded-2xl mb-4 d-flex align-items-center flex-wrap">
            <span class="h3 font-weight-bold text-danger mb-0 mr-3" id="displaySalePrice">
              <?= $def_sale > 0 ? number_format($def_sale, 0, ',', '.') . ' ₫' : 'Liên hệ' ?>
            </span>
            <span class="text-muted small mr-2 <?= $def_discount <= 0 ? 'd-none' : '' ?>" id="displayOldPrice" style="text-decoration: line-through;">
              <?= number_format($def_price, 0, ',', '.') ?> ₫
            </span>
            <span class="badge badge-danger small font-weight-bold <?= $def_percent <= 0 ? 'd-none' : '' ?>" id="displayDiscountBadge">
              -<?= $def_percent ?>%
            </span>
          </div>

          <?php if (!empty($variants)): ?>
            <div class="mb-4">
              <label class="font-weight-bold small text-dark d-block mb-2">Chọn phiên bản cấu hình:</label>
              <div class="d-flex flex-wrap" style="gap: 8px;">
                <?php foreach ($variants as $idx => $v):
                  $v_price = (int)$v['price'];
                  $v_disc = (int)$v['discount'];
                  $v_sale = max(0, $v_price - $v_disc);
                  $v_stock = (int)$v['stock_quantity'];

                  $label_parts = array_filter([$v['storage'], $v['ram'], $v['color']]);
                  $spec_title = !empty($label_parts) ? implode(' - ', $label_parts) : 'Bản tiêu chuẩn';
                ?>
                  <button type="button" 
                          class="btn-variant-select <?= $idx === 0 ? 'active' : '' ?>"
                          data-id="<?= $v['id'] ?>"
                          data-sku="<?= htmlspecialchars($v['sku'] ?? '') ?>"
                          data-cpu="<?= htmlspecialchars($v['cpu'] ?? '') ?>"
                          data-ram="<?= htmlspecialchars($v['ram'] ?? '') ?>"
                          data-storage="<?= htmlspecialchars($v['storage'] ?? '') ?>"
                          data-gpu="<?= htmlspecialchars($v['gpu'] ?? '') ?>"
                          data-color="<?= htmlspecialchars($v['color'] ?? '') ?>"
                          data-price="<?= number_format($v_price, 0, ',', '.') ?>"
                          data-sale="<?= number_format($v_sale, 0, ',', '.') ?>"
                          data-discount="<?= $v_disc ?>"
                          data-stock="<?= $v_stock ?>"
                          onclick="selectVariant(this)">
                    <?= htmlspecialchars($spec_title) ?>
                  </button>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <div class="mb-4">
            <label class="font-weight-bold small text-dark d-block mb-2">Số lượng:</label>
            <div class="d-flex align-items-center">
              <div class="input-group input-group-sm mr-3" style="width: 120px;">
                <div class="input-group-prepend">
                  <button class="btn btn-outline-secondary" type="button" onclick="adjustQuantity(-1)">-</button>
                </div>
                <input type="number" name="quantity" id="inputQuantity" class="form-control text-center font-weight-bold bg-white" value="1" min="1" max="<?= max(1, $def_stock) ?>" readonly>
                <div class="input-group-append">
                  <button class="btn btn-outline-secondary" type="button" onclick="adjustQuantity(1)">+</button>
                </div>
              </div>
              <span class="text-muted small" id="stockNotice">
                <?= $def_stock > 0 ? "Còn {$def_stock} sản phẩm" : "<span class='text-danger font-weight-bold'>Tạm hết hàng</span>" ?>
              </span>
            </div>
          </div>

          <div class="d-flex mb-4">
            <?php if ($is_logged_in): ?>
              <button type="submit" name="btn_add_cart" class="btn btn-outline-primary rounded-pill-custom px-4 py-2 font-weight-bold mr-3 shadow-sm" <?= $def_stock <= 0 ? 'disabled' : '' ?> id="btnAddToCart">
                <i class="fas fa-cart-plus mr-2"></i> Thêm Vào Giỏ
              </button>
              <button type="submit" name="btn_buy_now" value="1" class="btn btn-primary rounded-pill-custom px-4 py-2 font-weight-bold shadow-sm" <?= $def_stock <= 0 ? 'disabled' : '' ?> id="btnBuyNow">
                Mua Ngay
              </button>
            <?php else: ?>
              <button type="button" onclick="requireLoginNotice()" class="btn btn-outline-primary rounded-pill-custom px-4 py-2 font-weight-bold mr-3 shadow-sm" <?= $def_stock <= 0 ? 'disabled' : '' ?> id="btnAddToCart">
                <i class="fas fa-cart-plus mr-2"></i> Thêm Vào Giỏ
              </button>
              <button type="button" onclick="requireLoginNotice()" class="btn btn-primary rounded-pill-custom px-4 py-2 font-weight-bold shadow-sm" <?= $def_stock <= 0 ? 'disabled' : '' ?> id="btnBuyNow">
                Mua Ngay
              </button>
            <?php endif; ?>
          </div>

          <div class="border-top pt-3 text-muted small">
            <div class="row">
              <div class="col-6 mb-2"><i class="fas fa-check-circle text-success mr-2"></i>Bảo hành chính hãng 12 tháng</div>
              <div class="col-6 mb-2"><i class="fas fa-shipping-fast text-primary mr-2"></i>Giao nhanh toàn quốc</div>
              <div class="col-6"><i class="fas fa-sync-alt text-warning mr-2"></i>Lỗi 1 đổi 1 trong 30 ngày</div>
              <div class="col-6"><i class="fas fa-box-open text-info mr-2"></i>Nguyên seal, đầy đủ phụ kiện</div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-8 mb-4">
      <!-- MÔ TẢ SẢN PHẨM -->
      <div class="card border-0 rounded-2xl shadow-soft p-4 bg-white mb-4">
        <h5 class="font-weight-bold text-dark border-bottom pb-3 mb-3">Đặc Điểm Nổi Bật & Mô Tả</h5>
        <div class="text-secondary" style="line-height: 1.8; white-space: pre-line;">
          <?= !empty($product['description']) ? htmlspecialchars($product['description']) : 'Thông tin chi tiết sản phẩm đang được cập nhật.' ?>
        </div>
      </div>

      <!-- KHỐI ĐÁNH GIÁ & BÌNH LUẬN -->
      <div class="card border-0 rounded-2xl shadow-soft p-4 bg-white">
        <h5 class="font-weight-bold text-dark border-bottom pb-3 mb-4">
          <i class="fas fa-star text-warning mr-2"></i>Đánh Giá & Nhận Xét
        </h5>

        <!-- Khung thống kê điểm số sao -->
        <div class="row align-items-center mb-4 p-3 bg-light rounded-2xl mx-0">
          <div class="col-sm-4 text-center border-right mb-3 mb-sm-0">
            <h1 class="font-weight-bold text-danger display-4 mb-0"><?= number_format($avg_rating, 1) ?></h1>
            <div class="text-warning small my-1">
              <?php for ($s = 1; $s <= 5; $s++): ?>
                <i class="fas fa-star <?= $s <= round($avg_rating) ? 'text-warning' : 'text-muted opacity-25' ?>"></i>
              <?php endfor; ?>
            </div>
            <span class="text-muted small"><?= $total_reviews ?> lượt đánh giá</span>
          </div>
          <div class="col-sm-8 pl-sm-4">
            <?php for ($st = 5; $st >= 1; $st--): 
              $count = $star_counts[$st] ?? 0;
              $pct = $total_reviews > 0 ? round(($count / $total_reviews) * 100) : 0;
            ?>
              <div class="d-flex align-items-center small my-1">
                <span style="width: 45px;"><?= $st ?> <i class="fas fa-star text-warning"></i></span>
                <div class="progress flex-grow-1 mx-2" style="height: 6px; border-radius: 4px;">
                  <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $pct ?>%;"></div>
                </div>
                <span class="text-muted" style="width: 35px;"><?= $count ?></span>
              </div>
            <?php endfor; ?>
          </div>
        </div>

        <?php if (!empty($review_msg)): ?>
          <div class="alert <?= $review_success ? 'alert-success' : 'alert-danger' ?> font-weight-bold small">
            <?= htmlspecialchars($review_msg) ?>
          </div>
        <?php endif; ?>

        <!-- Form gửi đánh giá và nhận xét -->
        <div class="border rounded-2xl p-3 p-md-4 mb-4 bg-white">
          <h6 class="font-weight-bold text-dark mb-3">Gửi đánh giá của bạn</h6>
          <form method="POST">
            <input type="hidden" name="action" value="submit_review">
            
            <div class="form-group mb-3">
              <label class="small text-muted font-weight-bold mb-1 d-block">Bạn cảm thấy sản phẩm thế nào? (Chọn số sao):</label>
              <div class="rating-stars-input d-flex" style="gap: 6px; font-size: 1.4rem; cursor: pointer;">
                <input type="hidden" name="rating" id="ratingScore" value="5">
                <i class="fas fa-star text-warning star-item" data-value="1"></i>
                <i class="fas fa-star text-warning star-item" data-value="2"></i>
                <i class="fas fa-star text-warning star-item" data-value="3"></i>
                <i class="fas fa-star text-warning star-item" data-value="4"></i>
                <i class="fas fa-star text-warning star-item" data-value="5"></i>
                <span class="small font-weight-bold text-primary ml-2 align-self-center" id="ratingLabel">Tuyệt vời (5/5)</span>
              </div>
            </div>

            <?php if (!$is_logged_in): ?>
              <div class="form-group mb-3">
                <label class="small text-muted font-weight-bold mb-1">Họ và tên của bạn <span class="text-danger">*</span></label>
                <input type="text" name="fullname" class="form-control form-control-sm" placeholder="Nhập tên hiển thị..." required>
              </div>
            <?php else: ?>
              <input type="hidden" name="fullname" value="<?= htmlspecialchars($_SESSION['user']['fullname']) ?>">
            <?php endif; ?>

            <div class="form-group mb-3">
              <label class="small text-muted font-weight-bold mb-1">Nội dung nhận xét <span class="text-danger">*</span></label>
              <textarea name="comment" rows="3" class="form-control form-control-sm" placeholder="Chia sẻ cảm nhận về độ hoàn thiện, hiệu năng, pin hoặc thời gian giao hàng..." required></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-sm rounded-pill-custom px-4 font-weight-bold shadow-sm">
              <i class="fas fa-paper-plane mr-1"></i> Gửi Đánh Giá Ngay
            </button>
          </form>
        </div>

        <!-- Danh sách bình luận -->
        <div class="review-list">
          <h6 class="font-weight-bold text-dark mb-3">Các đánh giá mới nhất (<?= $total_reviews ?>)</h6>
          <?php if (!empty($reviews)): ?>
            <?php foreach ($reviews as $rev): ?>
              <div class="d-flex mb-3 pb-3 border-bottom">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mr-3 font-weight-bold" style="width: 42px; height: 42px; flex-shrink: 0; font-size: 1.1rem;">
                  <?= mb_substr($rev['fullname'], 0, 1, 'UTF-8') ?>
                </div>
                <div class="flex-grow-1">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="font-weight-bold text-dark small"><?= htmlspecialchars($rev['fullname']) ?></span>
                    <span class="text-muted" style="font-size: 0.75rem;">
                      <i class="far fa-clock mr-1"></i><?= date('d/m/Y H:i', strtotime($rev['created_at'])) ?>
                    </span>
                  </div>
                  <div class="text-warning mb-2" style="font-size: 0.75rem;">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                      <i class="fas fa-star <?= $s <= (int)$rev['rating'] ? 'text-warning' : 'text-muted opacity-25' ?>"></i>
                    <?php endfor; ?>
                  </div>
                  <div class="small text-secondary" style="line-height: 1.6;">
                    <?= nl2br(htmlspecialchars($rev['comment'])) ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="text-center py-4 text-muted small">
              <i class="far fa-comment-dots fa-2x mb-2 d-block text-black-50"></i>
              Chưa có nhận xét nào. Hãy là người đầu tiên đánh giá sản phẩm này!
            </div>
          <?php endif; ?>
        </div>

      </div>
    </div>

    <div class="col-lg-4 mb-4">
      <!-- THÔNG SỐ KỸ THUẬT -->
      <div class="card border-0 rounded-2xl shadow-soft p-4 bg-white h-100">
        <h5 class="font-weight-bold text-dark border-bottom pb-3 mb-3">Thông Số Kỹ Thuật</h5>
        <table class="table table-sm border-0 small mb-0">
          <tbody>
            <tr class="border-bottom">
              <td class="text-muted py-2">Thương hiệu</td>
              <td class="font-weight-bold text-dark text-right py-2"><?= htmlspecialchars($product['brand_name'] ?? 'Chính hãng') ?></td>
            </tr>
            <tr class="border-bottom">
              <td class="text-muted py-2">Mã SKU</td>
              <td class="font-weight-bold text-dark text-right py-2" id="specSku"><?= htmlspecialchars($first_variant['sku'] ?? '—') ?></td>
            </tr>
            <tr class="border-bottom" id="rowCpu" style="<?= empty($first_variant['cpu']) ? 'display: none;' : '' ?>">
              <td class="text-muted py-2">Vi xử lý (CPU)</td>
              <td class="font-weight-bold text-dark text-right py-2" id="specCpu"><?= htmlspecialchars($first_variant['cpu'] ?? '') ?></td>
            </tr>
            <tr class="border-bottom" id="rowRam" style="<?= empty($first_variant['ram']) ? 'display: none;' : '' ?>">
              <td class="text-muted py-2">Bộ nhớ RAM</td>
              <td class="font-weight-bold text-dark text-right py-2" id="specRam"><?= htmlspecialchars($first_variant['ram'] ?? '') ?></td>
            </tr>
            <tr class="border-bottom" id="rowStorage" style="<?= empty($first_variant['storage']) ? 'display: none;' : '' ?>">
              <td class="text-muted py-2">Ổ cứng / Bộ nhớ</td>
              <td class="font-weight-bold text-dark text-right py-2" id="specStorage"><?= htmlspecialchars($first_variant['storage'] ?? '') ?></td>
            </tr>
            <tr class="border-bottom" id="rowGpu" style="<?= empty($first_variant['gpu']) ? 'display: none;' : '' ?>">
              <td class="text-muted py-2">Card đồ họa (GPU)</td>
              <td class="font-weight-bold text-dark text-right py-2" id="specGpu"><?= htmlspecialchars($first_variant['gpu'] ?? '') ?></td>
            </tr>
            <tr id="rowColor" style="<?= empty($first_variant['color']) ? 'display: none;' : '' ?>">
              <td class="text-muted py-2">Màu sắc</td>
              <td class="font-weight-bold text-dark text-right py-2" id="specColor"><?= htmlspecialchars($first_variant['color'] ?? '') ?></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<style>
  .thumb-gallery-item {
    border: 2px solid transparent;
    transition: all 0.2s ease;
  }
  .thumb-gallery-item:hover,
  .thumb-gallery-item.active-thumb {
    border-color: #0071e3 !important;
  }
  .btn-variant-select {
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    color: #334155;
    font-size: 0.85rem;
    font-weight: 500;
    padding: 6px 14px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.15s ease;
    outline: none;
  }
  .btn-variant-select:hover {
    border-color: #0071e3;
    color: #0071e3;
  }
  .btn-variant-select.active {
    border-color: #0071e3;
    background-color: #f0f7ff;
    color: #0071e3;
    font-weight: 600;
    box-shadow: 0 0 0 1px #0071e3;
  }
</style>

<script>
  // Chức năng chọn sao đánh giá tương tác
  const starLabels = {
    1: 'Rất tệ (1/5)',
    2: 'Không hài lòng (2/5)',
    3: 'Tạm ổn (3/5)',
    4: 'Hài lòng (4/5)',
    5: 'Tuyệt vời (5/5)'
  };
  
  const starItems = document.querySelectorAll('.star-item');
  const ratingScoreInput = document.getElementById('ratingScore');
  const ratingLabel = document.getElementById('ratingLabel');

  starItems.forEach(star => {
    star.addEventListener('click', function() {
      const val = parseInt(this.getAttribute('data-value'));
      ratingScoreInput.value = val;
      ratingLabel.textContent = starLabels[val];

      starItems.forEach(s => {
        const sVal = parseInt(s.getAttribute('data-value'));
        if (sVal <= val) {
          s.classList.remove('text-muted');
          s.classList.add('text-warning');
        } else {
          s.classList.remove('text-warning');
          s.classList.add('text-muted');
        }
      });
    });
  });

  function requireLoginNotice() {
    var oldToast = document.getElementById('login-alert-toast');
    if (oldToast) oldToast.remove();

    var loginUrl = 'login.php?redirect=' + encodeURIComponent(window.location.href);
    var toastDiv = document.createElement('div');
    toastDiv.id = 'login-alert-toast';
    toastDiv.style.cssText = 'position: fixed; top: 25px; right: 25px; z-index: 99999; min-width: 330px; max-width: 400px; transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); transform: translateY(-15px); opacity: 0;';

    toastDiv.innerHTML = `
      <div class="card border-0 shadow-lg" style="border-radius: 12px; overflow: hidden; border-left: 5px solid #ef4444 !important;">
        <div class="card-body p-3 bg-white text-dark">
          <div class="d-flex align-items-start justify-content-between mb-2">
            <div class="d-flex align-items-center">
              <span class="text-danger mr-2" style="font-size: 1.15rem;"><i class="fas fa-exclamation-circle"></i></span>
              <strong class="text-dark font-weight-bold" style="font-size: 0.95rem;">Yêu cầu đăng nhập</strong>
            </div>
            <button type="button" class="close text-muted" onclick="this.closest('#login-alert-toast').remove()" style="outline: none; font-size: 1.25rem; line-height: 1;">&times;</button>
          </div>
          <p class="small text-muted mb-3" style="line-height: 1.5;">Vui lòng đăng nhập tài khoản để thêm vào giỏ và đặt mua sản phẩm này.</p>
          <div class="d-flex justify-content-end" style="gap: 8px;">
            <button type="button" class="btn btn-sm btn-light border px-3 py-1 font-weight-bold" onclick="this.closest('#login-alert-toast').remove()">Đóng</button>
            <a href="${loginUrl}" class="btn btn-sm btn-danger px-3 py-1 font-weight-bold text-white shadow-sm" style="border-radius: 6px;">Đăng nhập ngay</a>
          </div>
        </div>
      </div>
    `;

    document.body.appendChild(toastDiv);

    setTimeout(function() {
      toastDiv.style.opacity = '1';
      toastDiv.style.transform = 'translateY(0)';
    }, 10);

    setTimeout(function() {
      if (toastDiv && document.body.contains(toastDiv)) {
        toastDiv.style.opacity = '0';
        toastDiv.style.transform = 'translateX(50px)';
        setTimeout(function() { toastDiv.remove(); }, 400);
      }
    }, 6000);
  }

  function changeMainImg(src, thumbEl) {
    document.getElementById('mainProductImg').src = src;
    document.querySelectorAll('.thumb-gallery-item').forEach(el => el.classList.remove('active-thumb'));
    thumbEl.classList.add('active-thumb');
  }

  function selectVariant(btn) {
    document.querySelectorAll('.btn-variant-select').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    var vId = btn.getAttribute('data-id');
    var sale = btn.getAttribute('data-sale');
    var price = btn.getAttribute('data-price');
    var discount = parseInt(btn.getAttribute('data-discount')) || 0;
    var stock = parseInt(btn.getAttribute('data-stock')) || 0;

    document.getElementById('selectedVariantId').value = vId;

    document.getElementById('displaySalePrice').textContent = sale + ' ₫';
    var oldPriceEl = document.getElementById('displayOldPrice');
    var badgeEl = document.getElementById('displayDiscountBadge');

    if (discount > 0) {
      oldPriceEl.textContent = price + ' ₫';
      oldPriceEl.classList.remove('d-none');
      var numPrice = parseInt(price.replace(/\./g, '')) || 1;
      var percent = Math.round((discount / numPrice) * 100);
      badgeEl.textContent = '-' + percent + '%';
      badgeEl.classList.remove('d-none');
    } else {
      oldPriceEl.classList.add('d-none');
      badgeEl.classList.add('d-none');
    }

    var stockEl = document.getElementById('stockNotice');
    var btnAdd = document.getElementById('btnAddToCart');
    var btnBuy = document.getElementById('btnBuyNow');
    var inputQty = document.getElementById('inputQuantity');

    if (stock > 0) {
      stockEl.innerHTML = 'Còn ' + stock + ' sản phẩm';
      btnAdd.removeAttribute('disabled');
      btnBuy.removeAttribute('disabled');
      inputQty.setAttribute('max', stock);
      if (parseInt(inputQty.value) > stock) inputQty.value = 1;
    } else {
      stockEl.innerHTML = '<span class="text-danger font-weight-bold">Tạm hết hàng</span>';
      btnAdd.setAttribute('disabled', 'disabled');
      btnBuy.setAttribute('disabled', 'disabled');
      inputQty.value = 1;
    }

    updateSpecRow('specSku', 'rowSku', btn.getAttribute('data-sku'));
    updateSpecRow('specCpu', 'rowCpu', btn.getAttribute('data-cpu'));
    updateSpecRow('specRam', 'rowRam', btn.getAttribute('data-ram'));
    updateSpecRow('specStorage', 'rowStorage', btn.getAttribute('data-storage'));
    updateSpecRow('specGpu', 'rowGpu', btn.getAttribute('data-gpu'));
    updateSpecRow('specColor', 'rowColor', btn.getAttribute('data-color'));
  }

  function updateSpecRow(targetId, rowId, value) {
    var textEl = document.getElementById(targetId);
    var rowEl = document.getElementById(rowId);
    if (!textEl) return;
    if (value && value.trim() !== '') {
      textEl.textContent = value;
      if (rowEl) rowEl.style.display = '';
    } else {
      if (rowEl) rowEl.style.display = 'none';
    }
  }

  function adjustQuantity(delta) {
    var input = document.getElementById('inputQuantity');
    var val = parseInt(input.value) || 1;
    var maxVal = parseInt(input.getAttribute('max')) || 1;
    val += delta;
    if (val < 1) val = 1;
    if (val > maxVal) val = maxVal;
    input.value = val;
  }
</script>

<?php 
include 'includes/footer.php';
?>