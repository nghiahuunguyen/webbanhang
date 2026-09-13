<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once('database/dbhelper.php');
require_once('config/mail_helper.php');

if (empty($_SESSION['reset_email'])) {
    header('Location: forgot-password.php');
    die();
}

$toast_type = 'danger';
$toast_message = '';

// 1. XỬ LÝ KHI NGƯỜI DÙNG BẤM "GỬI LẠI MÃ" (RESEND OTP)
if (isset($_GET['action']) && $_GET['action'] === 'resend') {
    $email = $_SESSION['reset_email'];
    $safe_email = str_replace("'", "\'", $email);
    $checkUser = executeResult("SELECT fullname FROM `User` WHERE email = '$safe_email' AND deleted = 0", true);

    if ($checkUser) {
        $otp = rand(100000, 999999);
        $_SESSION['reset_otp']  = (string)$otp;
        $_SESSION['otp_expire'] = time() + 300; // Hiệu lực 5 phút

        $subject = 'Mã xác thực OTP mới của bạn';
        $content = "
            <div style='font-family: Arial, sans-serif; max-width: 500px; margin: auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                <h3 style='color: #2b6cb0; text-align: center;'>Mã Xác Thực OTP Mới</h3>
                <p>Xin chào <strong>{$checkUser['fullname']}</strong>,</p>
                <p>Mã OTP mới của bạn là:</p>
                <div style='font-size: 28px; font-weight: bold; color: #e53e3e; text-align: center; letter-spacing: 5px; margin: 20px 0;'>
                    {$otp}
                </div>
                <p style='color: #718096; font-size: 13px;'>Mã này có hiệu lực trong 5 phút.</p>
            </div>";

        if (sendCustomMail($email, $checkUser['fullname'], $subject, $content)) {
            $toast_type = 'success';
            $toast_message = 'Mã OTP mới đã được gửi vào email của bạn!';
        } else {
            $toast_message = 'Không thể gửi lại email. Vui lòng thử lại sau!';
        }
    }
}

// 2. XỬ LÝ KHI NGƯỜI DÙNG BẤM "XÁC NHẬN MÃ"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp_arr = $_POST['otp'] ?? [];
    $entered_otp = implode('', $otp_arr);

    if (empty($_SESSION['reset_otp']) || time() > ($_SESSION['otp_expire'] ?? 0)) {
        $toast_message = 'Mã OTP đã hết hạn! Vui lòng nhấn nút gửi lại.';
        unset($_SESSION['reset_otp'], $_SESSION['otp_expire']);
    } elseif ($entered_otp !== $_SESSION['reset_otp']) {
        $toast_message = 'Mã OTP không chính xác!';
    } else {
        $_SESSION['otp_verified'] = true;
        unset($_SESSION['reset_otp'], $_SESSION['otp_expire']);
        header('Location: reset-password.php');
        die();
    }
}

include 'includes/header.php'; 
?>

<!-- TOAST THÔNG BÁO GÓC TRÊN PHẢI (TỰ TẮT SAU 3S) -->
<?php if (!empty($toast_message)): ?>
  <div id="toast-wrapper" style="position: fixed; top: 25px; right: 25px; z-index: 99999; min-width: 320px; transition: all 0.5s ease;">
    <div class="toast show shadow-lg border-0" role="alert">
      <div class="toast-header border-0 bg-white py-2 px-3">
        <?php if ($toast_type === 'success'): ?>
          <strong class="mr-auto text-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i> Thành Công</strong>
        <?php else: ?>
          <strong class="mr-auto text-danger font-weight-bold"><i class="fas fa-exclamation-circle mr-1"></i> Thông Báo</strong>
        <?php endif; ?>
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
      <div class="card border-0 rounded-2xl shadow-soft p-4 p-md-5 bg-white text-center">
        <div class="badge badge-light rounded-circle p-3 mb-3 text-success" style="font-size: 1.5rem;">
          <i class="fas fa-shield-alt"></i>
        </div>
        <h3 class="font-weight-bold text-dark mb-1">Xác Thực OTP</h3>
        <p class="text-muted small mb-4">
          Mã xác thực đã được gửi tới <strong><?= htmlspecialchars($_SESSION['reset_email']) ?></strong>
        </p>

        <form action="" method="POST">
          <div class="d-flex justify-content-center mb-4">
            <?php for ($i = 0; $i < 6; $i++): ?>
              <input type="text" 
                     name="otp[]" 
                     maxlength="1" 
                     class="form-control text-center font-weight-bold mx-1 border rounded-lg otp-input" 
                     style="width: 46px; height: 52px; font-size: 1.25rem;" 
                     required 
                     <?= ($i === 0) ? 'autofocus' : '' ?> 
                     pattern="[0-9]*" 
                     inputmode="numeric">
            <?php endfor; ?>
          </div>

          <button type="submit" class="btn btn-primary btn-block rounded-pill-custom py-2 font-weight-bold shadow-sm mb-3">
            Xác Nhận Mã
          </button>

          <!-- KHU VỰC NÚT GỬI LẠI ĐẾM NGƯỢC 30 GIÂY -->
          <div class="text-muted small">
            Không nhận được mã? 
            <span id="countdown-text" class="text-muted font-weight-bold">Gửi lại sau (<span id="timer">30</span>s)</span>
            <a href="verify-otp.php?action=resend" id="resend-link" class="text-primary font-weight-bold d-none">Gửi lại ngay</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</main>

<?php include 'includes/footer.php'; ?>

<script>
  // 1. Tự động nhảy ô khi gõ OTP
  const inputs = document.querySelectorAll('.otp-input');
  inputs.forEach((input, index) => {
    input.addEventListener('input', (e) => {
      if (e.target.value.length === 1 && index < inputs.length - 1) {
        inputs[index + 1].focus();
      }
    });
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Backspace' && e.target.value === '' && index > 0) {
        inputs[index - 1].focus();
      }
    });
  });

  // 2. Đếm ngược 30 giây cho nút Gửi lại mã
  let timeLeft = 30;
  const timerElement = document.getElementById('timer');
  const countdownText = document.getElementById('countdown-text');
  const resendLink = document.getElementById('resend-link');

  const countdown = setInterval(function() {
    timeLeft--;
    if (timerElement) {
      timerElement.textContent = timeLeft;
    }
    
    if (timeLeft <= 0) {
      clearInterval(countdown);
      countdownText.classList.add('d-none');
      resendLink.classList.remove('d-none');
    }
  }, 1000);

  // 3. Tự ẩn Toast thông báo sau 3 giây
  window.addEventListener('load', function() {
    var toastBox = document.getElementById('toast-wrapper');
    if (toastBox) {
      setTimeout(function() {
        toastBox.style.opacity = '0';
        setTimeout(function() {
          toastBox.remove();
        }, 500);
      }, 3000);
    }
  });
</script>