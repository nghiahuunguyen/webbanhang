<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('database/dbhelper.php');
include 'includes/header.php';

// Kiểm tra nếu chưa đăng nhập thì chuyển về trang login
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$user_id = (int)$_SESSION['user']['id'];
$toast_type    = '';
$toast_message = '';

// 1. XỬ LÝ CẬP NHẬT THÔNG TIN CÁ NHÂN (HỌ TÊN, EMAIL, SĐT, ĐỊA CHỈ)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_update_info'])) {
    $fullname     = trim($_POST['fullname'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    
    // Nhận 4 trường địa chỉ gửi lên từ form
    $province     = trim($_POST['province'] ?? '');
    $district     = trim($_POST['district'] ?? '');
    $ward         = trim($_POST['ward'] ?? '');
    $street       = trim($_POST['street'] ?? '');

    if (empty($fullname)) {
        $toast_type    = 'danger';
        $toast_message = 'Vui lòng nhập họ và tên!';
    } elseif (empty($email)) {
        $toast_type    = 'danger';
        $toast_message = 'Vui lòng nhập địa chỉ email!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $toast_type    = 'danger';
        $toast_message = 'Địa chỉ email không đúng định dạng!';
    } elseif (empty($phone_number)) {
        $toast_type    = 'danger';
        $toast_message = 'Vui lòng nhập số điện thoại!';
    } elseif (!preg_match('/^0[0-9]{9}$/', $phone_number)) {
        $toast_type    = 'danger';
        $toast_message = 'Số điện thoại phải gồm đúng 10 số và bắt đầu bằng số 0!';
    } else {
        $email_escaped = addslashes($email);
        $phone_escaped = addslashes($phone_number);

        // Kiểm tra email trùng lặp với tài khoản khác
        $checkEmail = executeResult("SELECT id FROM `User` WHERE email = '$email_escaped' AND id != '$user_id' AND deleted = 0 LIMIT 1", true);

        // Kiểm tra số điện thoại trùng lặp với tài khoản khác
        $checkPhone = executeResult("SELECT id FROM `User` WHERE phone_number = '$phone_escaped' AND id != '$user_id' AND deleted = 0 LIMIT 1", true);

        if ($checkEmail != null) {
            $toast_type    = 'danger';
            $toast_message = 'Địa chỉ email này đã được tài khoản khác sử dụng!';
        } elseif ($checkPhone != null) {
            $toast_type    = 'danger';
            $toast_message = 'Số điện thoại này đã được tài khoản khác sử dụng!';
        } else {
            // Ghép chuỗi địa chỉ hoàn chỉnh: "Số nhà, Xã/Phường, Quận/Huyện, Tỉnh/TP"
            $address_parts = array_filter([$street, $ward, $district, $province]);
            $full_address  = implode(', ', $address_parts);

            $fullname_escaped = addslashes($fullname);
            $address_escaped  = addslashes($full_address);

            $sql = "UPDATE `User` 
                    SET fullname = '$fullname_escaped', 
                        email = '$email_escaped', 
                        phone_number = '$phone_escaped', 
                        address = '$address_escaped', 
                        updated_at = NOW() 
                    WHERE id = '$user_id'";
            execute($sql);

            // Đồng bộ lại Session
            $_SESSION['user']['fullname']     = $fullname;
            $_SESSION['user']['email']        = $email;
            $_SESSION['user']['phone_number'] = $phone_number;
            $_SESSION['user']['address']      = $full_address;

            $toast_type    = 'success';
            $toast_message = 'Cập nhật thông tin cá nhân và email thành công!';
        }
    }
}

// 2. XỬ LÝ ĐỔI MẬT KHẨU
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_change_password'])) {
    $current_pwd = $_POST['current_password'] ?? '';
    $new_pwd     = $_POST['new_password'] ?? '';
    $confirm_pwd = $_POST['confirm_password'] ?? '';

    if (empty($current_pwd) || empty($new_pwd) || empty($confirm_pwd)) {
        $toast_type    = 'danger';
        $toast_message = 'Vui lòng điền đầy đủ các thông tin mật khẩu!';
    } elseif (strlen($new_pwd) < 6) {
        $toast_type    = 'danger';
        $toast_message = 'Mật khẩu mới phải có ít nhất 6 ký tự!';
    } elseif ($new_pwd !== $confirm_pwd) {
        $toast_type    = 'danger';
        $toast_message = 'Mật khẩu mới và xác nhận mật khẩu không trùng khớp!';
    } else {
        $checkUser = executeResult("SELECT password FROM `User` WHERE id = '$user_id' LIMIT 1", true);
        if ($checkUser == null || $checkUser['password'] !== md5($current_pwd)) {
            $toast_type    = 'danger';
            $toast_message = 'Mật khẩu hiện tại không chính xác!';
        } else {
            $new_pwd_hash = md5($new_pwd);
            execute("UPDATE `User` SET password = '$new_pwd_hash', updated_at = NOW() WHERE id = '$user_id'");
            
            $toast_type    = 'success';
            $toast_message = 'Đổi mật khẩu thành công!';
        }
    }
}

// Lấy dữ liệu người dùng mới nhất từ Database
$currentUser = executeResult("SELECT * FROM `User` WHERE id = '$user_id' LIMIT 1", true);

// Phân tách ngược chuỗi địa chỉ cũ (nếu có)
$raw_address = $currentUser['address'] ?? '';
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
?>

<!-- TOAST THÔNG BÁO GÓC PHẢI TRÊN (TỰ ẨN SAU 3 GIÂY) -->
<?php if (!empty($toast_message)): ?>
  <div id="toast-wrapper" style="position: fixed; top: 20px; right: 20px; z-index: 99999; min-width: 320px; transition: all 0.5s ease;">
    <div class="toast show shadow-lg border-0" role="alert">
      <div class="toast-header <?php echo ($toast_type === 'success') ? 'bg-success' : 'bg-danger'; ?> text-white">
        <i class="fas <?php echo ($toast_type === 'success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
        <strong class="mr-auto"><?php echo ($toast_type === 'success') ? 'Thành công' : 'Thông báo lỗi'; ?></strong>
        <small class="text-white">Vừa xong</small>
        <button type="button" class="ml-2 mb-1 close text-white" onclick="document.getElementById('toast-wrapper').remove()" style="outline: none;">
          <span>&times;</span>
        </button>
      </div>
      <div class="toast-body bg-white text-dark font-weight-bold">
        <?= htmlspecialchars($toast_message) ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<main class="container my-5">
  <div class="row">
    <!-- Cột trái: Sidebar tài khoản -->
    <div class="col-lg-3 col-md-4 mb-4">
      <div class="card border-0 rounded-2xl shadow-soft p-3 bg-white">
        <div class="text-center py-3 border-bottom">
          <div class="btn-icon bg-light text-primary rounded-circle mx-auto mb-2 border d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; font-size: 1.4rem;">
            <i class="far fa-user"></i>
          </div>
          <h6 class="font-weight-bold text-dark mb-1">
            <?= htmlspecialchars($currentUser['fullname'] ?? 'Khách hàng') ?>
          </h6>
          <span class="badge badge-pill badge-warning px-2 py-1 font-weight-bold">Thành viên NovaShop</span>
        </div>

        <div class="list-group list-group-flush pt-2">
          <a href="profile.php" class="list-group-item list-group-item-action border-0 py-2 px-3 font-weight-bold text-primary active bg-light rounded">
            <i class="fas fa-user-circle mr-2"></i> Hồ sơ cá nhân
          </a>
          <a href="orders.php" class="list-group-item list-group-item-action border-0 py-2 px-3 text-secondary rounded">
            <i class="fas fa-box-open mr-2"></i> Lịch sử đơn hàng
          </a>
          <a href="logout.php" class="list-group-item list-group-item-action border-0 py-2 px-3 text-danger mt-3 font-weight-semibold rounded">
            <i class="fas fa-sign-out-alt mr-2"></i> Đăng xuất
          </a>
        </div>
      </div>
    </div>

    <!-- Cột phải: Biểu mẫu thông tin & Địa chỉ -->
    <div class="col-lg-9 col-md-8">
      <div class="card border-0 rounded-2xl shadow-soft p-4 p-md-5 bg-white mb-4">
        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
          <div>
            <h5 class="font-weight-bold text-dark mb-1">Thông Tin Cá Nhân & Địa Chỉ Giao Hàng</h5>
            <p class="text-muted small mb-0">Cập nhật thông tin liên hệ và địa chỉ nhận hàng của bạn</p>
          </div>
          <span class="badge badge-success px-3 py-2">Đã kích hoạt</span>
        </div>

        <form action="" method="POST">
          <div class="row">
            <div class="col-md-6 form-group mb-3">
              <label class="font-weight-semibold small text-muted">Họ và tên <span class="text-danger">*</span></label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text bg-light border-0"><i class="far fa-user text-muted"></i></span>
                </div>
                <input type="text" name="fullname" class="form-control bg-light border-0 py-2" value="<?= htmlspecialchars($currentUser['fullname'] ?? '') ?>" required>
              </div>
            </div>

            <div class="col-md-6 form-group mb-3">
              <label class="font-weight-semibold small text-muted">Số điện thoại <span class="text-danger">*</span></label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text bg-light border-0"><i class="fas fa-phone-alt text-muted"></i></span>
                </div>
                <input type="tel" name="phone_number" class="form-control bg-light border-0 py-2" value="<?= htmlspecialchars($currentUser['phone_number'] ?? '') ?>" placeholder="0xxxxxxxxx" required>
              </div>
            </div>
          </div>

          <!-- MỞ KHÓA TRƯỜNG EMAIL CHO PHÉP CHỈNH SỬA -->
          <div class="form-group mb-3">
            <label class="font-weight-semibold small text-muted">Địa chỉ Email <span class="text-danger">*</span></label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text bg-light border-0"><i class="far fa-envelope text-muted"></i></span>
              </div>
              <input type="email" name="email" class="form-control bg-light border-0 py-2" value="<?= htmlspecialchars($currentUser['email'] ?? '') ?>" placeholder="example@gmail.com" required>
            </div>
            <small class="form-text text-muted">Email dùng để nhận thông báo trạng thái đơn hàng và hóa đơn thanh toán.</small>
          </div>

          <hr class="my-3">
          <label class="font-weight-bold text-dark mb-3"><i class="fas fa-map-marked-alt text-primary mr-2"></i>Địa chỉ nhận hàng</label>

          <!-- Dropdown 3 cấp chọn Tỉnh/Thành - Quận/Huyện - Phường/Xã -->
          <div class="row">
            <div class="col-md-4 form-group mb-3">
              <label class="font-weight-semibold small text-muted">Tỉnh / Thành phố <span class="text-danger">*</span></label>
              <select class="form-control bg-light border-0" id="city">
                <option value="" selected>-- Chọn Tỉnh / Thành --</option>
              </select>
              <input type="hidden" name="province" id="province_name" value="<?= htmlspecialchars($parsed_province) ?>">
            </div>

            <div class="col-md-4 form-group mb-3">
              <label class="font-weight-semibold small text-muted">Quận / Huyện <span class="text-danger">*</span></label>
              <select class="form-control bg-light border-0" id="district">
                <option value="" selected>-- Chọn Quận / Huyện --</option>
              </select>
              <input type="hidden" name="district" id="district_name" value="<?= htmlspecialchars($parsed_district) ?>">
            </div>

            <div class="col-md-4 form-group mb-3">
              <label class="font-weight-semibold small text-muted">Phường / Xã / Thị trấn <span class="text-danger">*</span></label>
              <select class="form-control bg-light border-0" id="ward">
                <option value="" selected>-- Chọn Phường / Xã --</option>
              </select>
              <input type="hidden" name="ward" id="ward_name" value="<?= htmlspecialchars($parsed_ward) ?>">
            </div>
          </div>

          <!-- Chi tiết số nhà, tên đường -->
          <div class="form-group mb-4">
            <label class="font-weight-semibold small text-muted">Số nhà, tên đường, ngõ ngách</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text bg-light border-0"><i class="fas fa-home text-muted"></i></span>
              </div>
              <input type="text" name="street" class="form-control bg-light border-0 py-2" 
                     placeholder="Ví dụ: 123 Lê Lợi, Căn hộ 402..." 
                     value="<?= htmlspecialchars($parsed_street) ?>">
            </div>
          </div>

          <button type="submit" name="btn_update_info" class="btn btn-primary px-4 py-2 font-weight-bold shadow-sm">
            <i class="fas fa-save mr-2"></i> Lưu Thông Tin
          </button>
        </form>
      </div>

      <!-- Khối 2: Đổi mật khẩu tài khoản -->
      <div class="card border-0 rounded-2xl shadow-soft p-4 p-md-5 bg-white">
        <div class="mb-4 pb-2 border-bottom">
          <h5 class="font-weight-bold text-dark mb-1">Bảo Mật & Mật Khẩu</h5>
          <p class="text-muted small mb-0">Cập nhật mật khẩu để bảo vệ an toàn cho tài khoản</p>
        </div>

        <form action="" method="POST">
          <div class="row">
            <div class="col-md-4 form-group mb-3">
              <label class="font-weight-semibold small text-muted">Mật khẩu hiện tại</label>
              <input type="password" name="current_password" class="form-control bg-light border-0 py-2" placeholder="••••••••">
            </div>
            <div class="col-md-4 form-group mb-3">
              <label class="font-weight-semibold small text-muted">Mật khẩu mới</label>
              <input type="password" name="new_password" class="form-control bg-light border-0 py-2" placeholder="Tối thiểu 6 ký tự">
            </div>
            <div class="col-md-4 form-group mb-3">
              <label class="font-weight-semibold small text-muted">Xác nhận mật khẩu mới</label>
              <input type="password" name="confirm_password" class="form-control bg-light border-0 py-2" placeholder="••••••••">
            </div>
          </div>

          <button type="submit" name="btn_change_password" class="btn btn-outline-dark px-4 py-2 font-weight-bold small">
            Cập Nhật Mật Khẩu
          </button>
        </form>
      </div>
    </div>
  </div>
</main>

<?php include 'includes/footer.php'; ?>

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

  // Nạp dữ liệu hành chính chuẩn Việt Nam
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