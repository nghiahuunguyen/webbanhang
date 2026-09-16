<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once __DIR__ . '/database/dbhelper.php';

$success_msg = '';
$error_msg   = '';

// Tự động phân tách họ và tên nếu người dùng đã đăng nhập
$firstname = '';
$lastname  = '';
$email     = $_SESSION['user']['email'] ?? '';
$phone     = $_SESSION['user']['phone_number'] ?? '';

if (!empty($_SESSION['user']['fullname'])) {
    $parts = explode(' ', trim($_SESSION['user']['fullname']));
    if (count($parts) > 1) {
        $lastname  = array_pop($parts);
        $firstname = implode(' ', $parts);
    } else {
        $lastname = $parts[0];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname  = trim($_POST['lastname'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone_number'] ?? '');
    $subject   = trim($_POST['subject_name'] ?? '');
    $note      = trim($_POST['note'] ?? '');

    if (empty($firstname) || empty($lastname) || empty($email) || empty($subject) || empty($note)) {
        $error_msg = 'Vui lòng điền đầy đủ tất cả các trường thông tin bắt buộc!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = 'Địa chỉ email không đúng định dạng!';
    } else {
        $safe_firstname = str_replace("'", "\'", $firstname);
        $safe_lastname  = str_replace("'", "\'", $lastname);
        $safe_email     = str_replace("'", "\'", $email);
        $safe_phone     = str_replace("'", "\'", $phone);
        $safe_subject   = str_replace("'", "\'", $subject);
        $safe_note      = str_replace("'", "\'", $note);

        $sql = "INSERT INTO `FeedBack` (`firstname`, `lastname`, `email`, `phone_number`, `subject_name`, `note`, `created_at`)
                VALUES ('$safe_firstname', '$safe_lastname', '$safe_email', '$safe_phone', '$safe_subject', '$safe_note', NOW())";
        execute($sql);

        $success_msg = 'Cảm ơn bạn đã gửi ý kiến! NovaShop sẽ tiếp nhận và xử lý phản hồi trong thời gian sớm nhất.';

        if (!isset($_SESSION['user'])) {
            $firstname = $lastname = $email = $phone = '';
        }
    }
}

$page_title = 'Gửi Ý Kiến Phản Hồi - NovaShop';
include 'includes/header.php';
?>

<!-- TOAST THÀNH CÔNG GÓC PHẢI TRÊN (TỰ BIẾN MẤT SAU 3 GIÂY)[cite: 6, 8] -->
<?php if (!empty($success_msg)): ?>
  <div id="toast-wrapper" style="position: fixed; top: 25px; right: 25px; z-index: 99999; min-width: 320px; max-width: 380px; transition: all 0.5s ease;">
    <div class="toast show shadow-lg border-0" role="alert" style="border-radius: 12px; overflow: hidden;">
      <div class="toast-header bg-success text-white py-2">
        <i class="fas fa-check-circle mr-2"></i>
        <strong class="mr-auto">Gửi thành công</strong>
        <small class="text-white">Vừa xong</small>
        <button type="button" class="ml-2 mb-1 close text-white" onclick="document.getElementById('toast-wrapper').remove()" style="outline: none;">
          <span>&times;</span>
        </button>
      </div>
      <div class="toast-body bg-white text-dark small" style="line-height: 1.5;">
        <?= htmlspecialchars($success_msg) ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- TOAST LỖI GÓC PHẢI TRÊN (TỰ BIẾN MẤT SAU 3 GIÂY)[cite: 4, 9] -->
<?php if (!empty($error_msg)): ?>
  <div id="toast-wrapper" style="position: fixed; top: 25px; right: 25px; z-index: 99999; min-width: 320px; max-width: 380px; transition: all 0.5s ease;">
    <div class="toast show shadow-lg border-0" role="alert" style="border-radius: 12px; overflow: hidden;">
      <div class="toast-header bg-danger text-white py-2">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <strong class="mr-auto">Thông báo lỗi</strong>
        <small class="text-white">Vừa xong</small>
        <button type="button" class="ml-2 mb-1 close text-white" onclick="document.getElementById('toast-wrapper').remove()" style="outline: none;">
          <span>&times;</span>
        </button>
      </div>
      <div class="toast-body bg-white text-dark small" style="line-height: 1.5;">
        <?= htmlspecialchars($error_msg) ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<main class="container my-5">
  <div class="row justify-content-center">
    <div class="col-lg-8 col-md-10">
      
      <div class="card border-0 rounded-2xl shadow-soft p-4 p-md-5 bg-white">
        <div class="text-center mb-4">
          <span class="badge badge-primary rounded-circle p-3 mb-3" style="background-color: #eff6ff; color: #2563eb;">
            <i class="fas fa-envelope-open-text fa-2x"></i>
          </span>
          <h3 class="font-weight-bold text-dark mb-2">Đóng Góp Ý Kiến & Phản Hồi</h3>
          <p class="text-muted small">Ý kiến của bạn là cơ sở quý giá giúp NovaShop nâng cao chất lượng dịch vụ mỗi ngày.</p>
        </div>

        <form method="POST" action="feedback.php">
          <div class="form-row">
            <div class="form-group col-md-6 mb-3">
              <label class="font-weight-bold small text-dark">Họ & Tên đệm <span class="text-danger">*</span></label>
              <input type="text" name="firstname" class="form-control rounded-pill-custom bg-light border-0 py-2 px-3" value="<?= htmlspecialchars($firstname) ?>" placeholder="VD: Nguyễn Văn" required>
            </div>
            <div class="form-group col-md-6 mb-3">
              <label class="font-weight-bold small text-dark">Tên <span class="text-danger">*</span></label>
              <input type="text" name="lastname" class="form-control rounded-pill-custom bg-light border-0 py-2 px-3" value="<?= htmlspecialchars($lastname) ?>" placeholder="VD: An" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-6 mb-3">
              <label class="font-weight-bold small text-dark">Địa chỉ Email <span class="text-danger">*</span></label>
              <input type="email" name="email" class="form-control rounded-pill-custom bg-light border-0 py-2 px-3" value="<?= htmlspecialchars($email) ?>" placeholder="name@example.com" required>
            </div>
            <div class="form-group col-md-6 mb-3">
              <label class="font-weight-bold small text-dark">Số điện thoại liên hệ</label>
              <input type="tel" name="phone_number" class="form-control rounded-pill-custom bg-light border-0 py-2 px-3" value="<?= htmlspecialchars($phone) ?>" placeholder="Số điện thoại của bạn...">
            </div>
          </div>

          <div class="form-group mb-3">
            <label class="font-weight-bold small text-dark">Chủ đề phản hồi <span class="text-danger">*</span></label>
            <input type="text" name="subject_name" class="form-control rounded-pill-custom bg-light border-0 py-2 px-3" placeholder="VD: Góp ý chất lượng phục vụ, hỗ trợ đổi trả..." required>
          </div>

          <div class="form-group mb-4">
            <label class="font-weight-bold small text-dark">Nội dung chi tiết <span class="text-danger">*</span></label>
            <textarea name="note" rows="5" class="form-control rounded-2xl bg-light border-0 p-3" placeholder="Chia sẻ chi tiết vấn đề bạn gặp phải hoặc ý kiến đóng góp cho shop..." required></textarea>
          </div>

          <div class="text-center">
            <button type="submit" class="btn btn-primary rounded-pill-custom px-5 py-2 font-weight-bold shadow-sm" style="background-color: #2563eb; border-color: #2563eb;">
              <i class="fas fa-paper-plane mr-2"></i> Gửi Phản Hồi Ngay
            </button>
          </div>
        </form>

      </div>

    </div>
  </div>
</main>

<?php include 'includes/footer.php'; ?>

<!-- SCRIPT TỰ ĐỘNG BIẾN MẤT TOAST SAU 3 GIÂY[cite: 4, 6] -->
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