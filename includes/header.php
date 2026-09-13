<?php
  if (session_status() === PHP_SESSION_NONE) {
      session_start();
  }
  // Nhúng dbhelper để truy vấn danh mục động từ CSDL
  require_once __DIR__ . '/../database/dbhelper.php';
  // Lấy danh sách danh mục hợp lệ (chưa bị xóa)
  $nav_categories = executeResult("SELECT * FROM Category WHERE deleted = 0 ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Cửa Hàng Hiện Đại - NovaShop</title>

  <!-- Google Font: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Bootstrap 4 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

  <style>
    body {
      font-family: 'Inter', sans-serif;
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
  </style>
</head>
<body>

  <!-- ==================== PHẦN ĐẦU (HEADER & NAVBAR) ==================== -->
  <header class="bg-white border-bottom shadow-sm sticky-top">
    <!-- Topbar -->
    <div class="container py-3">
      <div class="row align-items-center">
        <!-- Logo -->
        <div class="col-md-3 col-6">
          <a class="font-weight-bold text-dark h4 mb-0 text-decoration-none d-flex align-items-center" href="index.php">
            <span class="badge badge-primary mr-2 rounded-circle p-2"><i class="fas fa-cube text-white"></i></span>
            NOVA<span class="text-primary font-weight-extrabold">SHOP</span>
          </a>
        </div>

        <!-- Thanh tìm kiếm hiện đại -->
        <div class="col-md-6 d-none d-md-block">
          <form class="position-relative" action="search.php" method="GET">
            <input type="text" name="q" class="form-control rounded-pill-custom bg-light border-0 py-2 pl-4 pr-5 text-sm" placeholder="Tìm tai nghe, bàn phím, laptop..." style="height: 46px;">
            <button class="btn btn-primary rounded-circle position-absolute" style="top: 4px; right: 5px; width: 38px; height: 38px; padding: 0;" type="submit">
              <i class="fas fa-search"></i>
            </button>
          </form>
        </div>

        <!-- User & Giỏ hàng -->
        <div class="col-md-3 col-6 text-right d-flex align-items-center justify-content-end">
          <?php if (isset($_SESSION['user'])): ?>
            <!-- ĐÃ ĐĂNG NHẬP -->
            <a href="profile.php" class="text-dark small font-weight-bold mr-2 text-decoration-none text-truncate" style="max-width: 130px;" title="Xem hồ sơ">
              Chào, <?= htmlspecialchars($_SESSION['user']['fullname']) ?>
            </a>
            <a href="profile.php" class="btn btn-light btn-icon text-dark mr-2 border" title="Hồ sơ cá nhân">
              <i class="far fa-user"></i>
            </a>
          <?php else: ?>
            <!-- CHƯA ĐĂNG NHẬP -->
            <a href="login.php" class="btn btn-light btn-icon text-dark mr-2 border" title="Đăng nhập">
              <i class="far fa-user"></i>
            </a>
          <?php endif; ?>

          <a href="cart.php" class="btn btn-primary btn-icon position-relative shadow-sm" title="Giỏ hàng">
            <i class="fas fa-shopping-bag"></i>
            <span class="badge badge-danger position-absolute font-weight-bold" style="top: -4px; right: -4px; border-radius: 10px; font-size: 0.7rem;">3</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Thanh Menu / Phân Loại Sản Phẩm Động Từ CSDL -->
    <nav class="navbar navbar-expand-md navbar-light bg-white border-top py-2">
      <div class="container">
        <button class="navbar-toggler border-0" type="button" data-toggle="collapse" data-target="#productNav">
          <i class="fas fa-bars"></i>
        </button>

        <div class="collapse navbar-collapse" id="productNav">
          <ul class="navbar-nav font-weight-medium">
            <li class="nav-item active"><a class="nav-link text-primary font-weight-bold" href="index.php">Trang chủ</a></li>
            
            <!-- Dropdown nạp danh mục động -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle text-dark" href="#" id="catDrop" data-toggle="dropdown">
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