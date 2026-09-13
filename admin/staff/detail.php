<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once __DIR__ . '/../../database/dbhelper.php';
require_once __DIR__ . '/../check_role.php';

// Chỉ Admin (role_id = 1) mới được xem chi tiết tài khoản nội bộ
requireAdmin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: index.php');
    die();
}

// 1. Lấy thông tin tài khoản (Admin hoặc Nhân viên, chưa bị xóa hoàn toàn)
$sql = "SELECT u.*, r.name AS role_name 
        FROM `User` u
        LEFT JOIN `Role` r ON u.role_id = r.id
        WHERE u.id = $id AND u.role_id IN (1, 3)";
$staff = executeResult($sql, true);

if (!$staff) {
    header('Location: index.php');
    die();
}

$page_title = 'Chi Tiết Tài Khoản Nhân Viên';
$is_admin = ((int)$staff['role_id'] === 1);
$avatarLetter = strtoupper(mb_substr(trim($staff['fullname']), 0, 1, 'UTF-8'));

include '../layout/head.php'; 
include '../layout/sidebar.php'; 
?>

<!-- MAIN CONTENT -->
<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-3">
  <?php include '../layout/topbar.php'; ?>

  <div class="custom-card bg-white rounded-lg shadow-sm border mb-4">
    <!-- TIÊU ĐỀ & NÚT HÀNH ĐỘNG -->
    <div class="p-3 px-4 d-flex justify-content-between align-items-center border-bottom flex-wrap">
      <div>
        <h5 class="font-weight-bold mb-0 text-dark">
          Chi Tiết Hồ Sơ: <?= htmlspecialchars($staff['fullname']) ?>
        </h5>
        <small class="text-muted">Mã tài khoản: #UID-<?= str_pad($staff['id'], 4, '0', STR_PAD_LEFT) ?></small>
      </div>
      <div class="mt-2 mt-md-0">
        <a href="edit.php?id=<?= $staff['id'] ?>" class="btn btn-sm btn-warning font-weight-bold mr-1 text-white shadow-sm">
          <i class="fas fa-edit mr-1"></i> Chỉnh sửa
        </a>
        <a href="index.php" class="btn btn-sm btn-secondary font-weight-bold shadow-sm">
          <i class="fas fa-arrow-left mr-1"></i> Quay lại
        </a>
      </div>
    </div>
    
    <div class="p-4">
      <div class="row">
        <!-- CỘT TRÁI: AVATAR & THẺ TÓM TẮT -->
        <div class="col-lg-4 col-md-5 text-center mb-4 mb-md-0">
          <div class="border rounded p-4 bg-light shadow-sm text-center">
            <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center text-white font-weight-bold shadow mb-3" 
                 style="width: 100px; height: 100px; font-size: 2.5rem; background: linear-gradient(135deg, <?= $is_admin ? '#dc3545, #b02a37' : '#0d6efd, #0b5ed7' ?>);">
              <?= $avatarLetter ?>
            </div>

            <h5 class="font-weight-bold text-dark mb-1"><?= htmlspecialchars($staff['fullname']) ?></h5>
            <p class="text-muted small mb-2">@<?= htmlspecialchars($staff['account'] ?? 'chua_dat') ?></p>

            <div class="mb-3">
              <?php if ($is_admin): ?>
                <span class="badge badge-danger px-3 py-2 font-weight-bold">
                  <i class="fas fa-user-shield mr-1"></i> Quản Trị Viên (Admin)
                </span>
              <?php else: ?>
                <span class="badge badge-primary px-3 py-2 font-weight-bold">
                  <i class="fas fa-user-tie mr-1"></i> Nhân Viên (Staff)
                </span>
              <?php endif; ?>
            </div>

            <div class="border-top pt-3 text-left">
              <div class="small text-muted mb-2">
                <i class="fas fa-toggle-on mr-2 text-success"></i>Trạng thái: 
                <strong class="<?= $staff['deleted'] == 0 ? 'text-success' : 'text-danger' ?>">
                  <?= $staff['deleted'] == 0 ? 'Đang hoạt động' : 'Tạm khóa / Thùng rác' ?>
                </strong>
              </div>
              <div class="small text-muted mb-2">
                <i class="far fa-calendar-alt mr-2 text-primary"></i>Ngày tạo: 
                <strong><?= date('d/m/Y H:i', strtotime($staff['created_at'])) ?></strong>
              </div>
              <div class="small text-muted">
                <i class="fas fa-history mr-2 text-info"></i>Cập nhật: 
                <strong><?= date('d/m/Y H:i', strtotime($staff['updated_at'])) ?></strong>
              </div>
            </div>
          </div>
        </div>

        <!-- CỘT PHẢI: BẢNG DỮ LIỆU CHI TIẾT -->
        <div class="col-lg-8 col-md-7">
          <h6 class="text-primary font-weight-bold text-uppercase small tracking-wider mb-3">
            <i class="fas fa-id-card mr-1"></i> Thông Tin Chi Tiết & Liên Hệ
          </h6>

          <div class="table-responsive border rounded bg-white mb-4">
            <table class="table table-bordered mb-0">
              <tbody>
                <tr>
                  <th width="30%" class="bg-light text-muted small font-weight-bold align-middle">Tài khoản đăng nhập</th>
                  <td class="font-weight-bold text-dark align-middle">
                    <?= htmlspecialchars($staff['account'] ?? 'Chưa đặt') ?>
                  </td>
                </tr>
                <tr>
                  <th class="bg-light text-muted small font-weight-bold align-middle">Họ và tên</th>
                  <td class="align-middle font-weight-bold text-primary">
                    <?= htmlspecialchars($staff['fullname']) ?>
                  </td>
                </tr>
                <tr>
                  <th class="bg-light text-muted small font-weight-bold align-middle">Email xác thực</th>
                  <td class="align-middle">
                    <a href="mailto:<?= htmlspecialchars($staff['email']) ?>" class="text-dark font-weight-semibold">
                      <i class="fas fa-envelope text-muted mr-2"></i><?= htmlspecialchars($staff['email']) ?>
                    </a>
                  </td>
                </tr>
                <tr>
                  <th class="bg-light text-muted small font-weight-bold align-middle">Số điện thoại</th>
                  <td class="align-middle">
                    <a href="tel:<?= htmlspecialchars($staff['phone_number']) ?>" class="text-dark font-weight-semibold">
                      <i class="fas fa-phone-alt text-muted mr-2"></i><?= htmlspecialchars($staff['phone_number']) ?>
                    </a>
                  </td>
                </tr>
                <tr>
                  <th class="bg-light text-muted small font-weight-bold align-middle">Địa chỉ cư trú</th>
                  <td class="align-middle text-secondary">
                    <i class="fas fa-map-marker-alt text-muted mr-2"></i><?= !empty($staff['address']) ? htmlspecialchars($staff['address']) : 'Chưa cập nhật thông tin địa chỉ.' ?>
                  </td>
                </tr>
                <tr>
                  <th class="bg-light text-muted small font-weight-bold align-middle">Cấp độ truy cập</th>
                  <td class="align-middle">
                    <?= $is_admin ? 'Toàn quyền điều hành và quản trị hệ thống' : 'Quyền tác vụ quản lý đơn hàng, danh mục và hỗ trợ bán hàng' ?>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- BẢO MẬT & QUYỀN HẠN TÀI KHOẢN -->
          <div class="border rounded p-3 bg-light">
            <h6 class="font-weight-bold text-dark mb-2 small text-uppercase">
              <i class="fas fa-shield-alt mr-1 text-warning"></i> Lưu ý an toàn tài khoản
            </h6>
            <ul class="text-muted small mb-0 pl-3">
              <li>Mật khẩu đã được mã hóa an toàn qua thuật toán bảo mật của hệ thống.</li>
              <li>Chỉ có Quản trị viên mới được phép cấp lại mật khẩu hoặc đổi nhóm quyền cho nhân sự này.</li>
              <?php if (!$is_admin): ?>
                <li>Tài khoản nhân viên có thể bị vô hiệu hóa tạm thời bằng cách đưa vào mục <strong>Thùng rác</strong>.</li>
              <?php endif; ?>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php include '../layout/scripts.php'; ?>