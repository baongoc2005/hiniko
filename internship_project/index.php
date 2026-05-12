<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống Quản lý Thực tập - HINIKO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --card-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #f8f9fa;
            color: #2d3436;
        }
        .header-section {
            background: var(--primary-gradient);
            color: white;
            padding: 60px 0;
            margin-bottom: -50px;
            border-radius: 0 0 50px 50px;
        }
        .card-custom {
            border: none;
            border-radius: 20px;
            background: #fff;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            height: 100%;
            overflow: hidden;
        }
        .card-custom:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        .card-header-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .icon-1 { background: #e3f2fd; color: #2196f3; }
        .icon-2 { background: #e8f5e9; color: #4caf50; }
        .icon-3 { background: #fff3e0; color: #ff9800; }
        
        .nav-link-custom {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            margin-bottom: 8px;
            color: #4a5568;
            text-decoration: none;
            border-radius: 12px;
            transition: 0.2s;
            border: 1px solid transparent;
        }
        .nav-link-custom:hover {
            background-color: #f1f5f9;
            color: #4834d4;
            border-color: #e2e8f0;
        }
        .nav-link-custom i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }
        .badge-member {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
        }
    </style>
</head>
<body>

<div class="header-section text-center">
    <div class="container">
        <h1 class="display-5 fw-bold">Hệ Thống Quản Lý Thực Tập</h1>
        <p class="lead opacity-75">Nền tảng số hóa quy trình thực tập nhóm HINIKO</p>
    </div>
</div>

<div class="container pb-5">
    <div class="row g-4 mt-2">
        <div class="col-md-4">
            <div class="card card-custom p-4">
                <div class="badge-member text-primary mb-2">Thành viên 01</div>
                <div class="card-header-icon icon-1"><i class="fa-solid fa-building-user"></i></div>
                <h4 class="fw-bold mb-3">Quản lý Doanh nghiệp</h4>
                <div class="nav-list">
                    <a href="add_company.php" class="nav-link-custom"><i class="fa-solid fa-plus-circle"></i> Thêm doanh nghiệp mới</a>
                    <a href="list_companies.php" class="nav-link-custom"><i class="fa-solid fa-list-check"></i> Danh sách đối tác</a>
                    <a href="manage_periods.php" class="nav-link-custom"><i class="fa-solid fa-calendar-days"></i> Thiết lập đợt thực tập</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-custom p-4">
                <div class="badge-member text-success mb-2">Thành viên 02</div>
                <div class="card-header-icon icon-2"><i class="fa-solid fa-user-graduate"></i></div>
                <h4 class="fw-bold mb-3">Đăng ký & Tiến độ</h4>
                <div class="nav-list">
                    <a href="registration.php" class="nav-link-custom"><i class="fa-solid fa-file-signature"></i> Đăng ký thực tập</a>
                    <a href="update.php" class="nav-link-custom"><i class="fa-solid fa-spinner"></i> Cập nhật trạng thái</a>
                    <a href="upload_handler.php" class="nav-link-custom"><i class="fa-solid fa-cloud-arrow-up"></i> Quản lý tập tin</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-custom p-4">
                <div class="badge-member text-warning mb-2">Thành viên 03</div>
                <div class="card-header-icon icon-3"><i class="fa-solid fa-chart-line"></i></div>
                <h4 class="fw-bold mb-3">Đánh giá & Kết quả</h4>
                <div class="nav-list">
                    <a href="evaluation.php" class="nav-link-custom"><i class="fa-solid fa-star-half-stroke"></i> Chấm điểm & Nhận xét</a>
                    <a href="final_grades.php" class="nav-link-custom"><i class="fa-solid fa-square-poll-vertical"></i> Bảng điểm tổng hợp</a>
                    <a href="final_grades.php#compliance" class="nav-link-custom"><i class="fa-solid fa-triangle-exclamation"></i> Theo dõi tuân thủ</a>
                </div>
            </div>
        </div>
    </div>

    <footer class="mt-5 text-center text-muted small">
        &copy; 2026 Project Hiniko Team. Tất cả quyền được bảo lưu.
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>