<?php 
  include 'includes/header.php'; 
?>

  <main class="container my-5">
    <div class="d-flex align-items-center mb-4">
      <h3 class="font-weight-bold text-dark mb-0 mr-3">Giỏ Hàng Của Bạn</h3>
      <span class="badge badge-pill-soft bg-primary text-white">3 sản phẩm</span>
    </div>

    <div class="row">
      <!-- Cột danh sách sản phẩm trong giỏ -->
      <div class="col-lg-8 mb-4">
        <div class="card border-0 rounded-2xl shadow-soft p-4 bg-white">
          <div class="table-responsive">
            <table class="table align-middle border-0 mb-0">
              <thead class="text-muted small border-0">
                <tr>
                  <th scope="col" class="border-0 pl-0">SẢN PHẨM</th>
                  <th scope="col" class="border-0 text-center">ĐƠN GIÁ</th>
                  <th scope="col" class="border-0 text-center">SỐ LƯỢNG</th>
                  <th scope="col" class="border-0 text-right pr-0">THÀNH TIỀN</th>
                </tr>
              </thead>
              <tbody>
                <!-- Dòng sản phẩm 1 -->
                <tr class="border-bottom">
                  <td class="pl-0 py-3">
                    <div class="d-flex align-items-center">
                      <img src="https://images.unsplash.com/photo-1546435770-a3e426bf472b?auto=format&fit=crop&w=120&q=80" class="rounded-lg mr-3" style="width: 70px; height: 70px; object-fit: cover;" alt="SP">
                      <div>
                        <h6 class="font-weight-bold text-dark mb-1">Tai Nghe ANC Pro</h6>
                        <span class="text-muted small d-block">Màu sắc: Đen nhám</span>
                        <a href="#" class="text-danger small font-weight-bold"><i class="fas fa-trash-alt mr-1"></i> Xóa</a>
                      </div>
                    </div>
                  </td>
                  <td class="text-center align-middle font-weight-semibold">2.490.000 ₫</td>
                  <td class="text-center align-middle" style="width: 140px;">
                    <div class="input-group input-group-sm">
                      <div class="input-group-prepend">
                        <button class="btn btn-outline-secondary border-right-0 rounded-left" type="button">-</button>
                      </div>
                      <input type="text" class="form-control text-center font-weight-bold bg-white" value="1">
                      <div class="input-group-append">
                        <button class="btn btn-outline-secondary border-left-0 rounded-right" type="button">+</button>
                      </div>
                    </div>
                  </td>
                  <td class="text-right align-middle font-weight-bold text-dark pr-0">2.490.000 ₫</td>
                </tr>

                <!-- Dòng sản phẩm 2 -->
                <tr class="border-bottom">
                  <td class="pl-0 py-3">
                    <div class="d-flex align-items-center">
                      <img src="https://images.unsplash.com/photo-1508685096489-7aacd43bd3b1?auto=format&fit=crop&w=120&q=80" class="rounded-lg mr-3" style="width: 70px; height: 70px; object-fit: cover;" alt="SP">
                      <div>
                        <h6 class="font-weight-bold text-dark mb-1">Đồng Hồ Watch Ultra 2</h6>
                        <span class="text-muted small d-block">Bản: Dây Titan</span>
                        <a href="#" class="text-danger small font-weight-bold"><i class="fas fa-trash-alt mr-1"></i> Xóa</a>
                      </div>
                    </div>
                  </td>
                  <td class="text-center align-middle font-weight-semibold">4.150.000 ₫</td>
                  <td class="text-center align-middle">
                    <div class="input-group input-group-sm">
                      <div class="input-group-prepend">
                        <button class="btn btn-outline-secondary border-right-0 rounded-left" type="button">-</button>
                      </div>
                      <input type="text" class="form-control text-center font-weight-bold bg-white" value="1">
                      <div class="input-group-append">
                        <button class="btn btn-outline-secondary border-left-0 rounded-right" type="button">+</button>
                      </div>
                    </div>
                  </td>
                  <td class="text-right align-middle font-weight-bold text-dark pr-0">4.150.000 ₫</td>
                </tr>

                <!-- Dòng sản phẩm 3 -->
                <tr>
                  <td class="pl-0 py-3">
                    <div class="d-flex align-items-center">
                      <img src="https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?auto=format&fit=crop&w=120&q=80" class="rounded-lg mr-3" style="width: 70px; height: 70px; object-fit: cover;" alt="SP">
                      <div>
                        <h6 class="font-weight-bold text-dark mb-1">Chuột Master 3S</h6>
                        <span class="text-muted small d-block">Màu sắc: Xám</span>
                        <a href="#" class="text-danger small font-weight-bold"><i class="fas fa-trash-alt mr-1"></i> Xóa</a>
                      </div>
                    </div>
                  </td>
                  <td class="text-center align-middle font-weight-semibold">1.890.000 ₫</td>
                  <td class="text-center align-middle">
                    <div class="input-group input-group-sm">
                      <div class="input-group-prepend">
                        <button class="btn btn-outline-secondary border-right-0 rounded-left" type="button">-</button>
                      </div>
                      <input type="text" class="form-control text-center font-weight-bold bg-white" value="1">
                      <div class="input-group-append">
                        <button class="btn btn-outline-secondary border-left-0 rounded-right" type="button">+</button>
                      </div>
                    </div>
                  </td>
                  <td class="text-right align-middle font-weight-bold text-dark pr-0">1.890.000 ₫</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
            <a href="index.php" class="btn btn-light rounded-pill-custom small font-weight-bold">
              <i class="fas fa-arrow-left mr-2"></i> Tiếp tục mua sắm
            </a>
            <button class="btn btn-outline-secondary rounded-pill-custom small font-weight-bold">
              <i class="fas fa-sync-alt mr-2"></i> Cập nhật giỏ hàng
            </button>
          </div>
        </div>
      </div>

      <!-- Cột tóm tắt đơn hàng & thanh toán -->
      <div class="col-lg-4">
        <div class="card border-0 rounded-2xl shadow-soft p-4 bg-white mb-4">
          <h5 class="font-weight-bold text-dark mb-3">Tóm Tắt Đơn Hàng</h5>
          
          <div class="d-flex justify-content-between py-2 border-bottom text-muted small">
            <span>Tạm tính</span>
            <span class="text-dark font-weight-bold">8.530.000 ₫</span>
          </div>
          <div class="d-flex justify-content-between py-2 border-bottom text-muted small">
            <span>Phí vận chuyển</span>
            <span class="text-success font-weight-bold">Miễn phí</span>
          </div>
          <div class="d-flex justify-content-between py-2 border-bottom text-muted small">
            <span>Mã giảm giá</span>
            <span class="text-danger font-weight-bold">-100.000 ₫</span>
          </div>

          <div class="my-3">
            <label class="font-weight-semibold small text-muted">Mã voucher ưu đãi</label>
            <div class="input-group">
              <input type="text" class="form-control bg-light border-0 py-2" placeholder="Nhập mã giảm giá...">
              <div class="input-group-append">
                <button class="btn btn-dark px-3 font-weight-bold small rounded-right" type="button">Áp dụng</button>
              </div>
            </div>
          </div>

          <div class="d-flex justify-content-between py-3 border-top align-items-center mb-3">
            <span class="font-weight-bold text-dark">Tổng thanh toán</span>
            <span class="h4 font-weight-bold text-danger mb-0">8.430.000 ₫</span>
          </div>

          <button class="btn btn-primary btn-block rounded-pill-custom py-2 font-weight-bold shadow-sm mb-2">
            Tiến Hành Thanh Toán
          </button>
          <div class="text-center text-muted small">
            <i class="fas fa-shield-alt text-success mr-1"></i> Bảo mật thanh toán SSL 100%
          </div>
        </div>
      </div>
    </div>
  </main>

<?php 
  include 'includes/footer.php'; 
?>