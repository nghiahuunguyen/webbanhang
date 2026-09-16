<?php
$current_uri  = $_SERVER['REQUEST_URI'] ?? '';
$currentUser  = $_SESSION['user'] ?? null;
$current_role = (int)($currentUser['role_id'] ?? 0);
$is_admin     = ($current_role === 1);
?>

<nav id="sidebar" class="col-md-3 col-lg-2 px-0 d-none d-md-block">
  <div class="brand-box">
    <div class="brand-icon"><i class="fas fa-cube"></i></div>
    <span>CommerceOS</span>
  </div>

  <ul class="nav flex-column mt-2">
    <!-- Bảng điều khiển -->
    <li class="nav-item">
      <a class="nav-link <?= (preg_match('#/admin/(index\.php)?$#', $current_uri)) ? 'active font-weight-bold' : '' ?>" 
         href="/webbanhang/admin/index.php">
        <i class="fas fa-chart-pie mr-2"></i> Bảng điều khiển
      </a>
    </li>
  </ul>

  <!-- NHÓM SẢN PHẨM -->
  <div class="menu-header">Sản phẩm</div>
  <ul class="nav flex-column">
    <li class="nav-item">
      <a class="nav-link <?= (strpos($current_uri, '/category/') !== false) ? 'active font-weight-bold' : '' ?>" 
         href="/webbanhang/admin/category/index.php">
        <i class="fas fa-layer-group mr-2"></i> Danh mục
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= (strpos($current_uri, '/brand/') !== false) ? 'active font-weight-bold' : '' ?>" 
         href="/webbanhang/admin/brand/index.php">
        <i class="fas fa-industry mr-2"></i> Hãng
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= (strpos($current_uri, '/product/') !== false) ? 'active font-weight-bold' : '' ?>" 
         href="/webbanhang/admin/product/index.php">
        <i class="fas fa-box-open mr-2"></i> Sản phẩm
      </a>
    </li>
  </ul>

  <!-- NHÓM KHÁCH HÀNG & ĐƠN HÀNG -->
  <div class="menu-header mt-3">Khách hàng & Đơn hàng</div>
  <ul class="nav flex-column">
    <li class="nav-item">
      <a class="nav-link <?= (strpos($current_uri, '/customer/') !== false || strpos($current_uri, 'customer.php') !== false) ? 'active font-weight-bold' : '' ?>" 
         href="/webbanhang/admin/customer/index.php">
        <i class="fas fa-users mr-2"></i> Khách hàng
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= (strpos($current_uri, '/order/') !== false || strpos($current_uri, 'order.php') !== false) ? 'active font-weight-bold' : '' ?>" 
         href="/webbanhang/admin/order/index.php">
        <i class="fas fa-shopping-bag mr-2"></i> Đơn hàng
      </a>
    </li>
  </ul>

  <!-- NHÓM QUẢN TRỊ NỘI BỘ (Chỉ hiển thị cho Admin) -->
  <?php if ($is_admin): ?>
    <div class="menu-header mt-3">Quản trị</div>
    <ul class="nav flex-column">
      <li class="nav-item">
        <a class="nav-link <?= (strpos($current_uri, '/staff/') !== false) ? 'active font-weight-bold' : '' ?>" 
           href="/webbanhang/admin/staff/index.php">
          <i class="fas fa-user-tie mr-2"></i> Nhân viên & Quản trị
        </a>
      </li>
      <li class="nav-item">
      <a class="nav-link <?= (strpos($current_uri, '/revenue/') !== false) ? 'active font-weight-bold' : '' ?>" 
         href="/webbanhang/admin/revenue/index.php">
        <i class="fas fa-chart-line mr-2"></i> Báo cáo doanh thu
      </a>
    </li>
    </ul>
  <?php endif; ?>

  <!-- NHÓM TRANG WEB -->
  <div class="menu-header mt-3">Trang web</div>
  <ul class="nav flex-column mb-4">
    <!-- Cài đặt, Chính sách, Banner: Chỉ Admin mới thấy -->
    <?php if ($is_admin): ?>
      <li class="nav-item">
        <a class="nav-link <?= (strpos($current_uri, '/setting/') !== false) ? 'active font-weight-bold' : '' ?>" 
           href="/webbanhang/admin/setting/index.php">
          <i class="fas fa-cog mr-2"></i> Cài đặt
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link <?= (strpos($current_uri, '/policy/') !== false) ? 'active font-weight-bold' : '' ?>" 
           href="/webbanhang/admin/policy/index.php">
          <i class="fas fa-shield-alt mr-2"></i> Chính sách & Hỗ trợ
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link <?= (strpos($current_uri, '/banner/') !== false) ? 'active font-weight-bold' : '' ?>" 
           href="/webbanhang/admin/banner/index.php">
          <i class="fas fa-images mr-2"></i> Banner
        </a>
      </li>
    <?php endif; ?>

    <!-- Đánh giá / Phản hồi: Cả Admin và Nhân viên đều thấy để chăm sóc khách hàng -->
    <li class="nav-item">
      <a class="nav-link <?= (strpos($current_uri, '/feedback/') !== false) ? 'active font-weight-bold' : '' ?>" 
         href="/webbanhang/admin/feedback/index.php">
        <i class="fas fa-comments mr-2"></i> Đánh giá (Feedback)
      </a>
    </li>
  </ul>
</nav>