<?php
require_once 'db.php';

// SQL lấy điểm từ cả 2 bên và thông tin sinh viên
$sql = "SELECT u.ten AS student_name, 
               ce.score AS company_score, 
               le.score AS lecturer_score,
               ir.id AS reg_id
        FROM internship_registrations ir
        JOIN users u ON ir.student_id = u.id
        LEFT JOIN company_evaluations ce ON ir.id = ce.registration_id
        LEFT JOIN lecturer_evaluations le ON ir.id = le.registration_id";

$results = $pdo->query($sql)->fetchAll();

// SQL lấy nhật ký vi phạm
$logs = $pdo->query("SELECT cl.*, u.ten FROM compliance_logs cl JOIN internship_registrations ir ON cl.registration_id = ir.id JOIN users u ON ir.student_id = u.id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Bảng Điểm Tổng Kết - HINIKO</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 30px; line-height: 1.6; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
        th, td { border: 1px solid #dee2e6; padding: 15px; text-align: left; }
        th { background-color: #343a40; color: white; }
        tr:nth-child(even) { background-color: #f8f9fa; }
        .pass { color: #28a745; font-weight: bold; }
        .fail { color: #dc3545; font-weight: bold; }
        .alert-box { background: #fff3cd; border-left: 5px solid #ffc107; padding: 15px; margin-top: 40px; }
    </style>
</head>
<body>
    <h1>Bảng Điểm Tổng Kết (DN 60% - GV 40%)</h1>
    <table>
        <thead>
            <tr>
                <th>Sinh viên</th>
                <th>Điểm DN</th>
                <th>Điểm GV</th>
                <th>Điểm Tổng Kết</th>
                <th>Trạng thái</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $r): 
                $final = null;
                if ($r['company_score'] !== null && $r['lecturer_score'] !== null) {
                    $final = ($r['company_score'] * 0.6) + ($r['lecturer_score'] * 0.4);
                }
            ?>
            <tr>
                <td><?= $r['student_name'] ?></td>
                <td><?= $r['company_score'] ?? '---' ?></td>
                <td><?= $r['lecturer_score'] ?? '---' ?></td>
                <td><strong><?= $final !== null ? number_format($final, 2) : 'Đang chờ...' ?></strong></td>
                <td>
                    <?php if ($final >= 5): ?>
                        <span class="pass">Đạt</span>
                    <?php elseif ($final !== null): ?>
                        <span class="fail">Không đạt</span>
                    <?php else: ?>
                        <span>Chưa đủ cột điểm</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="alert-box">
        <h2>Nhật ký vi phạm & Tuân thủ</h2>
        <table>
            <tr style="background: #eee;">
                <th>Sinh viên</th>
                <th>Vấn đề vi phạm</th>
                <th>Ngày ghi nhận</th>
            </tr>
            <?php if (empty($logs)): ?>
                <tr><td colspan="3">Không có vi phạm nào.</td></tr>
            <?php else: ?>
                <?php foreach ($logs as $l): ?>
                <tr>
                    <td><?= $l['ten'] ?></td>
                    <td><?= $l['van_de'] ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($l['create_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>