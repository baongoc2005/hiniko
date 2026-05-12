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
} else {
    // Nếu không có ID thì quay về trang danh sách
    header("Location: registration.php");
    exit;
}

// Xử lý khi bấm nút "Cập nhật"
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $new_status = $_POST['status'];

    $stmt_update = $pdo->prepare("UPDATE internship_registrations SET status = ? WHERE id = ?");
    if ($stmt_update->execute([$new_status, $id])) {
        // QUAN TRỌNG: Sửa từ index.php thành registration.php
        header("Location: registration.php");
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
    <style> 
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; } 
        .container { max-width: 500px; border: 1px solid #ccc; padding: 20px; border-radius: 10px; }
        h2 { color: #2c3e50; }
        button { cursor: pointer; padding: 8px 15px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Cập nhật Trạng thái</h2>
        <form method="POST" action="">
            <input type="hidden" name="id" value="<?= $record['id'] ?>">
            
            <p><b>ID Sinh viên:</b> <?= htmlspecialchars($record['student_id']) ?></p>
            <p><b>ID Vị trí:</b> <?= htmlspecialchars($record['position_id']) ?></p>
            
            <label><b>Trạng thái mới:</b></label>
            <select name="status" style="padding: 5px;">
                <option value="Pending" <?= $record['status'] == 'Pending' ? 'selected' : '' ?>>Pending (Chờ duyệt)</option>
                <option value="Approved" <?= $record['status'] == 'Approved' ? 'selected' : '' ?>>Approved (Chấp nhận)</option>
                <option value="Rejected" <?= $record['status'] == 'Rejected' ? 'selected' : '' ?>>Rejected (Từ chối)</option>
            </select>
            <br><br>
            
            <button type="submit" style="background-color: #27ae60; color: white; border: none;">Lưu Cập nhật</button>
            
            <a href="registration.php"><button type="button">Quay lại</button></a>
        </form>
    </div>
</body>
</html>