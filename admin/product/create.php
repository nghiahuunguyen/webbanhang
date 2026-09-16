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
$page_title = 'Thêm Mới Sản Phẩm';

$error = '';
$title = '';
$slug = '';
$category_id = '';
$brand_id = '';
$description = '';

// Lấy danh mục và hãng chưa bị xóa (deleted = 0)
$categories = executeResult("SELECT * FROM Category WHERE deleted = 0 ORDER BY name ASC");
$brands     = executeResult("SELECT * FROM Brand WHERE deleted = 0 ORDER BY name ASC");

function createSlug($str) {
    $str = trim(mb_strtolower($str, 'UTF-8'));
    $str = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $str);
    $str = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $str);
    $str = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $str);
    $str = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $str);
    $str = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $str);
    $str = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $str);
    $str = preg_replace('/(đ)/', 'd', $str);
    $str = preg_replace('/[^a-z0-9-\s]/', '', $str);
    $str = preg_replace('/([\s]+)/', '-', $str);
    return trim($str, '-');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $slug        = trim($_POST['slug'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $brand_id    = intval($_POST['brand_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    // Thu nhận mảng biến thể
    $v_color    = $_POST['color'] ?? [];
    $v_cpu      = $_POST['cpu'] ?? [];
    $v_ram      = $_POST['ram'] ?? [];
    $v_storage  = $_POST['storage'] ?? [];
    $v_gpu      = $_POST['gpu'] ?? [];
    $v_price    = $_POST['price'] ?? [];
    $v_discount = $_POST['discount'] ?? [];
    $v_stock    = $_POST['stock'] ?? [];

    if (empty($title)) {
        $error = 'Vui lòng nhập tên dòng sản phẩm!';
    } elseif ($category_id <= 0) {
        $error = 'Vui lòng chọn danh mục sản phẩm!';
    } elseif ($brand_id <= 0) {
        $error = 'Vui lòng chọn thương hiệu / hãng!';
    } elseif (!isset($_FILES['thumbnail']) || $_FILES['thumbnail']['error'] !== 0) {
        $error = 'Vui lòng chọn ảnh đại diện cho sản phẩm!';
    } elseif (empty($v_price) || intval($v_price[0] ?? 0) <= 0) {
        $error = 'Vui lòng nhập ít nhất 1 phiên bản cấu hình có giá bán hợp lệ!';
    } else {
        $slug = empty($slug) ? createSlug($title) : createSlug($slug);
        $safe_slug = str_replace("'", "\'", $slug);

        // Kiểm tra trùng slug trong bảng Product
        $check_slug = executeResult("SELECT id FROM Product WHERE slug = '$safe_slug' AND deleted = 0 LIMIT 1", true);
        if ($check_slug) {
            $error = 'Đường dẫn (Slug) này đã tồn tại, vui lòng nhập tiêu đề hoặc slug khác!';
        } else {
            $upload_dir = '../../img/product/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // 1. Tải lên và lưu Thumbnail đại diện
            $thumb_file = $_FILES['thumbnail'];
            $ext = pathinfo($thumb_file['name'], PATHINFO_EXTENSION);
            $thumb_name = time() . '_' . uniqid() . '.' . strtolower($ext);
            $thumb_target = $upload_dir . $thumb_name;

            if (move_uploaded_file($thumb_file['tmp_name'], $thumb_target)) {
                $db_thumbnail     = 'img/product/' . $thumb_name;
                $safe_title       = str_replace("'", "\'", $title);
                $safe_description = str_replace("'", "\'", $description);
                $created_at       = date('Y-m-d H:i:s');
                $updated_at       = date('Y-m-d H:i:s');

                // 2. Chèn vào bảng Product (có cột slug)
                $sql_prod = "INSERT INTO Product (category_id, brand_id, title, slug, thumbnail, description, created_at, updated_at, deleted)
                             VALUES ('$category_id', '$brand_id', '$safe_title', '$safe_slug', '$db_thumbnail', '$safe_description', '$created_at', '$updated_at', 0)";
                execute($sql_prod);

                $last_p = executeResult("SELECT id FROM Product ORDER BY id DESC LIMIT 1", true);
                $product_id = intval($last_p['id'] ?? 0);

                if ($product_id > 0) {
                    // 3. Chèn toàn bộ các biến thể vào bảng Product_Variant
                    $total_variants = count($v_price);
                    for ($i = 0; $i < $total_variants; $i++) {
                        $cur_price = intval($v_price[$i] ?? 0);
                        if ($cur_price <= 0) continue;

                        $color_val   = trim($v_color[$i] ?? '');
                        $cpu_val     = trim($v_cpu[$i] ?? '');
                        $ram_val     = trim($v_ram[$i] ?? '');
                        $storage_val = trim($v_storage[$i] ?? '');
                        $gpu_val     = trim($v_gpu[$i] ?? '');
                        $disc_val    = intval($v_discount[$i] ?? 0);
                        $stock_val   = intval($v_stock[$i] ?? 0);

                        $sku_val = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $title), 0, 4)) . '-' . $product_id . '-' . ($i + 1);

                        $sql_variant = "INSERT INTO Product_Variant (product_id, sku, color, cpu, ram, storage, gpu, price, discount, stock_quantity, deleted)
                                        VALUES ('$product_id', '$sku_val', '$color_val', '$cpu_val', '$ram_val', '$storage_val', '$gpu_val', '$cur_price', '$disc_val', '$stock_val', 0)";
                        execute($sql_variant);
                    }

                    // 4. Lưu danh sách ảnh vào bảng Galery
                    if (isset($_FILES['galleries']) && !empty($_FILES['galleries']['name'][0])) {
                        $total_galls = count($_FILES['galleries']['name']);
                        for ($k = 0; $k < $total_galls; $k++) {
                            if ($_FILES['galleries']['error'][$k] === 0) {
                                $g_ext = pathinfo($_FILES['galleries']['name'][$k], PATHINFO_EXTENSION);
                                $g_name = time() . '_' . uniqid() . '_' . $k . '.' . strtolower($g_ext);
                                $g_target = $upload_dir . $g_name;

                                if (move_uploaded_file($_FILES['galleries']['tmp_name'][$k], $g_target)) {
                                    $db_g_path = 'img/product/' . $g_name;
                                    execute("INSERT INTO Galery (product_id, variant_id, thumbnail) VALUES ('$product_id', NULL, '$db_g_path')");
                                }
                            }
                        }
                    }

                    $_SESSION['success_msg'] = "Thêm sản phẩm và cấu hình thành công!";
                    header('Location: index.php');
                    die();
                }
            } else {
                $error = 'Không thể lưu file ảnh đại diện lên máy chủ!';
            }
        }
    }
}

include '../layout/head.php'; 
include '../layout/sidebar.php'; 
?>

<!-- TOAST THÔNG BÁO LỖI -->
<?php if (!empty($error)): ?>
  <div id="toast-wrapper" style="position: fixed; top: 20px; right: 20px; z-index: 99999; min-width: 320px; transition: all 0.5s ease;">
    <div class="toast show shadow-lg border-0" role="alert">
      <div class="toast-header bg-danger text-white">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <strong class="mr-auto">Lỗi nhập liệu</strong>
        <small class="text-white">Vừa xong</small>
        <button type="button" class="ml-2 mb-1 close text-white" onclick="document.getElementById('toast-wrapper').remove()" style="outline: none;">
          <span>&times;</span>
        </button>
      </div>
      <div class="toast-body bg-white text-dark font-weight-bold">
        <?= htmlspecialchars($error) ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-3">
  <?php include '../layout/topbar.php'; ?>

  <div class="custom-card mb-4">
    <div class="p-3 px-4 d-flex justify-content-between align-items-center border-bottom">
      <h5 class="font-weight-bold mb-0 text-dark">Thêm Mới Sản Phẩm & Biến Thể Cấu Hình</h5>
      <a href="index.php" class="btn btn-sm btn-secondary font-weight-bold">
        <i class="fas fa-arrow-left mr-1"></i> Quay lại
      </a>
    </div>

    <form class="p-4" method="POST" enctype="multipart/form-data" novalidate>
      
      <!-- KHỐI 1: THÔNG TIN CHUNG SẢN PHẨM -->
      <h6 class="text-primary font-weight-bold text-uppercase small tracking-wider mb-3">1. Thông tin chung</h6>
      <div class="form-row">
        <div class="form-group col-md-6">
          <label class="font-weight-bold">Tên sản phẩm <span class="text-danger">*</span></label>
          <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($title) ?>" placeholder="VD: iPhone 15 Pro Max, Laptop ROG Zephyrus..." required autofocus>
        </div>
        <div class="form-group col-md-6">
          <label class="font-weight-bold">Đường dẫn thân thiện (Slug)</label>
          <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($slug) ?>" placeholder="Tự động tạo từ tên nếu để trống (VD: iphone-15-pro-max)">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-6">
          <label class="font-weight-bold">Danh mục <span class="text-danger">*</span></label>
          <select name="category_id" class="form-control" required>
            <option value="">-- Chọn danh mục --</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= ($category_id == $cat['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group col-md-6">
          <label class="font-weight-bold">Hãng sản xuất <span class="text-danger">*</span></label>
          <select name="brand_id" class="form-control" required>
            <option value="">-- Chọn thương hiệu --</option>
            <?php foreach ($brands as $b): ?>
              <option value="<?= $b['id'] ?>" <?= ($brand_id == $b['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($b['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-6">
          <label class="font-weight-bold">Ảnh đại diện chính (Thumbnail) <span class="text-danger">*</span></label>
          <input type="file" name="thumbnail" class="form-control-file border p-1 rounded" accept="image/*" required>
        </div>
        <div class="form-group col-md-6">
          <label class="font-weight-bold">Bộ sưu tập ảnh chi tiết (Chọn nhiều ảnh)</label>
          <input type="file" name="galleries[]" class="form-control-file border p-1 rounded" accept="image/*" multiple>
        </div>
      </div>

      <div class="form-group mb-4">
        <label class="font-weight-bold">Mô tả tóm tắt tính năng</label>
        <textarea name="description" rows="3" class="form-control" placeholder="Nhập tóm tắt mô tả nổi bật về màn hình, pin, cổng kết nối..."><?= htmlspecialchars($description) ?></textarea>
      </div>

      <hr class="my-4">

      <!-- KHỐI 2: BIẾN THỂ CẤU HÌNH & BẢNG GIÁ ĐỘNG -->
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h6 class="text-primary font-weight-bold text-uppercase small tracking-wider mb-0">2. Cấu hình, Màu sắc & Giá bán</h6>
          <small class="text-muted">Nhập các phiên bản lựa chọn của máy (CPU, RAM, Ổ cứng, GPU, Màu sắc, Giá tương ứng)</small>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary font-weight-bold" id="btnAddVariant">
          <i class="fas fa-plus mr-1"></i> Thêm phiên bản
        </button>
      </div>

      <div class="table-responsive mb-4">
        <table class="table table-bordered table-sm text-center mb-0" id="variantTable">
          <thead class="thead-light">
            <tr>
              <th style="min-width: 120px;">Màu sắc</th>
              <th style="min-width: 130px;">CPU</th>
              <th style="min-width: 90px;">RAM</th>
              <th style="min-width: 110px;">Ổ cứng</th>
              <th style="min-width: 120px;">GPU</th>
              <th style="min-width: 130px;">Giá bán (VNĐ) <span class="text-danger">*</span></th>
              <th style="min-width: 120px;">Giảm giá</th>
              <th style="min-width: 90px;">Tồn kho</th>
              <th width="50px">Xóa</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><input type="text" name="color[]" class="form-control form-control-sm" placeholder="Titan Tự Nhiên"></td>
              <td><input type="text" name="cpu[]" class="form-control form-control-sm" placeholder="Core i7 / A17 Pro"></td>
              <td><input type="text" name="ram[]" class="form-control form-control-sm" placeholder="8GB"></td>
              <td><input type="text" name="storage[]" class="form-control form-control-sm" placeholder="256GB"></td>
              <td><input type="text" name="gpu[]" class="form-control form-control-sm" placeholder="RTX 4060"></td>
              <td><input type="number" name="price[]" class="form-control form-control-sm font-weight-bold" placeholder="VD: 25000000" required></td>
              <td><input type="number" name="discount[]" class="form-control form-control-sm" placeholder="0" value="0"></td>
              <td><input type="number" name="stock[]" class="form-control form-control-sm" placeholder="10" value="10"></td>
              <td class="align-middle text-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" disabled>
                  <i class="fas fa-trash-alt"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <button type="submit" class="btn btn-primary px-4 font-weight-bold shadow-sm">
        <i class="fas fa-save mr-1"></i> Lưu Toàn Bộ Sản Phẩm
      </button>
    </form>
  </div>
</main>

<?php include '../layout/scripts.php'; ?>

<script>
  document.getElementById('btnAddVariant').addEventListener('click', function () {
    var tableBody = document.querySelector('#variantTable tbody');
    var newRow = document.createElement('tr');
    newRow.innerHTML = `
      <td><input type="text" name="color[]" class="form-control form-control-sm" placeholder="Đen Nhám"></td>
      <td><input type="text" name="cpu[]" class="form-control form-control-sm" placeholder="Core i9 / M3 Max"></td>
      <td><input type="text" name="ram[]" class="form-control form-control-sm" placeholder="16GB"></td>
      <td><input type="text" name="storage[]" class="form-control form-control-sm" placeholder="512GB"></td>
      <td><input type="text" name="gpu[]" class="form-control form-control-sm" placeholder="RTX 4070"></td>
      <td><input type="number" name="price[]" class="form-control form-control-sm font-weight-bold" placeholder="VD: 28000000" required></td>
      <td><input type="number" name="discount[]" class="form-control form-control-sm" placeholder="0" value="0"></td>
      <td><input type="number" name="stock[]" class="form-control form-control-sm" placeholder="10" value="10"></td>
      <td class="align-middle text-center">
        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">
          <i class="fas fa-trash-alt"></i>
        </button>
      </td>
    `;
    tableBody.appendChild(newRow);
    attachRemoveEvent();
  });

  function attachRemoveEvent() {
    var removeButtons = document.querySelectorAll('.btn-remove-row');
    removeButtons.forEach(function (btn) {
      btn.onclick = function () {
        var row = this.closest('tr');
        var tbody = row.parentElement;
        if (tbody.children.length > 1) {
          row.remove();
        }
      };
    });
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