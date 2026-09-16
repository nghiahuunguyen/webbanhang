<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../database/dbhelper.php';

// 1. KIỂM TRA TRẠNG THÁI ĐÓNG / MỞ WEB TỪ SYSTEM_SETTINGS
$site_settings_raw = executeResult("SELECT `key_name`, `key_value` FROM `System_Settings`");
$site_cfg = [];
if (!empty($site_settings_raw)) {
    foreach ($site_settings_raw as $s) {
        $site_cfg[$s['key_name']] = $s['key_value'];
    }
}

$site_status     = $site_cfg['site_status'] ?? '1';
$maintenance_msg = $site_cfg['maintenance_message'] ?? 'Website đang bảo trì nâng cấp, vui lòng quay lại sau!';
$is_admin        = isset($_SESSION['user']) && in_array((int)($_SESSION['user']['role_id'] ?? 0), [1, 3]);

if ($site_status === '0' && !$is_admin) {
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Website Đang Bảo Trì - <?= htmlspecialchars($site_cfg['site_name'] ?? 'NovaShop') ?></title>
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
      <style>
        body { background: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
        .m-card { background: #fff; border-radius: 20px; padding: 45px 35px; max-width: 520px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; }
        .m-icon { width: 85px; height: 85px; background: #eff6ff; color: #2563eb; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 2.2rem; margin-bottom: 25px; }
      </style>
    </head>
    <body>
      <div class="m-card">
        <div class="m-icon"><i class="fas fa-tools"></i></div>
        <h3 class="font-weight-bold text-dark mb-3">HỆ THỐNG ĐANG BẢO TRÌ</h3>
        <p class="text-muted mb-4" style="line-height: 1.6;"><?= htmlspecialchars($maintenance_msg) ?></p>
        <div class="alert alert-light border small text-muted mb-0">
          <i class="fas fa-clock mr-1 text-primary"></i> Chúng tôi sẽ sớm quay trở lại phục vụ bạn!
        </div>
      </div>
    </body>
    </html>
    <?php
    exit();
}

// 2. LẤY DANH MỤC SẢN PHẨM HỢP LỆ
$nav_categories = executeResult("SELECT * FROM `Category` WHERE deleted = 0 ORDER BY id ASC");

// 3. TÍNH SỐ LƯỢNG GIỎ HÀNG
$cart_badge_count = 0;
if (isset($_SESSION['user']['id'])) {
    $uid = intval($_SESSION['user']['id']);
    $countRow = executeResult("SELECT SUM(num) AS total FROM `Cart` WHERE user_id = $uid", true);
    $cart_badge_count = intval($countRow['total'] ?? 0);
} elseif (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $cItem) {
        $cart_badge_count += intval($cItem['num'] ?? 0);
    }
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title><?= htmlspecialchars($page_title ?? ($site_cfg['site_name'] ?? 'NovaShop') . ' - Cửa Hàng Công Nghệ Hiện Đại') ?></title>

  <!-- Thẻ BASE giúp định tuyến ảnh, link và CSS không bị lỗi khi dùng /danh-muc/phone -->
  <base href="/webbanhang/">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" crossorigin="anonymous">

  <style>
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      background-color: #f8fafc;
      color: #0f172a;
    }

    .rounded-2xl { border-radius: 1.25rem !important; }
    .rounded-pill-custom { border-radius: 50rem !important; }
    
    .shadow-soft {
      box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
    }

    .btn-icon {
      width: 42px;
      height: 42px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
    }

    .dropdown-menu {
      border: 1px solid #e2e8f0;
      box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    }
    .dropdown-item:active, .dropdown-item:hover {
      background-color: #f1f5f9;
      color: #2563eb;
    }

    /* Giao diện khung kết quả gợi ý tìm kiếm */
    .search-suggest-box {
      position: absolute;
      top: 52px;
      left: 0;
      right: 0;
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 14px;
      box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
      z-index: 9999;
      display: none;
      max-height: 420px;
      overflow-y: auto;
    }
    .suggest-item {
      display: flex;
      align-items: center;
      padding: 10px 14px;
      border-bottom: 1px solid #f1f5f9;
      text-decoration: none !important;
      color: #1e293b;
      transition: background-color 0.15s ease;
    }
    .suggest-item:last-child {
      border-bottom: none;
    }
    .suggest-item:hover {
      background-color: #f8fafc;
    }
    .suggest-thumb {
      width: 44px;
      height: 44px;
      object-fit: contain;
      border-radius: 6px;
      background: #f8fafc;
      padding: 2px;
      margin-right: 12px;
      flex-shrink: 0;
    }
    .suggest-title {
      font-size: 0.88rem;
      font-weight: 600;
      color: #0f172a;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      line-height: 1.3;
    }
    .suggest-price {
      font-size: 0.82rem;
      font-weight: 700;
      color: #dc2626;
      margin-top: 2px;
    }
  </style>
</head>
<body>

  <!-- ==================== HEADER & TOPBAR ==================== -->
  <header class="bg-white border-bottom shadow-sm sticky-top">
    <!-- Topbar -->
    <div class="container py-3">
      <div class="row align-items-center">
        <!-- Logo -->
        <div class="col-md-3 col-6">
          <a class="font-weight-bold text-dark h4 mb-0 text-decoration-none d-flex align-items-center" href="index.php">
            <span class="badge badge-primary mr-2 rounded-circle p-2" style="background-color: #2563eb;"><i class="fas fa-cube text-white"></i></span>
            NOVA<span class="text-primary font-weight-bold" style="color: #2563eb !important;">SHOP</span>
          </a>
        </div>

        <!-- Thanh tìm kiếm tích hợp gợi ý live search -->
        <div class="col-md-6 d-none d-md-block">
          <div class="position-relative" id="searchWrapper">
            <form action="category.php" method="GET" id="searchForm" autocomplete="off">
              <input type="text" name="search" id="inputSearchHeader" class="form-control rounded-pill-custom bg-light border-0 py-2 pl-4 pr-5 text-sm" placeholder="Tìm kiếm điện thoại, laptop, phụ kiện..." style="height: 46px;" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
              <button class="btn btn-primary rounded-circle position-absolute" style="top: 4px; right: 5px; width: 38px; height: 38px; padding: 0; background-color: #2563eb; border-color: #2563eb;" type="submit">
                <i class="fas fa-search"></i>
              </button>
            </form>

            <!-- Hộp danh sách sản phẩm gợi ý -->
            <div id="searchSuggestBox" class="search-suggest-box"></div>
          </div>
        </div>

        <!-- User & Giỏ hàng -->
        <div class="col-md-3 col-6 text-right d-flex align-items-center justify-content-end">
          <?php if (isset($_SESSION['user'])): ?>
            <a href="profile.php" class="text-dark small font-weight-bold mr-2 text-decoration-none text-truncate d-none d-sm-inline" style="max-width: 120px;" title="Hồ sơ cá nhân">
              <?= htmlspecialchars($_SESSION['user']['fullname']) ?>
            </a>
            <a href="profile.php" class="btn btn-light btn-icon text-dark mr-2 border shadow-sm" title="Hồ sơ">
              <i class="far fa-user"></i>
            </a>
          <?php else: ?>
            <a href="login.php" class="btn btn-light btn-icon text-dark mr-2 border shadow-sm" title="Đăng nhập">
              <i class="far fa-user"></i>
            </a>
          <?php endif; ?>

          <a href="cart.php" class="btn btn-primary btn-icon position-relative shadow-sm" style="background-color: #2563eb; border-color: #2563eb;" title="Giỏ hàng">
            <i class="fas fa-shopping-bag"></i>
            <?php if ($cart_badge_count > 0): ?>
              <span class="badge badge-danger position-absolute font-weight-bold" style="top: -4px; right: -4px; border-radius: 10px; font-size: 0.7rem; min-width: 18px; line-height: 1.2; padding: 3px 5px;">
                <?= $cart_badge_count > 99 ? '99+' : $cart_badge_count ?>
              </span>
            <?php endif; ?>
          </a>
        </div>
      </div>
    </div>

    <!-- Thanh Menu điều hướng -->
    <nav class="navbar navbar-expand-md navbar-light bg-white border-top py-2">
      <div class="container">
        <button class="navbar-toggler border-0" type="button" data-toggle="collapse" data-target="#productNav">
          <i class="fas fa-bars"></i>
        </button>

        <div class="collapse navbar-collapse" id="productNav">
          <ul class="navbar-nav font-weight-medium">
            <li class="nav-item <?= ($current_page === 'index.php') ? 'active' : '' ?>">
              <a class="nav-link <?= ($current_page === 'index.php') ? 'text-primary font-weight-bold' : 'text-dark' ?>" href="index.php">Trang chủ</a>
            </li>
            
            <li class="nav-item dropdown <?= ($current_page === 'category.php') ? 'active' : '' ?>">
              <a class="nav-link dropdown-toggle text-dark font-weight-semibold" href="#" id="catDrop" data-toggle="dropdown">
                Danh mục sản phẩm
              </a>
              <div class="dropdown-menu border-0 shadow-soft rounded-2xl p-2" aria-labelledby="catDrop" style="min-width: 220px;">
                <?php if (!empty($nav_categories)): ?>
                  <?php foreach ($nav_categories as $c): 
                    // Tự động tạo link danh-muc/slug nếu có slug, ngược lại dùng category.php?id=...[cite: 10, 11]
                    $cat_url = !empty($c['slug']) 
                               ? "/webbanhang/danh-muc/" . urlencode($c['slug'])
                               : "/webbanhang/category.php?id=" . $c['id'];
                  ?>
                    <a class="dropdown-item rounded-pill-custom py-2" href="<?= $cat_url ?>">
                      <i class="fas fa-angle-right text-primary mr-2"></i>
                      <?= htmlspecialchars($c['name']) ?>
                    </a>
                  <?php endforeach; ?>
                <?php else: ?>
                  <span class="dropdown-item text-muted small">Đang cập nhật...</span>
                <?php endif; ?>

                <div class="dropdown-divider"></div>
                <a class="dropdown-item rounded-pill-custom py-2 text-primary font-weight-bold" href="/webbanhang/category.php">
                  <i class="fas fa-th-large mr-2"></i>Xem tất cả
                </a>
              </div>
            </li>

            <li class="nav-item <?= ($current_page === 'feedback.php') ? 'active' : '' ?>">
              <a class="nav-link <?= ($current_page === 'feedback.php') ? 'text-primary font-weight-bold' : 'text-secondary' ?>" href="feedback.php">
                <i class="far fa-comment-dots text-primary mr-1"></i> Phản hồi
              </a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
  </header>

  <!-- Script xử lý gợi ý sản phẩm tự động -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      var searchInput = document.getElementById('inputSearchHeader');
      var suggestBox  = document.getElementById('searchSuggestBox');
      var debounceTimer;

      if (!searchInput || !suggestBox) return;

      searchInput.addEventListener('input', function() {
        var query = this.value.trim();
        clearTimeout(debounceTimer);

        if (query.length === 0) {
          suggestBox.innerHTML = '';
          suggestBox.style.display = 'none';
          return;
        }

        // Chờ 250ms sau khi dừng gõ để gọi ajax
        debounceTimer = setTimeout(function() {
          fetch('search.php?q=' + encodeURIComponent(query))
            .then(function(res) { return res.json(); })
            .then(function(data) {
              if (!Array.isArray(data) || data.length === 0) {
                suggestBox.innerHTML = '<div class="p-3 text-muted small text-center"><i class="fas fa-search mr-1"></i> Không tìm thấy sản phẩm phù hợp.</div>';
                suggestBox.style.display = 'block';
                return;
              }

              var html = '';
              data.forEach(function(item) {
                html += `
                  <a href="${item.url}" class="suggest-item">
                    <img src="${item.thumb}" class="suggest-thumb border" alt="${item.title}">
                    <div style="flex: 1; overflow: hidden;">
                      <div class="suggest-title">${item.title}</div>
                      <div class="suggest-price">${item.price}</div>
                    </div>
                  </a>
                `;
              });

              html += `
                <div class="p-2 text-center bg-light border-top">
                  <a href="category.php?search=${encodeURIComponent(query)}" class="small font-weight-bold text-primary text-decoration-none">
                    Xem tất cả kết quả cho "${query}" &rarr;
                  </a>
                </div>
              `;

              suggestBox.innerHTML = html;
              suggestBox.style.display = 'block';
            })
            .catch(function(err) {
              suggestBox.style.display = 'none';
            });
        }, 250);
      });

      // Tự động đóng dropdown khi click chuột ra ngoài
      document.addEventListener('click', function(e) {
        var wrapper = document.getElementById('searchWrapper');
        if (wrapper && !wrapper.contains(e.target)) {
          suggestBox.style.display = 'none';
        }
      });

      // Mở lại kết quả nếu click vào ô input và đã có nội dung
      searchInput.addEventListener('focus', function() {
        if (suggestBox.children.length > 0 && this.value.trim() !== '') {
          suggestBox.style.display = 'block';
        }
      });
    });
  </script>