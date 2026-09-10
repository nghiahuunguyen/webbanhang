<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng Ký Tài Khoản</title>
  <!-- Bootstrap 4 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
      
      <div class="card shadow border-0">
        <div class="card-header bg-primary text-white text-center py-3">
          <h4 class="mb-0 font-weight-bold">ĐĂNG KÝ THÀNH VIÊN</h4>
        </div>
        
        <div class="card-body p-4">
          <form action="registe.php" method="POST" class="needs-validation" novalidate>
            
            <!-- fullname (varchar 50) -->
            <div class="form-group">
              <label for="fullname">Họ và tên <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="fullname" name="fullname" maxlength="50" placeholder="Nhập họ và tên" required>
              <div class="invalid-feedback">Vui lòng nhập họ và tên (tối đa 50 ký tự).</div>
            </div>

            <!-- email (varchar 150) -->
            <div class="form-group">
              <label for="email">Địa chỉ Email <span class="text-danger">*</span></label>
              <input type="email" class="form-control" id="email" name="email" maxlength="150" placeholder="name@example.com" required>
              <div class="invalid-feedback">Vui lòng nhập email hợp lệ.</div>
            </div>

            <!-- phone_number (varchar 20) -->
            <div class="form-group">
              <label for="phone_number">Số điện thoại</label>
              <input type="tel" class="form-control" id="phone_number" name="phone_number" maxlength="20" placeholder="0912345678">
            </div>

            <!-- address (varchar 200) -->
            <div class="form-group">
              <label for="address">Địa chỉ</label>
              <input type="text" class="form-control" id="address" name="address" maxlength="200" placeholder="Số nhà, tên đường, tỉnh/thành phố">
            </div>

            <!-- password (varchar 32) & confirm password -->
            <div class="form-row">
              <div class="col-md-6 mb-3">
                <label for="password">Mật khẩu <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="password" name="password" minlength="6" maxlength="32" placeholder="Tối thiểu 6 ký tự" required>
                <div class="invalid-feedback">Mật khẩu từ 6 đến 32 ký tự.</div>
              </div>
              <div class="col-md-6 mb-3">
                <label for="confirm_password">Nhập lại mật khẩu <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="confirm_password" placeholder="Khớp với mật khẩu" required>
                <div class="invalid-feedback">Vui lòng nhập lại mật khẩu.</div>
              </div>
            </div>

            <!-- Nút gửi form -->
            <button type="submit" class="btn btn-primary btn-block py-2 font-weight-bold mt-3">Đăng Ký</button>

            <div class="text-center mt-3">
              <small class="text-muted">Đã có tài khoản? <a href="login.php">Đăng nhập</a></small>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Script validate Bootstrap 4 -->
<script>
  (function () {
    'use strict';
    window.addEventListener('load', function () {
      var forms = document.getElementsByClassName('needs-validation');
      Array.prototype.filter.call(forms, function (form) {
        form.addEventListener('submit', function (event) {
          var pass = document.getElementById('password');
          var confirmPass = document.getElementById('confirm_password');

          if (pass.value !== confirmPass.value) {
            confirmPass.setCustomValidity('Passwords do not match');
          } else {
            confirmPass.setCustomValidity('');
          }

          if (form.checkValidity() === false) {
            event.preventDefault();
            event.stopPropagation();
          }
          form.classList.add('was-validated');
        }, false);
      });
    }, false);
  })();
</script>

</body>
</html>