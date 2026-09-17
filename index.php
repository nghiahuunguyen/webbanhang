<?php 
ini_set('display_errors', 0);
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once __DIR__ . '/database/dbhelper.php';

@execute("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''))");

// 1. Cấu hình Banner Slide từ CSDL
$cfg_slide = executeResult("SELECT key_name, key_value FROM `System_Settings` WHERE key_name IN ('banner_autoplay', 'banner_interval')");
$c_data = [];
if (!empty($cfg_slide)) {
    foreach ($cfg_slide as $c) { $c_data[$c['key_name']] = $c['key_value']; }
}
$autoplay_mode = ($c_data['banner_autoplay'] ?? '1') === '1' ? 'carousel' : 'false';
$interval_ms   = intval($c_data['banner_interval'] ?? 4000);

$banner_list = executeResult("SELECT * FROM `Banner` WHERE is_active = 1 AND deleted = 0 ORDER BY sort_order ASC, id DESC");
if (empty($banner_list)) {
    $banner_list = [
        ['image_url' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=1200&q=80'],
        ['image_url' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?auto=format&fit=crop&w=1200&q=80']
    ];
}

// 2. Hàm format đường dẫn ảnh
function fixImgUrl($path) {
    if (empty($path)) return 'https://via.placeholder.com/300x300?text=No+Image';
    if (strpos($path, 'http') === 0) return $path;
    return '/webbanhang/' . ltrim($path, '/');
}

// 3. Chỉ lấy 8 sản phẩm Nổi bật + tính trung bình rating thật từ Product_Review
$sql_featured = "SELECT p.id, p.title, p.slug, p.thumbnail, p.description,
                        c.name AS category_name, 
                        b.name AS brand_name,
                        COALESCE(MIN(v.price - v.discount), 0) AS min_sale_price,
                        COALESCE(MIN(v.price), 0) AS min_original_price,
                        COALESCE(MAX(v.discount), 0) AS max_discount,
                        COALESCE(AVG(r.rating), 5.0) AS avg_rating,
                        COUNT(r.id) AS total_reviews
                 FROM `Product` p
                 LEFT JOIN `Category` c ON p.category_id = c.id
                 LEFT JOIN `Brand` b ON p.brand_id = b.id
                 LEFT JOIN `Product_Variant` v ON p.id = v.product_id AND v.deleted = 0
                 LEFT JOIN `Product_Review` r ON p.id = r.product_id AND r.deleted = 0
                 WHERE p.deleted = 0 AND p.is_featured = 1
                 GROUP BY p.id, p.title, p.slug, p.thumbnail, p.description, c.name, b.name
                 ORDER BY p.id DESC
                 LIMIT 8";
$featured_products = executeResult($sql_featured);

// 4. Lấy danh sách Variant theo các sản phẩm nổi bật
$pids = !empty($featured_products) ? array_column($featured_products, 'id') : [];
$variants_by_product = [];
if (!empty($pids)) {
    $ids_str = implode(',', $pids);
    $variants_raw = executeResult("SELECT * FROM `Product_Variant` WHERE `product_id` IN ($ids_str) AND `deleted` = 0 ORDER BY `price` ASC");
    if (!empty($variants_raw)) {
        foreach ($variants_raw as $v) {
            $variants_by_product[$v['product_id']][] = $v;
        }
    }
}

$page_title = 'NovaShop - Sản Phẩm Nổi Bật';
include 'includes/header.php'; 
?>

<base href="/webbanhang/">

<style>
  .rounded-2xl { border-radius: 1.25rem !important; }
  .shadow-soft { box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05); }
  .rounded-pill-custom { border-radius: 50rem !important; }

  /* Banner Slide */
  #techHeroCarousel { border-radius: 1.25rem; overflow: hidden; }
  .hero-banner-img { width: 100%; height: 340px; object-fit: cover; object-position: center; display: block; }
  @media (max-width: 992px) { .hero-banner-img { height: 250px; } }
  @media (max-width: 576px) { .hero-banner-img { height: 160px; } }

  #techHeroCarousel .carousel-control-prev,
  #techHeroCarousel .carousel-control-next {
    width: 40px; height: 40px; background: rgba(0, 0, 0, 0.3); border-radius: 50%;
    top: 50%; transform: translateY(-50%); margin: 0 15px; transition: all 0.2s ease;
  }
  #techHeroCarousel .carousel-control-prev:hover,
  #techHeroCarousel .carousel-control-next:hover { background: rgba(0, 0, 0, 0.6); }
  #techHeroCarousel .carousel-indicators li { height: 4px; border-radius: 2px; margin: 0 3px; background-color: rgba(255, 255, 255, 0.5); border: none; }
  #techHeroCarousel .carousel-indicators .active { background-color: #fff; width: 24px; }

  /* Product Card */
  .product-card { transition: all 0.2s ease-in-out; border-color: #f1f5f9 !important; border-radius: 12px; }
  .product-card:hover { box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08); transform: translateY(-3px); }
  .product-thumb { height: 180px; object-fit: contain; transition: transform 0.3s ease; }
  .product-card:hover .product-thumb { transform: scale(1.04); }
  .product-title { font-size: 0.92rem; line-height: 1.4; min-height: 38px; }
  .badge-spec { background-color: #f8fafc; border: 1px solid #e2e8f0; color: #475569; font-size: 0.72rem; padding: 2px 6px; border-radius: 4px; font-weight: 500; }
  .btn-chip {
    background-color: #ffffff; border: 1px solid #e2e8f0; color: #334155; font-size: 0.78rem;
    font-weight: 500; padding: 2px 8px; border-radius: 6px; outline: none; cursor: pointer; transition: all 0.15s ease;
  }
  .btn-chip:hover, .btn-chip.active { border-color: #0071e3; color: #0071e3; background-color: #f0f7ff; font-weight: 600; }
  .price-current { font-size: 1.05rem; }
  
  .btn-view-more {
    border: 1px solid #0071e3;
    color: #0071e3;
    background: #ffffff;
    font-weight: 600;
    padding: 10px 32px;
    border-radius: 50rem;
    transition: all 0.2s ease;
    text-decoration: none !important;
    display: inline-flex;
    align-items: center;
  }
  .btn-view-more:hover {
    background-color: #0071e3;
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(0, 113, 227, 0.25);
    transform: translateY(-2px);
  }
</style>

<main class="container my-4 my-md-5">
  
  <!-- 1. BANNER SLIDE -->
  <div id="techHeroCarousel" class="carousel slide mb-4 shadow-soft rounded-2xl overflow-hidden" 
       data-ride="<?= $autoplay_mode ?>" 
       data-interval="<?= $autoplay_mode === 'false' ? 'false' : $interval_ms ?>" 
       data-pause="hover">

    <?php if (count($banner_list) > 1): ?>
      <ol class="carousel-indicators">
        <?php foreach ($banner_list as $index => $b): ?>
          <li data-target="#techHeroCarousel" data-slide-to="<?= $index ?>" class="<?= ($index === 0) ? 'active' : '' ?>"></li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>

    <div class="carousel-inner">
      <?php foreach ($banner_list as $index => $b): 
          $img = $b['image_url'];
          $img_src = (strpos($img, 'http') === 0) ? $img : fixImgUrl($img);
      ?>
        <div class="carousel-item <?= ($index === 0) ? 'active' : '' ?>">
          <img src="<?= htmlspecialchars($img_src) ?>" class="d-block w-100 hero-banner-img" alt="Banner <?= $index + 1 ?>">
        </div>
      <?php endforeach; ?>
    </div>

    <?php if (count($banner_list) > 1): ?>
      <a class="carousel-control-prev" href="#techHeroCarousel" role="button" data-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="sr-only">Trước</span>
      </a>
      <a class="carousel-control-next" href="#techHeroCarousel" role="button" data-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="sr-only">Sau</span>
      </a>
    <?php endif; ?>
  </div>

  <!-- 2. KHỐI CAM KẾT -->
  <div class="row text-center mb-5 g-3">
    <div class="col-6 col-md-3 mb-3 mb-md-0">
      <div class="p-3 bg-white rounded-2xl shadow-soft h-100 border">
        <i class="fas fa-check-double text-primary fa-2x mb-2"></i>
        <h6 class="font-weight-bold mb-1">Chính Hãng 100%</h6>
        <span class="text-muted small">Đầy đủ chứng nhận VAT</span>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-3 mb-md-0">
      <div class="p-3 bg-white rounded-2xl shadow-soft h-100 border">
        <i class="fas fa-tools text-primary fa-2x mb-2"></i>
        <h6 class="font-weight-bold mb-1">Bảo Hành 1 Đổi 1</h6>
        <span class="text-muted small">Lỗi phần cứng trong 30 ngày</span>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-3 mb-md-0">
      <div class="p-3 bg-white rounded-2xl shadow-soft h-100 border">
        <i class="fas fa-bolt text-warning fa-2x mb-2"></i>
        <h6 class="font-weight-bold mb-1">Giao Nhanh Toàn Quốc</h6>
        <span class="text-muted small">Kiểm tra trước khi thanh toán</span>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-3 mb-md-0">
      <div class="p-3 bg-white rounded-2xl shadow-soft h-100 border">
        <i class="fas fa-credit-card text-success fa-2x mb-2"></i>
        <h6 class="font-weight-bold mb-1">Trả Góp 0%</h6>
        <span class="text-muted small">Thủ tục duyệt hồ sơ nhanh</span>
      </div>
    </div>
  </div>

  <!-- 3. KHU VỰC: SẢN PHẨM NỔI BẬT (CHỈ HIỂN THỊ 8 MÁY) -->
  <section class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <span class="badge badge-warning text-dark px-2 py-1 small font-weight-bold">HOT SELECTION</span>
        <h3 class="font-weight-bold mt-1 mb-0 text-dark">
          <i class="fas fa-fire text-danger mr-2"></i>Sản Phẩm Nổi Bật
        </h3>
      </div>
      <a href="category.php?sort=hot" class="btn btn-outline-primary btn-sm rounded-pill-custom px-3 font-weight-bold">
        Xem tất cả &rarr;
      </a>
    </div>

    <div class="row">
      <?php if (!empty($featured_products)): ?>
        <?php foreach ($featured_products as $prod): 
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

          $rating_val = round((float)($prod['avg_rating'] ?? 5.0), 1);
          $review_cnt = intval($prod['total_reviews'] ?? 0);
        ?>
          <div class="col-lg-3 col-md-4 col-6 mb-4">
            <div class="product-card border rounded p-3 bg-white h-100 d-flex flex-column" data-base-title="<?= htmlspecialchars($prod['title']) ?>">
              
              <a href="<?= $detail_url ?>" class="d-block text-center py-2">
                <img src="<?= htmlspecialchars($thumb_src) ?>" alt="<?= htmlspecialchars($prod['title']) ?>" class="img-fluid product-thumb">
              </a>

              <h6 class="product-title font-weight-normal text-dark mb-2 mt-1">
                <a href="<?= $detail_url ?>" class="text-dark text-decoration-none">
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

              <!-- Nút chọn các phiên bản cấu hình -->
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

              <!-- Giá tiền -->
              <div class="mt-auto pt-2">
                <div class="price-current text-danger font-weight-bold">
                  <span class="val-sale"><?= $def_sale > 0 ? number_format($def_sale, 0, ',', '.') : 'Liên hệ' ?></span>₫
                </div>
                <div class="price-old-wrap text-muted small <?= ($def_discount <= 0 || $def_percent <= 0) ? 'd-none' : '' ?>">
                  <span class="val-old" style="text-decoration: line-through;"><?= number_format($def_price, 0, ',', '.') ?>₫</span>
                  <span class="text-danger font-weight-bold ml-1">-<span class="val-percent"><?= $def_percent ?></span>%</span>
                </div>
              </div>

              <!-- Hiển thị sao và số lượt đánh giá thật -->
              <div class="d-flex align-items-center mt-2 pt-2 border-top small text-muted">
                <i class="fas fa-star text-warning mr-1"></i>
                <span class="font-weight-bold text-dark mr-1"><?= number_format($rating_val, 1) ?></span>
                <span class="text-muted mr-1">(<?= $review_cnt ?>)</span>
                <span class="text-secondary ml-auto">• <?= htmlspecialchars($prod['brand_name'] ?? 'Chính hãng') ?></span>
              </div>

            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-12 text-center py-5 text-muted">
          <i class="fas fa-box-open fa-3x mb-3 text-black-50"></i>
          <p class="font-weight-bold">Chưa có sản phẩm nào được đánh dấu nổi bật trong Admin.</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- NÚT XEM THÊM CHUYỂN QUA TRANG DANH MỤC SẢN PHẨM -->
    <div class="text-center mt-3">
      <a href="category.php" class="btn-view-more shadow-sm">
        <span>Xem thêm sản phẩm</span>
        <i class="fas fa-chevron-right ml-2" style="font-size: 0.8rem;"></i>
      </a>
    </div>
  </section>

</main>

<!-- SCRIPT CHỌN BIẾN THỂ -->
<script>
  document.querySelectorAll('.product-card').forEach(function(card) {
    var baseTitle = card.getAttribute('data-base-title');
    var titleEl   = card.querySelector('.title-text');
    var saleEl    = card.querySelector('.val-sale');
    var oldEl     = card.querySelector('.val-old');
    var percentEl = card.querySelector('.val-percent');
    var oldWrap   = card.querySelector('.price-old-wrap');
    var tagsWrap  = card.querySelector('.tag-specs-container');

    card.querySelectorAll('.btn-chip').forEach(function(btn) {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        card.querySelectorAll('.btn-chip').forEach(b => b.classList.remove('active'));
        this.classList.add('active');

        var spec = this.getAttribute('data-spec');
        titleEl.textContent = baseTitle + ' ' + spec;
        saleEl.textContent = this.getAttribute('data-sale');

        var discount = parseInt(this.getAttribute('data-discount')) || 0;
        var percent  = parseInt(this.getAttribute('data-percent')) || 0;
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