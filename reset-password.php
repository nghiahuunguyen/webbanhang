<?php 
  include 'includes/header.php'; 
?>

  <main class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="card border-0 rounded-2xl shadow-soft p-4 p-md-5 bg-white">
          <div class="text-center mb-4">
            <div class="badge badge-light rounded-circle p-3 mb-3 text-warning" style="font-size: 1.5rem;">
              <i class="fas fa-lock-open"></i>
            </div>
            <h3 class="font-weight-bold text-dark mb-1">Đặt Lại Mật Khẩu</h3>
            <p class="text-muted small">Mật khẩu mới phải có tối thiểu 8 ký tự.</p>
          </div>

          <form action="login.php" method="GET">
            <div class="form-group mb-3">
              <label class="font-weight-semibold small text-muted">Mật khẩu mới</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text bg-light border-0 rounded-left"><i class="fas fa-lock text-muted"></i></span>
                </div>
                <input type="password" class="form-control bg-light border-0 py-2" placeholder="••••••••" required>
              </div>
            </div>

            <div class="form-group mb-4">
              <label class="font-weight-semibold small text-muted">Xác nhận mật khẩu mới</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text bg-light border-0 rounded-left"><i class="fas fa-check-circle text-muted"></i></span>
                </div>
                <input type="password" class="form-control bg-light border-0 py-2" placeholder="••••••••" required>
              </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block rounded-pill-custom py-2 font-weight-bold shadow-sm">
              Lưu Mật Khẩu & Đăng Nhập
            </button>
          </form>
        </div>
      </div>
    </div>
  </main>

<?php 
  include 'includes/footer.php'; 
?>