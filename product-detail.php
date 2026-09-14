<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once __DIR__ . '/database/dbhelper.php';

// 1. Nhận ID sản phẩm từ URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: category.php');
    exit();
}

// 2. Lấy thông tin chi tiết sản phẩm, danh mục và hãng
$sql_prod = "SELECT p.*, 
                    c.id AS cat_id, 
                    c.name AS category_name, 
                    b.name AS brand_name 
             FROM Product p 
             LEFT JOIN Category c ON p.category_id = c.id 
             LEFT JOIN Brand b ON p.brand_id = b.id 
             WHERE p.id = $id AND p.deleted = 0";
$product = executeResult($sql_prod, true);

if (!$product) {
    header('Location: category.php');
    exit();
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
$page_title = $product['title'];
include 'includes/header.php';
?>

<main class="container my-4">
  <!-- Breadcrumb điều hướng -->
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb bg-transparent p-0 small">
      <li class="breadcrumb-item"><a href="index.php" class="text-muted">Trang chủ</a></li>
      <?php if (!empty($product['category_name'])): ?>
        <li class="breadcrumb-item">
          <a href="category.php?id=<?= $product['cat_id'] ?>" class="text-muted">
            <?= htmlspecialchars($product['category_name']) ?>
          </a>
        </li>
      <?php endif; ?>
      <li class="breadcrumb-item active text-dark font-weight-bold" aria-current="page">
        <?= htmlspecialchars($product['title']) ?>
      </li>
    </ol>
  </nav>

  <!-- Khối Chi tiết sản phẩm chính -->
  <div class="card border-0 rounded-2xl shadow-soft p-4 p-md-5 bg-white mb-4">
    <div class="row">
      <!-- Cột trái: Thư viện hình ảnh -->
      <div class="col-lg-6 mb-4 mb-lg-0">
        <?php 
          $main_img = (!empty($product['thumbnail']) && file_exists($product['thumbnail'])) 
                      ? htmlspecialchars($product['thumbnail']) 
                      : 'https://via.placeholder.com/600x600?text=No+Image';
        ?>
        <div class="rounded-2xl overflow-hidden mb-3 border text-center bg-white p-3 d-flex align-items-center justify-content-center" style="min-height: 400px;">
          <img id="mainProductImg" src="<?= $main_img ?>" 
               class="img-fluid" style="max-height: 380px; object-fit: contain;" 
               alt="<?= htmlspecialchars($product['title']) ?>">
        </div>

        <!-- Thumbnails xem trước -->
        <div class="d-flex flex-wrap" style="gap: 8px;">
          <!-- Ảnh thumbnail chính -->
          <img src="<?= $main_img ?>" 
               class="img-thumbnail rounded-lg thumb-gallery-item active-thumb" 
               style="width: 70px; height: 70px; object-fit: contain; cursor: pointer;" 
               onclick="changeMainImg(this.src, this)">

          <!-- Danh sách ảnh trong Galery -->
          <?php foreach ($galleries as $gal): 
            $gal_src = (!empty($gal['thumbnail']) && file_exists($gal['thumbnail'])) ? htmlspecialchars($gal['thumbnail']) : '';
            if (!$gal_src) continue;
          ?>
            <img src="<?= $gal_src ?>" 
                 class="img-thumbnail rounded-lg thumb-gallery-item" 
                 style="width: 70px; height: 70px; object-fit: contain; cursor: pointer;" 
                 onclick="changeMainImg(this.src, this)">
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Cột phải: Thông tin giá & Lựa chọn phiên bản -->
      <div class="col-lg-6 pl-lg-4">
        <form action="cart.php" method="POST" id="formAddToCart">
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
          <input type="hidden" name="variant_id" id="selectedVariantId" value="<?= $first_variant['id'] ?? 0 ?>">

          <!-- Tên sản phẩm & thương hiệu -->
          <div class="mb-2">
            <span class="badge badge-pill badge-light border text-primary px-3 py-1 font-weight-bold">
              <?= htmlspecialchars($product['brand_name'] ?? 'Chính hãng') ?>
            </span>
          </div>
          <h3 class="font-weight-bold text-dark mb-2"><?= htmlspecialchars($product['title']) ?></h3>

          <!-- Đánh giá sao -->
          <div class="d-flex align-items-center mb-3">
            <span class="text-warning small mr-2">
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
            </span>
            <span class="text-dark font-weight-bold small mr-2">5.0</span>
            <span class="text-muted small border-left pl-2">Đã bán 49.5k</span>
          </div>

          <!-- Khung giá bán cập nhật theo cấu hình -->
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

          <!-- Danh sách cấu hình / biến thể cho người dùng chọn -->
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

          <!-- Bộ tăng giảm số lượng & Tồn kho -->
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

          <!-- Nút Mua hàng & Thêm vào giỏ có kiểm tra Đăng nhập -->
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

          <!-- Cam kết dịch vụ đi kèm -->
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

  <!-- Khối Mô tả chi tiết & Thông số kỹ thuật -->
  <div class="row">
    <!-- Cột trái: Mô tả sản phẩm lấy từ CSDL -->
    <div class="col-lg-8 mb-4">
      <div class="card border-0 rounded-2xl shadow-soft p-4 bg-white h-100">
        <h5 class="font-weight-bold text-dark border-bottom pb-3 mb-3">Đặc Điểm Nổi Bật & Mô Tả</h5>
        <div class="text-secondary" style="line-height: 1.8; white-space: pre-line;">
          <?= !empty($product['description']) ? htmlspecialchars($product['description']) : 'Thông tin chi tiết sản phẩm đang được cập nhật.' ?>
        </div>
      </div>
    </div>

    <!-- Cột phải: Bảng thông số kỹ thuật động theo biến thể -->
    <div class="col-lg-4 mb-4">
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
  // Hiển thị Toast thông báo yêu cầu đăng nhập ở góc trên bên phải
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

    // Kích hoạt hiệu ứng xuất hiện mượt
    setTimeout(function() {
      toastDiv.style.opacity = '1';
      toastDiv.style.transform = 'translateY(0)';
    }, 10);

    // Tự động biến mất sau 6 giây
    setTimeout(function() {
      if (toastDiv && document.body.contains(toastDiv)) {
        toastDiv.style.opacity = '0';
        toastDiv.style.transform = 'translateX(50px)';
        setTimeout(function() { toastDiv.remove(); }, 400);
      }
    }, 6000);
  }

  // Đổi ảnh lớn khi bấm vào ảnh con
  function changeMainImg(src, thumbEl) {
    document.getElementById('mainProductImg').src = src;
    document.querySelectorAll('.thumb-gallery-item').forEach(el => el.classList.remove('active-thumb'));
    thumbEl.classList.add('active-thumb');
  }

  // Chuyển đổi giữa các phiên bản cấu hình
  function selectVariant(btn) {
    document.querySelectorAll('.btn-variant-select').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    var vId = btn.getAttribute('data-id');
    var sale = btn.getAttribute('data-sale');
    var price = btn.getAttribute('data-price');
    var discount = parseInt(btn.getAttribute('data-discount')) || 0;
    var stock = parseInt(btn.getAttribute('data-stock')) || 0;

    // Gán ID biến thể vào hidden input để gửi form
    document.getElementById('selectedVariantId').value = vId;

    // Cập nhật giá bán
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

    // Cập nhật trạng thái số lượng & nút mua
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

    // Cập nhật bảng thông số kỹ thuật bên phải
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

  // Tăng giảm số lượng
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