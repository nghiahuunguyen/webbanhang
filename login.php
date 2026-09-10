<?php 
  include 'includes/header.php'; 
?>

  <main class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="card border-0 rounded-2xl shadow-soft p-4 p-md-5 bg-white">
          <div class="text-center mb-4">
            <h3 class="font-weight-bold text-dark mb-1">Đăng Nhập</h3>
            <p class="text-muted small">Chào mừng bạn quay lại với NovaShop</p>
          </div>

          <form action="#" method="POST">
            <div class="form-group mb-3">
              <label class="font-weight-semibold small text-muted">Email hoặc số điện thoại</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text bg-light border-0 rounded-left"><i class="far fa-envelope text-muted"></i></span>
                </div>
                <input type="text" class="form-control bg-light border-0 py-2" placeholder="name@example.com" required>
              </div>
            </div>

            <div class="form-group mb-3">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="font-weight-semibold small text-muted mb-0">Mật khẩu</label>
                <a href="forgot-password.php" class="small text-primary font-weight-bold">Quên mật khẩu?</a>
              </div>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text bg-light border-0 rounded-left"><i class="fas fa-lock text-muted"></i></span>
                </div>
                <input type="password" class="form-control bg-light border-0 py-2" placeholder="••••••••" required>
              </div>
            </div>

            <div class="custom-control custom-checkbox mb-4">
              <input type="checkbox" class="custom-control-input" id="rememberMe">
              <label class="custom-control-label small text-muted" for="rememberMe">Ghi nhớ đăng nhập</label>
            </div>

            <button type="submit" class="btn btn-primary btn-block rounded-pill-custom py-2 font-weight-bold shadow-sm">
              Đăng Nhập
            </button>
          </form>

          

          <p class="text-center text-muted small mt-4 mb-0">
            Chưa có tài khoản? <a href="register.php" class="text-primary font-weight-bold">Đăng ký ngay</a>
          </p>
        </div>
      </div>
    </div>
  </main>

<?php 
  include 'includes/footer.php'; 
?>