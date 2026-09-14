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
$page_title = 'Chỉnh Sửa Banner';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    die();
}

$banner = executeResult("SELECT * FROM `Banner` WHERE id = $id LIMIT 1", true);
if (!$banner) {
    header('Location: index.php');
    die();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sort_order = intval($_POST['sort_order'] ?? 0);
    $is_active  = isset($_POST['is_active']) ? 1 : 0;
    $manual_url = trim($_POST['image_url_manual'] ?? '');
    $db_image_url = $banner['image_url'];

    // 1. Tải ảnh mới lên thay thế nếu có chọn file
    if (isset($_FILES['banner_file']) && $_FILES['banner_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../img/banner/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_tmp  = $_FILES['banner_file']['tmp_name'];
        $file_name = $_FILES['banner_file']['name'];
        $ext       = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed   = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (in_array($ext, $allowed)) {
            $new_name = 'banner_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $target   = $upload_dir . $new_name;

            if (move_uploaded_file($file_tmp, $target)) {
                // Xóa file ảnh cũ nếu tồn tại trong thư mục máy chủ
                if (!empty($banner['image_url'])) {
                    $old_file = '../../' . $banner['image_url'];
                    if (file_exists($old_file) && !is_dir($old_file)) {
                        unlink($old_file);
                    }
                }
                $db_image_url = 'img/banner/' . $new_name;
            } else {
                $error = 'Không thể lưu file ảnh vào thư mục img/banner/!';
            }
        } else {
            $error = 'Chỉ chấp nhận các định dạng ảnh: JPG, JPEG, PNG, WEBP, GIF!';
        }
    } elseif (!empty($manual_url)) {
        $db_image_url = $manual_url;
    }

    if (empty($error)) {
        $safe_url = str_replace("'", "\'", $db_image_url);
        $sql = "UPDATE `Banner` 
                SET image_url = '$safe_url', 
                    sort_order = '$sort_order', 
                    is_active = '$is_active' 
                WHERE id = $id";
        execute($sql);

        $_SESSION['success_msg'] = "Cập nhật banner thành công!";
        header('Location: index.php');
        die();
    }
}

$img_display = (strpos($banner['image_url'], 'http') === 0) ? $banner['image_url'] : '../../' . $banner['image_url'];

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

<!-- MAIN CONTENT -->
<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-3">
  <?php include '../layout/topbar.php'; ?>

  <div class="custom-card mb-4">
    <div class="p-3 px-4 d-flex justify-content-between align-items-center border-bottom">
      <div>
        <h5 class="font-weight-bold mb-0 text-dark">Chỉnh Sửa Banner #BN-<?= $banner['id'] ?></h5>
      </div>
      <a href="index.php" class="btn btn-sm btn-secondary font-weight-bold">
        <i class="fas fa-arrow-left mr-1"></i> Quay lại
      </a>
    </div>

    <form class="p-4" method="POST" enctype="multipart/form-data" novalidate>
      <div class="row">
        <!-- Cột hiển thị ảnh hiện tại -->
        <div class="col-lg-5 text-center mb-4 mb-lg-0">
          <label class="font-weight-bold small text-muted d-block text-left mb-2">HÌNH ẢNH BANNER HIỆN TẠI</label>
          <div class="border rounded p-2 bg-white shadow-sm d-inline-block w-100">
            <img id="bannerPreview" 
                 src="<?= htmlspecialchars($img_display) ?>" 
                 alt="Banner <?= $banner['id'] ?>" 
                 class="img-fluid rounded" 
                 style="max-height: 220px; width: 100%; object-fit: cover;">
          </div>
          <small class="text-muted d-block mt-2 font-italic">Đường dẫn: <?= htmlspecialchars($banner['image_url']) ?></small>
        </div>

        <!-- Cột form sửa -->
        <div class="col-lg-7">
          <div class="form-group mb-3">
            <label class="font-weight-bold">Tải ảnh mới thay thế (Nếu muốn đổi ảnh)</label>
            <input type="file" name="banner_file" class="form-control-file border p-2 rounded bg-light" accept="image/*" onchange="previewImage(this)">
            <small class="text-muted">Bỏ trống nếu muốn giữ nguyên ảnh hiện tại.</small>
          </div>

          <div class="form-group mb-3">
            <label class="font-weight-bold">Hoặc chỉnh sửa lại đường dẫn ảnh (URL)</label>
            <input type="text" name="image_url_manual" class="form-control" value="<?= htmlspecialchars($banner['image_url']) ?>" oninput="previewManualUrl(this.value)">
          </div>

          <div class="form-row">
            <div class="form-group col-md-6 mb-3">
              <label class="font-weight-bold">Thứ tự ưu tiên hiển thị</label>
              <input type="number" name="sort_order" class="form-control" value="<?= $banner['sort_order'] ?>" min="0">
            </div>

            <div class="form-group col-md-6 mb-3 d-flex align-items-center pt-md-3">
              <div class="custom-control custom-checkbox mt-2">
                <input type="checkbox" class="custom-control-input" id="isActiveCheck" name="is_active" <?= ($banner['is_active'] == 1) ? 'checked' : '' ?>>
                <label class="custom-control-label font-weight-bold text-dark" for="isActiveCheck">
                  Kích hoạt hiển thị trên trang chủ
                </label>
              </div>
            </div>
          </div>

          <hr class="my-3">

          <button type="submit" class="btn btn-warning px-4 font-weight-bold text-white shadow-sm">
            <i class="fas fa-save mr-1"></i> Cập Nhật Banner
          </button>
        </div>
      </div>
    </form>
  </div>
</main>

<?php include '../layout/scripts.php'; ?>

<script>
  function previewImage(input) {
    if (input.files && input.files[0]) {
      var reader = new FileReader();
      reader.onload = function(e) {
        document.getElementById('bannerPreview').src = e.target.result;
      }
      reader.readAsDataURL(input.files[0]);
    }
  }

  function previewManualUrl(url) {
    if (url.trim() !== '') {
      document.getElementById('bannerPreview').src = url;
    }
  }

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