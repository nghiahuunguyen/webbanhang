<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= !empty($page_title) ? $page_title . ' - Admin' : 'CommerceOS - Admin Dashboard' ?></title>

  <!-- Font chữ Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap 4 CSS & Font Awesome 5 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f8fafc;
      color: #334155;
      overflow-x: hidden;
    }

    /* Sidebar */
    #sidebar {
      background-color: #ffffff;
      border-right: 1px solid #edf2f7;
      min-height: 100vh;
      transition: all 0.3s;
    }
    .brand-box {
      padding: 24px;
      font-weight: 700;
      font-size: 1.15rem;
      letter-spacing: -0.5px;
      color: #0f172a;
      display: flex;
      align-items: center;
    }
    .brand-icon {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 12px;
      box-shadow: 0 4px 10px rgba(79, 70, 229, 0.35);
    }
    .menu-header {
      font-size: 0.72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      color: #94a3b8;
      padding: 12px 24px 6px;
    }
    .nav-link {
      color: #64748b;
      font-weight: 500;
      font-size: 0.9rem;
      padding: 10px 16px;
      margin: 3px 14px;
      border-radius: 8px;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
    }
    .nav-link i {
      font-size: 1rem;
      margin-right: 12px;
      width: 20px;
      text-align: center;
    }
    .nav-link:hover {
      color: #4f46e5;
      background-color: #f1f5f9;
    }
    .nav-link.active {
      color: #4f46e5;
      background-color: #eef2ff;
      font-weight: 600;
    }

    /* Topbar */
    .topbar {
      background: #ffffff;
      border: 1px solid #edf2f7;
      border-radius: 12px;
      padding: 12px 24px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .search-box input {
      border: none;
      background-color: #f1f5f9;
      border-radius: 8px;
      padding: 8px 16px 8px 36px;
      font-size: 0.875rem;
    }
    .search-box i {
      position: absolute;
      left: 14px;
      top: 12px;
      color: #94a3b8;
    }

    /* Metric Cards */
    .stat-card {
      border: 1px solid #edf2f7;
      border-radius: 14px;
      padding: 20px;
      background: #ffffff;
      box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02), 0 2px 4px -1px rgba(0,0,0,0.02);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .stat-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
    }
    .stat-icon-wrapper {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
    }

    /* Data Table Card */
    .custom-card {
      background: #ffffff;
      border: 1px solid #edf2f7;
      border-radius: 14px;
      box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
    }
    .table thead th {
      border-top: none;
      border-bottom: 1px solid #edf2f7;
      color: #64748b;
      font-size: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      font-weight: 600;
      padding: 14px 20px;
    }
    .table tbody td {
      border-top: 1px solid #f1f5f9;
      padding: 16px 20px;
      vertical-align: middle;
      font-size: 0.88rem;
    }
    .badge-soft-success {
      background-color: #dcfce7;
      color: #15803d;
      font-weight: 600;
      padding: 6px 12px;
      border-radius: 6px;
    }
    .badge-soft-warning {
      background-color: #fef9c3;
      color: #854d0e;
      font-weight: 600;
      padding: 6px 12px;
      border-radius: 6px;
    }
  </style>
</head>
<body>
<div class="container-fluid">
  <div class="row">