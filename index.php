<?php 
  // 1. Nhúng phần đầu từ thư mục includes
  include 'includes/header.php'; 
?>

  <!-- CSS tuỳ chỉnh cho giao diện Công nghệ -->
  <style>
    :root {
      --tech-blue: #0d6efd;
      --tech-dark: #0f172a;
    }
    .rounded-2xl { border-radius: 1.25rem !important; }
    .shadow-soft { box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04); }
    .shadow-hover { transition: all 0.3s ease; }
    .shadow-hover:hover { 
      transform: translateY(-6px); 
      box-shadow: 0 20px 30px -10px rgba(13, 110, 253, 0.2); 
    }
    .rounded-pill-custom { border-radius: 50rem !important; }
    
    /* Khung ảnh đồ công nghệ tỷ lệ chuẩn vuông 1:1, nền xám nhạt chống chói */
    .tech-img-wrap {
      position: relative;
      width: 100%;
      padding-top: 85%;
      overflow: hidden;
      background: #f8fafc;
      border-top-left-radius: 1.25rem;
      border-top-right-radius: 1.25rem;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .tech-img-wrap img {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      max-width: 80%;
      max-height: 80%;
      object-fit: contain;
      transition: transform 0.4s ease;
    }
    .card:hover .tech-img-wrap img {
      transform: translate(-50%, -50%) scale(1.08);
    }
    .hero-banner-img {
      height: 480px;
      object-fit: cover;
    }
    @media (max-width: 768px) {
      .hero-banner-img { height: 350px; }
    }
    .badge-spec {
      background-color: #f1f5f9;
      color: #475569;
      font-size: 0.72rem;
      padding: 0.25rem 0.5rem;
      border-radius: 0.375rem;
      margin-right: 0.25rem;
      margin-bottom: 0.25rem;
      display: inline-block;
    }
  </style>

  <!-- ==================== 2. PHẦN Ở GIỮA (BANNER & SẢN PHẨM ĐIỆN TỬ) ==================== -->
  <main class="container my-4 my-md-5">
    
    <!-- BANNER SLIDE 3 ẢNH CHẠY TỰ ĐỘNG -->
    <div id="techHeroCarousel" class="carousel slide mb-5 shadow-soft rounded-2xl overflow-hidden" 
         data-ride="carousel" data-bs-ride="carousel" 
         data-interval="4000" data-bs-interval="4000">

      <!-- Indicators -->
      <ol class="carousel-indicators">
        <li data-target="#techHeroCarousel" data-bs-target="#techHeroCarousel" data-slide-to="0" data-bs-slide-to="0" class="active"></li>
        <li data-target="#techHeroCarousel" data-bs-target="#techHeroCarousel" data-slide-to="1" data-bs-slide-to="1"></li>
        <li data-target="#techHeroCarousel" data-bs-target="#techHeroCarousel" data-slide-to="2" data-bs-slide-to="2"></li>
      </ol>

      <div class="carousel-inner">
        <!-- Slide 1: Laptop & Thiết bị làm việc AI -->
        <div class="carousel-item active position-relative">
          <img src="https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=1200&q=80" 
               class="d-block w-100 hero-banner-img" 
               alt="Laptop Thế Hệ Mới">
          <div class="carousel-caption d-flex flex-column justify-content-end text-left text-start p-4 p-md-5 position-absolute" 
               style="top:0; left:0; right:0; bottom:0; background: linear-gradient(to top, rgba(15, 23, 42, 0.9) 0%, rgba(15, 23, 42, 0.3) 60%, transparent 100%);">
            <span class="badge badge-primary bg-primary text-white font-weight-bold fw-bold w-auto align-self-start mb-2 px-3 py-1 rounded-pill">CHIP M4 / CORE ULTRA</span>
            <h2 class="font-weight-bold fw-bold text-white display-5">Kỷ Nguyên Laptop Trí Tuệ Nhân Tạo</h2>
            <p class="text-light mb-3 d-none d-md-block">Hiệu năng đồ họa đỉnh cao, thời lượng pin cả ngày dài cùng công nghệ xử lý AI tăng tốc công việc.</p>
            <div>
              <a href="#" class="btn btn-primary rounded-pill-custom px-4 py-2 font-weight-bold fw-bold mr-2 me-2">Mua Trả Góp 0%</a>
              <a href="#" class="btn btn-outline-light rounded-pill-custom px-4 py-2 font-weight-bold fw-bold">Xem thông số</a>
            </div>
          </div>
        </div>

        <!-- Slide 2: Gaming Gear & Màn hình 240Hz -->
        <div class="carousel-item position-relative">
          <img src="https://images.unsplash.com/photo-1542751371-adc38448a05e?auto=format&fit=crop&w=1200&q=80" 
               class="d-block w-100 hero-banner-img" 
               alt="Góc Máy Chơi Game">
          <div class="carousel-caption d-flex flex-column justify-content-end text-left text-start p-4 p-md-5 position-absolute" 
               style="top:0; left:0; right:0; bottom:0; background: linear-gradient(to top, rgba(15, 23, 42, 0.9) 0%, rgba(15, 23, 42, 0.3) 60%, transparent 100%);">
            <span class="badge badge-danger bg-danger text-white font-weight-bold fw-bold w-auto align-self-start mb-2 px-3 py-1 rounded-pill">MEGA GAMING SALE</span>
            <h2 class="font-weight-bold fw-bold text-white display-5">Chiến Mọi Tựa Game AAA</h2>
            <p class="text-light mb-3 d-none d-md-block">Giảm đến 40% cho bàn phím cơ quang học, chuột gaming siêu nhẹ và tai nghe vòm 7.1.</p>
            <div>
              <a href="#" class="btn btn-danger rounded-pill-custom px-4 py-2 font-weight-bold fw-bold">Săn Combo Giảm Sâu</a>
            </div>
          </div>
        </div>

        <!-- Slide 3: Âm thanh không dây chống ồn -->
        <div class="carousel-item position-relative">
          <img src="https://images.unsplash.com/photo-1546435770-a3e426bf472b?auto=format&fit=crop&w=1200&q=80" 
               class="d-block w-100 hero-banner-img" 
               alt="Tai Nghe Chống Ồn">
          <div class="carousel-caption d-flex flex-column justify-content-end text-left text-start p-4 p-md-5 position-absolute" 
               style="top:0; left:0; right:0; bottom:0; background: linear-gradient(to top, rgba(15, 23, 42, 0.9) 0%, rgba(15, 23, 42, 0.3) 60%, transparent 100%);">
            <span class="badge badge-warning bg-warning text-dark font-weight-bold fw-bold w-auto align-self-start mb-2 px-3 py-1 rounded-pill">ÂM THANH HI-RES</span>
            <h2 class="font-weight-bold fw-bold text-white display-5">Tách Biệt Khỏi Mọi Tiếng Ồn</h2>
            <p class="text-light mb-3 d-none d-md-block">Chống ồn chủ động Hybrid ANC thông minh, pin 40 tiếng liên tục kèm mic lọc gió đàm thoại rõ nét.</p>
            <div>
              <a href="#" class="btn btn-light rounded-pill-custom px-4 py-2 font-weight-bold fw-bold">Trải nghiệm ngay</a>
            </div>
          </div>
        </div>
      </div>

      <!-- Controls -->
      <a class="carousel-control-prev" href="#techHeroCarousel" role="button" data-slide="prev" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="sr-only visually-hidden">Trước</span>
      </a>
      <a class="carousel-control-next" href="#techHeroCarousel" role="button" data-slide="next" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="sr-only visually-hidden">Sau</span>
      </a>
    </div>

    <!-- KHỐI CAM KẾT CHUẨN ĐỒ ĐIỆN TỬ -->
    <div class="row text-center mb-5 g-3">
      <div class="col-6 col-md-3">
        <div class="p-3 bg-white rounded-2xl shadow-soft h-100 border">
          <i class="fas fa-check-double text-primary fa-2x mb-2"></i>
          <h6 class="font-weight-bold fw-bold mb-1">Chính Hãng 100%</h6>
          <span class="text-muted small">Đầy đủ VAT & CO/CQ</span>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="p-3 bg-white rounded-2xl shadow-soft h-100 border">
          <i class="fas fa-tools text-primary fa-2x mb-2"></i>
          <h6 class="font-weight-bold fw-bold mb-1">Bảo Hành 1 Đổi 1</h6>
          <span class="text-muted small">Lỗi phần cứng trong 30 ngày</span>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="p-3 bg-white rounded-2xl shadow-soft h-100 border">
          <i class="fas fa-bolt text-warning fa-2x mb-2"></i>
          <h6 class="font-weight-bold fw-bold mb-1">Giao Nhanh 2 Giờ</h6>
          <span class="text-muted small">Nội thành nhận hàng ngay</span>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="p-3 bg-white rounded-2xl shadow-soft h-100 border">
          <i class="fas fa-credit-card text-success fa-2x mb-2"></i>
          <h6 class="font-weight-bold fw-bold mb-1">Trả Góp 0% Lãi Suất</h6>
          <span class="text-muted small">Duyệt hồ sơ online 5 phút</span>
        </div>
      </div>
    </div>

    <!-- KHU VỰC 1: SẢN PHẨM ĐIỆN TỬ BÁN CHẠY (BEST SELLER) -->
    <section class="mb-5">
      <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
          <span class="badge badge-danger bg-danger text-white text-uppercase px-2 py-1 small">Flash Sale</span>
          <h3 class="font-weight-bold fw-bold mt-1 mb-0">Thiết Bị Bán Chạy Nhất</h3>
        </div>
        <a href="#" class="btn btn-outline-primary btn-sm rounded-pill-custom px-3 font-weight-bold fw-bold">Xem tất cả &rarr;</a>
      </div>

      <div class="row">
        <!-- Tech Item 1 -->
        <div class="col-6 col-md-4 mb-4">
          <div class="card border rounded-2xl shadow-soft shadow-hover h-100 bg-white">
            <div class="tech-img-wrap">
              <span class="badge badge-danger bg-danger text-white position-absolute" style="top: 12px; left: 12px; z-index: 2;">-28%</span>
              <img src="https://images.unsplash.com/photo-1546435770-a3e426bf472b?auto=format&fit=crop&w=500&q=80" alt="Tai Nghe ANC">
            </div>
            <div class="card-body p-3 p-md-4 d-flex flex-column">
              <span class="text-muted small mb-1">Âm thanh cao cấp</span>
              <h6 class="card-title font-weight-bold fw-bold text-dark mb-1">Tai Nghe Không Dây Sony WH-1000XM5</h6>
              
              <!-- Badges thông số kỹ thuật -->
              <div class="my-2">
                <span class="badge-spec">ANC Chống ồn</span>
                <span class="badge-spec">Pin 30h</span>
                <span class="badge-spec">Hi-Res Audio</span>
              </div>

              <div class="d-flex align-items-center mb-2">
                <span class="text-warning small mr-1"><i class="fas fa-star"></i> 4.9</span>
                <span class="text-muted small">(420 đánh giá)</span>
              </div>

              <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                <div>
                  <span class="text-danger font-weight-bold fw-bold h5 mb-0 d-block">6.490.000 ₫</span>
                  <span class="text-muted small text-decoration-line-through"><del>8.990.000 ₫</del></span>
                </div>
                <button class="btn btn-primary rounded-circle p-2 shadow-sm" title="Thêm vào giỏ">
                  <i class="fas fa-cart-plus text-white"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Tech Item 2 -->
        <div class="col-6 col-md-4 mb-4">
          <div class="card border rounded-2xl shadow-soft shadow-hover h-100 bg-white">
            <div class="tech-img-wrap">
              <span class="badge badge-warning bg-warning text-dark position-absolute" style="top: 12px; left: 12px; z-index: 2;">Bán chạy</span>
              <img src="https://images.unsplash.com/photo-1508685096489-7aacd43bd3b1?auto=format&fit=crop&w=500&q=80" alt="Đồng hồ thông minh">
            </div>
            <div class="card-body p-3 p-md-4 d-flex flex-column">
              <span class="text-muted small mb-1">Smartwatch thể thao</span>
              <h6 class="card-title font-weight-bold fw-bold text-dark mb-1">Đồng Hồ Nova Watch Ultra Titanium 2</h6>
              
              <div class="my-2">
                <span class="badge-spec">Khung Titan</span>
                <span class="badge-spec">Đo SpO2 & ECG</span>
                <span class="badge-spec">Chống nước 5ATM</span>
              </div>

              <div class="d-flex align-items-center mb-2">
                <span class="text-warning small mr-1"><i class="fas fa-star"></i> 4.8</span>
                <span class="text-muted small">(195 đánh giá)</span>
              </div>

              <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                <div>
                  <span class="text-danger font-weight-bold fw-bold h5 mb-0 d-block">4.290.000 ₫</span>
                  <span class="text-muted small text-decoration-line-through"><del>5.490.000 ₫</del></span>
                </div>
                <button class="btn btn-primary rounded-circle p-2 shadow-sm" title="Thêm vào giỏ">
                  <i class="fas fa-cart-plus text-white"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Tech Item 3 -->
        <div class="col-6 col-md-4 mb-4">
          <div class="card border rounded-2xl shadow-soft shadow-hover h-100 bg-white">
            <div class="tech-img-wrap">
              <span class="badge badge-info bg-info text-white position-absolute" style="top: 12px; left: 12px; z-index: 2;">Tặng túi</span>
              <img src="https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=500&q=80" alt="Bàn phím cơ">
            </div>
            <div class="card-body p-3 p-md-4 d-flex flex-column">
              <span class="text-muted small mb-1">Gaming Gear</span>
              <h6 class="card-title font-weight-bold fw-bold text-dark mb-1">Bàn Phím Cơ Custom Không Dây RGB Pro</h6>
              
              <div class="my-2">
                <span class="badge-spec">Hot-swap 5 pin</span>
                <span class="badge-spec">Gasket Mount</span>
                <span class="badge-spec">3 Chế độ kết nối</span>
              </div>

              <div class="d-flex align-items-center mb-2">
                <span class="text-warning small mr-1"><i class="fas fa-star"></i> 5.0</span>
                <span class="text-muted small">(88 đánh giá)</span>
              </div>

              <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                <div>
                  <span class="text-danger font-weight-bold fw-bold h5 mb-0 d-block">1.650.000 ₫</span>
                </div>
                <button class="btn btn-primary rounded-circle p-2 shadow-sm" title="Thêm vào giỏ">
                  <i class="fas fa-cart-plus text-white"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- KHU VỰC 2: PHỤ KIỆN & NGOẠI VI NỔI BẬT -->
    <section class="mb-5">
      <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
          <span class="text-primary font-weight-bold fw-bold text-uppercase small tracking-wider">Hệ sinh thái thông minh</span>
          <h3 class="font-weight-bold fw-bold mb-0">Phụ Kiện Tiện Ích Đời Mới</h3>
        </div>
        <a href="#" class="btn btn-outline-primary btn-sm rounded-pill-custom px-3 font-weight-bold fw-bold">Khám phá phụ kiện &rarr;</a>
      </div>

      <div class="row">
        <!-- Sub Item 1 -->
        <div class="col-6 col-md-4 mb-4">
          <div class="card border rounded-2xl shadow-soft shadow-hover h-100 bg-white">
            <div class="tech-img-wrap">
              <img src="https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?auto=format&fit=crop&w=500&q=80" alt="Chuột công thái học">
            </div>
            <div class="card-body p-3 p-md-4 d-flex flex-column">
              <span class="text-muted small mb-1">Văn phòng / Đồ họa</span>
              <h6 class="card-title font-weight-bold fw-bold text-dark mb-1">Chuột Công Thái Học Không Dây Master 3S</h6>
              <div class="my-2">
                <span class="badge-spec">Cuộn MagSpeed</span>
                <span class="badge-spec">8000 DPI</span>
                <span class="badge-spec">Click Yên Tĩnh</span>
              </div>
              <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                <div>
                  <span class="text-danger font-weight-bold fw-bold h5 mb-0 d-block">1.990.000 ₫</span>
                </div>
                <button class="btn btn-light btn-icon border shadow-sm rounded-circle p-2" title="Thêm vào giỏ">
                  <i class="fas fa-cart-plus text-primary"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Sub Item 2 -->
        <div class="col-6 col-md-4 mb-4">
          <div class="card border rounded-2xl shadow-soft shadow-hover h-100 bg-white">
            <div class="tech-img-wrap">
              <span class="badge badge-success bg-success text-white position-absolute" style="top: 12px; left: 12px; z-index: 2;">Công nghệ mới</span>
              <img src="https://images.unsplash.com/photo-1608156639585-34a0a56ee6c9?auto=format&fit=crop&w=500&q=80" alt="Loa Bluetooth Chống Nước">
            </div>
            <div class="card-body p-3 p-md-4 d-flex flex-column">
              <span class="text-muted small mb-1">Loa ngoài trời</span>
              <h6 class="card-title font-weight-bold fw-bold text-dark mb-1">Loa Bluetooth Mini Bass Cực Sâu IPX7</h6>
              <div class="my-2">
                <span class="badge-spec">Chống nước IPX7</span>
                <span class="badge-spec">Công suất 25W</span>
                <span class="badge-spec">Pin 16 tiếng</span>
              </div>
              <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                <div>
                  <span class="text-danger font-weight-bold fw-bold h5 mb-0 d-block">850.000 ₫</span>
                </div>
                <button class="btn btn-light btn-icon border shadow-sm rounded-circle p-2" title="Thêm vào giỏ">
                  <i class="fas fa-cart-plus text-primary"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Sub Item 3 -->
        <div class="col-6 col-md-4 mb-4">
          <div class="card border rounded-2xl shadow-soft shadow-hover h-100 bg-white">
            <div class="tech-img-wrap">
              <img src="https://images.unsplash.com/photo-1583863788434-e58a36330cf0?auto=format&fit=crop&w=500&q=80" alt="Củ sạc nhanh GaN">
            </div>
            <div class="card-body p-3 p-md-4 d-flex flex-column">
              <span class="text-muted small mb-1">Nguồn sạc cao cấp</span>
              <h6 class="card-title font-weight-bold fw-bold text-dark mb-1">Củ Sạc Nhanh GaN 100W 3 Cổng Type-C</h6>
              <div class="my-2">
                <span class="badge-spec">Công nghệ GaN 5</span>
                <span class="badge-spec">PD 3.0 / PPS</span>
                <span class="badge-spec">Sạc Laptop & Phone</span>
              </div>
              <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                <div>
                  <span class="text-danger font-weight-bold fw-bold h5 mb-0 d-block">690.000 ₫</span>
                </div>
                <button class="btn btn-light btn-icon border shadow-sm rounded-circle p-2" title="Thêm vào giỏ">
                  <i class="fas fa-cart-plus text-primary"></i>
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