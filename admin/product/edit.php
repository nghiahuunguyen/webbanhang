<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once('../../database/dbhelper.php');
$page_title = 'Chỉnh Sửa Sản Phẩm';
$id = $_GET['id'] ?? '';
if (empty($id)) {
    header('Location: index.php');
    die();
}

// Lấy dữ liệu sản phẩm hiện tại
$product = executeResult("SELECT * FROM Product WHERE id = '$id'", true);
if (!$product) {
    header('Location: index.php');
    die();
}

// Lấy danh mục hợp lệ (deleted = 0)
$categories = executeResult("SELECT * FROM category WHERE deleted = 0 ORDER BY name ASC");

// Lấy danh sách ảnh phụ hiện tại từ bảng Galery
$galleries = executeResult("SELECT * FROM Galery WHERE product_id = '$id'");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $price       = intval($_POST['price'] ?? 0);
    $discount    = !empty($_POST['discount']) ? intval($_POST['discount']) : 0;
    $description = trim($_POST['description'] ?? '');

    if (empty($title)) {
        $error = 'Vui lòng nhập tên sản phẩm!';
    } elseif ($category_id <= 0) {
        $error = 'Vui lòng chọn danh mục sản phẩm!';
    } elseif ($price <= 0) {
        $error = 'Giá bán phải lớn hơn 0!';
    } elseif ($discount > 0 && $discount >= $price) {
        $error = 'Giá giảm phải nhỏ hơn giá bán gốc!';
    } else {
        $upload_dir = '../../img/product/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // 1. Xử lý ảnh đại diện (Thumbnail): nếu có chọn file mới thì thay thế, không thì giữ đường dẫn cũ
        $db_thumbnail = $product['thumbnail'];
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === 0) {
            $thumb_file = $_FILES['thumbnail'];
            $ext = pathinfo($thumb_file['name'], PATHINFO_EXTENSION);
            $thumb_name = time() . '_' . uniqid() . '.' . strtolower($ext);
            $thumb_target = $upload_dir . $thumb_name;

            if (move_uploaded_file($thumb_file['tmp_name'], $thumb_target)) {
                $db_thumbnail = 'img/product/' . $thumb_name;
            }
        }

        $safe_title       = str_replace("'", "\'", $title);
        $safe_thumbnail   = str_replace("'", "\'", $db_thumbnail);
        $safe_description = str_replace("'", "\'", $description);
        $updated_at       = date('Y-m-d H:i:s');

        // 2. Cập nhật bảng Product
        $sql = "UPDATE Product SET 
                    category_id = '$category_id',
                    title = '$safe_title',
                    price = '$price',
                    discount = '$discount',
                    thumbnail = '$safe_thumbnail',
                    description = '$safe_description',
                    updated_at = '$updated_at'
                WHERE id = '$id'";
        execute($sql);

        // 3. Tải thêm ảnh phụ vào Galery (nếu có chọn)
        if (isset($_FILES['galleries'])) {
            $total_files = count($_FILES['galleries']['name']);
            for ($i = 0; $i < $total_files; $i++) {
                if ($_FILES['galleries']['error'][$i] === 0) {
                    $gallery_ext = pathinfo($_FILES['galleries']['name'][$i], PATHINFO_EXTENSION);
                    $gallery_name = time() . '_' . uniqid() . '_' . $i . '.' . strtolower($gallery_ext);
                    $gallery_target = $upload_dir . $gallery_name;

                    if (move_uploaded_file($_FILES['galleries']['tmp_name'][$i], $gallery_target)) {
                        $db_gallery_path = 'img/product/' . $gallery_name;
                        execute("INSERT INTO Galery (product_id, thumbnail) VALUES ('$id', '$db_gallery_path')");
                    }
                }
            }
        }

        $_SESSION['success_msg'] = "Cập nhật sản phẩm thành công!";
        header('Location: index.php');
        die();
    }
}

// Giữ lại dữ liệu đang nhập nếu có lỗi, ngược lại dùng dữ liệu từ Database
$title_val       = $_POST['title'] ?? $product['title'];
$category_id_val = $_POST['category_id'] ?? $product['category_id'];
$price_val       = $_POST['price'] ?? $product['price'];
$discount_val    = isset($_POST['discount']) ? $_POST['discount'] : $product['discount'];
$description_val = $_POST['description'] ?? $product['description'];

include '../layout/head.php'; 
include '../layout/sidebar.php'; 
?>

<!-- TOAST THÔNG BÁO LỖI GÓC PHẢI TRÊN (TỰ BIẾN MẤT SAU 3S) -->
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

  <div class="custom-card">
    <div class="p-3 px-4 d-flex justify-content-between align-items-center border-bottom">
      <h5 class="font-weight-bold mb-0 text-dark">Chỉnh Sửa Sản Phẩm #PRD-<?= htmlspecialchars($product['id']) ?></h5>
      <a href="index.php" class="btn btn-sm btn-secondary font-weight-bold">
        <i class="fas fa-arrow-left mr-1"></i> Quay lại
      </a>
    </div>

    <form class="p-4" method="POST" enctype="multipart/form-data" novalidate>
      <div class="form-row">
        <div class="form-group col-md-8">
          <label class="font-weight-bold">Tên sản phẩm <span class="text-danger">*</span></label>
          <input type="text" 
                 name="title" 
                 class="form-control <?= (!empty($error) && empty($title_val)) ? 'is-invalid' : '' ?>" 
                 value="<?= htmlspecialchars($title_val) ?>" 
                 required autofocus>
        </div>
        <div class="form-group col-md-4">
          <label class="font-weight-bold">Danh mục <span class="text-danger">*</span></label>
          <select name="category_id" class="form-control <?= (!empty($error) && empty($category_id_val)) ? 'is-invalid' : '' ?>" required>
            <option value="">-- Chọn danh mục --</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= ($category_id_val == $cat['id']) ? 'selected' : '' ?>>
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
                 class="form-control <?= (!empty($error) && $price_val <= 0) ? 'is-invalid' : '' ?>" 
                 value="<?= htmlspecialchars($price_val) ?>" 
                 required>
        </div>
        <div class="form-group col-md-6">
          <label class="font-weight-bold">Giá khuyến mãi (VNĐ)</label>
          <input type="number" 
                 name="discount" 
                 class="form-control" 
                 value="<?= htmlspecialchars($discount_val ?: '') ?>">
        </div>
      </div>

      <!-- Ảnh đại diện -->
      <div class="form-group">
        <label class="font-weight-bold">Ảnh đại diện (Thumbnail)</label>
        <div class="d-flex align-items-center mb-2">
          <?php 
            $cur_thumb = '../../' . $product['thumbnail'];
            $thumb_src = (!empty($product['thumbnail']) && file_exists($cur_thumb)) ? $cur_thumb : 'https://via.placeholder.com/80x80?text=No+Image';
          ?>
          <img src="<?= $thumb_src ?>" class="rounded border p-1 mr-3" width="70" height="70" style="object-fit: cover;">
          <div class="flex-grow-1">
            <input type="file" name="thumbnail" class="form-control-file border p-1 rounded" accept="image/*">
            <small class="text-muted">Bỏ trống nếu muốn giữ nguyên ảnh hiện tại.</small>
          </div>
        </div>
      </div>

      <!-- Ảnh thư viện hiện có và tải thêm -->
      <div class="form-group">
        <label class="font-weight-bold">Thư viện ảnh phụ</label>
        <?php if (!empty($galleries)): ?>
          <div class="d-flex flex-wrap mb-2" style="gap: 8px;">
            <?php foreach ($galleries as $gal): 
              $gal_img = '../../' . $gal['thumbnail'];
              $gal_src = (!empty($gal['thumbnail']) && file_exists($gal_img)) ? $gal_img : 'https://via.placeholder.com/50x50?text=No+Image';
            ?>
              <img src="<?= $gal_src ?>" class="rounded border" width="55" height="55" style="object-fit: cover;">
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <input type="file" name="galleries[]" class="form-control-file border p-1 rounded" accept="image/*" multiple>
        <small class="text-muted">Tải thêm ảnh vào bộ sưu tập (giữ Ctrl để chọn nhiều ảnh).</small>
      </div>

      <div class="form-group">
        <label class="font-weight-bold">Mô tả sản phẩm</label>
        <textarea name="description" rows="4" class="form-control"><?= htmlspecialchars($description_val) ?></textarea>
      </div>

      <button type="submit" class="btn btn-warning px-4 font-weight-bold text-white">
        <i class="fas fa-save mr-1"></i> Lưu Thay Đổi
      </button>
    </form>
  </div>
</main>

<?php include '../layout/scripts.php'; ?>

<!-- SCRIPT TỰ ĐỘNG BIẾN MẤT THÔNG BÁO SAU 3 GIÂY -->
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