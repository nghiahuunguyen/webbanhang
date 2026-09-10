<?php include 'layout/head.php'; ?>
<?php include 'layout/sidebar.php'; ?>

<!-- MAIN CONTENT -->
<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-3">

  <?php include 'layout/topbar.php'; ?>

  <!-- STATS CARDS -->
  <div class="row mb-4">
    <div class="col-xl-3 col-sm-6 mb-3 mb-xl-0">
      <div class="stat-card d-flex align-items-center justify-content-between">
        <div>
          <p class="text-muted font-weight-medium small mb-1">Đơn đặt hàng</p>
          <h3 class="font-weight-bold mb-0 text-dark">1,248</h3>
          <small class="text-success font-weight-bold"><i class="fas fa-arrow-up mr-1"></i>+12.5%</small>
        </div>
        <div class="stat-icon-wrapper" style="background: #eff6ff; color: #3b82f6;">
          <i class="fas fa-shopping-cart"></i>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-sm-6 mb-3 mb-xl-0">
      <div class="stat-card d-flex align-items-center justify-content-between">
        <div>
          <p class="text-muted font-weight-medium small mb-1">Sản phẩm kho</p>
          <h3 class="font-weight-bold mb-0 text-dark">486</h3>
          <small class="text-muted">Tổng mẫu mã</small>
        </div>
        <div class="stat-icon-wrapper" style="background: #f0fdf4; color: #22c55e;">
          <i class="fas fa-boxes"></i>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-sm-6 mb-3 mb-xl-0">
      <div class="stat-card d-flex align-items-center justify-content-between">
        <div>
          <p class="text-muted font-weight-medium small mb-1">Ngành hàng</p>
          <h3 class="font-weight-bold mb-0 text-dark">24</h3>
          <small class="text-muted">Phân loại hoạt động</small>
        </div>
        <div class="stat-icon-wrapper" style="background: #faf5ff; color: #a855f7;">
          <i class="fas fa-tags"></i>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-sm-6 mb-3 mb-xl-0">
      <div class="stat-card d-flex align-items-center justify-content-between">
        <div>
          <p class="text-muted font-weight-medium small mb-1">Phản hồi khách</p>
          <h3 class="font-weight-bold mb-0 text-dark">98</h3>
          <small class="text-warning font-weight-bold"><i class="fas fa-circle mr-1 small"></i>5 chờ xử lý</small>
        </div>
        <div class="stat-icon-wrapper" style="background: #fffbeb; color: #f59e0b;">
          <i class="fas fa-comment-alt"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- TABLE CONTAINER -->
  <div class="custom-card">
    <div class="p-3 px-4 d-flex justify-content-between align-items-center border-bottom">
      <div>
        <h6 class="font-weight-bold mb-1 text-dark">Đơn Hàng Gần Đây</h6>
        <small class="text-muted">Dữ liệu thời gian thực từ bảng <code>orders</code></small>
      </div>
      <button class="btn btn-sm btn-outline-primary font-weight-bold px-3">Xuất báo cáo</button>
    </div>

    <div class="table-responsive">
      <table class="table mb-0">
        <thead>
          <tr>
            <th>MÃ ĐƠN</th>
            <th>KHÁCH HÀNG</th>
            <th>ĐIỆN THOẠI</th>
            <th>ĐỊA CHỈ NHẬN</th>
            <th>TỔNG THANH TOÁN</th>
            <th>TRẠNG THÁI</th>
            <th class="text-right">TÁC VỤ</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="font-weight-bold text-primary">#ORD-9021</td>
            <td>
              <div class="font-weight-bold text-dark">Nguyễn Văn An</div>
              <small class="text-muted">an.nv@gmail.com</small>
            </td>
            <td>0912 345 678</td>
            <td>Quận 1, TP. Hồ Chí Minh</td>
            <td class="font-weight-bold">1,850,000 đ</td>
            <td><span class="badge-soft-warning">Đang giao</span></td>
            <td class="text-right">
              <button class="btn btn-light btn-sm rounded-circle"><i class="fas fa-eye text-muted"></i></button>
              <button class="btn btn-light btn-sm rounded-circle text-danger"><i class="fas fa-trash-alt"></i></button>
            </td>
          </tr>
          <tr>
            <td class="font-weight-bold text-primary">#ORD-9022</td>
            <td>
              <div class="font-weight-bold text-dark">Lê Thị Hoa</div>
              <small class="text-muted">hoa.le@example.com</small>
            </td>
            <td>0988 123 456</td>
            <td>Thanh Xuân, Hà Nội</td>
            <td class="font-weight-bold">450,000 đ</td>
            <td><span class="badge-soft-success">Hoàn thành</span></td>
            <td class="text-right">
              <button class="btn btn-light btn-sm rounded-circle"><i class="fas fa-eye text-muted"></i></button>
              <button class="btn btn-light btn-sm rounded-circle text-danger"><i class="fas fa-trash-alt"></i></button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

</main>

<?php include 'layout/scripts.php'; ?>