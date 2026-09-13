<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once('database/dbhelper.php');

if (empty($_SESSION['reset_email']) || empty($_SESSION['otp_verified'])) {
    header('Location: forgot-password.php');
    die();
}

$toast_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($password)) {
        $toast_message = 'Vui lòng nhập mật khẩu mới!';
    } elseif (strlen($password) < 8) {
        $toast_message = 'Mật khẩu mới phải có tối thiểu 8 ký tự!';
    } elseif ($password !== $confirm_password) {
        $toast_message = 'Xác nhận mật khẩu không khớp!';
    } else {
        $email = $_SESSION['reset_email'];
        $safe_email = str_replace("'", "\'", $email);
        $password_hash = md5($password);
        $updated_at = date('Y-m-d H:i:s');

        $sql = "UPDATE `User` SET password = '$password_hash', updated_at = '$updated_at' WHERE email = '$safe_email'";
        execute($sql);

        unset($_SESSION['reset_email'], $_SESSION['otp_verified']);

        $_SESSION['success_msg'] = 'Đặt lại mật khẩu thành công! Vui lòng đăng nhập.';
        header('Location: login.php');
        die();
    }
}

include 'includes/header.php'; 
?>

<?php if (!empty($toast_message)): ?>
  <div id="toast-wrapper" style="position: fixed; top: 25px; right: 25px; z-index: 99999; min-width: 320px; transition: all 0.5s ease;">
    <div class="toast show shadow-lg border-0" role="alert">
      <div class="toast-header border-0 bg-white py-2 px-3">
        <strong class="mr-auto text-danger font-weight-bold"><i class="fas fa-exclamation-circle mr-1"></i> Lỗi nhập liệu</strong>
        <button type="button" class="ml-2 mb-1 close" onclick="document.getElementById('toast-wrapper').remove()">&times;</button>
      </div>
      <div class="toast-body bg-white rounded-bottom px-3 pb-3 pt-0 text-dark font-weight-bold">
        <?= htmlspecialchars($toast_message); ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<main class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      <div class="card border-0 rounded-2xl shadow-soft p-4 p-md-5 bg-white">
        <div class="text-center mb-4">
          <div class="badge badge-light rounded-circle p-3 mb-3 text-warning" style="font-size: 1.5rem;">
            <i class="fas fa-lock-open"></i>
          </div>
          <h3 class="font-weight-bold text-dark mb-1">Đặt Lại Mật Khẩu</h3>
          <p class="text-muted small">Mật khẩu mới phải có tối thiểu 8 ký tự.</p>
        </div>

        <form action="" method="POST" novalidate>
          <div class="form-group mb-3">
            <label class="font-weight-semibold small text-muted">Mật khẩu mới</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text bg-light border-0 rounded-left"><i class="fas fa-lock text-muted"></i></span>
              </div>
              <input type="password" name="password" class="form-control bg-light border-0 py-2" placeholder="••••••••" required autofocus>
            </div>
          </div>

          <div class="form-group mb-4">
            <label class="font-weight-semibold small text-muted">Xác nhận mật khẩu mới</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text bg-light border-0 rounded-left"><i class="fas fa-check-circle text-muted"></i></span>
              </div>
              <input type="password" name="confirm_password" class="form-control bg-light border-0 py-2" placeholder="••••••••" required>
            </div>
          </div>

          <button type="submit" class="btn btn-primary btn-block rounded-pill-custom py-2 font-weight-bold shadow-sm">
            Lưu Mật Khẩu & Đăng Nhập
          </button>
        </form>
      </div>
    </div>
  </div>
</main>

<?php include 'includes/footer.php'; ?>

<script>
  window.addEventListener('load', function() {
    var toastBox = document.getElementById('toast-wrapper');
    if (toastBox) {
      setTimeout(function() {
        toastBox.remove();
      }, 3500);
    }
  });
</script>