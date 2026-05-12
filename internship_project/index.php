<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hệ thống Quản lý Thực tập - HINIKO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; padding-top: 50px; }
        .card { border-radius: 15px; border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.08); transition: 0.3s; }
        .card:hover { transform: translateY(-10px); }
        .member-header { border-bottom: 2px solid #007bff; margin-bottom: 15px; padding-bottom: 10px; color: #007bff; }
        .btn-link-custom { text-decoration: none; color: #444; display: block; padding: 12px; border-radius: 10px; margin-bottom: 5px; }
        .btn-link-custom:hover { background-color: #f0f7ff; color: #007bff; }
    </style>
</head>
<body>
<div class="container">
    <h1 class="text-center fw-bold mb-5">Bảng điều khiển Hệ thống Quản lý Thực tập</h1>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card h-100 p-4">
                <h3 class="member-header">Thành viên 1</h3>
                <p class="text-muted small">Quản lý Doanh nghiệp & Hệ thống</p>
                <a href="add_company.php" class="btn-link-custom">➕ Thêm Doanh nghiệp</a>
                <a href="list_companies.php" class="btn-link-custom">📋 Danh sách Doanh nghiệp</a>
                <a href="manage_periods.php" class="btn-link-custom">📅 Quản lý Đợt thực tập</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 p-4">
                <h3 class="member-header" style="border-color: #17a2b8; color: #17a2b8;">Thành viên 2</h3>
                <p class="text-muted small">Quản lý Đăng ký & Tiến độ</p>
                <a href="registration.php" class="btn-link-custom">📝 Đăng ký Thực tập</a>
                <a href="update.php" class="btn-link-custom">⚙️ Cập nhật Trạng thái</a>
                <a href="upload_handler.php" class="btn-link-custom">📁 Quản lý Tập tin</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 p-4">
                <h3 class="member-header" style="border-color: #28a745; color: #28a745;">Thành viên 3</h3>
                <p class="text-muted small">Đánh giá & Tổng kết điểm</p>
                <a href="evaluation.php" class="btn-link-custom">⭐ Chấm điểm & Nhận xét</a>
                <a href="final_grades.php" class="btn-link-custom">📊 Bảng điểm Tổng kết</a>
                <a href="final_grades.php#compliance" class="btn-link-custom">⚠️ Nhật ký Tuân thủ</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>