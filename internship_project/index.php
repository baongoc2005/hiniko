<?php
require 'db.php'; // Gọi file kết nối CSDL em vừa làm

$message = "";

// --- XỬ LÝ CREATE (THÊM ĐĂNG KÝ MỚI) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'create') {
    $student_id = $_POST['student_id'];
    $position_id = $_POST['position_id'];

    // BUSINESS RULE: Ngăn chặn sinh viên đăng ký cùng 1 vị trí 2 lần
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM internship_registrations WHERE student_id = ? AND position_id = ?");
    $stmt_check->execute([$student_id, $position_id]);
    $exists = $stmt_check->fetchColumn();

    if ($exists > 0) {
        $message = "Lỗi Backend: Sinh viên này đã đăng ký vị trí này rồi! Không thể đăng ký trùng lặp.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO internship_registrations (student_id, position_id, status) VALUES (?, ?, 'Pending')");
        if ($stmt->execute([$student_id, $position_id])) {
            $message = "Đăng ký thực tập thành công!";
        } else {
            $message = "Lỗi khi lưu vào cơ sở dữ liệu.";
        }
    }
}

// --- XỬ LÝ DELETE (XÓA ĐĂNG KÝ) ---
if (isset($_GET['delete_id'])) {
    $stmt_delete = $pdo->prepare("DELETE FROM internship_registrations WHERE id = ?");
    $stmt_delete->execute([$_GET['delete_id']]);
    header("Location: index.php");
    exit;
}

// --- XỬ LÝ READ (LẤY DANH SÁCH) ---
$stmt_list = $pdo->query("SELECT * FROM internship_registrations ORDER BY registration_date DESC");
$registrations = $stmt_list->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Đăng ký Thực tập</title>
    <style> body { font-family: Arial; margin: 20px; } table, th, td { border: 1px solid black; border-collapse: collapse; padding: 8px; } </style>
</head>
<body>
    <h2>Module: Quản lý Đăng ký Thực tập (Thành viên 2)</h2>

    <div style="border: 1px solid #ccc; padding: 15px; margin-bottom: 20px;">
        <h3>Thêm Đăng ký Mới (Create)</h3>
        <p style="color: red; font-weight: bold;"><?= $message ?></p>
        <form method="POST" action="">
            <input type="hidden" name="action" value="create">
            <label>ID Sinh viên (Giả lập):</label>
            <input type="number" name="student_id" required>
            <br><br>
            <label>ID Vị trí doanh nghiệp (Giả lập):</label>
            <input type="number" name="position_id" required>
            <br><br>
            <button type="submit">Đăng ký ngay</button>
        </form>
    </div>

    <h3>Danh sách Đăng ký (Read, Update, Delete)</h3>
    <table>
        <tr>
            <th>ID Đăng ký</th>
            <th>ID Sinh viên</th>
            <th>ID Vị trí</th>
            <th>Ngày đăng ký</th>
            <th>Trạng thái</th>
            <th>Hành động</th>
        </tr>
        <?php foreach ($registrations as $row): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= $row['student_id'] ?></td>
            <td><?= $row['position_id'] ?></td>
            <td><?= $row['registration_date'] ?></td>
            <td><b><?= $row['status'] ?></b></td>
            <td>
                <a href="update.php?id=<?= $row['id'] ?>">Sửa trạng thái</a> | 
                <a href="?delete_id=<?= $row['id'] ?>" onclick="return confirm('Bạn có chắc muốn xóa?');" style="color: red;">Xóa</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>