<?php
require 'db.php'; // Sử dụng file kết nối PDO chung của nhóm

$message = "";

// --- 1. XỬ LÝ CREATE (Thêm đăng ký mới) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'create') {
    $student_id = $_POST['student_id'];
    $position_id = $_POST['position_id'];

    try {
        // BUSINESS RULE: Ngăn chặn sinh viên đăng ký cùng 1 vị trí 2 lần
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM internship_registrations WHERE student_id = ? AND position_id = ?");
        $stmt_check->execute([$student_id, $position_id]);
        $exists = $stmt_check->fetchColumn();

        if ($exists > 0) {
            $message = "Lỗi Backend: Sinh viên này đã đăng ký vị trí này rồi!";
        } else {
            // Thêm bản ghi mới với trạng thái mặc định là 'Pending'
            $stmt = $pdo->prepare("INSERT INTO internship_registrations (student_id, position_id, status) VALUES (?, ?, 'Pending')");
            if ($stmt->execute([$student_id, $position_id])) {
                $message = "Đăng ký thực tập thành công!";
            }
        }
    } catch (PDOException $e) {
        $message = "Lỗi hệ thống: " . $e->getMessage();
    }
}

// --- 2. XỬ LÝ DELETE (Xóa đăng ký) ---
if (isset($_GET['delete_id'])) {
    try {
        $stmt_delete = $pdo->prepare("DELETE FROM internship_registrations WHERE id = ?");
        $stmt_delete->execute([$_GET['delete_id']]);
        header("Location: registration.php"); // Chuyển hướng về trang hiện tại
        exit;
    } catch (PDOException $e) {
        $message = "Lỗi khi xóa: " . $e->getMessage();
    }
}

// --- 3. XỬ LÝ READ (Lấy danh sách hiển thị) ---
try {
    $stmt_list = $pdo->query("SELECT * FROM internship_registrations ORDER BY registration_date DESC");
    $registrations = $stmt_list->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi tải dữ liệu: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Đăng ký Thực tập</title>
    <style> 
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; } 
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f2f2f2; }
        .form-container { border: 1px solid #3498db; padding: 20px; border-radius: 8px; background: #f9f9f9; }
        .btn-delete { color: #e74c3c; font-weight: bold; text-decoration: none; }
    </style>
</head>
<body>
    <a href="index.php">← Quay lại Bảng điều khiển</a>
    <h2>Module: Quản lý Đăng ký Thực tập (Thành viên 2)</h2>

    <div class="form-container">
        <h3>Thêm Đăng ký Mới</h3>
        <p style="color: red;"><?= htmlspecialchars($message) ?></p>
        <form method="POST" action="">
            <input type="hidden" name="action" value="create">
            <label>ID Sinh viên:</label><br>
            <input type="number" name="student_id" required><br><br>
            
            <label>ID Vị trí doanh nghiệp:</label><br>
            <input type="number" name="position_id" required><br><br>
            
            <button type="submit" style="background: #3498db; color: white; border: none; padding: 10px 20px; cursor: pointer;">Đăng ký ngay</button>
        </form>
    </div>

    <h3>Danh sách Đăng ký hiện tại</h3>
    <table>
        <tr>
            <th>ID</th>
            <th>ID SV</th>
            <th>ID Vị trí</th>
            <th>Ngày đăng ký</th>
            <th>Trạng thái</th>
            <th>Hành động</th>
        </tr>
        <?php foreach ($registrations as $row): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['student_id']) ?></td>
            <td><?= htmlspecialchars($row['position_id']) ?></td>
            <td><?= $row['registration_date'] ?></td>
            <td><span style="padding: 5px; background: #eee; border-radius: 4px;"><?= $row['status'] ?></span></td>
            <td>
                <a href="update.php?id=<?= $row['id'] ?>">Sửa trạng thái</a> | 
                <a href="?delete_id=<?= $row['id'] ?>" class="btn-delete" onclick="return confirm('Xác nhận xóa đăng ký này?')">Xóa</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>