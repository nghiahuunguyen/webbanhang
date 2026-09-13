<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once('../../database/dbhelper.php');
$page_title = 'Thùng Rác Sản Phẩm';

$success_msg = $_SESSION['success_msg'] ?? '';
unset($_SESSION['success_msg']);

// 1. Cấu hình phân trang: 5 sản phẩm / trang
$limit = 5;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;

// 2. Đếm tổng số sản phẩm trong thùng rác
$countTrash = executeResult("SELECT COUNT(id) as total FROM Product WHERE deleted = 1", true);
$total_records = intval($countTrash['total'] ?? 0);
$total_pages = ceil($total_records / $limit);
if ($total_pages > 0 && $page > $total_pages) $page = $total_pages;

$start = ($page - 1) * $limit;
if ($start < 0) $start = 0;

// 3. Lấy danh sách sản phẩm đã xóa tạm (deleted = 1) kèm Hãng, Danh mục và khoảng giá biến thể theo trang
$sql = "SELECT 
            p.*, 
            c.name AS category_name, 
            b.name AS brand_name,
            COUNT(v.id) AS total_variants,
            MIN(v.price - v.discount) AS min_price,
            MAX(v.price - v.discount) AS max_price
        FROM Product p
        LEFT JOIN Category c ON p.category_id = c.id
        LEFT JOIN Brand b ON p.brand_id = b.id
        LEFT JOIN Product_Variant v ON p.id = v.product_id
        WHERE p.deleted = 1
        GROUP BY p.id
        ORDER BY p.id DESC
        LIMIT $start, $limit";
$trash_list = executeResult($sql);

include '../layout/head.php'; 
include '../layout/sidebar.php'; 
?>

<!-- TOAST THÔNG BÁO GÓC TRÊN BÊN PHẢI (TỰ BIẾN MẤT SAU 3S) -->
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

<!-- MAIN CONTENT -->
<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-3">
  <?php include '../layout/topbar.php'; ?>

  <div class="custom-card">
    <div class="p-3 px-4 d-flex justify-content-between align-items-center border-bottom flex-wrap">
      <div>
        <h5 class="font-weight-bold mb-1 text-danger"><i class="fas fa-trash-alt mr-2"></i>Thùng Rác Sản Phẩm</h5>
        <small class="text-muted">Tổng cộng <?= number_format($total_records, 0, ',', '.') ?> thiết bị đã tạm xóa</small>
      </div>
      <a href="index.php" class="btn btn-sm btn-secondary font-weight-bold mt-2 mt-md-0">
        <i class="fas fa-arrow-left mr-1"></i> Quay lại danh sách
      </a>
    </div>

    <div class="p-4 table-responsive">
      <table class="table table-bordered table-hover mb-0 align-middle">
        <thead class="thead-light">
          <tr>
            <th width="50px" class="text-center align-middle">STT</th>
            <th width="75px" class="text-center align-middle">ẢNH</th>
            <th width="260px" class="align-middle">TÊN THIẾT BỊ</th>
            <th width="140px" class="align-middle">HÃNG / DANH MỤC</th>
            <th width="180px" class="align-middle">KHOẢNG GIÁ</th>
            <th width="120px" class="text-center align-middle">TÁC VỤ</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($trash_list)): ?>
            <?php 
              $index = $start + 1; 
              foreach ($trash_list as $item): 
                $thumb_path = '../../' . $item['thumbnail'];
                $thumb_src = (!empty($item['thumbnail']) && file_exists($thumb_path)) 
                             ? $thumb_path 
                             : 'https://via.placeholder.com/60x60?text=No+Img';
                
                $min_p = (int)($item['min_price'] ?? 0);
                $max_p = (int)($item['max_price'] ?? 0);
                $total_variants = (int)($item['total_variants'] ?? 0);
            ?>
              <tr>
                <td class="text-center align-middle font-weight-bold"><?= $index++ ?></td>
                
                <!-- Hình ảnh sản phẩm -->
                <td class="text-center align-middle">
                  <img src="<?= $thumb_src ?>" 
                       alt="<?= htmlspecialchars($item['title']) ?>" 
                       class="rounded border p-1 bg-white" 
                       width="60" 
                       height="60" 
                       style="object-fit: cover;">
                </td>

                <!-- Tên sản phẩm & số cấu hình -->
                <td class="align-middle">
                  <span class="font-weight-bold text-dark d-block"><?= htmlspecialchars($item['title']) ?></span>
                  <span class="badge badge-light border text-muted small mt-1">
                    <i class="fas fa-layer-group mr-1"></i><?= $total_variants ?> phiên bản cấu hình
                  </span>
                </td>

                <!-- Hãng và Danh mục -->
                <td class="align-middle">
                  <div class="font-weight-bold text-dark mb-1">
                    <i class="fas fa-tag text-muted mr-1"></i><?= htmlspecialchars($item['brand_name'] ?? 'Chưa rõ') ?>
                  </div>
                  <span class="badge badge-pill badge-light border text-secondary px-2 py-1">
                    <?= htmlspecialchars($item['category_name'] ?? 'Chưa phân loại') ?>
                  </span>
                </td>

                <!-- Khoảng giá -->
                <td class="align-middle">
                  <?php if ($total_variants > 0): ?>
                    <?php if ($min_p === $max_p): ?>
                      <div class="text-danger font-weight-bold">
                        <?= number_format($min_p, 0, ',', '.') ?> đ
                      </div>
                    <?php else: ?>
                      <div class="text-danger font-weight-bold">
                        <?= number_format($min_p, 0, ',', '.') ?> đ
                      </div>
                      <small class="text-muted d-block">
                        đến <?= number_format($max_p, 0, ',', '.') ?> đ
                      </small>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="text-muted small">Không có giá</span>
                  <?php endif; ?>
                </td>

                <!-- Tác vụ khôi phục / Xóa vĩnh viễn -->
                <td class="text-center align-middle">
                  <div class="d-inline-flex align-items-center">
                    <!-- Nút khôi phục -->
                    <a href="restore.php?id=<?= $item['id'] ?>" class="btn btn-outline-success btn-sm mr-2" title="Khôi phục sản phẩm">
                      <i class="fas fa-undo"></i>
                    </a>
                    <!-- Nút mở popup xác nhận xóa vĩnh viễn -->
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
              <td colspan="6" class="text-center text-muted py-4 align-middle">Thùng rác trống.</td>
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

<!-- MODAL XÁC NHẬN XÓA VĨNH VIỄN -->
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
        <p class="text-muted mb-0">Toàn bộ thông số cấu hình, biến thể giá và ảnh trong bộ sưu tập của sản phẩm này sẽ bị xóa sạch khỏi hệ thống.</p>
      </div>
      <div class="modal-footer bg-light justify-content-center">
        <button type="button" class="btn btn-secondary font-weight-bold px-4" data-dismiss="modal">Hủy</button>
        <a href="#" id="btnConfirmDelete" class="btn btn-danger font-weight-bold px-4">Đồng ý xóa</a>
      </div>
    </div>
  </div>
</div>

<?php include '../layout/scripts.php'; ?>

<!-- SCRIPT ĐIỀU KHIỂN MODAL VÀ TỰ ẨN THÔNG BÁO -->
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