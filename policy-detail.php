<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once __DIR__ . '/database/dbhelper.php';

$slug = trim($_GET['slug'] ?? '');
if (empty($slug)) {
    header('Location: index.php');
    exit();
}

$safe_slug = str_replace("'", "\'", $slug);
$sql = "SELECT * FROM `Policy` WHERE `slug` = '$safe_slug' AND `is_active` = 1 AND `deleted` = 0 LIMIT 1";
$policy = executeResult($sql, true);

if (!$policy) {
    header('Location: index.php');
    exit();
}

// Lấy danh sách các chính sách khác để hiển thị ở cột menu bên trái
$all_policies = executeResult("SELECT `title`, `slug`, `group_type` FROM `Policy` WHERE `is_active` = 1 AND `deleted` = 0 ORDER BY `sort_order` ASC");

$page_title = $policy['title'] . ' - NovaShop';
include 'includes/header.php';
?>

<main class="container my-5">
  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb bg-transparent p-0 small">
      <li class="breadcrumb-item"><a href="index.php" class="text-muted text-decoration-none">Trang chủ</a></li>
      <li class="breadcrumb-item text-muted"><?= ($policy['group_type'] === 'policy') ? 'Chính sách' : 'Hỗ trợ' ?></li>
      <li class="breadcrumb-item active text-primary font-weight-bold" aria-current="page"><?= htmlspecialchars($policy['title']) ?></li>
    </ol>
  </nav>

  <div class="row">
    <!-- CỘT TRÁI -->
    <div class="col-lg-3 col-md-4 mb-4">
      <div class="card border-0 rounded-2xl shadow-soft p-3 bg-white position-sticky" style="top: 90px;">
        <h6 class="font-weight-bold text-dark px-3 pt-2 mb-2">Trung Tâm Trợ Giúp</h6>
        <div class="list-group list-group-flush">
          <?php foreach ($all_policies as $p): 
              $is_cur = ($p['slug'] === $slug);
          ?>
            <a href="chinh-sach/<?= htmlspecialchars($p['slug']) ?>.html" 
               class="list-group-item list-group-item-action border-0 rounded-pill-custom py-2 px-3 small font-weight-bold <?= $is_cur ? 'text-primary bg-light' : 'text-secondary' ?>">
              <i class="fas fa-angle-right mr-2 <?= $is_cur ? 'text-primary' : 'text-muted' ?>"></i>
              <?= htmlspecialchars($p['title']) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- CỘT PHẢI -->
    <div class="col-lg-9 col-md-8">
      <div class="card border-0 rounded-2xl shadow-soft p-4 p-md-5 bg-white">
        <h3 class="font-weight-bold text-dark mb-2"><?= htmlspecialchars($policy['title']) ?></h3>
        <p class="small text-muted border-bottom pb-3 mb-4">
          <i class="far fa-clock mr-1"></i> Cập nhật lần cuối: <?= date('d/m/Y H:i', strtotime($policy['updated_at'])) ?>
        </p>

        <div class="policy-content" style="line-height: 1.8; font-size: 1rem; color: #334155;">
          <?= $policy['content'] ?>
        </div>

        <div class="border-top pt-4 mt-5 d-flex justify-content-between align-items-center flex-wrap" style="gap: 10px;">
          <small class="text-muted">Bạn vẫn còn câu hỏi thắc mắc? Liên hệ hotline: <strong class="text-primary">1900 6868</strong></small>
          <a href="index.php" class="btn btn-primary rounded-pill-custom px-4 font-weight-bold shadow-sm btn-sm">
            &larr; Về Trang Chủ
          </a>
        </div>
      </div>
    </div>
  </div>
</main>

<style>
.policy-content p { margin-bottom: 1rem; }
.policy-content ul, .policy-content ol { padding-left: 1.5rem; margin-bottom: 1rem; }
.policy-content h4, .policy-content h5 { font-weight: 700; color: #0f172a; margin-top: 1.5rem; margin-bottom: 0.75rem; }
</style>

<?php 
include 'includes/footer.php'; 
?>