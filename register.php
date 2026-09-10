<?php 
  include 'includes/header.php'; 
?>

  <main class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-7 col-lg-6">
        <div class="card border-0 rounded-2xl shadow-soft p-4 p-md-5 bg-white">
          <div class="text-center mb-4">
            <h3 class="font-weight-bold text-dark mb-1">Tạo Tài Khoản</h3>
            <p class="text-muted small">Đăng ký để nhận nhiều ưu đãi thành viên</p>
          </div>

          <form action="#" method="POST">
            <div class="form-group mb-3">
              <label class="font-weight-semibold small text-muted">Họ và tên</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text bg-light border-0 rounded-left"><i class="far fa-user text-muted"></i></span>
                </div>
                <input type="text" class="form-control bg-light border-0 py-2" placeholder="Nguyễn Văn A" required>
              </div>
            </div>

            <div class="form-group mb-3">
              <label class="font-weight-semibold small text-muted">Địa chỉ Email</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text bg-light border-0 rounded-left"><i class="far fa-envelope text-muted"></i></span>
                </div>
                <input type="email" class="form-control bg-light border-0 py-2" placeholder="name@example.com" required>
              </div>
            </div>

            <div class="form-group mb-3">
              <label class="font-weight-semibold small text-muted">Số điện thoại</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text bg-light border-0 rounded-left"><i class="fas fa-phone-alt text-muted"></i></span>
                </div>
                <input type="tel" class="form-control bg-light border-0 py-2" placeholder="0901234567" required>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 form-group mb-3">
                <label class="font-weight-semibold small text-muted">Mật khẩu</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text bg-light border-0 rounded-left"><i class="fas fa-lock text-muted"></i></span>
                  </div>
                  <input type="password" class="form-control bg-light border-0 py-2" placeholder="••••••••" required>
                </div>
              </div>
              <div class="col-md-6 form-group mb-3">
                <label class="font-weight-semibold small text-muted">Xác nhận mật khẩu</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text bg-light border-0 rounded-left"><i class="fas fa-check-circle text-muted"></i></span>
                  </div>
                  <input type="password" class="form-control bg-light border-0 py-2" placeholder="••••••••" required>
                </div>
              </div>
            </div>

            <div class="custom-control custom-checkbox mb-4">
              <input type="checkbox" class="custom-control-input" id="termsCheck" required>
              <label class="custom-control-label small text-muted" for="termsCheck">
                Tôi đồng ý với <a href="#" class="text-primary font-weight-bold">Điều khoản & Chính sách</a> của shop
              </label>
            </div>

            <button type="submit" class="btn btn-primary btn-block rounded-pill-custom py-2 font-weight-bold shadow-sm">
              Đăng Ký Tài Khoản
            </button>
          </form>

          <p class="text-center text-muted small mt-4 mb-0">
            Đã có tài khoản? <a href="login.php" class="text-primary font-weight-bold">Đăng nhập tại đây</a>
          </p>
        </div>
      </div>
    </div>
  </main>

<?php 
  include 'includes/footer.php'; 
?>