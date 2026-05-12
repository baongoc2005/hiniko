<?php
include 'db.php';
$assignments = $pdo->query("SELECT ia.*, u.ten as student_name FROM internship_assignments ia 
                            JOIN users u ON ia.student_id = u.user_id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Phân công hướng dẫn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h2 class="mb-4">🤝 Danh sách phân công hướng dẫn</h2>
    <table class="table table-striped border text-center">
        <thead class="table-success">
            <tr><th>Mã SV</th><th>Tên Sinh viên</th><th>Trạng thái</th></tr>
        </thead>
        <tbody>
            <?php foreach ($assignments as $a): ?>
            <tr>
                <td><?= $a['student_id'] ?></td>
                <td><?= htmlspecialchars($a['student_name']) ?></td>
                <td><span class="badge bg-success"><?= $a['status'] ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="index.php" class="btn btn-secondary">Quay lại</a>
</body>
</html>