<?php
require 'config/db.php';
require 'includes/auth.php';
requireLogin(['admin', 'lecturer']);

$pageTitle = "Compliance Logs";
$active = "compliance";
$message = "";

$role = currentRole();
$isAdmin = $role === 'admin';
$isLecturer = $role === 'lecturer';
$isStudent = $role === 'student';

// Chỉ admin được ghi log thủ công
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO compliance_logs (registration_id, issue) VALUES (?, ?)");
    $stmt->execute([(int)$_POST['registration_id'], trim($_POST['issue'])]);
    $message = "Đã ghi nhận compliance log.";
}

$registrations = $pdo->query("
    SELECT ir.ir_id, u.name AS student_name, ip.title
    FROM internship_registrations ir
    JOIN users u ON ir.student_id = u.user_id
    JOIN internship_positions ip ON ir.position_id = ip.ip_id
    ORDER BY ir.applied_at DESC
")->fetchAll();

// === Compliance Check tự động: phát hiện SV thiếu nhật ký tuần ===
// Với mỗi đăng ký Approved, tính số tuần kỳ vọng từ start_date đến hôm nay (giới hạn theo độ dài đợt),
// rồi so với số nhật ký đã nộp (Submitted/Late). Thiếu => đưa vào danh sách nguy cơ.

// Dựng query nguy cơ theo role (đặt JOIN đúng vị trí)
$params = [];
$riskJoinRole = "";
$riskWhereRole = "";
if ($isLecturer) {
    $riskJoinRole = " JOIN internship_assignments ia ON ia.registration_id = ir.ir_id ";
    $riskWhereRole = " AND ia.lecturer_id = ? ";
    $params[] = currentUserId();
} elseif ($isStudent) {
    $riskWhereRole = " AND ir.student_id = ? ";
    $params[] = currentUserId();
}

$riskQuery = "
    SELECT ir.ir_id, u.name AS student_name, ip.title, c.name AS company_name,
           p.start_date, p.end_date,
           COUNT(DISTINCT CASE WHEN wj.status IN ('Submitted','Late') THEN wj.week_number END) AS submitted_weeks,
           SUM(CASE WHEN wj.status = 'Late' THEN 1 ELSE 0 END) AS late_count
    FROM internship_registrations ir
    JOIN users u ON ir.student_id = u.user_id
    JOIN internship_positions ip ON ir.position_id = ip.ip_id
    JOIN companies c ON ip.company_id = c.company_id
    JOIN internship_periods p ON ir.period_id = p.period_id
    $riskJoinRole
    LEFT JOIN weekly_journals wj ON wj.registration_id = ir.ir_id
    WHERE ir.status = 'Approved' $riskWhereRole
    GROUP BY ir.ir_id, u.name, ip.title, c.name, p.start_date, p.end_date
";

$riskStmt = $pdo->prepare($riskQuery);
$riskStmt->execute($params);
$riskRows = $riskStmt->fetchAll();

$today = new DateTimeImmutable('today');
$riskList = [];
foreach ($riskRows as $row) {
    $start = new DateTimeImmutable($row['start_date']);
    $end = new DateTimeImmutable($row['end_date']);

    if ($today < $start) {
        continue; // đợt chưa bắt đầu
    }

    // Số tuần kỳ vọng = số tuần trôi qua kể từ start, giới hạn bởi tổng số tuần của đợt
    $effective = $today < $end ? $today : $end;
    $expectedWeeks = (int)floor($start->diff($effective)->days / 7) + 1;
    $totalWeeks = (int)floor($start->diff($end)->days / 7) + 1;
    if ($expectedWeeks > $totalWeeks) {
        $expectedWeeks = $totalWeeks;
    }

    $submitted = (int)$row['submitted_weeks'];
    $missing = $expectedWeeks - $submitted;

    if ($missing > 0 || (int)$row['late_count'] > 0) {
        $riskList[] = [
            'ir_id' => $row['ir_id'],
            'student_name' => $row['student_name'],
            'title' => $row['title'],
            'company_name' => $row['company_name'],
            'expected' => $expectedWeeks,
            'submitted' => $submitted,
            'missing' => max(0, $missing),
            'late' => (int)$row['late_count'],
        ];
    }
}

// Danh sách log thủ công theo role
$logBase = "
    SELECT cl.*, u.name AS student_name, ip.title
    FROM compliance_logs cl
    JOIN internship_registrations ir ON cl.registration_id = ir.ir_id
    JOIN users u ON ir.student_id = u.user_id
    JOIN internship_positions ip ON ir.position_id = ip.ip_id
";

if ($isStudent) {
    $lStmt = $pdo->prepare($logBase . " WHERE ir.student_id = ? ORDER BY cl.created_at DESC");
    $lStmt->execute([currentUserId()]);
    $logs = $lStmt->fetchAll();
} elseif ($isLecturer) {
    $lStmt = $pdo->prepare($logBase . "
        JOIN internship_assignments ia ON ia.registration_id = ir.ir_id
        WHERE ia.lecturer_id = ? ORDER BY cl.created_at DESC");
    $lStmt->execute([currentUserId()]);
    $logs = $lStmt->fetchAll();
} else {
    $logs = $pdo->query($logBase . " ORDER BY cl.created_at DESC")->fetchAll();
}

include 'includes/header.php';
?>

<?php if ($message): ?><div class="alert success"><?= e($message) ?></div><?php endif; ?>

<div class="card">
    <h3>Danh sách nguy cơ không đủ điều kiện (tự động)</h3>
    <p class="help">Hệ thống tự tính số tuần kỳ vọng theo lịch đợt thực tập và đối chiếu nhật ký đã nộp.</p>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Reg ID</th><th>Sinh viên</th><th>Vị trí</th><th>Doanh nghiệp</th><th>Tuần kỳ vọng</th><th>Đã nộp</th><th>Thiếu</th><th>Nộp trễ</th></tr></thead>
            <tbody>
            <?php if (!$riskList): ?>
                <tr><td colspan="8">Không có sinh viên nào thuộc diện nguy cơ.</td></tr>
            <?php endif; ?>
            <?php foreach ($riskList as $rk): ?>
                <tr>
                    <td>#<?= e($rk['ir_id']) ?></td>
                    <td><?= e($rk['student_name']) ?></td>
                    <td><?= e($rk['title']) ?></td>
                    <td><?= e($rk['company_name']) ?></td>
                    <td><?= e($rk['expected']) ?></td>
                    <td><?= e($rk['submitted']) ?></td>
                    <td><?php if ($rk['missing'] > 0): ?><span class="badge rejected">Thiếu <?= e($rk['missing']) ?> tuần</span><?php else: ?>—<?php endif; ?></td>
                    <td><?php if ($rk['late'] > 0): ?><span class="badge pending"><?= e($rk['late']) ?> lần</span><?php else: ?>—<?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($isAdmin): ?>
<div class="card" style="margin-top:22px;">
    <h3>Ghi nhận vi phạm / cảnh báo</h3>
    <form class="form" method="POST">
        <div>
            <label>Đăng ký</label>
            <select name="registration_id" required>
                <?php foreach ($registrations as $r): ?>
                    <option value="<?= e($r['ir_id']) ?>">#<?= e($r['ir_id']) ?> — <?= e($r['student_name']) ?> — <?= e($r['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Nội dung vấn đề</label>
            <textarea name="issue" placeholder="VD: Student missed weekly journal submission in week 3." required></textarea>
        </div>
        <button>Lưu compliance log</button>
    </form>
</div>
<?php endif; ?>

<div class="card" style="margin-top:22px;">
    <h3>Danh sách compliance logs</h3>
    <table>
        <thead><tr><th>ID</th><th>Sinh viên</th><th>Vị trí</th><th>Issue</th><th>Ngày ghi nhận</th></tr></thead>
        <tbody>
        <?php foreach ($logs as $l): ?>
            <tr>
                <td>#<?= e($l['cl_id']) ?></td>
                <td><?= e($l['student_name']) ?></td>
                <td><?= e($l['title']) ?></td>
                <td><?= e($l['issue']) ?></td>
                <td><?= e($l['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
