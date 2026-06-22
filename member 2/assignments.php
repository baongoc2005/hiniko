<?php
require 'config/db.php';
require 'includes/auth.php';
requireLogin(['admin', 'lecturer']);

$pageTitle = "Lecturer Assignments";
$active = "assignments";
$message = "";

$isAdmin = currentRole() === 'admin';
$isLecturer = currentRole() === 'lecturer';

// Chỉ admin được phân công
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $registration_id = (int)$_POST['registration_id'];
    $lecturer_id = (int)$_POST['lecturer_id'];

    $stmt = $pdo->prepare("
        INSERT INTO internship_assignments (registration_id, lecturer_id)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE lecturer_id = VALUES(lecturer_id), assigned_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute([$registration_id, $lecturer_id]);
    $message = "Phân công giảng viên thành công.";
}

$registrations = $pdo->query("
    SELECT ir.ir_id, u.name AS student_name, ip.title, c.name AS company_name
    FROM internship_registrations ir
    JOIN users u ON ir.student_id = u.user_id
    JOIN internship_positions ip ON ir.position_id = ip.ip_id
    JOIN companies c ON ip.company_id = c.company_id
    WHERE ir.status = 'Approved'
    ORDER BY ir.applied_at DESC
")->fetchAll();

$lecturers = $pdo->query("SELECT * FROM users WHERE role = 'lecturer' ORDER BY name")->fetchAll();

// Lecturer chỉ thấy phân công của mình; admin thấy tất cả
if ($isLecturer) {
    $aStmt = $pdo->prepare("
        SELECT ia.*, stu.name AS student_name, stu.code AS student_code, stu.major AS student_major, lec.name AS lecturer_name, ip.title, c.name AS company_name
        FROM internship_assignments ia
        JOIN internship_registrations ir ON ia.registration_id = ir.ir_id
        JOIN users stu ON ir.student_id = stu.user_id
        JOIN users lec ON ia.lecturer_id = lec.user_id
        JOIN internship_positions ip ON ir.position_id = ip.ip_id
        JOIN companies c ON ip.company_id = c.company_id
        WHERE ia.lecturer_id = ?
        ORDER BY ia.assigned_at DESC
    ");
    $aStmt->execute([currentUserId()]);
    $assignments = $aStmt->fetchAll();
} else {
    $assignments = $pdo->query("
        SELECT ia.*, stu.name AS student_name, stu.code AS student_code, stu.major AS student_major, lec.name AS lecturer_name, ip.title, c.name AS company_name
        FROM internship_assignments ia
        JOIN internship_registrations ir ON ia.registration_id = ir.ir_id
        JOIN users stu ON ir.student_id = stu.user_id
        JOIN users lec ON ia.lecturer_id = lec.user_id
        JOIN internship_positions ip ON ir.position_id = ip.ip_id
        JOIN companies c ON ip.company_id = c.company_id
        ORDER BY ia.assigned_at DESC
    ")->fetchAll();
}

include 'includes/header.php';
?>

<?php if ($message): ?><div class="alert success"><?= e($message) ?></div><?php endif; ?>

<?php if ($isAdmin): ?>
<div class="card">
    <h3>Phân công GVHD</h3>
    <form class="form" method="POST">
        <div>
            <label>Đăng ký đã duyệt</label>
            <select name="registration_id" required>
                <?php foreach ($registrations as $r): ?>
                    <option value="<?= e($r['ir_id']) ?>">#<?= e($r['ir_id']) ?> — <?= e($r['student_name']) ?> — <?= e($r['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Giảng viên hướng dẫn</label>
            <select name="lecturer_id" required>
                <?php foreach ($lecturers as $l): ?>
                    <option value="<?= e($l['user_id']) ?>"><?= e($l['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button>Lưu phân công</button>
    </form>
</div>
<?php endif; ?>

<div class="card" style="margin-top:22px;">
    <h3>Danh sách phân công</h3>
    <table>
        <thead><tr><th>ID</th><th>Mã SV</th><th>Sinh viên</th><th>Chuyên ngành</th><th>Vị trí</th><th>Công ty thực tập</th><th>GVHD</th><th>Ngày phân công</th></tr></thead>
        <tbody>
        <?php foreach ($assignments as $a): ?>
            <tr>
                <td>#<?= e($a['ia_id']) ?></td>
                <td><?= $a['student_code'] ? e($a['student_code']) : '<span class="help">—</span>' ?></td>
                <td><?= e($a['student_name']) ?></td>
                <td><?= $a['student_major'] ? e($a['student_major']) : '<span class="help">—</span>' ?></td>
                <td><?= e($a['title']) ?></td>
                <td><?= e($a['company_name']) ?></td>
                <td><?= e($a['lecturer_name']) ?></td>
                <td><?= e($a['assigned_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
