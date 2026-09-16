<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once('../../database/dbhelper.php');
require_once('../check_role.php');

// Cho phép Admin (1) và Nhân viên (3) truy cập
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role_id'], [1, 3])) {
    header('Location: ../auth/login.php');
    die();
}

$id = $_GET['id'] ?? '';
if (empty($id)) {
    header('Location: index.php');
    die();
}

$id = intval($id);
$feedback = executeResult("SELECT * FROM `FeedBack` WHERE id = $id LIMIT 1", true);

if (!$feedback) {
    header('Location: index.php');
    die();
}

$fullname = trim(($feedback['firstname'] ?? '') . ' ' . ($feedback['lastname'] ?? ''));

$page_title = 'Chi Tiết Phản Hồi #' . $feedback['id'];
include '../layout/head.php'; 
include '../layout/sidebar.php'; 
?>

<!-- MAIN CONTENT -->
<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-3">
  <?php include '../layout/topbar.php'; ?>

  <div class="custom-card bg-white rounded-lg shadow-sm border mb-4">
    <!-- Header Card -->
    <div class="p-3 px-4 d-flex justify-content-between align-items-center border-bottom flex-wrap">
      <div>
        <h5 class="font-weight-bold mb-0 text-dark">
          <i class="fas fa-comment-dots text-primary mr-2"></i>Chi Tiết Phản Hồi #<?= $feedback['id'] ?>
        </h5>
        <small class="text-muted">
          <i class="far fa-clock mr-1"></i> Ngày gửi: <?= !empty($feedback['created_at']) ? date('d/m/Y H:i:s', strtotime($feedback['created_at'])) : '—' ?>
        </small>
      </div>
      <div class="mt-2 mt-md-0">
        <a href="index.php" class="btn btn-sm btn-secondary font-weight-bold mr-2">
          <i class="fas fa-arrow-left mr-1"></i> Quay lại
        </a>
        <a href="reply.php?id=<?= $feedback['id'] ?>" class="btn btn-sm btn-primary font-weight-bold shadow-sm">
          <i class="fas fa-paper-plane mr-1"></i> Gửi mail phản hồi
        </a>
      </div>
    </div>

    <!-- Body Card -->
    <div class="p-4">
      <div class="row">
        <!-- Cột trái: Thông tin người gửi -->
        <div class="col-md-5 mb-4 mb-md-0">
          <div class="border rounded p-3 bg-light h-100 d-flex flex-column">
            <h6 class="text-primary font-weight-bold text-uppercase small tracking-wider border-bottom pb-2 mb-3">
              <i class="fas fa-user-circle mr-1"></i> Thông tin khách hàng
            </h6>

            <div class="mb-3">
              <label class="text-muted small font-weight-bold mb-1 d-block">Họ và tên:</label>
              <div class="font-weight-bold text-dark h6 mb-0">
                <?= htmlspecialchars($fullname ?: 'Khách ẩn danh') ?>
              </div>
            </div>

            <div class="mb-3">
              <label class="text-muted small font-weight-bold mb-1 d-block">Địa chỉ Email:</label>
              <div class="text-dark">
                <?php if (!empty($feedback['email'])): ?>
                  <span class="text-primary font-weight-semibold">
                    <i class="fas fa-envelope mr-1 text-muted"></i><?= htmlspecialchars($feedback['email']) ?>
                  </span>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </div>
            </div>

            <div class="mb-3">
              <label class="text-muted small font-weight-bold mb-1 d-block">Số điện thoại:</label>
              <div class="text-dark">
                <?php if (!empty($feedback['phone_number'])): ?>
                  <span class="text-success font-weight-bold">
                    <i class="fas fa-phone-alt mr-1 text-muted"></i><?= htmlspecialchars($feedback['phone_number']) ?>
                  </span>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </div>
            </div>

            <?php if (!empty($feedback['email'])): ?>
              <div class="mt-auto pt-3 border-top">
                <a href="reply.php?id=<?= $feedback['id'] ?>" class="btn btn-sm btn-outline-primary btn-block font-weight-bold">
                  <i class="fas fa-reply mr-1"></i> Soạn thư phản hồi khách
                </a>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Cột phải: Nội dung chi tiết ý kiến -->
        <div class="col-md-7">
          <div class="border rounded p-3 bg-white h-100 d-flex flex-column">
            <h6 class="text-primary font-weight-bold text-uppercase small tracking-wider border-bottom pb-2 mb-3">
              <i class="fas fa-file-alt mr-1"></i> Nội dung phản ánh & góp ý
            </h6>

            <div class="mb-3">
              <label class="text-muted small font-weight-bold mb-1 d-block">Chủ đề:</label>
              <div class="p-2 rounded bg-light border font-weight-bold text-primary">
                <?= htmlspecialchars($feedback['subject_name'] ?? 'Không có chủ đề') ?>
              </div>
            </div>

            <div class="mb-3 flex-grow-1">
              <label class="text-muted small font-weight-bold mb-1 d-block">Nội dung chi tiết:</label>
              <div class="p-3 bg-light rounded text-secondary border" style="line-height: 1.8; min-height: 160px; white-space: pre-line;">
                <?= !empty($feedback['note']) ? htmlspecialchars($feedback['note']) : 'Không có nội dung phản hồi.' ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php include '../layout/scripts.php'; ?>