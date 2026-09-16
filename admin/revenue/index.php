<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once('../../database/dbhelper.php');
require_once('../check_role.php');

// Chỉ cho phép Admin (role_id = 1)[cite: 2]
requireAdmin();

$filter_type  = $_GET['type'] ?? 'month';
$filter_date  = $_GET['date'] ?? date('Y-m-d');
$filter_month = $_GET['month'] ?? date('Y-m');
$filter_year  = $_GET['year'] ?? date('Y');

$where_completed = "status = 2"; // Đơn hoàn thành tính doanh thu
$where_all = "1=1"; // Điều kiện cho tất cả đơn để tính tỷ trọng tròn
$title_filter = "";

if ($filter_type === 'day' && !empty($filter_date)) {
    $where_completed .= " AND DATE(order_date) = '$filter_date'";
    $where_all       .= " AND DATE(order_date) = '$filter_date'";
    $title_filter     = "Ngày " . date('d/m/Y', strtotime($filter_date));
} elseif ($filter_type === 'year' && !empty($filter_year)) {
    $where_completed .= " AND YEAR(order_date) = '$filter_year'";
    $where_all       .= " AND YEAR(order_date) = '$filter_year'";
    $title_filter     = "Năm " . $filter_year;
} else {
    $where_completed .= " AND DATE_FORMAT(order_date, '%Y-%m') = '$filter_month'";
    $where_all       .= " AND DATE_FORMAT(order_date, '%Y-%m') = '$filter_month'";
    $title_filter     = "Tháng " . date('m/Y', strtotime($filter_month . '-01'));
}

// 1. Số liệu tổng quan (Đơn hoàn thành)
$stats = executeResult("SELECT COUNT(id) AS total_orders, 
                               COALESCE(SUM(total_money), 0) AS total_revenue, 
                               COALESCE(AVG(total_money), 0) AS avg_money 
                        FROM `Orders` WHERE $where_completed", true);

$total_orders  = intval($stats['total_orders'] ?? 0);
$total_revenue = intval($stats['total_revenue'] ?? 0);
$avg_money     = intval($stats['avg_money'] ?? 0);

// 2. Dữ liệu cho biểu đồ cột (Bar Chart)
$chart_labels = [];
$chart_values = [];

if ($filter_type === 'day') {
    // Thống kê theo từng mốc giờ (0h - 23h)
    $sql_timeline = "SELECT HOUR(order_date) AS m_hour, COALESCE(SUM(total_money), 0) AS revenue 
                     FROM `Orders` WHERE $where_completed 
                     GROUP BY HOUR(order_date) ORDER BY m_hour ASC";
    $raw_timeline = executeResult($sql_timeline);
    $map = [];
    foreach ($raw_timeline as $r) { $map[(int)$r['m_hour']] = (int)$r['revenue']; }
    for ($h = 6; $h <= 23; $h++) { // Quét từ 6h sáng đến 23h
        $chart_labels[] = $h . 'h';
        $chart_values[] = $map[$h] ?? 0;
    }
} elseif ($filter_type === 'year') {
    // Thống kê theo 12 tháng
    $sql_timeline = "SELECT MONTH(order_date) AS m_month, COALESCE(SUM(total_money), 0) AS revenue 
                     FROM `Orders` WHERE $where_completed 
                     GROUP BY MONTH(order_date) ORDER BY m_month ASC";
    $raw_timeline = executeResult($sql_timeline);
    $map = [];
    foreach ($raw_timeline as $r) { $map[(int)$r['m_month']] = (int)$r['revenue']; }
    for ($m = 1; $m <= 12; $m++) {
        $chart_labels[] = 'T' . $m;
        $chart_values[] = $map[$m] ?? 0;
    }
} else {
    // Thống kê theo từng ngày trong tháng
    $days_in_month = date('t', strtotime($filter_month . '-01'));
    $sql_timeline = "SELECT DAY(order_date) AS m_day, COALESCE(SUM(total_money), 0) AS revenue 
                     FROM `Orders` WHERE $where_completed 
                     GROUP BY DAY(order_date) ORDER BY m_day ASC";
    $raw_timeline = executeResult($sql_timeline);
    $map = [];
    foreach ($raw_timeline as $r) { $map[(int)$r['m_day']] = (int)$r['revenue']; }
    for ($d = 1; $d <= $days_in_month; $d++) {
        $chart_labels[] = 'N' . $d;
        $chart_values[] = $map[$d] ?? 0;
    }
}

// 3. Dữ liệu cho biểu đồ tròn (Doughnut Chart) theo trạng thái đơn hàng
// Status: 0: Chờ xác nhận, 1: Đang vận chuyển, 2: Hoàn thành, 3: Đã hủy
$status_counts = [0 => 0, 1 => 0, 2 => 0, 3 => 0];
$raw_status = executeResult("SELECT status, COUNT(id) AS total FROM `Orders` WHERE $where_all GROUP BY status");
if (!empty($raw_status)) {
    foreach ($raw_status as $st) {
        $status_counts[(int)$st['status']] = (int)$st['total'];
    }
}

// 4. Danh sách đơn hàng hoàn thành
$orders = executeResult("SELECT * FROM `Orders` WHERE $where_completed ORDER BY id DESC");

$page_title = 'Báo Cáo Doanh Thu Bán Hàng';
include '../layout/head.php'; 
include '../layout/sidebar.php'; 
?>

<!-- CDN Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-3">
  <?php include '../layout/topbar.php'; ?>

  <!-- THANH BỘ LỌC THỜI GIAN & XUẤT EXCEL -->
  <div class="custom-card bg-white rounded-lg shadow-sm border mb-4">
    <div class="p-3 px-4 d-flex justify-content-between align-items-center border-bottom flex-wrap">
      <div>
        <h5 class="font-weight-bold mb-0 text-dark">
          <i class="fas fa-chart-line text-primary mr-2"></i>Báo Cáo & Thống Kê Doanh Thu
        </h5>
        <small class="text-muted">Đang xem dữ liệu: <strong><?= $title_filter ?></strong></small>
      </div>
      
      <div class="mt-2 mt-md-0">
        <a href="export_excel.php?type=<?= $filter_type ?>&date=<?= $filter_date ?>&month=<?= $filter_month ?>&year=<?= $filter_year ?>" 
           class="btn btn-sm btn-success font-weight-bold shadow-sm">
          <i class="fas fa-file-excel mr-1"></i> Xuất File Excel
        </a>
      </div>
    </div>

    <!-- Biểu mẫu bộ lọc -->
    <div class="p-3 px-4 bg-light">
      <form method="GET" class="form-inline flex-wrap">
        <label class="font-weight-bold small text-dark mr-2">Lọc theo mốc:</label>
        
        <select name="type" id="filterTypeSelect" class="form-control form-control-sm mr-2 mb-2 mb-md-0 font-weight-bold" onchange="toggleFilterInputs(this.value)">
          <option value="day" <?= ($filter_type === 'day') ? 'selected' : '' ?>>Theo Ngày</option>
          <option value="month" <?= ($filter_type === 'month') ? 'selected' : '' ?>>Theo Tháng</option>
          <option value="year" <?= ($filter_type === 'year') ? 'selected' : '' ?>>Theo Năm</option>
        </select>

        <input type="date" name="date" id="inputDate" class="form-control form-control-sm mr-2 mb-2 mb-md-0 <?= ($filter_type !== 'day') ? 'd-none' : '' ?>" value="<?= htmlspecialchars($filter_date) ?>">
        <input type="month" name="month" id="inputMonth" class="form-control form-control-sm mr-2 mb-2 mb-md-0 <?= ($filter_type !== 'month') ? 'd-none' : '' ?>" value="<?= htmlspecialchars($filter_month) ?>">

        <select name="year" id="inputYear" class="form-control form-control-sm mr-2 mb-2 mb-md-0 <?= ($filter_type !== 'year') ? 'd-none' : '' ?>">
          <?php for ($y = date('Y'); $y >= 2023; $y--): ?>
            <option value="<?= $y ?>" <?= ($filter_year == $y) ? 'selected' : '' ?>>Năm <?= $y ?></option>
          <?php endfor; ?>
        </select>

        <button type="submit" class="btn btn-sm btn-primary font-weight-bold px-3">
          <i class="fas fa-filter mr-1"></i> Áp dụng
        </button>
      </form>
    </div>
  </div>

  <!-- SỐ LIỆU TỔNG QUAN -->
  <div class="row mb-4">
    <div class="col-md-4 mb-3 mb-md-0">
      <div class="p-3 rounded border bg-white shadow-sm text-center">
        <span class="text-muted small font-weight-bold text-uppercase d-block mb-1">Tổng Doanh Thu</span>
        <h3 class="font-weight-bold text-danger mb-0"><?= number_format($total_revenue, 0, ',', '.') ?> đ</h3>
      </div>
    </div>
    <div class="col-md-4 mb-3 mb-md-0">
      <div class="p-3 rounded border bg-white shadow-sm text-center">
        <span class="text-muted small font-weight-bold text-uppercase d-block mb-1">Đơn Hoàn Thành</span>
        <h3 class="font-weight-bold text-success mb-0"><?= number_format($total_orders, 0, ',', '.') ?> đơn</h3>
      </div>
    </div>
    <div class="col-md-4">
      <div class="p-3 rounded border bg-white shadow-sm text-center">
        <span class="text-muted small font-weight-bold text-uppercase d-block mb-1">Giá Trị TB / Đơn</span>
        <h3 class="font-weight-bold text-primary mb-0"><?= number_format($avg_money, 0, ',', '.') ?> đ</h3>
      </div>
    </div>
  </div>

  <!-- KHU VỰC BIỂU ĐỒ TRỰC QUAN -->
  <div class="row mb-4">
    <!-- 1. BIỂU ĐỒ CỘT DOANH THU -->
    <div class="col-lg-8 mb-4 mb-lg-0">
      <div class="custom-card bg-white rounded-lg shadow-sm border p-3 h-100">
        <h6 class="font-weight-bold text-dark mb-3 border-bottom pb-2">
          <i class="fas fa-chart-bar text-primary mr-1"></i> Biến Động Doanh Thu (VNĐ)
        </h6>
        <div style="position: relative; height: 280px; width: 100%;">
          <canvas id="revenueBarChart"></canvas>
        </div>
      </div>
    </div>

    <!-- 2. BIỂU ĐỒ TRÒN TRẠNG THÁI ĐƠN HÀNG -->
    <div class="col-lg-4">
      <div class="custom-card bg-white rounded-lg shadow-sm border p-3 h-100">
        <h6 class="font-weight-bold text-dark mb-3 border-bottom pb-2">
          <i class="fas fa-chart-pie text-success mr-1"></i> Tỷ Trọng Đơn Hàng
        </h6>
        <div style="position: relative; height: 280px; width: 100%;">
          <canvas id="orderDoughnutChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- BẢNG CHI TIẾT ĐƠN HÀNG -->
  <div class="custom-card bg-white rounded-lg shadow-sm border mb-4">
    <div class="p-3 px-4 border-bottom">
      <h6 class="font-weight-bold mb-0 text-dark">Chi Tiết Đơn Hàng Hoàn Thành Trong Kỳ</h6>
    </div>

    <div class="p-4 table-responsive">
      <table class="table table-bordered table-hover mb-0 align-middle">
        <thead class="thead-light">
          <tr>
            <th width="100px" class="text-center align-middle">MÃ ĐƠN</th>
            <th class="align-middle">KHÁCH HÀNG</th>
            <th class="align-middle">ĐIỆN THOẠI</th>
            <th class="align-middle">ĐỊA CHỈ</th>
            <th class="align-middle text-center">NGÀY ĐẶT</th>
            <th width="160px" class="text-right align-middle">SỐ TIỀN</th>
            <th width="80px" class="text-center align-middle">XEM</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $ord): ?>
              <tr>
                <td class="text-center font-weight-bold text-primary align-middle">#ORD-<?= $ord['id'] ?></td>
                <td class="font-weight-bold text-dark align-middle"><?= htmlspecialchars($ord['fullname']) ?></td>
                <td class="align-middle small"><?= htmlspecialchars($ord['phone_number']) ?></td>
                <td class="align-middle small text-muted text-truncate" style="max-width: 220px;"><?= htmlspecialchars($ord['address']) ?></td>
                <td class="text-center align-middle small text-muted">
                  <?= date('d/m/Y H:i', strtotime($ord['order_date'])) ?>
                </td>
                <td class="text-right font-weight-bold text-danger align-middle">
                  <?= number_format($ord['total_money'], 0, ',', '.') ?> đ
                </td>
                <td class="text-center align-middle">
                  <a href="../order/detail.php?id=<?= $ord['id'] ?>" class="btn btn-sm btn-outline-info" title="Xem đơn hàng">
                    <i class="fas fa-eye"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="text-center text-muted py-4 align-middle">
                Không có đơn hàng hoàn thành nào trong khoảng thời gian này.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<script>
  function toggleFilterInputs(type) {
    document.getElementById('inputDate').classList.add('d-none');
    document.getElementById('inputMonth').classList.add('d-none');
    document.getElementById('inputYear').classList.add('d-none');

    if (type === 'day') {
      document.getElementById('inputDate').classList.remove('d-none');
    } else if (type === 'year') {
      document.getElementById('inputYear').classList.remove('d-none');
    } else {
      document.getElementById('inputMonth').classList.remove('d-none');
    }
  }

  // Khởi tạo biểu đồ với Chart.js
  document.addEventListener('DOMContentLoaded', function() {
    // 1. Cấu hình biểu đồ Cột (Doanh thu)
    const ctxBar = document.getElementById('revenueBarChart').getContext('2d');
    new Chart(ctxBar, {
      type: 'bar',
      data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
          label: 'Doanh thu (VNĐ)',
          data: <?= json_encode($chart_values) ?>,
          backgroundColor: '#3b82f6',
          borderRadius: 4,
          maxBarThickness: 32
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                return 'Doanh thu: ' + context.parsed.y.toLocaleString('vi-VN') + ' đ';
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function(value) {
                if (value >= 1000000) return (value / 1000000) + 'M';
                if (value >= 1000) return (value / 1000) + 'K';
                return value;
              }
            }
          }
        }
      }
    });

    // 2. Cấu hình biểu đồ Tròn (Trạng thái đơn hàng)
    const ctxDoughnut = document.getElementById('orderDoughnutChart').getContext('2d');
    new Chart(ctxDoughnut, {
      type: 'doughnut',
      data: {
        labels: ['Chờ duyệt', 'Đang giao', 'Hoàn thành', 'Đã hủy'],
        datasets: [{
          data: [
            <?= $status_counts[0] ?>,
            <?= $status_counts[1] ?>,
            <?= $status_counts[2] ?>,
            <?= $status_counts[3] ?>
          ],
          backgroundColor: [
            '#f59e0b', // Chờ duyệt (vàng)
            '#06b6d4', // Đang giao (cyan)
            '#10b981', // Hoàn thành (xanh lá)
            '#ef4444'  // Đã hủy (đỏ)
          ],
          borderWidth: 2,
          cutout: '65%'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: { boxWidth: 12, padding: 12 }
          }
        }
      }
    });
  });
</script>

<?php include '../layout/scripts.php'; ?>