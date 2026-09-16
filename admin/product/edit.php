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
$page_title = 'Chỉnh Sửa Sản Phẩm & Cấu Hình';

$id = $_GET['id'] ?? '';
if (empty($id)) {
    header('Location: index.php');
    die();
}
$id = intval($id);

// 1. Xử lý xóa nhanh một ảnh Gallery bằng GET request
if (isset($_GET['delete_gallery'])) {
    $gal_id = intval($_GET['delete_gallery']);
    $gal_item = executeResult("SELECT thumbnail FROM Galery WHERE id = $gal_id AND product_id = $id", true);
    if ($gal_item) {
        $file_on_disk = '../../' . $gal_item['thumbnail'];
        if (file_exists($file_on_disk)) {
            unlink($file_on_disk);
        }
        execute("DELETE FROM Galery WHERE id = $gal_id");
        $_SESSION['success_msg'] = "Đã xóa 1 ảnh khỏi thư viện!";
    }
    header("Location: edit.php?id=$id");
    die();
}

// 2. Lấy dữ liệu sản phẩm hiện tại
$product = executeResult("SELECT * FROM Product WHERE id = $id AND deleted = 0", true);
if (!$product) {
    header('Location: index.php');
    die();
}

// Lấy danh mục và thương hiệu hợp lệ
$categories = executeResult("SELECT * FROM Category WHERE deleted = 0 ORDER BY name ASC");
$brands     = executeResult("SELECT * FROM Brand WHERE deleted = 0 ORDER BY name ASC");

// Lấy danh sách biến thể hiện có của máy
$variants   = executeResult("SELECT * FROM Product_Variant WHERE product_id = $id AND deleted = 0 ORDER BY id ASC");

// Lấy danh sách ảnh phụ
$galleries  = executeResult("SELECT * FROM Galery WHERE product_id = $id ORDER BY id DESC");

$error = '';

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
    $v_id       = $_POST['variant_id'] ?? [];
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
    } elseif (empty($v_price)) {
        $error = 'Sản phẩm phải có ít nhất một phiên bản cấu hình!';
    } else {
        $slug = empty($slug) ? createSlug($title) : createSlug($slug);
        $safe_title       = str_replace("'", "\'", $title);
        $safe_slug        = str_replace("'", "\'", $slug);
        $safe_description = str_replace("'", "\'", $description);

        // Kiểm tra xem slug có trùng với sản phẩm khác không
        $check_slug = executeResult("SELECT id FROM Product WHERE slug = '$safe_slug' AND id != $id AND deleted = 0 LIMIT 1", true);
        if ($check_slug) {
            $error = 'Đường dẫn (Slug) này đã tồn tại ở sản phẩm khác!';
        } else {
            $upload_dir = '../../img/product/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // 1. Cập nhật ảnh đại diện nếu có chọn ảnh mới
            $db_thumbnail = $product['thumbnail'];
            if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === 0) {
                $thumb_file = $_FILES['thumbnail'];
                $ext = pathinfo($thumb_file['name'], PATHINFO_EXTENSION);
                $thumb_name = time() . '_' . uniqid() . '.' . strtolower($ext);
                $thumb_target = $upload_dir . $thumb_name;

                if (move_uploaded_file($thumb_file['tmp_name'], $thumb_target)) {
                    if (!empty($product['thumbnail']) && file_exists('../../' . $product['thumbnail'])) {
                        unlink('../../' . $product['thumbnail']);
                    }
                    $db_thumbnail = 'img/product/' . $thumb_name;
                }
            }

            $safe_thumbnail = str_replace("'", "\'", $db_thumbnail);
            $updated_at     = date('Y-m-d H:i:s');

            // 2. Cập nhật bảng Product kèm slug
            $sql_prod = "UPDATE Product SET 
                            category_id = '$category_id',
                            brand_id = '$brand_id',
                            title = '$safe_title',
                            slug = '$safe_slug',
                            thumbnail = '$safe_thumbnail',
                            description = '$safe_description',
                            updated_at = '$updated_at'
                         WHERE id = $id";
            execute($sql_prod);

            // 3. Xử lý danh sách biến thể
            $kept_variant_ids = [];
            $total_rows = count($v_price);

            for ($i = 0; $i < $total_rows; $i++) {
                $cur_price = intval($v_price[$i] ?? 0);
                if ($cur_price <= 0) continue;

                $var_id_curr = intval($v_id[$i] ?? 0);
                $color_val   = trim($v_color[$i] ?? '');
                $cpu_val     = trim($v_cpu[$i] ?? '');
                $ram_val     = trim($v_ram[$i] ?? '');
                $storage_val = trim($v_storage[$i] ?? '');
                $gpu_val     = trim($v_gpu[$i] ?? '');
                $disc_val    = intval($v_discount[$i] ?? 0);
                $stock_val   = intval($v_stock[$i] ?? 0);

                if ($var_id_curr > 0) {
                    $sql_u = "UPDATE Product_Variant SET 
                                color = '$color_val',
                                cpu = '$cpu_val',
                                ram = '$ram_val',
                                storage = '$storage_val',
                                gpu = '$gpu_val',
                                price = '$cur_price',
                                discount = '$disc_val',
                                stock_quantity = '$stock_val'
                              WHERE id = $var_id_curr AND product_id = $id";
                    execute($sql_u);
                    $kept_variant_ids[] = $var_id_curr;
                } else {
                    $sku_val = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $title), 0, 4)) . '-' . $id . '-' . time() . $i;
                    $sql_i = "INSERT INTO Product_Variant (product_id, sku, color, cpu, ram, storage, gpu, price, discount, stock_quantity, deleted)
                              VALUES ('$id', '$sku_val', '$color_val', '$cpu_val', '$ram_val', '$storage_val', '$gpu_val', '$cur_price', '$disc_val', '$stock_val', 0)";
                    execute($sql_i);
                    
                    $new_v = executeResult("SELECT id FROM Product_Variant ORDER BY id DESC LIMIT 1", true);
                    if ($new_v) $kept_variant_ids[] = $new_v['id'];
                }
            }

            // Xóa các biến thể đã bị người dùng gỡ khỏi bảng giao diện
            if (!empty($kept_variant_ids)) {
                $kept_str = implode(',', $kept_variant_ids);
                execute("UPDATE Product_Variant SET deleted = 1 WHERE product_id = $id AND id NOT IN ($kept_str)");
            }

            // 4. Tải thêm ảnh phụ vào Galery (nếu có chọn thêm)
            if (isset($_FILES['galleries']) && !empty($_FILES['galleries']['name'][0])) {
                $total_g = count($_FILES['galleries']['name']);
                for ($k = 0; $k < $total_g; $k++) {
                    if ($_FILES['galleries']['error'][$k] === 0) {
                        $g_ext = pathinfo($_FILES['galleries']['name'][$k], PATHINFO_EXTENSION);
                        $g_name = time() . '_' . uniqid() . '_' . $k . '.' . strtolower($g_ext);
                        $g_target = $upload_dir . $g_name;

                        if (move_uploaded_file($_FILES['galleries']['tmp_name'][$k], $g_target)) {
                            $db_g_path = 'img/product/' . $g_name;
                            execute("INSERT INTO Galery (product_id, variant_id, thumbnail) VALUES ('$id', NULL, '$db_g_path')");
                        }
                    }
                }
            }

            $_SESSION['success_msg'] = "Cập nhật sản phẩm và biến thể thành công!";
            header('Location: index.php');
            die();
        }
    }
}

// Giữ lại dữ liệu đang nhập nếu có lỗi
$title_val       = $_POST['title'] ?? $product['title'];
$slug_val        = $_POST['slug'] ?? ($product['slug'] ?? '');
$category_id_val = $_POST['category_id'] ?? $product['category_id'];
$brand_id_val    = $_POST['brand_id'] ?? $product['brand_id'];
$description_val = $_POST['description'] ?? $product['description'];

include '../layout/head.php'; 
include '../layout/sidebar.php'; 
?>

<!-- TOAST THÔNG BÁO LỖI -->
<?php if (!empty($error)): ?>
  <div id="toast-wrapper" style="position: fixed; top: 20px; right: 20px; z-index: 99999; min-width: 320px; transition: all 0.5s ease;">
    <div class="toast show shadow-lg border-0" role="alert">
      <div class="toast-header bg-danger text-white">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <strong class="mr-auto">Lỗi cập nhật</strong>
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

<!-- MAIN CONTENT -->
<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-3">
  <?php include '../layout/topbar.php'; ?>

  <div class="custom-card mb-4">
    <div class="p-3 px-4 d-flex justify-content-between align-items-center border-bottom flex-wrap">
      <div>
        <h5 class="font-weight-bold mb-0 text-dark">Chỉnh Sửa Sản Phẩm: <?= htmlspecialchars($product['title']) ?></h5>
        <small class="text-muted">Mã sản phẩm: #PRD-<?= $product['id'] ?></small>
      </div>
      <div class="mt-2 mt-md-0">
        <a href="detail.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-outline-info font-weight-bold mr-1">
          <i class="fas fa-eye mr-1"></i> Xem chi tiết
        </a>
        <a href="index.php" class="btn btn-sm btn-secondary font-weight-bold">
          <i class="fas fa-arrow-left mr-1"></i> Quay lại
        </a>
      </div>
    </div>

    <form class="p-4" method="POST" enctype="multipart/form-data" novalidate>
      <!-- KHỐI 1: THÔNG TIN CƠ BẢN -->
      <h6 class="text-primary font-weight-bold text-uppercase small tracking-wider mb-3">1. Thông tin chung</h6>
      <div class="form-row">
        <div class="form-group col-md-6">
          <label class="font-weight-bold">Tên sản phẩm <span class="text-danger">*</span></label>
          <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($title_val) ?>" required autofocus>
        </div>
        <div class="form-group col-md-6">
          <label class="font-weight-bold">Đường dẫn thân thiện (Slug)</label>
          <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($slug_val) ?>">
          <small class="text-muted">Đường dẫn chuẩn SEO (VD: san-pham/iphone-15-pro-max.html).</small>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-6">
          <label class="font-weight-bold">Danh mục <span class="text-danger">*</span></label>
          <select name="category_id" class="form-control" required>
            <option value="">-- Chọn danh mục --</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= ($category_id_val == $cat['id']) ? 'selected' : '' ?>>
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
              <option value="<?= $b['id'] ?>" <?= ($brand_id_val == $b['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($b['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Ảnh đại diện và album ảnh -->
      <div class="form-row">
        <div class="form-group col-md-6">
          <label class="font-weight-bold">Ảnh đại diện chính (Thumbnail)</label>
          <div class="d-flex align-items-center mb-2">
            <?php 
              $cur_thumb = '../../' . $product['thumbnail'];
              $thumb_src = (!empty($product['thumbnail']) && file_exists($cur_thumb)) ? $cur_thumb : 'https://via.placeholder.com/70x70?text=No+Img';
            ?>
            <img src="<?= $thumb_src ?>" class="rounded border p-1 mr-3 bg-white" width="65" height="65" style="object-fit: cover;">
            <div class="flex-grow-1">
              <input type="file" name="thumbnail" class="form-control-file border p-1 rounded" accept="image/*">
              <small class="text-muted">Bỏ trống nếu muốn giữ nguyên ảnh đại diện cũ.</small>
            </div>
          </div>
        </div>

        <div class="form-group col-md-6">
          <label class="font-weight-bold">Tải thêm ảnh vào bộ sưu tập</label>
          <input type="file" name="galleries[]" class="form-control-file border p-1 rounded" accept="image/*" multiple>
          <small class="text-muted">Giữ phím Ctrl để chọn cùng lúc nhiều file ảnh.</small>
        </div>
      </div>

      <?php if (!empty($galleries)): ?>
        <div class="form-group">
          <label class="font-weight-bold small text-muted text-uppercase mb-2">Thư viện ảnh hiện tại (Nhấn vào nút đỏ để xóa ảnh):</label>
          <div class="d-flex flex-wrap" style="gap: 12px;">
            <?php foreach ($galleries as $gal): 
              $gal_img = '../../' . $gal['thumbnail'];
              $gal_src = (!empty($gal['thumbnail']) && file_exists($gal_img)) ? $gal_img : 'https://via.placeholder.com/60x60?text=No+Img';
            ?>
              <div class="position-relative border rounded p-1 bg-white">
                <img src="<?= $gal_src ?>" width="65" height="65" class="rounded" style="object-fit: cover;">
                <a href="edit.php?id=<?= $id ?>&delete_gallery=<?= $gal['id'] ?>" 
                   class="badge badge-danger position-absolute" 
                   style="top: -6px; right: -6px; border-radius: 50%; padding: 4px 6px;"
                   onclick="return confirm('Bạn có chắc muốn xóa ảnh này?');"
                   title="Xóa ảnh này">
                  &times;
                </a>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="form-group mb-4">
        <label class="font-weight-bold">Mô tả chi tiết sản phẩm</label>
        <textarea name="description" rows="3" class="form-control"><?= htmlspecialchars($description_val) ?></textarea>
      </div>

      <hr class="my-4">

      <!-- KHỐI 2: BIẾN THỂ CẤU HÌNH & BẢNG GIÁ -->
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h6 class="text-primary font-weight-bold text-uppercase small tracking-wider mb-0">2. Cấu hình, Màu sắc & Giá bán</h6>
          <small class="text-muted">Chỉnh sửa CPU, giá, tồn kho hoặc bổ sung thêm biến thể</small>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary font-weight-bold" id="btnAddVariant">
          <i class="fas fa-plus mr-1"></i> Thêm phiên bản mới
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
            <?php if (!empty($variants)): ?>
              <?php foreach ($variants as $v): ?>
                <tr>
                  <input type="hidden" name="variant_id[]" value="<?= $v['id'] ?>">
                  <td><input type="text" name="color[]" class="form-control form-control-sm" value="<?= htmlspecialchars($v['color']) ?>" placeholder="Đen Nhám"></td>
                  <td><input type="text" name="cpu[]" class="form-control form-control-sm" value="<?= htmlspecialchars($v['cpu'] ?? '') ?>" placeholder="Core i7 / M3 Pro"></td>
                  <td><input type="text" name="ram[]" class="form-control form-control-sm" value="<?= htmlspecialchars($v['ram']) ?>" placeholder="8GB"></td>
                  <td><input type="text" name="storage[]" class="form-control form-control-sm" value="<?= htmlspecialchars($v['storage']) ?>" placeholder="256GB"></td>
                  <td><input type="text" name="gpu[]" class="form-control form-control-sm" value="<?= htmlspecialchars($v['gpu']) ?>" placeholder="RTX 4060"></td>
                  <td><input type="number" name="price[]" class="form-control form-control-sm font-weight-bold" value="<?= $v['price'] ?>" required></td>
                  <td><input type="number" name="discount[]" class="form-control form-control-sm" value="<?= $v['discount'] ?>"></td>
                  <td><input type="number" name="stock[]" class="form-control form-control-sm" value="<?= $v['stock_quantity'] ?>"></td>
                  <td class="align-middle text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">
                      <i class="fas fa-trash-alt"></i>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <input type="hidden" name="variant_id[]" value="0">
                <td><input type="text" name="color[]" class="form-control form-control-sm" placeholder="Mặc định"></td>
                <td><input type="text" name="cpu[]" class="form-control form-control-sm" placeholder="Core i5 / A16"></td>
                <td><input type="text" name="ram[]" class="form-control form-control-sm" placeholder="8GB"></td>
                <td><input type="text" name="storage[]" class="form-control form-control-sm" placeholder="128GB"></td>
                <td><input type="text" name="gpu[]" class="form-control form-control-sm" placeholder="Onboard"></td>
                <td><input type="number" name="price[]" class="form-control form-control-sm font-weight-bold" placeholder="VD: 15000000" required></td>
                <td><input type="number" name="discount[]" class="form-control form-control-sm" value="0"></td>
                <td><input type="number" name="stock[]" class="form-control form-control-sm" value="10"></td>
                <td class="align-middle text-center">
                  <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" disabled>
                    <i class="fas fa-trash-alt"></i>
                  </button>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <button type="submit" class="btn btn-warning px-4 font-weight-bold text-white shadow-sm">
        <i class="fas fa-save mr-1"></i> Lưu Toàn Bộ Cập Nhật
      </button>
    </form>
  </div>
</main>

<?php include '../layout/scripts.php'; ?>

<script>
  function attachRemoveEvent() {
    var removeButtons = document.querySelectorAll('.btn-remove-row');
    removeButtons.forEach(function (btn) {
      btn.onclick = function () {
        var row = this.closest('tr');
        var tbody = row.parentElement;
        if (tbody.children.length > 1) {
          row.remove();
        } else {
          alert('Sản phẩm phải có ít nhất 1 phiên bản cấu hình!');
        }
      };
    });
  }
  attachRemoveEvent();

  document.getElementById('btnAddVariant').addEventListener('click', function () {
    var tableBody = document.querySelector('#variantTable tbody');
    var newRow = document.createElement('tr');
    newRow.innerHTML = `
      <input type="hidden" name="variant_id[]" value="0">
      <td><input type="text" name="color[]" class="form-control form-control-sm" placeholder="Màu mới"></td>
      <td><input type="text" name="cpu[]" class="form-control form-control-sm" placeholder="Core i7 / Ryzen 7"></td>
      <td><input type="text" name="ram[]" class="form-control form-control-sm" placeholder="16GB"></td>
      <td><input type="text" name="storage[]" class="form-control form-control-sm" placeholder="512GB"></td>
      <td><input type="text" name="gpu[]" class="form-control form-control-sm" placeholder="RTX 4070"></td>
      <td><input type="number" name="price[]" class="form-control form-control-sm font-weight-bold" placeholder="Giá bán" required></td>
      <td><input type="number" name="discount[]" class="form-control form-control-sm" value="0"></td>
      <td><input type="number" name="stock[]" class="form-control form-control-sm" value="10"></td>
      <td class="align-middle text-center">
        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">
          <i class="fas fa-trash-alt"></i>
        </button>
      </td>
    `;
    tableBody.appendChild(newRow);
    attachRemoveEvent();
  });

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