<?php
include 'db.php';
$stmt = $pdo->query("SELECT * FROM users");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý tài khoản</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h2 class="mb-4">👥 Danh sách tài khoản người dùng</h2>
    <table class="table table-hover border">
        <thead class="table-primary">
            <tr><th>ID</th><th>Họ tên</th><th>Email</th><th>Vai trò</th></tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= $u['user_id'] ?></td>
                <td><?= htmlspecialchars($u['ten']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="badge bg-info text-dark"><?= $u['vai_tro'] ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="index.php" class="btn btn-secondary">Quay lại trang chủ</a>
</body>
</html>