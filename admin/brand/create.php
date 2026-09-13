<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once('../../database/dbhelper.php');

$error = '';
$name = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');

    if (empty($name)) {
        $error = 'Vui lòng nhập tên thương hiệu / hãng!';
    } elseif (mb_strlen($name, 'UTF-8') < 2) {
        $error = 'Tên thương hiệu phải có ít nhất 2 ký tự!';
    } else {
        $safe_name = str_replace("'", "\'", $name);
        
        // Kiểm tra trùng tên hãng
        $check = executeResult("SELECT id FROM Brand WHERE name = '$safe_name' AND deleted = 0", true);
        if ($check != null) {
            $error = 'Tên thương hiệu này đã tồn tại!';
        } else {
            $db_logo = null;

            // Xử lý upload ảnh Logo (nếu có chọn file)
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
                $upload_dir = '../../img/brand/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file = $_FILES['logo'];
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $logo_name = time() . '_' . uniqid() . '.' . strtolower($ext);
                $logo_target = $upload_dir . $logo_name;

                if (move_uploaded_file($file['tmp_name'], $logo_target)) {
                    $db_logo = 'img/brand/' . $logo_name;
                }
            }

            $safe_logo = $db_logo ? "'" . str_replace("'", "\'", $db_logo) . "'" : "NULL";
            $sql = "INSERT INTO Brand (name, logo, deleted) VALUES ('$safe_name', $safe_logo, 0)";
            execute($sql);

            $_SESSION['success_msg'] = "Thêm thương hiệu mới thành công!";
            header('Location: index.php');
            die();
        }
    }
}

$page_title = 'Thêm Mới Thương Hiệu';
include '../layout/head.php'; 
include '../layout/sidebar.php'; 
?>

<!-- TOAST THÔNG BÁO LỖI GÓC PHẢI TRÊN (TỰ ẨN SAU 3 GIÂY) -->
<?php if (!empty($error)): ?>
  <div id="toast-wrapper" style="position: fixed; top: 20px; right: 20px; z-index: 99999; min-width: 300px; transition: all 0.5s ease;">
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
      <h5 class="font-weight-bold mb-0 text-dark">Thêm Mới Thương Hiệu / Hãng</h5>
      <a href="index.php" class="btn btn-sm btn-secondary font-weight-bold">
        <i class="fas fa-arrow-left mr-1"></i> Quay lại
      </a>
    </div>

    <form class="p-4" method="POST" enctype="multipart/form-data" novalidate>
      <div class="form-group mb-3">
        <label class="font-weight-bold">Tên thương hiệu <span class="text-danger">*</span></label>
        <input type="text" 
               name="name" 
               class="form-control <?= !empty($error) ? 'is-invalid' : '' ?>" 
               value="<?= htmlspecialchars($name) ?>" 
               placeholder="Nhập tên hãng (VD: Apple, ASUS, Dell, Samsung...)" 
               autofocus 
               required>
      </div>

      <div class="form-group mb-4">
        <label class="font-weight-bold">Logo thương hiệu</label>
        <input type="file" 
               name="logo" 
               class="form-control-file border p-1 rounded" 
               accept="image/*">
        <small class="text-muted">Chọn file hình ảnh logo (.png, .jpg, .webp, .svg).</small>
      </div>

      <button type="submit" class="btn btn-primary px-4 font-weight-bold">
        <i class="fas fa-plus mr-1"></i> Lưu thương hiệu
      </button>
    </form>
  </div>
</main>

<?php include '../layout/scripts.php'; ?>

<!-- SCRIPT ĐẾM 3 GIÂY TỰ ĐỘNG BIẾN MẤT -->
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