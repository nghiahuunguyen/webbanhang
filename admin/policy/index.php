<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once('../../database/dbhelper.php');
require_once('../check_role.php');

// Chỉ cho phép Admin (role_id = 1) truy cập
if (!isset($_SESSION['user']) || (int)$_SESSION['user']['role_id'] !== 1) {
    header('Location: ../dashboard/index.php');
    die();
}

// Xử lý Bật/Tắt hiển thị nhanh
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $cur = executeResult("SELECT is_active FROM `Policy` WHERE id = $id LIMIT 1", true);
        if ($cur) {
            $new_status = ($cur['is_active'] == 1) ? 0 : 1;
            execute("UPDATE `Policy` SET is_active = $new_status WHERE id = $id");
            $_SESSION['policy_msg'] = "Cập nhật trạng thái hiển thị thành công!";
        }
    }
    header('Location: index.php');
    die();
}

// Xử lý xóa bài viết chính sách
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        execute("DELETE FROM `Policy` WHERE id = $id");
        $_SESSION['policy_msg'] = "Đã xóa bài viết chính sách!";
    }
    header('Location: index.php');
    die();
}

$policies = executeResult("SELECT * FROM `Policy` ORDER BY `group_type` ASC, `sort_order` ASC, `id` ASC");
$msg = $_SESSION['policy_msg'] ?? '';
unset($_SESSION['policy_msg']);

$page_title = 'Quản Lý Chính Sách & Hỗ Trợ';
include '../layout/head.php';
include '../layout/sidebar.php';
?>

<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-3">
  <?php include '../layout/topbar.php'; ?>

  <!-- TOAST THÔNG BÁO GÓC PHẢI -->
  <?php if (!empty($msg)): ?>
    <div id="toast-wrapper" style="position: fixed; top: 25px; right: 25px; z-index: 99999; min-width: 320px; transition: all 0.4s ease;">
      <div class="card border-0 shadow-lg" style="border-radius: 12px; border-left: 5px solid #10b981 !important;">
        <div class="card-body p-3 bg-white text-dark d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center">
            <span class="text-success mr-2 font-weight-bold" style="font-size: 1.25rem;"><i class="fas fa-check-circle"></i></span>
            <div>
              <strong class="d-block text-dark font-weight-bold" style="font-size: 0.9rem;">Thành công</strong>
              <span class="small text-muted"><?= htmlspecialchars($msg) ?></span>
            </div>
          </div>
          <button type="button" class="close text-muted ml-2" onclick="document.getElementById('toast-wrapper').remove()">&times;</button>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="card border-0 shadow-sm rounded-lg bg-white">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
      <div>
        <h5 class="font-weight-bold mb-0 text-dark">Quản Lý Điều Khoản & Chính Sách</h5>
        <small class="text-muted">Các bài viết sẽ hiển thị liên kết trực tiếp ở chân trang (Footer)</small>
      </div>
      <a href="create.php" class="btn btn-primary btn-sm font-weight-bold px-3 py-2" style="border-radius: 8px;">
        <i class="fas fa-plus mr-1"></i> Thêm Bài Viết Mới
      </a>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="thead-light">
            <tr>
              <th width="60" class="text-center">STT</th>
              <th width="240">TIÊU ĐỀ BÀI VIẾT</th>
              <th width="200">ĐƯỜNG DẪN (SLUG)</th>
              <th width="140" class="text-center">NHÓM FOOTER</th>
              <th width="100" class="text-center">THỨ TỰ</th>
              <th width="130" class="text-center">TRẠNG THÁI</th>
              <th width="120" class="text-center">THAO TÁC</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($policies)): ?>
              <?php $stt = 1; foreach ($policies as $row): ?>
                <tr>
                  <td class="text-center font-weight-bold text-muted"><?= $stt++ ?></td>
                  <td>
                    <strong class="text-dark d-block"><?= htmlspecialchars($row['title']) ?></strong>
                    <a href="../../policy-detail.php?slug=<?= htmlspecialchars($row['slug']) ?>" target="_blank" class="small text-primary">
                      <i class="fas fa-external-link-alt mr-1"></i>Xem ngoài web
                    </a>
                  </td>
                  <td><code><?= htmlspecialchars($row['slug']) ?></code></td>
                  <td class="text-center">
                    <?php if ($row['group_type'] === 'policy'): ?>
                      <span class="badge badge-pill badge-primary px-2 py-1">Chính sách</span>
                    <?php else: ?>
                      <span class="badge badge-pill badge-info px-2 py-1">Hỗ trợ</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center font-weight-bold"><?= $row['sort_order'] ?></td>
                  <td class="text-center">
                    <a href="index.php?action=toggle_status&id=<?= $row['id'] ?>" 
                       class="btn btn-sm <?= ($row['is_active'] == 1) ? 'btn-success' : 'btn-secondary' ?> rounded-pill px-3 py-1 font-weight-bold" style="font-size: 0.75rem;">
                      <?= ($row['is_active'] == 1) ? 'Hiển thị' : 'Tạm ẩn' ?>
                    </a>
                  </td>
                  <td class="text-center">
                    <a href="create.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-warning mr-1" title="Chỉnh sửa">
                      <i class="fas fa-edit"></i>
                    </a>
                    <a href="index.php?action=delete&id=<?= $row['id'] ?>" 
                       class="btn btn-sm btn-outline-danger" 
                       onclick="return confirm('Bạn có chắc chắn muốn xóa chính sách này?');" 
                       title="Xóa">
                      <i class="fas fa-trash-alt"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="fas fa-file-contract fa-2x mb-2 d-block text-black-50"></i>
                  Chưa có bài viết chính sách nào. Hãy thêm mới!
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
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
      setTimeout(function() { toastBox.remove(); }, 400);
    }, 3000);
  }
});
</script>