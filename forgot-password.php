<?php 
  include 'includes/header.php'; 
?>

  <main class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="card border-0 rounded-2xl shadow-soft p-4 p-md-5 bg-white">
          <div class="text-center mb-4">
            <div class="badge badge-light rounded-circle p-3 mb-3 text-primary" style="font-size: 1.5rem;">
              <i class="fas fa-key"></i>
            </div>
            <h3 class="font-weight-bold text-dark mb-1">Quên Mật Khẩu?</h3>
            <p class="text-muted small">Nhập email của bạn để nhận mã xác thực OTP gồm 6 chữ số.</p>
          </div>

          <form action="verify-otp.php" method="GET">
            <div class="form-group mb-4">
              <label class="font-weight-semibold small text-muted">Địa chỉ Email đã đăng ký</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text bg-light border-0 rounded-left"><i class="far fa-envelope text-muted"></i></span>
                </div>
                <input type="email" class="form-control bg-light border-0 py-2" placeholder="name@example.com" required>
              </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block rounded-pill-custom py-2 font-weight-bold shadow-sm">
              Gửi Mã Xác Thực &rarr;
            </button>
          </form>

          <p class="text-center text-muted small mt-4 mb-0">
            Nhớ mật khẩu? <a href="login.php" class="text-primary font-weight-bold">Quay lại đăng nhập</a>
          </p>
        </div>
      </div>
    </div>
  </main>

<?php 
  include 'includes/footer.php'; 
?>