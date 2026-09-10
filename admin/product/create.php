<?php 
// Thiết lập múi giờ Việt Nam (GMT+7)
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once('../../database/dbhelper.php');
$page_title = 'Thêm Mới Sản Phẩm';
$error = '';
$title = '';
$category_id = '';
$price = '';
$discount = '';
$description = '';

// Lấy danh mục sản phẩm (deleted = 0)
$categories = executeResult("SELECT * FROM category WHERE deleted = 0 ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title         = trim($_POST['title'] ?? '');
    $category_id   = intval($_POST['category_id'] ?? 0);
    $price         = intval($_POST['price'] ?? 0);
    $discount      = !empty($_POST['discount']) ? intval($_POST['discount']) : 0;
    $description   = trim($_POST['description'] ?? '');

    // Kiểm tra tính hợp lệ cơ bản
    if (empty($title)) {
        $error = 'Vui lòng nhập tên sản phẩm!';
    } elseif ($category_id <= 0) {
        $error = 'Vui lòng chọn danh mục sản phẩm!';
    } elseif ($price <= 0) {
        $error = 'Giá bán phải lớn hơn 0!';
    } elseif ($discount > 0 && $discount >= $price) {
        $error = 'Giá giảm phải nhỏ hơn giá bán gốc!';
    } elseif (!isset($_FILES['thumbnail']) || $_FILES['thumbnail']['error'] !== 0) {
        $error = 'Vui lòng chọn file ảnh đại diện!';
    } else {
        // Đường dẫn thư mục lưu ảnh: webbanhang/img/product/
        $upload_dir = '../../img/product/';
        
        // Tạo thư mục nếu chưa tồn tại
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // 1. Xử lý lưu Ảnh đại diện (Thumbnail)
        $thumb_file = $_FILES['thumbnail'];
        $ext = pathinfo($thumb_file['name'], PATHINFO_EXTENSION);
        // Đặt tên file ngẫu nhiên theo thời gian để tránh trùng tên ảnh
        $thumb_name = time() . '_' . uniqid() . '.' . strtolower($ext);
        $thumb_target = $upload_dir . $thumb_name;

        if (move_uploaded_file($thumb_file['tmp_name'], $thumb_target)) {
            // Đường dẫn ảnh lưu vào Database (để hiển thị ngoài web)
            $db_thumbnail = 'img/product/' . $thumb_name;
            
            $safe_title       = str_replace("'", "\'", $title);
            $safe_thumbnail   = str_replace("'", "\'", $db_thumbnail);
            $safe_description = str_replace("'", "\'", $description);
            $created_at       = date('Y-m-d H:i:s');
            $updated_at       = date('Y-m-d H:i:s');

            // 2. Thêm vào bảng Product
            $sql_product = "INSERT INTO Product (category_id, title, price, discount, thumbnail, description, created_at, updated_at, deleted) 
                            VALUES ('$category_id', '$safe_title', '$price', '$discount', '$safe_thumbnail', '$safe_description', '$created_at', '$updated_at', 0)";
            execute($sql_product);

            // Lấy ID sản phẩm vừa tạo
            $last_product = executeResult("SELECT id FROM Product ORDER BY id DESC LIMIT 1", true);
            $product_id = $last_product['id'] ?? 0;

            // 3. Xử lý lưu danh sách Ảnh phụ (Gallery)
            if ($product_id > 0 && isset($_FILES['galleries'])) {
                $total_files = count($_FILES['galleries']['name']);
                
                for ($i = 0; $i < $total_files; $i++) {
                    if ($_FILES['galleries']['error'][$i] === 0) {
                        $gallery_ext = pathinfo($_FILES['galleries']['name'][$i], PATHINFO_EXTENSION);
                        $gallery_name = time() . '_' . uniqid() . '_' . $i . '.' . strtolower($gallery_ext);
                        $gallery_target = $upload_dir . $gallery_name;

                        if (move_uploaded_file($_FILES['galleries']['tmp_name'][$i], $gallery_target)) {
                            $db_gallery_path = 'img/product/' . $gallery_name;
                            $sql_gallery = "INSERT INTO Galery (product_id, thumbnail) VALUES ('$product_id', '$db_gallery_path')";
                            execute($sql_gallery);
                        }
                    }
                }
            }

            $_SESSION['success_msg'] = "Thêm sản phẩm mới thành công!";
            header('Location: index.php');
            die();
        } else {
            $error = 'Tải ảnh đại diện lên máy chủ thất bại!';
        }
    }
}

include '../layout/head.php'; 
include '../layout/sidebar.php'; 
?>

<!-- TOAST THÔNG BÁO LỖI GÓC PHẢI TRÊN (TỰ ẨN 3S) -->
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

<!-- MAIN CONTENT -->
<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-3">
  <?php include '../layout/topbar.php'; ?>

  <div class="custom-card">
    <div class="p-3 px-4 d-flex justify-content-between align-items-center border-bottom">
      <h5 class="font-weight-bold mb-0 text-dark">Thêm Mới Sản Phẩm</h5>
      <a href="index.php" class="btn btn-sm btn-secondary font-weight-bold">
        <i class="fas fa-arrow-left mr-1"></i> Quay lại
      </a>
    </div>

    <!-- Quan trọng: bắt buộc có enctype="multipart/form-data" -->
    <form class="p-4" method="POST" enctype="multipart/form-data" novalidate>
      <div class="form-row">
        <div class="form-group col-md-8">
          <label class="font-weight-bold">Tên sản phẩm <span class="text-danger">*</span></label>
          <input type="text" 
                 name="title" 
                 class="form-control <?= (!empty($error) && empty($title)) ? 'is-invalid' : '' ?>" 
                 value="<?= htmlspecialchars($title) ?>" 
                 placeholder="Nhập tên sản phẩm..." 
                 required autofocus>
        </div>
        <div class="form-group col-md-4">
          <label class="font-weight-bold">Danh mục <span class="text-danger">*</span></label>
          <select name="category_id" class="form-control <?= (!empty($error) && empty($category_id)) ? 'is-invalid' : '' ?>" required>
            <option value="">-- Chọn danh mục --</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= ($category_id == $cat['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-6">
          <label class="font-weight-bold">Giá bán gốc (VNĐ) <span class="text-danger">*</span></label>
          <input type="number" 
                 name="price" 
                 class="form-control <?= (!empty($error) && $price <= 0) ? 'is-invalid' : '' ?>" 
                 value="<?= htmlspecialchars($price) ?>" 
                 placeholder="VD: 500000" 
                 required>
        </div>
        <div class="form-group col-md-6">
          <label class="font-weight-bold">Giá khuyến mãi (VNĐ)</label>
          <input type="number" 
                 name="discount" 
                 class="form-control" 
                 value="<?= htmlspecialchars($discount ?: '') ?>" 
                 placeholder="VD: 450000">
        </div>
      </div>

      <!-- Chọn file Ảnh đại diện -->
      <div class="form-group">
        <label class="font-weight-bold">Ảnh đại diện (Thumbnail) <span class="text-danger">*</span></label>
        <input type="file" 
               name="thumbnail" 
               class="form-control-file border p-1 rounded" 
               accept="image/*" 
               required>
      </div>

      <!-- Chọn nhiều file Ảnh phụ -->
      <div class="form-group">
        <label class="font-weight-bold">Thư viện ảnh bổ sung (Chọn nhiều ảnh cùng lúc)</label>
        <input type="file" 
               name="galleries[]" 
               class="form-control-file border p-1 rounded" 
               accept="image/*" 
               multiple>
        <small class="text-muted">Nhấn giữ phím Ctrl hoặc kéo chọn nhiều ảnh để tải lên cùng lúc.</small>
      </div>

      <div class="form-group">
        <label class="font-weight-bold">Mô tả sản phẩm</label>
        <textarea name="description" rows="4" class="form-control" placeholder="Mô tả thông số, đặc điểm chi tiết..."><?= htmlspecialchars($description) ?></textarea>
      </div>

      <button type="submit" class="btn btn-primary px-4 font-weight-bold">
        <i class="fas fa-plus mr-1"></i> Lưu sản phẩm
      </button>
    </form>
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