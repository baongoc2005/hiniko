<?php
include 'db.php';
$journals = $pdo->query("SELECT w.*, u.ten FROM weekly_journals w 
                         JOIN users u ON w.student_id = u.user_id 
                         ORDER BY w.week_number DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Nhật ký tuần</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h2 class="mb-4">📓 Nhật ký thực tập hàng tuần</h2>
    <?php foreach ($journals as $j): ?>
    <div class="card mb-3 border-left-success">
        <div class="card-body">
            <h6 class="fw-bold"><?= htmlspecialchars($j['ten']) ?> - Tuần <?= $j['week_number'] ?></h6>
            <p class="mb-1 text-muted small">Ngày gửi: <?= $j['Create_at'] ?></p>
            <p class="card-text italic">"<?= htmlspecialchars($j['content']) ?>"</p>
        </div>
    </div>
    <?php endforeach; ?>
    <a href="index.php" class="btn btn-secondary mt-3">Quay lại</a>
</body>
</html>