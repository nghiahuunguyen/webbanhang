<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - Web Bán Hàng</title>
  
  <!-- Bootstrap 4 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

  <style>
    body {
      min-height: 100vh;
      background-color: #f4f6f9;
    }
    #sidebar {
      min-height: 100vh;
      background-color: #343a40;
    }
    #sidebar .nav-link {
      color: #c2c7d0;
      padding: 12px 20px;
      font-size: 0.95rem;
      border-radius: 4px;
      margin: 2px 10px;
    }
    #sidebar .nav-link:hover, #sidebar .nav-link.active {
      color: #ffffff;
      background-color: #007bff;
    }
    #sidebar .nav-link i {
      width: 25px;
    }
    .brand-title {
      font-size: 1.2rem;
      font-weight: bold;
      color: #fff;
      padding: 18px 20px;
      display: block;
      border-bottom: 1px solid #4b545c;
    }
  </style>
</head>
<body>

<div class="container-fluid">
  <div class="row">
    
    <!-- SIDEBAR: Danh mục quản lý theo database webbanhang -->
    <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block px-0 collapse">
      <a href="#" class="brand-title text-decoration-none text-center">
        <i class="fas fa-store mr-2"></i> SHOP ADMIN
      </a>
      
      <ul class="nav flex-column mt-3">
        <li class="nav-item">
          <a class="nav-link active" href="#">
            <i class="fas fa-tachometer-alt"></i> Bảng điều khiển
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">
            <i class="fas fa-th-list"></i> Danh mục (category)
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">
            <i class="fas fa-box-open"></i> Sản phẩm (product)
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">
            <i class="fas fa-images"></i> Bộ sưu tập (galery)
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">
            <i class="fas fa-shopping-cart"></i> Đơn hàng (orders)
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">
            <i class="fas fa-receipt"></i> Chi tiết đơn (order_details)
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">
            <i class="fas fa-comments"></i> Phản hồi (feedback)
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">
            <i class="fas fa-users"></i> Người dùng (user)
          </a>
        </li>
      </ul>
    </nav>

    <!-- MAIN CONTENT -->
    <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 pb-5">
      
      <!-- Top Navbar -->
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom bg-white px-3 mt-2 rounded shadow-sm">
        <button class="btn btn-outline-secondary d-md-none mr-2" type="button" data-toggle="collapse" data-target="#sidebar">
          <i class="fas fa-bars"></i>
        </button>
        <h1 class="h4 mb-0 font-weight-bold text-dark">Tổng Quan Hệ Thống</h1>
        
        <div class="dropdown">
          <button class="btn btn-light dropdown-toggle font-weight-bold" type="button" id="userMenu" data-toggle="dropdown">
            <i class="fas fa-user-circle mr-1"></i> Quản trị viên
          </button>
          <div class="dropdown-menu dropdown-menu-right shadow-sm">
            <a class="dropdown-item" href="#"><i class="fas fa-id-card mr-2"></i>Hồ sơ</a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item text-danger" href="#"><i class="fas fa-sign-out-alt mr-2"></i>Đăng xuất</a>
          </div>
        </div>
      </div>

      <!-- Thống kê nhanh các bảng chính -->
      <div class="row">
        <!-- Đơn hàng -->
        <div class="col-xl-3 col-md-6 mb-4">
          <div class="card border-0 shadow-sm bg-primary text-white">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="text-uppercase mb-1 small font-weight-bold">Đơn hàng mới</h6>
                  <h3 class="mb-0 font-weight-bold">128</h3>
                </div>
                <i class="fas fa-shopping-cart fa-2x opacity-75"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Sản phẩm -->
        <div class="col-xl-3 col-md-6 mb-4">
          <div class="card border-0 shadow-sm bg-success text-white">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="text-uppercase mb-1 small font-weight-bold">Tổng sản phẩm</h6>
                  <h3 class="mb-0 font-weight-bold">534</h3>
                </div>
                <i class="fas fa-box fa-2x opacity-75"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Danh mục -->
        <div class="col-xl-3 col-md-6 mb-4">
          <div class="card border-0 shadow-sm bg-info text-white">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="text-uppercase mb-1 small font-weight-bold">Danh mục</h6>
                  <h3 class="mb-0 font-weight-bold">16</h3>
                </div>
                <i class="fas fa-tags fa-2x opacity-75"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Phản hồi -->
        <div class="col-xl-3 col-md-6 mb-4">
          <div class="card border-0 shadow-sm bg-warning text-white">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="text-uppercase mb-1 small font-weight-bold">Feedback</h6>
                  <h3 class="mb-0 font-weight-bold">42</h3>
                </div>
                <i class="fas fa-envelope fa-2x opacity-75"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bảng dữ liệu: Đơn hàng mới nhất (Demo load từ bảng orders) -->
      <div class="card shadow-sm border-0 mt-3">
        <div class="card-header bg-white font-weight-bold py-3">
          <i class="fas fa-clock mr-2 text-primary"></i> Đơn hàng cần xử lý gần đây
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
              <thead class="thead-light">
                <tr>
                  <th>#ID</th>
                  <th>Khách hàng</th>
                  <th>Số điện thoại</th>
                  <th>Địa chỉ giao</th>
                  <th>Tổng tiền</th>
                  <th>Trạng thái</th>
                  <th class="text-center">Hành động</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><strong>#101</strong></td>
                  <td>Nguyễn Văn A</td>
                  <td>0901234567</td>
                  <td>Quận 1, TP. Hồ Chí Minh</td>
                  <td>1.250.000 VNĐ</td>
                  <td><span class="badge badge-warning">Chờ duyệt</span></td>
                  <td class="text-center">
                    <button class="btn btn-sm btn-info" title="Xem chi tiết"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-sm btn-danger" title="Xóa"><i class="fas fa-trash"></i></button>
                  </td>
                </tr>
                <tr>
                  <td><strong>#102</strong></td>
                  <td>Trần Thị B</td>
                  <td>0988765432</td>
                  <td>Hải Châu, Đà Nẵng</td>
                  <td>850.000 VNĐ</td>
                  <td><span class="badge badge-success">Đã hoàn thành</span></td>
                  <td class="text-center">
                    <button class="btn btn-sm btn-info" title="Xem chi tiết"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-sm btn-danger" title="Xóa"><i class="fas fa-trash"></i></button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </main>
  </div>
</div>

<!-- Thư viện JS -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>