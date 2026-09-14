<?php
$current_uri = $_SERVER['REQUEST_URI'] ?? '';
$currentUser = $_SESSION['user'] ?? null;
$current_role = (int)($currentUser['role_id'] ?? 0);
?>

<nav id="sidebar" class="col-md-3 col-lg-2 px-0 d-none d-md-block">
  <div class="brand-box">
    <div class="brand-icon"><i class="fas fa-cube"></i></div>
    <span>CommerceOS</span>
  </div>

  <ul class="nav flex-column mt-2">
    <!-- Dashboard -->
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
        <i class="fas fa-shopping-bag mr-2"></i> Đơn hàng (orders)
      </a>
    </li>

    
  </ul>

  <!-- NHÓM HỆ THỐNG -->
  <div class="menu-header mt-3">Hệ thống</div>
  <ul class="nav flex-column mb-4">
    <li class="nav-item">
      <a class="nav-link <?= (strpos($current_uri, 'feedback.php') !== false) ? 'active font-weight-bold' : '' ?>" 
         href="/webbanhang/admin/feedback.php">
        <i class="fas fa-comment-dots mr-2"></i> Đánh giá (feedback)
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= (strpos($current_uri, 'users.php') !== false) ? 'active font-weight-bold' : '' ?>" 
         href="/webbanhang/admin/users.php">
        <i class="fas fa-user-friends mr-2"></i> Người dùng (user)
      </a>
    </li>
  </ul>

  <!-- NHÓM ADMIN (Chỉ hiển thị cho role_id = 1) -->
  <?php if ($current_role === 1): ?>
    <div class="menu-header mt-3">Admin</div>
    <ul class="nav flex-column mb-4">
      <li class="nav-item">
        <a class="nav-link <?= (strpos($current_uri, '/staff/') !== false || strpos($current_uri, '/user/') !== false) ? 'active font-weight-bold' : '' ?>" 
           href="/webbanhang/admin/staff/index.php">
          <i class="fas fa-user-tie mr-2"></i> Nhân viên 
        </a>
      </li>
    </ul>
  <?php endif; ?>



  <!-- NHÓM HỆ THỐNG -->
  <div class="menu-header mt-3">Trang web</div>
  <ul class="nav flex-column mb-4">
    <li class="nav-item">
      <a class="nav-link <?= (strpos($current_uri, '/setting/') !== false) ? 'active font-weight-bold' : '' ?>" 
         href="/webbanhang/admin/setting/index.php">
        <i class="fas fa-cog mr-2"></i> Cài đặt
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= (strpos($current_uri, '/policy/') !== false) ? 'active font-weight-bold' : '' ?>" 
         href="/webbanhang/admin/policy/index.php">
        <i class="fas fa-file-alt mr-2"></i> Chính sách & Hỗ trợ
      </a>
    </li>
  </ul>
</nav>