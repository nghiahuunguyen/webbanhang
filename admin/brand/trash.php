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
$page_title = 'Thùng Rác Hãng';

$success_msg = $_SESSION['success_msg'] ?? '';
unset($_SESSION['success_msg']);

// 1. Cấu hình phân trang: 10 hãng / trang
$limit = 5;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;

// 2. Đếm tổng số hãng trong thùng rác
$countTrash = executeResult("SELECT COUNT(id) as total FROM Brand WHERE deleted = 1", true);
$total_records = intval($countTrash['total'] ?? 0);
$total_pages = ceil($total_records / $limit);
if ($total_pages > 0 && $page > $total_pages) $page = $total_pages;

$start = ($page - 1) * $limit;
if ($start < 0) $start = 0;

// 3. Lấy danh sách các Hãng nằm trong thùng rác theo từng trang
$trash_list = executeResult("SELECT * FROM Brand WHERE deleted = 1 ORDER BY id DESC LIMIT $start, $limit");

include '../layout/head.php'; 
include '../layout/sidebar.php'; 
?>

<!-- TOAST THÔNG BÁO GÓC PHẢI TRÊN (3 GIÂY TỰ BIẾN MẤT) -->
<?php if (!empty($success_msg)): ?>
  <div id="toast-wrapper" style="position: fixed; top: 20px; right: 20px; z-index: 99999; min-width: 300px; transition: all 0.5s ease;">
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

<!-- MAIN CONTENT -->
<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-3">
  <?php include '../layout/topbar.php'; ?>

  <div class="custom-card">
    <div class="p-3 px-4 d-flex justify-content-between align-items-center border-bottom flex-wrap">
      <div>
        <h5 class="font-weight-bold mb-1 text-danger"><i class="fas fa-trash-alt mr-2"></i>Thùng Rác Hãng</h5>
      </div>
      <a href="index.php" class="btn btn-sm btn-secondary font-weight-bold mt-2 mt-md-0">
        <i class="fas fa-arrow-left mr-1"></i> Quay lại danh sách
      </a>
    </div>

    <div class="p-4 table-responsive">
      <table class="table table-bordered table-hover mb-0">
        <thead class="thead-light">
          <tr>
            <th width="70px" class="text-center align-middle">STT</th>
            <th width="110px" class="text-center align-middle">LOGO</th>
            <th class="align-middle">TÊN THƯƠNG HIỆU / HÃNG</th>
            <th width="140px" class="text-center align-middle">TÁC VỤ</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($trash_list)): ?>
            <?php 
              $index = $start + 1; 
              foreach ($trash_list as $item): 
                $logo_path = '../../' . $item['logo'];
                $logo_src = (!empty($item['logo']) && file_exists($logo_path)) 
                            ? $logo_path 
                            : 'https://via.placeholder.com/60x40?text=No+Logo';
            ?>
              <tr>
                <td class="text-center align-middle font-weight-bold"><?= $index++ ?></td>
                
                <td class="text-center align-middle">
                  <img src="<?= $logo_src ?>" 
                       class="rounded border p-1 bg-white" 
                       width="65" 
                       height="40" 
                       style="object-fit: contain;" 
                       alt="<?= htmlspecialchars($item['name']) ?>">
                </td>

                <td class="align-middle font-weight-bold text-dark">
                  <?= htmlspecialchars($item['name']) ?>
                </td>

                <td class="text-center align-middle">
                  <div class="d-inline-flex align-items-center">
                    <!-- Nút khôi phục -->
                    <a href="restore.php?id=<?= $item['id'] ?>" class="btn btn-outline-success btn-sm mr-2" title="Khôi phục">
                      <i class="fas fa-undo"></i>
                    </a>
                    <!-- Nút mở modal xác nhận xóa vĩnh viễn -->
                    <button type="button" 
                            class="btn btn-outline-danger btn-sm" 
                            title="Xóa vĩnh viễn" 
                            onclick="confirmDelete(<?= $item['id'] ?>)">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="4" class="text-center text-muted py-4 align-middle">Thùng rác trống.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- THANH PHÂN TRANG (PAGINATION) -->
    <?php if ($total_pages > 1): ?>
  <div class="card-footer bg-white border-top py-3">
    <nav class="d-flex justify-content-center">
      <ul class="pagination pagination-custom mb-0">
        <!-- Nút Trang Trước -->
        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
          <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Trước">
            <i class="fas fa-chevron-left fa-xs"></i>
          </a>
        </li>

        <!-- Các nút số trang -->
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
              <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
            </li>
          <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
            <li class="page-item disabled"><span class="page-link border-0">...</span></li>
          <?php endif; ?>
        <?php endfor; ?>

        <!-- Nút Trang Kế Tiếp -->
        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
          <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Sau">
            <i class="fas fa-chevron-right fa-xs"></i>
          </a>
        </li>
      </ul>
    </nav>
  </div>

  <style>
    /* Style nút phân trang bo tròn hiện đại */
    .pagination-custom {
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .pagination-custom .page-item .page-link {
      min-width: 36px;
      height: 36px;
      padding: 0 10px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      font-size: 0.875rem;
      color: #4b5563;
      background-color: #f3f4f6;
      border: 1px solid transparent;
      border-radius: 50rem !important;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .pagination-custom .page-item .page-link:hover {
      background-color: #e5e7eb;
      color: #111827;
      transform: translateY(-1px);
    }
    .pagination-custom .page-item.active .page-link {
      background-color: #2563eb;
      border-color: #2563eb;
      color: #ffffff;
      box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);
    }
    .pagination-custom .page-item.disabled .page-link {
      background-color: transparent;
      color: #9ca3af;
      cursor: not-allowed;
      box-shadow: none;
      transform: none;
    }
  </style>
<?php endif; ?>

  </div>
</main>

<!-- MODAL XÁC NHẬN XÓA GIAO DIỆN HIỆN ĐẠI -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title font-weight-bold">
          <i class="fas fa-exclamation-triangle mr-2"></i>Xác Nhận Xóa Vĩnh Viễn
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body text-center py-4">
        <i class="fas fa-trash-alt text-danger fa-3x mb-3"></i>
        <h6 class="font-weight-bold">Hành động này không thể hoàn tác!</h6>
        <p class="text-muted mb-0">Bạn có chắc chắn muốn xóa vĩnh viễn thương hiệu này và toàn bộ ảnh liên quan?</p>
      </div>
      <div class="modal-footer bg-light justify-content-center">
        <button type="button" class="btn btn-secondary font-weight-bold px-4" data-dismiss="modal">Hủy</button>
        <a href="#" id="btnConfirmDelete" class="btn btn-danger font-weight-bold px-4">Đồng ý xóa</a>
      </div>
    </div>
  </div>
</div>

<?php include '../layout/scripts.php'; ?>

<!-- SCRIPT ĐIỀU KHIỂN MODAL VÀ TOAST -->
<script>
  function confirmDelete(id) {
    document.getElementById('btnConfirmDelete').href = 'force_delete.php?id=' + id;
    $('#deleteModal').modal('show');
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