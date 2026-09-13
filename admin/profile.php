<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once __DIR__ . '/../database/dbhelper.php';
require_once __DIR__ . '/check_role.php';

// Lấy ID người dùng đang đăng nhập trong Session
$current_session = $_SESSION['user'] ?? null;
$user_id = (int)($current_session['id'] ?? 0);

if ($user_id <= 0) {
    header('Location: /webbanhang/login.php');
    exit();
}

// Lấy thông tin mới nhất từ CSDL
$user = executeResult("SELECT u.*, r.name AS role_name FROM `User` u LEFT JOIN `Role` r ON u.role_id = r.id WHERE u.id = $user_id AND u.deleted = 0", true);
if (!$user) {
    header('Location: /webbanhang/login.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname     = trim($_POST['fullname'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    
    // Nhận 4 trường dữ liệu địa chỉ
    $province     = trim($_POST['province'] ?? '');
    $district     = trim($_POST['district'] ?? '');
    $ward         = trim($_POST['ward'] ?? '');
    $street       = trim($_POST['street'] ?? '');

    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_pwd  = $_POST['confirm_password'] ?? '';

    // 1. Kiểm tra họ tên
    if (empty($fullname)) {
        $error = 'Họ và tên không được để trống!';
    // 2. Kiểm tra số điện thoại
    } elseif (empty($phone_number)) {
        $error = 'Vui lòng nhập số điện thoại!';
    } elseif (!preg_match('/^0[0-9]{9}$/', $phone_number)) {
        $error = 'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng số 0!';
    } else {
        $safe_phone = str_replace("'", "\'", $phone_number);
        // Kiểm tra xem số điện thoại có bị trùng với tài khoản khác không
        $check_phone = executeResult("SELECT id FROM `User` WHERE phone_number = '$safe_phone' AND id != $user_id AND deleted = 0 LIMIT 1", true);

        if ($check_phone != null) {
            $error = 'Số điện thoại này đã được sử dụng bởi tài khoản khác!';
        } else {
            $pwd_update = "";

            // 3. Nếu người dùng muốn đổi mật khẩu
            if (!empty($old_password) || !empty($new_password) || !empty($confirm_pwd)) {
                if (empty($old_password)) {
                    $error = 'Vui lòng nhập mật khẩu hiện tại!';
                } elseif (md5($old_password) !== $user['password']) {
                    $error = 'Mật khẩu hiện tại không chính xác!';
                } elseif (empty($new_password)) {
                    $error = 'Vui lòng nhập mật khẩu mới!';
                } elseif (strlen($new_password) < 6) {
                    $error = 'Mật khẩu mới phải có tối thiểu 6 ký tự!';
                } elseif ($new_password !== $confirm_pwd) {
                    $error = 'Xác nhận mật khẩu mới không khớp!';
                } else {
                    $new_hash = md5($new_password);
                    $pwd_update = ", `password` = '$new_hash'";
                }
            }

            // Nếu không có lỗi thì tiến hành cập nhật
            if (empty($error)) {
                // Ghép 4 trường thành chuỗi địa chỉ hoàn chỉnh: "Số nhà, Xã/Phường, Quận/Huyện, Tỉnh/TP"
                $address_parts = array_filter([$street, $ward, $district, $province]);
                $full_address  = implode(', ', $address_parts);

                $safe_fullname = str_replace("'", "\'", $fullname);
                $safe_address  = str_replace("'", "\'", $full_address);

                $sql = "UPDATE `User` 
                        SET `fullname` = '$safe_fullname',
                            `phone_number` = '$safe_phone',
                            `address` = '$safe_address',
                            `updated_at` = NOW()
                            $pwd_update
                        WHERE `id` = $user_id";
                execute($sql);

                // Cập nhật lại session thông tin hiển thị
                $_SESSION['user']['fullname']     = $fullname;
                $_SESSION['user']['phone_number'] = $phone_number;
                $_SESSION['user']['address']      = $full_address;

                // Lấy lại dữ liệu mới nhất
                $user = executeResult("SELECT u.*, r.name AS role_name FROM `User` u LEFT JOIN `Role` r ON u.role_id = r.id WHERE u.id = $user_id", true);
                $success = 'Cập nhật thông tin cá nhân thành công!';
            }
        }
    }
}

// Phân tách ngược chuỗi địa chỉ cũ
$raw_address     = $user['address'] ?? '';
$parsed_street   = '';
$parsed_ward     = '';
$parsed_district = '';
$parsed_province = '';

if (!empty($raw_address)) {
    $parts = array_map('trim', explode(',', $raw_address));
    $total_parts = count($parts);
    if ($total_parts >= 4) {
        $parsed_province = $parts[$total_parts - 1];
        $parsed_district = $parts[$total_parts - 2];
        $parsed_ward     = $parts[$total_parts - 3];
        $parsed_street   = implode(', ', array_slice($parts, 0, $total_parts - 3));
    } else {
        $parsed_street   = $raw_address;
    }
}

$page_title = 'Thiết Lập Tài Khoản';
$is_admin = ((int)$user['role_id'] === 1);
$avatarLetter = strtoupper(mb_substr(trim($user['fullname']), 0, 1, 'UTF-8'));

include 'layout/head.php'; 
include 'layout/sidebar.php'; 
?>

<!-- TOAST THÔNG BÁO LỖI HOẶC THÀNH CÔNG -->
<?php if (!empty($error) || !empty($success)): ?>
  <div id="toast-wrapper" style="position: fixed; top: 20px; right: 20px; z-index: 99999; min-width: 320px; transition: all 0.5s ease;">
    <div class="toast show shadow-lg border-0" role="alert">
      <div class="toast-header <?= !empty($error) ? 'bg-danger' : 'bg-success' ?> text-white">
        <i class="fas <?= !empty($error) ? 'fa-exclamation-circle' : 'fa-check-circle' ?> mr-2"></i>
        <strong class="mr-auto"><?= !empty($error) ? 'Lỗi thao tác' : 'Thông báo' ?></strong>
        <small class="text-white">Vừa xong</small>
        <button type="button" class="ml-2 mb-1 close text-white" onclick="document.getElementById('toast-wrapper').remove()" style="outline: none;">
          <span>&times;</span>
        </button>
      </div>
      <div class="toast-body bg-white text-dark font-weight-bold">
        <?= htmlspecialchars($error ?: $success) ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- MAIN CONTENT -->
<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-3">
  <?php include 'layout/topbar.php'; ?>

  <div class="custom-card bg-white rounded-lg shadow-sm border mb-4">
    <div class="p-3 px-4 border-bottom">
      <h5 class="font-weight-bold mb-0 text-dark">Hồ Sơ Cá Nhân & Thiết Lập Tài Khoản</h5>
      <small class="text-muted">Quản lý thông tin cá nhân và thay đổi mật khẩu đăng nhập</small>
    </div>

    <div class="p-4">
      <form method="POST" novalidate>
        <div class="row">
          <!-- CỘT TRÁI: THÔNG TIN TÓM TẮT & AVATAR -->
          <div class="col-lg-4 text-center border-right pr-lg-4 mb-4 mb-lg-0">
            <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center text-white font-weight-bold shadow-sm mb-3" 
                 style="width: 100px; height: 100px; font-size: 2.5rem; background: linear-gradient(135deg, <?= $is_admin ? '#dc3545, #b02a37' : '#0d6efd, #0b5ed7' ?>);">
              <?= $avatarLetter ?>
            </div>
            
            <h5 class="font-weight-bold text-dark mb-1"><?= htmlspecialchars($user['fullname']) ?></h5>
            <p class="text-muted small mb-2">@<?= htmlspecialchars($user['account'] ?? 'chua_co_account') ?></p>

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

            <div class="border-top pt-3 text-left small text-muted">
              <div class="mb-2"><i class="fas fa-envelope mr-2 text-secondary"></i>Email: <strong class="text-dark"><?= htmlspecialchars($user['email']) ?></strong></div>
              <div class="mb-2"><i class="far fa-calendar-alt mr-2 text-secondary"></i>Tham gia: <strong class="text-dark"><?= date('d/m/Y', strtotime($user['created_at'])) ?></strong></div>
              <div><i class="fas fa-history mr-2 text-secondary"></i>Đổi lần cuối: <strong class="text-dark"><?= date('d/m/Y H:i', strtotime($user['updated_at'])) ?></strong></div>
            </div>
          </div>

          <!-- CỘT PHẢI: FORM CHỈNH SỬA & ĐỔI MẬT KHẨU -->
          <div class="col-lg-8 pl-lg-4">
            <h6 class="text-primary font-weight-bold text-uppercase small tracking-wider mb-3">
              <i class="fas fa-user-edit mr-1"></i> Thông Tin Cơ Bản
            </h6>

            <div class="row">
              <div class="col-md-6 form-group">
                <label class="font-weight-bold">Tài khoản đăng nhập</label>
                <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($user['account'] ?? '') ?>" readonly>
              </div>

              <div class="col-md-6 form-group">
                <label class="font-weight-bold">Địa chỉ Email</label>
                <input type="email" class="form-control bg-light" value="<?= htmlspecialchars($user['email']) ?>" readonly>
              </div>

              <div class="col-md-6 form-group">
                <label class="font-weight-bold">Họ và tên <span class="text-danger">*</span></label>
                <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($user['fullname']) ?>" required>
              </div>

              <div class="col-md-6 form-group">
                <label class="font-weight-bold">Số điện thoại <span class="text-danger">*</span></label>
                <input type="tel" name="phone_number" class="form-control" value="<?= htmlspecialchars($user['phone_number']) ?>" required>
              </div>
            </div>

            <hr class="my-3">
            <h6 class="text-primary font-weight-bold text-uppercase small tracking-wider mb-3">
              <i class="fas fa-map-marked-alt mr-1"></i> Địa Chỉ Cư Trú / Làm Việc
            </h6>

            <!-- DROPDOWN 3 CẤP HÀNH CHÍNH -->
            <div class="row">
              <div class="col-md-4 form-group">
                <label class="font-weight-bold small text-muted">Tỉnh / Thành phố <span class="text-danger">*</span></label>
                <select class="form-control" id="city">
                  <option value="" selected>-- Chọn Tỉnh / Thành --</option>
                </select>
                <input type="hidden" name="province" id="province_name" value="<?= htmlspecialchars($parsed_province) ?>">
              </div>

              <div class="col-md-4 form-group">
                <label class="font-weight-bold small text-muted">Quận / Huyện <span class="text-danger">*</span></label>
                <select class="form-control" id="district">
                  <option value="" selected>-- Chọn Quận / Huyện --</option>
                </select>
                <input type="hidden" name="district" id="district_name" value="<?= htmlspecialchars($parsed_district) ?>">
              </div>

              <div class="col-md-4 form-group">
                <label class="font-weight-bold small text-muted">Phường / Xã / Thị trấn <span class="text-danger">*</span></label>
                <select class="form-control" id="ward">
                  <option value="" selected>-- Chọn Phường / Xã --</option>
                </select>
                <input type="hidden" name="ward" id="ward_name" value="<?= htmlspecialchars($parsed_ward) ?>">
              </div>
            </div>

            <!-- Ô NHẬP SỐ NHÀ, TÊN ĐƯỜNG -->
            <div class="form-group mb-4">
              <label class="font-weight-bold small text-muted">Số nhà, tên đường, thôn/xóm</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text bg-light"><i class="fas fa-home text-muted"></i></span>
                </div>
                <input type="text" name="street" class="form-control" 
                       placeholder="Ví dụ: 123 Đường Trần Phú, Tòa nhà Bitexco..." 
                       value="<?= htmlspecialchars($parsed_street) ?>">
              </div>
            </div>

            <hr class="my-4">

            <h6 class="text-danger font-weight-bold text-uppercase small tracking-wider mb-3">
              <i class="fas fa-key mr-1"></i> Đổi Mật Khẩu (Để trống nếu không đổi)
            </h6>

            <div class="row">
              <div class="col-md-4 form-group">
                <label class="font-weight-bold small">Mật khẩu hiện tại</label>
                <input type="password" name="old_password" class="form-control" placeholder="••••••••">
              </div>

              <div class="col-md-4 form-group">
                <label class="font-weight-bold small">Mật khẩu mới</label>
                <input type="password" name="new_password" class="form-control" placeholder="Tối thiểu 6 ký tự">
              </div>

              <div class="col-md-4 form-group">
                <label class="font-weight-bold small">Xác nhận mật khẩu mới</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="••••••••">
              </div>
            </div>

            <div class="mt-3">
              <button type="submit" class="btn btn-primary px-4 font-weight-bold shadow-sm">
                <i class="fas fa-save mr-1"></i> Lưu thay đổi
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</main>

<?php include 'layout/scripts.php'; ?>

<!-- THƯ VIỆN AXIOS & SCRIPT ĐIỀU KHIỂN DROPDOWN ĐỊA CHÍNH -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.21.1/axios.min.js"></script>
<script>
  const cities = document.getElementById("city");
  const districts = document.getElementById("district");
  const wards = document.getElementById("ward");

  const provinceNameInput = document.getElementById("province_name");
  const districtNameInput = document.getElementById("district_name");
  const wardNameInput = document.getElementById("ward_name");

  const savedProvince = "<?= addslashes($parsed_province) ?>";
  const savedDistrict = "<?= addslashes($parsed_district) ?>";
  const savedWard     = "<?= addslashes($parsed_ward) ?>";

  // Nạp dữ liệu địa giới hành chính chuẩn Việt Nam
  axios.get("https://raw.githubusercontent.com/kenzouno1/DiaGioiHanhChinhVN/master/data.json")
    .then(function (result) {
      renderCity(result.data);
    })
    .catch(function (error) {
      console.error("Lỗi khi tải dữ liệu địa giới:", error);
    });

  function renderCity(data) {
    // Đổ danh sách Tỉnh / Thành phố
    for (const item of data) {
      cities.options[cities.options.length] = new Option(item.Name, item.Id);
      if (savedProvince && item.Name.toLowerCase() === savedProvince.toLowerCase()) {
        cities.value = item.Id;
      }
    }

    if (cities.value !== "") {
      populateDistricts(data);
    }

    cities.onchange = function () {
      districts.length = 1;
      wards.length = 1;
      provinceNameInput.value = cities.options[cities.selectedIndex]?.text || '';
      districtNameInput.value = '';
      wardNameInput.value = '';

      if (this.value !== "") {
        populateDistricts(data);
      }
    };

    function populateDistricts(data) {
      const selectedCity = data.find(n => n.Id === cities.value);
      if (!selectedCity) return;

      provinceNameInput.value = selectedCity.Name;

      for (const item of selectedCity.Districts) {
        districts.options[districts.options.length] = new Option(item.Name, item.Id);
        if (savedDistrict && item.Name.toLowerCase() === savedDistrict.toLowerCase()) {
          districts.value = item.Id;
        }
      }

      if (districts.value !== "") {
        populateWards(selectedCity);
      }
    }

    districts.onchange = function () {
      wards.length = 1;
      districtNameInput.value = districts.options[districts.selectedIndex]?.text || '';
      wardNameInput.value = '';

      const selectedCity = data.find(n => n.Id === cities.value);
      if (this.value !== "" && selectedCity) {
        populateWards(selectedCity);
      }
    };

    function populateWards(selectedCity) {
      const selectedDistrict = selectedCity.Districts.find(n => n.Id === districts.value);
      if (!selectedDistrict) return;

      districtNameInput.value = selectedDistrict.Name;

      for (const item of selectedDistrict.Wards) {
        wards.options[wards.options.length] = new Option(item.Name, item.Id);
        if (savedWard && item.Name.toLowerCase() === savedWard.toLowerCase()) {
          wards.value = item.Id;
        }
      }

      if (wards.value !== "") {
        const selectedWard = selectedDistrict.Wards.find(n => n.Id === wards.value);
        if (selectedWard) wardNameInput.value = selectedWard.Name;
      }
    }

    wards.onchange = function () {
      wardNameInput.value = wards.options[wards.selectedIndex]?.text || '';
    };
  }

  // Tự động tắt Toast thông báo sau 3 giây
  window.addEventListener('load', function() {
    var toastBox = document.getElementById('toast-wrapper');
    if (toastBox) {
      setTimeout(function() {
        toastBox.style.opacity = '0';
        toastBox.style.transform = 'translateX(50px)';
        setTimeout(function() { toastBox.remove(); }, 500);
      }, 3000);
    }
  });
</script>