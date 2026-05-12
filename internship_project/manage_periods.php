<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Đợt thực tập - Người 1</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f0f2f5; }
        .container { max-width: 800px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h2 { color: #1a73e8; border-bottom: 2px solid #1a73e8; padding-bottom: 10px; }
        form { margin-bottom: 30px; background: #e8f0fe; padding: 15px; border-radius: 5px; }
        label { display: block; margin: 10px 0 5px; font-weight: bold; }
        input[type="text"], input[type="date"] { width: 95%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        button { background: #1a73e8; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f8f9fa; }
    </style>
</head>
<body>
<div class="container">
    <h2> Quản lý Đợt thực tập (Internship Period)</h2>
    
    <!-- Form thêm đợt mới -->
    <form action="manage_periods.php" method="POST">
        <label>Tên đợt thực tập:</label>
        <input type="text" name="ten" placeholder="Ví dụ: Học kỳ Hè 2026" required>
        
        <label>Ngày bắt đầu:</label>
        <input type="date" name="start_date" required>
        
        <label>Ngày kết thúc:</label>
        <input type="date" name="end_date" required>
        
        <button type="submit" name="add_period">Thêm Đợt mới</button>
    </form>

    <h3>Danh sách các đợt hiện có</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên đợt</th>
                <th>Bắt đầu</th>
                <th>Kết thúc</th>
                <th>Trạng thái</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>Học kỳ Hè 2026</td>
                <td>01/06/2026</td>
                <td>30/08/2026</td>
                <td><span style="color: green;">Đang mở</span></td>
            </tr>
        </tbody>
    </table>
</div>
</body>
</html>