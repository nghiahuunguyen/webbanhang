<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once __DIR__ . '/database/dbhelper.php';

// 1. Nhận các tham số lọc & tìm kiếm từ URL
$category_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$brand_id    = isset($_GET['brand']) ? intval($_GET['brand']) : 0;
$sort        = $_GET['sort'] ?? 'hot';
$price_range = $_GET['price_range'] ?? '';
$filter_ram  = trim($_GET['ram'] ?? '');
$filter_cpu  = trim($_GET['cpu'] ?? '');
$filter_gpu  = trim($_GET['gpu'] ?? '');
$keyword     = trim($_GET['search'] ?? '');

// Lấy thông tin danh mục hiện tại
$current_category = null;
if ($category_id > 0) {
    $current_category = executeResult("SELECT * FROM Category WHERE id = $category_id AND deleted = 0", true);
}

// 2. Lấy danh sách Hãng (Brand) có sản phẩm thuộc danh mục
$brand_sql_where = "WHERE p.deleted = 0";
if ($category_id > 0) {
    $brand_sql_where .= " AND p.category_id = $category_id";
}
$available_brands = executeResult("SELECT DISTINCT b.id, b.name, b.logo 
                                   FROM Brand b 
                                   INNER JOIN Product p ON b.id = p.brand_id 
                                   $brand_sql_where AND b.deleted = 0 
                                   ORDER BY b.name ASC");

// 3. Quét động các thông số RAM, CPU, GPU thực tế có trong CSDL
$variants_meta = executeResult("SELECT DISTINCT v.ram, v.cpu, v.gpu 
                                FROM Product_Variant v 
                                INNER JOIN Product p ON v.product_id = p.id 
                                $brand_sql_where AND v.deleted = 0");

$avail_rams = array_unique(array_filter(array_column($variants_meta, 'ram')));
$avail_cpus = array_unique(array_filter(array_column($variants_meta, 'cpu')));
$avail_gpus = array_unique(array_filter(array_column($variants_meta, 'gpu')));

// 4. Xây dựng điều kiện lọc SQL
$where_clauses = ["p.deleted = 0"];
if ($category_id > 0) {
    $where_clauses[] = "p.category_id = $category_id";
}
if ($brand_id > 0) {
    $where_clauses[] = "p.brand_id = $brand_id";
}
if (!empty($keyword)) {
    $safe_kw = str_replace("'", "\'", $keyword);
    $where_clauses[] = "(p.title LIKE '%$safe_kw%' OR p.description LIKE '%$safe_kw%')";
}
if (!empty($filter_ram)) {
    $safe_ram = str_replace("'", "\'", $filter_ram);
    $where_clauses[] = "p.id IN (SELECT product_id FROM Product_Variant WHERE ram = '$safe_ram' AND deleted = 0)";
}
if (!empty($filter_cpu)) {
    $safe_cpu = str_replace("'", "\'", $filter_cpu);
    $where_clauses[] = "p.id IN (SELECT product_id FROM Product_Variant WHERE cpu = '$safe_cpu' AND deleted = 0)";
}
if (!empty($filter_gpu)) {
    $safe_gpu = str_replace("'", "\'", $filter_gpu);
    $where_clauses[] = "p.id IN (SELECT product_id FROM Product_Variant WHERE gpu = '$safe_gpu' AND deleted = 0)";
}

// Lọc theo mốc giá (giá thực tế sau khi trừ discount)
if ($price_range === 'under_10') {
    $where_clauses[] = "p.id IN (SELECT product_id FROM Product_Variant WHERE (price - discount) < 10000000 AND deleted = 0)";
} elseif ($price_range === '10_15') {
    $where_clauses[] = "p.id IN (SELECT product_id FROM Product_Variant WHERE (price - discount) BETWEEN 10000000 AND 15000000 AND deleted = 0)";
} elseif ($price_range === '15_20') {
    $where_clauses[] = "p.id IN (SELECT product_id FROM Product_Variant WHERE (price - discount) BETWEEN 15000000 AND 20000000 AND deleted = 0)";
} elseif ($price_range === '20_30') {
    $where_clauses[] = "p.id IN (SELECT product_id FROM Product_Variant WHERE (price - discount) BETWEEN 20000000 AND 30000000 AND deleted = 0)";
} elseif ($price_range === 'over_30') {
    $where_clauses[] = "p.id IN (SELECT product_id FROM Product_Variant WHERE (price - discount) > 30000000 AND deleted = 0)";
}

$where_sql = implode(' AND ', $where_clauses);

// Sắp xếp
$order_by = "ORDER BY p.id DESC";
if ($sort === 'sale') {
    $order_by = "ORDER BY max_discount DESC";
} elseif ($sort === 'newest') {
    $order_by = "ORDER BY p.id DESC";
} elseif ($sort === 'price_asc') {
    $order_by = "ORDER BY min_sale_price ASC";
} elseif ($sort === 'price_desc') {
    $order_by = "ORDER BY min_sale_price DESC";
}

// 5. Cấu hình phân trang: 12 máy / trang
$limit = 12;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;

$count_res = executeResult("SELECT COUNT(DISTINCT p.id) AS total FROM Product p WHERE $where_sql", true);
$total_records = intval($count_res['total'] ?? 0);
$total_pages = ceil($total_records / $limit);
if ($total_pages > 0 && $page > $total_pages) $page = $total_pages;

$start = ($page - 1) * $limit;
if ($start < 0) $start = 0;

// 6. Lấy dữ liệu sản phẩm kết hợp khoảng giá
$sql_products = "SELECT p.*, 
                        c.name AS category_name, 
                        b.name AS brand_name,
                        MIN(v.price - v.discount) AS min_sale_price,
                        MAX(v.discount) AS max_discount
                 FROM Product p
                 LEFT JOIN Category c ON p.category_id = c.id
                 LEFT JOIN Brand b ON p.brand_id = b.id
                 LEFT JOIN Product_Variant v ON p.id = v.product_id AND v.deleted = 0
                 WHERE $where_sql
                 GROUP BY p.id
                 $order_by
                 LIMIT $start, $limit";
$products = executeResult($sql_products);

// Lấy danh sách biến thể tương ứng cho từng sản phẩm
$product_ids = array_column($products, 'id');
$variants_by_product = [];
if (!empty($product_ids)) {
    $ids_str = implode(',', $product_ids);
    $variants_raw = executeResult("SELECT * FROM Product_Variant WHERE product_id IN ($ids_str) AND deleted = 0 ORDER BY price ASC");
    foreach ($variants_raw as $v) {
        $variants_by_product[$v['product_id']][] = $v;
    }
}

// Hàm sinh URL giữ nguyên query
function filterUrl($overrides = []) {
    $params = $_GET;
    foreach ($overrides as $k => $v) {
        if ($v === null || $v === '') {
            unset($params[$k]);
        } else {
            $params[$k] = $v;
        }
    }
    unset($params['page']);
    return 'category.php?' . http_build_query($params);
}

$page_title = ($current_category ? $current_category['name'] : 'Danh sách sản phẩm') . ' - NovaShop';
include 'includes/header.php';
?>

<div class="container py-3">
  <!-- 1. Breadcrumb -->
  <div class="mb-3 small text-muted">
    <a href="index.php" class="text-secondary text-decoration-none">Trang chủ</a> 
    <i class="fas fa-chevron-right mx-1" style="font-size: 0.65rem;"></i>
    <span class="text-dark font-weight-bold">
      <?= number_format($total_records, 0, ',', '.') ?> <?= $current_category ? htmlspecialchars($current_category['name']) : 'Sản phẩm' ?>
      <?= !empty($keyword) ? ' cho từ khóa "' . htmlspecialchars($keyword) . '"' : '' ?>
    </span>
  </div>

  <!-- 2. Hàng bộ lọc: Nút [Lọc] + Dãy Hãng cuộn ngang -->
  <div class="d-flex align-items-center mb-3 brand-scroll-container">
    <button type="button" class="btn-filter-modal mr-2" data-toggle="modal" data-target="#filterModal">
      <i class="fas fa-filter mr-1 text-primary"></i> Lọc
      <?php 
        $active_count = ($brand_id ? 1 : 0) + ($price_range ? 1 : 0) + ($filter_ram ? 1 : 0) + ($filter_cpu ? 1 : 0) + ($filter_gpu ? 1 : 0);
        if ($active_count > 0): 
      ?>
        <span class="badge badge-primary rounded-circle ml-1" style="font-size: 0.65rem;"><?= $active_count ?></span>
      <?php endif; ?>
    </button>

    <div class="d-flex align-items-center brand-list-scroll">
      <a href="<?= filterUrl(['brand' => null]) ?>" 
         class="brand-pill-btn <?= ($brand_id === 0) ? 'active' : '' ?>">
        <span>Tất cả</span>
      </a>
      <?php foreach ($available_brands as $b): 
        $is_selected = ($brand_id === (int)$b['id']);
        $logo_path = $b['logo'];
      ?>
        <a href="<?= filterUrl(['brand' => $is_selected ? null : $b['id']]) ?>" 
           class="brand-pill-btn <?= $is_selected ? 'active' : '' ?>"
           title="<?= htmlspecialchars($b['name']) ?>">
          <?php if (!empty($logo_path) && (file_exists($logo_path) || file_exists(__DIR__ . '/' . $logo_path))): ?>
            <img src="<?= htmlspecialchars($logo_path) ?>" alt="<?= htmlspecialchars($b['name']) ?>">
          <?php else: ?>
            <span><?= htmlspecialchars($b['name']) ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- 3. Hàng sắp xếp -->
  <div class="d-flex align-items-center mb-4 flex-wrap sort-bar-text">
    <span class="text-dark font-weight-medium mr-3" style="font-size: 0.88rem;">Sắp xếp theo:</span>
    
    <a href="<?= filterUrl(['sort' => 'hot']) ?>" class="sort-link <?= ($sort === 'hot') ? 'active' : '' ?>">
      Nổi bật
    </a>
    <span class="text-muted mx-2">•</span>

    <a href="<?= filterUrl(['sort' => 'sale']) ?>" class="sort-link <?= ($sort === 'sale') ? 'active' : '' ?>">
      Giảm giá
    </a>
    <span class="text-muted mx-2">•</span>

    <a href="<?= filterUrl(['sort' => 'newest']) ?>" class="sort-link <?= ($sort === 'newest') ? 'active' : '' ?>">
      Mới
    </a>
    <span class="text-muted mx-2">•</span>

    <div class="dropdown d-inline-block">
      <a class="sort-link dropdown-toggle <?= in_array($sort, ['price_asc', 'price_desc']) ? 'active' : '' ?>" href="#" data-toggle="dropdown">
        Giá <?= ($sort === 'price_asc') ? ': Thấp đến Cao' : (($sort === 'price_desc') ? ': Cao đến Thấp' : '') ?>
      </a>
      <div class="dropdown-menu shadow-sm border-0 rounded-lg">
        <a class="dropdown-item small py-2 font-weight-medium <?= $sort === 'price_asc' ? 'text-primary' : '' ?>" href="<?= filterUrl(['sort' => 'price_asc']) ?>">
          <i class="fas fa-arrow-up mr-2 text-muted"></i>Giá từ thấp đến cao
        </a>
        <a class="dropdown-item small py-2 font-weight-medium <?= $sort === 'price_desc' ? 'text-primary' : '' ?>" href="<?= filterUrl(['sort' => 'price_desc']) ?>">
          <i class="fas fa-arrow-down mr-2 text-muted"></i>Giá từ cao đến thấp
        </a>
      </div>
    </div>
  </div>

  <!-- 4. Lưới hiển thị danh sách sản phẩm -->
  <div class="row">
    <?php if (!empty($products)): ?>
      <?php foreach ($products as $prod): 
        $pid = $prod['id'];
        $variants = $variants_by_product[$pid] ?? [];
        
        $raw_thumb = $prod['thumbnail'];
        $thumb_src = (!empty($raw_thumb) && (file_exists($raw_thumb) || file_exists(__DIR__ . '/' . $raw_thumb))) 
                     ? htmlspecialchars($raw_thumb) 
                     : (!empty($raw_thumb) && strpos($raw_thumb, 'http') === 0 ? htmlspecialchars($raw_thumb) : 'https://via.placeholder.com/300x300?text=No+Image');

        $first_v = !empty($variants) ? $variants[0] : null;
        $def_spec = $first_v ? ($first_v['storage'] ?: ($first_v['ram'] ?: '')) : '';
        $def_price = $first_v ? (int)$first_v['price'] : 0;
        $def_discount = $first_v ? (int)$first_v['discount'] : 0;
        $def_sale = max(0, $def_price - $def_discount);
        $def_percent = ($def_price > 0 && $def_discount > 0) ? round(($def_discount / $def_price) * 100) : 0;

        $spec_counts = [];
        foreach ($variants as $v) {
            $key = $v['storage'] ?: ($v['ram'] ?: 'Bản');
            $spec_counts[$key] = ($spec_counts[$key] ?? 0) + 1;
        }
      ?>
        <div class="col-lg-3 col-md-4 col-6 mb-4">
          <div class="product-card border rounded p-3 bg-white h-100 d-flex flex-column" data-base-title="<?= htmlspecialchars($prod['title']) ?>">
            
            <!-- Link chuẩn tới detail.php -->
            <a href="detail.php?id=<?= $pid ?>" class="d-block text-center py-2">
              <img src="<?= $thumb_src ?>" alt="<?= htmlspecialchars($prod['title']) ?>" class="img-fluid product-thumb">
            </a>

            <h6 class="product-title font-weight-normal text-dark mb-2 mt-1">
              <a href="detail.php?id=<?= $pid ?>" class="text-dark text-decoration-none">
                <span class="title-text"><?= htmlspecialchars($prod['title']) ?><?= $def_spec ? ' ' . htmlspecialchars($def_spec) : '' ?></span>
              </a>
            </h6>

            <!-- Tag CPU / GPU / RAM -->
            <div class="d-flex flex-wrap mb-2 tag-specs-container" style="gap: 4px; min-height: 24px;">
              <?php if (!empty($first_v['cpu'])): ?>
                <span class="badge-spec"><?= htmlspecialchars($first_v['cpu']) ?></span>
              <?php endif; ?>
              <?php if (!empty($first_v['gpu'])): ?>
                <span class="badge-spec"><?= htmlspecialchars($first_v['gpu']) ?></span>
              <?php elseif (!empty($first_v['ram'])): ?>
                <span class="badge-spec"><?= htmlspecialchars($first_v['ram']) ?> RAM</span>
              <?php endif; ?>
            </div>

            <!-- Nút chọn phiên bản cấu hình -->
            <?php if (!empty($variants)): ?>
              <div class="variant-chips d-flex flex-wrap mb-2" style="gap: 6px;">
                <?php foreach ($variants as $idx => $v): 
                  $base_key = $v['storage'] ?: ($v['ram'] ?: 'Bản ' . ($idx + 1));
                  $btn_label = ($spec_counts[$base_key] > 1 && !empty($v['color'])) 
                               ? $base_key . ' ' . $v['color'] 
                               : $base_key;

                  $v_price = (int)$v['price'];
                  $v_disc = (int)$v['discount'];
                  $v_sale = max(0, $v_price - $v_disc);
                  $v_pct = ($v_price > 0 && $v_disc > 0) ? round(($v_disc / $v_price) * 100) : 0;
                ?>
                  <button type="button" 
                          class="btn-chip <?= $idx === 0 ? 'active' : '' ?>"
                          data-spec="<?= htmlspecialchars($btn_label) ?>"
                          data-cpu="<?= htmlspecialchars($v['cpu'] ?? '') ?>"
                          data-gpu="<?= htmlspecialchars($v['gpu'] ?? '') ?>"
                          data-ram="<?= htmlspecialchars($v['ram'] ?? '') ?>"
                          data-sale="<?= number_format($v_sale, 0, ',', '.') ?>"
                          data-price="<?= number_format($v_price, 0, ',', '.') ?>"
                          data-discount="<?= $v_disc ?>"
                          data-percent="<?= $v_pct ?>">
                    <?= htmlspecialchars($btn_label) ?>
                  </button>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <!-- Khối giá bán -->
            <div class="mt-auto pt-2">
              <div class="price-current text-danger font-weight-bold">
                <span class="val-sale"><?= $def_sale > 0 ? number_format($def_sale, 0, ',', '.') : 'Liên hệ' ?></span>₫
              </div>
              <div class="price-old-wrap text-muted small <?= ($def_discount <= 0 || $def_percent <= 0) ? 'd-none' : '' ?>">
                <span class="val-old" style="text-decoration: line-through;"><?= number_format($def_price, 0, ',', '.') ?>₫</span>
                <span class="text-danger font-weight-bold ml-1">-<span class="val-percent"><?= $def_percent ?></span>%</span>
              </div>
            </div>

            <div class="d-flex align-items-center mt-2 pt-2 border-top small text-muted">
              <i class="fas fa-star text-warning mr-1"></i>
              <span class="font-weight-bold text-dark mr-1">4.9</span>
              <span class="text-secondary">• Bảo hành chính hãng</span>
            </div>

          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="col-12 py-5 text-center">
        <i class="fas fa-search fa-3x text-muted mb-3"></i>
        <h5 class="text-muted font-weight-bold">Không tìm thấy sản phẩm phù hợp.</h5>
        <a href="category.php<?= $category_id > 0 ? '?id=' . $category_id : '' ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3 mt-2 font-weight-bold">
          Xóa toàn bộ bộ lọc
        </a>
      </div>
    <?php endif; ?>
  </div>

  <!-- 5. Phân trang -->
  <?php if ($total_pages > 1): ?>
    <div class="d-flex justify-content-center mt-4">
      <ul class="pagination pagination-custom mb-0">
        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
          <a class="page-link" href="<?= filterUrl(['page' => $page - 1]) ?>">
            <i class="fas fa-chevron-left fa-xs"></i>
          </a>
        </li>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
              <a class="page-link" href="<?= filterUrl(['page' => $i]) ?>"><?= $i ?></a>
            </li>
          <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
            <li class="page-item disabled"><span class="page-link border-0">...</span></li>
          <?php endif; ?>
        <?php endfor; ?>
        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
          <a class="page-link" href="<?= filterUrl(['page' => $page + 1]) ?>">
            <i class="fas fa-chevron-right fa-xs"></i>
          </a>
        </li>
      </ul>
    </div>
  <?php endif; ?>
</div>

<!-- ==================== MODAL TẤT CẢ BỘ LỌC ==================== -->
<div class="modal fade" id="filterModal" tabindex="-1" role="dialog" aria-labelledby="filterModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content border-0 shadow-lg rounded-xl overflow-hidden">
      
      <div class="modal-header border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
        <h5 class="modal-title font-weight-bold text-dark mb-0" id="filterModalLabel">Tất cả bộ lọc</h5>
        <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 py-1 font-weight-bold text-secondary" data-dismiss="modal">
          &times; Đóng
        </button>
      </div>

      <form action="category.php" method="GET">
        <?php if ($category_id > 0): ?>
          <input type="hidden" name="id" value="<?= $category_id ?>">
        <?php endif; ?>
        <?php if (!empty($keyword)): ?>
          <input type="hidden" name="search" value="<?= htmlspecialchars($keyword) ?>">
        <?php endif; ?>
        <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">

        <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
          
          <?php if (!empty($available_brands)): ?>
            <div class="mb-4">
              <h6 class="font-weight-bold text-dark mb-2">Hãng</h6>
              <div class="d-flex flex-wrap" style="gap: 8px;">
                <?php foreach ($available_brands as $b): ?>
                  <label class="custom-filter-chip">
                    <input type="radio" name="brand" value="<?= $b['id'] ?>" <?= ($brand_id === (int)$b['id']) ? 'checked' : '' ?>>
                    <span><?= htmlspecialchars($b['name']) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <div class="mb-4">
            <h6 class="font-weight-bold text-dark mb-2">Mức giá</h6>
            <div class="d-flex flex-wrap" style="gap: 8px;">
              <label class="custom-filter-chip">
                <input type="radio" name="price_range" value="under_10" <?= ($price_range === 'under_10') ? 'checked' : '' ?>>
                <span>Dưới 10 triệu</span>
              </label>
              <label class="custom-filter-chip">
                <input type="radio" name="price_range" value="10_15" <?= ($price_range === '10_15') ? 'checked' : '' ?>>
                <span>10 - 15 triệu</span>
              </label>
              <label class="custom-filter-chip">
                <input type="radio" name="price_range" value="15_20" <?= ($price_range === '15_20') ? 'checked' : '' ?>>
                <span>15 - 20 triệu</span>
              </label>
              <label class="custom-filter-chip">
                <input type="radio" name="price_range" value="20_30" <?= ($price_range === '20_30') ? 'checked' : '' ?>>
                <span>20 - 30 triệu</span>
              </label>
              <label class="custom-filter-chip">
                <input type="radio" name="price_range" value="over_30" <?= ($price_range === 'over_30') ? 'checked' : '' ?>>
                <span>Trên 30 triệu</span>
              </label>
            </div>
          </div>

          <?php if (!empty($avail_rams)): ?>
            <div class="mb-4">
              <h6 class="font-weight-bold text-dark mb-2">Dung lượng RAM</h6>
              <div class="d-flex flex-wrap" style="gap: 8px;">
                <?php foreach ($avail_rams as $ram_item): ?>
                  <label class="custom-filter-chip">
                    <input type="radio" name="ram" value="<?= htmlspecialchars($ram_item) ?>" <?= ($filter_ram === $ram_item) ? 'checked' : '' ?>>
                    <span><?= htmlspecialchars($ram_item) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if (!empty($avail_cpus)): ?>
            <div class="mb-4">
              <h6 class="font-weight-bold text-dark mb-2">Bộ vi xử lý (CPU)</h6>
              <div class="d-flex flex-wrap" style="gap: 8px;">
                <?php foreach ($avail_cpus as $cpu_item): ?>
                  <label class="custom-filter-chip">
                    <input type="radio" name="cpu" value="<?= htmlspecialchars($cpu_item) ?>" <?= ($filter_cpu === $cpu_item) ? 'checked' : '' ?>>
                    <span><?= htmlspecialchars($cpu_item) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if (!empty($avail_gpus)): ?>
            <div class="mb-3">
              <h6 class="font-weight-bold text-dark mb-2">Card đồ họa (GPU)</h6>
              <div class="d-flex flex-wrap" style="gap: 8px;">
                <?php foreach ($avail_gpus as $gpu_item): ?>
                  <label class="custom-filter-chip">
                    <input type="radio" name="gpu" value="<?= htmlspecialchars($gpu_item) ?>" <?= ($filter_gpu === $gpu_item) ? 'checked' : '' ?>>
                    <span><?= htmlspecialchars($gpu_item) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

        </div>

        <div class="modal-footer bg-light py-3 px-4 d-flex justify-content-center" style="gap: 15px;">
          <a href="category.php<?= $category_id > 0 ? '?id=' . $category_id : '' ?>" class="btn btn-outline-danger font-weight-bold px-4 rounded-pill" style="min-width: 140px;">
            Bỏ chọn
          </a>
          <button type="submit" class="btn btn-primary font-weight-bold px-4 rounded-pill" style="min-width: 180px;">
            Xem <?= number_format($total_records, 0, ',', '.') ?> kết quả
          </button>
        </div>
      </form>

    </div>
  </div>
</div>

<style>
  .brand-scroll-container {
    overflow-x: auto;
    white-space: nowrap;
    padding-bottom: 6px;
  }
  .brand-scroll-container::-webkit-scrollbar {
    height: 4px;
  }
  .brand-scroll-container::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 4px;
  }
  .brand-list-scroll {
    display: flex;
    gap: 8px;
  }
  .btn-filter-modal {
    background: #ffffff;
    border: 1px solid #0071e3;
    color: #0071e3;
    font-weight: 600;
    font-size: 0.85rem;
    padding: 6px 14px;
    border-radius: 8px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    transition: all 0.2s ease;
    flex-shrink: 0;
  }
  .btn-filter-modal:hover {
    background: #f0f7ff;
  }
  .brand-pill-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 85px;
    height: 38px;
    padding: 4px 14px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #ffffff;
    color: #334155;
    font-size: 0.82rem;
    font-weight: 600;
    text-decoration: none !important;
    transition: all 0.15s ease;
    flex-shrink: 0;
  }
  .brand-pill-btn img {
    max-height: 22px;
    max-width: 65px;
    object-fit: contain;
  }
  .brand-pill-btn:hover {
    border-color: #0071e3;
    color: #0071e3;
  }
  .brand-pill-btn.active {
    border-color: #0071e3;
    background-color: #f0f7ff;
    box-shadow: 0 0 0 1px #0071e3;
  }
  .sort-bar-text .sort-link {
    color: #475569;
    font-size: 0.88rem;
    text-decoration: none !important;
    font-weight: 500;
    transition: color 0.15s ease;
  }
  .sort-bar-text .sort-link:hover {
    color: #0071e3;
  }
  .sort-bar-text .sort-link.active {
    color: #0071e3;
    font-weight: 700;
  }
  .custom-filter-chip {
    margin-bottom: 0;
    cursor: pointer;
  }
  .custom-filter-chip input {
    display: none;
  }
  .custom-filter-chip span {
    display: inline-block;
    padding: 6px 14px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #ffffff;
    color: #334155;
    font-size: 0.84rem;
    font-weight: 500;
    transition: all 0.15s ease;
  }
  .custom-filter-chip input:checked + span {
    border-color: #0071e3;
    background: #f0f7ff;
    color: #0071e3;
    font-weight: 700;
  }
  .custom-filter-chip:hover span {
    border-color: #0071e3;
  }
  .product-card {
    transition: all 0.2s ease-in-out;
    border-color: #f1f5f9 !important;
    border-radius: 12px;
  }
  .product-card:hover {
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    transform: translateY(-3px);
  }
  .product-thumb {
    height: 180px;
    object-fit: contain;
    transition: transform 0.3s ease;
  }
  .product-card:hover .product-thumb {
    transform: scale(1.04);
  }
  .product-title {
    font-size: 0.92rem;
    line-height: 1.4;
    min-height: 38px;
  }
  .badge-spec {
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #475569;
    font-size: 0.72rem;
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 500;
  }
  .btn-chip {
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    color: #334155;
    font-size: 0.78rem;
    font-weight: 500;
    padding: 2px 8px;
    border-radius: 6px;
    outline: none;
    cursor: pointer;
    transition: all 0.15s ease;
  }
  .btn-chip:hover {
    border-color: #0071e3;
    color: #0071e3;
  }
  .btn-chip.active {
    border-color: #0071e3;
    color: #0071e3;
    background-color: #f0f7ff;
    font-weight: 600;
  }
  .price-current {
    font-size: 1.05rem;
  }
  .pagination-custom {
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .pagination-custom .page-item .page-link {
    min-width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50rem !important;
    border: 1px solid #e2e8f0;
    color: #475569;
    background: #ffffff;
  }
  .pagination-custom .page-item.active .page-link {
    background-color: #0071e3;
    border-color: #0071e3;
    color: #ffffff;
  }
</style>

<script>
  document.querySelectorAll('.product-card').forEach(function(card) {
    var baseTitle = card.getAttribute('data-base-title');
    var titleEl = card.querySelector('.title-text');
    var saleEl = card.querySelector('.val-sale');
    var oldEl = card.querySelector('.val-old');
    var percentEl = card.querySelector('.val-percent');
    var oldWrap = card.querySelector('.price-old-wrap');
    var tagsWrap = card.querySelector('.tag-specs-container');

    card.querySelectorAll('.btn-chip').forEach(function(btn) {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        card.querySelectorAll('.btn-chip').forEach(b => b.classList.remove('active'));
        this.classList.add('active');

        var spec = this.getAttribute('data-spec');
        titleEl.textContent = baseTitle + ' ' + spec;
        saleEl.textContent = this.getAttribute('data-sale');

        var discount = parseInt(this.getAttribute('data-discount')) || 0;
        var percent = parseInt(this.getAttribute('data-percent')) || 0;
        if (discount > 0 && percent > 0) {
          oldEl.textContent = this.getAttribute('data-price') + '₫';
          percentEl.textContent = percent;
          oldWrap.classList.remove('d-none');
        } else {
          oldWrap.classList.add('d-none');
        }

        var cpu = this.getAttribute('data-cpu');
        var gpu = this.getAttribute('data-gpu');
        var ram = this.getAttribute('data-ram');
        var htmlTags = '';
        if (cpu) htmlTags += '<span class="badge-spec">' + cpu + '</span> ';
        if (gpu) htmlTags += '<span class="badge-spec">' + gpu + '</span> ';
        else if (ram) htmlTags += '<span class="badge-spec">' + ram + ' RAM</span> ';

        if (htmlTags !== '') {
          tagsWrap.innerHTML = htmlTags;
        }
      });
    });
  });
</script>

<?php 
include 'includes/footer.php'; 
?>