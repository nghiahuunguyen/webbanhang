<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once('../../database/dbhelper.php');
require_once('../check_role.php');

// Cho phép Admin (1) và Nhân viên (3) truy cập[cite: 6]
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role_id'], [1, 3])) {
    header('Location: ../auth/login.php');
    die();
}

$success_msg = $_SESSION['success_msg'] ?? '';
unset($_SESSION['success_msg']);

// Cấu hình phân trang: 5 hãng / trang[cite: 6]
$limit = 5;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;

$countBrand = executeResult("SELECT COUNT(id) as total FROM Brand WHERE deleted = 0", true);
$total_records = intval($countBrand['total'] ?? 0);
$total_pages = ceil($total_records / $limit);
if ($total_pages > 0 && $page > $total_pages) $page = $total_pages;

$start = ($page - 1) * $limit;
if ($start < 0) $start = 0;

$brands = executeResult("SELECT * FROM Brand WHERE deleted = 0 ORDER BY id DESC LIMIT $start, $limit");

$countTrash = executeResult("SELECT COUNT(id) as total FROM Brand WHERE deleted = 1", true);
$total_trash = intval($countTrash['total'] ?? 0);

$page_title = 'Quản Lý Hãng';
include '../layout/head.php'; 
include '../layout/sidebar.php'; 
?>

<!-- TOAST THÔNG BÁO[cite: 6] -->
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

<!-- MAIN CONTENT[cite: 6] -->
<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-3">
  <?php include '../layout/topbar.php'; ?>

  <div class="custom-card">
    <div class="p-3 px-4 d-flex justify-content-between align-items-center border-bottom flex-wrap">
      <div>
        <h5 class="font-weight-bold mb-0 text-dark">Quản Lý Thương Hiệu & Hãng</h5>
      </div>
      <div class="mt-2 mt-md-0">
        <a href="trash.php" class="btn btn-sm btn-outline-danger font-weight-bold mr-2">
          <i class="fas fa-trash-alt mr-1"></i> Thùng rác 
          <span class="badge badge-danger"><?= $total_trash ?></span>
        </a>
        <a href="create.php" class="btn btn-sm btn-success font-weight-bold">
          <i class="fas fa-plus mr-1"></i> Thêm mới hãng
        </a>
      </div>
    </div>

    <div class="p-4 table-responsive">
      <table class="table table-bordered table-hover mb-0 align-middle">
        <thead class="thead-light">
          <tr>
            <th width="70px" class="text-center align-middle">STT</th>
            <th width="110px" class="text-center align-middle">LOGO</th>
            <th class="align-middle">TÊN HÃNG</th>
            <th class="align-middle">ĐƯỜNG DẪN (SLUG)</th>
            <th width="120px" class="text-center align-middle">HÀNH ĐỘNG</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($brands)): ?>
            <?php 
              $index = $start + 1; 
              foreach ($brands as $item): 
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

                <td class="align-middle">
                  <code class="text-break small"><?= htmlspecialchars($item['slug'] ?? '') ?></code>
                  <a href="../../thuong-hieu/<?= htmlspecialchars($item['slug'] ?? '') ?>" target="_blank" class="ml-2 small text-primary" title="Xem sản phẩm hãng này">
                    <i class="fas fa-external-link-alt"></i>
                  </a>
                </td>

                <td class="text-center align-middle">
                  <div class="d-inline-flex align-items-center">
                    <a href="edit.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-warning mr-2" title="Sửa">
                      <i class="fas fa-edit"></i>
                    </a>
                    <a href="delete.php?id=<?= $item['id'] ?>" 
                       class="btn btn-sm btn-outline-danger" title="Xóa vào thùng rác">
                      <i class="fas fa-trash-alt"></i>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" class="text-center text-muted py-4 align-middle">Chưa có hãng nào.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- PHÂN TRANG[cite: 6] -->
    <?php if ($total_pages > 1): ?>
      <div class="card-footer bg-white border-top py-3">
        <nav class="d-flex justify-content-center">
          <ul class="pagination pagination-custom mb-0">
            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
              <a class="page-link" href="?page=<?= $page - 1 ?>"><i class="fas fa-chevron-left fa-xs"></i></a>
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
              <a class="page-link" href="?page=<?= $page + 1 ?>"><i class="fas fa-chevron-right fa-xs"></i></a>
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
          border-radius: 50rem !important; transition: all 0.2s ease;
        }
        .pagination-custom .page-item .page-link:hover { background-color: #e5e7eb; color: #111827; }
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