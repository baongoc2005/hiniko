<?php
require_once 'db.php';

// Xử lý khi bấm nút lưu điểm
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reg_id = $_POST['registration_id'];
    $type = $_POST['type']; // 'company' hoặc 'lecturer'
    $score = $_POST['score'];
    $comment = $_POST['comment'];

    if ($type == 'company') {
        $stmt = $pdo->prepare("INSERT INTO company_evaluations (registration_id, score, comment) VALUES (?, ?, ?)");
    } else {
        $stmt = $pdo->prepare("INSERT INTO lecturer_evaluations (registration_id, score, comment) VALUES (?, ?, ?)");
    }
    $stmt->execute([$reg_id, $score, $comment]);
    echo "<script>alert('Đã lưu đánh giá thành công!');</script>";
}

// Lấy danh sách sinh viên đã đăng ký để hiển thị trong menu chọn
$students = $pdo->query("SELECT ir.id, u.ten FROM internship_registrations ir JOIN users u ON ir.student_id = u.id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chấm điểm - Người 3</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f9f9f9; padding: 40px; }
        .box { background: white; padding: 25px; border-radius: 10px; max-width: 500px; margin: auto; shadow: 0 4px 6px rgba(0,0,0,0.1); border: 1px solid #ddd; }
        h2 { color: #333; text-align: center; }
        select, input, textarea { width: 100%; padding: 10px; margin: 10px 0; border-radius: 5px; border: 1px solid #ccc; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Nhập Đánh Giá Thực Tập</h2>
        <form method="POST">
            <label>Chọn Sinh viên:</label>
            <select name="registration_id" required>
                <?php foreach ($students as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= $s['ten'] ?> (Mã ĐK: <?= $s['id'] ?>)</option>
                <?php endforeach; ?>
            </select>

            <label>Đối tượng đánh giá:</label>
            <select name="type">
                <option value="company">Doanh nghiệp</option>
                <option value="lecturer">Giảng viên hướng dẫn</option>
            </select>

            <label>Điểm số (hệ 10):</label>
            <input type="number" step="0.1" name="score" min="0" max="10" placeholder="VD: 8.5" required>

            <label>Nhận xét chi tiết:</label>
            <textarea name="comment" rows="4" placeholder="Nhập nhận xét tại đây..."></textarea>

            <button type="submit">Lưu Kết Quả</button>
        </form>
    </div>
</body>
</html>