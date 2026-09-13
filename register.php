<?php 
  require_once('database/dbhelper.php');

  $toast_type    = ''; 
  $toast_message = '';

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $fullname     = trim($_POST['fullname'] ?? '');
      $email        = trim($_POST['email'] ?? '');
      $phone_number = trim($_POST['phone_number'] ?? '');
      $pwd          = $_POST['password'] ?? '';
      $confirm_pwd  = $_POST['confirm_password'] ?? '';
      $terms        = $_POST['terms'] ?? '';

      // 1. Kiểm tra họ và tên
      if (empty($fullname)) {
          $toast_type    = 'danger';
          $toast_message = 'Vui lòng nhập họ và tên!';

      // 2. Kiểm tra định dạng Email
      } elseif (empty($email)) {
          $toast_type    = 'danger';
          $toast_message = 'Vui lòng nhập địa chỉ Email!';
      } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
          $toast_type    = 'danger';
          $toast_message = 'Địa chỉ Email không đúng định dạng (Ví dụ: name@example.com)!';

      // 3. Kiểm tra số điện thoại: Đúng 10 chữ số và bắt đầu bằng số 0
      } elseif (empty($phone_number)) {
          $toast_type    = 'danger';
          $toast_message = 'Vui lòng nhập số điện thoại!';
      } elseif (!preg_match('/^0[0-9]{9}$/', $phone_number)) {
          $toast_type    = 'danger';
          $toast_message = 'Số điện thoại phải gồm đúng 10 số và bắt đầu bằng số 0!';

      // 4. Kiểm tra mật khẩu: Ít nhất 6 ký tự, gồm chữ, số và ký tự đặc biệt
      } elseif (empty($pwd)) {
          $toast_type    = 'danger';
          $toast_message = 'Vui lòng nhập mật khẩu!';
      } elseif (strlen($pwd) < 6) {
          $toast_type    = 'danger';
          $toast_message = 'Mật khẩu phải có độ dài ít nhất 6 ký tự!';
      } elseif (!preg_match('/[A-Za-z]/', $pwd) || !preg_match('/[0-9]/', $pwd) || !preg_match('/[@$!%*#?&_\-\.]/', $pwd)) {
          $toast_type    = 'danger';
          $toast_message = 'Mật khẩu phải bao gồm cả chữ cái, chữ số và ít nhất 1 ký tự đặc biệt (@$!%*#?&...)!';

      // 5. Kiểm tra xác nhận mật khẩu
      } elseif (empty($confirm_pwd)) {
          $toast_type    = 'danger';
          $toast_message = 'Vui lòng xác nhận lại mật khẩu!';
      } elseif ($pwd !== $confirm_pwd) {
          $toast_type    = 'danger';
          $toast_message = 'Mật khẩu và xác nhận mật khẩu không khớp!';

      // 6. Kiểm tra điều khoản
      } elseif (empty($terms)) {
          $toast_type    = 'danger';
          $toast_message = 'Bạn phải đồng ý với Điều khoản & Chính sách!';
      } else {
          $fullname_escaped     = addslashes($fullname);
          $email_escaped        = addslashes($email);
          $phone_number_escaped = addslashes($phone_number);
          $password_hash        = md5($pwd);

          // 1. Kiểm tra xem Email đã được tài khoản có mật khẩu nào dùng chưa
          $sql_check_email = "SELECT id FROM `User` 
                              WHERE email = '$email_escaped' 
                                AND password IS NOT NULL 
                                AND password != '' 
                                AND deleted = 0 
                              LIMIT 1";
          $emailInUse = executeResult($sql_check_email, true);

          if ($emailInUse != null) {
              $toast_type    = 'danger';
              $toast_message = 'Địa chỉ Email này đã được sử dụng bởi một tài khoản khác!';
          } else {
              // 2. Kiểm tra theo số điện thoại
              $sql_check_phone = "SELECT * FROM `User` 
                                  WHERE phone_number = '$phone_number_escaped' 
                                    AND deleted = 0 
                                  LIMIT 1";
              $existingUser = executeResult($sql_check_phone, true);

              if ($existingUser != null) {
                  // Trường hợp khách mua Offline: Đã có SĐT nhưng chưa có mật khẩu
                  if (empty($existingUser['password'])) {
                      $existing_id = intval($existingUser['id']);
                      $sql_update = "UPDATE `User` 
                                     SET fullname = '$fullname_escaped',
                                         email = '$email_escaped',
                                         account = '$phone_number_escaped',
                                         password = '$password_hash',
                                         updated_at = NOW() 
                                     WHERE id = $existing_id";
                      execute($sql_update);

                      $toast_type    = 'success';
                      $toast_message = 'Kích hoạt tài khoản thành công! Dữ liệu mua hàng trước đây của bạn đã được đồng bộ.';
                      header("refresh:2;url=login.php");
                  } else {
                      // Đã có tài khoản web đầy đủ
                      $toast_type    = 'danger';
                      $toast_message = 'Số điện thoại này đã được đăng ký tài khoản! Vui lòng đăng nhập.';
                  }
              } else {
                  // Khách hàng hoàn toàn mới: Thêm bản ghi mới
                  $sql_insert = "INSERT INTO `User` (fullname, email, phone_number, address, account, password, role_id, created_at, updated_at, deleted) 
                                 VALUES ('$fullname_escaped', '$email_escaped', '$phone_number_escaped', NULL, '$phone_number_escaped', '$password_hash', 2, NOW(), NOW(), 0)";
                  execute($sql_insert);

                  $toast_type    = 'success';
                  $toast_message = 'Đăng ký tài khoản thành công! Đang chuyển hướng...';
                  header("refresh:2;url=login.php");
              }
          }
      }
  }

  include 'includes/header.php';
?>

  <!-- THÔNG BÁO NỔI GÓC TRÊN BÊN PHẢI (TỰ ẨN SAU 3 GIÂY) -->
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

  <main class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-7 col-lg-6">
        <div class="card border-0 rounded-2xl shadow-soft p-4 p-md-5 bg-white">
          <div class="text-center mb-4">
            <h3 class="font-weight-bold text-dark mb-1">Tạo Tài Khoản</h3>
            <p class="text-muted small">Đăng ký để nhận nhiều ưu đãi thành viên</p>
          </div>

          <form action="" method="POST" novalidate>
            <div class="form-group mb-3">
              <label class="font-weight-semibold small text-muted">Họ và tên</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text bg-light border-0 rounded-left"><i class="far fa-user text-muted"></i></span>
                </div>
                <input type="text" name="fullname" class="form-control bg-light border-0 py-2" placeholder="Nguyễn Văn A" value="<?php echo htmlspecialchars($_POST['fullname'] ?? ''); ?>">
              </div>
            </div>

            <div class="form-group mb-3">
              <label class="font-weight-semibold small text-muted">Địa chỉ Email</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text bg-light border-0 rounded-left"><i class="far fa-envelope text-muted"></i></span>
                </div>
                <input type="email" name="email" class="form-control bg-light border-0 py-2" placeholder="name@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
              </div>
            </div>

            <div class="form-group mb-3">
              <label class="font-weight-semibold small text-muted">Số điện thoại (10 chữ số)</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text bg-light border-0 rounded-left"><i class="fas fa-phone-alt text-muted"></i></span>
                </div>
                <input type="tel" name="phone_number" class="form-control bg-light border-0 py-2" placeholder="0901234567" value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>">
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 form-group mb-3">
                <label class="font-weight-semibold small text-muted">Mật khẩu</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text bg-light border-0 rounded-left"><i class="fas fa-lock text-muted"></i></span>
                  </div>
                  <input type="password" name="password" class="form-control bg-light border-0 py-2" placeholder="Tối thiểu 6 ký tự">
                </div>
                <small class="text-muted" style="font-size: 0.72rem;">Gồm chữ cái, số và ký tự đặc biệt</small>
              </div>
              <div class="col-md-6 form-group mb-3">
                <label class="font-weight-semibold small text-muted">Xác nhận mật khẩu</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text bg-light border-0 rounded-left"><i class="fas fa-check-circle text-muted"></i></span>
                  </div>
                  <input type="password" name="confirm_password" class="form-control bg-light border-0 py-2" placeholder="Nhập lại mật khẩu">
                </div>
              </div>
            </div>

            <div class="custom-control custom-checkbox mb-4">
              <input type="checkbox" name="terms" value="1" class="custom-control-input" id="termsCheck" checked>
              <label class="custom-control-label small text-muted" for="termsCheck">
                Tôi đồng ý với <a href="terms.php" target="_blank" class="text-primary font-weight-bold">Điều khoản & Chính sách</a> của shop
              </label>
            </div>

            <button type="submit" class="btn btn-primary btn-block rounded-pill-custom py-2 font-weight-bold shadow-sm">
              Đăng Ký Tài Khoản
            </button>
          </form>

          <p class="text-center text-muted small mt-4 mb-0">
            Đã có tài khoản? <a href="login.php" class="text-primary font-weight-bold">Đăng nhập tại đây</a>
          </p>
        </div>
      </div>
    </div>
  </main>

<?php 
  include 'includes/footer.php'; 
?>

<!-- SCRIPT TỰ BIẾN MẤT TOAST SAU 3 GIÂY -->
<script>
  window.addEventListener('load', function() {
    var toastBox = document.getElementById('toast-wrapper');
    if (toastBox) {
      setTimeout(function() {
        toastBox.style.opacity = '0';
        toastBox.style.transform = 'translateX(50px)';
        setTimeout(function() {
          toastBox.remove();
        }, 500);
      }, 3000);
    }
  });
</script>