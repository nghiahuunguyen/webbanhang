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
$page_title = 'Quản Lý Banner Trang Chủ';

$success_msg = $_SESSION['success_msg'] ?? '';
unset($_SESSION['success_msg']);

// 1. Xử lý Bật / Tắt trạng thái hiển thị
if (isset($_GET['action']) && $_GET['action'] === 'toggle') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $cur = executeResult("SELECT is_active FROM `Banner` WHERE id = $id AND deleted = 0 LIMIT 1", true);
        if ($cur) {
            $new_st = ($cur['is_active'] == 1) ? 0 : 1;
            execute("UPDATE `Banner` SET is_active = $new_st WHERE id = $id");
            $_SESSION['success_msg'] = "Cập nhật trạng thái hiển thị banner thành công!";
        }
    }
    header('Location: index.php');
    die();
}

// 2. Lấy danh sách banner chưa bị xóa
$banners = executeResult("SELECT * FROM `Banner` WHERE deleted = 0 ORDER BY sort_order ASC, id DESC");

// 3. Đếm số lượng banner trong thùng rác
$countTrash = executeResult("SELECT COUNT(id) as total FROM `Banner` WHERE deleted = 1", true);
$total_trash = intval($countTrash['total'] ?? 0);

include '../layout/head.php'; 
include '../layout/sidebar.php'; 
?>

<!-- TOAST THÔNG BÁO GÓC PHẢI TRÊN -->
<?php if (!empty($success_msg)): ?>
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
        <?= htmlspecialchars($success_msg) ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-3">
  <?php include '../layout/topbar.php'; ?>

  <div class="custom-card">
    <div class="p-3 px-4 d-flex justify-content-between align-items-center border-bottom flex-wrap">
      <div>
        <h5 class="font-weight-bold mb-0 text-dark">Quản Lý Banner Slide Trang Chủ</h5>
        <small class="text-muted">Các hình ảnh trình chiếu xoay vòng đầu trang chủ</small>
      </div>
      <div class="mt-2 mt-md-0">
        <a href="trash.php" class="btn btn-sm btn-outline-danger font-weight-bold mr-2">
          <i class="fas fa-trash-alt mr-1"></i> Thùng rác 
          <span class="badge badge-danger"><?= $total_trash ?></span>
        </a>
        <a href="create.php" class="btn btn-sm btn-success font-weight-bold">
          <i class="fas fa-plus mr-1"></i> Thêm mới banner
        </a>
      </div>
    </div>

    <div class="p-4 table-responsive">
      <table class="table table-bordered table-hover mb-0 align-middle">
        <thead class="thead-light">
          <tr>
            <th width="60px" class="text-center align-middle">STT</th>
            <th width="240px" class="text-center align-middle">HÌNH ẢNH BANNER</th>
            <th class="align-middle">ĐƯỜNG DẪN ẢNH</th>
            <th width="120px" class="text-center align-middle">THỨ TỰ</th>
            <th width="140px" class="text-center align-middle">TRẠNG THÁI</th>
            <th width="160px" class="align-middle">NGÀY TẠO</th>
            <th width="120px" class="text-center align-middle">HÀNH ĐỘNG</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($banners)): ?>
            <?php 
              $stt = 1; 
              foreach ($banners as $b): 
                $img_path = '../../' . $b['image_url'];
                $img_src = (!empty($b['image_url']) && file_exists($img_path)) 
                           ? $img_path 
                           : ((strpos($b['image_url'], 'http') === 0) ? $b['image_url'] : 'https://via.placeholder.com/220x95?text=No+Img');
            ?>
              <tr>
                <td class="text-center align-middle font-weight-bold"><?= $stt++ ?></td>
                <td class="text-center align-middle">
                  <div style="width: 210px; height: 90px; border-radius: 8px; overflow: hidden; background: #f8fafc;" class="border mx-auto p-1 bg-white">
                    <img src="<?= htmlspecialchars($img_src) ?>" alt="Banner" style="width: 100%; height: 100%; object-fit: cover;" class="rounded">
                  </div>
                </td>
                <td class="align-middle">
                  <code class="text-break small"><?= htmlspecialchars($b['image_url']) ?></code>
                </td>
                <td class="text-center align-middle font-weight-bold text-primary" style="font-size: 1.05rem;">
                  <?= $b['sort_order'] ?>
                </td>
                <td class="text-center align-middle">
                  <a href="index.php?action=toggle&id=<?= $b['id'] ?>" 
                     class="btn btn-sm <?= ($b['is_active'] == 1) ? 'btn-success' : 'btn-secondary' ?> rounded-pill px-3 py-1 font-weight-bold" style="font-size: 0.75rem;">
                    <?= ($b['is_active'] == 1) ? '<i class="fas fa-eye mr-1"></i> Đang hiện' : '<i class="fas fa-eye-slash mr-1"></i> Đang ẩn' ?>
                  </a>
                </td>
                <td class="align-middle small text-muted">
                  <div><i class="far fa-calendar-alt mr-1"></i><?= date('d/m/Y', strtotime($b['created_at'])) ?></div>
                  <div><i class="far fa-clock mr-1"></i><?= date('H:i', strtotime($b['created_at'])) ?></div>
                </td>
                <td class="text-center align-middle">
                  <div class="d-inline-flex align-items-center">
                    <a href="edit.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-warning mr-2" title="Chỉnh sửa">
                      <i class="fas fa-edit"></i>
                    </a>
                    <a href="delete.php?id=<?= $b['id'] ?>" 
                       class="btn btn-sm btn-outline-danger" title="Xóa vào thùng rác">
                      <i class="fas fa-trash-alt"></i>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="text-center text-muted py-5 align-middle">
                <i class="far fa-images fa-2x mb-2 text-black-50 d-block"></i>
                Chưa có banner nào. Hãy bấm "Thêm mới banner" để tải ảnh lên!
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
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
        setTimeout(function() { toastBox.remove(); }, 500);
      }, 3000);
    }
  });
</script>