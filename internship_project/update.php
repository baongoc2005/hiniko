<?php
require 'db.php';

// Lấy thông tin bản ghi hiện tại để hiển thị lên form
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM internship_registrations WHERE id = ?");
    $stmt->execute([$id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        die("Không tìm thấy dữ liệu!");
    }
}

// Xử lý khi bấm nút "Cập nhật"
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $new_status = $_POST['status'];

    $stmt_update = $pdo->prepare("UPDATE internship_registrations SET status = ? WHERE id = ?");
    if ($stmt_update->execute([$new_status, $id])) {
        // Cập nhật xong thì quay về trang chủ
        header("Location: index.php");
        exit;
    } else {
        echo "Cập nhật thất bại!";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Cập nhật Trạng thái</title>
    <style> body { font-family: Arial; margin: 20px; } </style>
</head>
<body>
    <h2>Cập nhật Trạng thái Đăng ký (Update)</h2>
    <form method="POST" action="">
        <input type="hidden" name="id" value="<?= $record['id'] ?>">
        
        <p><b>ID Sinh viên:</b> <?= $record['student_id'] ?></p>
        <p><b>ID Vị trí:</b> <?= $record['position_id'] ?></p>
        
        <label>Trạng thái mới:</label>
        <select name="status">
            <option value="Pending" <?= $record['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option value="Approved" <?= $record['status'] == 'Approved' ? 'selected' : '' ?>>Approved</option>
            <option value="Rejected" <?= $record['status'] == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
        </select>
        <br><br>
        <button type="submit">Lưu Cập nhật</button>
        <a href="index.php"><button type="button">Hủy</button></a>
    </form>
</body>
</html>