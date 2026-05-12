<?php
include 'db.php';

try {
    // Đã sửa: JOIN dựa trên 'company_id' thay vì 'id'
    $sql = "SELECT p.*, c.ten 
            FROM internship_positions p 
            JOIN companies c ON p.company_id = c.company_id"; 
    
    $stmt = $pdo->query($sql);
    $positions = $stmt->fetchAll();
} catch (PDOException $e) {
    die("<div class='alert alert-danger'>Lỗi SQL: " . $e->getMessage() . "</div>");
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Vị trí Thực tập</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h2 class="text-primary fw-bold mb-4">💼 Danh sách Vị trí Thực tập</h2>
    <div class="row g-4">
        <?php foreach ($positions as $p): ?>
            <div class="col-md-4">
                <div class="card shadow-sm h-100 border-0">
                    <div class="card-body">
                        <h5 class="fw-bold"><?= htmlspecialchars($p['title']) ?></h5>
                        <p class="text-muted mb-1">🏢 <?= htmlspecialchars($p['ten']) ?></p>
                        <p class="small">Số lượng: <span class="badge bg-info text-dark"><?= $p['capacity'] ?></span></p>
                        <p class="card-text text-secondary"><?= htmlspecialchars($p['description']) ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <hr>
    <a href="index.php" class="btn btn-secondary">Quay lại trang chủ</a>
</body>
</html>