<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once('database/dbhelper.php');
require_once('config/mail_helper.php');

$toast_type = '';
$toast_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $toast_type = 'danger';
        $toast_message = 'Vui lòng nhập địa chỉ Email!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $toast_type = 'danger';
        $toast_message = 'Định dạng Email không hợp lệ!';
    } else {
        $safe_email = str_replace("'", "\'", $email);
        $checkUser = executeResult("SELECT id, fullname FROM `User` WHERE email = '$safe_email' AND deleted = 0", true);

        if (!$checkUser) {
            $toast_type = 'danger';
            $toast_message = 'Email này không tồn tại trong hệ thống!';
        } else {
            $otp = rand(100000, 999999);
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_otp']   = (string)$otp;
            $_SESSION['otp_expire']  = time() + 300; // Hết hạn sau 5 phút

            $subject = 'Mã xác thực OTP đặt lại mật khẩu';
            $content = "
                <div style='font-family: Arial, sans-serif; max-width: 500px; margin: auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                    <h3 style='color: #2b6cb0; text-align: center;'>Yêu Cầu Đặt Lại Mật Khẩu</h3>
                    <p>Xin chào <strong>{$checkUser['fullname']}</strong>,</p>
                    <p>Mã xác thực OTP của bạn là:</p>
                    <div style='font-size: 28px; font-weight: bold; color: #e53e3e; text-align: center; letter-spacing: 5px; margin: 20px 0;'>
                        {$otp}
                    </div>
                    <p style='color: #718096; font-size: 13px;'>Mã này có hiệu lực trong 5 phút. Vui lòng không chia sẻ mã này cho bất kỳ ai.</p>
                </div>";

            if (sendCustomMail($email, $checkUser['fullname'], $subject, $content)) {
                header('Location: verify-otp.php');
                die();
            } else {
                $toast_type = 'danger';
                $toast_message = 'Không thể gửi email lúc này. Vui lòng kiểm tra lại kết nối mạng!';
            }
        }
    }
}

include 'includes/header.php'; 
?>

<?php if (!empty($toast_message)): ?>
  <div id="toast-wrapper" style="position: fixed; top: 25px; right: 25px; z-index: 99999; min-width: 320px; transition: all 0.5s ease;">
    <div class="toast show shadow-lg border-0" role="alert">
      <div class="toast-header border-0 bg-white py-2 px-3">
        <strong class="mr-auto text-danger font-weight-bold"><i class="fas fa-exclamation-circle mr-1"></i> Thông Báo</strong>
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
          <div class="badge badge-light rounded-circle p-3 mb-3 text-primary" style="font-size: 1.5rem;">
            <i class="fas fa-key"></i>
          </div>
          <h3 class="font-weight-bold text-dark mb-1">Quên Mật Khẩu?</h3>
          <p class="text-muted small">Nhập email của bạn để nhận mã xác thực OTP gồm 6 chữ số.</p>
        </div>

        <form action="" method="POST" novalidate>
          <div class="form-group mb-4">
            <label class="font-weight-semibold small text-muted">Địa chỉ Email đã đăng ký</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text bg-light border-0 rounded-left"><i class="far fa-envelope text-muted"></i></span>
              </div>
              <input type="email" name="email" class="form-control bg-light border-0 py-2" placeholder="name@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
            </div>
          </div>

          <button type="submit" class="btn btn-primary btn-block rounded-pill-custom py-2 font-weight-bold shadow-sm">
            Gửi Mã Xác Thực &rarr;
          </button>
        </form>

        <p class="text-center text-muted small mt-4 mb-0">
          Nhớ mật khẩu? <a href="login.php" class="text-primary font-weight-bold">Quay lại đăng nhập</a>
        </p>
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