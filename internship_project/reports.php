<?php
include 'db.php';
// Lấy dữ liệu tổng hợp để báo cáo
$reports = $pdo->query("SELECT u.ten, fg.final_grade 
                        FROM final_grades fg 
                        JOIN internship_registrations ir ON fg.regstration_id = ir.id 
                        JOIN users u ON ir.student_id = u.user_id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Báo cáo tổng kết</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5 text-center">
    <h2 class="mb-4 text-danger">📄 Báo Cáo Tổng Kết Thực Tập</h2>
    <div class="alert alert-light border border-warning shadow-sm">
        <table class="table table-hover">
            <thead>
                <tr><th>STT</th><th>Họ và Tên Sinh viên</th><th>Điểm Tổng Kết</th></tr>
            </thead>
            <tbody>
                <?php $n=1; foreach ($reports as $r): ?>
                <tr>
                    <td><?= $n++ ?></td>
                    <td><?= htmlspecialchars($r['ten']) ?></td>
                    <td class="fw-bold text-danger"><?= number_format($r['final_grade'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <button onclick="window.print()" class="btn btn-primary px-4">🖨️ In Báo Cáo (PDF)</button>
    <a href="index.php" class="btn btn-secondary px-4">Quay lại</a>
</body>
</html>