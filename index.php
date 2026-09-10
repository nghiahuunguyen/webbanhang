<?php 
  // 1. Nhúng phần đầu từ thư mục includes
  include 'includes/header.php'; 
?>

  <!-- ==================== 2. PHẦN Ở GIỮA (BANNER & SẢN PHẨM) ==================== -->
  <main class="container my-5">
    
    <!-- Banner Slide Bo Cong -->
    <div id="heroCarousel" class="carousel slide mb-5 shadow-soft rounded-2xl overflow-hidden" data-ride="carousel">
      <div class="carousel-inner">
        <div class="carousel-item active position-relative">
          <img src="https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&w=1200&q=80" class="d-block w-100 hero-banner" alt="Banner 1">
          <div class="carousel-caption d-flex flex-column justify-content-end text-left p-4 p-md-5 banner-overlay position-absolute" style="top:0; left:0; right:0; bottom:0;">
            <span class="badge badge-warning text-dark font-weight-bold w-auto align-self-start mb-2 px-3 py-1 rounded-pill">MỚI RA MẮT</span>
            <h2 class="font-weight-bold text-white display-5">Công Nghệ Tương Lai Đột Phá</h2>
            <p class="text-light mb-3 d-none d-md-block">Giảm giá lên đến 35% cho các phụ kiện chơi game và làm việc chuyên nghiệp.</p>
            <div>
              <a href="#" class="btn btn-light rounded-pill-custom px-4 py-2 font-weight-bold">Khám phá ngay</a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- KHU VỰC 1: SẢN PHẨM BÁN CHẠY (1 HÀNG 3 CỘT: col-md-4) -->
    <section class="mb-5">
      <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
          <span class="text-danger font-weight-bold text-uppercase small tracking-wider">Thịnh hành</span>
          <h3 class="font-weight-bold mb-0">Sản Phẩm Bán Chạy</h3>
        </div>
        <a href="#" class="btn btn-outline-dark btn-sm rounded-pill-custom px-3">Tất cả sản phẩm &rarr;</a>
      </div>

      <div class="row">
        <!-- Item 1 -->
        <div class="col-md-4 mb-4">
          <div class="card border-0 rounded-2xl shadow-soft shadow-hover h-100 bg-white">
            <div class="product-img-wrap">
              <span class="badge badge-pill-soft bg-danger text-white position-absolute" style="top: 12px; left: 12px; z-index: 2;">-20%</span>
              <img src="https://images.unsplash.com/photo-1546435770-a3e426bf472b?auto=format&fit=crop&w=500&q=80" class="card-img-top product-img" alt="Tai nghe">
            </div>
            <div class="card-body p-4 d-flex flex-column">
              <span class="text-muted small mb-1">Âm thanh cao cấp</span>
              <h6 class="card-title font-weight-bold text-dark mb-2">Tai Nghe Không Dây ANC Pro</h6>
              <div class="d-flex align-items-center mb-3">
                <span class="text-warning small mr-2"><i class="fas fa-star"></i> 4.9</span>
                <span class="text-muted small">(128 đánh giá)</span>
              </div>
              <div class="d-flex justify-content-between align-items-center mt-auto">
                <div>
                  <span class="text-dark font-weight-bold h5 mb-0">2.490.000 ₫</span>
                </div>
                <button class="btn btn-light btn-icon border shadow-sm" title="Thêm vào giỏ">
                  <i class="fas fa-shopping-bag text-primary"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Item 2 -->
        <div class="col-md-4 mb-4">
          <div class="card border-0 rounded-2xl shadow-soft shadow-hover h-100 bg-white">
            <div class="product-img-wrap">
              <span class="badge badge-pill-soft bg-warning text-dark position-absolute" style="top: 12px; left: 12px; z-index: 2;">Best Seller</span>
              <img src="https://images.unsplash.com/photo-1508685096489-7aacd43bd3b1?auto=format&fit=crop&w=500&q=80" class="card-img-top product-img" alt="Đồng hồ">
            </div>
            <div class="card-body p-4 d-flex flex-column">
              <span class="text-muted small mb-1">Smartwatch</span>
              <h6 class="card-title font-weight-bold text-dark mb-2">Đồng Hồ Nova Watch Ultra 2</h6>
              <div class="d-flex align-items-center mb-3">
                <span class="text-warning small mr-2"><i class="fas fa-star"></i> 5.0</span>
                <span class="text-muted small">(94 đánh giá)</span>
              </div>
              <div class="d-flex justify-content-between align-items-center mt-auto">
                <div>
                  <span class="text-dark font-weight-bold h5 mb-0">4.150.000 ₫</span>
                </div>
                <button class="btn btn-light btn-icon border shadow-sm" title="Thêm vào giỏ">
                  <i class="fas fa-shopping-bag text-primary"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Item 3 -->
        <div class="col-md-4 mb-4">
          <div class="card border-0 rounded-2xl shadow-soft shadow-hover h-100 bg-white">
            <div class="product-img-wrap">
              <img src="https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=500&q=80" class="card-img-top product-img" alt="Bàn phím cơ">
            </div>
            <div class="card-body p-4 d-flex flex-column">
              <span class="text-muted small mb-1">Gaming Gear</span>
              <h6 class="card-title font-weight-bold text-dark mb-2">Bàn Phím Cơ Silent Switch RGB</h6>
              <div class="d-flex align-items-center mb-3">
                <span class="text-warning small mr-2"><i class="fas fa-star"></i> 4.8</span>
                <span class="text-muted small">(72 đánh giá)</span>
              </div>
              <div class="d-flex justify-content-between align-items-center mt-auto">
                <div>
                  <span class="text-dark font-weight-bold h5 mb-0">1.250.000 ₫</span>
                </div>
                <button class="btn btn-light btn-icon border shadow-sm" title="Thêm vào giỏ">
                  <i class="fas fa-shopping-bag text-primary"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- KHU VỰC 2: SẢN PHẨM NỔI BẬT (1 HÀNG 3 CỘT: col-md-4) -->
    <section class="mb-5">
      <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
          <span class="text-primary font-weight-bold text-uppercase small tracking-wider">Tuyển chọn</span>
          <h3 class="font-weight-bold mb-0">Sản Phẩm Nổi Bật</h3>
        </div>
        <a href="#" class="btn btn-outline-dark btn-sm rounded-pill-custom px-3">Tất cả sản phẩm &rarr;</a>
      </div>

      <div class="row">
        <!-- Item 1 -->
        <div class="col-md-4 mb-4">
          <div class="card border-0 rounded-2xl shadow-soft shadow-hover h-100 bg-white">
            <div class="product-img-wrap">
              <img src="https://images.unsplash.com/photo-1608156639585-34a0a56ee6c9?auto=format&fit=crop&w=500&q=80" class="card-img-top product-img" alt="Loa">
            </div>
            <div class="card-body p-4 d-flex flex-column">
              <span class="text-muted small mb-1">Loa di động</span>
              <h6 class="card-title font-weight-bold text-dark mb-2">Loa Bluetooth Nova Studio Mini</h6>
              <div class="d-flex align-items-center mb-3">
                <span class="text-warning small mr-2"><i class="fas fa-star"></i> 4.7</span>
                <span class="text-muted small">(41 đánh giá)</span>
              </div>
              <div class="d-flex justify-content-between align-items-center mt-auto">
                <div>
                  <span class="text-dark font-weight-bold h5 mb-0">980.000 ₫</span>
                </div>
                <button class="btn btn-light btn-icon border shadow-sm" title="Thêm vào giỏ">
                  <i class="fas fa-shopping-bag text-primary"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Item 2 -->
        <div class="col-md-4 mb-4">
          <div class="card border-0 rounded-2xl shadow-soft shadow-hover h-100 bg-white">
            <div class="product-img-wrap">
              <img src="https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?auto=format&fit=crop&w=500&q=80" class="card-img-top product-img" alt="Chuột">
            </div>
            <div class="card-body p-4 d-flex flex-column">
              <span class="text-muted small mb-1">Thiết bị văn phòng</span>
              <h6 class="card-title font-weight-bold text-dark mb-2">Chuột Công Thái Học Master 3S</h6>
              <div class="d-flex align-items-center mb-3">
                <span class="text-warning small mr-2"><i class="fas fa-star"></i> 4.9</span>
                <span class="text-muted small">(210 đánh giá)</span>
              </div>
              <div class="d-flex justify-content-between align-items-center mt-auto">
                <div>
                  <span class="text-dark font-weight-bold h5 mb-0">1.890.000 ₫</span>
                </div>
                <button class="btn btn-light btn-icon border shadow-sm" title="Thêm vào giỏ">
                  <i class="fas fa-shopping-bag text-primary"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Item 3 -->
        <div class="col-md-4 mb-4">
          <div class="card border-0 rounded-2xl shadow-soft shadow-hover h-100 bg-white">
            <div class="product-img-wrap">
              <img src="https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=500&q=80" class="card-img-top product-img" alt="Balo">
            </div>
            <div class="card-body p-4 d-flex flex-column">
              <span class="text-muted small mb-1">Phụ kiện mang theo</span>
              <h6 class="card-title font-weight-bold text-dark mb-2">Balo Chống Nước Urban Tech 20L</h6>
              <div class="d-flex align-items-center mb-3">
                <span class="text-warning small mr-2"><i class="fas fa-star"></i> 4.8</span>
                <span class="text-muted small">(55 đánh giá)</span>
              </div>
              <div class="d-flex justify-content-between align-items-center mt-auto">
                <div>
                  <span class="text-dark font-weight-bold h5 mb-0">790.000 ₫</span>
                </div>
                <button class="btn btn-light btn-icon border shadow-sm" title="Thêm vào giỏ">
                  <i class="fas fa-shopping-bag text-primary"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

<?php 
  // 3. Nhúng phần cuối từ thư mục includes
  include 'includes/footer.php'; 
?>