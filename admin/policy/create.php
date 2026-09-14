<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once('../../database/dbhelper.php');
require_once('../check_role.php');

// Chỉ cho phép Admin truy cập
if (!isset($_SESSION['user']) || (int)$_SESSION['user']['role_id'] !== 1) {
    header('Location: ../dashboard/index.php');
    die();
}

$id = intval($_GET['id'] ?? 0);
$policy = null;
if ($id > 0) {
    $policy = executeResult("SELECT * FROM `Policy` WHERE id = $id LIMIT 1", true);
}

$error = '';

// Hàm chuyển đổi tiêu đề thành slug URL chuẩn tiếng Việt
function createSlug($str) {
    $str = trim(mb_strtolower($str, 'UTF-8'));
    $str = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $str);
    $str = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $str);
    $str = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $str);
    $str = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $str);
    $str = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $str);
    $str = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $str);
    $str = preg_replace('/(đ)/', 'd', $str);
    $str = preg_replace('/[^a-z0-9-\s]/', '', $str);
    $str = preg_replace('/([\s]+)/', '-', $str);
    return trim($str, '-');
}

// Xử lý gửi Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_save'])) {
    $title      = trim($_POST['title'] ?? '');
    $slug       = trim($_POST['slug'] ?? '');
    $group_type = $_POST['group_type'] ?? 'policy';
    $content    = trim($_POST['content'] ?? '');
    $sort_order = intval($_POST['sort_order'] ?? 0);
    $is_active  = isset($_POST['is_active']) ? 1 : 0;

    if (empty($title)) {
        $error = 'Vui lòng nhập tiêu đề bài viết!';
    } else {
        if (empty($slug)) {
            $slug = createSlug($title);
        } else {
            $slug = createSlug($slug);
        }

        $safe_title = str_replace("'", "\'", $title);
        $safe_slug  = str_replace("'", "\'", $slug);
        $safe_cont  = str_replace("'", "\'", $content);

        // Kiểm tra slug trùng lặp
        $check_sql = "SELECT id FROM `Policy` WHERE `slug` = '$safe_slug' " . ($id > 0 ? "AND id != $id" : "") . " LIMIT 1";
        $check = executeResult($check_sql, true);

        if ($check) {
            $error = 'Đường dẫn (Slug) này đã tồn tại, vui lòng chọn tiêu đề hoặc slug khác!';
        } else {
            if ($id > 0) {
                // Cập nhật
                $sql = "UPDATE `Policy` 
                        SET `title` = '$safe_title', 
                            `slug` = '$safe_slug', 
                            `content` = '$safe_cont', 
                            `group_type` = '$group_type', 
                            `sort_order` = $sort_order, 
                            `is_active` = $is_active 
                        WHERE id = $id";
                execute($sql);
                $_SESSION['policy_msg'] = "Cập nhật bài viết '{$title}' thành công!";
            } else {
                // Thêm mới
                $sql = "INSERT INTO `Policy` (`title`, `slug`, `content`, `group_type`, `sort_order`, `is_active`) 
                        VALUES ('$safe_title', '$safe_slug', '$safe_cont', '$group_type', $sort_order, $is_active)";
                execute($sql);
                $_SESSION['policy_msg'] = "Thêm mới bài viết chính sách thành công!";
            }
            header('Location: index.php');
            die();
        }
    }
}

$page_title = ($id > 0) ? 'Chỉnh Sửa Chính Sách' : 'Thêm Mới Chính Sách';
include '../layout/head.php';
include '../layout/sidebar.php';
?>

<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-3">
  <?php include '../layout/topbar.php'; ?>

  <div class="row justify-content-center">
    <div class="col-lg-10">
      <div class="card border-0 shadow-sm rounded-lg bg-white">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
          <h5 class="font-weight-bold mb-0 text-dark"><?= ($id > 0) ? 'Chỉnh Sửa Bài Viết' : 'Thêm Mới Bài Viết Chính Sách' ?></h5>
          <a href="index.php" class="btn btn-outline-secondary btn-sm font-weight-bold">
            <i class="fas fa-arrow-left mr-1"></i> Quay lại
          </a>
        </div>

        <div class="card-body p-4">
          <?php if (!empty($error)): ?>
            <div class="alert alert-danger font-weight-bold small mb-4">
              <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
            </div>
          <?php endif; ?>

          <form action="" method="POST">
            <div class="form-row">
              <div class="form-group col-md-8 mb-3">
                <label class="font-weight-bold small text-muted">TIÊU ĐỀ CHÍNH SÁCH / HỖ TRỢ <span class="text-danger">*</span></label>
                <input type="text" name="title" id="policyTitle" class="form-control" placeholder="Ví dụ: Chính sách bảo hành" value="<?= htmlspecialchars($_POST['title'] ?? ($policy['title'] ?? '')) ?>" required>
              </div>

              <div class="form-group col-md-4 mb-3">
                <label class="font-weight-bold small text-muted">NHÓM HIỂN THỊ CHÂN TRANG</label>
                <select name="group_type" class="form-control">
                  <?php $gt = $_POST['group_type'] ?? ($policy['group_type'] ?? 'policy'); ?>
                  <option value="policy" <?= ($gt === 'policy') ? 'selected' : '' ?>>Chính Sách (Cột 2 Footer)</option>
                  <option value="support" <?= ($gt === 'support') ? 'selected' : '' ?>>Hỗ Trợ (Cột 3 Footer)</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-8 mb-3">
                <label class="font-weight-bold small text-muted">ĐƯỜNG DẪN THÂN THIỆN (SLUG)</label>
                <input type="text" name="slug" id="policySlug" class="form-control" placeholder="Tự tạo tự động nếu để trống (ví dụ: chinh-sach-bao-hanh)" value="<?= htmlspecialchars($_POST['slug'] ?? ($policy['slug'] ?? '')) ?>">
              </div>

              <div class="form-group col-md-4 mb-3">
                <label class="font-weight-bold small text-muted">THỨ TỰ HIỂN THỊ</label>
                <input type="number" name="sort_order" class="form-control" value="<?= intval($_POST['sort_order'] ?? ($policy['sort_order'] ?? 0)) ?>" min="0">
              </div>
            </div>

            <div class="form-group mb-3">
              <label class="font-weight-bold small text-muted">NỘI DUNG CHI TIẾT BÀI VIẾT (HỖ TRỢ HTML)</label>
              <textarea name="content" rows="12" class="form-control" placeholder="Nhập các điều khoản, quy định và thông tin chi tiết..."><?= htmlspecialchars($_POST['content'] ?? ($policy['content'] ?? '')) ?></textarea>
              <small class="form-text text-muted">Bạn có thể viết văn bản thông thường hoặc dùng các thẻ &lt;p&gt;, &lt;h4&gt;, &lt;ul&gt;, &lt;li&gt;, &lt;strong&gt;.</small>
            </div>

            <div class="form-group mb-4">
              <div class="custom-control custom-checkbox">
                <?php 
                  $active = isset($_POST['title']) ? isset($_POST['is_active']) : (($policy['is_active'] ?? 1) == 1);
                ?>
                <input type="checkbox" class="custom-control-input" id="isActiveCheck" name="is_active" <?= $active ? 'checked' : '' ?>>
                <label class="custom-control-label font-weight-bold text-dark" for="isActiveCheck">
                  Hiển thị bài viết này trên website
                </label>
              </div>
            </div>

            <div class="d-flex justify-content-end" style="gap: 10px;">
              <a href="index.php" class="btn btn-light border px-4 font-weight-bold">Hủy</a>
              <button type="submit" name="btn_save" class="btn btn-primary px-4 font-weight-bold shadow-sm">
                <i class="fas fa-save mr-1"></i> Lưu Bài Viết
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</main>

<?php include '../layout/scripts.php'; ?>