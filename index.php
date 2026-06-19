<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hệ thống Quản lý Thực tập</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; background-color: #f4f4f9; }
        .container { max-width: 800px; margin: auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #2c3e50; }
        .menu-container { display: flex; gap: 20px; justify-content: center; margin-top: 30px; }
        .member-box { border: 1px solid #ddd; padding: 20px; border-radius: 8px; width: 300px; transition: 0.3s; }
        .member-box:hover { border-color: #3498db; box-shadow: 0 0 5px rgba(52,152,219,0.5); }
        h2 { color: #2980b9; border-bottom: 2px solid #3498db; padding-bottom: 5px; }
        ul { list-style: none; padding: 0; }
        li { margin-bottom: 15px; }
        a { text-decoration: none; color: #3498db; font-weight: bold; font-size: 1.1em; }
        a:hover { color: #2c3e50; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Bảng điều khiển Hệ thống Quản lý Thực tập</h1>
        <hr>
        
        <div class="menu-container">
            <div class="member-box">
                <h2>Thành viên 1</h2>
                <p><i>Quản lý Doanh nghiệp</i></p>
                <ul>
                    <li><a href="add_company.php">➕ Thêm Doanh nghiệp</a></li>
                    <li><a href="list_companies.php">📋 Danh sách Doanh nghiệp</a></li>
                </ul>
            </div>

            <div class="member-box">
                <h2>Thành viên 2</h2>
                <p><i>Quản lý Đăng ký</i></p>
                <ul>
                    <li><a href="registration.php">📝 Đăng ký Thực tập</a></li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>