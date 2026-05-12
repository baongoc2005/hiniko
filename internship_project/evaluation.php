<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reg_id = $_POST['reg_id'];
    $score = $_POST['score'];
    $comment = $_POST['comment'];
    $type = $_POST['type']; 

    // Kiểm tra tên cột regstration_id trong DB của bạn (ERD ghi thiếu chữ i)
    if ($type == 'company') {
        $sql = "INSERT INTO company_evaluations (regstration_id, score, comment) VALUES (?, ?, ?)";
    } else {
        $sql = "INSERT INTO lecturer_evaluations (regstration_id, score, comment) VALUES (?, ?, ?)";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$reg_id, $score, $comment]);
    echo "<script>alert('Đã lưu đánh giá thành công!');</script>";
}

// LẤY DANH SÁCH: Nếu DB của bạn dùng IR_ID thì sửa id -> IR_ID
$students = $pdo->query("SELECT ir.id, u.ten 
                         FROM internship_registrations ir 
                         JOIN users u ON ir.student_id = u.user_id")->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chấm điểm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <div class="card p-4 shadow">
        <h2 class="mb-4 text-success">⭐ Chấm điểm thực tập</h2>
        <form method="POST">
            <div class="mb-3">
                <label>Chọn Sinh viên:</label>
                <select name="reg_id" class="form-select" required>
                    <?php foreach ($students as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['ten']) ?> (Mã: <?= $s['id'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Đối tượng chấm:</label>
                <select name="type" class="form-select">
                    <option value="company">Doanh nghiệp (60%)</option>
                    <option value="lecturer">Giảng viên (40%)</option>
                </select>
            </div>
            <div class="mb-3">
                <label>Điểm số:</label>
                <input type="number" name="score" step="0.1" min="0" max="10" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Nhận xét:</label>
                <textarea name="comment" class="form-control" rows="3"></textarea>
            </div>
            <button type="submit" class="btn btn-success w-100">Lưu kết quả</button>
            <a href="index.php" class="btn btn-link w-100 mt-2">Quay lại trang chủ</a>
        </form>
    </div>
</body>
</html>