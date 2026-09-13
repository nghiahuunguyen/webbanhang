<?php 
  include 'includes/header.php'; 
?>

  <main class="container my-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
      <ol class="breadcrumb bg-transparent p-0 small">
        <li class="breadcrumb-item"><a href="index.php" class="text-muted">Trang chủ</a></li>
        <li class="breadcrumb-item active text-dark font-weight-bold" aria-current="page">Điều khoản & Chính sách</li>
      </ol>
    </nav>

    <!-- Header Tiêu đề -->
    <div class="card border-0 rounded-2xl shadow-soft p-4 p-md-5 bg-white mb-4">
      <div class="text-center max-w-700 mx-auto">
        <span class="badge badge-pill-soft bg-primary text-white px-3 py-1 font-weight-bold mb-2">QUY ĐỊNH BÁN HÀNG</span>
        <h2 class="font-weight-bold text-dark mb-2">Điều Khoản Sử Dụng & Chính Sách</h2>
        <p class="text-muted small mb-0">Cập nhật lần cuối: Tháng 09/2026. Xin vui lòng đọc kỹ trước khi mua hàng hoặc tạo tài khoản.</p>
      </div>
    </div>

    <div class="row">
      <!-- Cột trái: Mục lục điều hướng nhanh (Sticky Nav) -->
      <div class="col-lg-3 mb-4">
        <div class="card border-0 rounded-2xl shadow-soft p-3 bg-white sticky-top" style="top: 90px; z-index: 10;">
          <h6 class="font-weight-bold text-dark px-3 pt-2 mb-2 small text-uppercase">Mục Lục</h6>
          <div class="nav flex-column nav-pills" id="policyNav">
            <a class="nav-link text-secondary font-weight-semibold small rounded-pill-custom py-2 px-3 active bg-light text-primary" href="#section-terms">
              <i class="fas fa-file-contract mr-2"></i> 1. Điều khoản sử dụng
            </a>
            <a class="nav-link text-secondary font-weight-semibold small rounded-pill-custom py-2 px-3" href="#section-privacy">
              <i class="fas fa-user-shield mr-2"></i> 2. Chính sách bảo mật
            </a>
            <a class="nav-link text-secondary font-weight-semibold small rounded-pill-custom py-2 px-3" href="#section-return">
              <i class="fas fa-sync-alt mr-2"></i> 3. Đổi trả & Hoàn tiền
            </a>
            <a class="nav-link text-secondary font-weight-semibold small rounded-pill-custom py-2 px-3" href="#section-shipping">
              <i class="fas fa-shipping-fast mr-2"></i> 4. Vận chuyển & Giao nhận
            </a>
            <a class="nav-link text-secondary font-weight-semibold small rounded-pill-custom py-2 px-3" href="#section-warranty">
              <i class="fas fa-shield-alt mr-2"></i> 5. Chính sách bảo hành
            </a>
          </div>
        </div>
      </div>

      <!-- Cột phải: Nội dung chi tiết các điều khoản -->
      <div class="col-lg-9">
        <div class="card border-0 rounded-2xl shadow-soft p-4 p-md-5 bg-white">
          
          <!-- Phần 1: Điều khoản sử dụng -->
          <section id="section-terms" class="mb-5 pb-4 border-bottom">
            <h4 class="font-weight-bold text-dark mb-3">1. Điều Khoản Sử Dụng</h4>
            <p class="text-muted small" style="line-height: 1.8;">
              Chào mừng bạn đến với hệ sinh thái mua sắm trực tuyến <strong>NovaShop</strong>. Khi đăng ký tài khoản hoặc sử dụng dịch vụ trên website, bạn đồng ý tuân thủ và chịu ràng buộc bởi các điều khoản sau:
            </p>
            <ul class="text-muted small pl-3" style="line-height: 1.8;">
              <li class="mb-2"><strong>Quyền tài khoản:</strong> Bạn có trách nhiệm bảo mật thông tin tài khoản, mật khẩu và mọi hoạt động diễn ra dưới tài khoản của mình.</li>
              <li class="mb-2"><strong>Tính chính xác:</strong> Cung cấp thông tin cá nhân (họ tên, email, số điện thoại, địa chỉ) trung thực và chính xác để việc xử lý đơn hàng diễn ra thuận lợi.</li>
              <li><strong>Nghiêm cấm:</strong> Sử dụng website vào mục đích gian lận, phá hoại hạ tầng mạng hoặc can thiệp trái phép vào hệ thống dữ liệu người dùng.</li>
            </ul>
          </section>

          <!-- Phần 2: Chính sách bảo mật -->
          <section id="section-privacy" class="mb-5 pb-4 border-bottom">
            <h4 class="font-weight-bold text-dark mb-3">2. Chính Sách Bảo Mật Thông Tin</h4>
            <p class="text-muted small" style="line-height: 1.8;">
              NovaShop cam kết bảo mật tuyệt đối dữ liệu khách hàng theo quy định pháp luật:
            </p>
            <div class="bg-light rounded-2xl p-3 mb-3 border">
              <h6 class="font-weight-bold text-dark small mb-1"><i class="fas fa-lock text-success mr-2"></i>Mục đích thu thập:</h6>
              <p class="text-muted small mb-0">Xác thực tài khoản, xử lý đơn đặt hàng, liên hệ giao nhận và gửi các ưu đãi đặc quyền (nếu được khách hàng đồng ý).</p>
            </div>
            <ul class="text-muted small pl-3" style="line-height: 1.8;">
              <li class="mb-2">Dữ liệu mật khẩu của người dùng được mã hóa bảo vệ hoàn toàn trong hệ thống.</li>
              <li>Chúng tôi <strong>cam kết không bán, chia sẻ</strong> hoặc thương mại hóa thông tin khách hàng cho bên thứ ba ngoại trừ các đối tác vận chuyển phục vụ giao nhận bưu kiện.</li>
            </ul>
          </section>

          <!-- Phần 3: Đổi trả & hoàn tiền -->
          <section id="section-return" class="mb-5 pb-4 border-bottom">
            <h4 class="font-weight-bold text-dark mb-3">3. Chính Sách Đổi Trả & Hoàn Tiền</h4>
            <div class="row mb-3">
              <div class="col-md-6 mb-3 mb-md-0">
                <div class="border rounded-2xl p-3 h-100">
                  <span class="badge badge-success mb-2">Đổi mới 30 ngày</span>
                  <h6 class="font-weight-bold text-dark small mb-1">Lỗi do nhà sản xuất</h6>
                  <p class="text-muted small mb-0">Sản phẩm phát sinh lỗi phần cứng trong 30 ngày đầu tiên được hỗ trợ đổi ngay sản phẩm mới cùng loại.</p>
                </div>
              </div>
              <div class="col-md-6">
                <div class="border rounded-2xl p-3 h-100">
                  <span class="badge badge-warning text-dark mb-2 font-weight-bold">Đổi trả 7 ngày</span>
                  <h6 class="font-weight-bold text-dark small mb-1">Đổi ý / Không vừa ý</h6>
                  <p class="text-muted small mb-0">Hỗ trợ đổi mẫu khác trong vòng 7 ngày kể từ khi nhận hàng. Yêu cầu sản phẩm còn nguyên hộp và tem niêm phong.</p>
                </div>
              </div>
            </div>
          </section>

          <!-- Phần 4: Vận chuyển & Giao nhận -->
          <section id="section-shipping" class="mb-5 pb-4 border-bottom">
            <h4 class="font-weight-bold text-dark mb-3">4. Vận Chuyển & Giao Nhận</h4>
            <ul class="text-muted small pl-3" style="line-height: 1.8;">
              <li class="mb-2"><strong>Miễn phí vận chuyển:</strong> Áp dụng tự động cho tất cả đơn hàng có tổng thanh toán từ <strong>500.000 ₫</strong> trở lên.</li>
              <li class="mb-2"><strong>Thời gian giao hàng:</strong> Khu vực nội thành (1 - 2 ngày làm việc), các tỉnh thành khác (2 - 4 ngày làm việc).</li>
              <li><strong>Đồng kiểm hàng:</strong> Khách hàng được quyền kiểm tra ngoại quan hộp bưu kiện trước khi thanh toán cho nhân viên giao hàng.</li>
            </ul>
          </section>

          <!-- Phần 5: Chính sách bảo hành -->
          <section id="section-warranty">
            <h4 class="font-weight-bold text-dark mb-3">5. Chính Sách Bảo Hành Thiết Bị</h4>
            <p class="text-muted small" style="line-height: 1.8;">
              Toàn bộ thiết bị phần cứng chính hãng được bảo hành điện tử theo số điện thoại mua hàng:
            </p>
            <ul class="text-muted small pl-3" style="line-height: 1.8;">
              <li class="mb-2">Thời hạn bảo hành tiêu chuẩn từ <strong>12 đến 24 tháng</strong> tùy chủng loại sản phẩm.</li>
              <li class="mb-2">Từ chối bảo hành đối với các trường hợp: Rơi vỡ, vô nước, cấn móp, côn trùng xâm nhập hoặc tự ý sửa chữa tại các cơ sở ngoài.</li>
              <li>Mọi thắc mắc và yêu cầu tra cứu bảo hành, quý khách vui lòng liên hệ hotline <strong>1900 6868</strong> (8:00 - 21:30).</li>
            </ul>
          </section>

        </div>
      </div>
    </div>
  </main>

<?php 
  include 'includes/footer.php'; 
?>