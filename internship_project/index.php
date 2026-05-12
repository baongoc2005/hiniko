<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>HINIKO - Quản lý Thực tập (3 Thành viên x 4 Bảng)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #2563eb; --secondary: #64748b; --bg: #f8fafc; }
        body { background-color: var(--bg); font-family: 'Inter', system-ui, sans-serif; }
        .hero { background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); color: white; padding: 50px 0; border-radius: 0 0 40px 40px; margin-bottom: 40px; }
        .card-custom { border: none; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); background: white; transition: 0.3s; height: 100%; }
        .card-custom:hover { transform: translateY(-10px); }
        .nav-link-custom { display: flex; align-items: center; padding: 12px 15px; margin-bottom: 8px; color: #334155; text-decoration: none; border-radius: 12px; background: #f1f5f9; transition: 0.2s; }
        .nav-link-custom:hover { background: #e2e8f0; color: var(--primary); }
        .nav-link-custom i { margin-right: 12px; width: 20px; text-align: center; }
        .badge-4 { float: right; background: var(--primary); color: white; border-radius: 20px; padding: 2px 10px; font-size: 11px; }
    </style>
</head>
<body>
<div class="hero text-center shadow">
    <h1 class="fw-bold">Hệ Thống Quản Lý Thực Tập HINIKO</h1>
    <p class="opacity-75">Cấu trúc chuyên nghiệp: 3 Thành viên | 12 Bảng dữ liệu</p>
</div>

<div class="container pb-5">
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card-custom p-4">
                <div class="mb-3 border-bottom pb-2">
                    <span class="badge-4">Admin</span>
                    <h5 class="fw-bold text-primary">THÀNH VIÊN 01</h5>
                </div>
                <a href="manage_users.php" class="nav-link-custom"><i class="fa-solid fa-users-gear"></i> Quản lý Tài khoản</a>
                <a href="list_companies.php" class="nav-link-custom"><i class="fa-solid fa-building"></i> Quản lý Doanh nghiệp</a>
                <a href="manage_positions.php" class="nav-link-custom"><i class="fa-solid fa-briefcase"></i> Vị trí Thực tập</a>
                <a href="manage_periods.php" class="nav-link-custom"><i class="fa-solid fa-calendar-days"></i> Đợt thực tập</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card-custom p-4">
                <div class="mb-3 border-bottom pb-2">
                    <span class="badge-4">Progress</span>
                    <h5 class="fw-bold text-success">THÀNH VIÊN 02</h5>
                </div>
                <a href="registration.php" class="nav-link-custom"><i class="fa-solid fa-file-signature"></i> Đăng ký Thực tập</a>
                <a href="assignments.php" class="nav-link-custom"><i class="fa-solid fa-user-check"></i> Phân công hướng dẫn</a>
                <a href="weekly_journals.php" class="nav-link-custom"><i class="fa-solid fa-book"></i> Nhật ký tuần</a>
                <a href="upload_handler.php" class="nav-link-custom"><i class="fa-solid fa-cloud-arrow-up"></i> Quản lý Tập tin</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card-custom p-4">
                <div class="mb-3 border-bottom pb-2">
                    <span class="badge-4">Results</span>
                    <h5 class="fw-bold text-warning">THÀNH VIÊN 03</h5>
                </div>
                <a href="evaluation.php" class="nav-link-custom"><i class="fa-solid fa-star-half-stroke"></i> Chấm điểm (DN & GV)</a>
                <a href="final_grades.php" class="nav-link-custom"><i class="fa-solid fa-chart-simple"></i> Bảng điểm tổng kết</a>
                <a href="final_grades.php#compliance" class="nav-link-custom"><i class="fa-solid fa-triangle-exclamation"></i> Nhật ký tuân thủ</a>
                <a href="reports.php" class="nav-link-custom"><i class="fa-solid fa-file-export"></i> Xuất báo cáo tổng kết</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>