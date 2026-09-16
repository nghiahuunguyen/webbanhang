<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once('../../database/dbhelper.php');
require_once('../check_role.php');
require_once('../../config/mail_helper.php');

// Cho phép Admin (1) và Nhân viên (3) truy cập
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role_id'], [1, 3])) {
    header('Location: ../auth/login.php');
    die();
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    die();
}

$feedback = executeResult("SELECT * FROM `FeedBack` WHERE id = $id LIMIT 1", true);
if (!$feedback) {
    header('Location: index.php');
    die();
}

$fullname = trim(($feedback['firstname'] ?? '') . ' ' . ($feedback['lastname'] ?? ''));
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $toEmail      = trim($_POST['email'] ?? '');
    $mailSubject  = trim($_POST['subject'] ?? '');
    $replyContent = trim($_POST['content'] ?? '');

    if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        $error_msg = 'Địa chỉ email người nhận không hợp lệ!';
    } elseif (empty($mailSubject) || empty($replyContent)) {
        $error_msg = 'Vui lòng nhập đầy đủ tiêu đề và nội dung phản hồi!';
    } else {
        // Tạo giao diện HTML cho nội dung email
        $bodyHtml = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 25px; border: 1px solid #e2e8f0; border-radius: 12px; background: #ffffff;'>
            <div style='text-align: center; border-bottom: 2px solid #2563eb; padding-bottom: 15px; margin-bottom: 20px;'>
                <h2 style='color: #2563eb; margin: 0;'>" . MAIL_FROM_NAME . "</h2>
                <p style='color: #64748b; margin: 5px 0 0 0; font-size: 14px;'>Thư phản hồi ý kiến đóng góp</p>
            </div>

            <p>Kính gửi Quý khách <strong>{$fullname}</strong>,</p>
            <p>Cảm ơn bạn đã gửi ý kiến đóng góp về chủ đề <em>\"" . htmlspecialchars($feedback['subject_name'] ?? '') . "\"</em>.</p>
            
            <div style='background-color: #f8fafc; border-left: 4px solid #2563eb; padding: 15px; margin: 20px 0; font-size: 14px; line-height: 1.6; color: #1e293b; border-radius: 4px;'>
                " . nl2br(htmlspecialchars($replyContent)) . "
            </div>

            <p style='font-size: 14px; color: #475569;'>Nếu cần hỗ trợ thêm, bạn có thể phản hồi trực tiếp qua email này hoặc liên hệ hotline cửa hàng.</p>
            
            <div style='margin-top: 30px; padding-top: 15px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8; text-align: center;'>
                Trân trọng cảm ơn bạn đã đồng hành cùng " . MAIL_FROM_NAME . "!
            </div>
        </div>";

        // Gửi mail bằng hàm có sẵn trong config/mail_helper.php
        $isSent = sendCustomMail($toEmail, $fullname, $mailSubject, $bodyHtml);

        if ($isSent) {
            $_SESSION['success_msg'] = "Đã gửi email phản hồi tới {$toEmail} thành công!";
            header('Location: index.php');
            die();
        } else {
            $error_msg = 'Không thể gửi email. Vui lòng kiểm tra lại cấu hình tài khoản gửi trong mail_helper.php!';
        }
    }
}

$page_title = 'Gửi Phản Hồi Cho Khách Hàng';
include '../layout/head.php'; 
include '../layout/sidebar.php'; 
?>

<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-3">
  <?php include '../layout/topbar.php'; ?>

  <div class="custom-card bg-white rounded-lg shadow-sm border mb-4">
    <div class="p-3 px-4 d-flex justify-content-between align-items-center border-bottom flex-wrap">
      <div>
        <h5 class="font-weight-bold mb-0 text-dark">
          <i class="fas fa-paper-plane text-primary mr-2"></i>Gửi Thư Phản Hồi
        </h5>
        <small class="text-muted">Phản hồi yêu cầu của khách hàng: #<?= $feedback['id'] ?></small>
      </div>
      <a href="index.php" class="btn btn-sm btn-secondary font-weight-bold">
        <i class="fas fa-arrow-left mr-1"></i> Quay lại
      </a>
    </div>

    <div class="p-4">
      <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger font-weight-bold small">
          <i class="fas fa-exclamation-triangle mr-1"></i><?= htmlspecialchars($error_msg) ?>
        </div>
      <?php endif; ?>

      <!-- Khối tóm tắt nội dung của khách -->
      <div class="p-3 bg-light rounded border mb-4">
        <div class="row small mb-2">
          <div class="col-md-6"><strong>Khách hàng:</strong> <?= htmlspecialchars($fullname ?: 'Khách ẩn danh') ?></div>
          <div class="col-md-6"><strong>Số điện thoại:</strong> <?= htmlspecialchars($feedback['phone_number'] ?? '—') ?></div>
        </div>
        <div class="small mb-2"><strong>Chủ đề khách gửi:</strong> <span class="text-primary font-weight-bold"><?= htmlspecialchars($feedback['subject_name'] ?? '—') ?></span></div>
        <div class="small"><strong>Nội dung góp ý:</strong> <span class="text-muted"><?= htmlspecialchars($feedback['note'] ?? '') ?></span></div>
      </div>

      <!-- Form nhập nội dung gửi mail -->
      <form method="POST">
        <div class="form-group mb-3">
          <label class="font-weight-bold small text-dark">Gửi đến địa chỉ Email <span class="text-danger">*</span></label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($feedback['email'] ?? '') ?>" required>
        </div>

        <div class="form-group mb-3">
          <label class="font-weight-bold small text-dark">Tiêu đề thư phản hồi <span class="text-danger">*</span></label>
          <input type="text" name="subject" class="form-control font-weight-bold" value="Phản hồi từ <?= MAIL_FROM_NAME ?>: Về vấn đề <?= htmlspecialchars($feedback['subject_name'] ?? '') ?>" required>
        </div>

        <div class="form-group mb-4">
          <label class="font-weight-bold small text-dark">Nội dung phản hồi <span class="text-danger">*</span></label>
          <textarea name="content" rows="6" class="form-control" placeholder="Nhập nội dung giải đáp, cảm ơn hoặc hỗ trợ tới khách hàng..." required>Chào <?= htmlspecialchars($fullname ?: 'bạn') ?>,

Cảm ơn bạn đã đóng góp ý kiến cho cửa hàng. Về vấn đề bạn phản ánh, chúng tôi xin phản hồi như sau:

</textarea>
        </div>

        <button type="submit" class="btn btn-primary px-4 font-weight-bold shadow-sm">
          <i class="fas fa-paper-plane mr-1"></i> Gửi Thư Ngay
        </button>
      </form>
    </div>
  </div>
</main>

<?php include '../layout/scripts.php'; ?>