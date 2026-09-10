<?php 
session_start();
require_once('../../database/dbhelper.php');

$id = $_GET['id'] ?? '';
if (empty($id)) {
    header('Location: index.php');
    die();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');

    if (empty($name)) {
        $error = 'Vui lòng nhập tên loại sản phẩm!';
    } elseif (mb_strlen($name, 'UTF-8') < 2) {
        $error = 'Tên danh mục phải có ít nhất 2 ký tự!';
    } else {
        $safe_name = str_replace("'", "\'", $name);

        // Kiểm tra xem tên mới có bị trùng với danh mục khác không (loại trừ chính nó)
        $check = executeResult("SELECT id FROM category WHERE name = '$safe_name' AND id != '$id'", true);
        if ($check != null) {
            $error = 'Tên danh mục này đã tồn tại!';
        } else {
            $sql = "UPDATE category SET name = '$safe_name' WHERE id = '$id'";
            execute($sql);

            $_SESSION['success_msg'] = "Cập nhật danh mục thành công!";
            header('Location: index.php');
            die();
        }
    }
}

// Lấy thông tin danh mục từ database
$category = executeResult("SELECT * FROM category WHERE id = '$id'", true);
if (!$category) {
    header('Location: index.php');
    die();
}

// Nếu có lỗi nhập thì giữ lại tên vừa nhập, còn không thì dùng tên từ CSDL
$current_name = isset($_POST['name']) ? $_POST['name'] : $category['name'];
$page_title = 'Chỉnh Sửa Danh Mục';
include '../layout/head.php'; 
include '../layout/sidebar.php'; 
?>

<!-- TOAST THÔNG BÁO LỖI GÓC PHẢI TRÊN (TỰ ẨN SAU 3 GIÂY) -->
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
      <h5 class="font-weight-bold mb-0 text-dark">Chỉnh Sửa Loại / Danh Mục</h5>
      <a href="index.php" class="btn btn-sm btn-secondary font-weight-bold">
        <i class="fas fa-arrow-left mr-1"></i> Quay lại
      </a>
    </div>

    <form class="p-4" method="POST" novalidate>
      <div class="form-group">
        <label class="font-weight-bold">Tên loại sản phẩm</label>
        <input type="text" 
               name="name" 
               class="form-control <?= !empty($error) ? 'is-invalid' : '' ?>" 
               value="<?php echo htmlspecialchars($current_name); ?>" 
               required 
               autofocus>
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