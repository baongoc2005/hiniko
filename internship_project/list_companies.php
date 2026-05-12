<?php
include 'db.php';
// Gọi đúng tên các cột từ ảnh phpMyAdmin của bạn
$companies = $pdo->query("SELECT company_id, ten, su_mieu_ta, han_ngach FROM companies")->fetchAll();
?>
<body class="container mt-5">
    <h2>🏢 Danh sách Doanh nghiệp đối tác</h2>
    <table class="table table-bordered mt-3">
        <thead class="table-dark">
            <tr><th>Mã DN</th><th>Tên công ty</th><th>Mô tả</th><th>Hạn ngạch</th></tr>
        </thead>
        <tbody>
            <?php foreach ($companies as $c): ?>
            <tr>
                <td><?= $c['company_id'] ?></td>
                <td><?= htmlspecialchars($c['ten']) ?></td>
                <td><?= htmlspecialchars($c['su_mieu_ta']) ?></td>
                <td><?= $c['han_ngach'] ?> SV</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="index.php" class="btn btn-secondary">Quay lại</a>
</body>