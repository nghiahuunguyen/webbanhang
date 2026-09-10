<?php 
  include 'includes/header.php'; 
?>

  <main class="container my-5">
    <div class="row">
      <!-- Cột trái: Sidebar điều hướng tài khoản -->
      <div class="col-lg-3 col-md-4 mb-4">
        <div class="card border-0 rounded-2xl shadow-soft p-3 bg-white">
          <!-- Thông tin tóm tắt & Avatar -->
          <div class="text-center py-3 border-bottom">
            <div class="position-relative d-inline-block mb-3">
              <img src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80" 
                   class="rounded-circle border" style="width: 88px; height: 88px; object-fit: cover;" alt="Avatar">
              <button class="btn btn-primary rounded-circle position-absolute p-0" 
                      style="bottom: 0; right: 0; width: 28px; height: 28px;" title="Đổi ảnh đại diện">
                <i class="fas fa-camera text-white" style="font-size: 0.75rem;"></i>
              </button>
            </div>
            <h6 class="font-weight-bold text-dark mb-0">Nguyễn Văn A</h6>
            <span class="badge badge-pill-soft bg-warning text-dark mt-1 font-weight-bold">Thành viên Vàng</span>
          </div>

          <!-- Danh sách Menu Sidebar -->
          <div class="list-group list-group-flush pt-2">
            <a href="profile.php" class="list-group-item list-group-item-action border-0 rounded-pill-custom py-2 px-3 font-weight-bold text-primary active bg-light">
              <i class="fas fa-user-circle mr-2"></i> Hồ sơ cá nhân
            </a>
            <a href="orders.php" class="list-group-item list-group-item-action border-0 rounded-pill-custom py-2 px-3 text-secondary">
              <i class="fas fa-box-open mr-2"></i> Lịch sử đơn hàng
            </a>
            <!-- <a href="#" class="list-group-item list-group-item-action border-0 rounded-pill-custom py-2 px-3 text-secondary">
              <i class="fas fa-map-marker-alt mr-2"></i> Sổ địa chỉ nhận hàng
            </a>
            <a href="cart.php" class="list-group-item list-group-item-action border-0 rounded-pill-custom py-2 px-3 text-secondary">
              <i class="fas fa-shopping-bag mr-2"></i> Giỏ hàng của tôi
            </a>
            <a href="#" class="list-group-item list-group-item-action border-0 rounded-pill-custom py-2 px-3 text-secondary">
              <i class="fas fa-bell mr-2"></i> Thông báo ưu đãi
            </a> -->
            <a href="login.php" class="list-group-item list-group-item-action border-0 rounded-pill-custom py-2 px-3 text-danger mt-3">
              <i class="fas fa-sign-out-alt mr-2"></i> Đăng xuất
            </a>
          </div>
        </div>
      </div>

      <!-- Cột phải: Form cập nhật thông tin & đổi mật khẩu -->
      <div class="col-lg-9 col-md-8">
        <!-- Khối 1: Thông tin cá nhân -->
        <div class="card border-0 rounded-2xl shadow-soft p-4 p-md-5 bg-white mb-4">
          <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <div>
              <h5 class="font-weight-bold text-dark mb-1">Thông Tin Cá Nhân</h5>
              <p class="text-muted small mb-0">Quản lý và cập nhật thông tin tài khoản của bạn</p>
            </div>
            <span class="badge badge-pill-soft bg-success text-white">Đã xác minh</span>
          </div>

          <form action="#" method="POST">
            <div class="row">
              <div class="col-md-6 form-group mb-3">
                <label class="font-weight-semibold small text-muted">Họ và tên</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text bg-light border-0 rounded-left"><i class="far fa-user text-muted"></i></span>
                  </div>
                  <input type="text" class="form-control bg-light border-0 py-2" value="Nguyễn Văn A" required>
                </div>
              </div>

              <div class="col-md-6 form-group mb-3">
                <label class="font-weight-semibold small text-muted">Số điện thoại</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text bg-light border-0 rounded-left"><i class="fas fa-phone-alt text-muted"></i></span>
                  </div>
                  <input type="tel" class="form-control bg-light border-0 py-2" value="0901234567" required>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 form-group mb-3">
                <label class="font-weight-semibold small text-muted">Địa chỉ Email</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text bg-light border-0 rounded-left"><i class="far fa-envelope text-muted"></i></span>
                  </div>
                  <input type="email" class="form-control bg-light border-0 py-2" value="nguyenvana@gmail.com" required>
                </div>
              </div>

              <div class="col-md-6 form-group mb-3">
                <label class="font-weight-semibold small text-muted">Ngày sinh</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text bg-light border-0 rounded-left"><i class="far fa-calendar-alt text-muted"></i></span>
                  </div>
                  <input type="date" class="form-control bg-light border-0 py-2" value="1995-08-15">
                </div>
              </div>
            </div>

            <div class="form-group mb-3">
              <label class="font-weight-semibold small text-muted d-block">Giới tính</label>
              <div class="custom-control custom-radio custom-control-inline">
                <input type="radio" id="genderMale" name="gender" class="custom-control-input" checked>
                <label class="custom-control-label small text-muted" for="genderMale">Nam</label>
              </div>
              <div class="custom-control custom-radio custom-control-inline">
                <input type="radio" id="genderFemale" name="gender" class="custom-control-input">
                <label class="custom-control-label small text-muted" for="genderFemale">Nữ</label>
              </div>
              <div class="custom-control custom-radio custom-control-inline">
                <input type="radio" id="genderOther" name="gender" class="custom-control-input">
                <label class="custom-control-label small text-muted" for="genderOther">Khác</label>
              </div>
            </div>

            <div class="form-group mb-4">
              <label class="font-weight-semibold small text-muted">Địa chỉ mặc định</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text bg-light border-0 rounded-left"><i class="fas fa-map-marker-alt text-muted"></i></span>
                </div>
                <input type="text" class="form-control bg-light border-0 py-2" value="123 Lê Lợi, Phường Bến Nghé, Quận 1, TP. Hồ Chí Minh">
              </div>
            </div>

            <button type="submit" class="btn btn-primary rounded-pill-custom px-4 py-2 font-weight-bold shadow-sm">
              <i class="fas fa-save mr-2"></i> Lưu Thay Đổi
            </button>
          </form>
        </div>

        <!-- Khối 2: Đổi mật khẩu tài khoản -->
        <div class="card border-0 rounded-2xl shadow-soft p-4 p-md-5 bg-white">
          <div class="mb-4 pb-2 border-bottom">
            <h5 class="font-weight-bold text-dark mb-1">Bảo Mật & Mật Khẩu</h5>
            <p class="text-muted small mb-0">Cập nhật mật khẩu định kỳ để tăng cường bảo vệ tài khoản</p>
          </div>

          <form action="#" method="POST">
            <div class="row">
              <div class="col-md-4 form-group mb-3">
                <label class="font-weight-semibold small text-muted">Mật khẩu hiện tại</label>
                <input type="password" class="form-control bg-light border-0 py-2" placeholder="••••••••" required>
              </div>
              <div class="col-md-4 form-group mb-3">
                <label class="font-weight-semibold small text-muted">Mật khẩu mới</label>
                <input type="password" class="form-control bg-light border-0 py-2" placeholder="••••••••" required>
              </div>
              <div class="col-md-4 form-group mb-3">
                <label class="font-weight-semibold small text-muted">Xác nhận mật khẩu</label>
                <input type="password" class="form-control bg-light border-0 py-2" placeholder="••••••••" required>
              </div>
            </div>

            <button type="submit" class="btn btn-outline-dark rounded-pill-custom px-4 py-2 font-weight-bold small">
              Cập Nhật Mật Khẩu
            </button>
          </form>
        </div>
      </div>
    </div>
  </main>

<?php 
  include 'includes/footer.php'; 
?>