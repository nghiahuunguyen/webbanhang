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

$id = $_GET['id'] ?? '';
if (empty($id)) {
    header('Location: index.php');
    die();
}

$id = intval($id);
$category = executeResult("SELECT * FROM Category WHERE id = $id AND deleted = 0", true);
if (!$category) {
    header('Location: index.php');
    die();
}

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
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');

    if (empty($name)) {
        $error = 'Vui lòng nhập tên loại sản phẩm!';
    } elseif (mb_strlen($name, 'UTF-8') < 2) {
        $error = 'Tên danh mục phải có ít nhất 2 ký tự!';
    } else {
        $slug = empty($slug) ? createSlug($name) : createSlug($slug);
        $safe_name = str_replace("'", "\'", $name);
        $safe_slug = str_replace("'", "\'", $slug);

        // Kiểm tra xem tên hoặc slug mới có trùng với danh mục khác không
        $check_name = executeResult("SELECT id FROM Category WHERE name = '$safe_name' AND id != $id AND deleted = 0", true);
        $check_slug = executeResult("SELECT id FROM Category WHERE slug = '$safe_slug' AND id != $id AND deleted = 0", true);

        if ($check_name != null) {
            $error = 'Tên danh mục này đã tồn tại!';
        } elseif ($check_slug != null) {
            $error = 'Đường dẫn (Slug) này đã tồn tại ở danh mục khác!';
        } else {
            $sql = "UPDATE Category SET name = '$safe_name', slug = '$safe_slug' WHERE id = $id";
            execute($sql);

            $_SESSION['success_msg'] = "Cập nhật danh mục thành công!";
            header('Location: index.php');
            die();
        }
    }
}

$current_name = isset($_POST['name']) ? $_POST['name'] : $category['name'];
$current_slug = isset($_POST['slug']) ? $_POST['slug'] : ($category['slug'] ?? '');
$page_title = 'Chỉnh Sửa Danh Mục';
include '../layout/head.php';  
include '../layout/sidebar.php'; 
?>

<!-- TOAST THÔNG BÁO LỖI GÓC PHẢI TRÊN -->
<?php if (!empty($error)): ?>
  <div id="toast-wrapper" style="position: fixed; top: 20px; right: 20px; z-index: 99999; min-width: 300px; transition: all 0.5s ease;">
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
      <h5 class="font-weight-bold mb-0 text-dark">Chỉnh Sửa Loại / Danh Mục #<?= $category['id'] ?></h5>
      <a href="index.php" class="btn btn-sm btn-secondary font-weight-bold">
        <i class="fas fa-arrow-left mr-1"></i> Quay lại
      </a>
    </div>

    <form class="p-4" method="POST" novalidate>
      <div class="form-group mb-3">
        <label class="font-weight-bold">Tên loại sản phẩm <span class="text-danger">*</span></label>
        <input type="text" 
               name="name" 
               class="form-control <?= !empty($error) ? 'is-invalid' : '' ?>" 
               value="<?= htmlspecialchars($current_name) ?>" 
               required 
               autofocus>
      </div>

      <div class="form-group mb-4">
        <label class="font-weight-bold">Đường dẫn thân thiện (Slug)</label>
        <input type="text" 
               name="slug" 
               class="form-control" 
               value="<?= htmlspecialchars($current_slug) ?>">
        <small class="text-muted">Đường dẫn dùng cho SEO ngoài giao diện web.</small>
      </div>

      <button type="submit" class="btn btn-warning px-4 font-weight-bold text-white">
        <i class="fas fa-save mr-1"></i> Lưu Thay Đổi
      </button>
    </form>
  </div>
</main>

<?php include '../layout/scripts.php'; ?>

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