<?php
require 'config/db.php';
require 'includes/auth.php';
requireLogin(['admin', 'lecturer', 'company']);

$pageTitle = "Evaluation Management";
$active = "evaluations";
$message = "";
$type = "success";

$role = currentRole();
$isAdmin = $role === 'admin';
$isLecturer = $role === 'lecturer';
$isCompany = $role === 'company';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $registration_id = (int)$_POST['registration_id'];
    // Company chỉ được nhập đánh giá doanh nghiệp; lecturer chỉ đánh giá giảng viên
    if ($isCompany) {
        $evalType = 'company';
    } elseif ($isLecturer) {
        $evalType = 'lecturer';
    } else {
        $evalType = $_POST['type'] === 'lecturer' ? 'lecturer' : 'company';
    }
    $score = (float)$_POST['score'];
    $comment = trim($_POST['comment']);

    // Kiểm tra quyền trên đăng ký đang chấm
    $allowed = true;
    if ($isCompany) {
        $chk = $pdo->prepare("
            SELECT COUNT(*) FROM internship_registrations ir
            JOIN internship_positions ip ON ir.position_id = ip.ip_id
            WHERE ir.ir_id = ? AND ip.company_id = ?
        ");
        $chk->execute([$registration_id, (int)currentCompanyId()]);
        $allowed = $chk->fetchColumn() > 0;
    } elseif ($isLecturer) {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM internship_assignments WHERE registration_id = ? AND lecturer_id = ?");
        $chk->execute([$registration_id, currentUserId()]);
        $allowed = $chk->fetchColumn() > 0;
    }

    if (!$allowed) {
        $type = "error";
        $message = "Bạn không có quyền đánh giá đăng ký này.";
    } else {
        if ($evalType === 'company') {
            $stmt = $pdo->prepare("
                INSERT INTO company_evaluations (registration_id, score, comment)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE score = VALUES(score), comment = VALUES(comment), evaluated_at = CURRENT_TIMESTAMP
            ");
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO lecturer_evaluations (registration_id, score, comment)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE score = VALUES(score), comment = VALUES(comment), evaluated_at = CURRENT_TIMESTAMP
            ");
        }
        $stmt->execute([$registration_id, $score, $comment]);
        $message = "Lưu đánh giá thành công.";
    }
}

// Danh sách đăng ký để chấm điểm (lọc theo role)
if ($isCompany) {
    $rStmt = $pdo->prepare("
        SELECT ir.ir_id, u.name AS student_name, ip.title, c.name AS company_name
        FROM internship_registrations ir
        JOIN users u ON ir.student_id = u.user_id
        JOIN internship_positions ip ON ir.position_id = ip.ip_id
        JOIN companies c ON ip.company_id = c.company_id
        WHERE ir.status = 'Approved' AND ip.company_id = ?
        ORDER BY ir.applied_at DESC
    ");
    $rStmt->execute([(int)currentCompanyId()]);
    $registrations = $rStmt->fetchAll();
} elseif ($isLecturer) {
    $rStmt = $pdo->prepare("
        SELECT ir.ir_id, u.name AS student_name, ip.title, c.name AS company_name
        FROM internship_registrations ir
        JOIN internship_assignments ia ON ia.registration_id = ir.ir_id
        JOIN users u ON ir.student_id = u.user_id
        JOIN internship_positions ip ON ir.position_id = ip.ip_id
        JOIN companies c ON ip.company_id = c.company_id
        WHERE ir.status = 'Approved' AND ia.lecturer_id = ?
        ORDER BY ir.applied_at DESC
    ");
    $rStmt->execute([currentUserId()]);
    $registrations = $rStmt->fetchAll();
} else {
    $registrations = $pdo->query("
        SELECT ir.ir_id, u.name AS student_name, ip.title, c.name AS company_name
        FROM internship_registrations ir
        JOIN users u ON ir.student_id = u.user_id
        JOIN internship_positions ip ON ir.position_id = ip.ip_id
        JOIN companies c ON ip.company_id = c.company_id
        WHERE ir.status = 'Approved'
        ORDER BY ir.applied_at DESC
    ")->fetchAll();
}

// Bảng tổng quan: lọc theo role
$evalBase = "
    SELECT ir.ir_id, u.name AS student_name, ip.title,
           ce.score AS company_score, le.score AS lecturer_score,
           ce.comment AS company_comment, le.comment AS lecturer_comment
    FROM internship_registrations ir
    JOIN users u ON ir.student_id = u.user_id
    JOIN internship_positions ip ON ir.position_id = ip.ip_id
    LEFT JOIN company_evaluations ce ON ir.ir_id = ce.registration_id
    LEFT JOIN lecturer_evaluations le ON ir.ir_id = le.registration_id
";

if ($isCompany) {
    $eStmt = $pdo->prepare($evalBase . " WHERE ip.company_id = ? ORDER BY ir.applied_at DESC");
    $eStmt->execute([(int)currentCompanyId()]);
    $evaluations = $eStmt->fetchAll();
} elseif ($isLecturer) {
    $eStmt = $pdo->prepare($evalBase . "
        JOIN internship_assignments ia ON ia.registration_id = ir.ir_id
        WHERE ia.lecturer_id = ? ORDER BY ir.applied_at DESC");
    $eStmt->execute([currentUserId()]);
    $evaluations = $eStmt->fetchAll();
} else {
    $evaluations = $pdo->query($evalBase . " ORDER BY ir.applied_at DESC")->fetchAll();
}

include 'includes/header.php';
?>

<?php if ($message): ?><div class="alert success"><?= e($message) ?></div><?php endif; ?>

<div class="card">
    <h3>Nhập đánh giá</h3>
    <form class="form" method="POST">
        <div>
            <label>Đăng ký đã duyệt</label>
            <select name="registration_id" required>
                <?php foreach ($registrations as $r): ?>
                    <option value="<?= e($r['ir_id']) ?>">#<?= e($r['ir_id']) ?> — <?= e($r['student_name']) ?> — <?= e($r['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <div>
                <label>Loại đánh giá</label>
                <?php if ($isAdmin): ?>
                <select name="type">
                    <option value="company">Company Evaluation</option>
                    <option value="lecturer">Lecturer Evaluation</option>
                </select>
                <?php else: ?>
                <input type="text" value="<?= $isCompany ? 'Company Evaluation (Doanh nghiệp)' : 'Lecturer Evaluation (Giảng viên)' ?>" disabled>
                <?php endif; ?>
            </div>
            <div>
                <label>Điểm số</label>
                <input type="number" name="score" min="0" max="10" step="0.1" required>
            </div>
        </div>
        <div>
            <label>Nhận xét</label>
            <textarea name="comment" placeholder="Nhập nhận xét chi tiết"></textarea>
        </div>
        <button>Lưu đánh giá</button>
    </form>
</div>

<div class="card" style="margin-top:22px;">
    <h3>Tổng quan đánh giá</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Reg ID</th><th>Sinh viên</th><th>Vị trí</th><th>Điểm DN</th><?php if (!$isCompany): ?><th>Điểm GV</th><?php endif; ?></tr></thead>
            <tbody>
            <?php foreach ($evaluations as $eRow): ?>
                <tr>
                    <td>#<?= e($eRow['ir_id']) ?></td>
                    <td><?= e($eRow['student_name']) ?></td>
                    <td><?= e($eRow['title']) ?></td>
                    <td><?= $eRow['company_score'] !== null ? e($eRow['company_score']) : '---' ?></td>
                    <?php if (!$isCompany): ?>
                    <td><?= $eRow['lecturer_score'] !== null ? e($eRow['lecturer_score']) : '---' ?></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
