<?php
ini_set('display_errors', 0);
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once __DIR__ . '/database/dbhelper.php';

@execute("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''))");

// 1. Nhận tham số lọc từ URL
$category_id   = isset($_GET['id']) ? intval($_GET['id']) : 0;
$category_slug = trim($_GET['slug'] ?? '');
$brand_id      = isset($_GET['brand']) ? intval($_GET['brand']) : 0;
$brand_slug    = trim($_GET['brand_slug'] ?? '');
$sort          = $_GET['sort'] ?? 'hot';
$price_range   = $_GET['price_range'] ?? '';
$filter_ram    = trim($_GET['ram'] ?? '');
$filter_cpu    = trim($_GET['cpu'] ?? '');
$filter_gpu    = trim($_GET['gpu'] ?? '');
$keyword       = trim($_GET['search'] ?? '');

// Tìm danh mục qua Slug hoặc ID
$current_category = null;
if (!empty($category_slug)) {
    $safe_cslug = str_replace("'", "\'", $category_slug);
    $current_category = executeResult("SELECT * FROM `Category` WHERE `slug` = '$safe_cslug' AND `deleted` = 0 LIMIT 1", true);
    if ($current_category) {
        $category_id = intval($current_category['id']);
    }
} elseif ($category_id > 0) {
    $current_category = executeResult("SELECT * FROM `Category` WHERE `id` = $category_id AND `deleted` = 0 LIMIT 1", true);
}

// Tìm Hãng qua brand_slug nếu có
if (!empty($brand_slug) && $brand_id === 0) {
    $safe_bslug = str_replace("'", "\'", $brand_slug);
    $b_find = executeResult("SELECT id FROM `Brand` WHERE `slug` = '$safe_bslug' AND `deleted` = 0 LIMIT 1", true);
    if ($b_find) {
        $brand_id = intval($b_find['id']);
    }
}

// 2. Lấy danh sách Brand thuộc danh mục này
$brand_sql_where = "WHERE p.deleted = 0";
if ($category_id > 0) {
    $brand_sql_where .= " AND p.category_id = $category_id";
}
$available_brands = executeResult("SELECT DISTINCT b.id, b.name, b.slug, b.logo 
                                   FROM `Brand` b 
                                   INNER JOIN `Product` p ON b.id = p.brand_id 
                                   $brand_sql_where AND b.deleted = 0 
                                   ORDER BY b.name ASC");

// 3. Quét thông số cấu hình
$variants_meta = executeResult("SELECT DISTINCT v.ram, v.cpu, v.gpu 
                                FROM `Product_Variant` v 
                                INNER JOIN `Product` p ON v.product_id = p.id 
                                $brand_sql_where AND v.deleted = 0");

$avail_rams = !empty($variants_meta) ? array_values(array_filter(array_unique(array_column($variants_meta, 'ram')))) : [];
$avail_cpus = !empty($variants_meta) ? array_values(array_filter(array_unique(array_column($variants_meta, 'cpu')))) : [];
$avail_gpus = !empty($variants_meta) ? array_values(array_filter(array_unique(array_column($variants_meta, 'gpu')))) : [];

// 4. Xây dựng điều kiện lọc
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
    $where_clauses[] = "p.id IN (SELECT product_id FROM `Product_Variant` WHERE `ram` = '$safe_ram' AND `deleted` = 0)";
}
if (!empty($filter_cpu)) {
    $safe_cpu = str_replace("'", "\'", $filter_cpu);
    $where_clauses[] = "p.id IN (SELECT product_id FROM `Product_Variant` WHERE `cpu` = '$safe_cpu' AND `deleted` = 0)";
}
if (!empty($filter_gpu)) {
    $safe_gpu = str_replace("'", "\'", $filter_gpu);
    $where_clauses[] = "p.id IN (SELECT product_id FROM `Product_Variant` WHERE `gpu` = '$safe_gpu' AND `deleted` = 0)";
}

if ($price_range === 'under_10') {
    $where_clauses[] = "p.id IN (SELECT product_id FROM `Product_Variant` WHERE (price - discount) < 10000000 AND deleted = 0)";
} elseif ($price_range === '10_15') {
    $where_clauses[] = "p.id IN (SELECT product_id FROM `Product_Variant` WHERE (price - discount) BETWEEN 10000000 AND 15000000 AND deleted = 0)";
} elseif ($price_range === '15_20') {
    $where_clauses[] = "p.id IN (SELECT product_id FROM `Product_Variant` WHERE (price - discount) BETWEEN 15000000 AND 20000000 AND deleted = 0)";
} elseif ($price_range === '20_30') {
    $where_clauses[] = "p.id IN (SELECT product_id FROM `Product_Variant` WHERE (price - discount) BETWEEN 20000000 AND 30000000 AND deleted = 0)";
} elseif ($price_range === 'over_30') {
    $where_clauses[] = "p.id IN (SELECT product_id FROM `Product_Variant` WHERE (price - discount) > 30000000 AND deleted = 0)";
}

$where_sql = implode(' AND ', $where_clauses);

// Sắp xếp: Ưu tiên p.is_featured DESC khi chọn lọc 'hot' (Nổi bật)
$order_by = "ORDER BY p.is_featured DESC, p.id DESC";
if ($sort === 'sale') {
    $order_by = "ORDER BY max_discount DESC";
} elseif ($sort === 'newest') {
    $order_by = "ORDER BY p.id DESC";
} elseif ($sort === 'price_asc') {
    $order_by = "ORDER BY min_sale_price ASC";
} elseif ($sort === 'price_desc') {
    $order_by = "ORDER BY min_sale_price DESC";
}

// 5. Phân trang
$limit = 12;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;

$count_res = executeResult("SELECT COUNT(DISTINCT p.id) AS total FROM `Product` p WHERE $where_sql", true);
$total_records = intval($count_res['total'] ?? 0);
$total_pages = ceil($total_records / $limit);
if ($total_pages > 0 && $page > $total_pages) $page = $total_pages;

$start = ($page - 1) * $limit;
if ($start < 0) $start = 0;

// 6. Truy vấn danh sách sản phẩm lấy thêm p.is_featured
$sql_products = "SELECT p.id, p.title, p.slug, p.thumbnail, p.description, p.is_featured,
                        c.name AS category_name, 
                        b.name AS brand_name,
                        COALESCE(MIN(v.price - v.discount), 0) AS min_sale_price,
                        COALESCE(MIN(v.price), 0) AS min_original_price,
                        COALESCE(MAX(v.discount), 0) AS max_discount
                 FROM `Product` p
                 LEFT JOIN `Category` c ON p.category_id = c.id
                 LEFT JOIN `Brand` b ON p.brand_id = b.id
                 LEFT JOIN `Product_Variant` v ON p.id = v.product_id AND v.deleted = 0
                 WHERE $where_sql
                 GROUP BY p.id, p.title, p.slug, p.thumbnail, p.description, p.is_featured, c.name, b.name
                 $order_by
                 LIMIT $start, $limit";
$products = executeResult($sql_products);

$product_ids = !empty($products) ? array_column($products, 'id') : [];
$variants_by_product = [];
if (!empty($product_ids)) {
    $ids_str = implode(',', $product_ids);
    $variants_raw = executeResult("SELECT * FROM `Product_Variant` WHERE `product_id` IN ($ids_str) AND `deleted` = 0 ORDER BY `price` ASC");
    if (!empty($variants_raw)) {
        foreach ($variants_raw as $v) {
            $variants_by_product[$v['product_id']][] = $v;
        }
    }
}

// Hàm format đường dẫn ảnh tránh lỗi thư mục ảo
function fixImgUrl($path) {
    if (empty($path)) return 'https://via.placeholder.com/300x300?text=No+Image';
    if (strpos($path, 'http') === 0) return $path;
    return '/webbanhang/' . ltrim($path, '/');
}

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
    return '/webbanhang/category.php?' . http_build_query($params);
}

$page_title = ($current_category ? $current_category['name'] : 'Danh sách sản phẩm') . ' - NovaShop';
include 'includes/header.php';
?>

<base href="/webbanhang/">

<div class="container py-3">
  <div class="mb-3 small text-muted">
    <a href="index.php" class="text-secondary text-decoration-none">Trang chủ</a>
    <i class="fas fa-chevron-right mx-1" style="font-size: 0.65rem;"></i>
    <span class="text-dark font-weight-bold">
      <?= number_format($total_records, 0, ',', '.') ?> <?= $current_category ? htmlspecialchars($current_category['name']) : 'Sản phẩm' ?>
      <?= !empty($keyword) ? ' cho từ khóa "' . htmlspecialchars($keyword) . '"' : '' ?>
    </span>
  </div>

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
      <a href="<?= filterUrl(['brand' => null, 'brand_slug' => null]) ?>" 
         class="brand-pill-btn <?= ($brand_id === 0) ? 'active' : '' ?>">
        <span>Tất cả</span>
      </a>
      <?php if (!empty($available_brands)): ?>
        <?php foreach ($available_brands as $b): 
          $is_selected = ($brand_id === (int)$b['id']);
          $logo_src = fixImgUrl($b['logo']);
        ?>
          <a href="<?= filterUrl(['brand' => $is_selected ? null : $b['id'], 'brand_slug' => null]) ?>" 
             class="brand-pill-btn <?= $is_selected ? 'active' : '' ?>"
             title="<?= htmlspecialchars($b['name']) ?>">
            <?php if (!empty($b['logo'])): ?>
              <img src="<?= htmlspecialchars($logo_src) ?>" alt="<?= htmlspecialchars($b['name']) ?>">
            <?php else: ?>
              <span><?= htmlspecialchars($b['name']) ?></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="d-flex align-items-center mb-4 flex-wrap sort-bar-text">
    <span class="text-dark font-weight-medium mr-3" style="font-size: 0.88rem;">Sắp xếp theo:</span>
    
    <a href="<?= filterUrl(['sort' => 'hot']) ?>" class="sort-link <?= ($sort === 'hot') ? 'active' : '' ?>">
      <i class="fas fa-fire mr-1 text-danger"></i> Nổi bật
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

  <div class="row">
    <?php if (!empty($products)): ?>
      <?php foreach ($products as $prod): 
        $pid = $prod['id'];
        $variants = $variants_by_product[$pid] ?? [];
        
        $thumb_src = fixImgUrl($prod['thumbnail']);

        $first_v = !empty($variants) ? $variants[0] : null;
        $def_spec = $first_v ? ($first_v['storage'] ?: ($first_v['ram'] ?: '')) : '';
        $def_price = $first_v ? (int)$first_v['price'] : (int)$prod['min_original_price'];
        $def_discount = $first_v ? (int)$first_v['discount'] : (int)$prod['max_discount'];
        $def_sale = max(0, $def_price - $def_discount);
        $def_percent = ($def_price > 0 && $def_discount > 0) ? round(($def_discount / $def_price) * 100) : 0;

        $detail_url = !empty($prod['slug']) 
                      ? "san-pham/" . htmlspecialchars($prod['slug']) . ".html"
                      : "product-detail.php?id=" . $pid;

        $spec_counts = [];
        foreach ($variants as $v) {
            $key = $v['storage'] ?: ($v['ram'] ?: 'Bản');
            $spec_counts[$key] = ($spec_counts[$key] ?? 0) + 1;
        }
      ?>
        <div class="col-lg-3 col-md-4 col-6 mb-4">
          <div class="product-card border rounded p-3 bg-white h-100 d-flex flex-column position-relative" data-base-title="<?= htmlspecialchars($prod['title']) ?>">
            
            <!-- Huy hiệu Nổi bật nếu is_featured = 1 -->
            <?php if (!empty($prod['is_featured']) && (int)$prod['is_featured'] === 1): ?>
              <span class="badge badge-featured position-absolute shadow-sm" style="top: 10px; left: 10px; z-index: 2;">
                <i class="fas fa-fire mr-1"></i>Nổi bật
              </span>
            <?php endif; ?>

            <a href="<?= $detail_url ?>" class="d-block text-center py-2">
              <img src="<?= htmlspecialchars($thumb_src) ?>" alt="<?= htmlspecialchars($prod['title']) ?>" class="img-fluid product-thumb">
            </a>

            <h6 class="product-title font-weight-normal text-dark mb-2 mt-1">
              <a href="<?= $detail_url ?>" class="text-dark text-decoration-none">
                <span class="title-text"><?= htmlspecialchars($prod['title']) ?><?= $def_spec ? ' ' . htmlspecialchars($def_spec) : '' ?></span>
              </a>
            </h6>

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
        <a href="<?= filterUrl(['id' => $category_id, 'slug' => null]) ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3 mt-2 font-weight-bold">
          Xóa toàn bộ bộ lọc
        </a>
      </div>
    <?php endif; ?>
  </div>

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

<!-- Modal bộ lọc -->
<div class="modal fade" id="filterModal" tabindex="-1" role="dialog" aria-labelledby="filterModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content border-0 shadow-lg rounded-xl overflow-hidden">
      <div class="modal-header border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
        <h5 class="modal-title font-weight-bold text-dark mb-0" id="filterModalLabel">Tất cả bộ lọc</h5>
        <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 py-1 font-weight-bold text-secondary" data-dismiss="modal">
          &times; Đóng
        </button>
      </div>

      <form action="/webbanhang/category.php" method="GET">
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
          <a href="<?= filterUrl(['id' => $category_id, 'slug' => null]) ?>" class="btn btn-outline-danger font-weight-bold px-4 rounded-pill" style="min-width: 140px;">
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
  .brand-scroll-container { overflow-x: auto; white-space: nowrap; padding-bottom: 6px; }
  .brand-scroll-container::-webkit-scrollbar { height: 4px; }
  .brand-scroll-container::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 4px; }
  .brand-list-scroll { display: flex; gap: 8px; }
  .btn-filter-modal {
    background: #ffffff; border: 1px solid #0071e3; color: #0071e3; font-weight: 600; font-size: 0.85rem;
    padding: 6px 14px; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center;
    transition: all 0.2s ease; flex-shrink: 0;
  }
  .btn-filter-modal:hover { background: #f0f7ff; }
  .brand-pill-btn {
    display: inline-flex; align-items: center; justify-content: center; min-width: 85px; height: 38px;
    padding: 4px 14px; border: 1px solid #e2e8f0; border-radius: 8px; background: #ffffff; color: #334155;
    font-size: 0.82rem; font-weight: 600; text-decoration: none !important; transition: all 0.15s ease; flex-shrink: 0;
  }
  .brand-pill-btn img { max-height: 22px; max-width: 65px; object-fit: contain; }
  .brand-pill-btn:hover { border-color: #0071e3; color: #0071e3; }
  .brand-pill-btn.active { border-color: #0071e3; background-color: #f0f7ff; box-shadow: 0 0 0 1px #0071e3; }
  .sort-bar-text .sort-link { color: #475569; font-size: 0.88rem; text-decoration: none !important; font-weight: 500; }
  .sort-bar-text .sort-link:hover, .sort-bar-text .sort-link.active { color: #0071e3; font-weight: 700; }
  .custom-filter-chip { margin-bottom: 0; cursor: pointer; }
  .custom-filter-chip input { display: none; }
  .custom-filter-chip span {
    display: inline-block; padding: 6px 14px; border: 1px solid #e2e8f0; border-radius: 8px;
    background: #ffffff; color: #334155; font-size: 0.84rem; font-weight: 500; transition: all 0.15s ease;
  }
  .custom-filter-chip input:checked + span { border-color: #0071e3; background: #f0f7ff; color: #0071e3; font-weight: 700; }
  .product-card { transition: all 0.2s ease-in-out; border-color: #f1f5f9 !important; border-radius: 12px; }
  .product-card:hover { box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08); transform: translateY(-3px); }
  .product-thumb { height: 180px; object-fit: contain; transition: transform 0.3s ease; }
  .product-card:hover .product-thumb { transform: scale(1.04); }
  .product-title { font-size: 0.92rem; line-height: 1.4; min-height: 38px; }
  .badge-spec { background-color: #f8fafc; border: 1px solid #e2e8f0; color: #475569; font-size: 0.72rem; padding: 2px 6px; border-radius: 4px; font-weight: 500; }
  
  /* Badge Nổi Bật */
  .badge-featured {
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    color: #ffffff;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
  }

  .btn-chip {
    background-color: #ffffff; border: 1px solid #e2e8f0; color: #334155; font-size: 0.78rem;
    font-weight: 500; padding: 2px 8px; border-radius: 6px; outline: none; cursor: pointer; transition: all 0.15s ease;
  }
  .btn-chip:hover, .btn-chip.active { border-color: #0071e3; color: #0071e3; background-color: #f0f7ff; font-weight: 600; }
  .price-current { font-size: 1.05rem; }
  .pagination-custom { display: flex; align-items: center; gap: 6px; }
  .pagination-custom .page-item .page-link {
    min-width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center;
    border-radius: 50rem !important; border: 1px solid #e2e8f0; color: #4b5563; background: #ffffff;
  }
  .pagination-custom .page-item.active .page-link { background-color: #0071e3; border-color: #0071e3; color: #ffffff; }
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