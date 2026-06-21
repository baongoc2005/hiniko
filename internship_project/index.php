<?php
require 'config/db.php';
require 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Welcome';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Internship Manager</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Arial, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.25), transparent 30%),
                radial-gradient(circle at bottom right, rgba(124, 58, 237, 0.25), transparent 30%),
                linear-gradient(135deg, #eef4ff, #f8f7ff);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #111827;
        }

        .welcome-container {
            width: 92%;
            max-width: 1100px;
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            background: rgba(255, 255, 255, 0.88);
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(15, 23, 42, 0.15);
            backdrop-filter: blur(18px);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        .left {
            padding: 60px;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            color: white;
        }

        .logo {
            width: 70px;
            height: 70px;
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.18);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 900;
            margin-bottom: 35px;
        }

        .left h1 {
            font-size: 48px;
            line-height: 1.15;
            margin: 0 0 22px;
        }

        .left p {
            font-size: 18px;
            line-height: 1.8;
            color: #e0e7ff;
            margin: 0;
        }

        .features {
            margin-top: 35px;
            display: grid;
            gap: 14px;
        }

        .feature {
            padding: 14px 18px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.14);
            font-weight: 600;
        }

        .right {
            padding: 60px 45px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .right h2 {
            font-size: 32px;
            margin: 0 0 12px;
        }

        .right p {
            color: #64748b;
            line-height: 1.7;
            margin-bottom: 30px;
        }

        .btn-group {
            display: grid;
            gap: 16px;
        }

        .btn {
            text-decoration: none;
            padding: 16px 22px;
            border-radius: 16px;
            font-weight: 800;
            text-align: center;
            transition: 0.25s;
            font-size: 16px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            color: white;
            box-shadow: 0 12px 30px rgba(37, 99, 235, 0.25);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 40px rgba(37, 99, 235, 0.35);
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #e2e8f0;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }

        .note {
            margin-top: 25px;
            font-size: 13px;
            color: #94a3b8;
        }

        @media (max-width: 850px) {
            .welcome-container {
                grid-template-columns: 1fr;
            }

            .left, .right {
                padding: 38px;
            }

            .left h1 {
                font-size: 36px;
            }
        }
    </style>
</head>
<body>

<div class="welcome-container">
    <div class="left">
        <div class="logo">IM</div>

        <h1>Internship Manager System</h1>

        <p>
            Hệ thống quản lý toàn bộ quy trình thực tập của sinh viên:
            đăng ký vị trí, phê duyệt, phân công giảng viên hướng dẫn,
            nộp nhật ký, upload hồ sơ, đánh giá và tính điểm tổng kết.
        </p>

        <div class="features">
            <div class="feature">✓ Company & Position Management</div>
            <div class="feature">✓ Internship Registration & Approval</div>
            <div class="feature">✓ Dual Evaluation & Final Grade</div>
        </div>
    </div>

    <div class="right">
        <h2>Welcome Back</h2>

        <p>
            Vui lòng đăng nhập để vào hệ thống quản lý.
            Nếu chưa có tài khoản, bạn có thể đăng ký tài khoản mới.
        </p>

        <div class="btn-group">
            <a href="login.php" class="btn btn-primary">Đăng nhập</a>
            <a href="register.php" class="btn btn-secondary">Đăng ký</a>
        </div>

        <div class="note">
            Demo account: admin@ischool.edu.vn / 123456
        </div>
    </div>
</div>

</body>
</html>