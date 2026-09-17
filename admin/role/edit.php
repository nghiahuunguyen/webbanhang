<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once('../../database/dbhelper.php');
require_once('../check_role.php');

requireAdmin();

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit();
}

$role = executeResult("SELECT * FROM `Role` WHERE id = $id LIMIT 1", true);
if (!$role) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role_name = trim($_POST['name'] ?? '');
    $selected_permissions = $_POST['permissions'] ?? [];

    if (empty($role_name)) {
        $_SESSION['error_msg'] = 'Tên vai trò không được để trống!';
        header('Location: edit.php?id=' . $id);
        exit();
    } else {
        $safe_name = str_replace("'", "\'", htmlspecialchars($role_name));
        execute("UPDATE `Role` SET `name` = '$safe_name' WHERE id = $id");

        // Không sửa danh sách quyền của Admin tối cao (luôn giữ toàn quyền)
        if ($id !== 1) {
            execute("DELETE FROM `Role_Permission` WHERE role_id = $id");
            if (!empty($selected_permissions)) {
                foreach ($selected_permissions as $pid) {
                    $perm_id = intval($pid);
                    execute("INSERT INTO `Role_Permission` (`role_id`, `permission_id`) VALUES ($id, $perm_id)");
                }
            }
        }

        $_SESSION['success_msg'] = "Cập nhật vai trò và phân quyền thành công!";
        header('Location: index.php');
        exit();
    }
}

// Lấy danh sách ID các quyền vai trò này đang có
$current_perms_raw = executeResult("SELECT permission_id FROM `Role_Permission` WHERE role_id = $id");
$current_perm_ids = !empty($current_perms_raw) ? array_column($current_perms_raw, 'permission_id') : [];

// Danh sách quyền hệ thống
$permissions_raw = executeResult("SELECT * FROM `Permission` ORDER BY group_name ASC, id ASC");
$perm_groups = [];
foreach ($permissions_raw as $p) {
    $perm_groups[$p['group_name']][] = $p;
}

$page_title = 'Chỉnh Sửa Quyền Hạn: ' . $role['name'];
include '../layout/head.php';
include '../layout/sidebar.php';
?>

<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-3">
  <?php include '../layout/topbar.php'; ?>

  <div class="custom-card bg-white rounded shadow-sm border mb-4">
    <div class="p-3 px-4 d-flex justify-content-between align-items-center border-bottom">
      <h5 class="font-weight-bold mb-0 text-dark">
        <i class="fas fa-edit text-warning mr-2"></i>Chỉnh Sửa Quyền: <?= htmlspecialchars($role['name']) ?>
      </h5>
      <a href="index.php" class="btn btn-sm btn-secondary font-weight-bold">
        <i class="fas fa-arrow-left mr-1"></i> Quay lại
      </a>
    </div>

    <form method="POST" class="p-4">
      <div class="form-group mb-4">
        <label class="font-weight-bold text-dark">Tên vai trò / Chức vụ <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control font-weight-bold" value="<?= htmlspecialchars($role['name']) ?>" required style="max-width: 480px;" <?= $id === 1 ? 'readonly' : '' ?>>
        <?php if ($id === 1): ?>
          <small class="text-muted">Vai trò Quản trị viên tối cao không được phép đổi tên và luôn giữ toàn bộ quyền.</small>
        <?php endif; ?>
      </div>

      <?php if ($id !== 1): ?>
        <div class="form-group mb-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <label class="font-weight-bold text-dark mb-0">Tích chọn các chức năng cho phép:</label>
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
                  
                  <?php foreach ($perms as $item): 
                    $is_checked = in_array((int)$item['id'], $current_perm_ids);
                  ?>
                    <div class="custom-control custom-checkbox my-2">
                      <input type="checkbox" 
                             class="custom-control-input perm-box" 
                             id="perm_<?= $item['id'] ?>" 
                             name="permissions[]" 
                             value="<?= $item['id'] ?>"
                             <?= $is_checked ? 'checked' : '' ?>>
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
      <?php endif; ?>

      <button type="submit" class="btn btn-warning px-4 font-weight-bold shadow-sm text-dark">
        <i class="fas fa-save mr-1"></i> Cập Nhật Vai Trò
      </button>
    </form>
  </div>
</main>

<script>
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

<?php include '../layout/scripts.php'; ?>