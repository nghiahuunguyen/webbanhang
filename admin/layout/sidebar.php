<?php
// Lấy đường dẫn URI hiện tại trên trình duyệt
$current_uri = $_SERVER['REQUEST_URI'] ?? '';
?>

<nav id="sidebar" class="col-md-3 col-lg-2 px-0 d-none d-md-block">
  <div class="brand-box">
    <div class="brand-icon"><i class="fas fa-cube"></i></div>
    <span>CommerceOS</span>
  </div>

  <ul class="nav flex-column mt-2">
    <!-- Dashboard: active khi vào đúng admin/ hoặc admin/index.php -->
    <li class="nav-item">
      <a class="nav-link <?= (preg_match('#/admin/(index\.php)?$#', $current_uri)) ? 'active font-weight-bold' : '' ?>" 
         href="/webbanhang/admin/index.php">
        <i class="fas fa-chart-pie mr-2"></i> Bảng điều khiển
      </a>
    </li>
  </ul>

  <div class="menu-header">Sản phẩm</div>
  <ul class="nav flex-column">
    <!-- Danh mục: active khi URL chứa /category/ -->
    <li class="nav-item">
      <a class="nav-link <?= (strpos($current_uri, '/category/') !== false) ? 'active font-weight-bold' : '' ?>" 
         href="/webbanhang/admin/category/index.php">
        <i class="fas fa-layer-group mr-2"></i> Danh mục
      </a>
    </li>

    <!-- Sản phẩm: active khi URL chứa /product/ -->
    <li class="nav-item">
      <a class="nav-link <?= (strpos($current_uri, '/product/') !== false) ? 'active font-weight-bold' : '' ?>" 
         href="/webbanhang/admin/product/index.php">
        <i class="fas fa-box-open mr-2"></i> Sản phẩm
      </a>
    </li>

    <!-- Đơn hàng -->
    <li class="nav-item">
      <a class="nav-link <?= (strpos($current_uri, 'orders.php') !== false) ? 'active font-weight-bold' : '' ?>" 
         href="/webbanhang/admin/orders.php">
        <i class="fas fa-shopping-bag mr-2"></i> Đơn hàng (orders)
      </a>
    </li>

    <!-- Chi tiết đơn -->
    <li class="nav-item">
      <a class="nav-link <?= (strpos($current_uri, 'order_details.php') !== false) ? 'active font-weight-bold' : '' ?>" 
         href="/webbanhang/admin/order_details.php">
        <i class="fas fa-file-invoice-dollar mr-2"></i> Chi tiết đơn (order_details)
      </a>
    </li>
  </ul>

  <div class="menu-header mt-3">Hệ thống</div>
  <ul class="nav flex-column mb-4">
    <!-- Feedback -->
    <li class="nav-item">
      <a class="nav-link <?= (strpos($current_uri, 'feedback.php') !== false) ? 'active font-weight-bold' : '' ?>" 
         href="/webbanhang/admin/feedback.php">
        <i class="fas fa-comment-dots mr-2"></i> Đánh giá (feedback)
      </a>
    </li>

    <!-- Users -->
    <li class="nav-item">
      <a class="nav-link <?= (strpos($current_uri, 'users.php') !== false) ? 'active font-weight-bold' : '' ?>" 
         href="/webbanhang/admin/users.php">
        <i class="fas fa-user-friends mr-2"></i> Người dùng (user)
      </a>
    </li>
  </ul>
</nav>