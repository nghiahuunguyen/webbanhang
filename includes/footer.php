<?php
// 1. Lấy dữ liệu cấu hình hệ thống từ bảng System_Settings
$settings_query = executeResult("SELECT `key_name`, `key_value` FROM `System_Settings`");
$site_cfg = [];
if (!empty($settings_query)) {
    foreach ($settings_query as $st) {
        $site_cfg[$st['key_name']] = $st['key_value'];
    }
}

// 2. Lấy danh sách Chính sách và Hỗ trợ đang kích hoạt từ bảng Policy
$policies = executeResult("SELECT `title`, `slug` FROM `Policy` WHERE `group_type` = 'policy' AND `is_active` = 1 ORDER BY `sort_order` ASC, `id` ASC");
$supports = executeResult("SELECT `title`, `slug` FROM `Policy` WHERE `group_type` = 'support' AND `is_active` = 1 ORDER BY `sort_order` ASC, `id` ASC");
?>

<!-- ==================== PHẦN CUỐI (FOOTER & CHÍNH SÁCH) ==================== -->
  <footer class="bg-white border-top pt-5">
    <div class="container pb-4">
      
      <!-- Khối chính sách hiện đại -->
      <div class="row bg-light rounded-2xl p-4 mb-5 border">
        <div class="col-md-3 col-6 text-center my-2">
          <i class="fas fa-shipping-fast text-primary fa-lg mb-2"></i>
          <h6 class="font-weight-bold mb-1">Giao Siêu Tốc</h6>
          <small class="text-muted">Miễn phí mọi đơn trên 500k</small>
        </div>
        <div class="col-md-3 col-6 text-center my-2">
          <i class="fas fa-sync-alt text-primary fa-lg mb-2"></i>
          <h6 class="font-weight-bold mb-1">30 Ngày Đổi Trả</h6>
          <small class="text-muted">Đổi sản phẩm mới tức thì</small>
        </div>
        <div class="col-md-3 col-6 text-center my-2">
          <i class="fas fa-shield-alt text-primary fa-lg mb-2"></i>
          <h6 class="font-weight-bold mb-1">Hàng Chính Hãng</h6>
          <small class="text-muted">Bảo hành điện tử 12 tháng</small>
        </div>
        <div class="col-md-3 col-6 text-center my-2">
          <i class="fas fa-headset text-primary fa-lg mb-2"></i>
          <h6 class="font-weight-bold mb-1">Hỗ Trợ 24/7</h6>
          <small class="text-muted">Phản hồi nhanh trong 5 phút</small>
        </div>
      </div>

      <!-- Footer Links & Giới Thiệu -->
      <div class="row">
        <!-- Cột 1: Thương hiệu & Mạng xã hội -->
        <div class="col-lg-4 col-md-6 mb-4">
          <h5 class="font-weight-bold text-dark mb-3">
            <?= htmlspecialchars($site_cfg['site_name'] ?? 'NOVASHOP') ?>
          </h5>
          <p class="text-muted small pr-md-4">
            <?= htmlspecialchars($site_cfg['site_slogan'] ?? 'Đồng hành cùng phong cách sống hiện đại. Mua sắm dễ dàng, thanh toán linh hoạt và dịch vụ hậu mãi chuẩn 5 sao.') ?>
          </p>
          <div class="mt-3">
            <?php if (!empty($site_cfg['facebook_link'])): ?>
              <a href="<?= htmlspecialchars($site_cfg['facebook_link']) ?>" target="_blank" class="btn btn-light btn-icon text-muted border mr-2"><i class="fab fa-facebook-f"></i></a>
            <?php endif; ?>
            <?php if (!empty($site_cfg['instagram_link'])): ?>
              <a href="<?= htmlspecialchars($site_cfg['instagram_link']) ?>" target="_blank" class="btn btn-light btn-icon text-muted border mr-2"><i class="fab fa-instagram"></i></a>
            <?php endif; ?>
            <?php if (!empty($site_cfg['youtube_link'])): ?>
              <a href="<?= htmlspecialchars($site_cfg['youtube_link']) ?>" target="_blank" class="btn btn-light btn-icon text-muted border mr-2"><i class="fab fa-youtube"></i></a>
            <?php endif; ?>
            <?php if (!empty($site_cfg['tiktok_link'])): ?>
              <a href="<?= htmlspecialchars($site_cfg['tiktok_link']) ?>" target="_blank" class="btn btn-light btn-icon text-muted border"><i class="fab fa-tiktok"></i></a>
            <?php endif; ?>
          </div>
        </div>

        <!-- Cột 2: Chính sách (Lấy tự động từ CSDL bảng Policy) -->
        <div class="col-lg-2 col-md-3 col-6 mb-4">
          <h6 class="font-weight-bold text-dark mb-3">Chính Sách</h6>
          <ul class="list-unstyled small text-muted">
            <?php if (!empty($policies)): ?>
              <?php foreach ($policies as $pol): ?>
                <li class="mb-2">
                  <a href="policy-detail.php?slug=<?= htmlspecialchars($pol['slug']) ?>" class="text-muted text-decoration-none">
                    <?= htmlspecialchars($pol['title']) ?>
                  </a>
                </li>
              <?php endforeach; ?>
            <?php else: ?>
              <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Chính sách bảo hành</a></li>
              <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Chính sách hoàn tiền</a></li>
              <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Phương thức vận chuyển</a></li>
              <li><a href="#" class="text-muted text-decoration-none">Bảo mật thông tin</a></li>
            <?php endif; ?>
          </ul>
        </div>

        <!-- Cột 3: Hỗ trợ (Lấy tự động từ CSDL bảng Policy) -->
        <div class="col-lg-2 col-md-3 col-6 mb-4">
          <h6 class="font-weight-bold text-dark mb-3">Hỗ Trợ</h6>
          <ul class="list-unstyled small text-muted">
            <?php if (!empty($supports)): ?>
              <?php foreach ($supports as $sup): ?>
                <li class="mb-2">
                  <a href="policy-detail.php?slug=<?= htmlspecialchars($sup['slug']) ?>" class="text-muted text-decoration-none">
                    <?= htmlspecialchars($sup['title']) ?>
                  </a>
                </li>
              <?php endforeach; ?>
            <?php else: ?>
              <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Tra cứu bảo hành</a></li>
              <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Theo dõi bưu kiện</a></li>
              <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Hướng dẫn thanh toán</a></li>
              <li><a href="#" class="text-muted text-decoration-none">Liên hệ hợp tác</a></li>
            <?php endif; ?>
          </ul>
        </div>

        <!-- Cột 4: Giới thiệu Shop & Thông tin liên hệ (Lấy từ System_Settings) -->
        <div class="col-lg-4 col-md-12 mb-4">
          <h6 class="font-weight-bold text-dark mb-3">Về Chúng Tôi</h6>
          <p class="text-muted small mb-3">
            <?= htmlspecialchars($site_cfg['about_company'] ?? 'NOVASHOP là đơn vị phân phối phụ kiện và thiết bị công nghệ chính hãng. Mọi sản phẩm bán ra đều được kiểm định chất lượng nghiêm ngặt kèm tem bảo hành rõ ràng.') ?>
          </p>
          <ul class="list-unstyled small text-muted">
            <li class="mb-2">
              <i class="fas fa-map-marker-alt text-primary mr-2"></i>
              <span>Showroom: <?= htmlspecialchars($site_cfg['address'] ?? '123 Đường ABC, Quận 1, TP. HCM') ?></span>
            </li>
            <li class="mb-2">
              <i class="fas fa-phone-alt text-primary mr-2"></i>
              <span>Hotline: <strong class="text-dark"><?= htmlspecialchars($site_cfg['hotline'] ?? '1900 6868') ?></strong>
              <?php if (!empty($site_cfg['warranty_phone'])): ?>
                (BH: <strong><?= htmlspecialchars($site_cfg['warranty_phone']) ?></strong>)
              <?php endif; ?>
              </span>
            </li>
            <li class="mb-2">
              <i class="fas fa-clock text-primary mr-2"></i>
              <span>Giờ mở cửa: <?= htmlspecialchars($site_cfg['working_hours'] ?? '08:00 - 22:00 tất cả các ngày') ?></span>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Dòng Copyright -->
    <div class="border-top py-3 text-center text-muted small">
      &copy; <?= date('Y') ?> <?= htmlspecialchars($site_cfg['site_name'] ?? 'NovaShop') ?>. Bản quyền thuộc về <?= htmlspecialchars($site_cfg['site_name'] ?? 'NovaShop') ?>.
    </div>
  </footer>

  <!-- ==================== KHỐI CHATBOX NỔI GÓC DƯỚI PHẢI ==================== -->
  <div id="floating-chat-container" style="position: fixed; bottom: 25px; right: 25px; z-index: 9999;">
    <!-- Hộp thoại Chatbox -->
    <div id="chatbox-window" class="card border-0 shadow-lg rounded-2xl mb-3 d-none" style="width: 340px; height: 430px; overflow: hidden; animation: slideUp 0.3s ease;">
      <!-- Header Chatbox -->
      <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between p-3 border-0">
        <div class="d-flex align-items-center">
          <div class="position-relative mr-2">
            <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
              <i class="fas fa-headset"></i>
            </div>
            <span class="badge badge-success position-absolute rounded-circle p-1" style="bottom: 0; right: 0; border: 2px solid #fff;"> </span>
          </div>
          <div>
            <h6 class="mb-0 font-weight-bold"><?= htmlspecialchars($site_cfg['site_name'] ?? 'NovaShop') ?> Support</h6>
            <small class="text-light" style="font-size: 0.75rem;">Trực tuyến 24/7</small>
          </div>
        </div>
        <button type="button" class="close text-white" id="btn-close-chat" style="outline: none; opacity: 0.9;">
          <span>&times;</span>
        </button>
      </div>

      <!-- Khung hiển thị tin nhắn -->
      <div class="card-body p-3 bg-light d-flex flex-column" id="chatbox-messages" style="overflow-y: auto; gap: 10px;">
        <div class="d-flex align-items-start">
          <div class="bg-white p-2 px-3 rounded-2xl shadow-sm text-dark small" style="max-width: 80%;">
            Chào bạn! <?= htmlspecialchars($site_cfg['site_name'] ?? 'NovaShop') ?> có thể hỗ trợ thông tin gì cho bạn hôm nay ạ?
          </div>
        </div>
      </div>

      <!-- Ô nhập nội dung chat -->
      <div class="card-footer bg-white p-2 border-top">
        <form id="chat-form" class="d-flex align-items-center">
          <input type="text" id="chat-input" class="form-control rounded-pill-custom border-0 bg-light small mr-2 py-2" placeholder="Nhập tin nhắn..." autocomplete="off">
          <button type="submit" class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; min-width: 38px;">
            <i class="fas fa-paper-plane fa-sm"></i>
          </button>
        </form>
      </div>
    </div>

    <!-- Nút tròn lơ lửng -->
    <div class="text-right">
      <button id="btn-toggle-chat" class="btn btn-primary shadow-lg rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 58px; height: 58px; border: none; outline: none; transition: transform 0.2s ease;">
        <i class="fas fa-comment-dots fa-lg text-white" id="chat-icon"></i>
      </button>
    </div>
  </div>

  <style>
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    #btn-toggle-chat:hover {
      transform: scale(1.08);
    }
  </style>

  <!-- Scripts: jQuery, Popper.js, Bootstrap 4 JS -->
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Script điều khiển đóng mở và gửi tin nhắn trong Chatbox -->
  <script>
    const chatBtn = document.getElementById('btn-toggle-chat');
    const closeBtn = document.getElementById('btn-close-chat');
    const chatWindow = document.getElementById('chatbox-window');
    const chatIcon = document.getElementById('chat-icon');
    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-input');
    const chatMessages = document.getElementById('chatbox-messages');

    function toggleChat() {
      chatWindow.classList.toggle('d-none');
      if (chatWindow.classList.contains('d-none')) {
        chatIcon.className = 'fas fa-comment-dots fa-lg text-white';
      } else {
        chatIcon.className = 'fas fa-times fa-lg text-white';
        chatInput.focus();
      }
    }

    if (chatBtn) chatBtn.addEventListener('click', toggleChat);
    if (closeBtn) closeBtn.addEventListener('click', toggleChat);

    if (chatForm) {
      chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const msg = chatInput.value.trim();
        if (!msg) return;

        // Thêm tin nhắn của khách vào khung chat
        const userMsgDiv = document.createElement('div');
        userMsgDiv.className = 'd-flex align-items-end justify-content-end';
        userMsgDiv.innerHTML = `<div class="bg-primary text-white p-2 px-3 rounded-2xl shadow-sm small" style="max-width: 80%;">${msg}</div>`;
        chatMessages.appendChild(userMsgDiv);

        chatInput.value = '';
        chatMessages.scrollTop = chatMessages.scrollHeight;

        // Phản hồi mẫu tự động sau 800ms
        setTimeout(() => {
          const botMsgDiv = document.createElement('div');
          botMsgDiv.className = 'd-flex align-items-start';
          botMsgDiv.innerHTML = `<div class="bg-white p-2 px-3 rounded-2xl shadow-sm text-dark small" style="max-width: 80%;">Chuyên viên CSKH đã nhận được tin nhắn và sẽ phản hồi trong giây lát!</div>`;
          chatMessages.appendChild(botMsgDiv);
          chatMessages.scrollTop = chatMessages.scrollHeight;
        }, 800);
      });
    }
  </script>
</body>
</html>