<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once __DIR__ . '/database/dbhelper.php';

$user_id = $_SESSION['user']['id'] ?? null;

// 1. THÊM SẢN PHẨM VÀO GIỎ HÀNG
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $variant_id = intval($_POST['variant_id'] ?? 0);
    $quantity   = intval($_POST['quantity'] ?? 1);
    if ($quantity < 1) $quantity = 1;

    if ($variant_id > 0 && $product_id > 0) {
        $variant = executeResult("SELECT stock_quantity FROM `Product_Variant` WHERE id = $variant_id AND deleted = 0 LIMIT 1", true);
        $stock = (int)($variant['stock_quantity'] ?? 0);

        if ($user_id) {
            $exist = executeResult("SELECT id, num FROM `Cart` WHERE user_id = $user_id AND variant_id = $variant_id LIMIT 1", true);
            if ($exist) {
                $new_num = min($stock, $exist['num'] + $quantity);
                execute("UPDATE `Cart` SET num = $new_num WHERE id = {$exist['id']}");
            } else {
                $new_num = min($stock, $quantity);
                execute("INSERT INTO `Cart` (user_id, product_id, variant_id, num) VALUES ($user_id, $product_id, $variant_id, $new_num)");
            }
        } else {
            if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
            $cur_num = $_SESSION['cart'][$variant_id]['num'] ?? 0;
            $_SESSION['cart'][$variant_id] = [
                'product_id' => $product_id,
                'variant_id' => $variant_id,
                'num'        => min($stock, $cur_num + $quantity)
            ];
        }

        $_SESSION['cart_toast'] = [
            'type'    => 'success',
            'title'   => 'Giỏ hàng',
            'message' => 'Đã thêm sản phẩm vào giỏ hàng thành công!'
        ];
    }

    if (isset($_POST['btn_buy_now'])) {
        header('Location: checkout.php');
    } else {
        header('Location: cart.php');
    }
    die();
}

// 2. CẬP NHẬT SỐ LƯỢNG SẢN PHẨM (+ / -)
if (isset($_GET['action']) && $_GET['action'] === 'update_qty') {
    $v_id = intval($_GET['variant_id'] ?? 0);
    $num  = intval($_GET['num'] ?? 1);

    $variant = executeResult("SELECT stock_quantity FROM `Product_Variant` WHERE id = $v_id AND deleted = 0 LIMIT 1", true);
    $stock = (int)($variant['stock_quantity'] ?? 0);
    $num = max(1, min($stock, $num));

    if ($user_id) {
        execute("UPDATE `Cart` SET num = $num WHERE user_id = $user_id AND variant_id = $v_id");
    } else {
        if (isset($_SESSION['cart'][$v_id])) {
            $_SESSION['cart'][$v_id]['num'] = $num;
        }
    }

    $_SESSION['cart_toast'] = [
        'type'    => 'info',
        'title'   => 'Cập nhật',
        'message' => 'Đã cập nhật lại số lượng sản phẩm!'
    ];

    header('Location: cart.php');
    die();
}

// 3. XÓA SẢN PHẨM KHỎI GIỎ HÀNG
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $v_id = intval($_GET['variant_id'] ?? 0);

    if ($user_id) {
        execute("DELETE FROM `Cart` WHERE user_id = $user_id AND variant_id = $v_id");
    } else {
        unset($_SESSION['cart'][$v_id]);
    }

    $_SESSION['cart_toast'] = [
        'type'    => 'danger',
        'title'   => 'Xóa sản phẩm',
        'message' => 'Đã xóa sản phẩm khỏi giỏ hàng!'
    ];

    header('Location: cart.php');
    die();
}

// TRUY VẤN DANH SÁCH SẢN PHẨM TRONG GIỎ
$cart_list = [];
if ($user_id) {
    $sql = "SELECT c.id AS cart_id, c.num, 
                   p.id AS product_id, p.title, p.thumbnail, 
                   v.id AS variant_id, v.sku, v.color, v.cpu, v.ram, v.storage, v.gpu, 
                   v.price, v.discount, (v.price - v.discount) AS final_price, 
                   v.stock_quantity
            FROM `Cart` c
            JOIN `Product` p ON c.product_id = p.id
            JOIN `Product_Variant` v ON c.variant_id = v.id
            WHERE c.user_id = $user_id AND v.deleted = 0 AND p.deleted = 0
            ORDER BY c.id DESC";
    $cart_list = executeResult($sql);
} else {
    if (!empty($_SESSION['cart'])) {
        $variant_ids = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
        if (!empty($variant_ids)) {
            $sql = "SELECT p.id AS product_id, p.title, p.thumbnail, 
                           v.id AS variant_id, v.sku, v.color, v.cpu, v.ram, v.storage, v.gpu, 
                           v.price, v.discount, (v.price - v.discount) AS final_price, 
                           v.stock_quantity
                    FROM `Product_Variant` v
                    JOIN `Product` p ON v.product_id = p.id
                    WHERE v.id IN ($variant_ids) AND v.deleted = 0 AND p.deleted = 0";
            $res = executeResult($sql);
            foreach ($res as $row) {
                $row['num'] = $_SESSION['cart'][$row['variant_id']]['num'] ?? 1;
                $cart_list[] = $row;
            }
        }
    }
}

// TÍNH TỔNG TIỀN VÀ SỐ LƯỢNG
$total_money = 0;
$total_items = 0;
foreach ($cart_list as $item) {
    $total_money += (int)$item['final_price'] * (int)$item['num'];
    $total_items += (int)$item['num'];
}

$toast_data = $_SESSION['cart_toast'] ?? null;
unset($_SESSION['cart_toast']);

$page_title = 'Giỏ Hàng Của Bạn';
include 'includes/header.php'; 
?>

<!-- TOAST THÔNG BÁO GÓC PHẢI -->
<div id="cart-toast-wrapper" style="position: fixed; top: 25px; right: 25px; z-index: 99999; min-width: 320px; max-width: 380px; display: none;">
  <div class="card border-0 shadow-lg" style="border-radius: 12px; overflow: hidden; border-left: 5px solid #2563eb !important;">
    <div class="card-body p-3 bg-white text-dark d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center">
        <span id="toastIcon" class="text-primary mr-2" style="font-size: 1.2rem;"><i class="fas fa-check-circle"></i></span>
        <div>
          <strong id="toastTitle" class="d-block text-dark font-weight-bold" style="font-size: 0.9rem;">Thông báo</strong>
          <span id="toastMessage" class="small text-muted" style="line-height: 1.3;">Thao tác thành công!</span>
        </div>
      </div>
      <button type="button" class="close text-muted ml-2" onclick="closeCartToast()" style="outline: none; font-size: 1.25rem;">&times;</button>
    </div>
  </div>
</div>

<main class="container my-5">
  <div class="d-flex align-items-center mb-4">
    <h3 class="font-weight-bold text-dark mb-0 mr-3">Giỏ Hàng Của Bạn</h3>
    <span class="badge badge-pill badge-primary px-3 py-2 text-white font-weight-bold">
      <?= $total_items ?> món hàng
    </span>
  </div>

  <?php if (empty($cart_list)): ?>
    <div class="card border-0 rounded-2xl shadow-soft p-5 text-center bg-white">
      <i class="fas fa-shopping-basket fa-4x text-muted mb-3"></i>
      <h5 class="text-secondary font-weight-bold">Giỏ hàng của bạn đang trống!</h5>
      <p class="text-muted small">Hãy dạo quanh cửa hàng để khám phá các sản phẩm công nghệ nhé.</p>
      <div class="mt-3">
        <a href="index.php" class="btn btn-primary font-weight-bold px-4 rounded-pill">Tiếp tục mua sắm</a>
      </div>
    </div>
  <?php else: ?>
    <div class="row">
      <!-- CỘT DANH SÁCH SẢN PHẨM -->
      <div class="col-lg-8 mb-4">
        <div class="card border-0 rounded-2xl shadow-soft p-4 bg-white">
          <div class="table-responsive">
            <table class="table align-middle border-0 mb-0">
              <thead class="text-muted small border-0">
                <tr>
                  <th scope="col" class="border-0 pl-0">SẢN PHẨM</th>
                  <th scope="col" class="border-0 text-center">ĐƠN GIÁ</th>
                  <th scope="col" class="border-0 text-center">SỐ LƯỢNG</th>
                  <th scope="col" class="border-0 text-right pr-0">THÀNH TIỀN</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($cart_list as $row): 
                    $specs = array_filter([$row['color'], $row['cpu'], $row['ram'], $row['storage'], $row['gpu']]);
                    $spec_str = !empty($specs) ? implode(' · ', $specs) : 'Bản tiêu chuẩn';
                    $line_total = (int)$row['final_price'] * (int)$row['num'];
                    $img = (!empty($row['thumbnail']) && file_exists($row['thumbnail'])) 
                           ? htmlspecialchars($row['thumbnail']) 
                           : 'https://via.placeholder.com/120x120?text=No+Image';
                ?>
                  <tr class="border-bottom">
                    <!-- Ảnh & Cấu hình -->
                    <td class="pl-0 py-3">
                      <div class="d-flex align-items-center">
                        <img src="<?= $img ?>" class="rounded-lg mr-3 border" style="width: 70px; height: 70px; object-fit: contain;" alt="<?= htmlspecialchars($row['title']) ?>">
                        <div>
                          <a href="product-detail.php?id=<?= $row['product_id'] ?>" class="h6 font-weight-bold text-dark mb-1 text-decoration-none d-block">
                            <?= htmlspecialchars($row['title']) ?>
                          </a>
                          <span class="text-muted small d-block mb-1"><?= htmlspecialchars($spec_str) ?></span>
                          <a href="javascript:;" 
                             class="text-danger small font-weight-bold text-decoration-none"
                             onclick="confirmDeleteToast(<?= $row['variant_id'] ?>)">
                            <i class="fas fa-trash-alt mr-1"></i> Xóa
                          </a>
                        </div>
                      </div>
                    </td>

                    <!-- Đơn giá -->
                    <td class="text-center align-middle font-weight-semibold" style="white-space: nowrap;">
                      <?= number_format($row['final_price'], 0, ',', '.') ?> ₫
                      <?php if ($row['discount'] > 0): ?>
                        <small class="text-muted d-block text-decoration-line-through"><del><?= number_format($row['price'], 0, ',', '.') ?> ₫</del></small>
                      <?php endif; ?>
                    </td>

                    <!-- Nút tăng giảm số lượng -->
                    <td class="text-center align-middle" style="width: 140px;">
                      <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                          <a href="cart.php?action=update_qty&variant_id=<?= $row['variant_id'] ?>&num=<?= $row['num'] - 1 ?>" 
                             class="btn btn-outline-secondary border-right-0 rounded-left <?= $row['num'] <= 1 ? 'disabled' : '' ?>">-</a>
                        </div>
                        <input type="text" class="form-control text-center font-weight-bold bg-white" value="<?= $row['num'] ?>" readonly>
                        <div class="input-group-append">
                          <a href="cart.php?action=update_qty&variant_id=<?= $row['variant_id'] ?>&num=<?= $row['num'] + 1 ?>" 
                             class="btn btn-outline-secondary border-left-0 rounded-right <?= $row['num'] >= $row['stock_quantity'] ? 'disabled' : '' ?>">+</a>
                        </div>
                      </div>
                      <small class="text-muted d-block mt-1 font-italic" style="font-size: 0.72rem;">Kho: <?= $row['stock_quantity'] ?></small>
                    </td>

                    <!-- Thành tiền -->
                    <td class="text-right align-middle font-weight-bold text-dark pr-0" style="white-space: nowrap;">
                      <?= number_format($line_total, 0, ',', '.') ?> ₫
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
            <a href="index.php" class="btn btn-light rounded-pill-custom small font-weight-bold">
              <i class="fas fa-arrow-left mr-2"></i> Tiếp tục mua sắm
            </a>
          </div>
        </div>
      </div>

      <!-- CỘT TỔNG TIỀN VÀ NÚT THANH TOÁN -->
      <div class="col-lg-4">
        <div class="card border-0 rounded-2xl shadow-soft p-4 bg-white mb-4">
          <h5 class="font-weight-bold text-dark mb-3">Tóm Tắt Đơn Hàng</h5>
          
          <div class="d-flex justify-content-between py-2 border-bottom text-muted small">
            <span>Tạm tính</span>
            <span class="text-dark font-weight-bold"><?= number_format($total_money, 0, ',', '.') ?> ₫</span>
          </div>
          <div class="d-flex justify-content-between py-2 border-bottom text-muted small">
            <span>Phí vận chuyển</span>
            <span class="text-success font-weight-bold">Miễn phí</span>
          </div>

          <div class="d-flex justify-content-between py-3 border-top align-items-center mb-3">
            <span class="font-weight-bold text-dark">Tổng thanh toán</span>
            <span class="h4 font-weight-bold text-danger mb-0"><?= number_format($total_money, 0, ',', '.') ?> ₫</span>
          </div>

          <a href="checkout.php" class="btn btn-primary btn-block rounded-pill-custom py-2 font-weight-bold shadow-sm mb-2 text-decoration-none text-center text-white">
            Tiến Hành Thanh Toán &rarr;
          </a>
          <div class="text-center text-muted small mt-2">
            <i class="fas fa-shield-alt text-success mr-1"></i> Bảo mật thanh toán an toàn 100%
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</main>

<script>
// Toast thông báo trạng thái
function showCartToast(title, message, type = 'success') {
  var wrapper = document.getElementById('cart-toast-wrapper');
  var titleEl = document.getElementById('toastTitle');
  var msgEl   = document.getElementById('toastMessage');
  var iconEl  = document.getElementById('toastIcon');
  var cardEl  = wrapper.querySelector('.card');

  if (!wrapper) return;

  titleEl.innerText = title;
  msgEl.innerText = message;

  if (type === 'danger') {
    cardEl.style.borderLeftColor = '#ef4444';
    iconEl.className = 'text-danger mr-2';
    iconEl.innerHTML = '<i class="fas fa-trash-alt"></i>';
  } else if (type === 'info') {
    cardEl.style.borderLeftColor = '#3b82f6';
    iconEl.className = 'text-primary mr-2';
    iconEl.innerHTML = '<i class="fas fa-sync-alt"></i>';
  } else {
    cardEl.style.borderLeftColor = '#10b981';
    iconEl.className = 'text-success mr-2';
    iconEl.innerHTML = '<i class="fas fa-check-circle"></i>';
  }

  wrapper.style.display = 'block';
  wrapper.style.opacity = '0';
  wrapper.style.transform = 'translateY(-15px)';
  wrapper.style.transition = 'all 0.35s cubic-bezier(0.16, 1, 0.3, 1)';

  setTimeout(function() {
    wrapper.style.opacity = '1';
    wrapper.style.transform = 'translateY(0)';
  }, 10);

  setTimeout(closeCartToast, 3500);
}

function closeCartToast() {
  var wrapper = document.getElementById('cart-toast-wrapper');
  if (wrapper) {
    wrapper.style.opacity = '0';
    wrapper.style.transform = 'translateX(40px)';
    setTimeout(function() { wrapper.style.display = 'none'; }, 350);
  }
}

// Popup xác nhận xóa đặt ở góc trên bên phải
function confirmDeleteToast(variantId) {
  var oldToast = document.getElementById('delete-confirm-toast');
  if (oldToast) oldToast.remove();

  var toastDiv = document.createElement('div');
  toastDiv.id = 'delete-confirm-toast';
  toastDiv.style.cssText = 'position: fixed; top: 25px; right: 25px; z-index: 99999; min-width: 320px; max-width: 380px; transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1); transform: translateY(-15px); opacity: 0;';

  toastDiv.innerHTML = `
    <div class="card border-0 shadow-lg" style="border-radius: 12px; overflow: hidden; border-left: 5px solid #ef4444 !important;">
      <div class="card-body p-3 bg-white text-dark">
        <div class="d-flex align-items-start justify-content-between mb-2">
          <div class="d-flex align-items-center">
            <span class="text-danger mr-2" style="font-size: 1.15rem;"><i class="fas fa-trash-alt"></i></span>
            <strong class="text-dark font-weight-bold" style="font-size: 0.95rem;">Xác nhận xóa</strong>
          </div>
          <button type="button" class="close text-muted" onclick="this.closest('#delete-confirm-toast').remove()" style="outline: none; font-size: 1.25rem; line-height: 1;">&times;</button>
        </div>
        <p class="small text-muted mb-3">Bạn có chắc chắn muốn xóa sản phẩm này khỏi giỏ hàng?</p>
        <div class="d-flex justify-content-end" style="gap: 8px;">
          <button type="button" class="btn btn-sm btn-light border px-3 py-1 font-weight-bold" onclick="this.closest('#delete-confirm-toast').remove()">Hủy</button>
          <a href="cart.php?action=delete&variant_id=${variantId}" class="btn btn-sm btn-danger px-3 py-1 font-weight-bold text-white shadow-sm" style="border-radius: 6px;">Xóa ngay</a>
        </div>
      </div>
    </div>
  `;

  document.body.appendChild(toastDiv);

  setTimeout(function() {
    toastDiv.style.opacity = '1';
    toastDiv.style.transform = 'translateY(0)';
  }, 10);
}

// Tự kích hoạt Toast khi có thao tác từ PHP
window.addEventListener('load', function() {
  <?php if (!empty($toast_data)): ?>
    showCartToast(
      '<?= addslashes($toast_data['title']) ?>', 
      '<?= addslashes($toast_data['message']) ?>', 
      '<?= addslashes($toast_data['type']) ?>'
    );
  <?php endif; ?>
});
</script>

<?php 
include 'includes/footer.php'; 
?>