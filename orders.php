<?php 
  include 'includes/header.php'; 
?>

  <main class="container my-5">
    <div class="row">
      <!-- Cột trái: Sidebar tài khoản đồng bộ -->
      <div class="col-lg-3 col-md-4 mb-4">
        <div class="card border-0 rounded-2xl shadow-soft p-3 bg-white">
          <div class="text-center py-3 border-bottom">
            <div class="position-relative d-inline-block mb-3">
              <img src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80" 
                   class="rounded-circle border" style="width: 88px; height: 88px; object-fit: cover;" alt="Avatar">
            </div>
            <h6 class="font-weight-bold text-dark mb-0">Nguyễn Văn A</h6>
            <span class="badge badge-pill-soft bg-warning text-dark mt-1 font-weight-bold">Thành viên Vàng</span>
          </div>

          <div class="list-group list-group-flush pt-2">
            <a href="profile.php" class="list-group-item list-group-item-action border-0 rounded-pill-custom py-2 px-3 text-secondary">
              <i class="fas fa-user-circle mr-2"></i> Hồ sơ cá nhân
            </a>
            <a href="orders.php" class="list-group-item list-group-item-action border-0 rounded-pill-custom py-2 px-3 font-weight-bold text-primary active bg-light">
              <i class="fas fa-box-open mr-2"></i> Lịch sử đơn hàng
            </a>
            
            </a>
            <a href="login.php" class="list-group-item list-group-item-action border-0 rounded-pill-custom py-2 px-3 text-danger mt-3">
              <i class="fas fa-sign-out-alt mr-2"></i> Đăng xuất
            </a>
          </div>
        </div>
      </div>

      <!-- Cột phải: Danh sách đơn hàng & tab trạng thái -->
      <div class="col-lg-9 col-md-8">
        <div class="card border-0 rounded-2xl shadow-soft p-4 bg-white mb-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <h5 class="font-weight-bold text-dark mb-1">Đơn Hàng Của Tôi</h5>
              <p class="text-muted small mb-0">Theo dõi tiến độ và kiểm tra lịch sử đặt hàng</p>
            </div>
          </div>

          <!-- Tabs trạng thái đơn hàng -->
          <ul class="nav nav-pills mb-4 border-bottom pb-3">
            <li class="nav-item mr-2">
              <a class="nav-link active rounded-pill-custom px-3 py-2 small font-weight-bold" href="#">Tất cả (3)</a>
            </li>
            <li class="nav-item mr-2">
              <a class="nav-link text-secondary rounded-pill-custom px-3 py-2 small font-weight-bold" href="#">Đang giao (1)</a>
            </li>
            <li class="nav-item mr-2">
              <a class="nav-link text-secondary rounded-pill-custom px-3 py-2 small font-weight-bold" href="#">Hoàn thành (1)</a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-secondary rounded-pill-custom px-3 py-2 small font-weight-bold" href="#">Đã hủy (1)</a>
            </li>
          </ul>

          <!-- Đơn hàng 1: Đang giao -->
          <div class="border rounded-2xl p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3 flex-wrap gap-2">
              <div>
                <span class="font-weight-bold text-dark">MÃ ĐƠN: #DH-98241</span>
                <span class="text-muted small ml-2"><i class="far fa-clock mr-1"></i> 10/09/2026 14:30</span>
              </div>
              <span class="badge badge-pill-soft bg-info text-white font-weight-bold"><i class="fas fa-shipping-fast mr-1"></i> Đang vận chuyển</span>
            </div>

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3 border-bottom">
              <div class="d-flex align-items-center">
                <img src="https://images.unsplash.com/photo-1546435770-a3e426bf472b?auto=format&fit=crop&w=100&q=80" class="rounded-lg mr-3" style="width: 68px; height: 68px; object-fit: cover;" alt="SP">
                <div>
                  <h6 class="font-weight-bold text-dark mb-1">Tai Nghe Không Dây ANC Pro</h6>
                  <span class="text-muted small">Phân loại: Đen nhám | Số lượng: x1</span>
                </div>
              </div>
              <div class="font-weight-bold text-dark">2.490.000 ₫</div>
            </div>

            <div class="d-flex justify-content-between align-items-center pt-3 flex-wrap gap-2">
              <span class="small text-muted">Dự kiến giao: <strong>12/09/2026</strong></span>
              <div class="text-right">
                <span class="small text-muted mr-2">Tổng tiền:</span>
                <span class="h5 font-weight-bold text-danger mb-0 mr-3">2.490.000 ₫</span>
                <button class="btn btn-outline-primary btn-sm rounded-pill-custom px-3 font-weight-bold">Theo dõi đơn</button>
              </div>
            </div>
          </div>

          <!-- Đơn hàng 2: Hoàn thành -->
          <div class="border rounded-2xl p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3 flex-wrap gap-2">
              <div>
                <span class="font-weight-bold text-dark">MÃ ĐƠN: #DH-87120</span>
                <span class="text-muted small ml-2"><i class="far fa-clock mr-1"></i> 02/09/2026 09:15</span>
              </div>
              <span class="badge badge-pill-soft bg-success text-white font-weight-bold"><i class="fas fa-check-circle mr-1"></i> Giao thành công</span>
            </div>

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3 border-bottom">
              <div class="d-flex align-items-center">
                <img src="https://images.unsplash.com/photo-1508685096489-7aacd43bd3b1?auto=format&fit=crop&w=100&q=80" class="rounded-lg mr-3" style="width: 68px; height: 68px; object-fit: cover;" alt="SP">
                <div>
                  <h6 class="font-weight-bold text-dark mb-1">Đồng Hồ Nova Watch Ultra 2</h6>
                  <span class="text-muted small">Phân loại: Dây Titan | Số lượng: x1</span>
                </div>
              </div>
              <div class="font-weight-bold text-dark">4.150.000 ₫</div>
            </div>

            <div class="d-flex justify-content-between align-items-center pt-3 flex-wrap gap-2">
              <span class="small text-muted">Đã thanh toán qua VNPay</span>
              <div class="text-right">
                <span class="small text-muted mr-2">Tổng tiền:</span>
                <span class="h5 font-weight-bold text-danger mb-0 mr-3">4.150.000 ₫</span>
                <button class="btn btn-outline-dark btn-sm rounded-pill-custom px-3 font-weight-bold mr-1">Đánh giá</button>
                <button class="btn btn-primary btn-sm rounded-pill-custom px-3 font-weight-bold">Mua lại</button>
              </div>
            </div>
          </div>

          <!-- Đơn hàng 3: Đã hủy -->
          <div class="border rounded-2xl p-4">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3 flex-wrap gap-2">
              <div>
                <span class="font-weight-bold text-dark">MÃ ĐƠN: #DH-76019</span>
                <span class="text-muted small ml-2"><i class="far fa-clock mr-1"></i> 20/08/2026 18:00</span>
              </div>
              <span class="badge badge-pill-soft bg-danger text-white font-weight-bold"><i class="fas fa-times-circle mr-1"></i> Đã hủy</span>
            </div>

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3 border-bottom">
              <div class="d-flex align-items-center">
                <img src="https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=100&q=80" class="rounded-lg mr-3" style="width: 68px; height: 68px; object-fit: cover;" alt="SP">
                <div>
                  <h6 class="font-weight-bold text-dark mb-1">Bàn Phím Cơ Silent Switch RGB</h6>
                  <span class="text-muted small">Phân loại: Red Switch | Số lượng: x1</span>
                </div>
              </div>
              <div class="font-weight-bold text-dark">1.250.000 ₫</div>
            </div>

            <div class="d-flex justify-content-between align-items-center pt-3 flex-wrap gap-2">
              <span class="small text-muted">Lý do hủy: Đổi ý không muốn mua nữa</span>
              <div class="text-right">
                <span class="small text-muted mr-2">Tổng tiền:</span>
                <span class="h5 font-weight-bold text-dark mb-0 mr-3">1.250.000 ₫</span>
                <button class="btn btn-primary btn-sm rounded-pill-custom px-3 font-weight-bold">Mua lại</button>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </main>

<?php 
  include 'includes/footer.php'; 
?>