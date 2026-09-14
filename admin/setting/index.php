<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once('../../database/dbhelper.php');
require_once('../check_role.php');

// Chỉ cho phép Admin truy cập
if (!isset($_SESSION['user']) || (int)$_SESSION['user']['role_id'] !== 1) {
    header('Location: ../dashboard/index.php');
    die();
}

$msg = '';
$error = '';

// Danh sách các khóa cấu hình chuẩn
$default_keys = [
    'site_status'          => '1',
    'maintenance_message'  => 'Website đang bảo trì nâng cấp định kỳ, vui lòng quay lại sau!',
    'site_name'            => 'NOVASHOP',
    'site_slogan'          => 'Đồng hành cùng phong cách sống hiện đại. Mua sắm dễ dàng, thanh toán linh hoạt và dịch vụ hậu mãi chuẩn 5 sao.',
    'hotline'              => '1900 6868',
    'warranty_phone'       => '0901 234 567',
    'working_hours'        => '08:00 - 22:00 tất cả các ngày',
    'address'              => '123 Đường ABC, Quận 1, TP. HCM',
    'about_company'        => 'NOVASHOP là đơn vị phân phối phụ kiện và thiết bị công nghệ chính hãng. Mọi sản phẩm bán ra đều được kiểm định chất lượng nghiêm ngặt kèm tem bảo hành rõ ràng.',
    'facebook_link'        => 'https://facebook.com',
    'instagram_link'       => 'https://instagram.com',
    'youtube_link'         => 'https://youtube.com',
    'tiktok_link'          => 'https://tiktok.com'
];

// Xử lý lưu thông tin cài đặt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_save_settings'])) {
    foreach ($default_keys as $key => $default_val) {
        $val = trim($_POST[$key] ?? '');
        $safe_val = str_replace("'", "\'", $val);
        $safe_key = str_replace("'", "\'", $key);

        // Kiểm tra xem khóa đã tồn tại chưa
        $exist = executeResult("SELECT id FROM `System_Settings` WHERE `key_name` = '$safe_key' LIMIT 1", true);
        if ($exist) {
            execute("UPDATE `System_Settings` SET `key_value` = '$safe_val', `updated_at` = NOW() WHERE `key_name` = '$safe_key'");
        } else {
            execute("INSERT INTO `System_Settings` (`key_name`, `key_value`, `updated_at`) VALUES ('$safe_key', '$safe_val', NOW())");
        }
    }
    $msg = 'Lưu cấu hình hệ thống thành công!';
}

// Đọc toàn bộ giá trị hiện tại từ CSDL
$db_settings = executeResult("SELECT `key_name`, `key_value` FROM `System_Settings`");
$current_data = $default_keys;
if (!empty($db_settings)) {
    foreach ($db_settings as $item) {
        $current_data[$item['key_name']] = $item['key_value'];
    }
}

$page_title = 'Cài Đặt Hệ Thống';
include '../layout/head.php';
include '../layout/sidebar.php';
?>

<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-3">
  <?php include '../layout/topbar.php'; ?>

  <!-- TOAST THÔNG BÁO GÓC PHẢI TRÊN (TỰ ẨN SAU 3 GIÂY) -->
  <?php if (!empty($msg)): ?>
    <div id="toast-wrapper" style="position: fixed; top: 25px; right: 25px; z-index: 99999; min-width: 320px; max-width: 400px; transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);">
      <div class="card border-0 shadow-lg" style="border-radius: 12px; overflow: hidden; border-left: 5px solid #10b981 !important;">
        <div class="card-body p-3 bg-white text-dark d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center">
            <span class="text-success mr-2" style="font-size: 1.3rem;">
              <i class="fas fa-check-circle"></i>
            </span>
            <div>
              <strong class="d-block text-dark font-weight-bold" style="font-size: 0.95rem;">Thành công</strong>
              <span class="small text-muted"><?= htmlspecialchars($msg) ?></span>
            </div>
          </div>
          <button type="button" class="close text-muted ml-2" onclick="dismissToast()" style="outline: none; font-size: 1.3rem;">&times;</button>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <form action="" method="POST">
    <!-- KHỐI 1: BẬT TẮT WEBSITE & BẢO TRÌ -->
    <div class="card border-0 shadow-sm rounded-lg bg-white mb-4">
      <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
        <i class="fas fa-power-off text-danger mr-2 fa-lg"></i>
        <h5 class="font-weight-bold mb-0 text-dark">Trạng Thái Hoạt Động Của Trang Web</h5>
      </div>
      <div class="card-body p-4">
        <div class="row align-items-center">
          <div class="col-md-5 mb-3 mb-md-0">
            <label class="font-weight-bold text-dark d-block">Trạng thái website hiện tại</label>
            <div class="custom-control custom-radio custom-control-inline">
              <input type="radio" id="site_open" name="site_status" value="1" class="custom-control-input" <?= ($current_data['site_status'] === '1') ? 'checked' : '' ?>>
              <label class="custom-control-label text-success font-weight-bold" for="site_open">
                <i class="fas fa-check-circle mr-1"></i> Đang mở cửa (Hoạt động bình thường)
              </label>
            </div>
            <div class="custom-control custom-radio custom-control-inline mt-2">
              <input type="radio" id="site_close" name="site_status" value="0" class="custom-control-input" <?= ($current_data['site_status'] === '0') ? 'checked' : '' ?>>
              <label class="custom-control-label text-danger font-weight-bold" for="site_close">
                <i class="fas fa-tools mr-1"></i> Tắt trang web (Bật chế độ bảo trì)
              </label>
            </div>
          </div>
          <div class="col-md-7">
            <label class="font-weight-semibold small text-muted">Thông báo hiển thị khi tắt web bảo trì</label>
            <input type="text" name="maintenance_message" class="form-control" value="<?= htmlspecialchars($current_data['maintenance_message']) ?>" placeholder="Ví dụ: Website đang bảo trì nâng cấp...">
          </div>
        </div>
      </div>
    </div>

    <!-- KHỐI 2: THÔNG TIN LIÊN HỆ & BẢO HÀNH (SĐT, ĐỊA CHỈ, GIỜ MỞ CỬA) -->
    <div class="card border-0 shadow-sm rounded-lg bg-white mb-4">
      <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
        <i class="fas fa-id-card text-primary mr-2 fa-lg"></i>
        <h5 class="font-weight-bold mb-0 text-dark">Thông Tin Liên Hệ & Showroom</h5>
      </div>
      <div class="card-body p-4">
        <div class="row">
          <div class="col-md-6 form-group mb-3">
            <label class="font-weight-semibold small text-muted">Tên cửa hàng / Thương hiệu <span class="text-danger">*</span></label>
            <div class="input-group">
              <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-store"></i></span></div>
              <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($current_data['site_name']) ?>" required>
            </div>
          </div>

          <div class="col-md-6 form-group mb-3">
            <label class="font-weight-semibold small text-muted">Giờ mở cửa / làm việc</label>
            <div class="input-group">
              <div class="input-group-prepend"><span class="input-group-text"><i class="far fa-clock"></i></span></div>
              <input type="text" name="working_hours" class="form-control" value="<?= htmlspecialchars($current_data['working_hours']) ?>" placeholder="Ví dụ: 08:00 - 22:00 tất cả các ngày">
            </div>
          </div>

          <div class="col-md-6 form-group mb-3">
            <label class="font-weight-semibold small text-muted">Số điện thoại Hotline mua hàng <span class="text-danger">*</span></label>
            <div class="input-group">
              <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-phone-alt"></i></span></div>
              <input type="text" name="hotline" class="form-control" value="<?= htmlspecialchars($current_data['hotline']) ?>" placeholder="1900 xxxx hoặc 09xxxxxxxx" required>
            </div>
          </div>

          <div class="col-md-6 form-group mb-3">
            <label class="font-weight-semibold small text-muted">Số điện thoại Hỗ trợ kỹ thuật / Bảo hành</label>
            <div class="input-group">
              <div class="input-group-prepend"><span class="input-group-text text-warning"><i class="fas fa-wrench"></i></span></div>
              <input type="text" name="warranty_phone" class="form-control" value="<?= htmlspecialchars($current_data['warranty_phone']) ?>" placeholder="SĐT chuyên nhận bảo hành máy">
            </div>
          </div>

          <div class="col-12 form-group mb-3">
            <label class="font-weight-semibold small text-muted">Địa chỉ Showroom / Cửa hàng <span class="text-danger">*</span></label>
            <div class="input-group">
              <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span></div>
              <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($current_data['address']) ?>" placeholder="Số nhà, tên đường, quận/huyện, tỉnh/thành phố" required>
            </div>
          </div>

          <div class="col-md-6 form-group mb-3">
            <label class="font-weight-semibold small text-muted">Khẩu hiệu / Slogan ngắn (hiển thị dưới logo chân trang)</label>
            <textarea name="site_slogan" rows="3" class="form-control"><?= htmlspecialchars($current_data['site_slogan']) ?></textarea>
          </div>

          <div class="col-md-6 form-group mb-3">
            <label class="font-weight-semibold small text-muted">Đoạn giới thiệu "Về Chúng Tôi" (Chân trang)</label>
            <textarea name="about_company" rows="3" class="form-control"><?= htmlspecialchars($current_data['about_company']) ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- KHỐI 3: LIÊN KẾT MẠNG XÃ HỘI -->
    <div class="card border-0 shadow-sm rounded-lg bg-white mb-4">
      <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
        <i class="fas fa-share-alt text-info mr-2 fa-lg"></i>
        <h5 class="font-weight-bold mb-0 text-dark">Liên Kết Mạng Xã Hội (Chân Trang)</h5>
      </div>
      <div class="card-body p-4">
        <div class="row">
          <div class="col-md-6 form-group mb-3">
            <label class="small text-muted font-weight-bold">Facebook URL</label>
            <div class="input-group input-group-sm">
              <div class="input-group-prepend"><span class="input-group-text"><i class="fab fa-facebook text-primary"></i></span></div>
              <input type="url" name="facebook_link" class="form-control" value="<?= htmlspecialchars($current_data['facebook_link']) ?>" placeholder="https://facebook.com/...">
            </div>
          </div>

          <div class="col-md-6 form-group mb-3">
            <label class="small text-muted font-weight-bold">Instagram URL</label>
            <div class="input-group input-group-sm">
              <div class="input-group-prepend"><span class="input-group-text"><i class="fab fa-instagram text-danger"></i></span></div>
              <input type="url" name="instagram_link" class="form-control" value="<?= htmlspecialchars($current_data['instagram_link']) ?>" placeholder="https://instagram.com/...">
            </div>
          </div>

          <div class="col-md-6 form-group mb-3">
            <label class="small text-muted font-weight-bold">YouTube URL</label>
            <div class="input-group input-group-sm">
              <div class="input-group-prepend"><span class="input-group-text"><i class="fab fa-youtube text-danger"></i></span></div>
              <input type="url" name="youtube_link" class="form-control" value="<?= htmlspecialchars($current_data['youtube_link']) ?>" placeholder="https://youtube.com/...">
            </div>
          </div>

          <div class="col-md-6 form-group mb-3">
            <label class="small text-muted font-weight-bold">TikTok URL</label>
            <div class="input-group input-group-sm">
              <div class="input-group-prepend"><span class="input-group-text"><i class="fab fa-tiktok text-dark"></i></span></div>
              <input type="url" name="tiktok_link" class="form-control" value="<?= htmlspecialchars($current_data['tiktok_link']) ?>" placeholder="https://tiktok.com/@...">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- NÚT LƯU TOÀN BỘ -->
    <div class="d-flex justify-content-end mb-5">
      <button type="submit" name="btn_save_settings" class="btn btn-primary btn-lg font-weight-bold px-5 py-2 shadow" style="border-radius: 8px;">
        <i class="fas fa-save mr-2"></i> LƯU TẤT CẢ CÀI ĐẶT
      </button>
    </div>
  </form>
</main>

<?php include '../layout/scripts.php'; ?>
<script>
function dismissToast() {
  var toastBox = document.getElementById('toast-wrapper');
  if (toastBox) {
    toastBox.style.opacity = '0';
    toastBox.style.transform = 'translateX(50px)';
    setTimeout(function() {
      toastBox.remove();
    }, 400);
  }
}

window.addEventListener('load', function() {
  var toastBox = document.getElementById('toast-wrapper');
  if (toastBox) {
    setTimeout(dismissToast, 3000);
  }
});
</script>