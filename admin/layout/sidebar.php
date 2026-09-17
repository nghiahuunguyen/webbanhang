<?php
$current_uri = $_SERVER['REQUEST_URI'] ?? '';
$is_super_admin = isset($_SESSION['user']) && ((int)($_SESSION['user']['role_id'] ?? 0) === 1);
?>

<nav id="sidebar" class="col-md-3 col-lg-2 px-0 d-none d-md-block">
  <div class="brand-box">
    <div class="brand-icon"><i class="fas fa-cube"></i></div>
    <span>CommerceOS</span>
  </div>

  <ul class="nav flex-column mt-2">
    <li class="nav-item">
      <a class="nav-link <?= (preg_match('#/admin/(index\.php)?$#', $current_uri)) ? 'active font-weight-bold' : '' ?>" 
         href="/webbanhang/admin/index.php">
        <i class="fas fa-chart-pie mr-2"></i> Bảng điều khiển
      </a>
    </li>
  </ul>

  <!-- NHÓM SẢN PHẨM -->
  <?php if (hasPermission('category') || hasPermission('brand') || hasPermission('product')): ?>
    <div class="menu-header">Sản phẩm</div>
    <ul class="nav flex-column">
      <?php if (hasPermission('category')): ?>
        <li class="nav-item">
          <a class="nav-link <?= (strpos($current_uri, '/category/') !== false) ? 'active font-weight-bold' : '' ?>" 
             href="/webbanhang/admin/category/index.php">
            <i class="fas fa-layer-group mr-2"></i> Danh mục
          </a>
        </li>
      <?php endif; ?>

      <?php if (hasPermission('brand')): ?>
        <li class="nav-item">
          <a class="nav-link <?= (strpos($current_uri, '/brand/') !== false) ? 'active font-weight-bold' : '' ?>" 
             href="/webbanhang/admin/brand/index.php">
            <i class="fas fa-industry mr-2"></i> Hãng
          </a>
        </li>
      <?php endif; ?>

      <?php if (hasPermission('product')): ?>
        <li class="nav-item">
          <a class="nav-link <?= (strpos($current_uri, '/product/') !== false) ? 'active font-weight-bold' : '' ?>" 
             href="/webbanhang/admin/product/index.php">
            <i class="fas fa-box-open mr-2"></i> Sản phẩm
          </a>
        </li>
      <?php endif; ?>
    </ul>
  <?php endif; ?>

  <!-- NHÓM KHÁCH HÀNG & ĐƠN HÀNG -->
  <?php if (hasPermission('customer') || hasPermission('order')): ?>
    <div class="menu-header mt-3">Khách hàng & Đơn hàng</div>
    <ul class="nav flex-column">
      <?php if (hasPermission('customer')): ?>
        <li class="nav-item">
          <a class="nav-link <?= (strpos($current_uri, '/customer/') !== false) ? 'active font-weight-bold' : '' ?>" 
             href="/webbanhang/admin/customer/index.php">
            <i class="fas fa-users mr-2"></i> Khách hàng
          </a>
        </li>
      <?php endif; ?>

      <?php if (hasPermission('order')): ?>
        <li class="nav-item">
          <a class="nav-link <?= (strpos($current_uri, '/order/') !== false) ? 'active font-weight-bold' : '' ?>" 
             href="/webbanhang/admin/order/index.php">
            <i class="fas fa-shopping-bag mr-2"></i> Đơn hàng
          </a>
        </li>
      <?php endif; ?>
    </ul>
  <?php endif; ?>

  <!-- NHÓM QUẢN TRỊ NỘI BỘ & DOANH THU -->
  <?php if ($is_super_admin || hasPermission('revenue') || hasPermission('staff') || hasPermission('role')): ?>
    <div class="menu-header mt-3">Quản trị & Phân quyền</div>
    <ul class="nav flex-column">
      <?php if (hasPermission('revenue')): ?>
        <li class="nav-item">
          <a class="nav-link <?= (strpos($current_uri, '/revenue/') !== false) ? 'active font-weight-bold' : '' ?>" 
             href="/webbanhang/admin/revenue/index.php">
            <i class="fas fa-chart-line mr-2"></i> Báo cáo doanh thu
          </a>
        </li>
      <?php endif; ?>

      <?php if ($is_super_admin || hasPermission('role')): ?>
        <li class="nav-item">
          <a class="nav-link <?= (strpos($current_uri, '/role/') !== false) ? 'active font-weight-bold' : '' ?>" 
             href="/webbanhang/admin/role/index.php">
            <i class="fas fa-user-shield mr-2"></i> Vai trò & Quyền hạn
          </a>
        </li>
      <?php endif; ?>

      <?php if ($is_super_admin || hasPermission('staff')): ?>
        <li class="nav-item">
          <a class="nav-link <?= (strpos($current_uri, '/staff/') !== false) ? 'active font-weight-bold' : '' ?>" 
             href="/webbanhang/admin/staff/index.php">
            <i class="fas fa-user-tie mr-2"></i> Tài khoản nội bộ
          </a>
        </li>
      <?php endif; ?>
    </ul>
  <?php endif; ?>

  <!-- NHÓM TRANG WEB & TƯƠNG TÁC -->
  <?php if (hasPermission('setting') || hasPermission('policy') || hasPermission('banner') || hasPermission('feedback')): ?>
    <div class="menu-header mt-3">Trang web & Chăm sóc</div>
    <ul class="nav flex-column mb-4">
      <?php if (hasPermission('setting')): ?>
        <li class="nav-item">
          <a class="nav-link <?= (strpos($current_uri, '/setting/') !== false) ? 'active font-weight-bold' : '' ?>" 
             href="/webbanhang/admin/setting/index.php">
            <i class="fas fa-cog mr-2"></i> Cài đặt
          </a>
        </li>
      <?php endif; ?>

      <?php if (hasPermission('policy')): ?>
        <li class="nav-item">
          <a class="nav-link <?= (strpos($current_uri, '/policy/') !== false) ? 'active font-weight-bold' : '' ?>" 
             href="/webbanhang/admin/policy/index.php">
            <i class="fas fa-shield-alt mr-2"></i> Chính sách & Hỗ trợ
          </a>
        </li>
      <?php endif; ?>

      <?php if (hasPermission('banner')): ?>
        <li class="nav-item">
          <a class="nav-link <?= (strpos($current_uri, '/banner/') !== false) ? 'active font-weight-bold' : '' ?>" 
             href="/webbanhang/admin/banner/index.php">
            <i class="fas fa-images mr-2"></i> Banner
          </a>
        </li>
      <?php endif; ?>

      <?php if (hasPermission('feedback')): ?>
        <li class="nav-item">
          <a class="nav-link <?= (strpos($current_uri, '/feedback/') !== false) ? 'active font-weight-bold' : '' ?>" 
             href="/webbanhang/admin/feedback/index.php">
            <i class="fas fa-comments mr-2"></i> Đánh giá & Góp ý
          </a>
        </li>
      <?php endif; ?>
    </ul>
  <?php endif; ?>
</nav>