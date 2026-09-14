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

// Nếu web tắt (status = 0) và không phải Admin -> Dừng và hiện màn hình bảo trì
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
$nav_categories = executeResult("SELECT * FROM `Category` WHERE deleted = 0 ORDER BY name ASC");

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
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title><?= htmlspecialchars($page_title ?? ($site_cfg['site_name'] ?? 'NovaShop') . ' - Cửa Hàng Công Nghệ Hiện Đại') ?></title>

  <!-- Google Font: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Bootstrap 4 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" crossorigin="anonymous">
  <!-- Font Awesome 5 -->
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
    .shadow-hover {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .shadow-hover:hover {
      transform: translateY(-6px);
      box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.1), 0 8px 10px -6px rgba(15, 23, 42, 0.06);
    }

    .hero-banner {
      height: 380px;
      object-fit: cover;
    }
    .banner-overlay {
      background: linear-gradient(180deg, rgba(0,0,0,0.1) 0%, rgba(15, 23, 42, 0.8) 100%);
    }

    .product-img-wrap {
      overflow: hidden;
      border-radius: 1.25rem 1.25rem 0 0;
      position: relative;
    }
    .product-img {
      height: 240px;
      object-fit: cover;
      transition: transform 0.4s ease;
    }
    .card:hover .product-img {
      transform: scale(1.06);
    }

    .btn-icon {
      width: 42px;
      height: 42px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
    }

    .badge-pill-soft {
      padding: 0.35rem 0.75rem;
      font-weight: 600;
      font-size: 0.75rem;
      border-radius: 50rem;
    }

    /* Đảm bảo dropdown hiển thị đẹp */
    .dropdown-menu {
      border: 1px solid #e2e8f0;
      box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    }
    .dropdown-item:active, .dropdown-item:hover {
      background-color: #f1f5f9;
      color: #2563eb;
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

        <!-- Thanh tìm kiếm -->
        <div class="col-md-6 d-none d-md-block">
          <form class="position-relative" action="search.php" method="GET">
            <input type="text" name="q" class="form-control rounded-pill-custom bg-light border-0 py-2 pl-4 pr-5 text-sm" placeholder="Tìm kiếm điện thoại, laptop, phụ kiện..." style="height: 46px;">
            <button class="btn btn-primary rounded-circle position-absolute" style="top: 4px; right: 5px; width: 38px; height: 38px; padding: 0; background-color: #2563eb; border-color: #2563eb;" type="submit">
              <i class="fas fa-search"></i>
            </button>
          </form>
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
            <li class="nav-item active"><a class="nav-link text-primary font-weight-bold" href="index.php">Trang chủ</a></li>
            
            <!-- Dropdown danh mục sản phẩm -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle text-dark font-weight-semibold" href="#" id="catDrop" data-toggle="dropdown">
                Danh mục sản phẩm
              </a>
              <div class="dropdown-menu border-0 shadow-soft rounded-2xl p-2" aria-labelledby="catDrop" style="min-width: 220px;">
                <?php if (!empty($nav_categories)): ?>
                  <?php foreach ($nav_categories as $c): ?>
                    <a class="dropdown-item rounded-pill-custom py-2" href="category.php?id=<?= $c['id'] ?>">
                      <i class="fas fa-angle-right text-primary mr-2"></i>
                      <?= htmlspecialchars($c['name']) ?>
                    </a>
                  <?php endforeach; ?>
                <?php else: ?>
                  <span class="dropdown-item text-muted small">Đang cập nhật...</span>
                <?php endif; ?>

                <div class="dropdown-divider"></div>
                <a class="dropdown-item rounded-pill-custom py-2 text-primary font-weight-bold" href="category.php">
                  <i class="fas fa-th-large mr-2"></i>Xem tất cả
                </a>
              </div>
            </li>

            <li class="nav-item"><a class="nav-link text-secondary" href="products.php?filter=new">Sản phẩm mới</a></li>
            <li class="nav-item"><a class="nav-link text-secondary" href="products.php?filter=sale">Ưu đãi đặc quyền</a></li>
            <li class="nav-item"><a class="nav-link text-secondary" href="terms.php">Điều khoản & Chính sách</a></li>
          </ul>
        </div>
      </div>
    </nav>
  </header>