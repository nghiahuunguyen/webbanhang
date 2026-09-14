<?php 
  // 1. Nhúng phần đầu từ thư mục includes
  include 'includes/header.php'; 

  // Đọc cấu hình tốc độ tự động slide từ CSDL
  $cfg_slide = executeResult("SELECT key_name, key_value FROM `System_Settings` WHERE key_name IN ('banner_autoplay', 'banner_interval')");
  $c_data = [];
  if (!empty($cfg_slide)) {
      foreach ($cfg_slide as $c) { $c_data[$c['key_name']] = $c['key_value']; }
  }
  $autoplay_mode = ($c_data['banner_autoplay'] ?? '1') === '1' ? 'carousel' : 'false';
  $interval_ms   = intval($c_data['banner_interval'] ?? 4000);

  // Lấy danh sách banner đang chiếu và chưa bị xóa
  $banner_list = executeResult("SELECT * FROM `Banner` WHERE is_active = 1 AND deleted = 0 ORDER BY sort_order ASC, id DESC");
  if (empty($banner_list)) {
      $banner_list = [
          ['image_url' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=1200&q=80'],
          ['image_url' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?auto=format&fit=crop&w=1200&q=80']
      ];
  }
?>

  <!-- CSS tuỳ chỉnh giao diện Công nghệ -->
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

    /* KÍCH THƯỚC CHUẨN BANNER SLIDE TRANG CÔNG NGHỆ */
    #techHeroCarousel {
      border-radius: 1.25rem;
      overflow: hidden;
    }
    .hero-banner-img {
      width: 100%;
      height: 340px;
      object-fit: cover;
      object-position: center;
      display: block;
    }
    @media (max-width: 992px) {
      .hero-banner-img { height: 250px; }
    }
    @media (max-width: 576px) {
      .hero-banner-img { height: 160px; }
    }

    /* Tinh chỉnh nút điều hướng slide */
    #techHeroCarousel .carousel-control-prev,
    #techHeroCarousel .carousel-control-next {
      width: 40px;
      height: 40px;
      background: rgba(0, 0, 0, 0.3);
      border-radius: 50%;
      top: 50%;
      transform: translateY(-50%);
      margin: 0 15px;
      transition: all 0.2s ease;
    }
    #techHeroCarousel .carousel-control-prev:hover,
    #techHeroCarousel .carousel-control-next:hover {
      background: rgba(0, 0, 0, 0.6);
    }
    #techHeroCarousel .carousel-control-prev-icon,
    #techHeroCarousel .carousel-control-next-icon {
      width: 16px;
      height: 16px;
    }

    /* Vạch chỉ số slide */
    #techHeroCarousel .carousel-indicators li {
      height: 4px;
      border-radius: 2px;
      margin: 0 3px;
      background-color: rgba(255, 255, 255, 0.5);
      border: none;
    }
    #techHeroCarousel .carousel-indicators .active {
      background-color: #fff;
      width: 24px;
    }
    
    /* Khung ảnh sản phẩm */
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

  <main class="container my-4 my-md-5">
    
    <!-- BANNER SLIDE CHUẨN TỰ ĐỘNG -->
    <div id="techHeroCarousel" class="carousel slide mb-5 shadow-soft rounded-2xl overflow-hidden" 
         data-ride="<?= $autoplay_mode ?>" 
         data-interval="<?= $autoplay_mode === 'false' ? 'false' : $interval_ms ?>" 
         data-pause="hover">

      <!-- Indicators -->
      <?php if (count($banner_list) > 1): ?>
        <ol class="carousel-indicators">
          <?php foreach ($banner_list as $index => $b): ?>
            <li data-target="#techHeroCarousel" data-slide-to="<?= $index ?>" class="<?= ($index === 0) ? 'active' : '' ?>"></li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>

      <!-- Danh sách ảnh Banner -->
      <div class="carousel-inner">
        <?php foreach ($banner_list as $index => $b): 
            $img = $b['image_url'];
            $img_src = (strpos($img, 'http') === 0) ? $img : $img;
        ?>
          <div class="carousel-item <?= ($index === 0) ? 'active' : '' ?>">
            <img src="<?= htmlspecialchars($img_src) ?>" class="d-block w-100 hero-banner-img" alt="Banner <?= $index + 1 ?>">
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Controls -->
      <?php if (count($banner_list) > 1): ?>
        <a class="carousel-control-prev" href="#techHeroCarousel" role="button" data-slide="prev">
          <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          <span class="sr-only">Trước</span>
        </a>
        <a class="carousel-control-next" href="#techHeroCarousel" role="button" data-slide="next">
          <span class="carousel-control-next-icon" aria-hidden="true"></span>
          <span class="sr-only">Sau</span>
        </a>
      <?php endif; ?>
    </div>

    <!-- KHỐI CAM KẾT CHUẨN ĐỒ ĐIỆN TỬ -->
    <div class="row text-center mb-5 g-3">
      <div class="col-6 col-md-3 mb-3 mb-md-0">
        <div class="p-3 bg-white rounded-2xl shadow-soft h-100 border">
          <i class="fas fa-check-double text-primary fa-2x mb-2"></i>
          <h6 class="font-weight-bold mb-1">Chính Hãng 100%</h6>
          <span class="text-muted small">Đầy đủ VAT & CO/CQ</span>
        </div>
      </div>
      <div class="col-6 col-md-3 mb-3 mb-md-0">
        <div class="p-3 bg-white rounded-2xl shadow-soft h-100 border">
          <i class="fas fa-tools text-primary fa-2x mb-2"></i>
          <h6 class="font-weight-bold mb-1">Bảo Hành 1 Đổi 1</h6>
          <span class="text-muted small">Lỗi phần cứng trong 30 ngày</span>
        </div>
      </div>
      <div class="col-6 col-md-3 mb-3 mb-md-0">
        <div class="p-3 bg-white rounded-2xl shadow-soft h-100 border">
          <i class="fas fa-bolt text-warning fa-2x mb-2"></i>
          <h6 class="font-weight-bold mb-1">Giao Nhanh 2 Giờ</h6>
          <span class="text-muted small">Nội thành nhận hàng ngay</span>
        </div>
      </div>
      <div class="col-6 col-md-3 mb-3 mb-md-0">
        <div class="p-3 bg-white rounded-2xl shadow-soft h-100 border">
          <i class="fas fa-credit-card text-success fa-2x mb-2"></i>
          <h6 class="font-weight-bold mb-1">Trả Góp 0% Lãi Suất</h6>
          <span class="text-muted small">Duyệt hồ sơ online 5 phút</span>
        </div>
      </div>
    </div>

    <!-- KHU VỰC 1: SẢN PHẨM ĐIỆN TỬ BÁN CHẠY -->
    <section class="mb-5">
      <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
          <span class="badge badge-danger px-2 py-1 small">Flash Sale</span>
          <h3 class="font-weight-bold mt-1 mb-0">Thiết Bị Bán Chạy Nhất</h3>
        </div>
        <a href="products.php" class="btn btn-outline-primary btn-sm rounded-pill-custom px-3 font-weight-bold">Xem tất cả &rarr;</a>
      </div>

      <div class="row">
        <!-- Item 1 -->
        <div class="col-6 col-md-4 mb-4">
          <div class="card border rounded-2xl shadow-soft shadow-hover h-100 bg-white">
            <div class="tech-img-wrap">
              <span class="badge badge-danger text-white position-absolute" style="top: 12px; left: 12px; z-index: 2;">-28%</span>
              <img src="https://images.unsplash.com/photo-1546435770-a3e426bf472b?auto=format&fit=crop&w=500&q=80" alt="Tai Nghe ANC">
            </div>
            <div class="card-body p-3 p-md-4 d-flex flex-column">
              <span class="text-muted small mb-1">Âm thanh cao cấp</span>
              <h6 class="card-title font-weight-bold text-dark mb-1">Tai Nghe Không Dây Sony WH-1000XM5</h6>
              <div class="my-2">
                <span class="badge-spec">ANC Chống ồn</span>
                <span class="badge-spec">Pin 30h</span>
                <span class="badge-spec">Hi-Res Audio</span>
              </div>
              <div class="d-flex align-items-center mb-2">
                <span class="text-warning small mr-1"><i class="fas fa-star"></i> 4.9</span>
                <span class="text-muted small">(420)</span>
              </div>
              <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                <div>
                  <span class="text-danger font-weight-bold h5 mb-0 d-block">6.490.000 ₫</span>
                  <span class="text-muted small"><del>8.990.000 ₫</del></span>
                </div>
                <button class="btn btn-primary rounded-circle p-2 shadow-sm" title="Thêm vào giỏ">
                  <i class="fas fa-cart-plus text-white"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Item 2 -->
        <div class="col-6 col-md-4 mb-4">
          <div class="card border rounded-2xl shadow-soft shadow-hover h-100 bg-white">
            <div class="tech-img-wrap">
              <span class="badge badge-warning text-dark position-absolute" style="top: 12px; left: 12px; z-index: 2;">Bán chạy</span>
              <img src="https://images.unsplash.com/photo-1508685096489-7aacd43bd3b1?auto=format&fit=crop&w=500&q=80" alt="Đồng hồ thông minh">
            </div>
            <div class="card-body p-3 p-md-4 d-flex flex-column">
              <span class="text-muted small mb-1">Smartwatch thể thao</span>
              <h6 class="card-title font-weight-bold text-dark mb-1">Đồng Hồ Nova Watch Ultra Titanium 2</h6>
              <div class="my-2">
                <span class="badge-spec">Khung Titan</span>
                <span class="badge-spec">SpO2 & ECG</span>
                <span class="badge-spec">5ATM</span>
              </div>
              <div class="d-flex align-items-center mb-2">
                <span class="text-warning small mr-1"><i class="fas fa-star"></i> 4.8</span>
                <span class="text-muted small">(195)</span>
              </div>
              <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                <div>
                  <span class="text-danger font-weight-bold h5 mb-0 d-block">4.290.000 ₫</span>
                  <span class="text-muted small"><del>5.490.000 ₫</del></span>
                </div>
                <button class="btn btn-primary rounded-circle p-2 shadow-sm" title="Thêm vào giỏ">
                  <i class="fas fa-cart-plus text-white"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Item 3 -->
        <div class="col-6 col-md-4 mb-4">
          <div class="card border rounded-2xl shadow-soft shadow-hover h-100 bg-white">
            <div class="tech-img-wrap">
              <span class="badge badge-info text-white position-absolute" style="top: 12px; left: 12px; z-index: 2;">Tặng túi</span>
              <img src="https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=500&q=80" alt="Bàn phím cơ">
            </div>
            <div class="card-body p-3 p-md-4 d-flex flex-column">
              <span class="text-muted small mb-1">Gaming Gear</span>
              <h6 class="card-title font-weight-bold text-dark mb-1">Bàn Phím Cơ Custom Không Dây RGB Pro</h6>
              <div class="my-2">
                <span class="badge-spec">Hot-swap 5 pin</span>
                <span class="badge-spec">Gasket Mount</span>
                <span class="badge-spec">Wireless</span>
              </div>
              <div class="d-flex align-items-center mb-2">
                <span class="text-warning small mr-1"><i class="fas fa-star"></i> 5.0</span>
                <span class="text-muted small">(88)</span>
              </div>
              <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                <div>
                  <span class="text-danger font-weight-bold h5 mb-0 d-block">1.650.000 ₫</span>
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

    <!-- KHU VỰC 2: PHỤ KIỆN TIỆN ÍCH -->
    <section class="mb-5">
      <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
          <span class="text-primary font-weight-bold text-uppercase small tracking-wider">Hệ sinh thái thông minh</span>
          <h3 class="font-weight-bold mb-0">Phụ Kiện Tiện Ích Đời Mới</h3>
        </div>
        <a href="products.php" class="btn btn-outline-primary btn-sm rounded-pill-custom px-3 font-weight-bold">Khám phá phụ kiện &rarr;</a>
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
              <h6 class="card-title font-weight-bold text-dark mb-1">Chuột Công Thái Học Không Dây Master 3S</h6>
              <div class="my-2">
                <span class="badge-spec">MagSpeed</span>
                <span class="badge-spec">8000 DPI</span>
                <span class="badge-spec">Quiet Click</span>
              </div>
              <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                <div>
                  <span class="text-danger font-weight-bold h5 mb-0 d-block">1.990.000 ₫</span>
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
              <span class="badge badge-success text-white position-absolute" style="top: 12px; left: 12px; z-index: 2;">Công nghệ mới</span>
              <img src="https://images.unsplash.com/photo-1608156639585-34a0a56ee6c9?auto=format&fit=crop&w=500&q=80" alt="Loa Bluetooth">
            </div>
            <div class="card-body p-3 p-md-4 d-flex flex-column">
              <span class="text-muted small mb-1">Loa ngoài trời</span>
              <h6 class="card-title font-weight-bold text-dark mb-1">Loa Bluetooth Mini Bass Cực Sâu IPX7</h6>
              <div class="my-2">
                <span class="badge-spec">IPX7</span>
                <span class="badge-spec">25W</span>
                <span class="badge-spec">Pin 16h</span>
              </div>
              <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                <div>
                  <span class="text-danger font-weight-bold h5 mb-0 d-block">850.000 ₫</span>
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
              <img src="https://images.unsplash.com/photo-1583863788434-e58a36330cf0?auto=format&fit=crop&w=500&q=80" alt="Củ sạc GaN">
            </div>
            <div class="card-body p-3 p-md-4 d-flex flex-column">
              <span class="text-muted small mb-1">Nguồn sạc cao cấp</span>
              <h6 class="card-title font-weight-bold text-dark mb-1">Củ Sạc Nhanh GaN 100W 3 Cổng Type-C</h6>
              <div class="my-2">
                <span class="badge-spec">GaN 5</span>
                <span class="badge-spec">PD 3.0 / PPS</span>
                <span class="badge-spec">100W Max</span>
              </div>
              <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                <div>
                  <span class="text-danger font-weight-bold h5 mb-0 d-block">690.000 ₫</span>
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