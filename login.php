<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once('database/dbhelper.php');

// Lấy link muốn chuyển hướng đến sau khi đăng nhập (mặc định là index.php)
$redirect_url = trim($_GET['redirect'] ?? ($_POST['redirect'] ?? ''));

// Nếu đã đăng nhập thì điều hướng về đúng trang tương ứng
if (isset($_SESSION['user'])) {
    $role = (int)($_SESSION['user']['role_id'] ?? 0);
    if ($role === 1 || $role === 3) {
        header('Location: admin/index.php');
    } else {
        header('Location: ' . (!empty($redirect_url) ? $redirect_url : 'index.php'));
    }
    die();
}

$toast_type = '';
$toast_message = '';

// Nhận thông báo thành công nếu chuyển hướng từ trang khác
if (!empty($_SESSION['success_msg'])) {
    $toast_type = 'success';
    $toast_message = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

// Lấy tài khoản đã lưu từ Cookie trình duyệt
$saved_account = $_COOKIE['remember_account'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account  = trim($_POST['account'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($account)) {
        $toast_type    = 'danger';
        $toast_message = 'Vui lòng nhập Tài khoản, Email hoặc Số điện thoại!';
    } elseif (empty($password)) {
        $toast_type    = 'danger';
        $toast_message = 'Vui lòng nhập mật khẩu!';
    } else {
        $safe_account  = str_replace("'", "\'", $account);
        $password_hash = md5($password);

        // Kiểm tra khớp tài khoản bằng account, email HOẶC phone_number
        $sql = "SELECT * FROM `User` 
                WHERE (account = '$safe_account' OR email = '$safe_account' OR phone_number = '$safe_account') 
                  AND password = '$password_hash' 
                  AND deleted = 0 
                LIMIT 1";
        $user = executeResult($sql, true);

        if ($user != null) {
            $_SESSION['user'] = $user;

            // Xử lý ghi nhớ tài khoản (30 ngày)
            if ($remember) {
                setcookie('remember_account', $account, time() + (86400 * 30), "/");
            } else {
                setcookie('remember_account', '', time() - 3600, "/");
            }

            // Phân luồng điều hướng:
            // Admin (1) hoặc Nhân viên (3) luôn vào trang quản trị
            // Khách hàng (2) quay lại trang xem dở (redirect) hoặc về trang chủ
            $user_role = (int)$user['role_id'];
            if ($user_role === 1 || $user_role === 3) {
                header('Location: admin/index.php');
            } else {
                if (!empty($redirect_url)) {
                    header('Location: ' . $redirect_url);
                } else {
                    header('Location: index.php');
                }
            }
            die();
        } else {
            $toast_type    = 'danger';
            $toast_message = 'Tài khoản hoặc mật khẩu không chính xác!';
        }
    }
}

include 'includes/header.php'; 
?>

<!-- TOAST THÔNG BÁO -->
<?php if (!empty($toast_message)): ?>
  <div id="toast-wrapper" style="position: fixed; top: 25px; right: 25px; z-index: 99999; min-width: 320px; transition: all 0.5s ease;">
    <div class="toast show shadow-lg border-0" role="alert">
      <div class="toast-header border-0 bg-white py-2 px-3">
        <?php if ($toast_type === 'success'): ?>
          <div class="btn-icon bg-success text-white rounded-circle mr-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 0.75rem;">
            <i class="fas fa-check"></i>
          </div>
          <strong class="mr-auto text-success font-weight-bold">Thành Công</strong>
        <?php else: ?>
          <div class="btn-icon bg-danger text-white rounded-circle mr-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 0.75rem;">
            <i class="fas fa-exclamation"></i>
          </div>
          <strong class="mr-auto text-danger font-weight-bold">Thông Báo</strong>
        <?php endif; ?>
        <small class="text-muted">Vừa xong</small>
        <button type="button" class="ml-2 mb-1 close" onclick="document.getElementById('toast-wrapper').remove()" style="outline: none;">&times;</button>
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
          <h3 class="font-weight-bold text-dark mb-1">Đăng Nhập</h3>
          <p class="text-muted small">Chào mừng bạn quay lại với hệ thống</p>
        </div>

        <form action="" method="POST" novalidate>
          <!-- Giữ lại link redirect khi bấm đăng nhập -->
          <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect_url) ?>">

          <div class="form-group mb-3">
            <label class="font-weight-semibold small text-muted">Tài khoản, Email hoặc Số điện thoại</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text bg-light border-0 rounded-left"><i class="far fa-user text-muted"></i></span>
              </div>
              <input type="text" 
                     name="account" 
                     class="form-control bg-light border-0 py-2" 
                     placeholder="Nhập tên đăng nhập, email hoặc SĐT..." 
                     value="<?= htmlspecialchars($_POST['account'] ?? $saved_account) ?>" 
                     required autofocus>
            </div>
          </div>

          <div class="form-group mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <label class="font-weight-semibold small text-muted mb-0">Mật khẩu</label>
              <a href="forgot-password.php" class="small text-primary font-weight-bold">Quên mật khẩu?</a>
            </div>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text bg-light border-0 rounded-left"><i class="fas fa-lock text-muted"></i></span>
              </div>
              <input type="password" 
                     name="password" 
                     class="form-control bg-light border-0 py-2" 
                     placeholder="••••••••" 
                     required>
            </div>
          </div>

          <div class="custom-control custom-checkbox mb-4">
            <input type="checkbox" 
                   name="remember" 
                   class="custom-control-input" 
                   id="rememberMe" 
                   <?= (!empty($saved_account) || isset($_POST['remember'])) ? 'checked' : '' ?>>
            <label class="custom-control-label small text-muted" for="rememberMe">Ghi nhớ đăng nhập</label>
          </div>

          <button type="submit" class="btn btn-primary btn-block rounded-pill-custom py-2 font-weight-bold shadow-sm">
            Đăng Nhập
          </button>
        </form>

        <p class="text-center text-muted small mt-4 mb-0">
          Chưa có tài khoản? <a href="register.php<?= !empty($redirect_url) ? '?redirect=' . urlencode($redirect_url) : '' ?>" class="text-primary font-weight-bold">Đăng ký ngay</a>
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
        toastBox.style.opacity = '0';
        toastBox.style.transform = 'translateX(50px)';
        setTimeout(function() {
          toastBox.remove();
        }, 500);
      }, 3000);
    }
  });
</script>