<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once('../../database/dbhelper.php');
require_once('../check_role.php');

requireAdmin();

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role_name = trim($_POST['name'] ?? '');
    $selected_permissions = $_POST['permissions'] ?? [];

    if (empty($role_name)) {
        $error_msg = 'Vui lòng nhập tên vai trò / chức vụ!';
    } else {
        $safe_name = str_replace("'", "\'", htmlspecialchars($role_name));
        
        // 1. Thêm vai trò mới vào bảng Role
        execute("INSERT INTO `Role` (`name`) VALUES ('$safe_name')");
        $new_role_id = executeResult("SELECT LAST_INSERT_ID() AS id", true)['id'];

        // 2. Lưu quyền được chọn vào bảng Role_Permission
        if (!empty($selected_permissions)) {
            foreach ($selected_permissions as $pid) {
                $perm_id = intval($pid);
                execute("INSERT INTO `Role_Permission` (`role_id`, `permission_id`) VALUES ($new_role_id, $perm_id)");
            }
        }

        $_SESSION['success_msg'] = "Đã tạo vai trò \"$role_name\" và phân quyền thành công!";
        header('Location: index.php');
        exit();
    }
}

// Lấy danh sách quyền theo từng nhóm
$permissions_raw = executeResult("SELECT * FROM `Permission` ORDER BY group_name ASC, id ASC");
$perm_groups = [];
foreach ($permissions_raw as $p) {
    $perm_groups[$p['group_name']][] = $p;
}

$page_title = 'Tạo Vai Trò Mới';
include '../layout/head.php';
include '../layout/sidebar.php';
?>

<!-- TOAST THÔNG BÁO GÓC PHẢI TRÊN (CHUẨN PRODUCT) -->
<div id="dynamic-toast-wrapper" style="position: fixed; top: 20px; right: 20px; z-index: 99999; min-width: 320px; display: none; transition: all 0.4s ease;">
  <div class="toast show shadow-lg border-0" role="alert">
    <div class="toast-header bg-success text-white" id="toastHeader">
      <i class="fas fa-check-circle mr-2" id="toastIcon"></i>
      <strong class="mr-auto" id="toastTitle">Thông báo</strong>
      <small class="text-white">Vừa xong</small>
      <button type="button" class="ml-2 mb-1 close text-white" onclick="document.getElementById('dynamic-toast-wrapper').style.display='none'" style="outline: none;">
        <span>&times;</span>
      </button>
    </div>
    <div class="toast-body bg-white text-dark font-weight-bold" id="toastBody"></div>
  </div>
</div>

<?php if (!empty($error_msg)): ?>
  <script>
    window.addEventListener('DOMContentLoaded', function() {
      showToast('<?= addslashes($error_msg) ?>', false);
    });
  </script>
<?php endif; ?>

<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-3">
  <?php include '../layout/topbar.php'; ?>

  <div class="custom-card bg-white rounded shadow-sm border mb-4">
    <div class="p-3 px-4 d-flex justify-content-between align-items-center border-bottom">
      <h5 class="font-weight-bold mb-0 text-dark">
        <i class="fas fa-plus-circle text-primary mr-2"></i>Thêm Mới Vai Trò & Tích Chọn Quyền
      </h5>
      <a href="index.php" class="btn btn-sm btn-secondary font-weight-bold">
        <i class="fas fa-arrow-left mr-1"></i> Quay lại
      </a>
    </div>

    <form method="POST" class="p-4">
      <!-- Tên vai trò -->
      <div class="form-group mb-4">
        <label class="font-weight-bold text-dark">Tên vai trò / Chức danh mới <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control font-weight-bold" placeholder="Ví dụ: Thủ kho, Nhân viên CSKH, Kế toán..." required style="max-width: 480px;">
      </div>

      <!-- Danh sách Checkbox chọn quyền theo từng nhóm -->
      <div class="form-group mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <label class="font-weight-bold text-dark mb-0">Tích chọn các chức năng được cấp quyền:</label>
          <button type="button" class="btn btn-sm btn-outline-primary font-weight-bold" onclick="toggleSelectAll(this)">Chọn tất cả</button>
        </div>

        <div class="row">
          <?php foreach ($perm_groups as $group_name => $perms): ?>
            <div class="col-md-6 mb-3">
              <div class="border rounded p-3 bg-light h-100">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                  <h6 class="font-weight-bold text-primary mb-0">
                    <i class="fas fa-folder-open mr-1"></i> <?= htmlspecialchars($group_name) ?>
                  </h6>
                  <button type="button" class="btn btn-xs btn-link p-0 text-decoration-none font-weight-bold" onclick="toggleGroup(this)">Chọn nhóm này</button>
                </div>
                
                <?php foreach ($perms as $item): ?>
                  <div class="custom-control custom-checkbox my-2">
                    <input type="checkbox" 
                           class="custom-control-input perm-box" 
                           id="perm_<?= $item['id'] ?>" 
                           name="permissions[]" 
                           value="<?= $item['id'] ?>">
                    <label class="custom-control-label text-dark font-weight-medium" for="perm_<?= $item['id'] ?>">
                      <?= htmlspecialchars($item['name']) ?>
                      <code class="ml-1 text-muted">(<?= htmlspecialchars($item['code']) ?>)</code>
                    </label>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <button type="submit" class="btn btn-primary px-4 font-weight-bold shadow-sm">
        <i class="fas fa-save mr-1"></i> Lưu Vai Trò
      </button>
    </form>
  </div>
</main>

<?php include '../layout/scripts.php'; ?>

<script>
  function showToast(msg, isSuccess = true) {
    var wrapper = document.getElementById('dynamic-toast-wrapper');
    var header  = document.getElementById('toastHeader');
    var title   = document.getElementById('toastTitle');
    var body    = document.getElementById('toastBody');
    var icon    = document.getElementById('toastIcon');

    if (isSuccess) {
      header.className = 'toast-header bg-success text-white';
      icon.className = 'fas fa-check-circle mr-2';
      title.textContent = 'Thành công';
    } else {
      header.className = 'toast-header bg-danger text-white';
      icon.className = 'fas fa-exclamation-circle mr-2';
      title.textContent = 'Lỗi nhập liệu';
    }

    body.textContent = msg;
    wrapper.style.display = 'block';

    setTimeout(function() {
      wrapper.style.display = 'none';
    }, 3000);
  }

  function toggleSelectAll(btn) {
    const boxes = document.querySelectorAll('.perm-box');
    const isAllChecked = Array.from(boxes).every(b => b.checked);
    boxes.forEach(b => b.checked = !isAllChecked);
    btn.textContent = isAllChecked ? 'Chọn tất cả' : 'Bỏ chọn tất cả';
  }

  function toggleGroup(btn) {
    const parent = btn.closest('.border');
    const groupBoxes = parent.querySelectorAll('.perm-box');
    const isAllChecked = Array.from(groupBoxes).every(b => b.checked);
    groupBoxes.forEach(b => b.checked = !isAllChecked);
    btn.textContent = isAllChecked ? 'Chọn nhóm này' : 'Bỏ chọn nhóm';
  }
</script>