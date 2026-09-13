<?php
// Lấy dữ liệu user đang đăng nhập từ Session
$currentUser = $_SESSION['user'] ?? null;
$displayName = $currentUser['fullname'] ?? ($currentUser['account'] ?? 'Hệ thống');
$currentRole = (int)($currentUser['role_id'] ?? 0);
$roleName    = ($currentRole === 1) ? 'Admin' : 'Nhân viên';

// Lấy chữ cái đầu của tên hiển thị làm avatar
$avatarLetter = strtoupper(mb_substr(trim($displayName), 0, 1, 'UTF-8'));
?>

<div class="topbar d-flex justify-content-between align-items-center mb-4">
  <button class="btn btn-light d-md-none mr-2" type="button" data-toggle="collapse" data-target="#sidebar">
    <i class="fas fa-bars"></i>
  </button>

  <div class="position-relative search-box d-none d-md-block">
    <i class="fas fa-search"></i>
    <input type="text" class="form-control form-control-sm" placeholder="Tìm sản phẩm, đơn hàng...">
  </div>

  <div class="d-flex align-items-center">
    <!-- Nút thông báo chuông -->
    <button class="btn btn-light btn-sm rounded-circle mr-3" style="width: 38px; height: 38px;">
      <i class="far fa-bell text-muted"></i>
    </button>
    
    <!-- Dropdown thông tin tài khoản -->
    <div class="dropdown">
      <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
        <div class="rounded-circle mr-2 d-flex align-items-center justify-content-center bg-primary text-white font-weight-bold shadow-sm" 
             style="width: 34px; height: 34px; font-size: 0.85rem;">
          <?= $avatarLetter ?>
        </div>
        <div class="d-none d-md-block text-left mr-1" style="line-height: 1.2;">
          <span class="font-weight-bold small text-dark d-block"><?= htmlspecialchars($displayName) ?></span>
          <small class="text-muted" style="font-size: 0.72rem;"><?= $roleName ?></small>
        </div>
      </a>

      <div class="dropdown-menu dropdown-menu-right border-0 shadow mt-2" style="min-width: 200px; border-radius: 8px;">
        <div class="px-3 py-2 border-bottom d-md-none">
          <div class="font-weight-bold small text-dark"><?= htmlspecialchars($displayName) ?></div>
          <span class="badge badge-primary"><?= $roleName ?></span>
        </div>
        
        <a class="dropdown-item py-2 small font-weight-medium" href="/webbanhang/admin/profile.php">
          <i class="fas fa-user-cog mr-2 text-muted"></i>Thiết lập tài khoản
        </a>

        <div class="dropdown-divider my-1"></div>

        <a class="dropdown-item py-2 small font-weight-bold text-danger" href="/webbanhang/logout.php" onclick="return confirm('Bạn có chắc muốn đăng xuất?');">
          <i class="fas fa-sign-out-alt mr-2"></i>Đăng xuất
        </a>
      </div>
    </div>
  </div>
</div>