<?php
include 'db.php';

try {
    // SQL Lấy điểm: Hãy đảm bảo các tên cột ce.xxx và le.xxx khớp với DB của bạn
    // Mình sẽ để là registration_id (có chữ i) - nếu vẫn lỗi bạn hãy đổi thành regstration_id
    $sql = "SELECT u.ten AS student_name, 
                   ce.score AS c_score, 
                   le.score AS l_score,
                   ir.id
            FROM internship_registrations ir
            JOIN users u ON ir.student_id = u.user_id
            LEFT JOIN company_evaluations ce ON ir.id = ce.registration_id
            LEFT JOIN lecturer_evaluations le ON ir.id = le.registration_id";

    $stmt = $pdo->query($sql);
    $grades = $stmt->fetchAll();

    // SQL Lấy nhật ký tuân thủ
    $logs = $pdo->query("SELECT cl.*, u.ten 
                         FROM compliance_logs cl 
                         JOIN internship_registrations ir ON cl.registration_id = ir.id 
                         JOIN users u ON ir.student_id = u.user_id")->fetchAll();
} catch (PDOException $e) {
    // Nếu vẫn lỗi, trang web sẽ hiện thông báo hướng dẫn thay vì báo lỗi nghiêm trọng
    die("<div style='color:red; padding:20px; border:1px solid red;'>
            <h3>Lỗi kết nối Database!</h3>
            <p>Vui lòng kiểm tra lại tên cột trong SQL. Lỗi chi tiết: " . $e->getMessage() . "</p>
            <p>Mẹo: Kiểm tra xem trong bảng company_evaluations, cột ID đăng ký là <b>registration_id</b> hay <b>regstration_id</b> rồi sửa lại trong code.</p>
         </div>");
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Bảng Điểm Tổng Kết</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h2 class="text-primary mb-4">📊 Kết Quả Thực Tập Cuối Kỳ</h2>
    <table class="table table-bordered shadow-sm">
        <thead class="table-dark">
            <tr>
                <th>Sinh viên</th>
                <th>Điểm DN (60%)</th>
                <th>Điểm GV (40%)</th>
                <th>Tổng kết</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($grades as $g): 
                $cs = $g['c_score'] ?? 0;
                $ls = $g['l_score'] ?? 0;
                $final = ($cs * 0.6) + ($ls * 0.4);
            ?>
            <tr>
                <td><?= htmlspecialchars($g['student_name']) ?></td>
                <td><?= $cs ?></td>
                <td><?= $ls ?></td>
                <td class="fw-bold text-danger"><?= number_format($final, 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="index.php" class="btn btn-secondary mt-3">Quay lại Trang chủ</a>
</body>
</html>