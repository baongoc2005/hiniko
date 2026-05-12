<?php
require 'db.php'; // Sử dụng db.php dùng PDO

try {
    // Lấy danh sách công ty
    $stmt = $pdo->query("SELECT * FROM companies ORDER BY create_at DESC");
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi truy vấn: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Danh sách công ty</title>
    <style>table, th, td { border: 1px solid black; border-collapse: collapse; padding: 10px; }</style>
</head>
<body>
    <h2>Danh sách Doanh nghiệp (Người 1)</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Tên công ty</th>
            <th>Hạn ngạch</th>
            <th>Hành động</th>
        </tr>
        <?php foreach ($companies as $row): ?>
        <tr>
            <td><?= $row['company_id'] ?></td>
            <td><?= $row['ten'] ?></td>
            <td><?= $row['han_ngach'] ?></td>
            <td>
                <a href="delete_company.php?id=<?= $row['company_id'] ?>" onclick="return confirm('Xóa?')">Xóa</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <br>
    <a href="add_company.php">Thêm công ty mới</a> | <a href="registration.php">Qua trang Người 2</a>
</body>
</html>