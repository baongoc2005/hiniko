<?php
require 'config/db.php';
require 'includes/auth.php';
requireLogin(['admin', 'student', 'lecturer', 'company']);

$pageTitle = "Final Grades";
$active = "final_grades";
$message = "";

// Trọng số điểm tổng kết (DN 60%, GV 40%)
const COMPANY_WEIGHT = 0.6;
const LECTURER_WEIGHT = 0.4;

$role = currentRole();
$isAdmin = $role === 'admin';

// Chỉ admin được tính lại điểm tổng kết
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $rows = $pdo->query("
        SELECT ir.ir_id, ce.score AS company_score, le.score AS lecturer_score
        FROM internship_registrations ir
        JOIN company_evaluations ce ON ir.ir_id = ce.registration_id
        JOIN lecturer_evaluations le ON ir.ir_id = le.registration_id
    ")->fetchAll();

    $stmt = $pdo->prepare("
        INSERT INTO final_grades (registration_id, company_score, lecturer_score, final_score)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            company_score = VALUES(company_score),
            lecturer_score = VALUES(lecturer_score),
            final_score = VALUES(final_score),
            created_at = CURRENT_TIMESTAMP
    ");

    foreach ($rows as $r) {
        $final = ($r['company_score'] * COMPANY_WEIGHT) + ($r['lecturer_score'] * LECTURER_WEIGHT);
        $stmt->execute([$r['ir_id'], $r['company_score'], $r['lecturer_score'], $final]);
    }

    $message = "Đã tính lại điểm tổng kết.";
}

$baseSelect = "
    SELECT ir.ir_id, u.name AS student_name, ip.title, c.name AS company_name,
           ce.score AS company_score, le.score AS lecturer_score,
           fg.final_score, fg.created_at
    FROM internship_registrations ir
    JOIN users u ON ir.student_id = u.user_id
    JOIN internship_positions ip ON ir.position_id = ip.ip_id
    JOIN companies c ON ip.company_id = c.company_id
    LEFT JOIN company_evaluations ce ON ir.ir_id = ce.registration_id
    LEFT JOIN lecturer_evaluations le ON ir.ir_id = le.registration_id
    LEFT JOIN final_grades fg ON ir.ir_id = fg.registration_id
";

if ($role === 'student') {
    $gStmt = $pdo->prepare($baseSelect . " WHERE ir.student_id = ? ORDER BY ir.applied_at DESC");
    $gStmt->execute([currentUserId()]);
    $grades = $gStmt->fetchAll();
} elseif ($role === 'lecturer') {
    $gStmt = $pdo->prepare($baseSelect . "
        JOIN internship_assignments ia ON ia.registration_id = ir.ir_id
        WHERE ia.lecturer_id = ? ORDER BY ir.applied_at DESC");
    $gStmt->execute([currentUserId()]);
    $grades = $gStmt->fetchAll();
} elseif ($role === 'company') {
    $gStmt = $pdo->prepare($baseSelect . " WHERE ip.company_id = ? ORDER BY ir.applied_at DESC");
    $gStmt->execute([(int)currentCompanyId()]);
    $grades = $gStmt->fetchAll();
} else {
    $grades = $pdo->query($baseSelect . " ORDER BY ir.applied_at DESC")->fetchAll();
}

include 'includes/header.php';
?>

<?php if ($message): ?><div class="alert success"><?= e($message) ?></div><?php endif; ?>

<?php if ($isAdmin): ?>
<div class="card">
    <h3>Tính điểm tổng kết</h3>
    <p class="help">Công thức: Điểm cuối = Điểm DN × <?= COMPANY_WEIGHT ?> + Điểm GV × <?= LECTURER_WEIGHT ?></p>
    <form method="POST">
        <button>Tính lại điểm tổng kết</button>
    </form>
</div>
<?php endif; ?>

<div class="card" style="margin-top:22px;">
    <h3>Bảng điểm tổng kết</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Reg ID</th><th>Sinh viên</th><th>Vị trí</th><th>Doanh nghiệp</th><th>Điểm DN</th><th>Điểm GV</th><th>Điểm cuối</th><th>Trạng thái</th></tr>
            </thead>
            <tbody>
            <?php foreach ($grades as $g): 
                $calculated = null;
                if ($g['company_score'] !== null && $g['lecturer_score'] !== null) {
                    $calculated = ($g['company_score'] * COMPANY_WEIGHT) + ($g['lecturer_score'] * LECTURER_WEIGHT);
                }
                $final = $g['final_score'] ?? $calculated;
            ?>
                <tr>
                    <td>#<?= e($g['ir_id']) ?></td>
                    <td><?= e($g['student_name']) ?></td>
                    <td><?= e($g['title']) ?></td>
                    <td><?= e($g['company_name']) ?></td>
                    <td><?= $g['company_score'] !== null ? e($g['company_score']) : '---' ?></td>
                    <td><?= $g['lecturer_score'] !== null ? e($g['lecturer_score']) : '---' ?></td>
                    <td><b><?= $final !== null ? number_format((float)$final, 2) : 'Waiting' ?></b></td>
                    <td>
                        <?php if ($final === null): ?>
                            <span class="badge pending">Waiting</span>
                        <?php elseif ($final >= 5): ?>
                            <span class="badge pass">Pass</span>
                        <?php else: ?>
                            <span class="badge fail">Fail</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
