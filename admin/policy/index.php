<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once('../../database/dbhelper.php');
require_once('../check_role.php');

if (!isset($_SESSION['user']) || (int)$_SESSION['user']['role_id'] !== 1) {
    header('Location: ../dashboard/index.php');
    die();
}

$page_title = 'Quản Lý Chính Sách & Hỗ Trợ';

// 1. XỬ LÝ LƯU TIÊU ĐỀ 2 CỘT TỪ FORM ADMIN (Lưu vào System_Settings)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_save_titles'])) {
    $col2_title = trim($_POST['footer_policy_title'] ?? 'Chính Sách');
    $col3_title = trim($_POST['footer_support_title'] ?? 'Hỗ Trợ');

    $save_keys = [
        'footer_policy_title'  => $col2_title,
        'footer_support_title' => $col3_title
    ];

    foreach ($save_keys as $k => $val) {
        $safe_val = str_replace("'", "\'", $val);
        $check = executeResult("SELECT id FROM `System_Settings` WHERE key_name = '$k' LIMIT 1", true);
        if ($check) {
            execute("UPDATE `System_Settings` SET key_value = '$safe_val', updated_at = NOW() WHERE key_name = '$k'");
        } else {
            execute("INSERT INTO `System_Settings` (key_name, key_value, updated_at) VALUES ('$k', '$safe_val', NOW())");
        }
    }

    $_SESSION['policy_msg'] = "Đã cập nhật tiêu đề hiển thị ở chân trang!";
    header('Location: index.php');
    die();
}

// 2. Xử lý Bật/Tắt hiển thị nhanh bài viết
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status') {
    $id = intval($_GET['id'] ?? 0);
    $page_curr = intval($_GET['page'] ?? 1);
    if ($id > 0) {
        $cur = executeResult("SELECT is_active FROM `Policy` WHERE id = $id AND deleted = 0 LIMIT 1", true);
        if ($cur) {
            $new_status = ($cur['is_active'] == 1) ? 0 : 1;
            execute("UPDATE `Policy` SET is_active = $new_status WHERE id = $id");
            $_SESSION['policy_msg'] = "Cập nhật trạng thái hiển thị thành công!";
        }
    }
    header("Location: index.php?page=$page_curr");
    die();
}

$msg = $_SESSION['policy_msg'] ?? '';
unset($_SESSION['policy_msg']);

// 3. Đọc tiêu đề 2 cột hiện tại từ System_Settings
$curr_titles_raw = executeResult("SELECT key_name, key_value FROM `System_Settings` WHERE key_name IN ('footer_policy_title', 'footer_support_title')");
$titles_map = [];
if (!empty($curr_titles_raw)) {
    foreach ($curr_titles_raw as $t) {
        $titles_map[$t['key_name']] = $t['key_value'];
    }
}
$policy_title_name  = $titles_map['footer_policy_title'] ?? 'Chính Sách';
$support_title_name = $titles_map['footer_support_title'] ?? 'Hỗ Trợ';

// 4. Phân trang 5 bài / trang
$limit = 5;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;

$countPolicy = executeResult("SELECT COUNT(id) AS total FROM `Policy` WHERE deleted = 0", true);
$total_records = intval($countPolicy['total'] ?? 0);
$total_pages = ceil($total_records / $limit);
if ($total_pages > 0 && $page > $total_pages) $page = $total_pages;

$start = ($page - 1) * $limit;
if ($start < 0) $start = 0;

$policies = executeResult("SELECT * FROM `Policy` WHERE deleted = 0 ORDER BY `group_type` ASC, `sort_order` ASC, `id` ASC LIMIT $start, $limit");

$countTrash = executeResult("SELECT COUNT(id) AS total FROM `Policy` WHERE deleted = 1", true);
$total_trash = intval($countTrash['total'] ?? 0);

include '../layout/head.php';
include '../layout/sidebar.php';
?>

<!-- TOAST THÔNG BÁO -->
<?php if (!empty($msg)): ?>
  <div id="toast-wrapper" style="position: fixed; top: 20px; right: 20px; z-index: 99999; min-width: 320px; transition: all 0.5s ease;">
    <div class="toast show shadow-lg border-0" role="alert">
      <div class="toast-header bg-success text-white">
        <i class="fas fa-check-circle mr-2"></i>
        <strong class="mr-auto">Thông báo</strong>
        <small class="text-white">Vừa xong</small>
        <button type="button" class="ml-2 mb-1 close text-white" onclick="document.getElementById('toast-wrapper').remove()" style="outline: none;">
          <span>&times;</span>
        </button>
      </div>
      <div class="toast-body bg-white text-dark font-weight-bold">
        <?= htmlspecialchars($msg) ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-3">
  <?php include '../layout/topbar.php'; ?>

  <!-- KHỐI ĐỔI TÊN TIÊU ĐỀ TRỰC TIẾP TRONG ADMIN -->
  <div class="card border-0 shadow-sm rounded-lg bg-white mb-4">
    <div class="card-body p-3 px-4">
      <form method="POST" class="d-flex flex-wrap align-items-center justify-content-between">
        <div class="d-flex align-items-center mb-2 mb-md-0">
          <div class="mr-3 text-primary" style="font-size: 1.4rem;">
            <i class="fas fa-heading"></i>
          </div>
          <div>
            <h6 class="font-weight-bold mb-0 text-dark">Tùy Biến Tên Tiêu Đề Cột Chân Trang</h6>
            <small class="text-muted">Đổi tên hiển thị cho 2 nhóm nội dung (ví dụ: Chính Sách -> Quy Định)</small>
          </div>
        </div>

        <div class="d-flex align-items-center flex-wrap" style="gap: 12px;">
          <div>
            <input type="text" name="footer_policy_title" class="form-control form-control-sm font-weight-bold" 
                   value="<?= htmlspecialchars($policy_title_name) ?>" placeholder="Tên nhóm 1" style="width: 150px;">
          </div>
          <div>
            <input type="text" name="footer_support_title" class="form-control form-control-sm font-weight-bold" 
                   value="<?= htmlspecialchars($support_title_name) ?>" placeholder="Tên nhóm 2" style="width: 150px;">
          </div>
          <button type="submit" name="btn_save_titles" class="btn btn-sm btn-primary font-weight-bold px-3">
            <i class="fas fa-save mr-1"></i> Lưu Tên
          </button>
        </div>
      </form>
    </div>
  </div>

  <div class="custom-card">
    <div class="p-3 px-4 d-flex justify-content-between align-items-center border-bottom flex-wrap">
      <div>
        <h5 class="font-weight-bold mb-0 text-dark">Danh Sách Bài Viết</h5>
        <small class="text-muted">Các bài viết nằm dưới 2 cột chân trang</small>
      </div>
      <div class="mt-2 mt-md-0">
        <a href="trash.php" class="btn btn-sm btn-outline-danger font-weight-bold mr-2">
          <i class="fas fa-trash-alt mr-1"></i> Thùng rác 
          <span class="badge badge-danger"><?= $total_trash ?></span>
        </a>
        <a href="create.php" class="btn btn-sm btn-success font-weight-bold">
          <i class="fas fa-plus mr-1"></i> Thêm mới bài viết
        </a>
      </div>
    </div>

    <div class="p-4 table-responsive">
      <table class="table table-bordered table-hover mb-0 align-middle">
        <thead class="thead-light">
          <tr>
            <th width="60px" class="text-center align-middle">STT</th>
            <th width="260px" class="align-middle">TIÊU ĐỀ BÀI VIẾT</th>
            <th class="align-middle">ĐƯỜNG DẪN (SLUG)</th>
            <th width="150px" class="text-center align-middle">CỘT HIỂN THỊ</th>
            <th width="100px" class="text-center align-middle">THỨ TỰ</th>
            <th width="130px" class="text-center align-middle">TRẠNG THÁI</th>
            <th width="120px" class="text-center align-middle">HÀNH ĐỘNG</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($policies)): ?>
            <?php 
              $stt = $start + 1; 
              foreach ($policies as $row): 
            ?>
              <tr>
                <td class="text-center align-middle font-weight-bold"><?= $stt++ ?></td>
                <td class="align-middle">
                  <strong class="text-dark d-block"><?= htmlspecialchars($row['title']) ?></strong>
                  <a href="../../policy-detail.php?slug=<?= htmlspecialchars($row['slug']) ?>" target="_blank" class="small text-primary">
                    <i class="fas fa-external-link-alt mr-1"></i>Xem bài viết
                  </a>
                </td>
                <td class="align-middle">
                  <code class="text-break small"><?= htmlspecialchars($row['slug']) ?></code>
                </td>
                <td class="text-center align-middle">
                  <?php if ($row['group_type'] === 'policy'): ?>
                    <span class="badge badge-pill badge-primary px-2 py-1"><?= htmlspecialchars($policy_title_name) ?></span>
                  <?php else: ?>
                    <span class="badge badge-pill badge-info px-2 py-1"><?= htmlspecialchars($support_title_name) ?></span>
                  <?php endif; ?>
                </td>
                <td class="text-center align-middle font-weight-bold text-primary">
                  <?= $row['sort_order'] ?>
                </td>
                <td class="text-center align-middle">
                  <a href="index.php?action=toggle_status&id=<?= $row['id'] ?>&page=<?= $page ?>" 
                     class="btn btn-sm <?= ($row['is_active'] == 1) ? 'btn-success' : 'btn-secondary' ?> rounded-pill px-3 py-1 font-weight-bold" style="font-size: 0.75rem;">
                    <?= ($row['is_active'] == 1) ? '<i class="fas fa-eye mr-1"></i> Đang hiện' : '<i class="fas fa-eye-slash mr-1"></i> Đang ẩn' ?>
                  </a>
                </td>
                <td class="text-center align-middle">
                  <div class="d-inline-flex align-items-center">
                    <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-warning mr-2" title="Chỉnh sửa">
                      <i class="fas fa-edit"></i>
                    </a>
                    <a href="delete.php?id=<?= $row['id'] ?>" 
                       class="btn btn-sm btn-outline-danger" 
                       onclick="return confirm('Bạn có chắc muốn chuyển bài viết này vào thùng rác?');" 
                       title="Xóa vào thùng rác">
                      <i class="fas fa-trash-alt"></i>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="text-center text-muted py-5 align-middle">
                <i class="fas fa-file-contract fa-2x mb-2 text-black-50 d-block"></i>
                Chưa có bài viết nào. Hãy bấm "Thêm mới bài viết"!
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Phân trang 5 mục/trang -->
    <?php if ($total_pages > 1): ?>
      <div class="card-footer bg-white border-top py-3">
        <nav class="d-flex justify-content-center">
          <ul class="pagination pagination-custom mb-0">
            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
              <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Trước">
                <i class="fas fa-chevron-left fa-xs"></i>
              </a>
            </li>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
              <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                  <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
              <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                <li class="page-item disabled"><span class="page-link border-0">...</span></li>
              <?php endif; ?>
            <?php endfor; ?>
            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
              <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Sau">
                <i class="fas fa-chevron-right fa-xs"></i>
              </a>
            </li>
          </ul>
        </nav>
      </div>

      <style>
        .pagination-custom { display: flex; align-items: center; gap: 6px; }
        .pagination-custom .page-item .page-link {
          min-width: 36px; height: 36px; padding: 0 10px;
          display: inline-flex; align-items: center; justify-content: center;
          font-weight: 600; font-size: 0.875rem; color: #4b5563;
          background-color: #f3f4f6; border: 1px solid transparent;
          border-radius: 50rem !important; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .pagination-custom .page-item .page-link:hover { background-color: #e5e7eb; color: #111827; transform: translateY(-1px); }
        .pagination-custom .page-item.active .page-link {
          background-color: #2563eb; border-color: #2563eb; color: #ffffff;
          box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);
        }
        .pagination-custom .page-item.disabled .page-link { background-color: transparent; color: #9ca3af; cursor: not-allowed; }
      </style>
    <?php endif; ?>
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
        setTimeout(function() { toastBox.remove(); }, 500);
      }, 3000);
    }
  });
</script>