<?php 
  include 'includes/header.php'; 
?>

  <main class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="card border-0 rounded-2xl shadow-soft p-4 p-md-5 bg-white text-center">
          <div class="badge badge-light rounded-circle p-3 mb-3 text-success" style="font-size: 1.5rem;">
            <i class="fas fa-shield-alt"></i>
          </div>
          <h3 class="font-weight-bold text-dark mb-1">Xác Thực OTP</h3>
          <p class="text-muted small mb-4">Chúng tôi đã gửi mã xác thực gồm 6 chữ số đến email của bạn.</p>

          <form action="reset-password.php" method="GET">
            <!-- 6 ô nhập mã OTP -->
            <div class="d-flex justify-content-center mb-4">
              <input type="text" maxlength="1" class="form-control text-center font-weight-bold mx-1 border rounded-lg" style="width: 46px; height: 52px; font-size: 1.25rem;" required autofocus>
              <input type="text" maxlength="1" class="form-control text-center font-weight-bold mx-1 border rounded-lg" style="width: 46px; height: 52px; font-size: 1.25rem;" required>
              <input type="text" maxlength="1" class="form-control text-center font-weight-bold mx-1 border rounded-lg" style="width: 46px; height: 52px; font-size: 1.25rem;" required>
              <input type="text" maxlength="1" class="form-control text-center font-weight-bold mx-1 border rounded-lg" style="width: 46px; height: 52px; font-size: 1.25rem;" required>
              <input type="text" maxlength="1" class="form-control text-center font-weight-bold mx-1 border rounded-lg" style="width: 46px; height: 52px; font-size: 1.25rem;" required>
              <input type="text" maxlength="1" class="form-control text-center font-weight-bold mx-1 border rounded-lg" style="width: 46px; height: 52px; font-size: 1.25rem;" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block rounded-pill-custom py-2 font-weight-bold shadow-sm mb-3">
              Xác Nhận Mã
            </button>

            <div class="text-muted small">
              Không nhận được mã? <a href="#" class="text-primary font-weight-bold">Gửi lại (60s)</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </main>

<?php 
  include 'includes/footer.php'; 
?>