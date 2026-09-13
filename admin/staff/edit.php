<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once __DIR__ . '/../../database/dbhelper.php';
require_once __DIR__ . '/../check_role.php';

// Chỉ Admin (role_id = 1) mới có quyền chỉnh sửa nhân viên / quản trị viên
requireAdmin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: index.php');
    die();
}

// 1. Lấy tài khoản Admin (role_id = 1) hoặc Nhân viên (role_id = 3)
$user = executeResult("SELECT * FROM `User` WHERE id = $id AND role_id IN (1, 3) AND deleted = 0", true);
if (!$user) {
    header('Location: index.php');
    die();
}

$error        = '';
$fullname     = $user['fullname'];
$email        = $user['email'];
$phone_number = $user['phone_number'];
$account      = $user['account'];
$role_id      = (int)$user['role_id'];

// Phân tách ngược chuỗi địa chỉ cũ (nếu có) dạng: "Số nhà, Xã/Phường, Quận/Huyện, Tỉnh/TP"
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname     = trim($_POST['fullname'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $account      = trim($_POST['account'] ?? '');
    $password     = $_POST['password'] ?? '';
    
    // Nhận 4 trường dữ liệu địa chỉ
    $province     = trim($_POST['province'] ?? '');
    $district     = trim($_POST['district'] ?? '');
    $ward         = trim($_POST['ward'] ?? '');
    $street       = trim($_POST['street'] ?? '');

    // Nhận role_id từ form (chỉ chấp nhận 1 hoặc 3)
    $role_id      = isset($_POST['role_id']) ? intval($_POST['role_id']) : 3;
    if ($role_id !== 1 && $role_id !== 3) {
        $role_id = 3;
    }

    // 1. Kiểm tra họ tên
    if (empty($fullname)) {
        $error = 'Vui lòng nhập họ và tên!';

    // 2. Kiểm tra tài khoản đăng nhập
    } elseif (empty($account)) {
        $error = 'Vui lòng nhập tên tài khoản!';
    } elseif (strlen($account) < 4) {
        $error = 'Tên tài khoản phải có ít nhất 4 ký tự!';

    // 3. Kiểm tra Email
    } elseif (empty($email)) {
        $error = 'Vui lòng nhập địa chỉ Email!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Địa chỉ Email không đúng định dạng!';

    // 4. Kiểm tra số điện thoại
    } elseif (empty($phone_number)) {
        $error = 'Vui lòng nhập số điện thoại!';
    } elseif (!preg_match('/^0[0-9]{9}$/', $phone_number)) {
        $error = 'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng số 0!';

    // 5. Kiểm tra mật khẩu (nếu đổi)
    } elseif (!empty($password) && strlen($password) < 6) {
        $error = 'Mật khẩu mới phải có ít nhất 6 ký tự!';
    } else {
        $safe_fullname = str_replace("'", "\'", $fullname);
        $safe_email    = str_replace("'", "\'", $email);
        $safe_phone    = str_replace("'", "\'", $phone_number);
        $safe_account  = str_replace("'", "\'", $account);

        // Ghép chuỗi địa chỉ đầy đủ
        $address_parts = array_filter([$street, $ward, $district, $province]);
        $full_address  = implode(', ', $address_parts);
        $safe_address  = str_replace("'", "\'", $full_address);

        // Kiểm tra xem Account, Email hoặc SĐT có bị trùng với tài khoản khác không
        $sql_check = "SELECT id, email, phone_number, account FROM `User` 
                      WHERE (account = '$safe_account' OR email = '$safe_email' OR phone_number = '$safe_phone') 
                        AND id != $id 
                        AND deleted = 0 
                      LIMIT 1";
        $existing = executeResult($sql_check, true);

        if ($existing != null) {
            if ($existing['account'] === $account) {
                $error = 'Tên tài khoản này đã được sử dụng bởi người khác!';
            } elseif ($existing['email'] === $email) {
                $error = 'Địa chỉ Email này đã tồn tại trong hệ thống!';
            } else {
                $error = 'Số điện thoại này đã được đăng ký cho người khác!';
            }
        } else {
            $pwd_update = "";
            if (!empty($password)) {
                $password_hash = md5($password);
                $pwd_update = ", password = '$password_hash'";
            }

            $sql = "UPDATE `User` 
                    SET fullname     = '$safe_fullname',
                        email        = '$safe_email',
                        phone_number = '$safe_phone',
                        account      = '$safe_account',
                        address      = '$safe_address',
                        role_id      = $role_id,
                        updated_at   = NOW()
                        $pwd_update 
                    WHERE id = $id";
            execute($sql);

            // Nếu người sửa chính là tài khoản đang đăng nhập, cập nhật lại session
            if (isset($_SESSION['user']) && (int)$_SESSION['user']['id'] === $id) {
                $_SESSION['user']['fullname']     = $fullname;
                $_SESSION['user']['email']        = $email;
                $_SESSION['user']['phone_number'] = $phone_number;
                $_SESSION['user']['account']      = $account;
                $_SESSION['user']['address']      = $full_address;
                $_SESSION['user']['role_id']      = $role_id;
            }

            $_SESSION['success_msg'] = "Cập nhật thông tin thành công!";
            header('Location: index.php');
            die();
        }
    }
}

$page_title = 'Chỉnh Sửa Thông Tin';
include '../layout/head.php'; 
include '../layout/sidebar.php'; 
?>

<!-- TOAST THÔNG BÁO LỖI GÓC PHẢI TRÊN (TỰ ẨN SAU 3 GIÂY) -->
<?php if (!empty($error)): ?>
  <div id="toast-wrapper" style="position: fixed; top: 20px; right: 20px; z-index: 99999; min-width: 320px; transition: all 0.5s ease;">
    <div class="toast show shadow-lg border-0" role="alert">
      <div class="toast-header bg-danger text-white">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <strong class="mr-auto">Lỗi cập nhật</strong>
        <small class="text-white">Vừa xong</small>
        <button type="button" class="ml-2 mb-1 close text-white" onclick="document.getElementById('toast-wrapper').remove()" style="outline: none;">
          <span>&times;</span>
        </button>
      </div>
      <div class="toast-body bg-white text-dark font-weight-bold">
        <?= htmlspecialchars($error) ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- MAIN CONTENT -->
<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-3">
  <?php include '../layout/topbar.php'; ?>

  <div class="custom-card bg-white rounded-lg shadow-sm border">
    <div class="p-3 px-4 d-flex justify-content-between align-items-center border-bottom">
      <h5 class="font-weight-bold mb-0 text-dark">Chỉnh Sửa Thông Tin Tài Khoản</h5>
      <a href="index.php" class="btn btn-sm btn-secondary font-weight-bold">
        <i class="fas fa-arrow-left mr-1"></i> Quay lại
      </a>
    </div>

    <form class="p-4" method="POST" novalidate>
      <div class="row">
        <!-- Họ và tên -->
        <div class="col-md-6 form-group">
          <label class="font-weight-bold">Họ và tên <span class="text-danger">*</span></label>
          <input type="text" 
                 name="fullname" 
                 class="form-control" 
                 value="<?= htmlspecialchars($fullname) ?>" 
                 required autofocus>
        </div>

        <!-- Tên tài khoản đăng nhập -->
        <div class="col-md-6 form-group">
          <label class="font-weight-bold">Tài khoản đăng nhập <span class="text-danger">*</span></label>
          <input type="text" 
                 name="account" 
                 class="form-control" 
                 value="<?= htmlspecialchars($account) ?>" 
                 required>
        </div>

        <!-- Email -->
        <div class="col-md-6 form-group">
          <label class="font-weight-bold">Email <span class="text-danger">*</span></label>
          <input type="email" 
                 name="email" 
                 class="form-control" 
                 value="<?= htmlspecialchars($email) ?>" 
                 required>
        </div>

        <!-- Số điện thoại -->
        <div class="col-md-6 form-group">
          <label class="font-weight-bold">Số điện thoại <span class="text-danger">*</span></label>
          <input type="tel" 
                 name="phone_number" 
                 class="form-control" 
                 value="<?= htmlspecialchars($phone_number) ?>" 
                 required>
        </div>

        <!-- Đổi mật khẩu -->
        <div class="col-md-6 form-group">
          <label class="font-weight-bold">Mật khẩu mới</label>
          <input type="password" 
                 name="password" 
                 class="form-control" 
                 placeholder="Để trống nếu không muốn đổi mật khẩu">
        </div>

        <!-- Chọn Quyền hạn -->
        <div class="col-md-6 form-group">
          <label class="font-weight-bold">Vai trò <span class="text-danger">*</span></label>
          <select name="role_id" class="form-control">
            <option value="3" <?= ($role_id === 3) ? 'selected' : '' ?>>
              Nhân viên (Staff)
            </option>
            <option value="1" <?= ($role_id === 1) ? 'selected' : '' ?>>
              Quản trị viên (Admin)
            </option>
          </select>
        </div>
      </div>

      <hr class="my-3">
      <h6 class="font-weight-bold text-primary mb-3">
        <i class="fas fa-map-marked-alt mr-2"></i>Địa Chỉ Cư Trú / Làm Việc
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

        <!-- SỐ NHÀ, TÊN ĐƯỜNG -->
        <div class="col-12 form-group">
          <label class="font-weight-bold small text-muted">Số nhà, tên đường, thôn/xóm</label>
          <div class="input-group">
            <div class="input-group-prepend">
              <span class="input-group-text bg-light"><i class="fas fa-home text-muted"></i></span>
            </div>
            <input type="text" 
                   name="street" 
                   class="form-control" 
                   value="<?= htmlspecialchars($parsed_street) ?>" 
                   placeholder="Ví dụ: 123 Đường Trần Phú, Tòa nhà Landmark...">
          </div>
        </div>
      </div>

      <div class="mt-3">
        <button type="submit" class="btn btn-warning px-4 font-weight-bold text-white shadow-sm">
          <i class="fas fa-save mr-1"></i> Cập nhật thông tin
        </button>
      </div>
    </form>
  </div>
</main>

<?php include '../layout/scripts.php'; ?>

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

  // Tự động tắt Toast sau 3 giây
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